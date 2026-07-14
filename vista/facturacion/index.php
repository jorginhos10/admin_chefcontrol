<?php
require_once __DIR__ . '/../../config/config.php';
$basePath = SupConfig::getBasePath();
$baseUrl  = SupConfig::getBaseUrl();
$nombre   = htmlspecialchars($_SESSION['sup_nombre'] ?? 'Admin');

// Planes disponibles (para modal de cambio)
$planesDisponibles = [];
try {
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    $dbSup = new PDO("mysql:host=" . SupConfig::DB_HOST . ";dbname=" . SupConfig::DB_NAME_SUP . ";charset=utf8mb4",
                     SupConfig::DB_USER, SupConfig::DB_PASS, $opts);
    $planesDisponibles = $dbSup->query("SELECT id, nombre, slug, precio, periodo, color FROM planes WHERE activo=1 ORDER BY orden ASC")->fetchAll();
} catch (\Throwable $e) {
    error_log('Facturación — no se pudo cargar planesDisponibles: ' . $e->getMessage());
}

// Estadísticas rápidas
$totalCuentas  = count($cuentas);
$alDia         = count(array_filter($cuentas, fn($c) => $c['estado_pago'] === 'al_dia'));
$porVencer     = count(array_filter($cuentas, fn($c) => $c['estado_pago'] === 'por_vencer'));
$vencidos      = count(array_filter($cuentas, fn($c) => $c['estado_pago'] === 'vencido'));
$gratuitos     = count(array_filter($cuentas, fn($c) => $c['estado_pago'] === 'gratuito'));

function badgePlan(string $plan): string {
    $map = [
        'gratuito'   => ['#1e1e30','#6366f1','Gratuito'],
        'basico'     => ['#0d2a0d','#22c55e','Básico'],
        'pro'        => ['#1e2a0d','#84cc16','Pro'],
        'enterprise' => ['#1a0d2a','#a855f7','Enterprise'],
    ];
    [$bg, $color, $lbl] = $map[$plan] ?? ['#1e1e30','#aaa',$plan];
    return "<span style='background:{$bg};color:{$color};padding:2px 10px;border-radius:999px;
                         font-size:11px;font-weight:700;'>{$lbl}</span>";
}

