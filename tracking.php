<?php
/**
 * Halaman Publik Pelacakan Status Cucian
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/order_helper.php';

$pageTitle = 'Lacak Cucian Pelanggan';

$orderCode   = strtoupper(trim($_GET['kode'] ?? ''));
$token       = strtoupper(trim($_GET['token'] ?? ''));
$searched    = !empty($orderCode) && !empty($token);
$order       = null;
$items       = [];
$histories   = [];
$errorMessage = null;

if ($searched) {
    try {
        $pdo = get_db();
        $tokenHash = hash('sha256', $token);

        $stmt = $pdo->prepare("
            SELECT o.id, o.order_code, o.received_at, o.estimated_at, o.status, o.total_amount, o.notes,
                   c.name AS customer_name
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            WHERE o.order_code = ? AND o.tracking_token_hash = ?
            LIMIT 1
        ");
        $stmt->execute([$orderCode, $tokenHash]);
        $order = $stmt->fetch();

        if ($order) {
            // Rincian Item Layanan
            $stmtItems = $pdo->prepare("
                SELECT oi.quantity, oi.unit_price, oi.subtotal, s.name AS service_name, s.unit
                FROM order_items oi
                JOIN services s ON oi.service_id = s.id
                WHERE oi.order_id = ?
            ");
            $stmtItems->execute([$order['id']]);
            $items = $stmtItems->fetchAll();

            // Histori Status
            $stmtHist = $pdo->prepare("
                SELECT from_status, to_status, changed_at, note
                FROM order_status_histories
                WHERE order_id = ?
                ORDER BY id ASC
            ");
            $stmtHist->execute([$order['id']]);
            $histories = $stmtHist->fetchAll();

            // Status Keuangan
            $fin = calculate_order_financials($pdo, (int)$order['id']);
            $order['financials'] = $fin;
        } else {
            $errorMessage = 'Pesanan dengan kode dan token tersebut tidak ditemukan. Pastikan kode dan token sesuai dengan yang tertera di nota.';
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
            <div class="card card-custom p-4 shadow-sm mb-4">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-primary mb-1">
                        <i class="bi bi-search me-2"></i>Lacak Status Cucian Anda
                    </h3>
                    <p class="text-muted small">
                        Masukkan <strong>Kode Pesanan</strong> dan <strong>Token Akses</strong> 6 karakter yang tertera pada nota fisik Queen Laundry Anda.
                    </p>
                </div>

                <form method="GET" action="/tracking.php" class="row g-3">
                    <div class="col-md-6">
                        <label for="kode" class="form-label small fw-semibold text-secondary">Kode Pesanan</label>
                        <input type="text" class="form-control text-uppercase" id="kode" name="kode" value="<?= sanitize($orderCode) ?>" placeholder="Contoh: QL-202611-0001" required>
                    </div>
                    <div class="col-md-4">
                        <label for="token" class="form-label small fw-semibold text-secondary">Token Akses Nota</label>
                        <input type="text" class="form-control text-uppercase" id="token" name="token" value="<?= sanitize($token) ?>" placeholder="Contoh: QL7X9K" maxlength="10" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-teal w-100 fw-semibold">
                            Lacak
                        </button>
                    </div>
                </form>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger mt-4 mb-0" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= sanitize($errorMessage) ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($order): ?>
                <?php
                    $statusInfo = get_order_status_info($order['status']);
                    $currentStep = $statusInfo['step'];
                ?>
                <div class="card card-custom p-4 shadow-sm mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4 gap-2">
                        <div>
                            <span class="badge bg-primary px-3 py-2 fs-6 rounded-pill">
                                Nota: <?= sanitize($order['order_code']) ?>
                            </span>
                            <div class="text-muted small mt-1">
                                Pelanggan: <strong><?= sanitize($order['customer_name']) ?></strong>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-<?= sanitize($statusInfo['badge']) ?> fs-6 px-3 py-2 rounded-pill">
                                <i class="bi <?= sanitize($statusInfo['icon']) ?> me-1"></i> <?= sanitize($statusInfo['label']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Visual Stepper Progress Bar -->
                    <?php if ($order['status'] !== 'dibatalkan'): ?>
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

                    <!-- Informasi Waktu & Estimasi -->
                    <div class="row g-3 bg-light p-3 rounded-3 mb-4">
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Tanggal Terima:</small>
                            <strong><?= format_tanggal($order['received_at']) ?></strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Estimasi Selesai:</small>
                            <strong class="text-primary"><?= format_tanggal($order['estimated_at']) ?></strong>
                        </div>
                    </div>

                    <!-- Rincian Layanan Cucian -->
                    <h5 class="fw-bold text-dark mb-3">Rincian Cucian</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Layanan</th>
                                    <th class="text-center">Kuantitas</th>
                                    <th class="text-end">Harga Satuan</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><strong><?= sanitize($item['service_name']) ?></strong></td>
                                        <td class="text-center"><?= (float)$item['quantity'] ?> <?= sanitize($item['unit']) ?></td>
                                        <td class="text-end"><?= format_rupiah($item['unit_price']) ?></td>
                                        <td class="text-end fw-semibold"><?= format_rupiah($item['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-group-divider">
                                <tr>
                                    <th colspan="3" class="text-end">Total Biaya:</th>
                                    <th class="text-end text-primary fs-5"><?= format_rupiah($order['total_amount']) ?></th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Status Pembayaran:</th>
                                    <th class="text-end">
                                        <?php if ($order['financials']['payment_status'] === 'lunas'): ?>
                                            <span class="badge bg-success px-2 py-1">LUNAS</span>
                                        <?php elseif ($order['financials']['payment_status'] === 'sebagian'): ?>
                                            <span class="badge bg-warning text-dark px-2 py-1">DIBAYAR SEBAGIAN</span>
                                            <div class="small text-danger mt-1">Sisa: <?= format_rupiah($order['financials']['remaining_balance']) ?></div>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-2 py-1">BELUM LUNAS</span>
                                            <div class="small text-muted mt-1">Bayar saat pengambilan</div>
                                        <?php endif; ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Timeline Histori Perubahan Status -->
                    <h6 class="fw-bold text-secondary mb-3"><i class="bi bi-clock-history me-1"></i> Jejak Status</h6>
                    <ul class="list-group list-group-flush border rounded-3">
                        <?php foreach ($histories as $hist): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <span class="badge bg-secondary-subtle text-dark me-2">
                                        <?= sanitize(ucfirst(str_replace('_', ' ', $hist['to_status']))) ?>
                                    </span>
                                    <small class="text-muted"><?= sanitize($hist['note'] ?: '-') ?></small>
                                </div>
                                <small class="text-muted"><?= format_tanggal($hist['changed_at']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/public_footer.php'; ?>
