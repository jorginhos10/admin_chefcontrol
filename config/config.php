<?php
// chefcontrol-sup/config/config.php

class SupConfig {
    const DB_HOST     = 'localhost';
    const DB_NAME     = 'jorginho_app-chefcontrol';      // DB principal (restaurantes)
    const DB_NAME_SUP = 'jorginho_su-chefcontrol';  // DB superadmin
    const DB_USER     = 'jorginho_app-chefcontrol';
    const DB_PASS     = 'jorginho10.';
    const DB_CHARSET  = 'utf8mb4';

    const SESSION_TIMEOUT = 1800;

    // Dominio del panel cliente (repo hermano cliente_chefcontrol), en otro subdominio
    const CLIENT_URL = 'https://chefcontrol.cloud-control.co';

    // ── Inalambria Express (SMS) ─────────────────────────────────────────────────
    const SMS_API_KEY  = 'sk_live_xJzMgrEoJExJ1GppbDzJnuPBD0LEhkQhAYmHFZgD0O4';
    const SMS_API_BASE = 'https://api.inalambria.express/v1';

    // Llamada GET genérica autenticada contra la API de Inalambria Express
    public static function inalambriaGet(string $path, array $query = []): array {
        $url = self::SMS_API_BASE . $path . ($query ? '?' . http_build_query($query) : '');
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . self::SMS_API_KEY],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) return ['ok' => false, 'msg' => $err];
        $data = json_decode((string)$resp, true);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            return ['ok' => false, 'msg' => $data['error'] ?? "Error al consultar Inalambria (HTTP {$code})."];
        }
        return ['ok' => true, 'data' => $data];
    }

    // Envía un SMS de texto plano a un único destinatario (formato E.164, ej. +573001234567)
    public static function enviarSMS(string $telefono, string $mensaje): array {
        $ch = curl_init(self::SMS_API_BASE . '/messages/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . self::SMS_API_KEY,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'content'    => $mensaje,
                'recipients' => [$telefono],
                'async'      => true,
            ]),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) return ['ok' => false, 'msg' => $err];
        $data = json_decode((string)$resp, true) ?? [];
        if ($code >= 200 && $code < 300) return ['ok' => true];
        return ['ok' => false, 'msg' => $data['error'] ?? "No se pudo enviar el SMS (HTTP {$code})."];
    }

    public static function getBasePath(): string {
        return str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
    }

    public static function getBaseUrl(): string {
        $https    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'];
        $basePath = self::getBasePath();
        return "{$https}://{$host}{$basePath}";
    }
}

date_default_timezone_set('America/Mexico_City');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Expirar sesión por inactividad
if (!empty($_SESSION['sup_logged_in'])) {
    $now  = time();
    $last = $_SESSION['sup_last_activity'] ?? $now;
    if (($now - $last) > SupConfig::SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['error'] = 'Sesión expirada por inactividad.';
        header('Location: ' . SupConfig::getBasePath() . '/login');
        exit;
    }
    $_SESSION['sup_last_activity'] = $now;
}