function badgeEstado(string $estado): string {
    $map = [
        'al_dia'    => ['#0d2a0d','#22c55e','Al día'],
        'por_vencer'=> ['#2a1e0d','#f59e0b','Por vencer'],
        'vencido'   => ['#2a0d0d','#ef4444','Vencido'],
        'gratuito'  => ['#1e1e30','#6366f1','Sin cobro'],
        'en_proceso'=> ['#1e3a5f','#93c5fd','En proceso'],
    ];
    [$bg, $color, $lbl] = $map[$estado] ?? ['#1e1e30','#aaa',$estado];
    return "<span style='background:{$bg};color:{$color};padding:2px 10px;border-radius:999px;
                         font-size:11px;font-weight:700;'>{$lbl}</span>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación — ChefControl SUP</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dias-ok      { color: #22c55e; font-weight: 700; }
        .dias-warn    { color: #f59e0b; font-weight: 700; }
        .dias-vencido { color: #ef4444; font-weight: 700; }
        .dias-na      { color: #444; }
        .tbl-fact td, .tbl-fact th { padding: 11px 14px; vertical-align: middle; }
        .tbl-fact tbody tr:hover { background: rgba(99,102,241,.06); }
        .btn-acc {
            background: #1e1e30; border: 1px solid rgba(255,255,255,.08);
            color: #e0e0e0; border-radius: 6px; padding: 5px 10px;
            cursor: pointer; font-size: 12px; transition: background .15s;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .btn-acc:hover { background: #2d2d44; }
        .search-wrap { position: relative; }
        .search-wrap input { padding-left: 34px; }
        .search-wrap .s-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #555; font-size: 13px; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-shield-alt"></i></div>
        <div>
            <div class="brand-text">ChefControl</div>
            <div class="brand-sub">SUP Panel</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a class="nav-item" href="<?= $basePath ?>/dashboard">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a class="nav-item" href="<?= $basePath ?>/chat" style="justify-content:space-between;">
            <span><i class="fas fa-comments"></i> Chat</span>
            <span id="chatBadge" style="display:none;background:#7c3aed;color:#fff;
                  border-radius:999px;font-size:11px;padding:1px 7px;">0</span>
        </a>
        <a class="nav-item active" href="<?= $basePath ?>/facturacion">
            <i class="fas fa-file-invoice-dollar"></i> Facturación
        </a>
        <a class="nav-item" href="<?= $basePath ?>/financiera">
            <i class="fas fa-wallet"></i> Financiera
        </a>
        <a class="nav-item" href="<?= $basePath ?>/mensajeria">
            <i class="fas fa-sms"></i> Mensajería
        </a>
        <a class="nav-item" href="<?= $basePath ?>/planes">
            <i class="fas fa-layer-group"></i> Planes
        </a>
        <a class="nav-item" href="<?= $basePath ?>/usuarios">
            <i class="fas fa-user-shield"></i> Usuarios
        </a>
        <a class="nav-item" href="<?= $basePath ?>/configuraciones">
            <i class="fas fa-sliders"></i> Configuraciones
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-name"><?= $nombre ?></div>
        <div><?= htmlspecialchars($_SESSION['sup_username'] ?? '') ?></div>
    </div>
</aside>

<div class="main">

    <header class="topbar">
        <div>
            <h2>Facturación</h2>
            <p><?= date('d \d\e F, Y') ?></p>
        </div>
        <a class="btn-logout" href="<?= $basePath ?>/logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </header>

    <div class="content">

        <!-- Estadísticas -->
        <div class="stats-grid" style="margin-bottom:24px;">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-store"></i></div>
                <div>
                    <div class="stat-value"><?= $totalCuentas ?></div>
                    <div class="stat-label">Total cuentas</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
                <div>
                    <div class="stat-value"><?= $alDia ?></div>
                    <div class="stat-label">Al día</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#2a1e0d;color:#f59e0b;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#f59e0b;"><?= $porVencer ?></div>
                    <div class="stat-label">Por vencer (≤7 días)</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
                <div>
                    <div class="stat-value" style="color:#ef4444;"><?= $vencidos ?></div>
                    <div class="stat-label">Vencidos</div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-file-invoice-dollar" style="color:#6366f1;"></i>
                    <h3>Cuentas</h3>
                    <span><?= $totalCuentas ?> registros</span>
                </div>
                <div class="search-wrap">
                    <i class="fas fa-search s-icon"></i>
                    <input id="buscar" type="text" placeholder="Buscar comercio..."
                           oninput="filtrar(this.value)"
                           style="background:#12121e;border:1px solid #2d2d44;border-radius:6px;
                                  padding:8px 12px 8px 32px;color:#e0e0e0;font-size:13px;
                                  outline:none;width:220px;">
                </div>
            </div>

            <?php if (empty($cuentas)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-invoice-dollar"></i>
                    No hay cuentas registradas.
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="tbl-fact" id="tablaFact" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #1e1e30;">
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">COMERCIO</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">PLAN</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">REGISTRO</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">ÚLTIMO PAGO</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">PRÓX. COBRO</th>
                        <th style="text-align:center;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">DÍAS</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">ESTADO</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($cuentas as $c):
                    $dias = $c['dias_restantes'];
                    if ($c['estado_pago'] === 'gratuito') {
                        $diasHtml = '<span class="dias-na">—</span>';
                    } elseif ($dias < 0) {
                        $diasHtml = '<span class="dias-vencido">' . abs($dias) . ' vencido</span>';
                    } elseif ($dias <= 7) {
                        $diasHtml = '<span class="dias-warn">' . $dias . ' días</span>';
                    } else {
                        $diasHtml = '<span class="dias-ok">' . $dias . ' días</span>';
                    }
                ?>
                    <tr data-nombre="<?= strtolower(htmlspecialchars($c['nombre'])) ?>">
                        <td>
                            <div style="font-weight:600;color:#e0e0e0;"><?= htmlspecialchars($c['nombre']) ?></div>
                            <div style="color:#555;font-size:11px;"><?= htmlspecialchars($c['slug'] ?? '') ?></div>
                        </td>
                        <td><?= badgePlan($c['plan']) ?></td>
                        <td style="color:#888;font-size:12px;"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                        <td>
                            <?php if ($c['ultimo_pago']): ?>
                                <div style="color:#e0e0e0;font-size:13px;"><?= date('d/m/Y', strtotime($c['ultimo_pago'])) ?></div>
                                <div style="color:#555;font-size:11px;">
                                    <?= number_format((float)$c['ultimo_monto'], 0, ',', '.') ?> · <?= htmlspecialchars($c['ultimo_metodo']) ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#444;font-size:12px;">Sin pagos</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#aaa;font-size:12px;">
                            <?= $c['plan_vence'] ? date('d/m/Y', strtotime($c['plan_vence'])) : '<span style="color:#444;">—</span>' ?>
                        </td>
                        <td style="text-align:center;"><?= $diasHtml ?></td>
                        <td><?= badgeEstado($c['estado_pago']) ?></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-acc"
                                        onclick="abrirPago(<?= htmlspecialchars(json_encode([
                                            'id'     => $c['id'],
                                            'nombre' => $c['nombre'],
                                            'plan'   => $c['plan'],
                                            'vence'  => $c['plan_vence'],
                                        ]), ENT_QUOTES) ?>)"
                                        title="Registrar pago">
                                    <i class="fas fa-dollar-sign"></i> Pago
                                </button>
                                <button class="btn-acc"
                                        onclick="abrirCambioPlan(<?= htmlspecialchars(json_encode([
                                            'id'     => $c['id'],
                                            'nombre' => $c['nombre'],
                                            'plan'   => $c['plan'],
                                        ]), ENT_QUOTES) ?>)"
                                        title="Cambiar plan"
                                        style="color:#a78bfa;">
                                    <i class="fas fa-layer-group"></i> Plan
                                </button>
                                <button class="btn-acc"
                                        onclick="verHistorial(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nombre'])) ?>')"
                                        title="Historial de pagos">
                                    <i class="fas fa-clock-rotate-left"></i>
                                </button>
                                <button class="btn-acc"
                                        id="btn-sus-<?= $c['id'] ?>"
                                        onclick="toggleSuspender(<?= $c['id'] ?>, <?= (int)$c['activo'] ?>)"
                                        title="<?= (int)$c['activo'] ? 'Suspender' : 'Activar' ?>"
                                        style="<?= (int)$c['activo'] ? 'color:#f87171;' : 'color:#22c55e;' ?>">
                                    <i class="fas <?= (int)$c['activo'] ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                                    <?= (int)$c['activo'] ? 'Suspender' : 'Activar' ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- ── Modal: Registrar pago ───────────────────────────────────────────────── -->
