<?php
/**
 * Laporan Pendapatan Kas Periode
 * Sistem Informasi Queen Laundry
 * Sesuai Perancangan 01_ANALISIS_KEBUTUHAN.md F09 & 03_ERD_DATABASE.md
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();
$pageTitle = 'Laporan Pendapatan';

$pdo = get_db();

// Default filter: Tanggal 1 bulan ini sampai hari ini
$startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
$endDate   = trim($_GET['end_date'] ?? date('Y-m-d'));

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $startDate = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $endDate = date('Y-m-d');
}

// akhir_eksklusif = hari setelah endDate pada jam 00:00:00 (BB10)
$startDatetime = $startDate . ' 00:00:00';
$endExclusiveDatetime = date('Y-m-d 00:00:00', strtotime($endDate . ' +1 day'));

// 1. Rekap Harian Pembayaran Kas Masuk (Query ERD 03 § Contoh Laporan)
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

// 2. Total Kas Masuk pada Periode
$stmtTotal = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total_kas, COUNT(*) AS total_tx
    FROM payments
    WHERE paid_at >= ? AND paid_at < ?
");
$stmtTotal->execute([$startDatetime, $endExclusiveDatetime]);
$totalRow = $stmtTotal->fetch();
$totalRevenue = (float)$totalRow['total_kas'];
$totalTransactions = (int)$totalRow['total_tx'];

// 3. Rincian Setiap Transaksi Pembayaran
$stmtDetails = $pdo->prepare("
    SELECT p.id, p.amount, p.method, p.paid_at, p.reference_no,
           o.order_code, o.id AS order_id,
           c.name AS customer_name,
           u.name AS recorder_name
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN customers c ON o.customer_id = c.id
    LEFT JOIN users u ON p.recorded_by = u.id
    WHERE p.paid_at >= ? AND p.paid_at < ?
    ORDER BY p.paid_at DESC
");
$stmtDetails->execute([$startDatetime, $endExclusiveDatetime]);
$paymentDetails = $stmtDetails->fetchAll();

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-primary mb-1">Laporan Pendapatan Kas</h3>
        <p class="text-muted small mb-0">Rekapitulasi penerimaan uang laundry yang sah berdasarkan pembayaran masuk.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/reports/print.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" target="_blank" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </a>
        <a href="/admin/reports/export.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-teal rounded-pill">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Ekspor CSV
        </a>
    </div>
</div>

<!-- Card Filter Periode -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/reports/index.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="start_date" class="form-label small fw-semibold text-secondary">Tanggal Mulai</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= sanitize($startDate) ?>" required>
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label small fw-semibold text-secondary">Tanggal Selesai</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= sanitize($endDate) ?>" required>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Terapkan Filter
                </button>
                <a href="/admin/reports/index.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Ringkasan Kartu Kas -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-4">
        <div class="card p-3 border-start border-4 border-success h-100">
            <span class="text-muted small fw-semibold text-uppercase">Total Kas Diterima</span>
            <h3 class="fw-bold text-success mt-1 mb-0"><?= format_rupiah($totalRevenue) ?></h3>
            <small class="text-muted mt-2 d-block">Periode: <?= format_tanggal($startDate, false) ?> s/d <?= format_tanggal($endDate, false) ?></small>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card p-3 border-start border-4 border-primary h-100">
            <span class="text-muted small fw-semibold text-uppercase">Jumlah Transaksi Pembayaran</span>
            <h3 class="fw-bold text-primary mt-1 mb-0"><?= $totalTransactions ?> Transaksi</h3>
            <small class="text-muted mt-2 d-block">Pembayaran tunai & transfer manual</small>
        </div>
    </div>
</div>

<!-- Tabel Rekapitulasi Harian -->
<div class="card shadow-sm mb-4">
    <div class="card-header fw-bold text-dark">
        <i class="bi bi-calendar-check me-2 text-primary"></i>Rekap Pendapatan per Tanggal (Harian)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">#</th>
                        <th>Tanggal Pembayaran</th>
                        <th class="text-center">Jumlah Pembayaran Diterima</th>
                        <th class="text-end pe-3">Subtotal Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dailyReports)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Tidak ada transaksi pembayaran pada rentang tanggal ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dailyReports as $idx => $d): ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                <td><strong><?= format_tanggal($d['tanggal'], false) ?></strong></td>
                                <td class="text-center"><?= $d['jumlah_transaksi'] ?> pembayaran</td>
                                <td class="text-end pe-3 fw-bold text-success"><?= format_rupiah($d['pendapatan_diterima']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-group-divider table-light">
                    <tr>
                        <th colspan="3" class="text-end fs-6">TOTAL KAS DITERIMA:</th>
                        <th class="text-end pe-3 fs-5 text-success"><?= format_rupiah($totalRevenue) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Tabel Rincian Semua Pembayaran -->
<div class="card shadow-sm mb-4">
    <div class="card-header fw-bold text-dark">
        <i class="bi bi-list-check me-2 text-secondary"></i>Rincian Riwayat Pembayaran Masuk
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Waktu Bayar</th>
                        <th>Kode Nota</th>
                        <th>Pelanggan</th>
                        <th>Metode</th>
                        <th>No. Referensi / Ket.</th>
                        <th>Kasir</th>
                        <th class="text-end pe-3">Nominal Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paymentDetails)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Tidak ada rincian data pembayaran.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paymentDetails as $p): ?>
                            <tr>
                                <td class="ps-3 small"><?= format_tanggal($p['paid_at']) ?></td>
                                <td>
                                    <a href="/admin/orders/show.php?id=<?= $p['order_id'] ?>" class="text-decoration-none fw-bold font-monospace">
                                        <?= sanitize($p['order_code']) ?>
                                    </a>
                                </td>
                                <td><?= sanitize($p['customer_name']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border text-uppercase"><?= sanitize($p['method']) ?></span>
                                </td>
                                <td class="small text-muted"><?= sanitize($p['reference_no'] ?: '-') ?></td>
                                <td class="small"><?= sanitize($p['recorder_name'] ?: 'Admin') ?></td>
                                <td class="text-end pe-3 fw-semibold text-success"><?= format_rupiah($p['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

