<?php
/**
 * Tambah Layanan Baru
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_admin();
$pageTitle = 'Tambah Layanan & Tarif';

$name  = '';
$unit  = 'kg';
$price = '';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_abort();

    $name  = trim($_POST['name'] ?? '');
    $unit  = trim($_POST['unit'] ?? 'kg');
    $rawPrice = trim($_POST['price'] ?? '');

    // Validasi BB04: Nama wajib & harga tidak boleh negatif
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
            $pdo = get_db();
            $stmt = $pdo->prepare("
                INSERT INTO services (name, unit, price, is_active, created_at, updated_at)
                VALUES (?, ?, ?, 1, NOW(), NOW())
            ");
            $stmt->execute([$name, $unit, (float)$rawPrice]);

            set_flash('success', 'Paket layanan "' . sanitize($name) . '" berhasil ditambahkan.');
            redirect('/admin/services/index.php');
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem saat menyimpan: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Form Layanan & Tarif Baru</span>
                <a href="/admin/services/index.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= sanitize($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/services/create.php">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold text-secondary small">Nama Layanan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= sanitize($name) ?>" placeholder="Contoh: Cuci Kering Kilat" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="unit" class="form-label fw-semibold text-secondary small">Satuan Pengukuran <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit" name="unit" required>
                            <option value="kg" <?= $unit === 'kg' ? 'selected' : '' ?>>Kilogram (kg) - Untuk pakaian kiloan</option>
                            <option value="item" <?= $unit === 'item' ? 'selected' : '' ?>>Satuan Item (item) - Untuk bedcover, selimut, jaket, dll.</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="price" class="form-label fw-semibold text-secondary small">Tarif per Satuan (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Rp</span>
                            <input type="number" step="100" min="0" class="form-control" id="price" name="price" value="<?= sanitize($price) ?>" placeholder="Contoh: 8000" required>
                        </div>
                        <div class="form-text">Masukkan angka bulat tanpa tanda titik atau minus.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/admin/services/index.php" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-teal px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Layanan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