<div id="modalPago" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);
     z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#1e1e2e;border:1px solid #2d2d44;border-radius:16px;
                padding:32px 28px;width:100%;max-width:440px;position:relative;">
        <button onclick="document.getElementById('modalPago').style.display='none'"
                style="position:absolute;top:14px;right:16px;background:none;border:none;
                       color:#888;font-size:18px;cursor:pointer;"><i class="fas fa-times"></i></button>
        <h3 style="color:#fff;margin:0 0 4px;font-size:17px;">
            <i class="fas fa-dollar-sign" style="color:#22c55e;margin-right:8px;"></i>Registrar pago
        </h3>
        <p id="pagoNombre" style="color:#888;font-size:13px;margin:0 0 20px;"></p>
        <div id="pagoError" style="display:none;background:#3b1c1c;border:1px solid #e74c3c;
             color:#e74c3c;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;"></div>
        <form id="formPago" style="display:flex;flex-direction:column;gap:13px;">
            <input type="hidden" id="pagoCid" name="comercio_id">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Monto *</label>
                    <input name="monto" type="number" step="0.01" min="0" required placeholder="0.00"
                           style="<?= estiloInput() ?>">
                </div>
                <div>
                    <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Fecha pago *</label>
                    <input name="fecha" type="date" required id="pagoFecha"
                           style="<?= estiloInput() ?>">
                </div>
            </div>

            <div>
                <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Válido hasta (próx. cobro) *</label>
                <input name="periodo_hasta" type="date" required id="pagoHasta"
                       style="<?= estiloInput() ?>">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Método</label>
                    <select name="metodo" style="<?= estiloInput() ?>">
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="nequi">Nequi</option>
                        <option value="daviplata">Daviplata</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Referencia</label>
                    <input name="referencia" type="text" placeholder="Opcional"
                           style="<?= estiloInput() ?>">
                </div>
            </div>

            <div>
                <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Notas</label>
                <textarea name="notas" rows="2" placeholder="Opcional"
                          style="<?= estiloInput() ?>resize:vertical;"></textarea>
            </div>

            <button type="submit" id="btnGuardarPago"
                    style="background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border:none;
                           padding:12px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">
                <i class="fas fa-check-circle"></i> Guardar pago
            </button>
        </form>
    </div>
