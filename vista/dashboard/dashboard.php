<?php
// chefcontrol-sup/vista/dashboard/dashboard.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../modelo/adminModel.php';

$basePath = SupConfig::getBasePath();
$baseUrl  = SupConfig::getBaseUrl();

$model = new AdminModel();
$stats = $model->estadisticasGlobales();

$nombre = htmlspecialchars($_SESSION['sup_nombre'] ?? 'Admin');
$fecha  = date('d \d\e F, Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefControl SUP — Dashboard</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        <a class="nav-item active" href="<?= $basePath ?>/dashboard">
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

<!-- Main -->
<div class="main">

    <header class="topbar">
        <div>
            <h2>Dashboard</h2>
            <p><?= $fecha ?></p>
        </div>
        <div style="display:flex;gap:12px;align-items:center;">
            <button onclick="abrirModalInvitar()"
                    style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;
                           padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;
                           cursor:pointer;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-plus"></i> Crear restaurante
            </button>
            <a class="btn-logout" href="<?= $basePath ?>/logout">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </div>
    </header>

    <div class="content">

        <?php if (!empty($stats['error'])): ?>
        <div class="alert-warn">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($stats['error']) ?> — Las estadísticas no están disponibles.
        </div>
        <?php endif; ?>

        <!-- Tarjetas de estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-store"></i></div>
                <div>
                    <div class="stat-value"><?= $stats['total_restaurantes'] ?></div>
                    <div class="stat-label">Restaurantes registrados</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="stat-value"><?= $stats['activos'] ?></div>
                    <div class="stat-label">Restaurantes activos</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-ban"></i></div>
                <div>
                    <div class="stat-value"><?= $stats['inactivos'] ?></div>
                    <div class="stat-label">Restaurantes inactivos</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon cyan"><i class="fas fa-users"></i></div>
                <div>
                    <div class="stat-value"><?= $stats['total_usuarios'] ?></div>
                    <div class="stat-label">Usuarios globales</div>
                </div>
            </div>
        </div>

        <!-- Tabla restaurantes -->
        <?php $model2 = new AdminModel(); $todos = $model2->obtenerComercios(); ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-store"></i>
                <h3>Restaurantes</h3>
                <span><?= count($todos) ?> registros</span>
            </div>
            <?php if (empty($todos)): ?>
                <div class="empty-state">
                    <i class="fas fa-store-slash"></i>
                    No hay restaurantes registrados aún.
                </div>
            <?php else: ?>
            <table id="tablaRestaurantes">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Email</th>
                        <th>Registro</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($todos as $r): ?>
                    <tr id="row-<?= $r['id'] ?>">
                        <td style="display:flex;align-items:center;gap:8px;">
                            <a href="<?= $basePath ?>/config/<?= $r['id'] ?>"
                               title="Configuración del restaurante"
                               style="width:28px;height:28px;border-radius:6px;background:#1e1e30;
                                      border:1px solid #2d2d44;display:flex;align-items:center;
                                      justify-content:center;color:#7c3aed;text-decoration:none;
                                      flex-shrink:0;transition:background .15s;"
                               onmouseover="this.style.background='#2d2d44'"
                               onmouseout="this.style.background='#1e1e30'">
                                <i class="fas fa-gear" style="font-size:13px;"></i>
                            </a>
                            <strong style="color:#e0e0e0;"><?= htmlspecialchars($r['nombre']) ?></strong>
                            <?php if ((int)$r['verificado']): ?>
                                <i class="fas fa-circle-check verif-chk" style="color:#22c55e;font-size:12px;" title="Verificado"></i>
                            <?php endif; ?>
                        </td>
                        <td style="color:#888;font-size:12px;"><?= htmlspecialchars($r['slug'] ?? '—') ?></td>
                        <td style="color:#aaa;font-size:13px;"><?= htmlspecialchars($r['email'] ?? '—') ?></td>
                        <td style="color:#aaa;font-size:12px;"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                        <td>
                            <span class="badge <?= (int)$r['activo'] ? 'badge-active' : 'badge-inactive' ?>"
                                  id="badge-activo-<?= $r['id'] ?>">
                                <?= (int)$r['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <!-- Bloquear / Desbloquear -->
                                <button onclick="toggleActivo(<?= $r['id'] ?>)"
                                        id="btn-toggle-<?= $r['id'] ?>"
                                        title="<?= (int)$r['activo'] ? 'Bloquear' : 'Desbloquear' ?>"
                                        style="<?= estiloBtn((int)$r['activo'] ? '#7f1d1d' : '#14532d') ?>">
                                    <i class="fas <?= (int)$r['activo'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                </button>
                                <!-- Editar -->
                                <button onclick="abrirEditar(<?= $r['id'] ?>, <?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)"
                                        title="Editar"
                                        style="<?= estiloBtn('#2d2d00') ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <!-- Documentos -->
                                <button onclick="verDocumentos(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['nombre']), ENT_QUOTES) ?>')"
                                        title="Ver documentos de verificación"
                                        style="<?= estiloBtn('#1a3a2a') ?>">
                                    <i class="fas fa-clipboard-check"></i>
                                </button>
                                <!-- Acceder -->
                                <button onclick="acceder(<?= $r['id'] ?>)"
                                        title="Acceder al panel"
                                        style="<?= estiloBtn('#1a1a4a') ?>">
                                    <i class="fas fa-arrow-right-to-bracket"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div><!-- /content -->
</div><!-- /main -->

<!-- ── Modal: Generar invitación ──────────────────────────────────────────── -->
<div id="modalInvitar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);
     z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#1e1e2e;border:1px solid #2d2d44;border-radius:16px;
                padding:36px 32px;width:100%;max-width:500px;position:relative;">

        <button onclick="cerrarModalInvitar()"
                style="position:absolute;top:16px;right:16px;background:none;border:none;
                       color:#888;font-size:20px;cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>

        <!-- Estado inicial: botón generar -->
        <div id="invitarIdle">
            <div style="text-align:center;padding:16px 0 24px;">
                <div style="width:64px;height:64px;border-radius:50%;background:#1a0a3a;
                            display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i class="fas fa-link" style="font-size:28px;color:#7c3aed;"></i>
                </div>
                <h3 style="color:#fff;margin:0 0 8px;font-size:18px;">Generar link de registro</h3>
                <p style="color:#888;font-size:13px;margin:0 0 28px;line-height:1.5;">
                    Se generará un link de uso único válido por <strong style="color:#a78bfa;">24 horas</strong>.<br>
                    Compártelo con el restaurante para que complete su registro.
                </p>
                <button id="btnGenerar" onclick="generarInvitacion()"
                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;
                               padding:13px 32px;border-radius:8px;font-size:15px;font-weight:600;
                               cursor:pointer;width:100%;">
                    <i class="fas fa-wand-magic-sparkles"></i> Generar link
                </button>
            </div>
        </div>

        <!-- Estado generado: mostrar link -->
        <div id="invitarGenerado" style="display:none;">
            <div style="text-align:center;margin-bottom:20px;">
                <div style="width:56px;height:56px;border-radius:50%;background:#0d2a0d;
                            display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                    <i class="fas fa-check" style="font-size:22px;color:#22c55e;"></i>
                </div>
                <h3 style="color:#fff;margin:0 0 4px;font-size:17px;">Link generado</h3>
                <p style="color:#888;font-size:12px;margin:0;" id="invitarExpira"></p>
            </div>

            <div style="background:#12121e;border:1px solid #2d2d44;border-radius:10px;
                        padding:14px 16px;margin-bottom:16px;">
                <div style="font-size:10px;color:#666;margin-bottom:6px;letter-spacing:.5px;">URL DE REGISTRO</div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input id="invitarUrl" type="text" readonly
                           style="flex:1;background:#0d0d1a;border:1px solid #2d2d44;border-radius:6px;
                                  padding:9px 12px;color:#a78bfa;font-size:12px;outline:none;
                                  font-family:monospace;overflow:hidden;text-overflow:ellipsis;">
                    <button onclick="copiarLink()"
                            id="btnCopiar"
                            style="background:#2d2d44;border:none;color:#e0e0e0;border-radius:6px;
                                   padding:9px 14px;cursor:pointer;font-size:13px;white-space:nowrap;
                                   transition:background .15s;"
                            title="Copiar link">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                </div>
            </div>

            <p style="color:#666;font-size:11px;text-align:center;margin:0 0 16px;">
                <i class="fas fa-triangle-exclamation" style="color:#f59e0b;"></i>
                El link expira en 24 h o al completarse el registro. Solo puede usarse una vez.
            </p>

            <div style="background:#12121e;border:1px solid #2d2d44;border-radius:10px;
                        padding:14px 16px;margin-bottom:16px;">
                <div style="font-size:10px;color:#666;margin-bottom:6px;letter-spacing:.5px;">ENVIAR POR SMS</div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input id="invitarTelefono" type="tel" inputmode="numeric" maxlength="10"
                           placeholder="Número de teléfono"
                           style="flex:1;background:#0d0d1a;border:1px solid #2d2d44;border-radius:6px;
                                  padding:9px 12px;color:#e0e0e0;font-size:13px;outline:none;">
                    <button onclick="enviarInvitacionSMS()"
                            id="btnEnviarSms"
                            style="background:#6366f1;border:none;color:#fff;border-radius:6px;
                                   padding:9px 14px;cursor:pointer;font-size:13px;white-space:nowrap;">
                        <i class="fas fa-paper-plane"></i> Enviar SMS
                    </button>
                </div>
                <div id="invitarSmsMsg" style="font-size:12px;margin-top:8px;display:none;"></div>
            </div>

            <button onclick="generarInvitacion()"
                    style="width:100%;background:#1e1e30;border:1px solid #2d2d44;color:#aaa;
                           border-radius:8px;padding:10px;font-size:13px;cursor:pointer;">
                <i class="fas fa-rotate-right"></i> Generar nuevo link
            </button>
        </div>

    </div>
</div>

<!-- ── Modal: Editar Restaurante ─────────────────────────────────────────── -->
<div id="modalEditar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);
     z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#1e1e2e;border:1px solid #2d2d44;border-radius:16px;
                padding:36px 32px;width:100%;max-width:480px;position:relative;">
        <button onclick="document.getElementById('modalEditar').style.display='none'"
                style="position:absolute;top:16px;right:16px;background:none;border:none;
                       color:#888;font-size:20px;cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
        <h3 style="color:#fff;margin:0 0 20px;font-size:18px;">
            <i class="fas fa-pen" style="color:#f59e0b;margin-right:8px;"></i>Editar restaurante
        </h3>
        <div id="editError" style="display:none;background:#3b1c1c;border:1px solid #e74c3c;
             color:#e74c3c;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;"></div>
        <form id="formEditar" style="display:flex;flex-direction:column;gap:14px;">
            <input type="hidden" id="editId" name="id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Nombre *</label>
                    <input id="editNombre" name="nombre" type="text" required style="<?= estiloInput() ?>">
                </div>
                <div>
                    <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Slug *</label>
                    <input id="editSlug" name="slug" type="text" required style="<?= estiloInput() ?>">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Email</label>
                    <input id="editEmail" name="email" type="email" style="<?= estiloInput() ?>">
                </div>
                <div>
                    <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Tipo</label>
                    <select id="editTipo" name="tipo" style="<?= estiloInput() ?>">
                        <option value="restaurante">Restaurante</option>
                        <option value="cafeteria">Cafetería</option>
                        <option value="bar">Bar</option>
                        <option value="panaderia">Panadería</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
            </div>
            <button type="submit" id="btnEditar"
                    style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;
                           padding:12px;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;">
                <i class="fas fa-save"></i> Guardar cambios
            </button>
        </form>
    </div>
</div>

<!-- ── Modal: Documentos de verificación ─────────────────────────────────── -->
<div id="modalDocs" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);
     z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#1e1e2e;border:1px solid #2d2d44;border-radius:16px;
                padding:32px 28px;width:100%;max-width:600px;position:relative;
                max-height:90vh;overflow-y:auto;">
        <button onclick="document.getElementById('modalDocs').style.display='none'"
                style="position:absolute;top:14px;right:16px;background:none;border:none;
                       color:#888;font-size:20px;cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
        <h3 style="color:#fff;margin:0 0 4px;font-size:17px;">
            <i class="fas fa-clipboard-check" style="color:#22c55e;margin-right:8px;"></i>
            Documentos — <span id="docsNombre"></span>
        </h3>
        <div id="docsEstadoBadge" style="margin-bottom:20px;"></div>

        <!-- Loading -->
        <div id="docsLoading" style="text-align:center;padding:32px;color:#888;">
            <i class="fas fa-spinner fa-spin" style="font-size:24px;"></i>
        </div>
        <!-- Content -->
        <div id="docsContent" style="display:none;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:24px;"
                 id="docsGrid"></div>

            <!-- Acciones -->
            <div id="docsAcciones">
                <!-- Botones principales -->
                <div style="display:flex;gap:10px;margin-bottom:14px;">
                    <button id="btnAprobar" onclick="aprobarDocs()"
                            style="flex:1;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;
                                   border:none;padding:10px;border-radius:8px;font-size:13px;
                                   font-weight:600;cursor:pointer;">
                        <i class="fas fa-check-circle"></i> Aprobar todo
                    </button>
                    <button onclick="guardarRechazos()"
                            style="flex:1;background:#7f1d1d;color:#fff;border:none;padding:10px;
                                   border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                        <i class="fas fa-times-circle"></i> Guardar rechazos
                    </button>
                </div>
                <!-- Rechazos individuales por documento -->
                <div id="rechazosGrid" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;"></div>
            </div>
            <div id="docsMsg" style="display:none;margin-top:12px;border-radius:8px;
                 padding:12px 16px;font-size:13px;"></div>
        </div>
    </div>
</div>

<!-- ── Modal: Acceder como tenant ────────────────────────────────────────── -->
<div id="modalAcceder" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);
     z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#1e1e2e;border:1px solid #2d2d44;border-radius:16px;
                padding:36px 32px;width:100%;max-width:420px;text-align:center;position:relative;">
        <button onclick="document.getElementById('modalAcceder').style.display='none'"
                style="position:absolute;top:16px;right:16px;background:none;border:none;
                       color:#888;font-size:20px;cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
        <div style="width:60px;height:60px;border-radius:50%;background:#1a1a4a;
                    display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="fas fa-arrow-right-to-bracket" style="font-size:24px;color:#818cf8;"></i>
        </div>
        <h3 style="color:#fff;margin:0 0 8px;" id="accederNombre">Acceder al panel</h3>
        <p style="color:#888;font-size:13px;margin:0 0 16px;" id="accederInfo"></p>
        <div id="accederWarning" style="display:none;background:#3b1c1c;border:1px solid #dc2626;
             color:#fca5a5;border-radius:8px;padding:12px 16px;font-size:13px;margin-bottom:16px;">
            <i class="fas fa-triangle-exclamation"></i>
            Este restaurante no tiene los documentos verificados. Aprueba sus documentos antes de acceder.
        </div>
        <a id="accederLink" href="#" target="_blank"
           style="display:inline-block;background:linear-gradient(135deg,#4f46e5,#7c3aed);
                  color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;
                  font-weight:600;font-size:15px;">
            <i class="fas fa-external-link-alt"></i> Abrir panel
        </a>
    </div>
