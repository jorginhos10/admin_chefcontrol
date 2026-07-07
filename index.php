<?php
// chefcontrol-sup/index.php

require_once __DIR__ . '/config/config.php';

$url      = rtrim($_GET['url'] ?? 'login', '/');
$parts    = explode('/', $url);
$action   = $parts[0] ?? 'login';
$basePath = SupConfig::getBasePath();

$loggedIn = !empty($_SESSION['sup_logged_in']);

switch ($action) {

    case 'login':
        if ($loggedIn) { header("Location: {$basePath}/dashboard"); exit; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/controlador/authController.php';
            (new AuthController())->login();
        } else {
            require_once __DIR__ . '/vista/login/login.php';
        }
        break;

    case 'logout':
        require_once __DIR__ . '/controlador/authController.php';
        (new AuthController())->logout();
        break;

    case 'dashboard':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        require_once __DIR__ . '/vista/dashboard/dashboard.php';
        break;

    case 'restaurante':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        require_once __DIR__ . '/modelo/adminModel.php';
        header('Content-Type: application/json');
        $model = new AdminModel();
        $sub   = $parts[1] ?? '';
        $id    = (int)($parts[2] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($sub === 'crear') {
                echo json_encode($model->crearRestaurante(
                    trim($_POST['nombre']         ?? ''),
                    trim($_POST['slug']           ?? ''),
                    trim($_POST['email']          ?? ''),
                    trim($_POST['tipo']           ?? 'restaurante'),
                    trim($_POST['admin_nombre']   ?? ''),
                    trim($_POST['admin_username'] ?? ''),
                    trim($_POST['admin_password'] ?? '')
                ));
            } elseif ($sub === 'editar' && $id) {
                echo json_encode($model->editarRestaurante($id,
                    trim($_POST['nombre'] ?? ''),
                    trim($_POST['email']  ?? ''),
                    trim($_POST['tipo']   ?? ''),
                    trim($_POST['slug']   ?? '')
                ));
            } elseif ($sub === 'aprobar' && $id) {
                echo json_encode($model->aprobarDocumentos($id));
            } elseif ($sub === 'rechazar' && $id) {
                $rechazos = $_POST['rechazos'] ?? [];
                echo json_encode($model->rechazarDocumentos($id, (array)$rechazos));
            } else {
                echo json_encode(['ok' => false, 'msg' => 'Acción no válida.']);
            }
        } elseif ($sub === 'toggle' && $id) {
            echo json_encode($model->toggleActivo($id));
        } elseif ($sub === 'verificar' && $id) {
            echo json_encode($model->toggleVerificado($id));
        } elseif ($sub === 'acceder' && $id) {
            echo json_encode($model->generarAccesoTemporal($id));
        } elseif ($sub === 'impersonar' && $id) {
            // Solo permite acceder si el restaurante está verificado
            $data = $model->generarAccesoTemporal($id);
            if (!$data['ok']) {
                header('Content-Type: application/json');
                echo json_encode($data); exit;
            }
            // Verificar doc_estado
            $docData = $model->obtenerDocumentos($id);
            if (!$docData['ok'] || ($docData['docs']['doc_estado'] ?? '') !== 'verificado') {
                echo '<script>alert("Este restaurante no ha sido verificado. Revisa y aprueba sus documentos antes de acceder.");history.back();</script>';
                exit;
            }
            $u = $data['usuario'];
            $tokenData = $model->crearTokenImpersonacion($id, (int)$u['id']);
            if (!$tokenData['ok']) {
                echo '<script>alert(' . json_encode('No se pudo generar el acceso: ' . ($tokenData['msg'] ?? '')) . ');history.back();</script>';
                exit;
            }
            $chefPath = rtrim(str_replace('chefcontrol-sup', 'chefcontrol', $basePath), '/');
            header("Location: {$chefPath}/impersonar-login/{$tokenData['token']}");
            exit;
        } elseif ($sub === 'documentos' && $id) {
            echo json_encode($model->obtenerDocumentos($id));
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Ruta no encontrada.']);
        }
        exit;

    case 'config':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        require_once __DIR__ . '/modelo/adminModel.php';
        $model = new AdminModel();
        $id    = (int)($parts[1] ?? 0);
        if (!$id) { header("Location: {$basePath}/dashboard"); exit; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $sub = $parts[2] ?? '';
            if ($sub === 'plan') {
                echo json_encode($model->actualizarPlan(
                    $id,
                    trim($_POST['plan']       ?? 'gratuito'),
                    trim($_POST['plan_vence'] ?? '') ?: null,
                    trim($_POST['plan_notas'] ?? '') ?: null
                ));
            } elseif ($sub === 'modulos') {
                // Los checkboxes enviados = módulos ACTIVOS; los ausentes = desactivados
                $todosModulos = ['ventas','cocina','mesas','domicilios','clientes','cupones',
                                 'pqrs','propinas','recetas','insumos','inventario','proveedores',
                                 'ingresos','perdidas','reportes','chat','notificaciones'];
                $activos      = array_filter($todosModulos, fn($m) => !empty($_POST['modulos'][$m]));
                $desactivados = array_values(array_diff($todosModulos, $activos));
                echo json_encode($model->guardarModulos($id, $desactivados));
            } elseif ($sub === 'idioma') {
                echo json_encode($model->actualizarIdioma($id, trim($_POST['idioma'] ?? 'es')));
            } else {
                echo json_encode(['ok' => false, 'msg' => 'Acción no encontrada.']);
            }
            exit;
        }

        $comercio = $model->obtenerPorId($id);
        if (!$comercio) { header("Location: {$basePath}/dashboard"); exit; }
        $totalUsuarios = $model->obtenerTotalUsuarios($id);
        require_once __DIR__ . '/vista/config/index.php';
        break;

    case 'planes':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        require_once __DIR__ . '/modelo/adminModel.php';
        $model = new AdminModel();
        $sub   = $parts[1] ?? '';
        $id    = (int)($parts[2] ?? 0);

        // Vista principal — sin JSON
        if (!$sub) {
            $planes = $model->obtenerPlanes();
            require_once __DIR__ . '/vista/planes/index.php';
            exit;
        }

        // Endpoints JSON
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cars    = array_values(array_filter(array_map('trim', (array)($_POST['caracteristicas'] ?? []))));
            $modulos = array_values(array_filter((array)($_POST['modulos'] ?? [])));
            if ($sub === 'crear') {
                echo json_encode($model->crearPlan(
                    trim($_POST['nombre']      ?? ''), trim($_POST['slug']        ?? ''),
                    trim($_POST['descripcion'] ?? ''), (float)($_POST['precio']   ?? 0),
                    trim($_POST['periodo']     ?? 'mensual'), trim($_POST['color'] ?? '#6366f1'),
                    $cars, (int)($_POST['destacado'] ?? 0), (int)($_POST['orden'] ?? 0), $modulos
                ));
            } elseif ($sub === 'editar' && $id) {
                echo json_encode($model->editarPlan(
                    $id, trim($_POST['nombre'] ?? ''), trim($_POST['slug']        ?? ''),
                    trim($_POST['descripcion'] ?? ''), (float)($_POST['precio']   ?? 0),
                    trim($_POST['periodo']     ?? 'mensual'), trim($_POST['color'] ?? '#6366f1'),
                    $cars, (int)($_POST['destacado'] ?? 0), (int)($_POST['orden'] ?? 0), $modulos
                ));
            } elseif ($sub === 'eliminar' && $id) {
                echo json_encode($model->eliminarPlan($id));
            } else {
                echo json_encode(['ok' => false, 'msg' => 'Acción no válida.']);
            }
        } elseif ($sub === 'toggle'   && $id) { echo json_encode($model->togglePlanActivo($id)); }
        elseif  ($sub === 'destacar'  && $id) { echo json_encode($model->togglePlanDestacado($id)); }
        else { echo json_encode(['ok' => false, 'msg' => 'Ruta no encontrada.']); }
        exit;

    case 'facturacion':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        require_once __DIR__ . '/modelo/adminModel.php';
        $model = new AdminModel();
        $sub   = $parts[1] ?? '';
        $id    = (int)($parts[2] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if ($sub === 'pago') {
                echo json_encode($model->registrarPago(
                    (int)($_POST['comercio_id']   ?? 0),
                    (float)($_POST['monto']       ?? 0),
                    trim($_POST['fecha']           ?? date('Y-m-d')),
                    trim($_POST['metodo']          ?? 'efectivo'),
                    trim($_POST['periodo_hasta']   ?? ''),
                    trim($_POST['referencia']      ?? ''),
                    trim($_POST['notas']           ?? '')
                ));
            } elseif ($sub === 'cambiar-plan' && $id) {
                $plan = trim($_POST['plan'] ?? '');
                if (!$plan) { echo json_encode(['ok' => false, 'msg' => 'Plan requerido.']); exit; }
                echo json_encode($model->cambiarPlanComercio(
                    $id, $plan,
                    (float)($_POST['monto']      ?? 0),
                    trim($_POST['fecha']          ?? date('Y-m-d')),
                    trim($_POST['hasta']          ?? ''),
                    trim($_POST['metodo']         ?? 'efectivo'),
                    trim($_POST['referencia']     ?? '')
                ));
            } else {
                echo json_encode(['ok' => false, 'msg' => 'Acción no válida.']);
            }
            exit;
        }

        if ($sub === 'historial' && $id) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'data' => $model->historialPagos($id)]);
            exit;
        }

        $cuentas = $model->obtenerFacturacion();
        require_once __DIR__ . '/vista/facturacion/index.php';
        break;

    case 'financiera':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        require_once __DIR__ . '/modelo/adminModel.php';
        $model       = new AdminModel();
        $filtroDesde = trim($_GET['desde'] ?? '') ?: date('Y-m-01');
        $filtroHasta = trim($_GET['hasta'] ?? '') ?: date('Y-m-d');
        $financiera  = $model->obtenerFinanciera($filtroDesde, $filtroHasta);
        require_once __DIR__ . '/vista/financiera/index.php';
        break;

    case 'invitacion':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        require_once __DIR__ . '/modelo/adminModel.php';
        header('Content-Type: application/json');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            echo json_encode(['ok' => false, 'msg' => 'Método no permitido.']);
            exit;
        }
        if (($parts[1] ?? '') === 'sms') {
            echo json_encode((new AdminModel())->enviarInvitacionSMS(
                trim($_POST['telefono'] ?? ''),
                trim($_POST['url']      ?? '')
            ));
        } else {
            echo json_encode((new AdminModel())->generarInvitacion());
        }
        exit;

    case 'chat':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        require_once __DIR__ . '/modelo/adminModel.php';
        $model = new AdminModel();
        $sub   = $parts[1] ?? '';
        $id    = (int)($parts[2] ?? 0);

        if ($sub === 'mensajes' && $id) {
            header('Content-Type: application/json');
            $desde = (int)($_GET['desde'] ?? 0);
            $model->marcarLeidos($id, 'restaurante');
            echo json_encode($model->obtenerMensajes($id, $desde));
            exit;
        }
        if ($sub === 'enviar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $cid     = (int)($_POST['comercio_id'] ?? 0);
            $mensaje = trim($_POST['mensaje'] ?? '');
            if (!$cid || !$mensaje) { echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit; }
            echo json_encode($model->enviarMensaje($cid, 'superadmin', $mensaje));
            exit;
        }
        if ($sub === 'no-leidos') {
            header('Content-Type: application/json');
            echo json_encode(['total' => $model->contarNoLeidos()]);
            exit;
        }
        require_once __DIR__ . '/vista/chat/index.php';
        break;

    case 'mensajeria':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        require_once __DIR__ . '/controlador/mensajeriaController.php';
        $mc = new MensajeriaController();
        ($parts[1] ?? '') === 'csv' ? $mc->csv() : $mc->index();
        break;

    case 'configuraciones':
        if (!$loggedIn) { header("Location: {$basePath}/login"); exit; }
        require_once __DIR__ . '/modelo/adminModel.php';
        $model = new AdminModel();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            echo json_encode($model->setConfig(
                trim($_POST['clave'] ?? ''),
                trim($_POST['valor'] ?? '')
            ));
            exit;
        }
        $supConfig = $model->getAllConfig();
        require_once __DIR__ . '/vista/configuraciones/index.php';
        break;

    default:
        if ($loggedIn) { header("Location: {$basePath}/dashboard"); exit; }
        header("Location: {$basePath}/login");
        exit;
}
