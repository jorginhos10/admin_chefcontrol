<?php
require_once __DIR__ . '/../../config/config.php';
$basePath = SupConfig::getBasePath();
$baseUrl  = SupConfig::getBaseUrl();
$nombre   = htmlspecialchars($_SESSION['sup_nombre'] ?? 'Admin');

$movimientos = $financiera['movimientos'];

function metodoIcono(string $m): string {
    $map = [
        'efectivo'      => 'fa-money-bill',
        'transferencia' => 'fa-building-columns',
        'tarjeta'       => 'fa-credit-card',
        'nequi'         => 'fa-mobile',
        'daviplata'     => 'fa-mobile',
        'epayco'        => 'fa-credit-card',
        'otro'          => 'fa-receipt',
    ];
    return $map[$m] ?? 'fa-receipt';
}

function metodoLabel(string $m): string {
    $map = [
        'efectivo'      => 'Efectivo',
        'transferencia' => 'Transferencia',
        'tarjeta'       => 'Tarjeta',
        'nequi'         => 'Nequi',
        'daviplata'     => 'Daviplata',
        'epayco'        => 'ePayco',
        'otro'          => 'Otro',
    ];
    return $map[$m] ?? ucfirst($m);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financiera — ChefControl SUP</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .tbl-fin td, .tbl-fin th { padding: 11px 14px; vertical-align: middle; }
        .tbl-fin tbody tr:hover { background: rgba(99,102,241,.06); }
        .search-wrap { position: relative; }
        .search-wrap input { padding-left: 34px; }
        .search-wrap .s-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #555; font-size: 13px; }
        .fin-filtros {
            display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap;
        }
        .fin-filtros label { color: #888; font-size: 11px; font-weight: 700; display: block; margin-bottom: 4px; }
        .fin-filtros input[type="date"] {
            background: #12121e; border: 1px solid #2d2d44; border-radius: 6px;
            padding: 8px 10px; color: #e0e0e0; font-size: 13px; outline: none;
        }
        .fin-filtros button {
            background: #6366f1; border: none; color: #fff; border-radius: 6px;
            padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 6px; height: 36px;
        }
        .fin-metodo-pill {
            display: inline-flex; align-items: center; gap: 6px;
            background: #12121e; border: 1px solid #2d2d44; border-radius: 999px;
            padding: 6px 12px; font-size: 12px; color: #ccc;
        }
        .fin-metodo-pill b { color: #22c55e; }
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
        <a class="nav-item" href="<?= $basePath ?>/facturacion">
            <i class="fas fa-file-invoice-dollar"></i> Facturación
        </a>
        <a class="nav-item active" href="<?= $basePath ?>/financiera">
            <i class="fas fa-sack-dollar"></i> Financiera
        </a>
        <a class="nav-item" href="<?= $basePath ?>/mensajeria">
            <i class="fas fa-sms"></i> Mensajería
        </a>
        <a class="nav-item" href="<?= $basePath ?>/planes">
            <i class="fas fa-layer-group"></i> Planes
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
            <h2>Financiera</h2>
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
                <div class="stat-icon green"><i class="fas fa-sack-dollar"></i></div>
                <div>
                    <div class="stat-value">$<?= number_format($financiera['total_historico'], 0, ',', '.') ?></div>
                    <div class="stat-label">Total recibido (histórico)</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <div class="stat-value">$<?= number_format($financiera['total_mes_actual'], 0, ',', '.') ?></div>
                    <div class="stat-label">Ingresos del mes actual</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon cyan"><i class="fas fa-filter"></i></div>
                <div>
                    <div class="stat-value">$<?= number_format($financiera['total_periodo'], 0, ',', '.') ?></div>
                    <div class="stat-label">En el período filtrado</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,.2);color:#f59e0b;">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#f59e0b;"><?= $financiera['cantidad_periodo'] ?></div>
                    <div class="stat-label">Movimientos en el período</div>
                </div>
            </div>
        </div>

        <!-- Desglose por método de pago -->
        <?php if (!empty($financiera['por_metodo'])): ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;">
            <?php foreach ($financiera['por_metodo'] as $m => $total): ?>
            <span class="fin-metodo-pill">
                <i class="fas <?= metodoIcono($m) ?>"></i>
                <?= metodoLabel($m) ?>:
                <b>$<?= number_format($total, 0, ',', '.') ?></b>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Tabla de movimientos -->
        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-list"></i>
                    <h3>Movimientos</h3>
                    <span><?= count($movimientos) ?> registros</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <form method="get" action="<?= $basePath ?>/financiera" class="fin-filtros">
                        <div>
                            <label>Desde</label>
                            <input type="date" name="desde" value="<?= htmlspecialchars($filtroDesde) ?>">
                        </div>
                        <div>
                            <label>Hasta</label>
                            <input type="date" name="hasta" value="<?= htmlspecialchars($filtroHasta) ?>">
                        </div>
                        <button type="submit"><i class="fas fa-search"></i> Filtrar</button>
                    </form>
                    <div class="search-wrap">
                        <i class="fas fa-search s-icon"></i>
                        <input id="buscar" type="text" placeholder="Buscar comercio..."
                               oninput="filtrar(this.value)"
                               style="background:#12121e;border:1px solid #2d2d44;border-radius:6px;
                                      padding:8px 12px 8px 32px;color:#e0e0e0;font-size:13px;
                                      outline:none;width:200px;">
                    </div>
                </div>
            </div>

            <?php if (empty($movimientos)): ?>
                <div class="empty-state">
                    <i class="fas fa-sack-dollar"></i>
                    No hay movimientos registrados en este período.
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="tbl-fin" id="tablaFin" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #1e1e30;">
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">FECHA</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">COMERCIO</th>
                        <th style="text-align:right;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">MONTO</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">MÉTODO</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">VÁLIDO HASTA</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">REFERENCIA</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">ESTADO</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($movimientos as $m): ?>
                    <tr data-nombre="<?= strtolower(htmlspecialchars($m['comercio_nombre'])) ?>">
                        <td style="color:#aaa;font-size:13px;"><?= date('d/m/Y', strtotime($m['fecha'])) ?></td>
                        <td>
                            <div style="font-weight:600;color:#e0e0e0;"><?= htmlspecialchars($m['comercio_nombre']) ?></div>
                            <div style="color:#555;font-size:11px;"><?= htmlspecialchars($m['comercio_slug']) ?></div>
                        </td>
                        <td style="text-align:right;color:#22c55e;font-weight:700;">
                            $<?= number_format((float)$m['monto'], 0, ',', '.') ?>
                        </td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:6px;color:#ccc;font-size:12px;">
                                <i class="fas <?= metodoIcono($m['metodo']) ?>" style="color:#6366f1;"></i>
                                <?= metodoLabel($m['metodo']) ?>
                            </span>
                        </td>
                        <td style="color:#888;font-size:12px;">
                            <?= $m['periodo_hasta'] ? date('d/m/Y', strtotime($m['periodo_hasta'])) : '<span style="color:#444;">—</span>' ?>
                        </td>
                        <td style="color:#888;font-size:12px;"><?= htmlspecialchars($m['referencia'] ?: '—') ?></td>
                        <td>
                            <?php
                            $estadoMap = [
                                'pagado'    => ['#0d2a0d', '#22c55e', 'Pagado'],
                                'pendiente' => ['#2a1e0d', '#f59e0b', 'Pendiente'],
                                'fallido'   => ['#2a0d0d', '#ef4444', 'Fallido'],
                            ];
                            [$bg, $color, $lbl] = $estadoMap[$m['estado']] ?? ['#1e1e30', '#aaa', $m['estado']];
                            ?>
                            <span style="background:<?= $bg ?>;color:<?= $color ?>;padding:2px 10px;
                                         border-radius:999px;font-size:11px;font-weight:700;"><?= $lbl ?></span>
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

<script>
const BP = '<?= $basePath ?>';

function filtrar(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tablaFin tbody tr').forEach(tr => {
        tr.style.display = tr.dataset.nombre.includes(q) ? '' : 'none';
    });
}

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
