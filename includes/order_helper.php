<?php
/**
 * Logika Bisnis Pesanan, Status, dan Token Pelacakan
 * Sistem Informasi Queen Laundry
 */

require_once __DIR__ . '/../config/database.php';

function generate_order_code(PDO $pdo): string
{
    $prefix = 'QL-' . date('Ym') . '-';
    $stmt = $pdo->prepare("
        SELECT order_code 
        FROM orders 
        WHERE order_code LIKE ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();

    if ($last) {
        $lastNumber = (int)substr($last, -4);
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }

    return $prefix . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
}

function generate_tracking_token(): string
{
    // Menggunakan karakter alfanumerik yang mudah dibaca tanpa ambigu (tanpa 0/O, 1/I)
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $token = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < 6; $i++) {
        $token .= $chars[random_int(0, $max)];
    }
    return $token;
}

function get_order_status_info(string $status): array
{
    $statuses = [
        'diterima' => [
            'label' => 'Diterima',
            'badge' => 'secondary',
            'icon'  => 'bi-box-seam',
            'step'  => 1,
        ],
        'dicuci' => [
            'label' => 'Sedang Dicuci',
            'badge' => 'primary',
            'icon'  => 'bi-water',
            'step'  => 2,
        ],
        'disetrika' => [
            'label' => 'Sedang Disetrika',
            'badge' => 'info',
            'icon'  => 'bi-fire',
            'step'  => 3,
        ],
        'siap_diambil' => [
            'label' => 'Siap Diambil',
            'badge' => 'warning text-dark',
            'icon'  => 'bi-check2-circle',
            'step'  => 4,
        ],
        'diambil' => [
            'label' => 'Sudah Diambil',
            'badge' => 'success',
            'icon'  => 'bi-check-all',
            'step'  => 5,
        ],
        'dibatalkan' => [
            'label' => 'Dibatalkan',
            'badge' => 'danger',
            'icon'  => 'bi-x-circle',
            'step'  => 0,
        ],
    ];

    return $statuses[$status] ?? [
        'label' => ucfirst($status),
        'badge' => 'dark',
        'icon'  => 'bi-question-circle',
        'step'  => 0,
    ];
}

function get_allowed_next_statuses(string $currentStatus): array
{
    $transitions = [
        'diterima'     => ['dicuci', 'siap_diambil', 'dibatalkan'],
        'dicuci'       => ['disetrika', 'siap_diambil', 'dibatalkan'],
        'disetrika'    => ['siap_diambil', 'dibatalkan'],
        'siap_diambil' => ['diambil', 'dibatalkan'],
        'diambil'      => [],
        'dibatalkan'   => [],
    ];

    return $transitions[$currentStatus] ?? [];
}

function calculate_order_financials(PDO $pdo, int $orderId): array
{
    $stmt = $pdo->prepare("SELECT total_amount FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $totalAmount = (float)$stmt->fetchColumn();

    $stmtPay = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = ?");
    $stmtPay->execute([$orderId]);
    $totalPaid = (float)$stmtPay->fetchColumn();

    $remaining = max(0, $totalAmount - $totalPaid);

    if ($totalPaid >= $totalAmount && $totalAmount > 0) {
        $paymentStatus = 'lunas';
    } elseif ($totalPaid > 0) {
        $paymentStatus = 'sebagian';
    } else {
        $paymentStatus = 'belum_bayar';
    }

    return [
        'total_amount'      => $totalAmount,
        'total_paid'        => $totalPaid,
        'remaining_balance' => $remaining,
        'payment_status'    => $paymentStatus,
    ];
}

