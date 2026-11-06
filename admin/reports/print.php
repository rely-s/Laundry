<?php
/**
 * Format Cetak Laporan Pendapatan Kas
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
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

// 1. Rekap Harian
$stmtDaily = $pdo->prepare("
    SELECT DATE(p.paid_at) AS tanggal,
           COUNT(*) AS jumlah_transaksi,
           SUM(p.amount) AS pendapatan_diterima
    FROM payments p
    WHERE p.paid_at >= ? AND p.paid_at < ?
    GROUP BY DATE(p.paid_at)
    ORDER BY tanggal ASC
");
$stmtDaily->execute([$startDatetime, $endExclusiveDatetime]);
$dailyReports = $stmtDaily->fetchAll();

// 2. Total
$stmtTotal = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total_kas, COUNT(*) AS total_tx
    FROM payments
    WHERE paid_at >= ? AND paid_at < ?
");
$stmtTotal->execute([$startDatetime, $endExclusiveDatetime]);
$totalRow = $stmtTotal->fetch();
$totalRevenue = (float)$totalRow['total_kas'];
$totalTransactions = (int)$totalRow['total_tx'];

// 3. Rincian
$stmtDetails = $pdo->prepare("
    SELECT p.id, p.amount, p.method, p.paid_at, p.reference_no,
           o.order_code, c.name AS customer_name, u.name AS recorder_name
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN customers c ON o.customer_id = c.id
    LEFT JOIN users u ON p.recorded_by = u.id
    WHERE p.paid_at >= ? AND p.paid_at < ?
    ORDER BY p.paid_at ASC
");
$stmtDetails->execute([$startDatetime, $endExclusiveDatetime]);
$paymentDetails = $stmtDetails->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan_Pendapatan_<?= $startDate ?>_<?= $endDate ?> - Queen Laundry</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            background-color: #FFFFFF;
            padding: 20px;
        }
        .header-kop {
            text-align: center;
            border-bottom: 3px double #000000;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }
        .signature-box {
            margin-top: 50px;
            float: right;
            text-align: center;
            width: 240px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="no-print mb-4 text-center">
    <button onclick="window.print()" class="btn btn-primary px-4 fw-semibold me-2">
        <i class="bi bi-printer me-1"></i> Cetak Dokumen
    </button>
    <a href="/admin/reports/index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Sistem
    </a>
</div>

<div class="header-kop">
    <h2 class="fw-bold mb-0">QUEEN LAUNDRY</h2>
    <p class="mb-1">Layanan Cuci Kiloan & Satuan Bersih, Rapi, dan Higienis</p>
    <p class="mb-0 small text-muted">Jl. Kaliurang KM 5, Sleman, D.I. Yogyakarta | Telp/WA: 0812-3456-7890</p>
</div>

<div class="text-center mb-4">
    <h4 class="fw-bold mb-1">LAPORAN PENDAPATAN KAS LAUNDRY</h4>
    <p class="mb-0">Periode: <strong><?= format_tanggal($startDate, false) ?></strong> s/d <strong><?= format_tanggal($endDate, false) ?></strong></p>
</div>

<!-- Ringkasan Singkat -->
<div class="row mb-4">
    <div class="col-6">
        <table class="table table-sm table-bordered">
            <tr>
                <th class="table-light" style="width: 50%;">Total Pembayaran Diterima</th>
                <td><?= $totalTransactions ?> Transaksi</td>
            </tr>
            <tr>
                <th class="table-light">Total Kas Masuk</th>
                <td class="fw-bold text-success fs-6"><?= format_rupiah($totalRevenue) ?></td>
            </tr>
        </table>
    </div>
</div>

<!-- Rekap Harian -->
<h6 class="fw-bold mb-2">A. Rekap Pendapatan Harian</h6>
<table class="table table-bordered table-sm mb-4">
    <thead class="table-light">
        <tr>
            <th class="text-center" style="width: 40px;">No</th>
            <th>Tanggal</th>
            <th class="text-center" style="width: 180px;">Jumlah Transaksi</th>
            <th class="text-end" style="width: 220px;">Subtotal Diterima</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($dailyReports)): ?>
            <tr><td colspan="4" class="text-center py-2 text-muted">Tidak ada kas masuk pada periode ini.</td></tr>
        <?php else: ?>
            <?php foreach ($dailyReports as $i => $row): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td><?= format_tanggal($row['tanggal'], false) ?></td>
                    <td class="text-center"><?= $row['jumlah_transaksi'] ?></td>
                    <td class="text-end"><?= format_rupiah($row['pendapatan_diterima']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr class="fw-bold table-light">
            <th colspan="3" class="text-end">TOTAL KAS:</th>
            <th class="text-end"><?= format_rupiah($totalRevenue) ?></th>
        </tr>
    </tfoot>
</table>

<!-- Rincian Transaksi -->
<h6 class="fw-bold mb-2">B. Rincian Penerimaan Pembayaran</h6>
<table class="table table-bordered table-sm mb-4 small">
    <thead class="table-light">
        <tr>
            <th class="text-center" style="width: 35px;">No</th>
            <th>Waktu Bayar</th>
            <th>Kode Nota</th>
            <th>Nama Pelanggan</th>
            <th>Metode</th>
            <th>No. Referensi / Keterangan</th>
            <th class="text-end">Nominal</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($paymentDetails)): ?>
            <tr><td colspan="7" class="text-center py-2 text-muted">Tidak ada rincian transaksi.</td></tr>
        <?php else: ?>
            <?php foreach ($paymentDetails as $j => $p): ?>
                <tr>
                    <td class="text-center"><?= $j + 1 ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($p['paid_at'])) ?></td>
                    <td class="font-monospace fw-bold"><?= sanitize($p['order_code']) ?></td>
                    <td><?= sanitize($p['customer_name']) ?></td>
                    <td class="text-uppercase"><?= sanitize($p['method']) ?></td>
                    <td><?= sanitize($p['reference_no'] ?: '-') ?></td>
                    <td class="text-end fw-semibold"><?= format_rupiah($p['amount']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Tanda Tangan Pengesahan -->
<div class="clearfix">
    <div class="signature-box">
        <p class="mb-1">Sleman, <?= date('d') . ' ' . [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][(int)date('m')] . ' ' . date('Y') ?></p>
        <p class="mb-5">Pemilik Queen Laundry,</p>
        <br><br>
        <strong class="d-block border-bottom border-dark pb-1">( Denia )</strong>
        <span class="small text-muted">Owner / Administrator</span>
    </div>
</div>

</body>
</html>

