<?php
/**
 * Edit Paket Layanan
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_admin();
$pageTitle = 'Edit Layanan & Tarif';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID Layanan tidak valid.');
    redirect('/admin/services/index.php');
}

$pdo = get_db();
$stmt = $pdo->prepare("SELECT id, name, unit, price, is_active FROM services WHERE id = ?");
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) {
    set_flash('danger', 'Paket layanan tidak ditemukan.');
    redirect('/admin/services/index.php');
}

$name  = $service['name'];
$unit  = $service['unit'];
$price = (float)$service['price'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_abort();

    $name     = trim($_POST['name'] ?? '');
    $unit     = trim($_POST['unit'] ?? 'kg');
    $rawPrice = trim($_POST['price'] ?? '');

    if (empty($name)) {
        $error = 'Nama layanan wajib diisi.';
    } elseif (!in_array($unit, ['kg', 'item'])) {
        $error = 'Satuan harus berupa "kg" atau "item".';
    } elseif (!is_numeric($rawPrice)) {
        $error = 'Tarif harus berupa angka yang valid.';
    } elseif ((float)$rawPrice < 0) {
        $error = 'Tarif layanan tidak boleh bertanda negatif.';
    } else {
        try {
            $update = $pdo->prepare("
                UPDATE services 
                SET name = ?, unit = ?, price = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $update->execute([$name, $unit, (float)$rawPrice, $id]);

            set_flash('success', 'Perubahan paket layanan berhasil disimpan.');
            redirect('/admin/services/index.php');
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem saat memperbarui: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-pencil me-2 text-primary"></i>Edit Layanan & Tarif</span>
                <a href="/admin/services/index.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= sanitize($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/services/edit.php?id=<?= $id ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold text-secondary small">Nama Layanan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= sanitize($name) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="unit" class="form-label fw-semibold text-secondary small">Satuan Pengukuran <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit" name="unit" required>
                            <option value="kg" <?= $unit === 'kg' ? 'selected' : '' ?>>Kilogram (kg)</option>
                            <option value="item" <?= $unit === 'item' ? 'selected' : '' ?>>Satuan Item (item)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="price" class="form-label fw-semibold text-secondary small">Tarif per Satuan (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Rp</span>
                            <input type="number" step="100" min="0" class="form-control" id="price" name="price" value="<?= $price ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/admin/services/index.php" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