</div>

<!-- ── Modal: Cambiar plan (2 pasos) ──────────────────────────────────────── -->
<div id="modalCambioPlan" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);
     z-index:1000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#1e1e2e;border:1px solid #2d2d44;border-radius:16px;
                width:100%;max-width:680px;position:relative;max-height:90vh;overflow-y:auto;">

        <!-- Cabecera -->
        <div style="padding:20px 24px 14px;border-bottom:1px solid #2d2d44;
                    display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h3 style="color:#fff;margin:0 0 3px;font-size:17px;">
                    <i class="fas fa-layer-group" style="color:#7c3aed;margin-right:8px;"></i>Cambiar plan
                </h3>
                <p id="cambioPlanNombre" style="color:#666;font-size:12px;margin:0;"></p>
            </div>
            <button onclick="document.getElementById('modalCambioPlan').style.display='none'"
                    style="background:none;border:none;color:#555;font-size:18px;cursor:pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Indicador de pasos -->
        <div style="padding:14px 24px 0;display:flex;gap:0;align-items:center;">
            <div id="stepInd1" style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#7c3aed;">
                <div style="width:22px;height:22px;border-radius:50%;background:#7c3aed;color:#fff;
                            display:flex;align-items:center;justify-content:center;font-size:11px;">1</div>
                Seleccionar plan
            </div>
            <div style="flex:1;height:1px;background:#2d2d44;margin:0 10px;"></div>
            <div id="stepInd2" style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#444;">
                <div id="stepCircle2" style="width:22px;height:22px;border-radius:50%;background:#2d2d44;color:#666;
                            display:flex;align-items:center;justify-content:center;font-size:11px;">2</div>
                Registrar pago
            </div>
        </div>

        <!-- PASO 1: Tarjetas de planes -->
        <div id="cpPaso1">
            <div style="padding:16px 24px;display:grid;grid-template-columns:repeat(auto-fill,minmax(145px,1fr));gap:10px;">
            <?php foreach ($planesDisponibles as $pl):
                $color  = $pl['color'] ?? '#6366f1';
                $precio = (float)$pl['precio'];
            ?>
                <div onclick="seleccionarNuevoPlan(<?= htmlspecialchars(json_encode([
                        'slug'    => $pl['slug'],
                        'nombre'  => $pl['nombre'],
                        'precio'  => $precio,
                        'periodo' => $pl['periodo'],
                        'color'   => $color,
                    ]), ENT_QUOTES) ?>)"
                     id="planOpt-<?= htmlspecialchars($pl['slug'], ENT_QUOTES) ?>"
                     style="background:#12121e;border:2px solid #2d2d44;border-radius:12px;
                            padding:14px 12px;cursor:pointer;text-align:center;transition:.15s;"
                     onmouseover="this.style.borderColor='<?= $color ?>'"
                     onmouseout="if(!this.classList.contains('sel'))this.style.borderColor='#2d2d44'">
                    <div style="width:36px;height:36px;border-radius:9px;background:<?= $color ?>22;
                                display:flex;align-items:center;justify-content:center;margin:0 auto 8px;">
                        <i class="fas fa-crown" style="color:<?= $color ?>;font-size:15px;"></i>
                    </div>
                    <div style="color:#fff;font-weight:700;font-size:13px;margin-bottom:3px;">
                        <?= htmlspecialchars($pl['nombre']) ?>
                    </div>
                    <div style="color:<?= $color ?>;font-weight:800;font-size:15px;">
                        <?= $precio > 0 ? '$'.number_format($precio,0,',','.') : 'Gratis' ?>
                    </div>
                    <?php if ($precio > 0): ?>
                    <div style="color:#555;font-size:10px;">/<?= htmlspecialchars($pl['periodo']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
            <div style="padding:0 24px 18px;display:flex;gap:10px;">
                <button id="btnSiguientePaso" onclick="irPaso2()" disabled
                        style="flex:1;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;
                               border:none;border-radius:8px;padding:11px;font-size:14px;
                               font-weight:600;cursor:pointer;opacity:.4;transition:.15s;">
                    Siguiente <i class="fas fa-arrow-right"></i>
                </button>
                <button onclick="document.getElementById('modalCambioPlan').style.display='none'"
                        style="background:#1e1e30;border:1px solid #2d2d44;color:#888;
                               border-radius:8px;padding:11px 18px;font-size:13px;cursor:pointer;">
                    Cancelar
                </button>
            </div>
        </div>

        <!-- PASO 2: Datos del pago -->
        <div id="cpPaso2" style="display:none;padding:18px 24px 20px;">
            <!-- Resumen del plan elegido -->
            <div id="cpResumenPlan" style="background:#12121e;border-radius:10px;padding:13px 16px;
                                           margin-bottom:16px;display:flex;align-items:center;gap:12px;">
                <div id="cpResumenIcon" style="width:36px;height:36px;border-radius:9px;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-crown" style="font-size:15px;"></i>
                </div>
                <div style="flex:1;">
                    <div id="cpResumenNombre" style="color:#fff;font-weight:700;font-size:14px;"></div>
                    <div id="cpResumenPrecio" style="font-size:12px;color:#888;"></div>
                </div>
                <button onclick="volverPaso1()"
                        style="background:none;border:1px solid #2d2d44;color:#666;
                               border-radius:6px;padding:5px 10px;font-size:11px;cursor:pointer;">
                    <i class="fas fa-pen"></i> Cambiar
                </button>
            </div>

            <!-- Formulario pago -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div>
                    <label style="color:#888;font-size:11px;font-weight:700;display:block;margin-bottom:5px;">
                        FECHA DE PAGO
                    </label>
                    <input id="cpFecha" type="date"
                           style="width:100%;box-sizing:border-box;background:#12121e;border:1px solid #2d2d44;
                                  border-radius:7px;padding:9px 11px;color:#e0e0e0;font-size:13px;outline:none;">
                </div>
                <div>
                    <label style="color:#888;font-size:11px;font-weight:700;display:block;margin-bottom:5px;">
                        MONTO COBRADO
                    </label>
                    <input id="cpMonto" type="number" min="0" step="1000"
                           style="width:100%;box-sizing:border-box;background:#12121e;border:1px solid #2d2d44;
                                  border-radius:7px;padding:9px 11px;color:#e0e0e0;font-size:13px;outline:none;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div>
                    <label style="color:#888;font-size:11px;font-weight:700;display:block;margin-bottom:5px;">
                        VÁLIDO HASTA
                    </label>
                    <input id="cpHasta" type="date"
                           style="width:100%;box-sizing:border-box;background:#12121e;border:1px solid #2d2d44;
                                  border-radius:7px;padding:9px 11px;color:#e0e0e0;font-size:13px;outline:none;">
                </div>
                <div>
                    <label style="color:#888;font-size:11px;font-weight:700;display:block;margin-bottom:5px;">
                        MÉTODO DE PAGO
                    </label>
                    <select id="cpMetodo"
                            style="width:100%;box-sizing:border-box;background:#12121e;border:1px solid #2d2d44;
                                   border-radius:7px;padding:9px 11px;color:#e0e0e0;font-size:13px;outline:none;">
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="nequi">Nequi</option>
                        <option value="daviplata">Daviplata</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <label style="color:#888;font-size:11px;font-weight:700;display:block;margin-bottom:5px;">
                    REFERENCIA / COMPROBANTE <span style="color:#333;font-weight:400;">(opcional)</span>
                </label>
                <input id="cpReferencia" type="text" placeholder="Nº de transacción o referencia"
                       style="width:100%;box-sizing:border-box;background:#12121e;border:1px solid #2d2d44;
                              border-radius:7px;padding:9px 11px;color:#e0e0e0;font-size:13px;outline:none;">
            </div>

            <div id="cpError" style="display:none;background:#3b1c1c;border:1px solid #e74c3c;
                 color:#f87171;border-radius:7px;padding:9px 13px;margin-bottom:12px;font-size:13px;"></div>

            <div style="display:flex;gap:10px;">
                <button id="btnAplicarPlan" onclick="aplicarCambioPlan()"
                        style="flex:1;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;
                               border:none;border-radius:8px;padding:12px;font-size:14px;
                               font-weight:600;cursor:pointer;transition:.15s;">
                    <i class="fas fa-file-invoice-dollar"></i> Confirmar y generar factura
                </button>
                <button onclick="volverPaso1()"
                        style="background:#1e1e30;border:1px solid #2d2d44;color:#888;
                               border-radius:8px;padding:12px 16px;font-size:13px;cursor:pointer;">
                    <i class="fas fa-arrow-left"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal: Historial de pagos ──────────────────────────────────────────── -->
<div id="modalHistorial" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);
     z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#1e1e2e;border:1px solid #2d2d44;border-radius:16px;
                padding:28px 24px;width:100%;max-width:580px;position:relative;
                max-height:85vh;overflow-y:auto;">
        <button onclick="document.getElementById('modalHistorial').style.display='none'"
                style="position:absolute;top:14px;right:16px;background:none;border:none;
                       color:#888;font-size:18px;cursor:pointer;"><i class="fas fa-times"></i></button>
        <h3 style="color:#fff;margin:0 0 4px;font-size:17px;">
            <i class="fas fa-clock-rotate-left" style="color:#6366f1;margin-right:8px;"></i>Historial de pagos
        </h3>
        <p id="historialNombre" style="color:#888;font-size:13px;margin:0 0 20px;"></p>
        <div id="historialBody"></div>
    </div>
