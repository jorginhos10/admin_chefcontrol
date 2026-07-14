<?php
require_once __DIR__ . '/../../config/config.php';

$basePath = SupConfig::getBasePath();
$baseUrl  = SupConfig::getBaseUrl();
$supNombre = htmlspecialchars($_SESSION['sup_nombre'] ?? 'Admin');

// Planes reales configurados en /planes (solo los activos son seleccionables aquí)
$planes = [];
foreach ($planesReales ?? [] as $p) {
    if (!(int)$p['activo']) continue;
    $planes[$p['slug']] = [
        'label' => $p['nombre'],
        'color' => $p['color'] ?: '#6b7280',
        'icon'  => 'fa-crown',
        'desc'  => $p['descripcion'] ?? '',
    ];
}

$idiomas = [
    'es' => ['label'=>'Español',    'flag'=>'🇪🇸', 'desc'=>'Idioma predeterminado'],
    'en' => ['label'=>'English',    'flag'=>'🇺🇸', 'desc'=>'English language'],
    'pt' => ['label'=>'Português',  'flag'=>'🇧🇷', 'desc'=>'Idioma português'],
];
$idiomaActual = $comercio['idioma'] ?? 'es';

$planActual      = $comercio['plan']          ?? 'gratuito';
$vence           = $comercio['plan_vence']   ?? null;
$notas           = $comercio['plan_notas']   ?? '';
$desactivadosRaw = $comercio['modulos_config'] ?? null;
$desactivados    = $desactivadosRaw ? (json_decode($desactivadosRaw, true) ?? []) : [];

