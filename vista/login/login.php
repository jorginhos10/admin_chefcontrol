<?php
// chefcontrol-sup/vista/login/login.php
require_once __DIR__ . '/../../config/config.php';
$basePath = SupConfig::getBasePath();
$baseUrl  = SupConfig::getBaseUrl();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefControl SUP — Acceso</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="login-card">

    <div class="brand">
        <div class="brand-icon"><i class="fas fa-shield-alt"></i></div>
        <h1>ChefControl SUP</h1>
        <p>Panel de Super Administrador</p>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="<?= $basePath ?>/login">

        <div class="form-group">
            <label class="form-label" for="username">Usuario</label>
            <div class="input-wrap">
                <i class="fas fa-user icon-left"></i>
                <input class="form-input" type="text" id="username" name="username"
                       placeholder="Nombre de usuario" required autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Contraseña</label>
            <div class="input-wrap">
                <i class="fas fa-lock icon-left"></i>
                <input class="form-input" type="password" id="password" name="password"
                       placeholder="••••••••" required autocomplete="current-password">
            </div>
        </div>

        <button type="submit" class="btn-login" id="btnLogin">
            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </button>
    </form>

    <div class="login-footer">
        <i class="fas fa-lock" style="margin-right:5px;"></i>Acceso restringido — solo personal autorizado
    </div>

</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnLogin');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
});
</script>
</body>
</html>
