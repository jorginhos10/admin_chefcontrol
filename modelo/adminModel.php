<?php
// chefcontrol-sup/modelo/adminModel.php

require_once __DIR__ . '/../config/config.php';

class AdminModel {
    private PDO $db;    // chefcontrol (restaurantes)
    private PDO $dbSup; // chefcontrol_sup (admins)

    public function __construct() {
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $host    = SupConfig::DB_HOST;
        $charset = SupConfig::DB_CHARSET;
        $user    = SupConfig::DB_USER;
        $pass    = SupConfig::DB_PASS;

        $this->db    = new PDO("mysql:host={$host};dbname=" . SupConfig::DB_NAME     . ";charset={$charset}", $user, $pass, $opts);
        $this->dbSup = new PDO("mysql:host={$host};dbname=" . SupConfig::DB_NAME_SUP . ";charset={$charset}", $user, $pass, $opts);

        $this->migrar();
    }

    private function migrar(): void {
        $cols = [
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_estado VARCHAR(20) NOT NULL DEFAULT 'pendiente'",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_cedula_frente VARCHAR(255) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_cedula_trasera VARCHAR(255) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_logo VARCHAR(255) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_foto_negocio VARCHAR(255) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_rechazo_motivo TEXT NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_cedula_frente_rechazo VARCHAR(500) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_cedula_trasera_rechazo VARCHAR(500) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_logo_rechazo VARCHAR(500) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS doc_foto_negocio_rechazo VARCHAR(500) NULL",
            "ALTER TABLE comercios ADD COLUMN IF NOT EXISTS verificado TINYINT(1) NOT NULL DEFAULT 0",
        ];
        foreach ($cols as $sql) {
            try { $this->db->exec($sql); } catch (\Throwable $e) {}
        }
    }

    public function verificar(string $username, string $password): ?array {
        $stmt = $this->dbSup->prepare(
            "SELECT id, username, nombre, email, password
             FROM sup_admins
             WHERE username = :u AND activo = 1
             LIMIT 1"
        );
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();
        if ($row && password_verify($password, $row['password'])) {
            $this->dbSup->prepare("UPDATE sup_admins SET ultimo_login = NOW() WHERE id = :id")
                        ->execute([':id' => $row['id']]);
            unset($row['password']);
            return $row;
        }
        return null;
    }

    public function estadisticasGlobales(): array {
        try {
            $comercios = $this->db->query(
                "SELECT COUNT(*) AS total,
                        SUM(activo = 1) AS activos,
                        SUM(activo = 0) AS inactivos
                 FROM comercios"
            )->fetch();

            $usuarios = (int)$this->db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

            $recientes = $this->db->query(
                "SELECT nombre, email, created_at, activo
                 FROM comercios
                 ORDER BY created_at DESC
                 LIMIT 8"
            )->fetchAll();

            return [
                'total_restaurantes' => (int)($comercios['total']   ?? 0),
                'activos'            => (int)($comercios['activos']  ?? 0),
                'inactivos'          => (int)($comercios['inactivos']?? 0),
                'total_usuarios'     => $usuarios,
                'recientes'          => $recientes,
            ];
        } catch (\Throwable $e) {
            return [
                'total_restaurantes' => 0,
                'activos'            => 0,
                'inactivos'          => 0,
                'total_usuarios'     => 0,
                'recientes'          => [],
                'error'              => $e->getMessage(),
            ];
        }
    }

