<?php
// modelo/mensajeriaModel.php

class MensajeriaModel {

    private PDO $dbSup;

    public function __construct() {
        $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
        $this->dbSup = new PDO(
            "mysql:host=" . SupConfig::DB_HOST . ";dbname=" . SupConfig::DB_NAME_SUP . ";charset=" . SupConfig::DB_CHARSET,
            SupConfig::DB_USER, SupConfig::DB_PASS, $opts
        );
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
        } catch (\Throwable $e) {}
    }

    // Trae el log local de envíos (con teléfono y comercio) para un rango de fechas,
    // indexado por el texto exacto del mensaje para cruzarlo con el historial de Inalambria.
    private function obtenerLogLocal(string $desde, string $hasta): array {
        try {
            $rows = $this->dbSup->prepare(
                "SELECT l.telefono, l.mensaje, l.comercio_id, l.created_at
                 FROM sms_log l
                 WHERE l.created_at >= ? AND l.created_at < DATE_ADD(?, INTERVAL 1 DAY)"
            );
            $rows->execute([$desde, $hasta]);
            $porMensaje = [];
            foreach ($rows->fetchAll() as $r) {
                $porMensaje[$r['mensaje']] = $r;
            }
            return $porMensaje;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function nombresComercios(array $ids): array {
        if (!$ids) return [];
        try {
            $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
            $db   = new PDO(
                "mysql:host=" . SupConfig::DB_HOST . ";dbname=" . SupConfig::DB_NAME . ";charset=" . SupConfig::DB_CHARSET,
                SupConfig::DB_USER, SupConfig::DB_PASS, $opts
            );
            $in  = implode(',', array_map('intval', array_unique($ids)));
            $rows = $db->query("SELECT id, nombre FROM comercios WHERE id IN ({$in})")->fetchAll();
            return array_column($rows, 'nombre', 'id');
        } catch (\Throwable $e) {
            return [];
        }
    }

    // Trae y agrega el uso de SMS entre dos fechas (YYYY-MM-DD) consultando
    // directamente el historial de consumo de Inalambria Express, y lo enriquece
    // con el teléfono/comercio destino desde nuestro log local (Inalambria no lo expone).
    public function obtenerUso(string $desde, string $hasta): array {
        $dateFrom = $desde . 'T00:00:00Z';
        $dateTo   = date('Y-m-d', strtotime($hasta . ' +1 day')) . 'T00:00:00Z';

        $resp = SupConfig::inalambriaGet('/messages/history', [
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
            'limit'    => 100,
        ]);

        if (!$resp['ok']) {
            return [
                'ok' => false, 'msg' => $resp['msg'],
                'requests' => 0, 'gastado' => 0, 'entregados' => 0, 'fallidos' => 0, 'pendientes' => 0,
                'actividad' => [],
            ];
        }

        $items      = $resp['data']['consumptions'] ?? [];
        $logLocal   = $this->obtenerLogLocal($desde, $hasta);
        $comercios  = $this->nombresComercios(array_column($logLocal, 'comercio_id'));

        $requests   = count($items);
        $gastado    = 0;
        $entregados = 0;
        $fallidos   = 0;
        $pendientes = 0;

        foreach ($items as &$it) {
            $gastado += (float)($it['amount'] ?? 0);
            $status   = $it['status'] ?? (($it['isSent'] ?? false) ? 'sent' : 'pending');
            if ($status === 'sent' || !empty($it['isSent'])) $entregados++;
            elseif ($status === 'failed') $fallidos++;
            else $pendientes++;

            $log = $logLocal[$it['sampleMessage'] ?? ''] ?? null;
            $it['telefono']        = $log['telefono'] ?? null;
            $it['comercio_nombre'] = $log && $log['comercio_id'] ? ($comercios[$log['comercio_id']] ?? null) : null;
        }
        unset($it);

        return [
            'ok'         => true,
            'requests'   => $requests,
            'gastado'    => $gastado,
            'entregados' => $entregados,
            'fallidos'   => $fallidos,
            'pendientes' => $pendientes,
            'actividad'  => $items,
        ];
    }

    public function obtenerBalance(): array {
        $resp = SupConfig::inalambriaGet('/messages/balance');
        return $resp['ok'] ? $resp['data'] : [];
    }
}
