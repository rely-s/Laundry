<?php
/**
 * Dashboard Administrator
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/order_helper.php';

require_admin();
$pageTitle = 'Dashboard Ringkasan';

$pdo = get_db();

// 1. KPI Metrik
// Pesanan hari ini
$stmtToday = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(received_at) = CURDATE()");
$ordersToday = (int)$stmtToday->fetchColumn();

// Pesanan sedang diproses (diterima, dicuci, disetrika)
$stmtProcess = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('diterima', 'dicuci', 'disetrika')");
$ordersInProcess = (int)$stmtProcess->fetchColumn();

// Pesanan siap diambil
$stmtReady = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'siap_diambil'");
$ordersReady = (int)$stmtReady->fetchColumn();

// Total Pendapatan Kas Diterima Bulan Ini
$stmtRev = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) 
    FROM payments 
    WHERE MONTH(paid_at) = MONTH(CURDATE()) AND YEAR(paid_at) = YEAR(CURDATE())
");
$revenueThisMonth = (float)$stmtRev->fetchColumn();

// 2. Data Grafik Pendapatan 7 Hari Terakhir
$chartStmt = $pdo->query("
    SELECT DATE(paid_at) AS tgl, SUM(amount) AS total_harian
    FROM payments
    WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(paid_at)
    ORDER BY tgl ASC
");
$chartRaw = $chartStmt->fetchAll();
$chartDataMap = [];
foreach ($chartRaw as $row) {
    $chartDataMap[$row['tgl']] = (float)$row['total_harian'];
}

$chartLabels = [];
$chartValues = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = date('d M', strtotime($d));
    $chartValues[] = $chartDataMap[$d] ?? 0;
}

// 3. Pesanan Terbaru (5 Transaksi Terakhir)
$stmtRecent = $pdo->query("
    SELECT o.id, o.order_code, o.received_at, o.status, o.total_amount, o.tracking_token_plain,
           c.name AS customer_name
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    ORDER BY o.id DESC
    LIMIT 5
");
$recentOrders = $stmtRecent->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>

<!-- Baris Tombol Aksi Cepat -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-primary mb-1">Dashboard Operasional</h3>
        <p class="text-muted small mb-0">Pantauan transaksi laundry dan kas masuk secara real-time.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/orders/create.php" class="btn btn-teal rounded-pill fw-semibold px-3 shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Buat Pesanan Baru
        </a>
        <a href="/admin/reports/index.php" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-bar-chart-line me-1"></i> Laporan
        </a>
    </div>
</div>

<!-- Kartu KPI Ringkasan -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-start border-4 border-primary h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Pesanan Hari Ini</span>
                    <h3 class="fw-bold mb-0 mt-1"><?= $ordersToday ?></h3>
                </div>
                <div class="p-3 bg-primary-subtle text-primary rounded-circle">
                    <i class="bi bi-inbox fs-4"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">Transaksi tercatat hari ini</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-start border-4 border-info h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Sedang Diproses</span>
                    <h3 class="fw-bold mb-0 mt-1 text-info"><?= $ordersInProcess ?></h3>
                </div>
                <div class="p-3 bg-info-subtle text-info rounded-circle">
                    <i class="bi bi-water fs-4"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">Diterima / Dicuci / Disetrika</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-start border-4 border-warning h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Siap Diambil</span>
                    <h3 class="fw-bold mb-0 mt-1 text-warning"><?= $ordersReady ?></h3>
                </div>
                <div class="p-3 bg-warning-subtle text-warning rounded-circle">
                    <i class="bi bi-bell fs-4"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">Selesai dicuci, menunggu pelanggan</small>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 border-start border-4 border-success h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Pendapatan Bulan Ini</span>
                    <h4 class="fw-bold mb-0 mt-1 text-success"><?= format_rupiah($revenueThisMonth) ?></h4>
                </div>
                <div class="p-3 bg-success-subtle text-success rounded-circle">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
            <small class="text-muted mt-2 d-block">Kas masuk yang telah terbayar</small>
        </div>
    </div>
</div>

<!-- Grafik Pendapatan & Pesanan Terbaru -->
<div class="row g-4 mb-4">
    <!-- Kolom Grafik -->
    <div class="col-lg-7">
        <div class="card h-100 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark"><i class="bi bi-graph-up text-primary me-2"></i>Tren Pendapatan (7 Hari Terakhir)</span>
                <span class="badge bg-light text-secondary border">Kas Masuk</span>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Kolom Status Operasional -->
    <div class="col-lg-5">
        <div class="card h-100 shadow-sm">
            <div class="card-header fw-bold text-dark">
                <i class="bi bi-lightning-charge text-warning me-2"></i>Aksi Cepat & Navigasi
            </div>
            <div class="card-body d-flex flex-column justify-content-between gap-3">
                <div class="list-group list-group-flush">
                    <a href="/admin/orders/create.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-primary-subtle text-primary rounded"><i class="bi bi-plus-lg fs-5"></i></div>
                            <div>
                                <strong class="d-block">Buat Pesanan Laundry</strong>
                                <small class="text-muted">Input cucian baru & hitung biaya otomatis</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>

                    <a href="/admin/customers/create.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-success-subtle text-success rounded"><i class="bi bi-person-plus fs-5"></i></div>
                            <div>
                                <strong class="d-block">Daftar Pelanggan Baru</strong>
                                <small class="text-muted">Simpan nama, kontak, dan alamat pelanggan</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>

                    <a href="/admin/reports/index.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-info-subtle text-info rounded"><i class="bi bi-printer fs-5"></i></div>
                            <div>
                                <strong class="d-block">Rekap Laporan Keuangan</strong>
                                <small class="text-muted">Cetak dan periksa penerimaan kas periode</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Pesanan Terbaru -->
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold text-dark"><i class="bi bi-clock-history text-secondary me-2"></i>5 Pesanan Terbaru</span>
        <a href="/admin/orders/index.php" class="btn btn-sm btn-outline-primary rounded-pill">Lihat Semua Pesanan</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Kode Nota</th>
                        <th>Pelanggan</th>
                        <th>Tanggal Terima</th>
                        <th>Status</th>
                        <th class="text-end">Total Biaya</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada transaksi pesanan yang dibuat.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <?php $stInfo = get_order_status_info($order['status']); ?>
                            <tr>
                                <td class="ps-3">
                                    <strong class="text-primary"><?= sanitize($order['order_code']) ?></strong>
                                    <div class="small text-muted font-monospace">Token: <?= sanitize($order['tracking_token_plain']) ?></div>
                                </td>
                                <td>
                                    <strong><?= sanitize($order['customer_name']) ?></strong>
                                </td>
                                <td><?= format_tanggal($order['received_at']) ?></td>
                                <td>
                                    <span class="badge bg-<?= sanitize($stInfo['badge']) ?> rounded-pill px-2 py-1">
                                        <i class="bi <?= sanitize($stInfo['icon']) ?> me-1"></i> <?= sanitize($stInfo['label']) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">
                                    <?= format_rupiah($order['total_amount']) ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/orders/show.php?id=<?= $order['id'] ?>" class="btn btn-outline-primary" title="Detail Pesanan">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                        <a href="/admin/orders/receipt.php?id=<?= $order['id'] ?>" target="_blank" class="btn btn-outline-secondary" title="Cetak Nota">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const labels = <?= json_encode($chartLabels) ?>;
        const dataValues = <?= json_encode($chartValues) ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: dataValues,
                    borderColor: '#0D9488',
                    backgroundColor: 'rgba(13, 148, 136, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#1E3A8A',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Pendapatan: Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + (value / 1000) + 'k';
                            }
                        }
                    }
                }
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

