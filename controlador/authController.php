<?php
// chefcontrol-sup/controlador/authController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../modelo/adminModel.php';

class AuthController {

    public function login(): void {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Completa todos los campos.';
            header('Location: ' . SupConfig::getBasePath() . '/login');
            exit;
        }

        $model  = new AdminModel();
        $admin  = $model->verificar($username, $password);

        if (!$admin) {
            $_SESSION['error'] = 'Usuario o contraseña incorrectos.';
            header('Location: ' . SupConfig::getBasePath() . '/login');
            exit;
        }

        $_SESSION['sup_logged_in']    = true;
        $_SESSION['sup_last_activity'] = time();
        $_SESSION['sup_id']           = $admin['id'];
        $_SESSION['sup_username']     = $admin['username'];
        $_SESSION['sup_nombre']       = $admin['nombre'];
        $_SESSION['sup_email']        = $admin['email'];

        header('Location: ' . SupConfig::getBasePath() . '/dashboard');
        exit;
    }

    public function logout(): void {
        session_unset();
        session_destroy();
        header('Location: ' . SupConfig::getBasePath() . '/login');
        exit;
    }
}