</div>

<?php
function estiloBtn(string $bg): string {
    return "background:{$bg};border:1px solid rgba(255,255,255,.08);color:#e0e0e0;
            border-radius:6px;padding:6px 10px;cursor:pointer;font-size:13px;transition:opacity .2s;";
}

function estiloInput(): string {
    return "width:100%;box-sizing:border-box;background:#12121e;border:1px solid #2d2d44;
            border-radius:6px;padding:9px 12px;color:#e0e0e0;font-size:13px;outline:none;";
}
?>

<script>
// ── Modal invitación ──────────────────────────────────────────────────────────
function abrirModalInvitar() {
    document.getElementById('invitarIdle').style.display     = 'block';
    document.getElementById('invitarGenerado').style.display = 'none';
    document.getElementById('invitarTelefono').value          = '';
    document.getElementById('invitarSmsMsg').style.display    = 'none';
    document.getElementById('modalInvitar').style.display    = 'flex';
}
function cerrarModalInvitar() {
    document.getElementById('modalInvitar').style.display = 'none';
}
document.getElementById('modalInvitar').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalInvitar();
});

async function generarInvitacion() {
    const btn = document.getElementById('btnGenerar');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...'; }

    try {
        const res  = await fetch('<?= $basePath ?>/invitacion', { method: 'POST' });
        const data = await res.json();
        if (!data.ok) { alert(data.msg || 'Error al generar el link.'); return; }

        const chefBase = '<?= rtrim(SupConfig::CLIENT_URL, '/') ?>';
        const url      = `${chefBase}/registro?token=${data.token}`;

        document.getElementById('invitarUrl').value   = url;
        const expira = new Date(data.expira_en.replace(' ', 'T'));
        document.getElementById('invitarExpira').textContent =
            'Expira el ' + expira.toLocaleString('es-CO', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});

        document.getElementById('invitarIdle').style.display     = 'none';
        document.getElementById('invitarGenerado').style.display = 'block';
    } catch(e) {
        alert('Error de conexión.');
    }
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Generar link'; }
}