$todosModulos = [
    'ventas'         => ['label'=>'Ventas',          'icon'=>'fa-cash-register',       'desc'=>'Registro de ventas y cobros'],
    'cocina'         => ['label'=>'Cocina',           'icon'=>'fa-fire-burner',          'desc'=>'Panel de órdenes en cocina'],
    'mesas'          => ['label'=>'Salón / Mesas',    'icon'=>'fa-chair',                'desc'=>'Gestión de mesas y salón'],
    'menu-digital'   => ['label'=>'Menú Digital',     'icon'=>'fa-qrcode',               'desc'=>'Menús QR y pedidos desde la mesa'],
    'domicilios'     => ['label'=>'Domicilios',       'icon'=>'fa-motorcycle',           'desc'=>'Pedidos y links de domicilio'],
    'clientes'       => ['label'=>'Clientes',         'icon'=>'fa-users',                'desc'=>'Base de datos de clientes'],
    'recetas'        => ['label'=>'Recetas',          'icon'=>'fa-book-open',            'desc'=>'Fichas técnicas de productos'],
    'insumos'        => ['label'=>'Insumos',          'icon'=>'fa-boxes-stacked',        'desc'=>'Gestión de materias primas'],
    'insumos-internos' => ['label'=>'Uso Interno',    'icon'=>'fa-broom',                'desc'=>'Insumos de uso interno (limpieza, papelería, etc.)'],
    'inventario'     => ['label'=>'Inventario',       'icon'=>'fa-warehouse',            'desc'=>'Control de stock e inventario'],
    'reportes'       => ['label'=>'Reportes',         'icon'=>'fa-chart-bar',            'desc'=>'Cierres Z, reportes X y KPIs'],
    'chat'           => ['label'=>'Chat interno',     'icon'=>'fa-comments',             'desc'=>'Mensajería entre empleados'],
    'cupones'        => ['label'=>'Cupones',          'icon'=>'fa-ticket',               'desc'=>'Descuentos y promociones'],
    'propinas'       => ['label'=>'Propinas',         'icon'=>'fa-hand-holding-dollar',  'desc'=>'Gestión y distribución de propinas'],
    'pqrs'           => ['label'=>'PQRS',             'icon'=>'fa-comment-dots',         'desc'=>'Peticiones, quejas y sugerencias'],
    'proveedores'    => ['label'=>'Proveedores',      'icon'=>'fa-truck',                'desc'=>'Directorio y pedidos a proveedores'],
    'ingresos'       => ['label'=>'Ingresos',         'icon'=>'fa-circle-plus',          'desc'=>'Registro de ingresos adicionales'],
    'perdidas'       => ['label'=>'Pérdidas',         'icon'=>'fa-circle-minus',         'desc'=>'Control de mermas y pérdidas'],
    'notificaciones' => ['label'=>'Notificaciones',   'icon'=>'fa-bell',                 'desc'=>'Alertas y notificaciones del sistema'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Config — <?= htmlspecialchars($comercio['nombre']) ?> — ChefControl SUP</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .config-grid { display:grid; grid-template-columns:260px 1fr; gap:24px; align-items:start; }

        /* Sidebar de secciones */
        .config-sections {
            background:#161625; border:1px solid #2d2d44;
            border-radius:12px; overflow:hidden; position:sticky; top:0;
        }
        .config-section-item {
            display:flex; align-items:center; gap:12px; padding:14px 18px;
            color:#aaa; font-size:14px; cursor:pointer; border-left:3px solid transparent;
            transition:all .15s; text-decoration:none;
        }
        .config-section-item:hover  { background:#1e1e30; color:#e0e0e0; }
        .config-section-item.active { background:#1e1e30; color:#fff;
                                       border-left-color:#7c3aed; font-weight:600; }
        .config-section-item i { width:18px; text-align:center; color:#7c3aed; }

        /* Cards de sección */
        .config-card {
            background:#161625; border:1px solid #2d2d44;
            border-radius:12px; padding:28px; display:none;
        }
        .config-card.visible { display:block; }
        .config-card h3 {
            color:#fff; font-size:17px; margin:0 0 6px;
            display:flex; align-items:center; gap:10px;
        }
        .config-card h3 i { color:#7c3aed; }
        .config-card .card-desc { color:#888; font-size:13px; margin:0 0 24px; }

        /* Plan cards */
        .plan-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }
        .plan-card {
            border:2px solid #2d2d44; border-radius:10px; padding:18px;
            cursor:pointer; transition:all .2s; position:relative;
        }
        .plan-card:hover { border-color:#7c3aed; background:#1a1a2e; }
        .plan-card.selected { border-color:var(--pc); background:#1a1a2e; }
        .plan-card .pc-icon {
            width:40px; height:40px; border-radius:8px;
            display:flex; align-items:center; justify-content:center;
            font-size:18px; margin-bottom:10px;
            background:rgba(255,255,255,.06);
        }
        .plan-card .pc-name { color:#e0e0e0; font-weight:700; font-size:15px; }
        .plan-card .pc-desc { color:#888; font-size:12px; margin-top:4px; }
        .plan-card .pc-check {
            position:absolute; top:12px; right:12px;
            width:22px; height:22px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:12px; background:var(--pc); color:#fff;
            opacity:0; transition:opacity .2s;
        }
        .plan-card.selected .pc-check { opacity:1; }

        /* Inputs */
        .field { margin-bottom:18px; }
        .field label { color:#aaa; font-size:12px; display:block; margin-bottom:6px; letter-spacing:.3px; }
        .field input, .field textarea, .field select {
            width:100%; box-sizing:border-box; background:#12121e;
            border:1px solid #2d2d44; border-radius:8px; padding:10px 14px;
            color:#e0e0e0; font-size:14px; outline:none; font-family:inherit;
        }
        .field input:focus, .field textarea:focus { border-color:#4f46e5; }

        .btn-save {
            background:linear-gradient(135deg,#7c3aed,#4f46e5);
            color:#fff; border:none; border-radius:8px;
            padding:12px 28px; font-size:14px; font-weight:600;
            cursor:pointer; transition:opacity .2s;
        }
        .btn-save:hover { opacity:.85; }

        .toast {
            position:fixed; bottom:28px; right:28px; z-index:9999;
            background:#22c55e; color:#fff; padding:12px 20px;
            border-radius:8px; font-size:14px; font-weight:600;
            box-shadow:0 8px 24px rgba(0,0,0,.4);
            transform:translateY(20px); opacity:0;
            transition:all .3s;
        }
        .toast.show { transform:translateY(0); opacity:1; }
        .toast.error { background:#ef4444; }

        /* Toggle switches */
        .modulo-row {
            display:flex; align-items:center; justify-content:space-between;
            padding:14px 16px; border:1px solid #2d2d44; border-radius:10px;
            background:#12121e; transition:border-color .15s;
        }
        .modulo-row:hover { border-color:#3d3d5e; }
        .modulo-row.off { opacity:.55; }
        .modulo-info { display:flex; align-items:center; gap:12px; }
        .modulo-icon {
            width:36px; height:36px; border-radius:8px; background:#1e1e30;
            display:flex; align-items:center; justify-content:center;
            font-size:15px; color:#7c3aed; flex-shrink:0;
        }
        .modulo-label  { color:#e0e0e0; font-size:14px; font-weight:600; }
        .modulo-desc   { color:#666; font-size:12px; margin-top:2px; }
        .modulo-grid   { display:grid; grid-template-columns:1fr 1fr; gap:10px; }

        /* Toggle */
        .tog { position:relative; display:inline-block; width:44px; height:24px; flex-shrink:0; }
        .tog input { opacity:0; width:0; height:0; }
        .tog-slider {
            position:absolute; inset:0; background:#2d2d44; border-radius:99px;
            cursor:pointer; transition:.2s;
        }
        .tog-slider:before {
            content:''; position:absolute; height:18px; width:18px; left:3px; bottom:3px;
            background:#fff; border-radius:50%; transition:.2s;
        }
        .tog input:checked + .tog-slider { background:#7c3aed; }
        .tog input:checked + .tog-slider:before { transform:translateX(20px); }
    </style>
</head>
<body>

<!-- Sidebar -->
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
        <a class="nav-item" href="<?= $basePath ?>/chat">
            <i class="fas fa-comments"></i> Chat
        </a>
        <a class="nav-item" href="<?= $basePath ?>/facturacion">
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
        <div class="admin-name"><?= $supNombre ?></div>
        <div><?= htmlspecialchars($_SESSION['sup_username'] ?? '') ?></div>
    </div>
</aside>

<!-- Main -->
<div class="main">
    <header class="topbar">
        <div style="display:flex;align-items:center;gap:14px;">
            <a href="<?= $basePath ?>/dashboard"
               style="color:#888;text-decoration:none;font-size:14px;display:flex;align-items:center;gap:6px;">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <div>
                <h2 style="display:flex;align-items:center;gap:10px;">
                    <span style="background:#1e1e30;border:1px solid #2d2d44;border-radius:8px;
                                 width:34px;height:34px;display:inline-flex;align-items:center;
                                 justify-content:center;font-size:15px;color:#7c3aed;">
                        <?= mb_strtoupper(mb_substr($comercio['nombre'], 0, 1)) ?>
                    </span>
                    <?= htmlspecialchars($comercio['nombre']) ?>
                    <?php if ($comercio['verificado']): ?>
                        <i class="fas fa-circle-check" style="color:#22c55e;font-size:16px;" title="Verificado"></i>
                    <?php endif; ?>
                </h2>
                <p style="color:#888;font-size:13px;margin:0;">
                    Slug: <code style="color:#7c3aed;"><?= htmlspecialchars($comercio['slug'] ?? '—') ?></code>
                    &nbsp;·&nbsp; <?= $totalUsuarios ?> usuario<?= $totalUsuarios !== 1 ? 's' : '' ?>
                    &nbsp;·&nbsp; Registrado: <?= date('d/m/Y', strtotime($comercio['created_at'])) ?>
                </p>
            </div>
        </div>
        <a class="btn-logout" href="<?= $basePath ?>/logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </header>

    <div class="content">
        <div class="config-grid">

            <!-- Menú lateral de secciones -->
            <div class="config-sections">
                <a class="config-section-item active" onclick="mostrarSeccion('plan', this)">
                    <i class="fas fa-layer-group"></i> Plan de suscripción
                </a>
                <a class="config-section-item" onclick="mostrarSeccion('modulos', this)">
                    <i class="fas fa-puzzle-piece"></i> Módulos
                </a>
                <a class="config-section-item" onclick="mostrarSeccion('idioma', this)">
                    <i class="fas fa-language"></i> Idioma
                </a>
            </div>

            <!-- Secciones -->
            <div>

                <!-- ── Plan ─────────────────────────────────────────────── -->
                <div class="config-card visible" id="sec-plan">
                    <h3><i class="fas fa-layer-group"></i> Plan de suscripción</h3>
                    <p class="card-desc">Cambia el plan activo de este restaurante y configura la fecha de vencimiento.</p>

                    <!-- Plan actual badge -->
                    <?php $pi = $planes[$planActual] ?? [
                        'label' => ucfirst($planActual),
                        'color' => '#6b7280',
                        'icon'  => 'fa-question',
                        'desc'  => 'Este plan ya no está disponible o fue desactivado.',
                    ]; ?>
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;
                                background:#12121e;border:1px solid #2d2d44;border-radius:10px;padding:16px;">
                        <div style="width:44px;height:44px;border-radius:10px;display:flex;align-items:center;
                                    justify-content:center;font-size:20px;background:<?= $pi['color'] ?>22;color:<?= $pi['color'] ?>;">
                            <i class="fas <?= $pi['icon'] ?>"></i>
                        </div>
                        <div>
                            <div style="color:#aaa;font-size:12px;">Plan actual</div>
                            <div style="color:#fff;font-weight:700;font-size:16px;"><?= $pi['label'] ?></div>
                        </div>
                        <?php if ($vence): ?>
                        <div style="margin-left:auto;text-align:right;">
                            <div style="color:#aaa;font-size:11px;">Vence</div>
                            <div style="color:<?= strtotime($vence) < time() ? '#ef4444' : '#22c55e' ?>;font-weight:600;font-size:14px;">
                                <?= date('d/m/Y', strtotime($vence)) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <form id="formPlan">
                        <!-- Selección de plan -->
                        <div class="field">
                            <label>SELECCIONAR PLAN</label>
                            <div class="plan-grid">
                                <?php foreach ($planes as $key => $p): ?>
                                <div class="plan-card <?= $planActual === $key ? 'selected' : '' ?>"
                                     style="--pc:<?= $p['color'] ?>;"
                                     onclick="seleccionarPlan('<?= $key ?>', this)">
                                    <div class="pc-icon" style="color:<?= $p['color'] ?>;">
                                        <i class="fas <?= $p['icon'] ?>"></i>
                                    </div>
                                    <div class="pc-name"><?= $p['label'] ?></div>
                                    <div class="pc-desc"><?= $p['desc'] ?></div>
                                    <div class="pc-check"><i class="fas fa-check"></i></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="planSeleccionado" name="plan" value="<?= $planActual ?>">
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <div class="field">
                                <label>FECHA DE VENCIMIENTO <span style="color:#555;">(opcional)</span></label>
                                <input type="date" name="plan_vence"
                                       value="<?= htmlspecialchars($vence ?? '') ?>">
                            </div>
                            <div class="field">
                                <label>NOTAS INTERNAS</label>
                                <input type="text" name="plan_notas" placeholder="Ej: Pagó via transferencia"
                                       value="<?= htmlspecialchars($notas ?? '') ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn-save" id="btnGuardarPlan">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                    </form>
                </div>

                <!-- ── Módulos ─────────────────────────────────────────── -->
                <div class="config-card" id="sec-modulos">
                    <h3><i class="fas fa-puzzle-piece"></i> Módulos</h3>
                    <p class="card-desc">
                        Activa o desactiva módulos para este restaurante.
                        Los módulos desactivados no aparecen en el panel del negocio.
                    </p>

                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                        <span style="color:#aaa;font-size:12px;letter-spacing:.4px;">MÓDULOS DISPONIBLES</span>
                        <div style="display:flex;gap:8px;">
                            <button type="button" onclick="toggleTodos(true)"
                                    style="background:#14532d;color:#86efac;border:none;border-radius:6px;
                                           padding:5px 12px;font-size:12px;cursor:pointer;">
                                Activar todos
                            </button>
                            <button type="button" onclick="toggleTodos(false)"
                                    style="background:#7f1d1d;color:#fca5a5;border:none;border-radius:6px;
                                           padding:5px 12px;font-size:12px;cursor:pointer;">
                                Desactivar todos
                            </button>
                        </div>
                    </div>

                    <form id="formModulos">
                        <div class="modulo-grid" id="modulosGrid">
                        <?php foreach ($todosModulos as $key => $m):
                            $activo = !in_array($key, $desactivados); ?>
                        <div class="modulo-row <?= $activo ? '' : 'off' ?>" id="mrow-<?= $key ?>">
                            <div class="modulo-info">
                                <div class="modulo-icon"><i class="fas <?= $m['icon'] ?>"></i></div>
                                <div>
                                    <div class="modulo-label"><?= $m['label'] ?></div>
                                    <div class="modulo-desc"><?= $m['desc'] ?></div>
                                </div>
                            </div>
                            <label class="tog">
                                <input type="checkbox" name="modulos[<?= $key ?>]" value="1"
                                       <?= $activo ? 'checked' : '' ?>
                                       onchange="actualizarRow('<?= $key ?>', this.checked)">
                                <span class="tog-slider"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        </div>

                        <div style="margin-top:20px;display:flex;align-items:center;gap:12px;">
                            <button type="submit" class="btn-save" id="btnGuardarModulos">
                                <i class="fas fa-save"></i> Guardar cambios
                            </button>
                            <span id="modulosStatus" style="color:#888;font-size:13px;"></span>
                        </div>
                    </form>
                </div>

                <!-- ── Idioma ──────────────────────────────────────────── -->
                <div class="config-card" id="sec-idioma">
                    <h3><i class="fas fa-language"></i> Idioma</h3>
                    <p class="card-desc">Selecciona el idioma con el que este restaurante usará el panel.</p>

                    <form id="formIdioma">
                        <div class="field">
                            <label>SELECCIONAR IDIOMA</label>
                            <div class="plan-grid" style="grid-template-columns:repeat(3,1fr);">
                                <?php foreach ($idiomas as $key => $idm): ?>
                                <div class="plan-card <?= $idiomaActual === $key ? 'selected' : '' ?>"
                                     style="--pc:#7c3aed;"
                                     onclick="seleccionarIdioma('<?= $key ?>', this)">
                                    <div class="pc-icon" style="font-size:22px;background:transparent;"><?= $idm['flag'] ?></div>
                                    <div class="pc-name"><?= $idm['label'] ?></div>
                                    <div class="pc-desc"><?= $idm['desc'] ?></div>
                                    <div class="pc-check"><i class="fas fa-check"></i></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="idiomaSeleccionado" name="idioma" value="<?= $idiomaActual ?>">
                        </div>

                        <button type="submit" class="btn-save" id="btnGuardarIdioma">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                    </form>
                </div>

            </div><!-- /secciones -->
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
const CID  = <?= $comercio['id'] ?>;
const BASE = '<?= $basePath ?>';

function mostrarSeccion(id, el) {
    document.querySelectorAll('.config-card').forEach(c => c.classList.remove('visible'));
    document.querySelectorAll('.config-section-item').forEach(c => c.classList.remove('active'));
    document.getElementById('sec-' + id).classList.add('visible');
    el.classList.add('active');
}

function seleccionarPlan(plan, el) {
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('planSeleccionado').value = plan;
}

document.getElementById('formPlan').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarPlan');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    const fd = new FormData(this);
    try {
        const res  = await fetch(`${BASE}/config/${CID}/plan`, { method:'POST', body:fd });
        const data = await res.json();
        mostrarToast(data.ok ? 'Plan actualizado correctamente' : (data.msg || 'Error'), !data.ok);
        if (data.ok) setTimeout(() => location.reload(), 1200);
    } catch(e) {
        mostrarToast('Error de conexión', true);
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Guardar cambios';
});

// ── Módulos ───────────────────────────────────────────────────────────────
function actualizarRow(key, checked) {
    const row = document.getElementById('mrow-' + key);
    if (row) row.classList.toggle('off', !checked);
}

function toggleTodos(estado) {
    document.querySelectorAll('#modulosGrid input[type=checkbox]').forEach(cb => {
        cb.checked = estado;
        actualizarRow(cb.name.match(/modulos\[(\w+)\]/)[1], estado);
    });
}

document.getElementById('formModulos').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarModulos');
    const st  = document.getElementById('modulosStatus');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    st.textContent = '';

    const fd = new FormData(this);
    try {
        const res  = await fetch(`${BASE}/config/${CID}/modulos`, { method:'POST', body:fd });
        const data = await res.json();
        if (data.ok) {
            mostrarToast('Módulos actualizados correctamente');
        } else {
            mostrarToast(data.msg || 'Error al guardar', true);
        }
    } catch(e) {
        mostrarToast('Error de conexión', true);
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Guardar cambios';
});

// ── Idioma ────────────────────────────────────────────────────────────────
function seleccionarIdioma(idioma, el) {
    el.parentElement.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('idiomaSeleccionado').value = idioma;
}

document.getElementById('formIdioma').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarIdioma');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    const fd = new FormData(this);
    try {
        const res  = await fetch(`${BASE}/config/${CID}/idioma`, { method:'POST', body:fd });
        const data = await res.json();
        mostrarToast(data.ok ? 'Idioma actualizado correctamente' : (data.msg || 'Error'), !data.ok);
    } catch(e) {
        mostrarToast('Error de conexión', true);
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Guardar cambios';
});

function mostrarToast(msg, error = false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = 'toast' + (error ? ' error' : '');
    void t.offsetWidth;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}
</script>

</body>
</html>
