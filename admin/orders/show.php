<?php
/**
 * Detail Pesanan Laundry, Timeline Status, dan Pembayaran
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/order_helper.php';

require_admin();
$pageTitle = 'Detail Pesanan Laundry';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID Pesanan tidak valid.');
    redirect('/admin/orders/index.php');
}

$pdo = get_db();

// 1. Ambil Data Header Pesanan & Pelanggan
$stmt = $pdo->prepare("
    SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address,
           u.name AS creator_name
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN users u ON o.created_by = u.id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('danger', 'Pesanan tidak ditemukan.');
    redirect('/admin/orders/index.php');
}

// 2. Ambil Rincian Item Layanan
$stmtItems = $pdo->prepare("
    SELECT oi.*, s.name AS service_name, s.unit
    FROM order_items oi
    JOIN services s ON oi.service_id = s.id
    WHERE oi.order_id = ?
");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

// 3. Ambil Histori Perubahan Status
$stmtHist = $pdo->prepare("
    SELECT h.*, u.name AS changer_name
    FROM order_status_histories h
    LEFT JOIN users u ON h.changed_by = u.id
    WHERE h.order_id = ?
    ORDER BY h.id ASC
");
$stmtHist->execute([$id]);
$histories = $stmtHist->fetchAll();

// 4. Ambil Riwayat Pembayaran
$stmtPay = $pdo->prepare("
    SELECT p.*, u.name AS recorder_name
    FROM payments p
    LEFT JOIN users u ON p.recorded_by = u.id
    WHERE p.order_id = ?
    ORDER BY p.id ASC
");
$stmtPay->execute([$id]);
$payments = $stmtPay->fetchAll();

// 5. Kalkulasi Keuangan
$financials = calculate_order_financials($pdo, $id);
$allowedNext = get_allowed_next_statuses($order['status']);
$statusInfo = get_order_status_info($order['status']);

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold text-primary mb-0"><?= sanitize($order['order_code']) ?></h3>
            <span class="badge bg-<?= sanitize($statusInfo['badge']) ?> rounded-pill px-3 py-2">
                <i class="bi <?= sanitize($statusInfo['icon']) ?> me-1"></i> <?= sanitize($statusInfo['label']) ?>
            </span>
        </div>
        <p class="text-muted small mb-0 mt-1">
            Pelanggan: <strong><?= sanitize($order['customer_name']) ?></strong> | 
            Token Lacak: <span class="badge bg-light text-dark border font-monospace"><?= sanitize($order['tracking_token_plain']) ?></span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/orders/receipt.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-printer me-1"></i> Cetak Nota
        </a>
        <a href="/admin/orders/index.php" class="btn btn-outline-primary rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Detail Cucian & Items -->
    <div class="col-lg-8">
        <!-- Rincian Item Cucian -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-bold text-dark d-flex justify-content-between align-items-center">
                <span><i class="bi bi-basket3 me-2 text-primary"></i>Rincian Layanan Cucian</span>
                <span class="text-muted small">Waktu Masuk: <?= format_tanggal($order['received_at']) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Paket Layanan</th>
                                <th class="text-center" style="width: 140px;">Kuantitas</th>
                                <th class="text-end" style="width: 160px;">Tarif Satuan (Snapshot)</th>
                                <th class="text-end" style="width: 170px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= sanitize($item['service_name']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?= (float)$item['quantity'] ?> <?= sanitize($item['unit']) ?>
                                    </td>
                                    <td class="text-end text-muted">
                                        <?= format_rupiah($item['unit_price']) ?>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        <?= format_rupiah($item['subtotal']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-group-divider">
                            <tr>
                                <th colspan="3" class="text-end fs-5">TOTAL TAGIHAN:</th>
                                <th class="text-end text-primary fs-5"><?= format_rupiah($financials['total_amount']) ?></th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end text-success">Total Pembayaran Masuk:</th>
                                <th class="text-end text-success fs-6"><?= format_rupiah($financials['total_paid']) ?></th>
                            </tr>
                            <tr class="<?= $financials['remaining_balance'] > 0 ? 'table-warning' : 'table-success' ?>">
                                <th colspan="3" class="text-end">Sisa Tagihan:</th>
                                <th class="text-end fw-bold fs-5 <?= $financials['remaining_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= format_rupiah($financials['remaining_balance']) ?>
                                    <?php if ($financials['remaining_balance'] <= 0): ?>
                                        <span class="badge bg-success ms-2">LUNAS</span>
                                    <?php endif; ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php if (!empty($order['notes'])): ?>
                <div class="card-footer bg-light small">
                    <strong>Catatan Khusus:</strong> <?= sanitize($order['notes']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Riwayat Pembayaran Kas -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark"><i class="bi bi-cash-stack me-2 text-success"></i>Riwayat Kas Pembayaran</span>
                <?php if ($financials['remaining_balance'] > 0): ?>
                    <button type="button" class="btn btn-sm btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <i class="bi bi-plus-circle me-1"></i> Catat Pembayaran
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tanggal Bayar</th>
                                <th>Metode</th>
                                <th>No. Referensi</th>
                                <th>Dicatat Oleh</th>
                                <th class="text-end pe-3">Nominal Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada pembayaran yang dicatat untuk pesanan ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $pay): ?>
                                    <tr>
                                        <td class="ps-3"><?= format_tanggal($pay['paid_at']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border text-uppercase">
                                                <?= sanitize($pay['method']) ?>
                                            </span>
                                        </td>
                                        <td><?= sanitize($pay['reference_no'] ?: '-') ?></td>
                                        <td><?= sanitize($pay['recorder_name'] ?: 'Admin') ?></td>
                                        <td class="text-end pe-3 fw-bold text-success"><?= format_rupiah($pay['amount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Jejak Audit Perubahan Status -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-bold text-dark">
                <i class="bi bi-clock-history me-2 text-secondary"></i>Log Perubahan Status (Audit Trail)
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($histories as $h): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <span class="badge bg-secondary-subtle text-dark me-2">
                                    <?= sanitize(ucfirst(str_replace('_', ' ', $h['to_status']))) ?>
                                </span>
                                <?php if (!empty($h['note'])): ?>
                                    <span class="text-dark"><?= sanitize($h['note']) ?></span>
                                <?php endif; ?>
                                <div class="small text-muted mt-1">
                                    Oleh: <strong><?= sanitize($h['changer_name'] ?: 'Admin') ?></strong>
                                </div>
                            </div>
                            <span class="text-muted small"><?= format_tanggal($h['changed_at']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Aksi Status & Info Pelanggan -->
    <div class="col-lg-4">
        <!-- Panel Perbarui Status -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-bold text-dark">
                <i class="bi bi-arrow-repeat me-2 text-primary"></i>Pembaruan Status Cucian
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Status Saat Ini:</small>
                    <span class="badge bg-<?= sanitize($statusInfo['badge']) ?> fs-6 px-3 py-2 mt-1">
                        <i class="bi <?= sanitize($statusInfo['icon']) ?> me-1"></i> <?= sanitize($statusInfo['label']) ?>
                    </span>
                </div>

                <?php if (!empty($allowedNext)): ?>
                    <form method="POST" action="/admin/orders/update_status.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="order_id" value="<?= $id ?>">

                        <div class="mb-3">
                            <label for="to_status" class="form-label small fw-semibold text-secondary">Ubah Status Menjadi:</label>
                            <select class="form-select" id="to_status" name="to_status" required>
                                <?php foreach ($allowedNext as $nextKey): ?>
                                    <?php $nextInfo = get_order_status_info($nextKey); ?>
                                    <option value="<?= $nextKey ?>">
                                        <?= sanitize($nextInfo['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="status_note" class="form-label small fw-semibold text-secondary">Catatan Perubahan (Opsional)</label>
                            <input type="text" class="form-control" id="status_note" name="note" placeholder="Contoh: Selesai disetrika dan dipacking">
                        </div>

                        <button type="submit" class="btn btn-teal w-100 fw-semibold">
                            <i class="bi bi-check-lg me-1"></i> Perbarui Status
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Pesanan ini berada pada status akhir (terminal) dan tidak dapat diubah lagi.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Pelanggan & Estimasi -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-bold text-dark">
                <i class="bi bi-person me-2 text-primary"></i>Informasi Pelanggan
            </div>
            <div class="card-body">
                <h5 class="fw-bold mb-1"><?= sanitize($order['customer_name']) ?></h5>
                <?php if (!empty($order['customer_phone'])): ?>
                    <p class="mb-1 text-muted">
                        <i class="bi bi-whatsapp text-success me-1"></i>
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/\D/', '', $order['customer_phone'])) ?>" target="_blank" class="text-decoration-none">
                            <?= sanitize($order['customer_phone']) ?>
                        </a>
                    </p>
                <?php endif; ?>
                <?php if (!empty($order['customer_address'])): ?>
                    <p class="mb-3 text-muted small"><i class="bi bi-geo-alt me-1"></i><?= sanitize($order['customer_address']) ?></p>
                <?php endif; ?>
                <hr>
                <div class="mb-2">
                    <small class="text-muted d-block">Estimasi Selesai:</small>
                    <strong class="text-primary"><?= format_tanggal($order['estimated_at']) ?></strong>
                </div>
                <div class="mb-0">
                    <small class="text-muted d-block">Petugas Kasir (Pencatat):</small>
                    <span><?= sanitize($order['creator_name']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Catat Pembayaran -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/admin/payments/create.php">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= $id ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="paymentModalLabel">
                        <i class="bi bi-cash-coin me-2 text-success"></i>Catat Pembayaran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        Total Tagihan: <strong><?= format_rupiah($financials['total_amount']) ?></strong><br>
                        Sisa Tagihan: <strong class="text-danger"><?= format_rupiah($financials['remaining_balance']) ?></strong>
                    </div>

                    <div class="mb-3">
                        <label for="modal_amount" class="form-label small fw-semibold text-secondary">Nominal Bayar (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" step="500" min="1" max="<?= $financials['remaining_balance'] ?>" class="form-control" id="modal_amount" name="amount" value="<?= $financials['remaining_balance'] ?>" required>
                        </div>
                        <div class="form-text">Maksimal nominal bayar adalah <?= format_rupiah($financials['remaining_balance']) ?>.</div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_method" class="form-label small fw-semibold text-secondary">Metode Pembayaran <span class="text-danger">*</span></label>
                        <select class="form-select" id="modal_method" name="method" required>
                            <option value="tunai">Tunai (Cash)</option>
                            <option value="transfer">Transfer Manual / QRIS</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label for="modal_ref" class="form-label small fw-semibold text-secondary">No. Referensi / Catatan</label>
                        <input type="text" class="form-control" id="modal_ref" name="reference_no" placeholder="Contoh: Transfer BCA an Budi / Lunas di kasir">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-semibold">
                        <i class="bi bi-check-circle me-1"></i> Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

