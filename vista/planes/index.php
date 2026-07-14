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
    <title>Planes — ChefControl SUP</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .planes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .plan-card {
            background: #1e1e2e;
            border: 1px solid #2d2d44;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            transition: transform .2s, box-shadow .2s;
        }
        .plan-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,.3); }
        .plan-card.inactivo { opacity: .5; }
        .plan-card-top {
            padding: 22px 22px 18px;
            border-bottom: 1px solid #2d2d44;
        }
        .plan-badge-destacado {
            position: absolute;
            top: 12px; right: 12px;
            font-size: 10px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .plan-color-dot {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
            font-size: 18px;
            color: #fff;
        }
        .plan-nombre { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .plan-desc   { font-size: 12px; color: #666; margin-bottom: 14px; }
        .plan-precio { font-size: 30px; font-weight: 900; }
        .plan-precio span { font-size: 13px; font-weight: 400; color: #555; }
        .plan-periodo { font-size: 11px; color: #555; margin-top: 2px; }

        .plan-features {
            padding: 16px 22px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-height: 120px;
        }
        .plan-feature {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: #aaa;
        }
        .plan-feature i { font-size: 11px; flex-shrink: 0; }

        .plan-actions {
            padding: 14px 22px;
            border-top: 1px solid #1e1e30;
            display: flex; gap: 8px; flex-wrap: wrap;
        }
        .btn-sm {
            background: #12121e;
            border: 1px solid #2d2d44;
            color: #aaa;
            border-radius: 7px;
            padding: 6px 12px;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex; align-items: center; gap: 5px;
            transition: .15s;
        }
        .btn-sm:hover { background: #2d2d44; color: #e0e0e0; }
        .btn-sm.danger:hover { background: #3b1c1c; color: #f87171; border-color: #7f1d1d; }
        .btn-sm.primary { background: #1a1a4a; border-color: #3730a3; color: #818cf8; }
        .btn-sm.primary:hover { background: #2d2d6a; }
        .visibilidad-btn.active { background: #2d1a5c; border-color: #7c3aed; color: #c4b5fd; }
        .comercio-row:hover { background: #1e1e30; }

        /* Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.65); z-index: 1000;
            align-items: center; justify-content: center;
        }
        .modal-box {
            background: #1e1e2e; border: 1px solid #2d2d44;
            border-radius: 16px; padding: 28px 28px;
            width: 100%; max-width: 540px;
            position: relative; max-height: 90vh; overflow-y: auto;
        }
        .modal-close {
            position: absolute; top: 14px; right: 16px;
            background: none; border: none; color: #555;
            font-size: 18px; cursor: pointer;
        }
        .modal-close:hover { color: #e0e0e0; }
        .field-label { color: #aaa; font-size: 12px; display: block; margin-bottom: 5px; font-weight: 600; }
        .field-input {
            width: 100%; box-sizing: border-box;
            background: #12121e; border: 1px solid #2d2d44;
            border-radius: 7px; padding: 9px 12px;
            color: #e0e0e0; font-size: 13px; outline: none;
        }
        .field-input:focus { border-color: #6366f1; }
        .features-list { display: flex; flex-direction: column; gap: 6px; }
        .feature-row { display: flex; gap: 6px; align-items: center; }
        .feature-row input { flex: 1; }
        .btn-remove-feat {
            background: #3b1c1c; border: none; color: #f87171;
            border-radius: 5px; width: 28px; height: 28px;
            cursor: pointer; font-size: 12px; flex-shrink: 0;
        }
        .btn-add-feat {
            background: #1a1a3a; border: 1px dashed #3730a3;
            color: #818cf8; border-radius: 7px;
            padding: 7px 14px; font-size: 12px; cursor: pointer;
            width: 100%; margin-top: 4px;
        }
        .btn-add-feat:hover { background: #2d2d5a; }
        .color-preview {
            width: 32px; height: 32px; border-radius: 7px;
            border: 1px solid #2d2d44; cursor: pointer; flex-shrink: 0;
        }
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
        <a class="nav-item active" href="<?= $basePath ?>/planes">
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
            <h2>Planes</h2>
            <p><?= date('d \d\e F, Y') ?></p>
        </div>
        <div style="display:flex;gap:12px;align-items:center;">
            <button onclick="abrirCrear()"
                    style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;
                           padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;
                           cursor:pointer;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-plus"></i> Nuevo plan
            </button>
            <a class="btn-logout" href="<?= $basePath ?>/logout">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </div>
    </header>

    <div class="content">

        <?php if (empty($planes)): ?>
        <div class="card">
            <div class="empty-state">
                <i class="fas fa-layer-group"></i> No hay planes creados aún.
            </div>
        </div>
        <?php else: ?>
        <div class="planes-grid">
        <?php foreach ($planes as $p):
            $cars = json_decode($p['caracteristicas'] ?? '[]', true) ?: [];
            $mods = json_decode($p['modulos'] ?? '[]', true) ?: [];
        ?>
            <div class="plan-card <?= !(int)$p['activo'] ? 'inactivo' : '' ?>" id="card-<?= $p['id'] ?>">

                <?php if ((int)$p['destacado']): ?>
                <div class="plan-badge-destacado"
                     style="background:<?= htmlspecialchars($p['color']) ?>22;
                            color:<?= htmlspecialchars($p['color']) ?>;
                            border:1px solid <?= htmlspecialchars($p['color']) ?>44;">
                    <i class="fas fa-star"></i> Recomendado
                </div>
                <?php endif; ?>

                <?php $esPrivado = ($p['visibilidad'] ?? 'publico') === 'privado'; ?>
                <div class="plan-badge-destacado" style="left:12px;right:auto;
                            background:<?= $esPrivado ? '#f59e0b22' : '#22c55e22' ?>;
                            color:<?= $esPrivado ? '#f59e0b' : '#22c55e' ?>;
                            border:1px solid <?= $esPrivado ? '#f59e0b44' : '#22c55e44' ?>;">
                    <i class="fas <?= $esPrivado ? 'fa-lock' : 'fa-globe' ?>"></i>
                    <?= $esPrivado ? 'Privado' : 'Público' ?>
                </div>

                <div class="plan-card-top">
                    <div class="plan-color-dot"
                         style="background:<?= htmlspecialchars($p['color']) ?>22;
                                border:1.5px solid <?= htmlspecialchars($p['color']) ?>66;">
                        <i class="fas fa-crown" style="color:<?= htmlspecialchars($p['color']) ?>;"></i>
                    </div>
                    <div class="plan-nombre"><?= htmlspecialchars($p['nombre']) ?></div>
                    <div class="plan-desc"><?= htmlspecialchars($p['descripcion'] ?? '') ?></div>
                    <div class="plan-precio" style="color:<?= htmlspecialchars($p['color']) ?>">
                        <?= (float)$p['precio'] > 0
                            ? '$' . number_format((float)$p['precio'], 0, ',', '.')
                            : 'Gratis' ?>
                        <?php if ((float)$p['precio'] > 0): ?>
                        <span>/<?= htmlspecialchars($p['periodo']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="plan-features">
                    <?php foreach ($cars as $car): ?>
                    <div class="plan-feature">
                        <i class="fas fa-check" style="color:<?= htmlspecialchars($p['color']) ?>;"></i>
                        <?= htmlspecialchars($car) ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($cars)): ?>
                    <div style="color:#333;font-size:12px;font-style:italic;">Sin características</div>
                    <?php endif; ?>
                    <?php if (!empty($mods)): ?>
                    <div style="margin-top:10px;padding-top:10px;border-top:1px solid #2d2d44;
                                display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                        <span style="color:#555;font-size:11px;">
                            <i class="fas fa-puzzle-piece" style="color:<?= htmlspecialchars($p['color']) ?>;"></i>
                            <?= count($mods) ?> módulo<?= count($mods) !== 1 ? 's' : '' ?>
                        </span>
                        <?php foreach (array_slice($mods, 0, 5) as $m): ?>
                        <span style="background:<?= htmlspecialchars($p['color']) ?>18;color:<?= htmlspecialchars($p['color']) ?>;
                                     border-radius:4px;padding:1px 6px;font-size:10px;font-weight:600;">
                            <?= htmlspecialchars($m) ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($mods) > 5): ?>
                        <span style="color:#555;font-size:10px;">+<?= count($mods) - 5 ?> más</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="plan-actions">
                    <button class="btn-sm primary" onclick="abrirEditar(<?= htmlspecialchars(json_encode($p, JSON_INVALID_UTF8_SUBSTITUTE), ENT_QUOTES) ?>)">
                        <i class="fas fa-pen"></i> Editar
                    </button>
                    <button class="btn-sm" id="btn-toggle-<?= $p['id'] ?>"
                            onclick="toggleActivo(<?= $p['id'] ?>, <?= (int)$p['activo'] ?>)"
                            title="<?= (int)$p['activo'] ? 'Desactivar' : 'Activar' ?>">
                        <i class="fas <?= (int)$p['activo'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                        <?= (int)$p['activo'] ? 'Desactivar' : 'Activar' ?>
                    </button>
                    <?php if (!(int)$p['destacado']): ?>
                    <button class="btn-sm" id="btn-dest-<?= $p['id'] ?>"
                            onclick="marcarDestacado(<?= $p['id'] ?>)"
                            title="Marcar como recomendado">
                        <i class="fas fa-star"></i>
                    </button>
                    <?php endif; ?>
                    <button class="btn-sm danger" onclick="eliminarPlan(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- ── Modal Crear / Editar ────────────────────────────────────────────────── -->
<div class="modal-overlay" id="modalPlan">
    <div class="modal-box">
        <button class="modal-close" onclick="cerrarModal()"><i class="fas fa-times"></i></button>
        <h3 id="modalTitulo" style="color:#fff;margin:0 0 20px;font-size:17px;"></h3>

        <div id="planError" style="display:none;background:#3b1c1c;border:1px solid #e74c3c;
             color:#f87171;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;"></div>

        <form id="formPlan" style="display:flex;flex-direction:column;gap:14px;">
            <input type="hidden" id="planId" name="plan_id">

            <!-- Fila 1: nombre + slug -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="field-label">Nombre *</label>
                    <input class="field-input" id="planNombre" name="nombre" type="text"
                           required placeholder="Plan Pro" oninput="autoSlug(this.value)">
                </div>
                <div>
                    <label class="field-label">Slug * <span style="color:#555;font-size:10px;">(único)</span></label>
                    <input class="field-input" id="planSlug" name="slug" type="text"
                           required placeholder="pro">
                </div>
            </div>

            <!-- Descripción -->
            <div>
                <label class="field-label">Descripción corta</label>
                <input class="field-input" id="planDesc" name="descripcion" type="text"
                       placeholder="Para negocios en crecimiento">
            </div>

            <!-- Precio + periodo + orden -->
            <div style="display:grid;grid-template-columns:1fr 1fr 80px;gap:12px;">
                <div>
                    <label class="field-label">Precio (COP)</label>
                    <input class="field-input" id="planPrecio" name="precio" type="number"
                           min="0" step="1000" placeholder="79000">
                </div>
                <div>
                    <label class="field-label">Período</label>
                    <select class="field-input" id="planPeriodo" name="periodo">
                        <option value="mensual">Mensual</option>
                        <option value="bimestral">Bimestral</option>
                        <option value="trimestral">Trimestral</option>
                        <option value="semestral">Semestral</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
                <div>
                    <label class="field-label">Orden</label>
                    <input class="field-input" id="planOrden" name="orden" type="number" min="0" value="0">
                </div>
            </div>

            <!-- Color + destacado -->
            <div style="display:flex;gap:16px;align-items:center;">
                <div style="flex:1;">
                    <label class="field-label">Color de acento</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input class="field-input" id="planColorText" name="color" type="text"
                               value="#6366f1" placeholder="#6366f1"
                               oninput="syncColor(this.value)"
                               style="flex:1;font-family:monospace;">
                        <input type="color" id="planColorPicker" value="#6366f1"
                               class="color-preview" oninput="syncColorPicker(this.value)">
                    </div>
                </div>
                <div style="padding-top:18px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:#aaa;font-size:13px;">
                        <input type="checkbox" id="planDestacado" name="destacado" value="1"
                               style="width:16px;height:16px;accent-color:#f59e0b;">
                        <i class="fas fa-star" style="color:#f59e0b;"></i> Recomendado
                    </label>
                </div>
            </div>

            <!-- Visibilidad -->
            <div>
                <label class="field-label">Visibilidad</label>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn-sm visibilidad-btn" id="btnVisPublico"
                            onclick="setVisibilidad('publico')"
                            style="flex:1;justify-content:center;padding:9px;">
                        <i class="fas fa-globe"></i> Público (todos los restaurantes)
                    </button>
                    <button type="button" class="btn-sm visibilidad-btn" id="btnVisPrivado"
                            onclick="setVisibilidad('privado')"
                            style="flex:1;justify-content:center;padding:9px;">
                        <i class="fas fa-lock"></i> Privado (solo restaurantes elegidos)
                    </button>
                </div>
                <input type="hidden" id="planVisibilidad" name="visibilidad" value="publico">

                <div id="comerciosPickerWrap" style="display:none;margin-top:10px;">
                    <input class="field-input" id="comerciosBuscar" type="text"
                           placeholder="Buscar restaurante..." oninput="filtrarComercios(this.value)"
                           style="margin-bottom:8px;">
                    <div id="comerciosList" style="max-height:180px;overflow-y:auto;display:flex;
                         flex-direction:column;gap:4px;border:1px solid #2d2d44;border-radius:8px;padding:8px;">
                        <?php foreach ($comercios ?? [] as $c): ?>
                        <label class="comercio-row" data-nombre="<?= strtolower(htmlspecialchars($c['nombre'])) ?>"
                               style="display:flex;align-items:center;gap:8px;padding:5px 6px;
                                      border-radius:6px;cursor:pointer;font-size:12px;color:#aaa;">
                            <input type="checkbox" name="comercios[]" value="<?= (int)$c['id'] ?>"
                                   style="width:14px;height:14px;accent-color:#7c3aed;cursor:pointer;">
                            <?= htmlspecialchars($c['nombre']) ?>
                            <span style="color:#555;">— <?= htmlspecialchars($c['slug'] ?? '') ?></span>
                        </label>
                        <?php endforeach; ?>
                        <?php if (empty($comercios)): ?>
                        <div style="color:#555;font-size:12px;text-align:center;padding:10px;">No hay restaurantes registrados.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Características -->
            <div>
                <label class="field-label">Características</label>
                <div id="featuresList" class="features-list"></div>
                <button type="button" class="btn-add-feat" onclick="addFeature('')">
                    <i class="fas fa-plus"></i> Agregar característica
                </button>
            </div>

            <!-- Módulos habilitados -->
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <label class="field-label" style="margin:0;">Módulos habilitados</label>
                    <div style="display:flex;gap:8px;">
                        <button type="button" onclick="toggleTodosModulos(true)"
                                style="background:none;border:1px solid #2d2d44;color:#888;border-radius:5px;
                                       padding:3px 10px;font-size:11px;cursor:pointer;">
                            Todos
                        </button>
                        <button type="button" onclick="toggleTodosModulos(false)"
                                style="background:none;border:1px solid #2d2d44;color:#888;border-radius:5px;
                                       padding:3px 10px;font-size:11px;cursor:pointer;">
                            Ninguno
                        </button>
                    </div>
                </div>
                <div id="modulosGrid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;">
                    <?php
                    $todosModulos = [
                        'ventas'         => ['Ventas / Caja',    'fa-cash-register'],
                        'cocina'         => ['Pantalla Cocina',  'fa-utensils'],
                        'mesas'          => ['Mesas',            'fa-chair'],
                        'menu-digital'   => ['Menú Digital',     'fa-qrcode'],
                        'domicilios'     => ['Domicilios',       'fa-motorcycle'],
                        'clientes'       => ['Clientes',         'fa-users'],
                        'cupones'        => ['Cupones',          'fa-tag'],
                        'pqrs'           => ['PQRS',             'fa-headset'],
                        'propinas'       => ['Propinas',         'fa-hand-holding-dollar'],
                        'recetas'        => ['Recetas',          'fa-book-open'],
                        'insumos'        => ['Insumos',          'fa-boxes-stacked'],
                        'insumos-internos' => ['Uso Interno',    'fa-broom'],
                        'inventario'     => ['Inventario',       'fa-warehouse'],
                        'inventario-inmobiliario' => ['Inv. Inmobiliario', 'fa-couch'],
                        'proveedores'    => ['Proveedores',      'fa-truck'],
                        'ingresos'       => ['Ingresos',         'fa-arrow-trend-up'],
                        'perdidas'       => ['Pérdidas',         'fa-arrow-trend-down'],
                        'reportes'       => ['Reportes',         'fa-chart-bar'],
                        'chat'           => ['Chat Soporte',     'fa-comments'],
                        'notificaciones' => ['Notificaciones',   'fa-bell'],
                    ];
                    foreach ($todosModulos as $slug => [$label, $icon]): ?>
                    <label style="display:flex;align-items:center;gap:7px;padding:7px 9px;
                                  background:#12121e;border:1px solid #2d2d44;border-radius:7px;
                                  cursor:pointer;font-size:12px;color:#aaa;
                                  transition:border-color .15s;"
                           onmouseover="this.style.borderColor='#3d3d5e'"
                           onmouseout="this.style.borderColor='#2d2d44'">
                        <input type="checkbox" name="modulos[]" value="<?= $slug ?>"
                               id="mod-<?= $slug ?>"
                               style="width:14px;height:14px;accent-color:#7c3aed;cursor:pointer;">
                        <i class="fas <?= $icon ?>" style="color:#555;font-size:11px;width:13px;text-align:center;"></i>
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" id="btnGuardarPlan"
                    style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;
                           padding:12px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;margin-top:4px;">
                <i class="fas fa-save"></i> Guardar plan
            </button>
        </form>
    </div>
</div>

<script>
const BP = '<?= $basePath ?>';
const PLAN_COMERCIOS = <?= json_encode($planComerciosMap ?? [], JSON_FORCE_OBJECT) ?>;

// ── Modal ─────────────────────────────────────────────────────────────────────
function cerrarModal() { document.getElementById('modalPlan').style.display = 'none'; }
document.getElementById('modalPlan').addEventListener('click', function(e) { if (e.target===this) cerrarModal(); });

// ── Visibilidad (público / privado) ────────────────────────────────────────────
function setVisibilidad(v) {
    document.getElementById('planVisibilidad').value = v;
    document.getElementById('btnVisPublico').classList.toggle('active', v === 'publico');
    document.getElementById('btnVisPrivado').classList.toggle('active', v === 'privado');
    document.getElementById('comerciosPickerWrap').style.display = v === 'privado' ? 'block' : 'none';
}

function filtrarComercios(term) {
    term = term.toLowerCase().trim();
    document.querySelectorAll('#comerciosList .comercio-row').forEach(row => {
        row.style.display = !term || row.dataset.nombre.includes(term) ? 'flex' : 'none';
    });
}

function marcarComercios(ids) {
    const set = new Set((ids || []).map(String));
    document.querySelectorAll('#comerciosList input[type=checkbox]').forEach(cb => {
        cb.checked = set.has(cb.value);
    });
}

function abrirCrear() {
    document.getElementById('planId').value        = '';
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-plus" style="color:#7c3aed;margin-right:8px;"></i>Nuevo plan';
    document.getElementById('formPlan').reset();
    document.getElementById('planColorText').value   = '#6366f1';
    document.getElementById('planColorPicker').value = '#6366f1';
    document.getElementById('planError').style.display = 'none';
    document.getElementById('featuresList').innerHTML = '';
    addFeature('');
    toggleTodosModulos(false);
    document.getElementById('comerciosBuscar').value = '';
    filtrarComercios('');
    marcarComercios([]);
    setVisibilidad('publico');
    document.getElementById('modalPlan').style.display = 'flex';
}

function abrirEditar(p) {
  try {
    if (!p) { alert('No se pudo leer la información del plan.'); return; }
    document.getElementById('planId').value          = p.id;
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-pen" style="color:#f59e0b;margin-right:8px;"></i>Editar plan';
    document.getElementById('planNombre').value      = p.nombre   || '';
    document.getElementById('planSlug').value        = p.slug     || '';
    document.getElementById('planDesc').value        = p.descripcion || '';
    document.getElementById('planPrecio').value      = p.precio   || 0;
    document.getElementById('planPeriodo').value     = p.periodo  || 'mensual';
    document.getElementById('planOrden').value       = p.orden    || 0;
    document.getElementById('planColorText').value   = p.color    || '#6366f1';
    document.getElementById('planColorPicker').value = p.color    || '#6366f1';
    document.getElementById('planDestacado').checked = p.destacado == 1;
    document.getElementById('planError').style.display = 'none';

    let cars = [];
    try { cars = JSON.parse(p.caracteristicas || '[]'); } catch (e) { cars = []; }
    document.getElementById('featuresList').innerHTML = '';
    cars.forEach(c => addFeature(c));
    if (!cars.length) addFeature('');

    // Módulos
    let mods = [];
    try { mods = JSON.parse(p.modulos || '[]'); } catch (e) { mods = []; }
    document.querySelectorAll('#modulosGrid input[type=checkbox]').forEach(cb => {
        cb.checked = mods.includes(cb.value);
    });

    // Visibilidad y restaurantes con acceso (si es privado)
    document.getElementById('comerciosBuscar').value = '';
    filtrarComercios('');
    marcarComercios(PLAN_COMERCIOS[p.id] || []);
    setVisibilidad(p.visibilidad === 'privado' ? 'privado' : 'publico');

    document.getElementById('modalPlan').style.display = 'flex';
  } catch (e) {
    console.error('abrirEditar:', e);
    alert('No se pudo abrir el editor de este plan.\n\nError: ' + e.message);
  }
}

function toggleTodosModulos(estado) {
    document.querySelectorAll('#modulosGrid input[type=checkbox]').forEach(cb => cb.checked = estado);
}

// ── Auto slug ─────────────────────────────────────────────────────────────────
function autoSlug(val) {
    if (document.getElementById('planId').value) return; // no tocar en edición
    document.getElementById('planSlug').value = val.toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-');
}

// ── Color sync ────────────────────────────────────────────────────────────────
function syncColor(v)       { if (/^#[0-9a-f]{6}$/i.test(v)) document.getElementById('planColorPicker').value = v; }
function syncColorPicker(v) { document.getElementById('planColorText').value = v; }

// ── Características ───────────────────────────────────────────────────────────
function addFeature(val) {
    const list = document.getElementById('featuresList');
    const row  = document.createElement('div');
    row.className = 'feature-row';
    row.innerHTML = `
        <input class="field-input" type="text" name="caracteristicas[]"
               value="${val.replace(/"/g,'&quot;')}" placeholder="Ej: Mesas ilimitadas">
        <button type="button" class="btn-remove-feat" onclick="this.closest('.feature-row').remove()">
            <i class="fas fa-times"></i>
        </button>`;
    list.appendChild(row);
    row.querySelector('input').focus();
}

// ── Submit ────────────────────────────────────────────────────────────────────
document.getElementById('formPlan').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn  = document.getElementById('btnGuardarPlan');
    const err  = document.getElementById('planError');
    const id   = document.getElementById('planId').value;
    const url  = id ? `${BP}/planes/editar/${id}` : `${BP}/planes/crear`;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    err.style.display = 'none';

    // Incluir destacado manualmente (checkbox no enviado si no está checked)
    const fd = new FormData(this);
    if (!document.getElementById('planDestacado').checked) fd.set('destacado', '0');

    try {
        const res  = await fetch(url, { method: 'POST', body: fd });
        const raw  = await res.text();
        let data;
        try { data = JSON.parse(raw); }
        catch (parseErr) {
            console.error('Respuesta no-JSON del servidor:', raw);
            throw new Error('El servidor respondió algo inesperado (revisa la consola).');
        }
        if (data.ok) { cerrarModal(); location.reload(); }
        else { err.style.display = 'block'; err.textContent = data.msg || 'Error al guardar.'; }
    } catch(ex) {
        err.style.display = 'block'; err.textContent = ex.message || 'Error de conexión.';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Guardar plan';
});

// ── Toggle activo ─────────────────────────────────────────────────────────────
async function toggleActivo(id, activo) {
    const res  = await fetch(`${BP}/planes/toggle/${id}`);
    const data = await res.json();
    if (!data.ok) { alert(data.msg); return; }
    const card = document.getElementById(`card-${id}`);
    const btn  = document.getElementById(`btn-toggle-${id}`);
    if (data.activo) {
        card.classList.remove('inactivo');
        btn.innerHTML = '<i class="fas fa-eye-slash"></i> Desactivar';
        btn.onclick   = () => toggleActivo(id, 1);
    } else {
        card.classList.add('inactivo');
        btn.innerHTML = '<i class="fas fa-eye"></i> Activar';
        btn.onclick   = () => toggleActivo(id, 0);
    }
}

// ── Marcar destacado ──────────────────────────────────────────────────────────
async function marcarDestacado(id) {
    const res  = await fetch(`${BP}/planes/destacar/${id}`);
    const data = await res.json();
    if (data.ok) location.reload();
}

// ── Eliminar ──────────────────────────────────────────────────────────────────
async function eliminarPlan(id, nombre) {
    if (!confirm(`¿Eliminar el plan "${nombre}"? Esta acción no se puede deshacer.`)) return;
    const fd = new FormData();
    const res  = await fetch(`${BP}/planes/eliminar/${id}`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) { document.getElementById(`card-${id}`).remove(); }
    else alert(data.msg);
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