async function enviarInvitacionSMS() {
    const telefono = document.getElementById('invitarTelefono').value.trim();
    const url       = document.getElementById('invitarUrl').value;
    const msgBox    = document.getElementById('invitarSmsMsg');
    const btn       = document.getElementById('btnEnviarSms');

    msgBox.style.display = 'none';
    if (telefono.replace(/\D/g, '').length < 10) {
        msgBox.style.display = 'block';
        msgBox.style.color   = '#f87171';
        msgBox.textContent   = 'Ingresa un número de teléfono válido.';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    try {
        const fd = new FormData();
        fd.append('telefono', telefono);
        fd.append('url', url);
        const res  = await fetch('<?= $basePath ?>/invitacion/sms', { method: 'POST', body: fd });
        const data = await res.json();

        msgBox.style.display = 'block';
        if (data.ok) {
            msgBox.style.color = '#22c55e';
            msgBox.textContent = 'SMS enviado correctamente.';
        } else {
            msgBox.style.color = '#f87171';
            msgBox.textContent = data.msg || 'No se pudo enviar el SMS.';
        }
    } catch (e) {
        msgBox.style.display = 'block';
        msgBox.style.color   = '#f87171';
        msgBox.textContent   = 'Error de conexión.';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar SMS';
}

async function copiarLink() {
    const url = document.getElementById('invitarUrl').value;
    try {
        await navigator.clipboard.writeText(url);
    } catch(e) {
        document.getElementById('invitarUrl').select();
        document.execCommand('copy');
    }
    const btn = document.getElementById('btnCopiar');
    btn.innerHTML = '<i class="fas fa-check"></i> Copiado';
    btn.style.background = '#14532d';
    setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i> Copiar'; btn.style.background = '#2d2d44'; }, 2000);
}

// Badge de chat no leídos
async function actualizarChatBadge() {
    try {
        const r = await fetch('<?= $basePath ?>/chat/no-leidos');
        const d = await r.json();
        const el = document.getElementById('chatBadge');
        if (d.total > 0) { el.textContent = d.total; el.style.display = ''; }
        else el.style.display = 'none';
    } catch(e) {}
}
actualizarChatBadge();
setInterval(actualizarChatBadge, 10000);

// Cerrar al hacer clic fuera del modal crear
document.getElementById('modalEditar').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
document.getElementById('modalAcceder').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
document.getElementById('modalDocs').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

// ── Bloquear / Desbloquear ────────────────────────────────────────────────
async function toggleActivo(id) {
    const res  = await fetch(`<?= $basePath ?>/restaurante/toggle/${id}`);
    const data = await res.json();
    if (!data.ok) return alert(data.msg);
    const badge = document.getElementById(`badge-activo-${id}`);
    const btn   = document.getElementById(`btn-toggle-${id}`);
    if (data.activo) {
        badge.textContent = 'Activo';
        badge.className = 'badge badge-active';
        btn.style.background = '#7f1d1d';
        btn.title = 'Bloquear';
        btn.innerHTML = '<i class="fas fa-ban"></i>';
    } else {
        badge.textContent = 'Inactivo';
        badge.className = 'badge badge-inactive';
        btn.style.background = '#14532d';
        btn.title = 'Desbloquear';
        btn.innerHTML = '<i class="fas fa-check"></i>';
    }
}

// ── Verificar ─────────────────────────────────────────────────────────────
async function toggleVerificado(id) {
    const res  = await fetch(`<?= $basePath ?>/restaurante/verificar/${id}`);
    const data = await res.json();
    if (!data.ok) return alert(data.msg);
    const btn = document.getElementById(`btn-verif-${id}`);
    btn.style.background = data.verificado ? '#1e3a5f' : '#1a3a1a';
    btn.title = data.verificado ? 'Quitar verificación' : 'Verificar';
    // Actualizar el ícono de check en el nombre
    const row = document.getElementById(`row-${id}`);
    let chk = row.querySelector('.verif-chk');
    if (data.verificado) {
        if (!chk) {
            chk = document.createElement('i');
            chk.className = 'fas fa-circle-check verif-chk';
            chk.style.cssText = 'color:#22c55e;font-size:12px;margin-left:4px;';
            chk.title = 'Verificado';
            row.querySelector('strong').after(chk);
        }
    } else {
        if (chk) chk.remove();
    }
}

// ── Editar ────────────────────────────────────────────────────────────────
function abrirEditar(id, datos) {
    document.getElementById('editId').value    = id;
    document.getElementById('editNombre').value = datos.nombre || '';
    document.getElementById('editSlug').value   = datos.slug   || '';
    document.getElementById('editEmail').value  = datos.email  || '';
    document.getElementById('editTipo').value   = datos.tipo   || 'restaurante';
    document.getElementById('editError').style.display = 'none';
    document.getElementById('modalEditar').style.display = 'flex';
}

document.getElementById('formEditar').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnEditar');
    const err = document.getElementById('editError');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    err.style.display = 'none';
    const id = document.getElementById('editId').value;
    const fd = new FormData(this);
    try {
        const res  = await fetch(`<?= $basePath ?>/restaurante/editar/${id}`, { method:'POST', body:fd });
        const data = await res.json();
        if (data.ok) {
            document.getElementById('modalEditar').style.display = 'none';
            location.reload();
        } else {
            err.style.display = 'block';
            err.textContent = data.msg;
        }
    } catch(ex) {
        err.style.display = 'block';
        err.textContent = 'Error de conexión.';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Guardar cambios';
});

// ── Documentos ────────────────────────────────────────────────────────────
let _docsId = 0;
const chefBase = '<?= rtrim(SupConfig::CLIENT_URL, '/') ?>';
const DOCS_CAMPOS = [
    {key:'cedula_frente',  label:'Cédula (frente)'},
    {key:'cedula_trasera', label:'Cédula (trasera)'},
    {key:'logo',           label:'Logo del negocio'},
    {key:'foto_negocio',   label:'Foto del negocio'},
];

async function verDocumentos(id, nombre) {
    _docsId = id;
    document.getElementById('docsNombre').textContent    = nombre;
    document.getElementById('docsEstadoBadge').innerHTML = '';
    document.getElementById('docsLoading').style.display = 'block';
    document.getElementById('docsContent').style.display = 'none';
    document.getElementById('docsMsg').style.display     = 'none';
    document.getElementById('modalDocs').style.display   = 'flex';

    let data;
    try {
        const res = await fetch(`<?= $basePath ?>/restaurante/documentos/${id}`);
        data = await res.json();
    } catch(e) {
        document.getElementById('docsLoading').style.display = 'none';
        alert('Error al cargar los documentos: ' + e.message);
        return;
    }
    document.getElementById('docsLoading').style.display = 'none';
    if (!data.ok) { alert(data.msg); return; }

    const d = data.docs;

    // Badge de estado
    const estadoColores = {
        pendiente:   ['#78350f','#fde68a','Pendiente'],
        en_revision: ['#1e3a5f','#93c5fd','En revisión'],
        verificado:  ['#14532d','#86efac','Verificado'],
        rechazado:   ['#7f1d1d','#fca5a5','Rechazado'],
    };
    const [bg, color, lbl] = estadoColores[d.doc_estado] || ['#2d2d44','#aaa', d.doc_estado];
    document.getElementById('docsEstadoBadge').innerHTML =
        `<span style="background:${bg};color:${color};padding:3px 12px;border-radius:999px;
                font-size:12px;font-weight:700;">${lbl}</span>`;

    // Grid de previsualización
    const icons = {cedula_frente:'fa-id-card', cedula_trasera:'fa-id-card-clip', logo:'fa-image', foto_negocio:'fa-store'};
    let grid = '';
    for (const {key, label} of DOCS_CAMPOS) {
        const ruta    = d[`doc_${key}`];
        const rechazo = d[`doc_${key}_rechazo`];
        const borde   = rechazo ? '#dc2626' : (ruta ? '#374151' : '#1e1e30');
        const bg2     = rechazo ? '#2a0a0a' : '#12121e';
        grid += `<div style="border:1px solid ${borde};border-radius:10px;overflow:hidden;background:${bg2};">
            <div style="padding:5px 8px;font-size:10px;color:#888;border-bottom:1px solid #1e1e30;
                        display:flex;align-items:center;justify-content:space-between;">
                <span>${label}</span>
                ${rechazo ? '<span style="color:#f87171;font-size:10px;">⚠ Rechazado</span>' : (ruta ? '<span style="color:#4ade80;font-size:10px;">✓</span>' : '')}
            </div>`;
        if (ruta) {
            const esPdf = ruta.endsWith('.pdf');
            grid += esPdf
                ? `<div style="padding:16px;text-align:center;color:#6366f1;">
                      <i class="fas fa-file-pdf" style="font-size:28px;"></i>
                      <div style="margin-top:6px;"><a href="${chefBase}/${ruta}" target="_blank"
                           style="color:#818cf8;font-size:11px;text-decoration:none;">Ver PDF</a></div></div>`
                : `<a href="${chefBase}/${ruta}" target="_blank">
                      <img src="${chefBase}/${ruta}" alt="${label}"
                           style="width:100%;height:110px;object-fit:cover;display:block;"></a>`;
        } else {
            grid += `<div style="padding:20px;text-align:center;color:#444;">
                <i class="fas ${icons[key]}" style="font-size:24px;"></i>
                <div style="font-size:10px;margin-top:6px;color:#555;">No subido</div></div>`;
        }
        grid += '</div>';
    }
    document.getElementById('docsGrid').innerHTML = grid;

    // Grid de rechazos individuales
    let rechazosHtml = '';
    for (const {key, label} of DOCS_CAMPOS) {
        const rechazoActual = d[`doc_${key}_rechazo`] || '';
        rechazosHtml += `
        <div style="background:#12121e;border:1px solid ${rechazoActual ? '#dc2626' : '#2d2d44'};
                    border-radius:8px;padding:10px;">
            <label style="color:${rechazoActual ? '#f87171' : '#aaa'};font-size:11px;font-weight:600;
                          display:block;margin-bottom:4px;">${label}</label>
            <textarea name="rechazos[${key}]" rows="2"
                      placeholder="Deja vacío si está OK"
                      style="width:100%;box-sizing:border-box;background:#0d0d1a;border:1px solid #2d2d44;
                             border-radius:5px;padding:7px 10px;color:#e0e0e0;font-size:12px;
                             resize:vertical;outline:none;"
                      oninput="resaltarRechazoCampo(this)">${rechazoActual}</textarea>
        </div>`;
    }
    document.getElementById('rechazosGrid').innerHTML = rechazosHtml;

    // Ocultar acciones si ya está verificado
    document.getElementById('docsAcciones').style.display = d.doc_estado === 'verificado' ? 'none' : 'block';
    document.getElementById('docsContent').style.display  = 'block';
}

function resaltarRechazoCampo(ta) {
    const wrapper = ta.closest('div');
    wrapper.style.borderColor = ta.value.trim() ? '#dc2626' : '#2d2d44';
    wrapper.querySelector('label').style.color = ta.value.trim() ? '#f87171' : '#aaa';
}

async function aprobarDocs() {
    if (!confirm('¿Aprobar y verificar este restaurante?')) return;
    const btn = document.getElementById('btnAprobar');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aprobando...';
    let data;
    try {
        const res = await fetch(`<?= $basePath ?>/restaurante/aprobar/${_docsId}`, {method:'POST'});
        data = await res.json();
    } catch(e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Aprobar todo';
        alert('Error al aprobar: ' + e.message);
        return;
    }
    const msg  = document.getElementById('docsMsg');
    msg.style.cssText = 'display:block;border-radius:8px;padding:12px 16px;font-size:13px;margin-top:12px;';
    if (data.ok) {
        msg.style.cssText += 'background:#14532d;color:#86efac;border:1px solid #166534;';
        msg.innerHTML = '<i class="fas fa-check-circle"></i> Restaurante verificado correctamente.';
        document.getElementById('docsAcciones').style.display = 'none';
        document.getElementById('docsEstadoBadge').innerHTML =
            '<span style="background:#14532d;color:#86efac;padding:3px 12px;border-radius:999px;font-size:12px;font-weight:700;">Verificado</span>';
        const row = document.getElementById(`row-${_docsId}`);
        if (row) {
            let chk = row.querySelector('.verif-chk');
            if (!chk) {
                chk = document.createElement('i');
                chk.className = 'fas fa-circle-check verif-chk';
                chk.style.cssText = 'color:#22c55e;font-size:12px;margin-left:4px;';
                chk.title = 'Verificado';
                row.querySelector('strong').after(chk);
            }
        }
    } else {
        msg.style.cssText += 'background:#7f1d1d;color:#fca5a5;border:1px solid #991b1b;';
        msg.textContent = data.msg || 'Error al aprobar.';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Aprobar todo';
    }
}

async function guardarRechazos() {
    const textareas = document.querySelectorAll('#rechazosGrid textarea');
    const fd = new FormData();
    let hayAlgo = false;
    textareas.forEach(ta => {
        const key = ta.name.match(/rechazos\[(\w+)\]/)[1];
        fd.append(`rechazos[${key}]`, ta.value.trim());
        if (ta.value.trim()) hayAlgo = true;
    });
    if (!hayAlgo) { alert('Escribe el motivo de al menos un rechazo.'); return; }
    if (!confirm('¿Guardar los rechazos? El usuario deberá volver a subir los documentos indicados.')) return;

    const res  = await fetch(`<?= $basePath ?>/restaurante/rechazar/${_docsId}`, {method:'POST', body:fd});
    const data = await res.json();
    const msg  = document.getElementById('docsMsg');
    msg.style.cssText = 'display:block;border-radius:8px;padding:12px 16px;font-size:13px;margin-top:12px;';
    if (data.ok) {
        msg.style.cssText += 'background:#78350f;color:#fde68a;border:1px solid #92400e;';
        msg.innerHTML = '<i class="fas fa-times-circle"></i> Rechazos guardados. El usuario verá el motivo por documento.';
        document.getElementById('docsEstadoBadge').innerHTML =
            '<span style="background:#7f1d1d;color:#fca5a5;padding:3px 12px;border-radius:999px;font-size:12px;font-weight:700;">Rechazado</span>';
    } else {
        msg.style.cssText += 'background:#7f1d1d;color:#fca5a5;border:1px solid #991b1b;';
        msg.textContent = data.msg || 'Error al guardar.';
    }
}

// ── Acceder ───────────────────────────────────────────────────────────────
async function acceder(id) {
    const [resAcceso, resDocs] = await Promise.all([
        fetch(`<?= $basePath ?>/restaurante/acceder/${id}`),
        fetch(`<?= $basePath ?>/restaurante/documentos/${id}`),
    ]);
    const acceso = await resAcceso.json();
    const docs   = await resDocs.json();
    if (!acceso.ok) return alert(acceso.msg);

    const u          = acceso.usuario;
    const verificado = docs.ok && docs.docs.doc_estado === 'verificado';

    document.getElementById('accederNombre').textContent = u.comercio_nombre;
    document.getElementById('accederInfo').textContent   = `Usuario: ${u.username} · Rol: ${u.rol}`;
    document.getElementById('accederWarning').style.display = verificado ? 'none' : 'block';
    document.getElementById('accederLink').style.display    = verificado ? 'inline-block' : 'none';
    if (verificado) {
        document.getElementById('accederLink').href = `<?= $basePath ?>/restaurante/impersonar/${id}`;
    }
    document.getElementById('modalAcceder').style.display = 'flex';
}
</script>

</body>
</html>
