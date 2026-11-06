<?php
/**
 * Cetak Nota Laundry (Struk Resmi)
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/order_helper.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('ID Pesanan tidak valid.');
}

$pdo = get_db();

// Header Pesanan & Pelanggan
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
    die('Pesanan tidak ditemukan.');
}

// Items
$stmtItems = $pdo->prepare("
    SELECT oi.*, s.name AS service_name, s.unit
    FROM order_items oi
    JOIN services s ON oi.service_id = s.id
    WHERE oi.order_id = ?
");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

$financials = calculate_order_financials($pdo, $id);
$statusInfo = get_order_status_info($order['status']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota_<?= sanitize($order['order_code']) ?> - Queen Laundry</title>
    <!-- Favicon Queen Laundry -->
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #F1F5F9;
            font-family: 'Courier New', Courier, monospace, system-ui;
            color: #0F172A;
        }
        .receipt-card {
            max-width: 480px;
            margin: 2rem auto;
            background: #FFFFFF;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border: 1px dashed #CBD5E1;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #94A3B8;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        .receipt-footer {
            text-align: center;
            border-top: 2px dashed #94A3B8;
            padding-top: 1rem;
            margin-top: 1.5rem;
            font-size: 0.85rem;
        }
        .token-box {
            background-color: #F8FAFC;
            border: 2px solid #0D9488;
            border-radius: 6px;
            padding: 0.75rem;
            text-align: center;
            margin: 1rem 0;
        }
        @media print {
            body {
                background: #FFFFFF !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .receipt-card {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 10px !important;
            }
        }
    </style>
</head>
<body>

<div class="container no-print py-3 text-center">
    <button onclick="window.print()" class="btn btn-primary px-4 fw-semibold me-2">
        <i class="bi bi-printer me-1"></i> Cetak Struk
    </button>
    <a href="/admin/orders/show.php?id=<?= $id ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Detail
    </a>
</div>

<div class="receipt-card">
    <div class="receipt-header">
        <h3 class="fw-bold mb-0" style="letter-spacing: 1px;">QUEEN LAUNDRY</h3>
        <p class="mb-1 small">Layanan Cuci, Kering, dan Setrika Terpercaya</p>
        <p class="mb-0 small text-muted">Jl. Kaliurang KM 5, Sleman | Telp/WA: 0812-3456-7890</p>
    </div>

    <!-- Informasi Nota & Pelanggan -->
    <div class="d-flex justify-content-between mb-1 small">
        <span>No. Nota:</span>
        <strong class="font-monospace fs-6"><?= sanitize($order['order_code']) ?></strong>
    </div>
    <div class="d-flex justify-content-between mb-1 small">
        <span>Tanggal:</span>
        <span><?= format_tanggal($order['received_at']) ?></span>
    </div>
    <div class="d-flex justify-content-between mb-1 small">
        <span>Pelanggan:</span>
        <strong><?= sanitize($order['customer_name']) ?></strong>
    </div>
    <?php if (!empty($order['customer_phone'])): ?>
        <div class="d-flex justify-content-between mb-1 small">
            <span>Kontak:</span>
            <span><?= sanitize($order['customer_phone']) ?></span>
        </div>
    <?php endif; ?>
    <div class="d-flex justify-content-between mb-2 small">
        <span>Est. Selesai:</span>
        <strong><?= format_tanggal($order['estimated_at']) ?></strong>
    </div>

    <!-- Box Token Pelacakan Mandiri -->
    <div class="token-box">
        <div class="small fw-semibold text-secondary text-uppercase" style="letter-spacing: 0.5px;">Kode Akses Lacak Cucian</div>
        <div class="display-6 fw-bold font-monospace text-teal" style="color: #0D9488; letter-spacing: 3px;">
            <?= sanitize($order['tracking_token_plain']) ?>
        </div>
        <div class="small text-muted" style="font-size: 0.75rem;">
            Pantau status cucian di: <strong>queenlaundry.test/tracking.php</strong>
        </div>
    </div>

    <!-- Tabel Rincian Layanan -->
    <table class="table table-sm table-borderless small mb-2">
        <thead style="border-bottom: 1px dashed #000;">
            <tr>
                <th>Layanan</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Harga</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= sanitize($item['service_name']) ?></td>
                    <td class="text-center"><?= (float)$item['quantity'] ?> <?= sanitize($item['unit']) ?></td>
                    <td class="text-end"><?= number_format($item['unit_price'], 0, ',', '.') ?></td>
                    <td class="text-end"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot style="border-top: 1px dashed #000;">
            <tr>
                <th colspan="3" class="text-end pt-2">TOTAL BIAYA:</th>
                <th class="text-end pt-2 fs-6"><?= format_rupiah($financials['total_amount']) ?></th>
            </tr>
            <tr>
                <th colspan="3" class="text-end">Sudah Dibayar:</th>
                <th class="text-end text-success"><?= format_rupiah($financials['total_paid']) ?></th>
            </tr>
            <tr>
                <th colspan="3" class="text-end">SISA TAGIHAN:</th>
                <th class="text-end fs-6 <?= $financials['remaining_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                    <?= format_rupiah($financials['remaining_balance']) ?>
                </th>
            </tr>
            <tr>
                <th colspan="3" class="text-end">Status:</th>
                <th class="text-end">
                    <?php if ($financials['payment_status'] === 'lunas'): ?>
                        <strong class="text-success">[LUNAS]</strong>
                    <?php else: ?>
                        <strong class="text-danger">[BELUM LUNAS]</strong>
                    <?php endif; ?>
                </th>
            </tr>
        </tfoot>
    </table>

    <?php if (!empty($order['notes'])): ?>
        <div class="small text-muted p-2 bg-light rounded mb-2">
            <strong>Catatan:</strong> <?= sanitize($order['notes']) ?>
        </div>
    <?php endif; ?>

    <div class="receipt-footer">
        <p class="mb-1">Terima kasih atas kepercayaan Anda di Queen Laundry!</p>
        <p class="mb-1 text-muted" style="font-size: 0.75rem;">Barang yang tidak diambil lebih dari 30 hari di luar tanggung jawab kami.</p>
        <p class="mb-0 text-muted" style="font-size: 0.75rem;">Kasir: <?= sanitize($order['creator_name']) ?></p>
    </div>
</div>

<?php if (isset($_GET['autoprint']) && $_GET['autoprint'] === '1'): ?>
<script>
    window.addEventListener('load', () => {
        window.print();
    });
</script>
<?php endif; ?>

</body>
</html>

