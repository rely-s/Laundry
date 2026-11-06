<?php
/**
 * Logout Handler
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_abort();
}

logout_user();
set_flash('info', 'Anda telah berhasil keluar dari sistem.');
redirect('/login.php');

