<?php
/**
 * Tambah Data Pelanggan Baru
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_admin();
$pageTitle = 'Tambah Pelanggan Baru';

$name    = '';
$phone   = '';
$address = '';
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_abort();

    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validasi BB03: Nama wajib diisi
    if (empty($name)) {
        $error = 'Nama pelanggan wajib diisi.';
    } else {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare("
                INSERT INTO customers (name, phone, address, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $name,
                !empty($phone) ? $phone : null,
                !empty($address) ? $address : null,
            ]);

            $newId = $pdo->lastInsertId();
            set_flash('success', 'Pelanggan "' . sanitize($name) . '" berhasil ditambahkan.');

            if (!empty($_GET['return_to_order'])) {
                redirect('/admin/orders/create.php?customer_id=' . $newId);
            } else {
                redirect('/admin/customers/index.php');
            }
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
                <span class="fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Form Pelanggan Baru</span>
                <a href="/admin/customers/index.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= sanitize($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/customers/create.php<?= !empty($_GET['return_to_order']) ? '?return_to_order=1' : '' ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold text-secondary small">Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= sanitize($name) ?>" placeholder="Contoh: Rina Wijaya" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold text-secondary small">Nomor Telepon / WhatsApp</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?= sanitize($phone) ?>" placeholder="Contoh: 08123456789">
                        <div class="form-text">Boleh dikosongkan jika pelanggan tidak memiliki nomor telepon.</div>
                    </div>

                    <div class="mb-4">
                        <label for="address" class="form-label fw-semibold text-secondary small">Alamat Lengkap</label>
                        <textarea class="form-control" id="address" name="address" rows="3" placeholder="Contoh: Jl. Magelang KM 6, Sleman"><?= sanitize($address) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/admin/customers/index.php" class="btn btn-light border px-4">Batal</a>
                        <button type="submit" class="btn btn-teal px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Pelanggan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

