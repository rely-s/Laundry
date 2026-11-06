<?php
/**
 * Ekspor Laporan Pendapatan ke CSV
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();

$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate   = trim($_GET['end_date'] ?? date('Y-m-d'));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $startDate = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $endDate = date('Y-m-d');
}

$startDatetime = $startDate . ' 00:00:00';
$endExclusiveDatetime = date('Y-m-d 00:00:00', strtotime($endDate . ' +1 day'));

$pdo = get_db();
$stmt = $pdo->prepare("
    SELECT p.id, p.paid_at, o.order_code, c.name AS customer_name, p.method, p.amount, p.reference_no, u.name AS recorder_name
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN customers c ON o.customer_id = c.id
    LEFT JOIN users u ON p.recorded_by = u.id
    WHERE p.paid_at >= ? AND p.paid_at < ?
    ORDER BY p.paid_at ASC
");
$stmt->execute([$startDatetime, $endExclusiveDatetime]);
$rows = $stmt->fetchAll();

$filename = "laporan_pendapatan_queen_laundry_{$startDate}_{$endDate}.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM untuk UTF-8 di Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header Kolom
fputcsv($output, ['ID Pembayaran', 'Waktu Pembayaran', 'Kode Nota', 'Nama Pelanggan', 'Metode Pembayaran', 'Nominal (Rp)', 'Referensi / Keterangan', 'Dicatat Oleh']);

$total = 0;
foreach ($rows as $r) {
    $total += (float)$r['amount'];
    fputcsv($output, [
        $r['id'],
        $r['paid_at'],
        $r['order_code'],
        $r['customer_name'],
        strtoupper($r['method']),
        $r['amount'],
        $r['reference_no'] ?: '-',
        $r['recorder_name'] ?: 'Admin',
    ]);
}

fputcsv($output, []);
fputcsv($output, ['', '', '', '', 'TOTAL PENDAPATAN', $total, '', '']);

fclose($output);
exit;

