<?php
/**
 * Handler Pembaruan Status Pesanan
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/order_helper.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/orders/index.php');
}

validate_csrf_or_abort();

$orderId  = (int)($_POST['order_id'] ?? 0);
$toStatus = trim($_POST['to_status'] ?? '');
$note     = trim($_POST['note'] ?? '');
$user     = current_user();

if ($orderId <= 0 || empty($toStatus)) {
    set_flash('danger', 'Data pembaruan status tidak lengkap.');
    redirect('/admin/orders/index.php');
}

try {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, status, order_code FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        set_flash('danger', 'Pesanan tidak ditemukan.');
        redirect('/admin/orders/index.php');
    }

    $currentStatus = $order['status'];
    $allowed = get_allowed_next_statuses($currentStatus);

    if (!in_array($toStatus, $allowed)) {
        set_flash('danger', "Transisi status dari '{$currentStatus}' ke '{$toStatus}' tidak diperbolehkan.");
        redirect('/admin/orders/show.php?id=' . $orderId);
    }

    $pdo->beginTransaction();

    // 1. Update status di tabel orders
    $updateStmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$toStatus, $orderId]);

    // 2. Catat riwayat di order_status_histories (BB07)
    $histStmt = $pdo->prepare("
        INSERT INTO order_status_histories (order_id, changed_by, from_status, to_status, changed_at, note, created_at)
        VALUES (?, ?, ?, ?, NOW(), ?, NOW())
    ");
    $histStmt->execute([
        $orderId,
        $user['id'],
        $currentStatus,
        $toStatus,
        !empty($note) ? $note : 'Status diubah ke ' . $toStatus,
    ]);

    $pdo->commit();

    $toInfo = get_order_status_info($toStatus);
    set_flash('success', "Status pesanan {$order['order_code']} berhasil diperbarui menjadi: " . $toInfo['label']);
    redirect('/admin/orders/show.php?id=' . $orderId);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Terjadi kesalahan saat memperbarui status: ' . $e->getMessage());
    redirect('/admin/orders/show.php?id=' . $orderId);
}