</div>

<?php
function estiloInput(): string {
    return "width:100%;box-sizing:border-box;background:#12121e;border:1px solid #2d2d44;
            border-radius:6px;padding:9px 12px;color:#e0e0e0;font-size:13px;outline:none;";
}
?>

<script>
const BP = '<?= $basePath ?>';

// ── Filtro de búsqueda ────────────────────────────────────────────────────────
function filtrar(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tablaFact tbody tr').forEach(tr => {
        tr.style.display = tr.dataset.nombre.includes(q) ? '' : 'none';
    });
}

// ── Modal pago ────────────────────────────────────────────────────────────────
function abrirPago(data) {
    document.getElementById('pagoCid').value           = data.id;
    document.getElementById('pagoNombre').textContent  = data.nombre;
    document.getElementById('pagoError').style.display = 'none';
    document.getElementById('formPago').reset();
    document.getElementById('pagoCid').value           = data.id;

    // Fecha hoy por defecto
    const hoy = new Date().toISOString().slice(0, 10);
    document.getElementById('pagoFecha').value = hoy;

    // Próx. cobro: 30 días desde hoy (o desde vencimiento actual si es futuro)
    let base = data.vence && data.vence > hoy ? data.vence : hoy;
    const hasta = new Date(base);
    hasta.setDate(hasta.getDate() + 30);
    document.getElementById('pagoHasta').value = hasta.toISOString().slice(0, 10);

    document.getElementById('modalPago').style.display = 'flex';
}

