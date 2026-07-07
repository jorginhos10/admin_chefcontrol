<?php
require_once __DIR__ . '/../../config/config.php';
$basePath = SupConfig::getBasePath();
$baseUrl  = SupConfig::getBaseUrl();
$nombre   = htmlspecialchars($_SESSION['sup_nombre'] ?? 'Admin');

$registroWeb = ($supConfig['registro_web'] ?? '1') === '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraciones — ChefControl SUP</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cfg-section { background:#1e1e2e;border:1px solid #2d2d44;border-radius:14px;overflow:hidden;margin-bottom:20px; }
        .cfg-section-head { padding:16px 22px;border-bottom:1px solid #2d2d44;display:flex;align-items:center;gap:10px; }
        .cfg-section-head i { color:#7c3aed;font-size:16px; }
        .cfg-section-head h3 { color:#e0e0e0;font-size:15px;font-weight:700;margin:0; }
        .cfg-item { display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #12121e; }
        .cfg-item:last-child { border-bottom:none; }
        .cfg-item-info h4 { color:#e0e0e0;font-size:14px;font-weight:600;margin:0 0 4px; }
        .cfg-item-info p  { color:#6b7280;font-size:12px;margin:0; }
        /* Toggle */
        .cfg-toggle { position:relative;width:48px;height:26px;flex-shrink:0; }
        .cfg-toggle input { opacity:0;width:0;height:0;position:absolute; }
        .cfg-toggle-slider { position:absolute;inset:0;background:#374151;border-radius:999px;
                             cursor:pointer;transition:.25s; }
        .cfg-toggle-slider::before { content:'';position:absolute;width:20px;height:20px;
                                     background:#fff;border-radius:50%;top:3px;left:3px;transition:.25s; }
        .cfg-toggle input:checked + .cfg-toggle-slider { background:#7c3aed; }
        .cfg-toggle input:checked + .cfg-toggle-slider::before { transform:translateX(22px); }
        .cfg-status { font-size:11px;font-weight:700;margin-top:6px; }
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
            <i class="fas fa-sack-dollar"></i> Financiera
        </a>
        <a class="nav-item" href="<?= $basePath ?>/mensajeria">
            <i class="fas fa-sms"></i> Mensajería
        </a>
        <a class="nav-item" href="<?= $basePath ?>/planes">
            <i class="fas fa-layer-group"></i> Planes
        </a>
        <a class="nav-item active" href="<?= $basePath ?>/configuraciones">
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
            <h2>Configuraciones</h2>
            <p>Ajustes globales del sistema</p>
        </div>
        <a class="btn-logout" href="<?= $basePath ?>/logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </header>

    <div class="content" style="max-width:700px;">

        <!-- Sección: Registro -->
        <div class="cfg-section">
            <div class="cfg-section-head">
                <i class="fas fa-user-plus"></i>
                <h3>Registro</h3>
            </div>

            <div class="cfg-item">
                <div class="cfg-item-info">
                    <h4>Registro desde sitio web</h4>
                    <p>Permite que nuevos restaurantes se registren libremente desde la página de registro.</p>
                    <div class="cfg-status" id="statusRegistroWeb"
                         style="color:<?= $registroWeb ? '#22c55e' : '#ef4444' ?>;">
                        <?= $registroWeb ? 'Activado' : 'Desactivado' ?>
                    </div>
                </div>
                <label class="cfg-toggle">
                    <input type="checkbox" id="toggleRegistroWeb"
                           <?= $registroWeb ? 'checked' : '' ?>
                           onchange="toggleConfig('registro_web', this.checked)">
                    <span class="cfg-toggle-slider"></span>
                </label>
            </div>
        </div>

    </div>
</div>

<script>
async function toggleConfig(clave, valor) {
    const fd = new FormData();
    fd.append('clave', clave);
    fd.append('valor', valor ? '1' : '0');
    try {
        const res  = await fetch('<?= $basePath ?>/configuraciones', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.ok) { alert(data.msg || 'Error al guardar.'); return; }
        if (clave === 'registro_web') {
            const st = document.getElementById('statusRegistroWeb');
            st.textContent = valor ? 'Activado' : 'Desactivado';
            st.style.color  = valor ? '#22c55e' : '#ef4444';
        }
    } catch(e) { alert('Error de conexión.'); }
}
</script>

</body>
</html>
