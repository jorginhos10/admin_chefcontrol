<?php
require_once __DIR__ . '/../../config/config.php';
$basePath = SupConfig::getBasePath();
$baseUrl  = SupConfig::getBaseUrl();
$nombre   = htmlspecialchars($_SESSION['sup_nombre'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios — ChefControl SUP</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .btn-acc { background:#12121e;border:1px solid #2d2d44;color:#aaa;border-radius:6px;
                   padding:6px 10px;font-size:12px;cursor:pointer;display:inline-flex;
                   align-items:center;gap:5px;transition:.15s; }
        .btn-acc:hover { background:#2d2d44;color:#e0e0e0; }
        .badge-estado { padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700; }
        .badge-activo    { background:#0f2a1a;color:#22c55e; }
        .badge-inactivo  { background:#2a1414;color:#ef4444; }

        /* Modal */
        .modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);
                          z-index:1000;align-items:center;justify-content:center; }
        .modal-box { background:#1e1e2e;border:1px solid #2d2d44;border-radius:16px;padding:28px;
                     width:100%;max-width:440px;position:relative; }
        .modal-close { position:absolute;top:14px;right:16px;background:none;border:none;
                        color:#555;font-size:18px;cursor:pointer; }
        .modal-close:hover { color:#e0e0e0; }
        .field-label { color:#aaa;font-size:12px;display:block;margin-bottom:5px;font-weight:600; }
        .field-input { width:100%;box-sizing:border-box;background:#12121e;border:1px solid #2d2d44;
                        border-radius:7px;padding:9px 12px;color:#e0e0e0;font-size:13px;outline:none; }
        .field-input:focus { border-color:#6366f1; }
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
        <a class="nav-item" href="<?= $basePath ?>/mensajeria">
            <i class="fas fa-sms"></i> Mensajería
        </a>
        <a class="nav-item" href="<?= $basePath ?>/planes">
            <i class="fas fa-layer-group"></i> Planes
        </a>
        <a class="nav-item active" href="<?= $basePath ?>/usuarios">
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
            <h2>Usuarios</h2>
            <p>Administradores con acceso al panel SUP</p>
        </div>
        <div style="display:flex;gap:12px;align-items:center;">
            <button onclick="abrirCrear()"
                    style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;
                           padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;
                           cursor:pointer;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-plus"></i> Nuevo usuario
            </button>
            <a class="btn-logout" href="<?= $basePath ?>/logout">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </div>
    </header>

    <div class="content">

        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-user-shield" style="color:#6366f1;"></i>
                    <h3>Administradores</h3>
                    <span><?= count($admins) ?> registros</span>
                </div>
                <div class="search-wrap">
                    <i class="fas fa-search s-icon"></i>
                    <input id="buscar" type="text" placeholder="Buscar usuario..."
                           oninput="filtrar(this.value)"
                           style="background:#12121e;border:1px solid #2d2d44;border-radius:6px;
                                  padding:8px 12px 8px 32px;color:#e0e0e0;font-size:13px;
                                  outline:none;width:220px;">
                </div>
            </div>

            <?php if (empty($admins)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-shield"></i>
                    No hay usuarios registrados.
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="tbl-fact" id="tablaAdmins" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid #1e1e30;">
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">NOMBRE</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">USUARIO</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">EMAIL</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">ÚLTIMO ACCESO</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">ESTADO</th>
                        <th style="text-align:left;color:#555;font-size:11px;font-weight:700;letter-spacing:.5px;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($admins as $a): $esYo = (int)$a['id'] === $currentId; ?>
                    <tr data-nombre="<?= strtolower(htmlspecialchars($a['nombre'] . ' ' . $a['username'])) ?>" id="row-<?= $a['id'] ?>">
                        <td>
                            <div style="font-weight:600;color:#e0e0e0;">
                                <?= htmlspecialchars($a['nombre']) ?>
                                <?php if ($esYo): ?><span style="color:#6366f1;font-size:11px;">(tú)</span><?php endif; ?>
                            </div>
                        </td>
                        <td style="color:#aaa;font-size:13px;"><?= htmlspecialchars($a['username']) ?></td>
                        <td style="color:#aaa;font-size:13px;"><?= htmlspecialchars($a['email'] ?? '') ?: '<span style="color:#444;">—</span>' ?></td>
                        <td style="color:#888;font-size:12px;">
                            <?= $a['ultimo_login'] ? date('d/m/Y H:i', strtotime($a['ultimo_login'])) : '<span style="color:#444;">Nunca</span>' ?>
                        </td>
                        <td>
                            <span class="badge-estado <?= (int)$a['activo'] ? 'badge-activo' : 'badge-inactivo' ?>" id="badge-<?= $a['id'] ?>">
                                <?= (int)$a['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-acc"
                                        onclick="abrirEditar(<?= htmlspecialchars(json_encode([
                                            'id'       => $a['id'],
                                            'nombre'   => $a['nombre'],
                                            'username' => $a['username'],
                                            'email'    => $a['email'],
                                        ]), ENT_QUOTES) ?>)"
                                        title="Editar">
                                    <i class="fas fa-pen"></i> Editar
                                </button>
                                <button class="btn-acc" id="btn-toggle-<?= $a['id'] ?>"
                                        onclick="toggleActivo(<?= $a['id'] ?>)"
                                        title="<?= (int)$a['activo'] ? 'Desactivar' : 'Activar' ?>"
                                        style="<?= $esYo ? 'opacity:.4;cursor:not-allowed;' : '' ?>"
                                        <?= $esYo ? 'disabled' : '' ?>>
                                    <i class="fas <?= (int)$a['activo'] ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                                </button>
                                <button class="btn-acc"
                                        onclick="eliminarAdmin(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['nombre'])) ?>')"
                                        title="Eliminar"
                                        style="color:#f87171;<?= $esYo ? 'opacity:.4;cursor:not-allowed;' : '' ?>"
                                        <?= $esYo ? 'disabled' : '' ?>>
                                    <i class="fas fa-trash"></i>
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

<!-- ── Modal Crear / Editar ────────────────────────────────────────────────── -->
<div class="modal-overlay" id="modalAdmin">
    <div class="modal-box">
        <button class="modal-close" onclick="cerrarModal()"><i class="fas fa-times"></i></button>
        <h3 id="modalTitulo" style="color:#fff;margin:0 0 20px;font-size:17px;"></h3>

        <div id="adminError" style="display:none;background:#3b1c1c;border:1px solid #e74c3c;
             color:#f87171;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;"></div>

        <form id="formAdmin" style="display:flex;flex-direction:column;gap:14px;">
            <input type="hidden" id="adminId" name="admin_id">

            <div>
                <label class="field-label">Nombre *</label>
                <input class="field-input" id="adminNombre" name="nombre" type="text" required placeholder="Juan Pérez">
            </div>
            <div>
                <label class="field-label">Usuario *</label>
                <input class="field-input" id="adminUsername" name="username" type="text" required placeholder="jperez">
            </div>
            <div>
                <label class="field-label">Email</label>
                <input class="field-input" id="adminEmail" name="email" type="email" placeholder="juan@chefcontrol.co">
            </div>
            <div>
                <label class="field-label" id="adminPasswordLabel">Contraseña *</label>
                <input class="field-input" id="adminPassword" name="password" type="password" placeholder="Mínimo 6 caracteres">
            </div>

            <button type="submit" id="btnGuardarAdmin"
                    style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;
                           padding:12px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;margin-top:4px;">
                <i class="fas fa-save"></i> Guardar usuario
            </button>
        </form>
    </div>
</div>

<script>
const BP = '<?= $basePath ?>';

// ── Modal ─────────────────────────────────────────────────────────────────────
function cerrarModal() { document.getElementById('modalAdmin').style.display = 'none'; }
document.getElementById('modalAdmin').addEventListener('click', function(e) { if (e.target===this) cerrarModal(); });

function abrirCrear() {
    document.getElementById('adminId').value        = '';
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-plus" style="color:#7c3aed;margin-right:8px;"></i>Nuevo usuario';
    document.getElementById('formAdmin').reset();
    document.getElementById('adminPassword').required = true;
    document.getElementById('adminPasswordLabel').textContent = 'Contraseña *';
    document.getElementById('adminError').style.display = 'none';
    document.getElementById('modalAdmin').style.display = 'flex';
}

function abrirEditar(a) {
    document.getElementById('adminId').value       = a.id;
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-pen" style="color:#f59e0b;margin-right:8px;"></i>Editar usuario';
    document.getElementById('adminNombre').value    = a.nombre   || '';
    document.getElementById('adminUsername').value  = a.username || '';
    document.getElementById('adminEmail').value     = a.email    || '';
    document.getElementById('adminPassword').value  = '';
    document.getElementById('adminPassword').required = false;
    document.getElementById('adminPasswordLabel').textContent = 'Contraseña (dejar en blanco para no cambiar)';
    document.getElementById('adminError').style.display = 'none';
    document.getElementById('modalAdmin').style.display = 'flex';
}

// ── Submit ────────────────────────────────────────────────────────────────────
document.getElementById('formAdmin').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarAdmin');
    const err = document.getElementById('adminError');
    const id  = document.getElementById('adminId').value;
    const url = id ? `${BP}/usuarios/editar/${id}` : `${BP}/usuarios/crear`;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    err.style.display = 'none';

    const fd = new FormData(this);

    try {
        const res  = await fetch(url, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) { cerrarModal(); location.reload(); }
        else { err.style.display = 'block'; err.textContent = data.msg || 'Error al guardar.'; }
    } catch(ex) {
        err.style.display = 'block'; err.textContent = 'Error de conexión.';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Guardar usuario';
});

// ── Toggle activo ─────────────────────────────────────────────────────────────
async function toggleActivo(id) {
    const res  = await fetch(`${BP}/usuarios/toggle/${id}`);
    const data = await res.json();
    if (!data.ok) { alert(data.msg); return; }
    const badge = document.getElementById(`badge-${id}`);
    const btn   = document.getElementById(`btn-toggle-${id}`);
    if (data.activo) {
        badge.textContent = 'Activo';
        badge.className   = 'badge-estado badge-activo';
        btn.innerHTML      = '<i class="fas fa-ban"></i>';
        btn.title          = 'Desactivar';
    } else {
        badge.textContent = 'Inactivo';
        badge.className   = 'badge-estado badge-inactivo';
        btn.innerHTML      = '<i class="fas fa-circle-check"></i>';
        btn.title          = 'Activar';
    }
}

// ── Eliminar ──────────────────────────────────────────────────────────────────
async function eliminarAdmin(id, nombre) {
    if (!confirm(`¿Eliminar al usuario "${nombre}"? Esta acción no se puede deshacer.`)) return;
    const fd  = new FormData();
    const res  = await fetch(`${BP}/usuarios/eliminar/${id}`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) { document.getElementById(`row-${id}`).remove(); }
    else alert(data.msg);
}

// ── Buscador ──────────────────────────────────────────────────────────────────
function filtrar(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tablaAdmins tbody tr').forEach(tr => {
        tr.style.display = tr.dataset.nombre.includes(q) ? '' : 'none';
    });
}

// ── Chat badge ────────────────────────────────────────────────────────────────
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
