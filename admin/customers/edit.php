<?php
/**
 * Edit Data Pelanggan
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_admin();
$pageTitle = 'Edit Data Pelanggan';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID Pelanggan tidak valid.');
    redirect('/admin/customers/index.php');
}

$pdo = get_db();
$stmt = $pdo->prepare("SELECT id, name, phone, address FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    set_flash('danger', 'Data pelanggan tidak ditemukan.');
    redirect('/admin/customers/index.php');
}

$name    = $customer['name'];
$phone   = $customer['phone'] ?? '';
$address = $customer['address'] ?? '';
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_abort();

    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        $error = 'Nama pelanggan wajib diisi.';
    } else {
        try {
            $update = $pdo->prepare("
                UPDATE customers 
                SET name = ?, phone = ?, address = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $update->execute([
                $name,
                !empty($phone) ? $phone : null,
                !empty($address) ? $address : null,
                $id,
            ]);

            set_flash('success', 'Perubahan data pelanggan berhasil disimpan.');
            redirect('/admin/customers/index.php');
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
                <span class="fw-bold"><i class="bi bi-pencil me-2 text-primary"></i>Edit Pelanggan</span>
                <a href="/admin/customers/index.php" class="btn btn-sm btn-outline-secondary">Kembali</a>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= sanitize($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/customers/edit.php?id=<?= $id ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold text-secondary small">Nama Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= sanitize($name) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold text-secondary small">Nomor Telepon / WhatsApp</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?= sanitize($phone) ?>">
                    </div>

                    <div class="mb-4">
                        <label for="address" class="form-label fw-semibold text-secondary small">Alamat Lengkap</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?= sanitize($address) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/admin/customers/index.php" class="btn btn-light border px-4">Batal</a>
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