document.getElementById('modalPago').addEventListener('click', function(e) { if (e.target===this) this.style.display='none'; });

document.getElementById('formPago').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarPago');
    const err = document.getElementById('pagoError');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    err.style.display = 'none';

    try {
        const fd  = new FormData(this);
        const res = await fetch(`${BP}/facturacion/pago`, { method: 'POST', body: fd });
        const d   = await res.json();
        if (d.ok) {
            document.getElementById('modalPago').style.display = 'none';
            location.reload();
        } else {
            err.style.display = 'block';
            err.textContent = d.msg || 'Error al guardar.';
        }
    } catch(ex) {
        err.style.display = 'block';
        err.textContent = 'Error de conexión.';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Guardar pago';
});

// ── Historial ─────────────────────────────────────────────────────────────────
async function verHistorial(id, nombre) {
    document.getElementById('historialNombre').textContent = nombre;
    document.getElementById('historialBody').innerHTML =
        '<div style="text-align:center;padding:28px;color:#888;"><i class="fas fa-spinner fa-spin" style="font-size:22px;"></i></div>';
    document.getElementById('modalHistorial').style.display = 'flex';

    const res  = await fetch(`${BP}/facturacion/historial/${id}`);
    const data = await res.json();
    const body = document.getElementById('historialBody');

    if (!data.ok || !data.data.length) {
        body.innerHTML = '<div style="text-align:center;padding:28px;color:#555;">Sin pagos registrados.</div>';
        return;
    }

    const metodoIcon = { efectivo:'fa-money-bill', transferencia:'fa-building-columns',
                         tarjeta:'fa-credit-card', nequi:'fa-mobile', daviplata:'fa-mobile', otro:'fa-receipt' };

    body.innerHTML = data.data.map(p => `
        <div style="border:1px solid #1e1e30;border-radius:10px;padding:14px 16px;margin-bottom:10px;
                    display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;border-radius:8px;background:#0d2a0d;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas ${metodoIcon[p.metodo]||'fa-receipt'}" style="color:#22c55e;font-size:15px;"></i>
                </div>
                <div>
                    <div style="color:#e0e0e0;font-weight:600;font-size:14px;">
                        $${parseFloat(p.monto).toLocaleString('es-CO')}
                    </div>
                    <div style="color:#555;font-size:11px;">${p.metodo}${p.referencia ? ' · '+p.referencia : ''}</div>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="color:#aaa;font-size:13px;">${formatFecha(p.fecha)}</div>
                ${p.periodo_hasta
                    ? `<div style="color:#555;font-size:11px;">Válido hasta ${formatFecha(p.periodo_hasta)}</div>`
                    : ''}
            </div>
            ${p.notas ? `<div style="width:100%;color:#666;font-size:11px;border-top:1px solid #1e1e30;
                              padding-top:8px;margin-top:4px;">${p.notas}</div>` : ''}
        </div>
    `).join('');
}

document.getElementById('modalHistorial').addEventListener('click', function(e) { if (e.target===this) this.style.display='none'; });

function formatFecha(str) {
    if (!str) return '—';
    const [y,m,d] = str.split('-');
    return `${d}/${m}/${y}`;
}

// ── Suspender / Activar ───────────────────────────────────────────────────────
async function toggleSuspender(id, activo) {
    const accion = activo ? 'suspender' : 'activar';
    if (!confirm(`¿${activo ? 'Suspender' : 'Activar'} este comercio?`)) return;

    const res  = await fetch(`${BP}/restaurante/toggle/${id}`);
    const data = await res.json();
    if (!data.ok) { alert(data.msg || 'Error'); return; }

    const btn = document.getElementById(`btn-sus-${id}`);
    if (data.activo) {
        btn.style.color = '#f87171';
        btn.title = 'Suspender';
        btn.innerHTML = '<i class="fas fa-ban"></i> Suspender';
        btn.onclick = () => toggleSuspender(id, 1);
    } else {
        btn.style.color = '#22c55e';
        btn.title = 'Activar';
        btn.innerHTML = '<i class="fas fa-circle-check"></i> Activar';
        btn.onclick = () => toggleSuspender(id, 0);
    }
}

// ── Cambiar plan (2 pasos) ───────────────────────────────────────────────────
let _cambioPlanCid    = null;
let _cambioPlanActual = null;
let _nuevoPlan        = null; // {slug, nombre, precio, periodo, color}

const periodosDias = { mensual:30, bimestral:60, trimestral:90, semestral:180, anual:365 };

function abrirCambioPlan(data) {
    _cambioPlanCid    = data.id;
    _cambioPlanActual = data.plan;
    _nuevoPlan        = null;

    document.getElementById('cambioPlanNombre').textContent =
        data.nombre + (data.plan ? '  ·  Plan actual: ' + data.plan : '');

    // Resetear paso 1
    document.querySelectorAll('[id^="planOpt-"]').forEach(el => {
        el.classList.remove('sel');
        el.style.borderColor = '#2d2d44';
        el.style.background  = '#12121e';
    });
    const actEl = document.getElementById('planOpt-' + data.plan);
    if (actEl) actEl.style.background = '#1a1a2e';

    document.getElementById('btnSiguientePaso').disabled = true;
    document.getElementById('btnSiguientePaso').style.opacity = '.4';

    // Mostrar paso 1
    document.getElementById('cpPaso1').style.display = '';
    document.getElementById('cpPaso2').style.display = 'none';
    setStepIndicator(1);

    document.getElementById('modalCambioPlan').style.display = 'flex';
}

function seleccionarNuevoPlan(p) {
    _nuevoPlan = p;

    document.querySelectorAll('[id^="planOpt-"]').forEach(el => {
        el.classList.remove('sel');
        el.style.borderColor = '#2d2d44';
        el.style.background  = '#12121e';
    });
    const el = document.getElementById('planOpt-' + p.slug);
    if (el) {
        el.classList.add('sel');
        el.style.borderColor = p.color;
        el.style.background  = p.color + '18';
    }

    const btn = document.getElementById('btnSiguientePaso');
    btn.disabled = (p.slug === _cambioPlanActual);
    btn.style.opacity = btn.disabled ? '.4' : '1';
}

function irPaso2() {
    if (!_nuevoPlan || _nuevoPlan.slug === _cambioPlanActual) return;

    // Rellenar resumen
    const icon = document.getElementById('cpResumenIcon');
    icon.style.background = _nuevoPlan.color + '22';
    icon.querySelector('i').style.color = _nuevoPlan.color;
    document.getElementById('cpResumenNombre').textContent = _nuevoPlan.nombre;
    document.getElementById('cpResumenNombre').style.color = _nuevoPlan.color;
    document.getElementById('cpResumenPrecio').textContent =
        _nuevoPlan.precio > 0
            ? '$' + Number(_nuevoPlan.precio).toLocaleString('es-CO') + ' / ' + _nuevoPlan.periodo
            : 'Plan gratuito';

    // Fechas por defecto
    const hoy = new Date().toISOString().slice(0,10);
    document.getElementById('cpFecha').value = hoy;
    document.getElementById('cpMonto').value = _nuevoPlan.precio || 0;
    document.getElementById('cpReferencia').value = '';
    document.getElementById('cpError').style.display = 'none';

    const dias  = periodosDias[_nuevoPlan.periodo] || 30;
    const hasta = new Date();
    hasta.setDate(hasta.getDate() + dias);
    document.getElementById('cpHasta').value = hasta.toISOString().slice(0,10);

    document.getElementById('cpPaso1').style.display = 'none';
    document.getElementById('cpPaso2').style.display = '';
    setStepIndicator(2);
}

function volverPaso1() {
    document.getElementById('cpPaso1').style.display = '';
    document.getElementById('cpPaso2').style.display = 'none';
    setStepIndicator(1);
}

function setStepIndicator(paso) {
    const active  = { color:'#7c3aed', bg:'#7c3aed', txtColor:'#fff' };
    const inactive = { color:'#444',    bg:'#2d2d44', txtColor:'#666' };
    [1,2].forEach(n => {
        const s = n === paso ? active : inactive;
        document.getElementById('stepInd'+n).style.color = s.color;
        document.getElementById('stepCircle'+n)?.style && Object.assign(
            document.getElementById('stepCircle'+n).style, { background:s.bg, color:s.txtColor }
        );
    });
}

async function aplicarCambioPlan() {
    if (!_cambioPlanCid || !_nuevoPlan) return;

    const btn = document.getElementById('btnAplicarPlan');
    const err = document.getElementById('cpError');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando factura...';
    err.style.display = 'none';

    const fd = new FormData();
    fd.append('plan',       _nuevoPlan.slug);
    fd.append('monto',      document.getElementById('cpMonto').value);
    fd.append('fecha',      document.getElementById('cpFecha').value);
    fd.append('hasta',      document.getElementById('cpHasta').value);
    fd.append('metodo',     document.getElementById('cpMetodo').value);
    fd.append('referencia', document.getElementById('cpReferencia').value);

    try {
        const res  = await fetch(`${BP}/facturacion/cambiar-plan/${_cambioPlanCid}`, { method:'POST', body:fd });
        const data = await res.json();
        if (data.ok) {
            document.getElementById('modalCambioPlan').style.display = 'none';
            location.reload();
        } else {
            err.style.display = 'block';
            err.textContent = data.msg || 'Error al aplicar el cambio.';
        }
    } catch(ex) {
        err.style.display = 'block';
        err.textContent = 'Error de conexión.';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-file-invoice-dollar"></i> Confirmar y generar factura';
}

document.getElementById('modalCambioPlan').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

// Badge chat
async function actualizarChatBadge() {
    try {
        const r = await fetch(`${BP}/chat/no-leidos`);
        const d = await r.json();
        const el = document.getElementById('chatBadge');
        if (el) { if (d.total > 0) { el.textContent = d.total; el.style.display = ''; } else el.style.display = 'none'; }
    } catch(e) {}
}
actualizarChatBadge();
setInterval(actualizarChatBadge, 10000);
</script>
</body>
</html>
