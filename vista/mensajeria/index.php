<?php
require_once __DIR__ . '/../../config/config.php';
$basePath = SupConfig::getBasePath();
$baseUrl  = SupConfig::getBaseUrl();
$nombre   = htmlspecialchars($_SESSION['sup_nombre'] ?? 'Admin');

function tipoBadge(string $tipo): string {
    $map = [
        'IMMEDIATE' => ['#0d2a0d', '#22c55e', 'Enviado'],
        'SCHEDULED' => ['#2a1e0d', '#f59e0b', 'Programado'],
    ];
    [$bg, $color, $lbl] = $map[$tipo] ?? ['#1e1e30', '#aaa', $tipo];
    return "<span style='background:{$bg};color:{$color};padding:2px 10px;border-radius:999px;
                         font-size:11px;font-weight:700;'>{$lbl}</span>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajería — ChefControl SUP</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .msj-filtros { display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; }
        .msj-filtros label { color: #888; font-size: 11px; font-weight: 700; display: block; margin-bottom: 4px; }
        .msj-filtros input[type="date"] {
            background: #12121e; border: 1px solid #2d2d44; border-radius: 6px;
            padding: 8px 10px; color: #e0e0e0; font-size: 13px; outline: none;
        }
        .msj-filtros button, .msj-csv {
            background: #6366f1; border: none; color: #fff; border-radius: 6px;
            padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 6px; height: 36px; text-decoration: none;
        }
        .actividad-item {
            border: 1px solid #1e1e30; border-radius: 10px; padding: 14px 16px;
            margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap;
        }
        .actividad-resumen span { margin-right: 14px; font-size: 12px; }
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
        <a class="nav-item" href="<?= $basePath ?>/financiera">
            <i class="fas fa-wallet"></i> Financiera
        </a>
        <a class="nav-item active" href="<?= $basePath ?>/mensajeria">
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
            <h2>Mensajería</h2>
            <p><?= date('d \d\e F, Y') ?></p>
        </div>
        <div style="display:flex;align-items:center;gap:14px;">
            <?php if (!empty($balance)): ?>
            <div style="background:#12121e;border:1px solid #2d2d44;border-radius:10px;
                        padding:9px 16px;text-align:right;">
                <div style="color:#555;font-size:10px;font-weight:700;letter-spacing:.5px;">SALDO SMS</div>
                <div style="color:#22c55e;font-weight:800;font-size:16px;">
                    $<?= number_format((float)($balance['balance'] ?? 0), 0, ',', '.') ?>
                    <span style="color:#666;font-size:11px;font-weight:400;"><?= htmlspecialchars($balance['currency'] ?? '') ?></span>
                </div>
                <div style="color:#888;font-size:11px;">
                    ≈ <?= number_format((float)($balance['estimatedMessages'] ?? 0), 0, ',', '.') ?> mensajes restantes
                </div>
            </div>
            <?php endif; ?>
            <a class="btn-logout" href="<?= $basePath ?>/logout">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </div>
    </header>

    <div class="content">

        <?php if (!$uso['ok']): ?>
        <div class="alert" style="background:#2a0d0d;color:#f87171;border:1px solid #7f1d1d;
                                   border-radius:8px;padding:12px 16px;margin-bottom:20px;">
            No se pudo consultar el uso de SMS: <?= htmlspecialchars($uso['msg'] ?? 'error desconocido') ?>
        </div>
        <?php endif; ?>

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header" style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <form method="get" action="<?= $basePath ?>/mensajeria" class="msj-filtros">
                    <div>
                        <label>Desde</label>
                        <input type="date" name="desde" value="<?= htmlspecialchars($desde) ?>">
                    </div>
                    <div>
                        <label>Hasta</label>
                        <input type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
                    </div>
                    <button type="submit"><i class="fas fa-search"></i> Filtrar</button>
                </form>
                <a class="msj-csv" href="<?= $basePath ?>/mensajeria/csv?desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>">
                    <i class="fas fa-download"></i> CSV
                </a>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid" style="margin-bottom:24px;">
            <div class="stat-card">
                <div class="stat-icon cyan"><i class="fas fa-paper-plane"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$uso['requests'] ?></div>
                    <div class="stat-label">Requests</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-coins"></i></div>
                <div>
                    <div class="stat-value">$<?= number_format($uso['gastado'], 0, ',', '.') ?></div>
                    <div class="stat-label">Gastado</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$uso['entregados'] ?></div>
                    <div class="stat-label">Entregados</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
                <div>
                    <div class="stat-value"><?= (int)$uso['fallidos'] ?></div>
                    <div class="stat-label">Fallidos</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,.2);color:#f59e0b;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <div class="stat-value" style="color:#f59e0b;"><?= (int)$uso['pendientes'] ?></div>
                    <div class="stat-label">Pendientes</div>
                </div>
            </div>
        </div>

        <!-- Actividad reciente -->
        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-list" style="color:#6366f1;"></i>
                <h3>Actividad reciente</h3>
                <span><?= count($uso['actividad']) ?> registros</span>
            </div>

            <?php if (empty($uso['actividad'])): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    No hay mensajes en este período.
                </div>
            <?php else: ?>
            <div style="padding:16px;">
                <?php foreach ($uso['actividad'] as $it):
                    $enviado  = !empty($it['isSent']) || ($it['status'] ?? '') === 'sent';
                    $fallido  = ($it['status'] ?? '') === 'failed';
                    $n        = (int)($it['nMessages'] ?? 1);
                ?>
                <div class="actividad-item">
                    <div>
                        <div style="color:#e0e0e0;font-weight:600;font-size:14px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <?php if (!empty($it['telefono'])): ?>
                                <i class="fas fa-mobile-screen" style="color:#6366f1;font-size:12px;"></i>
                                <?= htmlspecialchars($it['telefono']) ?>
                            <?php else: ?>
                                <?= $n ?> mensaje<?= $n === 1 ? '' : 's' ?>
                            <?php endif; ?>
                            <?= tipoBadge($it['type'] ?? '') ?>
                        </div>
                        <?php if (!empty($it['comercio_nombre'])): ?>
                        <div style="color:#666;font-size:11px;margin-top:2px;">
                            <i class="fas fa-store" style="margin-right:4px;"></i><?= htmlspecialchars($it['comercio_nombre']) ?>
                        </div>
                        <?php endif; ?>
                        <div style="color:#888;font-size:12px;margin-top:4px;">
                            <?= htmlspecialchars($it['sampleMessage'] ?? '') ?>
                        </div>
                        <div style="color:#555;font-size:11px;margin-top:4px;">
                            <?= htmlspecialchars($it['type'] ?? '') ?> · $<?= number_format((float)($it['amount'] ?? 0), 0, ',', '.') ?>
                        </div>
                        <div class="actividad-resumen" style="margin-top:6px;">
                            <span style="color:#22c55e;"><?= $enviado ? $n : 0 ?> entregados</span>
                            <span style="color:#ef4444;"><?= $fallido ? $n : 0 ?> fallidos</span>
                            <span style="color:#f59e0b;"><?= (!$enviado && !$fallido) ? $n : 0 ?> pendientes</span>
                        </div>
                    </div>
                    <div style="text-align:right;color:#aaa;font-size:13px;">
                        <?= !empty($it['createdAt']) ? date('d M, H:i', strtotime($it['createdAt'])) : '—' ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
const BP = '<?= $basePath ?>';
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
