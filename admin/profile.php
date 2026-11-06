<?php
/**
 * Pengaturan Profil & Ganti Password Administrator
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

require_admin();
$pageTitle = 'Ganti Password Admin';

$user = current_user();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_abort();

    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword     = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Semua field password wajib diisi.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password baru minimal harus 6 karakter.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password baru tidak cocok.';
    } else {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($currentPassword, $hash)) {
                $error = 'Password saat ini (lama) tidak benar.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $update = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                $update->execute([$newHash, $user['id']]);

                set_flash('success', 'Password administrator berhasil diperbarui.');
                redirect('/admin/profile.php');
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header fw-bold text-dark">
                <i class="bi bi-shield-lock me-2 text-primary"></i>Ganti Password Administrator
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= sanitize($error) ?>
                    </div>
                <?php endif; ?>

                <div class="mb-3 p-3 bg-light rounded-3 small">
                    <div class="text-muted">Nama Pengguna:</div>
                    <strong class="d-block text-dark"><?= sanitize($user['name']) ?></strong>
                    <div class="text-muted mt-1">Alamat Email:</div>
                    <strong class="d-block text-dark"><?= sanitize($user['email']) ?></strong>
                </div>

                <form method="POST" action="/admin/profile.php">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="current_password" class="form-label small fw-semibold text-secondary">Password Saat Ini (Lama) <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label small fw-semibold text-secondary">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" placeholder="Minimal 6 karakter" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label small fw-semibold text-secondary">Ulangi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-key me-1"></i> Perbarui Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

