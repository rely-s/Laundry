<?php
/**
 * Toggle Status Aktif / Nonaktif Layanan
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_abort();

    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT is_active, name FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch();

        if ($service) {
            $newStatus = $service['is_active'] ? 0 : 1;
            $update = $pdo->prepare("UPDATE services SET is_active = ?, updated_at = NOW() WHERE id = ?");
            $update->execute([$newStatus, $id]);

            $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
            set_flash('info', 'Layanan "' . sanitize($service['name']) . '" berhasil ' . $statusText . '.');
        }
    }
}

redirect('/admin/services/index.php');