    public function crearRestaurante(string $nombre, string $slug, string $email,
                                     string $tipo, string $adminNombre,
                                     string $adminUsername, string $adminPassword): array {
        if (empty($nombre) || empty($slug) || empty($adminUsername) || empty($adminPassword)) {
            return ['ok' => false, 'msg' => 'Completa todos los campos obligatorios.'];
        }
        // Slug: solo letras, números y guiones
        $slug = strtolower(trim(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', $slug)), '-'));
        if (empty($slug)) {
            return ['ok' => false, 'msg' => 'El slug contiene caracteres inválidos.'];
        }
        try {
            // Verificar unicidad del slug
            $check = $this->db->prepare("SELECT COUNT(*) FROM comercios WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetchColumn() > 0) {
                return ['ok' => false, 'msg' => "El slug '{$slug}' ya está en uso."];
            }
            $this->db->beginTransaction();

            // Crear comercio
            $this->db->prepare(
                "INSERT INTO comercios (nombre, slug, email, tipo, activo)
                 VALUES (?, ?, ?, ?, 1)"
            )->execute([$nombre, $slug, $email ?: null, $tipo]);
            $comercioId = (int)$this->db->lastInsertId();

            // Crear primer usuario admin (marcado como propietario)
            $this->db->prepare(
                "INSERT INTO usuarios (comercio_id, username, password, nombre, rol, activo, propietario)
                 VALUES (?, ?, ?, ?, 'admin', 1, 1)"
            )->execute([
                $comercioId,
                $adminUsername,
                password_hash($adminPassword, PASSWORD_DEFAULT),
                $adminNombre ?: $adminUsername,
            ]);

            $this->db->commit();
            return ['ok' => true, 'id' => $comercioId, 'slug' => $slug];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function editarRestaurante(int $id, string $nombre, string $email, string $tipo, string $slug): array {
        if (empty($nombre) || empty($slug)) {
            return ['ok' => false, 'msg' => 'Nombre y slug son obligatorios.'];
        }
        $slug = strtolower(trim(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', $slug)), '-'));
        try {
            $check = $this->db->prepare("SELECT COUNT(*) FROM comercios WHERE slug = ? AND id != ?");
            $check->execute([$slug, $id]);
            if ($check->fetchColumn() > 0) {
                return ['ok' => false, 'msg' => "El slug '{$slug}' ya está en uso."];
            }
            $this->db->prepare(
                "UPDATE comercios SET nombre=?, email=?, tipo=?, slug=? WHERE id=?"
            )->execute([$nombre, $email ?: null, $tipo, $slug, $id]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function toggleActivo(int $id): array {
        try {
            $this->db->prepare("UPDATE comercios SET activo = 1 - activo WHERE id = ?")->execute([$id]);
            $nuevo = (int)$this->db->query("SELECT activo FROM comercios WHERE id = $id")->fetchColumn();
            return ['ok' => true, 'activo' => $nuevo];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function toggleVerificado(int $id): array {
        try {
            $this->db->prepare("UPDATE comercios SET verificado = 1 - verificado WHERE id = ?")->execute([$id]);
            $nuevo = (int)$this->db->query("SELECT verificado FROM comercios WHERE id = $id")->fetchColumn();
            return ['ok' => true, 'verificado' => $nuevo];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function generarAccesoTemporal(int $id): array {
        try {
            $row = $this->db->prepare(
                "SELECT u.id, u.username, u.nombre, u.email, u.rol, u.avatar, u.login_config,
                        c.nombre AS comercio_nombre, c.slug AS comercio_slug, c.activo
                 FROM usuarios u JOIN comercios c ON c.id = u.comercio_id
                 WHERE u.comercio_id = ? AND u.rol = 'admin' AND u.activo = 1
                 LIMIT 1"
            );
            $row->execute([$id]);
            $u = $row->fetch();
            if (!$u) {
                return ['ok' => false, 'msg' => 'No hay usuario admin activo en este restaurante.'];
            }
            if (!(int)$u['activo']) {
                return ['ok' => false, 'msg' => 'El restaurante está desactivado.'];
            }
            return ['ok' => true, 'usuario' => $u, 'comercio_id' => $id];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    // Token de un solo uso para que el panel cliente (otro subdominio, sesión
    // distinta) reconozca la impersonación sin depender de compartir cookies.
    public function generarTokenImpersonacion(int $usuarioId, int $comercioId): array {
        try {
            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS impersonacion_tokens (
                    token VARCHAR(64) NOT NULL PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    comercio_id INT NOT NULL,
                    expira_en DATETIME NOT NULL,
                    usado TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            $this->db->exec("DELETE FROM impersonacion_tokens WHERE expira_en < NOW()");

            $token  = bin2hex(random_bytes(24));
            $expira = date('Y-m-d H:i:s', strtotime('+2 minutes'));
            $this->db->prepare(
                "INSERT INTO impersonacion_tokens (token, usuario_id, comercio_id, expira_en) VALUES (?, ?, ?, ?)"
            )->execute([$token, $usuarioId, $comercioId, $expira]);

            return ['ok' => true, 'token' => $token];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function obtenerComercios(): array {
        try {
            return $this->db->query(
                "SELECT id, nombre, slug, email, tipo, activo, verificado, created_at FROM comercios ORDER BY created_at DESC"
            )->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public function obtenerPorId(int $id): ?array {
        try {
            $s = $this->db->prepare(
                "SELECT id, nombre, slug, email, tipo, activo, verificado, plan, plan_vence, plan_notas, modulos_config, created_at
                 FROM comercios WHERE id = ?"
            );
            $s->execute([$id]);
            return $s->fetch() ?: null;
        } catch (\Throwable $e) { return null; }
    }

    public function actualizarPlan(int $id, string $plan, ?string $vence, ?string $notas): array {
        $planesValidos = ['gratuito','basico','pro','enterprise'];
        if (!in_array($plan, $planesValidos)) {
            return ['ok' => false, 'msg' => 'Plan no válido.'];
        }
        try {
            $this->db->prepare(
                "UPDATE comercios SET plan=?, plan_vence=?, plan_notas=? WHERE id=?"
            )->execute([$plan, $vence ?: null, $notas ?: null, $id]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function obtenerTotalUsuarios(int $comercio_id): int {
        try {
            $s = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE comercio_id = ? AND activo = 1");
            $s->execute([$comercio_id]);
            return (int)$s->fetchColumn();
        } catch (\Throwable $e) { return 0; }
    }

    // ── Chat superadmin ───────────────────────────────────────────────────────

    public function obtenerConversaciones(): array {
        try {
            return $this->db->query(
                "SELECT c.id, c.nombre, c.slug,
                        COUNT(sc.id)                                      AS total_mensajes,
                        SUM(sc.leido = 0 AND sc.emisor = 'restaurante')   AS no_leidos,
                        MAX(sc.created_at)                                AS ultimo_mensaje,
                        (SELECT sc2.mensaje FROM sup_chat sc2
                         WHERE sc2.comercio_id = c.id
                         ORDER BY sc2.id DESC LIMIT 1)                    AS ultimo_texto
                 FROM comercios c
                 LEFT JOIN sup_chat sc ON sc.comercio_id = c.id
                 WHERE c.activo = 1
                 GROUP BY c.id
                 ORDER BY no_leidos DESC, ultimo_mensaje DESC, c.nombre ASC"
            )->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public function obtenerMensajes(int $comercio_id, int $desde = 0): array {
        try {
            $s = $this->db->prepare(
                "SELECT id, emisor, mensaje, leido, created_at
                 FROM sup_chat WHERE comercio_id = ? AND id > ?
                 ORDER BY id ASC LIMIT 200"
            );
            $s->execute([$comercio_id, $desde]);
            return $s->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public function enviarMensaje(int $comercio_id, string $emisor, string $mensaje): array {
        try {
            $this->db->prepare(
                "INSERT INTO sup_chat (comercio_id, emisor, mensaje) VALUES (?, ?, ?)"
            )->execute([$comercio_id, $emisor, mb_substr(trim($mensaje), 0, 2000)]);
            return ['ok' => true, 'id' => (int)$this->db->lastInsertId()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function marcarLeidos(int $comercio_id, string $emisor_opuesto): void {
        try {
            $this->db->prepare(
                "UPDATE sup_chat SET leido = 1 WHERE comercio_id = ? AND emisor = ? AND leido = 0"
            )->execute([$comercio_id, $emisor_opuesto]);
        } catch (\Throwable $e) {}
    }

    public function contarNoLeidos(): int {
        try {
            return (int)$this->db->query(
                "SELECT COUNT(*) FROM sup_chat WHERE emisor = 'restaurante' AND leido = 0"
            )->fetchColumn();
        } catch (\Throwable $e) { return 0; }
    }

    // ── Planes ────────────────────────────────────────────────────────────────

    public function obtenerPlanes(): array {
        try {
            return $this->dbSup->query("SELECT * FROM planes ORDER BY orden ASC, id ASC")->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public function obtenerPlan(int $id): ?array {
        try {
            $s = $this->dbSup->prepare("SELECT * FROM planes WHERE id = ? LIMIT 1");
            $s->execute([$id]);
            return $s->fetch() ?: null;
        } catch (\Throwable $e) { return null; }
    }

    public function crearPlan(string $nombre, string $slug, string $desc, float $precio,
                               string $periodo, string $color, array $cars,
                               int $destacado, int $orden, array $modulos = []): array {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', $slug))));
        if (!$nombre || !$slug) return ['ok' => false, 'msg' => 'Nombre y slug son obligatorios.'];
        try {
            $this->dbSup->prepare(
                "INSERT INTO planes (nombre, slug, descripcion, precio, periodo, color, caracteristicas, modulos, destacado, activo, orden)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)"
            )->execute([$nombre, $slug, $desc ?: null, $precio, $periodo, $color,
                        json_encode(array_values(array_filter(array_map('trim', $cars)))),
                        json_encode(array_values($modulos)),
                        $destacado ? 1 : 0, $orden ?: 0]);
            return ['ok' => true, 'id' => (int)$this->dbSup->lastInsertId()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => str_contains($e->getMessage(), 'Duplicate') ? "El slug '{$slug}' ya existe." : $e->getMessage()];
        }
    }

    public function editarPlan(int $id, string $nombre, string $slug, string $desc, float $precio,
                                string $periodo, string $color, array $cars,
                                int $destacado, int $orden, array $modulos = []): array {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', $slug))));
        if (!$nombre || !$slug) return ['ok' => false, 'msg' => 'Nombre y slug son obligatorios.'];
        try {
            $this->dbSup->prepare(
                "UPDATE planes SET nombre=?, slug=?, descripcion=?, precio=?, periodo=?, color=?,
                 caracteristicas=?, modulos=?, destacado=?, orden=? WHERE id=?"
            )->execute([$nombre, $slug, $desc ?: null, $precio, $periodo, $color,
                        json_encode(array_values(array_filter(array_map('trim', $cars)))),
                        json_encode(array_values($modulos)),
                        $destacado ? 1 : 0, $orden ?: 0, $id]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => str_contains($e->getMessage(), 'Duplicate') ? "El slug '{$slug}' ya existe." : $e->getMessage()];
        }
    }

    public function togglePlanActivo(int $id): array {
        try {
            $this->dbSup->prepare("UPDATE planes SET activo = 1 - activo WHERE id = ?")->execute([$id]);
            $nuevo = (int)$this->dbSup->query("SELECT activo FROM planes WHERE id = $id")->fetchColumn();
            return ['ok' => true, 'activo' => $nuevo];
        } catch (\Throwable $e) { return ['ok' => false, 'msg' => $e->getMessage()]; }
    }

    public function togglePlanDestacado(int $id): array {
        try {
            // Solo uno puede estar destacado a la vez
            $this->dbSup->exec("UPDATE planes SET destacado = 0");
            $this->dbSup->prepare("UPDATE planes SET destacado = 1 WHERE id = ?")->execute([$id]);
            return ['ok' => true];
        } catch (\Throwable $e) { return ['ok' => false, 'msg' => $e->getMessage()]; }
    }

    public function eliminarPlan(int $id): array {
        try {
            $this->dbSup->prepare("DELETE FROM planes WHERE id = ?")->execute([$id]);
            return ['ok' => true];
        } catch (\Throwable $e) { return ['ok' => false, 'msg' => $e->getMessage()]; }
    }

    // ── Facturación ───────────────────────────────────────────────────────────

    public function obtenerFacturacion(): array {
        try {
            // Por cada comercio, traemos su último pago desde chefcontrol_sup.pagos
            $comercios = $this->db->query(
                "SELECT id, nombre, slug, email, plan, plan_vence, activo, created_at, doc_estado FROM comercios ORDER BY nombre ASC"
            )->fetchAll();

            $ids = array_column($comercios, 'id');
            $ultimosPagos = [];
            if ($ids) {
                $in   = implode(',', array_map('intval', $ids));
                $rows = $this->dbSup->query(
                    "SELECT p.comercio_id, p.fecha, p.monto, p.metodo
                     FROM pagos p
                     INNER JOIN (
                         SELECT comercio_id, MAX(fecha) AS max_fecha
                         FROM pagos GROUP BY comercio_id
                     ) ult ON ult.comercio_id = p.comercio_id AND ult.max_fecha = p.fecha
                     WHERE p.comercio_id IN ($in)"
                )->fetchAll();
                foreach ($rows as $r) $ultimosPagos[$r['comercio_id']] = $r;
            }

            $hoy = new \DateTime('today');
            $resultado = [];
            foreach ($comercios as $c) {
                $pago      = $ultimosPagos[$c['id']] ?? null;
                $vence     = $c['plan_vence'] ? new \DateTime($c['plan_vence']) : null;
                $diasRestantes = $vence ? (int)$hoy->diff($vence)->days * ($vence >= $hoy ? 1 : -1) : null;

                if (($c['doc_estado'] ?? 'pendiente') !== 'verificado') {
                    $estadoPago = 'en_proceso';
                } elseif ($c['plan'] === 'gratuito' || !$vence) {
                    $estadoPago = 'gratuito';
                } elseif ($diasRestantes < 0) {
                    $estadoPago = 'vencido';
                } elseif ($diasRestantes <= 7) {
                    $estadoPago = 'por_vencer';
                } else {
                    $estadoPago = 'al_dia';
                }

                $resultado[] = [
                    'id'            => $c['id'],
                    'nombre'        => $c['nombre'],
                    'slug'          => $c['slug'],
                    'email'         => $c['email'],
                    'plan'          => $c['plan'],
                    'plan_vence'    => $c['plan_vence'],
                    'activo'        => $c['activo'],
                    'created_at'    => $c['created_at'],
                    'ultimo_pago'   => $pago['fecha']   ?? null,
                    'ultimo_monto'  => $pago['monto']   ?? null,
                    'ultimo_metodo' => $pago['metodo']  ?? null,
                    'dias_restantes'=> $diasRestantes,
                    'estado_pago'   => $estadoPago,
                ];
            }
            return $resultado;
        } catch (\Throwable $e) { return []; }
    }

    public function registrarPago(int $comercio_id, float $monto, string $fecha,
                                   string $metodo, string $periodo_hasta,
                                   string $referencia = '', string $notas = ''): array {
        try {
            // Actualizar plan_vence en comercios
            $this->db->prepare(
                "UPDATE comercios SET plan_vence = ? WHERE id = ?"
            )->execute([$periodo_hasta ?: null, $comercio_id]);

            // Si el plan sigue siendo gratuito, cambiarlo a básico al registrar un pago
            $this->db->prepare(
                "UPDATE comercios SET plan = 'basico' WHERE id = ? AND plan = 'gratuito'"
            )->execute([$comercio_id]);

            // Insertar pago
            try { $this->dbSup->exec("ALTER TABLE pagos ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'pagado'"); } catch(\Throwable $e) {}
            try { $this->dbSup->exec("ALTER TABLE pagos ADD COLUMN periodo_desde DATE NULL"); } catch(\Throwable $e) {}
            $this->dbSup->prepare(
                "INSERT INTO pagos (comercio_id, monto, fecha, periodo_desde, periodo_hasta, metodo, referencia, notas, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pagado')"
            )->execute([
                $comercio_id,
                $monto,
                $fecha,
                $fecha,
                $periodo_hasta ?: null,
                $metodo,
                $referencia ?: null,
                $notas ?: null,
            ]);
            return ['ok' => true, 'id' => (int)$this->dbSup->lastInsertId()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function historialPagos(int $comercio_id): array {
        try {
            $s = $this->dbSup->prepare(
                "SELECT id, monto, fecha, periodo_desde, periodo_hasta, metodo, referencia, notas,
                        COALESCE(estado,'pagado') AS estado, created_at
                 FROM pagos WHERE comercio_id = ? ORDER BY fecha DESC LIMIT 50"
            );
            $s->execute([$comercio_id]);
            return $s->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    // ── Financiera (movimientos de dinero entrante) ──────────────────────────

    public function obtenerFinanciera(string $desde = '', string $hasta = ''): array {
        $vacio = [
            'movimientos' => [], 'total_periodo' => 0.0, 'total_historico' => 0.0,
            'total_mes_actual' => 0.0, 'cantidad_periodo' => 0, 'por_metodo' => [],
        ];
        try {
            $desde = $desde ?: date('Y-m-01');
            $hasta = $hasta ?: date('Y-m-d');

            $stmt = $this->dbSup->prepare(
                "SELECT id, comercio_id, monto, fecha, periodo_desde, periodo_hasta, metodo,
                        referencia, notas, COALESCE(estado,'pagado') AS estado, created_at
                 FROM pagos
                 WHERE fecha BETWEEN ? AND ?
                 ORDER BY fecha DESC, id DESC"
            );
            $stmt->execute([$desde, $hasta]);
            $pagos = $stmt->fetchAll();

            // Nombre del comercio de cada movimiento
            $ids = array_unique(array_filter(array_column($pagos, 'comercio_id')));
            $nombres = [];
            if ($ids) {
                $in   = implode(',', array_map('intval', $ids));
                $rows = $this->db->query("SELECT id, nombre, slug FROM comercios WHERE id IN ($in)")->fetchAll();
                foreach ($rows as $r) $nombres[$r['id']] = $r;
            }
            foreach ($pagos as &$p) {
                $p['comercio_nombre'] = $nombres[$p['comercio_id']]['nombre'] ?? ('Comercio #' . $p['comercio_id']);
                $p['comercio_slug']   = $nombres[$p['comercio_id']]['slug']   ?? '';
            }
            unset($p);

            $totalPeriodo = array_sum(array_map('floatval', array_column($pagos, 'monto')));

            $totalHistorico = (float)$this->dbSup->query("SELECT COALESCE(SUM(monto),0) FROM pagos")->fetchColumn();

            $stmtMes = $this->dbSup->prepare("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE fecha BETWEEN ? AND ?");
            $stmtMes->execute([date('Y-m-01'), date('Y-m-d')]);
            $totalMesActual = (float)$stmtMes->fetchColumn();

            $porMetodo = [];
            foreach ($pagos as $p) {
                $m = $p['metodo'] ?: 'otro';
                $porMetodo[$m] = ($porMetodo[$m] ?? 0) + (float)$p['monto'];
            }
            arsort($porMetodo);

            return [
                'movimientos'      => $pagos,
                'total_periodo'    => $totalPeriodo,
                'total_historico'  => $totalHistorico,
                'total_mes_actual' => $totalMesActual,
                'cantidad_periodo' => count($pagos),
                'por_metodo'       => $porMetodo,
            ];
        } catch (\Throwable $e) { return $vacio; }
    }

    // ── Invitaciones de registro ──────────────────────────────────────────────

    public function generarInvitacion(): array {
        try {
            // Limpiar tokens expirados
            $this->dbSup->exec("DELETE FROM registro_invitaciones WHERE expira_en < NOW()");

            $token    = bin2hex(random_bytes(24)); // 48 chars
            $expira   = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $this->dbSup->prepare(
                "INSERT INTO registro_invitaciones (token, expira_en) VALUES (?, ?)"
            )->execute([$token, $expira]);
            return ['ok' => true, 'token' => $token, 'expira_en' => $expira];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function enviarInvitacionSMS(string $telefono, string $url): array {
        $digits = preg_replace('/\D/', '', $telefono);
        if (strlen($digits) === 10) $digits = '57' . $digits;
        if (strlen($digits) < 11) {
            return ['ok' => false, 'msg' => 'Ingresa un número de teléfono válido.'];
        }
        $destino = '+' . $digits;

        $mensaje = "Has sido invitado a registrarte en ChefControl. Completa tu registro aqui: {$url} "
                 . "(el link expira en 24 horas y solo puede usarse una vez).";

        $envio = SupConfig::enviarSMS($destino, $mensaje);
        if (!$envio['ok']) {
            return ['ok' => false, 'msg' => $envio['msg'] ?? 'No se pudo enviar el SMS.'];
        }

        try {
            $this->dbSup->exec(
                "CREATE TABLE IF NOT EXISTS sms_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    comercio_id INT NULL,
                    telefono VARCHAR(20) NOT NULL,
                    mensaje TEXT NOT NULL,
                    tipo VARCHAR(40) NOT NULL DEFAULT 'general',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_comercio (comercio_id),
                    INDEX idx_fecha (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            $this->dbSup->prepare(
                "INSERT INTO sms_log (comercio_id, telefono, mensaje, tipo) VALUES (NULL, ?, ?, 'invitacion_registro')"
            )->execute([$destino, $mensaje]);
        } catch (\Throwable $e) {}

        return ['ok' => true];
    }

    public function validarInvitacion(string $token): bool {
        try {
            $s = $this->dbSup->prepare(
                "SELECT id FROM registro_invitaciones
                 WHERE token = ? AND usado = 0 AND expira_en > NOW() LIMIT 1"
            );
            $s->execute([$token]);
            return (bool)$s->fetch();
        } catch (\Throwable $e) { return false; }
    }

    public function marcarInvitacionUsada(string $token): void {
        try {
            $this->dbSup->prepare(
                "UPDATE registro_invitaciones SET usado = 1 WHERE token = ?"
            )->execute([$token]);
        } catch (\Throwable $e) {}
    }

    // ── Módulos ───────────────────────────────────────────────────────────────

    public function obtenerModulosDesactivados(int $id): array {
        try {
            $json = $this->db->prepare("SELECT modulos_config FROM comercios WHERE id = ?")->execute([$id])
                ? $this->db->query("SELECT modulos_config FROM comercios WHERE id = $id")->fetchColumn()
                : null;
            $decoded = $json ? json_decode($json, true) : [];
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) { return []; }
    }

    public function guardarModulos(int $id, array $desactivados): array {
        try {
            $this->db->prepare(
                "UPDATE comercios SET modulos_config = ? WHERE id = ?"
            )->execute([json_encode(array_values($desactivados)), $id]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    // ── Documentos de verificación ────────────────────────────────────────────

    public function obtenerDocumentos(int $id): array {
        try {
            $s = $this->db->prepare(
                "SELECT id, nombre, doc_cedula_frente, doc_cedula_trasera,
                        doc_logo, doc_foto_negocio, doc_estado, doc_rechazo_motivo,
                        doc_cedula_frente_rechazo, doc_cedula_trasera_rechazo,
                        doc_logo_rechazo, doc_foto_negocio_rechazo
                 FROM comercios WHERE id = ?"
            );
            $s->execute([$id]);
            $row = $s->fetch();
            if (!$row) return ['ok' => false, 'msg' => 'Restaurante no encontrado.'];
            return ['ok' => true, 'docs' => $row];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function aprobarDocumentos(int $id): array {
        try {
            $this->db->prepare(
                "UPDATE comercios SET
                    doc_estado = 'verificado', verificado = 1, doc_rechazo_motivo = NULL,
                    doc_cedula_frente_rechazo = NULL, doc_cedula_trasera_rechazo = NULL,
                    doc_logo_rechazo = NULL, doc_foto_negocio_rechazo = NULL
                 WHERE id = ?"
            )->execute([$id]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * $rechazos = ['cedula_frente' => 'razón', 'logo' => 'razón', ...]
     * Claves ausentes o con valor vacío = ese documento se considera OK.
     */
    public function rechazarDocumentos(int $id, array $rechazos): array {
        $campos = ['cedula_frente','cedula_trasera','logo','foto_negocio'];
        $sets   = [];
        $params = [];

        foreach ($campos as $c) {
            $sets[]   = "doc_{$c}_rechazo = ?";
            $params[] = !empty(trim($rechazos[$c] ?? '')) ? mb_substr(trim($rechazos[$c]), 0, 500) : null;
        }

        $hayRechazos = (bool)array_filter(array_map('trim', $rechazos));
        if (!$hayRechazos) {
            return ['ok' => false, 'msg' => 'Indica el motivo de al menos un rechazo.'];
        }

        $sets[]   = "doc_estado = 'rechazado'";
        $sets[]   = "verificado = 0";
        $params[] = $id;

        try {
            $this->db->prepare(
                "UPDATE comercios SET " . implode(', ', $sets) . " WHERE id = ?"
            )->execute($params);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function cambiarPlanComercio(int $id, string $plan, float $monto,
                                        string $fecha, string $hasta,
                                        string $metodo, string $referencia): array {
        try {
            $stmt = $this->dbSup->prepare("SELECT slug FROM planes WHERE slug = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$plan]);
            if (!$stmt->fetch()) {
                return ['ok' => false, 'msg' => 'Plan no encontrado o inactivo.'];
            }

            // Actualizar plan y vencimiento en comercios
            $this->db->prepare(
                "UPDATE comercios SET plan = ?, plan_vence = ? WHERE id = ?"
            )->execute([$plan, $hasta ?: null, $id]);

            // Registrar pago/factura si hay monto o es plan pago
            if ($monto > 0 || $hasta) {
                try { $this->dbSup->exec("ALTER TABLE pagos ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'pagado'"); } catch(\Throwable $e) {}
                try { $this->dbSup->exec("ALTER TABLE pagos ADD COLUMN periodo_desde DATE NULL"); } catch(\Throwable $e) {}
                $this->dbSup->prepare(
                    "INSERT INTO pagos (comercio_id, monto, fecha, periodo_desde, periodo_hasta, metodo, referencia, notas, estado)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pagado')"
                )->execute([
                    $id, $monto, $fecha ?: date('Y-m-d'),
                    $fecha ?: date('Y-m-d'),
                    $hasta ?: null,
                    $metodo ?: 'efectivo',
                    $referencia ?: null,
                    'Cambio de plan a: ' . $plan,
                ]);
            }

            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    // ── Configuraciones globales ──────────────────────────────────────────────

    private function ensureConfigTable(): void {
        try {
            $this->dbSup->exec(
                "CREATE TABLE IF NOT EXISTS sup_config (
                    clave VARCHAR(100) NOT NULL PRIMARY KEY,
                    valor TEXT NOT NULL DEFAULT '',
                    actualizado TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (\Throwable $e) {}
    }

    public function getConfig(string $clave, string $default = ''): string {
        $this->ensureConfigTable();
        try {
            $s = $this->dbSup->prepare("SELECT valor FROM sup_config WHERE clave = ? LIMIT 1");
            $s->execute([$clave]);
            $row = $s->fetchColumn();
            return $row !== false ? $row : $default;
        } catch (\Throwable $e) { return $default; }
    }

    public function setConfig(string $clave, string $valor): array {
        $this->ensureConfigTable();
        try {
            $this->dbSup->prepare(
                "INSERT INTO sup_config (clave, valor) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
            )->execute([$clave, $valor]);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public function getAllConfig(): array {
        $this->ensureConfigTable();
        try {
            return $this->dbSup->query("SELECT clave, valor FROM sup_config")->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\Throwable $e) { return []; }
    }
}
?>
