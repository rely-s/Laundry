<?php
/**
 * Modul Autentikasi dan Otorisasi Sesi
 * Sistem Informasi Queen Laundry
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function is_authenticated(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    if (!is_authenticated()) {
        return null;
    }

    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'Admin',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'admin',
    ];
}

function require_admin(string $redirectTo = '/login.php'): void
{
    if (!is_authenticated()) {
        set_flash('danger', 'Silakan login terlebih dahulu untuk mengakses menu ini.');
        redirect($redirectTo);
        exit;
    }

    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        die('Akses ditolak: Anda tidak memiliki hak akses administrator.');
    }
}

function login_user(array $user): void
{
    // Cegah session fixation dengan regenerasi ID
    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

