<?php
/**
 * Beranda Publik Queen Laundry
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Beranda - Layanan Laundry Terpercaya';

try {
    $pdo = get_db();
    $stmt = $pdo->query("SELECT name, unit, price FROM services WHERE is_active = 1 ORDER BY unit ASC, price ASC");
    $services = $stmt->fetchAll();
} catch (Exception $e) {
    $services = [];
}

require_once __DIR__ . '/templates/public_header.php';
?>

<div class="container py-4">
    <!-- Hero Section -->
    <div class="p-5 mb-5 rounded-4 text-white shadow-sm" style="background: linear-gradient(135deg, #1E3A8A 0%, #0D9488 100%);">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-light text-primary px-3 py-2 rounded-pill fw-bold mb-3">
                    <i class="bi bi-patch-check-fill text-info me-1"></i> Bersih, Higienis & Wangi
                </span>
                <h1 class="display-5 fw-bold mb-3">Selamat Datang di Queen Laundry</h1>
                <p class="lead mb-4 text-white-50">
                    Solusi terbaik untuk perawatan pakaian sehari-hari, bedcover, selimut, dan perlengkapan rumah Anda. Kami menjamin kebersihan maksimal dengan harga terjangkau dan ketepatan waktu.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="/tracking.php" class="btn btn-warning text-dark btn-lg px-4 fw-semibold rounded-pill shadow-sm">
                        <i class="bi bi-search me-1"></i> Cek Status Cucian Saya
                    </a>
                    <a href="#daftar-harga" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                        <i class="bi bi-tags me-1"></i> Lihat Daftar Harga
                    </a>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-block text-center">
                <i class="bi bi-droplet-half text-white opacity-75" style="font-size: 10rem;"></i>
            </div>
        </div>
    </div>

    <!-- Quick Tracking Banner -->
    <div class="card card-custom p-4 mb-5 border-start border-4 border-info">
        <div class="row align-items-center">
            <div class="col-md-7 mb-3 mb-md-0">
                <h4 class="fw-bold mb-1 text-primary"><i class="bi bi-search me-2"></i>Lacak Status Cucian Berdasarkan Nama</h4>
                <p class="text-muted mb-0">Cukup masukkan nama Anda untuk memantau apakah cucian Anda sedang dicuci, disetrika, atau sudah siap diambil.</p>
            </div>
            <div class="col-md-5 text-md-end">
                <a href="/tracking.php" class="btn btn-teal px-4 py-2 rounded-pill fw-semibold">
                    Cek Cucian Saya <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Pricing Section -->
    <div id="daftar-harga" class="mb-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-primary">Daftar Paket Layanan & Tarif</h2>
            <p class="text-muted">Harga transparan, terjangkau, dan tanpa biaya tersembunyi</p>
        </div>

        <div class="row g-4">
            <?php if (empty($services)): ?>
                <div class="col-12 text-center text-muted py-4">Belum ada data layanan aktif.</div>
            <?php else: ?>
                <?php foreach ($services as $svc): ?>
                    <div class="col-md-4">
                        <div class="card card-custom h-100 p-4 text-center hover-shadow">
                            <div class="mb-3">
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                    Satuan per <?= sanitize($svc['unit']) ?>
                                </span>
                            </div>
                            <h4 class="fw-bold text-dark mb-2"><?= sanitize($svc['name']) ?></h4>
                            <div class="my-3">
                                <span class="display-6 fw-bold text-teal" style="color: #0D9488;">
                                    <?= format_rupiah($svc['price']) ?>
                                </span>
                                <span class="text-muted small"> / <?= sanitize($svc['unit']) ?></span>
                            </div>
                            <p class="text-muted small mb-0">Dicuci bersih, wangi tahan lama, rapi dan higienis.</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Keunggulan Section -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card card-custom p-4 h-100">
                <i class="bi bi-clock-history fs-1 text-primary mb-3"></i>
                <h5 class="fw-bold">Tepat Waktu</h5>
                <p class="text-muted small mb-0">Proses pencucian terjadwal dan selesai sesuai estimasi waktu yang diberikan pada nota.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom p-4 h-100">
                <i class="bi bi-shield-check fs-1 text-teal mb-3" style="color: #0D9488;"></i>
                <h5 class="fw-bold">Pencucian Higienis</h5>
                <p class="text-muted small mb-0">Deterjen berkualitas premium, pewangi pilihan, serta pemisahan pakaian putih dan berwarna.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom p-4 h-100">
                <i class="bi bi-phone fs-1 text-warning mb-3"></i>
                <h5 class="fw-bold">Pelacakan Online 24 Jam</h5>
                <p class="text-muted small mb-0">Pantau apakah cucian Anda sedang dicuci, disetrika, atau sudah siap diambil langsung dari HP.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/public_footer.php'; ?>

