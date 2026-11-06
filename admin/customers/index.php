<?php
/**
 * Kelola Data Pelanggan
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_admin();
$pageTitle = 'Data Pelanggan';

$pdo = get_db();
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT c.id, c.name, c.phone, c.address, c.created_at,
           COUNT(o.id) AS total_orders
    FROM customers c
    LEFT JOIN orders o ON c.id = o.customer_id
";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE c.name LIKE ? OR c.phone LIKE ?";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " GROUP BY c.id ORDER BY c.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-primary mb-1">Daftar Pelanggan</h3>
        <p class="text-muted small mb-0">Kelola informasi kontak dan alamat pelanggan Queen Laundry.</p>
    </div>
    <a href="/admin/customers/create.php" class="btn btn-teal rounded-pill fw-semibold px-3 shadow-sm">
        <i class="bi bi-person-plus me-1"></i> Tambah Pelanggan
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/customers/index.php" class="row g-2 mb-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" value="<?= sanitize($search) ?>" placeholder="Cari nama atau nomor HP...">
                    <button class="btn btn-primary" type="submit">Cari</button>
                    <?php if (!empty($search)): ?>
                        <a href="/admin/customers/index.php" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">#</th>
                        <th>Nama Pelanggan</th>
                        <th>Nomor Telepon / WA</th>
                        <th>Alamat</th>
                        <th class="text-center">Total Pesanan</th>
                        <th>Terdaftar Sejak</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                Tidak ada data pelanggan <?= !empty($search) ? 'yang cocok dengan pencarian' : '' ?>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $index => $c): ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $index + 1 ?></td>
                                <td>
                                    <strong><?= sanitize($c['name']) ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($c['phone'])): ?>
                                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/\D/', '', $c['phone'])) ?>" target="_blank" class="text-decoration-none text-dark">
                                            <i class="bi bi-whatsapp text-success me-1"></i><?= sanitize($c['phone']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= sanitize($c['address'] ?: '-') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= $c['total_orders'] ?> pesanan</span>
                                </td>
                                <td><?= format_tanggal($c['created_at'], false) ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/customers/edit.php?id=<?= $c['id'] ?>" class="btn btn-outline-secondary" title="Edit Data">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="/admin/orders/create.php?customer_id=<?= $c['id'] ?>" class="btn btn-outline-primary" title="Buat Pesanan Baru">
                                            <i class="bi bi-plus-circle"></i>
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

