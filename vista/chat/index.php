<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../modelo/adminModel.php';

$basePath = SupConfig::getBasePath();
$baseUrl  = SupConfig::getBaseUrl();
$nombre   = htmlspecialchars($_SESSION['sup_nombre'] ?? 'Admin');

$model         = new AdminModel();
$conversaciones = $model->obtenerConversaciones();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat — ChefControl SUP</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .chat-layout { display:flex; height:calc(100vh - 80px); gap:0; overflow:hidden; }

        /* Lista conversaciones */
        .conv-list {
            width:300px; min-width:260px; background:#161625;
            border-right:1px solid #2d2d44; display:flex; flex-direction:column;
        }
        .conv-search {
            padding:14px 16px; border-bottom:1px solid #2d2d44;
        }
        .conv-search input {
            width:100%; box-sizing:border-box; background:#12121e;
            border:1px solid #2d2d44; border-radius:8px; padding:9px 14px;
            color:#e0e0e0; font-size:13px; outline:none;
        }
        .conv-scroll { flex:1; overflow-y:auto; }
        .conv-item {
            display:flex; align-items:center; gap:12px; padding:14px 16px;
            border-bottom:1px solid #1e1e30; cursor:pointer; transition:background .15s;
        }
        .conv-item:hover  { background:#1e1e30; }
        .conv-item.active { background:#252545; }
        .conv-avatar {
            width:42px; height:42px; border-radius:50%;
            background:linear-gradient(135deg,#7c3aed,#4f46e5);
            display:flex; align-items:center; justify-content:center;
            font-weight:700; font-size:16px; color:#fff; flex-shrink:0;
        }
        .conv-info { flex:1; min-width:0; }
        .conv-nombre { color:#e0e0e0; font-weight:600; font-size:14px;
                       white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .conv-preview { color:#888; font-size:12px; margin-top:2px;
                        white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .conv-badge {
            background:#7c3aed; color:#fff; border-radius:999px;
            font-size:11px; font-weight:700; padding:2px 7px; flex-shrink:0;
        }

        /* Área de conversación */
        .chat-area {
            flex:1; display:flex; flex-direction:column; background:#12121e;
        }
        .chat-header {
            padding:16px 24px; border-bottom:1px solid #2d2d44;
            display:flex; align-items:center; gap:14px;
            background:#161625;
        }
        .chat-header-avatar {
            width:40px; height:40px; border-radius:50%;
            background:linear-gradient(135deg,#7c3aed,#4f46e5);
            display:flex; align-items:center; justify-content:center;
            font-weight:700; font-size:15px; color:#fff;
        }
        .chat-header-info strong { color:#e0e0e0; font-size:15px; display:block; }
        .chat-header-info span   { color:#888; font-size:12px; }

        /* Mensajes */
        .chat-messages {
            flex:1; overflow-y:auto; padding:20px 24px;
            display:flex; flex-direction:column; gap:12px;
        }
        .msg { display:flex; gap:10px; max-width:70%; }
        .msg.sup  { align-self:flex-end; flex-direction:row-reverse; }
        .msg.rest { align-self:flex-start; }

        .msg-bubble {
            padding:10px 14px; border-radius:14px;
            font-size:14px; line-height:1.5; word-break:break-word;
        }
        .msg.sup  .msg-bubble { background:#4f46e5; color:#fff; border-bottom-right-radius:4px; }
        .msg.rest .msg-bubble { background:#1e1e30; color:#e0e0e0; border-bottom-left-radius:4px; }

        .msg-meta { font-size:11px; color:#666; margin-top:3px; text-align:right; }
        .msg.rest .msg-meta { text-align:left; }

        /* Input */
        .chat-input-bar {
            padding:16px 24px; border-top:1px solid #2d2d44;
            display:flex; gap:10px; background:#161625;
        }
        .chat-input-bar textarea {
            flex:1; background:#12121e; border:1px solid #2d2d44;
            border-radius:10px; padding:10px 14px; color:#e0e0e0;
            font-size:14px; resize:none; outline:none; font-family:inherit;
            line-height:1.4; max-height:120px;
        }
        .chat-input-bar textarea:focus { border-color:#4f46e5; }
        .btn-send {
            background:linear-gradient(135deg,#7c3aed,#4f46e5);
            border:none; border-radius:10px; padding:0 20px;
            color:#fff; font-size:18px; cursor:pointer; transition:opacity .2s;
        }
        .btn-send:hover { opacity:.85; }

        .chat-empty {
            flex:1; display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            color:#444; text-align:center; gap:12px;
        }
        .chat-empty i { font-size:48px; }
        .chat-empty p { font-size:15px; }
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
        <a class="nav-item active" href="<?= $basePath ?>/chat">
            <i class="fas fa-comments"></i> Chat
            <span id="sidebarBadge" style="display:none;background:#7c3aed;color:#fff;
                  border-radius:999px;font-size:11px;padding:1px 7px;margin-left:auto;">0</span>
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
            <h2><i class="fas fa-comments" style="color:#7c3aed;margin-right:10px;"></i>Chat con restaurantes</h2>
            <p><?= count($conversaciones) ?> conversaciones</p>
        </div>
        <a class="btn-logout" href="<?= $basePath ?>/logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </header>

    <div class="content" style="padding:0;">
        <div class="chat-layout">

            <!-- Lista de conversaciones -->
            <div class="conv-list">
                <div class="conv-search">
                    <input type="text" id="buscarConv" placeholder="Buscar restaurante..."
                           oninput="filtrarConv(this.value)">
                </div>
                <div class="conv-scroll" id="convScroll">
                    <?php if (empty($conversaciones)): ?>
                        <div style="padding:24px;text-align:center;color:#555;font-size:13px;">
                            No hay restaurantes activos.
                        </div>
                    <?php else: ?>
                    <?php foreach ($conversaciones as $c): ?>
                    <div class="conv-item" data-id="<?= $c['id'] ?>" data-nombre="<?= htmlspecialchars(strtolower($c['nombre'])) ?>"
                         onclick="abrirConv(<?= $c['id'] ?>, '<?= htmlspecialchars($c['nombre'], ENT_QUOTES) ?>')">
                        <div class="conv-avatar"><?= mb_strtoupper(mb_substr($c['nombre'], 0, 1)) ?></div>
                        <div class="conv-info">
                            <div class="conv-nombre"><?= htmlspecialchars($c['nombre']) ?></div>
                            <div class="conv-preview">
                                <?= $c['ultimo_texto']
                                    ? htmlspecialchars(mb_substr($c['ultimo_texto'], 0, 40))
                                    : '<em style="color:#555">Sin mensajes</em>' ?>
                            </div>
                        </div>
                        <?php if ((int)$c['no_leidos'] > 0): ?>
                        <span class="conv-badge" id="badge-<?= $c['id'] ?>"><?= $c['no_leidos'] ?></span>
                        <?php else: ?>
                        <span class="conv-badge" id="badge-<?= $c['id'] ?>" style="display:none">0</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Área de chat -->
            <div class="chat-area" id="chatArea">
                <div class="chat-empty" id="chatEmpty">
                    <i class="fas fa-comments"></i>
                    <p>Selecciona un restaurante para comenzar</p>
                </div>

                <div id="chatConversacion" style="display:none;flex:1;flex-direction:column;overflow:hidden;">
                    <div class="chat-header">
                        <div class="chat-header-avatar" id="chatAvatar">R</div>
                        <div class="chat-header-info">
                            <strong id="chatNombre">Restaurante</strong>
                            <span id="chatSlug"></span>
                        </div>
                    </div>
                    <div class="chat-messages" id="chatMessages"></div>
                    <div class="chat-input-bar">
                        <textarea id="inputMensaje" rows="1" placeholder="Escribe un mensaje..."
                                  onkeydown="enviarConEnter(event)"></textarea>
                        <button class="btn-send" onclick="enviarMensaje()" title="Enviar">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
const BASE = '<?= $basePath ?>';
let cidActivo    = null;
let ultimoId     = 0;
let pollingTimer = null;

function filtrarConv(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.conv-item').forEach(el => {
        el.style.display = el.dataset.nombre.includes(q) ? '' : 'none';
    });
}

function abrirConv(cid, nombre) {
    cidActivo = cid; ultimoId = 0;
    document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
    document.querySelector(`.conv-item[data-id="${cid}"]`)?.classList.add('active');

    document.getElementById('chatEmpty').style.display        = 'none';
    document.getElementById('chatConversacion').style.display = 'flex';
    document.getElementById('chatNombre').textContent  = nombre;
    document.getElementById('chatAvatar').textContent  = nombre.charAt(0).toUpperCase();
    document.getElementById('chatMessages').innerHTML  = '';

    // Limpiar badge
    const badge = document.getElementById(`badge-${cid}`);
    if (badge) { badge.style.display = 'none'; badge.textContent = '0'; }

    cargarMensajes(true);
    clearInterval(pollingTimer);
    pollingTimer = setInterval(() => cargarMensajes(false), 3000);
}

async function cargarMensajes(scroll) {
    if (!cidActivo) return;
    try {
        const res  = await fetch(`${BASE}/chat/mensajes/${cidActivo}?desde=${ultimoId}`);
        const msgs = await res.json();
        if (!Array.isArray(msgs) || msgs.length === 0) return;

        const box = document.getElementById('chatMessages');
        msgs.forEach(m => {
            const esSup = m.emisor === 'superadmin';
            const div   = document.createElement('div');
            div.className = `msg ${esSup ? 'sup' : 'rest'}`;
            div.innerHTML = `
                <div>
                    <div class="msg-bubble">${escHtml(m.mensaje)}</div>
                    <div class="msg-meta">${formatHora(m.created_at)}</div>
                </div>`;
            box.appendChild(div);
            ultimoId = Math.max(ultimoId, parseInt(m.id));
        });
        if (scroll) box.scrollTop = box.scrollHeight;
        else {
            const atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;
            if (atBottom) box.scrollTop = box.scrollHeight;
        }
    } catch(e) {}
}

async function enviarMensaje() {
    const ta  = document.getElementById('inputMensaje');
    const msg = ta.value.trim();
    if (!msg || !cidActivo) return;
    ta.value = '';
    ta.style.height = 'auto';
    const fd = new FormData();
    fd.append('comercio_id', cidActivo);
    fd.append('mensaje', msg);
    try {
        await fetch(`${BASE}/chat/enviar`, { method:'POST', body:fd });
        cargarMensajes(true);
    } catch(e) {}
}

function enviarConEnter(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); enviarMensaje(); }
}

// Auto-resize textarea
document.getElementById('inputMensaje').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\n/g,'<br>');
}
function formatHora(dt) {
    const d = new Date(dt.replace(' ','T'));
    return d.toLocaleTimeString('es', {hour:'2-digit', minute:'2-digit'});
}

// Badge global en sidebar
async function actualizarBadgeSidebar() {
    try {
        const res  = await fetch(`${BASE}/chat/no-leidos`);
        const data = await res.json();
        const el   = document.getElementById('sidebarBadge');
        if (data.total > 0) { el.textContent = data.total; el.style.display = ''; }
        else                 { el.style.display = 'none'; }
    } catch(e) {}
}
setInterval(actualizarBadgeSidebar, 8000);
actualizarBadgeSidebar();
</script>

</body>
</html>
