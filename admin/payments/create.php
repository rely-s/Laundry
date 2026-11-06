<?php
/**
 * Handler Pencatatan Pembayaran Kas Masuk
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

$orderId     = (int)($_POST['order_id'] ?? 0);
$rawAmount   = trim($_POST['amount'] ?? '');
$method      = trim($_POST['method'] ?? 'tunai');
$referenceNo = trim($_POST['reference_no'] ?? '');
$user        = current_user();

if ($orderId <= 0) {
    set_flash('danger', 'ID Pesanan tidak valid.');
    redirect('/admin/orders/index.php');
}

try {
    $pdo = get_db();

    // Periksa pesanan & hitung sisa tagihan
    $stmt = $pdo->prepare("SELECT id, order_code, total_amount FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        set_flash('danger', 'Pesanan tidak ditemukan.');
        redirect('/admin/orders/index.php');
    }

    $financials = calculate_order_financials($pdo, $orderId);
    $remaining = $financials['remaining_balance'];

    if (!is_numeric($rawAmount) || (float)$rawAmount <= 0) {
        set_flash('danger', 'Nominal pembayaran harus berupa angka lebih besar dari 0.');
        redirect('/admin/orders/show.php?id=' . $orderId);
    }

    $amount = (float)$rawAmount;

    // Validasi BB09: Pembayaran melebihi sisa tagihan ditolak
    if ($amount > $remaining) {
        set_flash('danger', 'Gagal mencatat pembayaran: Nominal bayar (' . format_rupiah($amount) . ') melebihi sisa tagihan (' . format_rupiah($remaining) . ').');
        redirect('/admin/orders/show.php?id=' . $orderId);
    }

    if (!in_array($method, ['tunai', 'transfer'])) {
        $method = 'tunai';
    }

    $insert = $pdo->prepare("
        INSERT INTO payments (order_id, recorded_by, amount, method, paid_at, reference_no, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), ?, NOW(), NOW())
    ");
    $insert->execute([
        $orderId,
        $user['id'],
        $amount,
        $method,
        !empty($referenceNo) ? $referenceNo : null,
    ]);

    set_flash('success', 'Pembayaran sebesar ' . format_rupiah($amount) . ' berhasil dicatat untuk pesanan ' . $order['order_code'] . '.');
    redirect('/admin/orders/show.php?id=' . $orderId);
} catch (Exception $e) {
    set_flash('danger', 'Terjadi kesalahan sistem saat mencatat pembayaran: ' . $e->getMessage());
    redirect('/admin/orders/show.php?id=' . $orderId);
}

