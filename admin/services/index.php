<?php
/**
 * Kelola Paket Layanan & Tarif
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_admin();
$pageTitle = 'Layanan & Tarif';

$pdo = get_db();
$stmt = $pdo->query("SELECT id, name, unit, price, is_active FROM services ORDER BY is_active DESC, unit ASC, price ASC");
$services = $stmt->fetchAll();

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="fw-bold text-primary mb-1">Paket Layanan & Tarif</h3>
        <p class="text-muted small mb-0">Atur harga per kilogram atau per satuan item untuk kalkulasi otomatis.</p>
    </div>
    <a href="/admin/services/create.php" class="btn btn-teal rounded-pill fw-semibold px-3 shadow-sm">
        <i class="bi bi-plus-circle me-1"></i> Tambah Layanan
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 50px;">#</th>
                        <th>Nama Layanan</th>
                        <th class="text-center">Satuan</th>
                        <th class="text-end">Tarif Satuan</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: 160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada data paket layanan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $index => $s): ?>
                            <tr class="<?= !$s['is_active'] ? 'table-light text-muted' : '' ?>">
                                <td class="ps-3 text-muted"><?= $index + 1 ?></td>
                                <td>
                                    <strong><?= sanitize($s['name']) ?></strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">Per <?= sanitize($s['unit']) ?></span>
                                </td>
                                <td class="text-end fw-semibold">
                                    <?= format_rupiah($s['price']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['is_active']): ?>
                                        <span class="badge bg-success-subtle text-success px-2 py-1">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a href="/admin/services/edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Layanan">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="/admin/services/toggle.php" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn btn-sm <?= $s['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $s['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <i class="bi <?= $s['is_active'] ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                            </button>
                                        </form>
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

