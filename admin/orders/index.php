<?php
/**
 * Daftar Pesanan Laundry
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/order_helper.php';

require_admin();
$pageTitle = 'Pesanan Laundry';

$pdo = get_db();
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT o.id, o.order_code, o.customer_id, o.received_at, o.estimated_at, o.status, o.total_amount,
           o.tracking_token_plain,
           c.name AS customer_name, c.phone AS customer_phone,
           COALESCE(SUM(p.amount), 0) AS total_paid
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    LEFT JOIN payments p ON o.id = p.order_id
";

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(o.order_code LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($statusFilter) && in_array($statusFilter, ['diterima', 'dicuci', 'disetrika', 'siap_diambil', 'diambil', 'dibatalkan'])) {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " GROUP BY o.id ORDER BY o.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-primary mb-1">Pesanan Laundry</h3>
        <p class="text-muted small mb-0">Kelola antrean pencucian, pembaruan status, dan pembayaran pelanggan.</p>
    </div>
    <a href="/admin/orders/create.php" class="btn btn-teal rounded-pill fw-semibold px-3 shadow-sm">
        <i class="bi bi-plus-circle me-1"></i> Buat Pesanan Baru
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <!-- Filter dan Pencarian -->
        <form method="GET" action="/admin/orders/index.php" class="row g-2 mb-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" value="<?= sanitize($search) ?>" placeholder="Cari kode nota atau nama pelanggan...">
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value="">Semua Status Pesanan</option>
                    <option value="diterima" <?= $statusFilter === 'diterima' ? 'selected' : '' ?>>Diterima</option>
                    <option value="dicuci" <?= $statusFilter === 'dicuci' ? 'selected' : '' ?>>Sedang Dicuci</option>
                    <option value="disetrika" <?= $statusFilter === 'disetrika' ? 'selected' : '' ?>>Sedang Disetrika</option>
                    <option value="siap_diambil" <?= $statusFilter === 'siap_diambil' ? 'selected' : '' ?>>Siap Diambil</option>
                    <option value="diambil" <?= $statusFilter === 'diambil' ? 'selected' : '' ?>>Sudah Diambil</option>
                    <option value="dibatalkan" <?= $statusFilter === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <?php if (!empty($search) || !empty($statusFilter)): ?>
                    <a href="/admin/orders/index.php" class="btn btn-outline-secondary">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Tabel Pesanan -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Kode Nota</th>
                        <th>Pelanggan</th>
                        <th>Tanggal Masuk</th>
                        <th>Estimasi Selesai</th>
                        <th>Status Cucian</th>
                        <th class="text-end">Total Biaya</th>
                        <th class="text-center">Pembayaran</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                Tidak ada data pesanan yang sesuai kriteria pencarian.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                                $stInfo = get_order_status_info($order['status']);
                                $totalAmount = (float)$order['total_amount'];
                                $totalPaid   = (float)$order['total_paid'];
                                $isLunas     = $totalPaid >= $totalAmount && $totalAmount > 0;
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <strong class="text-primary font-monospace"><?= sanitize($order['order_code']) ?></strong>
                                    <div class="small text-muted font-monospace">Token: <span class="badge bg-light text-dark border"><?= sanitize($order['tracking_token_plain']) ?></span></div>
                                </td>
                                <td>
                                    <strong><?= sanitize($order['customer_name']) ?></strong>
                                    <?php if (!empty($order['customer_phone'])): ?>
                                        <div class="small text-muted"><?= sanitize($order['customer_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= format_tanggal($order['received_at']) ?></td>
                                <td><?= format_tanggal($order['estimated_at'], false) ?></td>
                                <td>
                                    <span class="badge bg-<?= sanitize($stInfo['badge']) ?> rounded-pill px-2 py-1">
                                        <i class="bi <?= sanitize($stInfo['icon']) ?> me-1"></i> <?= sanitize($stInfo['label']) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">
                                    <?= format_rupiah($totalAmount) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isLunas): ?>
                                        <span class="badge bg-success-subtle text-success px-2 py-1">Lunas</span>
                                    <?php elseif ($totalPaid > 0): ?>
                                        <span class="badge bg-warning-subtle text-warning px-2 py-1">Sebagian</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger px-2 py-1">Belum Bayar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/orders/show.php?id=<?= $order['id'] ?>" class="btn btn-outline-primary" title="Detail & Pembayaran">
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

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

