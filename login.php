<?php
/**
 * Halaman Login Administrator
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (is_authenticated()) {
    redirect('/admin/index.php');
}

$pageTitle = 'Login Administrator';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_abort();

    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // Rate limiting sederhana berbasis sesi
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] > 10) {
        $error = 'Terlalu banyak percobaan login gagal. Silakan tunggu beberapa menit.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Reset percobaan login
                unset($_SESSION['login_attempts']);
                
                login_user([
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'role'  => $user['role'],
                ]);

                set_flash('success', 'Selamat datang kembali, ' . sanitize($user['name']) . '!');
                redirect('/admin/index.php');
            } else {
                $error = 'Kombinasi email atau password tidak sesuai.';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/templates/public_header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card card-custom p-4 shadow-sm">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-primary-subtle text-primary mb-2">
                        <i class="bi bi-shield-lock fs-2"></i>
                    </div>
                    <h4 class="fw-bold text-primary mb-1">Login Admin</h4>
                    <p class="text-muted small">Masuk ke panel manajemen Queen Laundry</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= sanitize($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/login.php" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-secondary">Alamat Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?= sanitize($_POST['email'] ?? '') ?>" placeholder="admin@queenlaundry.test" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-pill">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top text-center">
                    <small class="text-muted">
                        Bukan admin? <a href="/tracking.php" class="text-decoration-none fw-semibold">Lacak Cucian Pelanggan</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/public_footer.php'; ?>

