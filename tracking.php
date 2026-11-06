<?php
/**
 * Halaman Publik Pelacakan Status Cucian Berdasarkan Nama Pelanggan
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/order_helper.php';

$pageTitle = 'Lacak Status Cucian';

$searchQuery = trim($_GET['nama'] ?? $_GET['q'] ?? '');
$searched    = !empty($searchQuery);
$orders      = [];
$errorMessage = null;

if ($searched) {
    try {
        $pdo = get_db();

        // Cari pesanan berdasarkan nama pelanggan, no telepon, atau kode pesanan
        $stmt = $pdo->prepare("
            SELECT o.id, o.order_code, o.received_at, o.estimated_at, o.status, o.total_amount, o.notes,
                   c.name AS customer_name, c.phone AS customer_phone
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            WHERE c.name LIKE ? OR c.phone LIKE ? OR o.order_code LIKE ?
            ORDER BY o.id DESC
            LIMIT 10
        ");
        $param = '%' . $searchQuery . '%';
        $stmt->execute([$param, $param, $param]);
        $orders = $stmt->fetchAll();

        if (!empty($orders)) {
            // Ambil rincian item layanan dan status keuangan untuk setiap pesanan
            $stmtItems = $pdo->prepare("
                SELECT oi.order_id, oi.quantity, oi.unit_price, oi.subtotal, s.name AS service_name, s.unit
                FROM order_items oi
                JOIN services s ON oi.service_id = s.id
                WHERE oi.order_id = ?
            ");

            foreach ($orders as &$ord) {
                $stmtItems->execute([$ord['id']]);
                $ord['items'] = $stmtItems->fetchAll();
                $ord['financials'] = calculate_order_financials($pdo, (int)$ord['id']);
            }
            unset($ord);
        } else {
            $errorMessage = 'Tidak ditemukan data cucian atas nama "' . sanitize($searchQuery) . '". Silakan pastikan penulisan nama sudah sesuai.';
        }
    } catch (Exception $e) {
        $errorMessage = 'Terjadi kesalahan sistem saat mencari pesanan: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/templates/public_header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <!-- Box Form Pencarian Berdasarkan Nama -->
            <div class="card card-custom p-4 shadow-sm mb-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex p-3 rounded-circle bg-teal-subtle text-teal mb-2" style="background-color: #CCFBF1; color: #0D9488;">
                        <i class="bi bi-search fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-primary mb-1">
                        Lacak Status Cucian Anda
                    </h3>
                    <p class="text-muted small">
                        Cukup masukkan <strong>Nama Anda</strong> yang didaftarkan saat mengantar pakaian ke Queen Laundry.
                    </p>
                </div>

                <form method="GET" action="/tracking.php" class="row g-2 justify-content-center">
                    <div class="col-sm-8 col-md-9">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= sanitize($searchQuery) ?>" placeholder="Contoh: Siti Rahma / Budi..." required autofocus>
                        </div>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <button type="submit" class="btn btn-teal btn-lg w-100 fw-semibold">
                            <i class="bi bi-search me-1"></i> Cek Status
                        </button>
                    </div>
                </form>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-warning mt-4 mb-0" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i> <?= $errorMessage ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Hasil Pencarian Status Pesanan -->
            <?php if (!empty($orders)): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-card-checklist text-primary me-2"></i>Hasil Pencarian: <?= count($orders) ?> Pesanan Ditemukan
                    </h5>
                    <span class="badge bg-light text-dark border">Nama: <?= sanitize($searchQuery) ?></span>
                </div>

                <?php foreach ($orders as $ord): ?>
                    <?php
                        $statusInfo = get_order_status_info($ord['status']);
                        $currentStep = $statusInfo['step'];
                    ?>
                    <div class="card card-custom p-4 shadow-sm mb-4 border-start border-4 border-teal" style="border-left-color: #0D9488 !important;">
                        <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4 gap-2">
                            <div>
                                <h4 class="fw-bold text-primary mb-1">
                                    <i class="bi bi-person-circle me-1"></i> <?= sanitize($ord['customer_name']) ?>
                                </h4>
                                <div class="text-muted small">
                                    Tanggal Masuk: <strong><?= format_tanggal($ord['received_at']) ?></strong>
                                    <?php if (!empty($ord['order_code'])): ?>
                                        <span class="ms-2 badge bg-light text-secondary border font-monospace"><?= sanitize($ord['order_code']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?= sanitize($statusInfo['badge']) ?> fs-6 px-3 py-2 rounded-pill">
                                    <i class="bi <?= sanitize($statusInfo['icon']) ?> me-1"></i> <?= sanitize($statusInfo['label']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Visual Stepper Progress Bar -->
                        <?php if ($ord['status'] !== 'dibatalkan'): ?>
                            <div class="stepper-wrapper mb-4">
                                <?php
                                $steps = [
                                    1 => 'Diterima',
                                    2 => 'Sedang Dicuci',
                                    3 => 'Sedang Disetrika',
                                    4 => 'Siap Diambil',
                                    5 => 'Sudah Diambil',
                                ];
                                foreach ($steps as $stepNum => $stepLabel):
                                    $stepClass = '';
                                    if ($currentStep > $stepNum) {
                                        $stepClass = 'completed';
                                    } elseif ($currentStep === $stepNum) {
                                        $stepClass = 'active';
                                    }
                                ?>
                                    <div class="stepper-item <?= $stepClass ?>">
                                        <div class="step-counter">
                                            <?php if ($currentStep > $stepNum): ?>
                                                <i class="bi bi-check-lg"></i>
                                            <?php else: ?>
                                                <?= $stepNum ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="step-name"><?= $stepLabel ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger mb-4">
                                <i class="bi bi-x-circle-fill me-2"></i> Pesanan ini telah <strong>Dibatalkan</strong> oleh pihak laundry.
                            </div>
                        <?php endif; ?>

                        <!-- Informasi Estimasi & Catatan -->
                        <div class="row g-3 bg-light p-3 rounded-3 mb-3">
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Estimasi Selesai:</small>
                                <strong class="text-primary fs-6"><?= format_tanggal($ord['estimated_at']) ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Status Pembayaran:</small>
                                <?php if ($ord['financials']['payment_status'] === 'lunas'): ?>
                                    <strong class="text-success"><i class="bi bi-check-circle me-1"></i>LUNAS</strong>
                                <?php elseif ($ord['financials']['payment_status'] === 'sebagian'): ?>
                                    <strong class="text-warning text-dark">Dibayar Sebagian (Sisa: <?= format_rupiah($ord['financials']['remaining_balance']) ?>)</strong>
                                <?php else: ?>
                                    <strong class="text-danger">Belum Lunas (Bayar saat pengambilan)</strong>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($ord['notes'])): ?>
                                <div class="col-12 border-top pt-2 mt-2">
                                    <small class="text-muted d-block">Catatan Cucian:</small>
                                    <span class="small text-dark"><?= sanitize($ord['notes']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Rincian Layanan yang Dicuci -->
                        <h6 class="fw-bold text-dark mb-2">Rincian Cucian:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Paket Layanan</th>
                                        <th class="text-center" style="width: 120px;">Kuantitas</th>
                                        <th class="text-end" style="width: 140px;">Tarif Satuan</th>
                                        <th class="text-end" style="width: 150px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ord['items'] as $item): ?>
                                        <tr>
                                            <td><?= sanitize($item['service_name']) ?></td>
                                            <td class="text-center"><?= (float)$item['quantity'] ?> <?= sanitize($item['unit']) ?></td>
                                            <td class="text-end text-muted"><?= format_rupiah($item['unit_price']) ?></td>
                                            <td class="text-end fw-semibold"><?= format_rupiah($item['subtotal']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <th colspan="3" class="text-end">Total Biaya Cucian:</th>
                                        <th class="text-end text-primary fs-6"><?= format_rupiah($ord['total_amount']) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/public_footer.php'; ?>
