<?php
/**
 * Buat Pesanan Laundry Baru (Multi-Item)
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/order_helper.php';

require_admin();
$pageTitle = 'Buat Pesanan Laundry Baru';

$pdo = get_db();
$user = current_user();

// Ambil data pelanggan aktif
$customersStmt = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name ASC");
$customers = $customersStmt->fetchAll();

// Ambil data layanan aktif
$servicesStmt = $pdo->query("SELECT id, name, unit, price FROM services WHERE is_active = 1 ORDER BY unit ASC, price ASC");
$activeServices = $servicesStmt->fetchAll();

$selectedCustomerId = (int)($_GET['customer_id'] ?? 0);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_abort();

    $customerId   = (int)($_POST['customer_id'] ?? 0);
    $receivedAt   = trim($_POST['received_at'] ?? date('Y-m-d H:i'));
    $estimatedAt  = trim($_POST['estimated_at'] ?? date('Y-m-d H:i', strtotime('+2 days')));
    $notes        = trim($_POST['notes'] ?? '');
    $postedItems  = $_POST['items'] ?? [];

    // Opsi bayar langsung saat input
    $payDirectly  = !empty($_POST['pay_directly']);
    $payAmount    = (float)($_POST['pay_amount'] ?? 0);
    $payMethod    = trim($_POST['pay_method'] ?? 'tunai');

    // 1. Validasi Pelanggan
    if ($customerId <= 0) {
        $error = 'Silakan pilih pelanggan.';
    }
    // 2. Validasi BB06: Pesanan tanpa item ditolak
    elseif (empty($postedItems) || !is_array($postedItems)) {
        $error = 'Pesanan harus memiliki minimal satu item layanan.';
    } else {
        // Validasi dan siapkan item
        $validItems = [];
        $totalAmount = 0.00;

        // Siapkan lookup map layanan untuk snapshot harga
        $servicesMap = [];
        foreach ($activeServices as $s) {
            $servicesMap[$s['id']] = $s;
        }

        foreach ($postedItems as $item) {
            $svcId = (int)($item['service_id'] ?? 0);
            $qty   = (float)($item['quantity'] ?? 0);

            if ($svcId <= 0 || !isset($servicesMap[$svcId])) {
                continue;
            }
            if ($qty <= 0) {
                $error = 'Kuantitas setiap layanan harus lebih besar dari 0.';
                break;
            }

            // Snapshot harga satuan saat ini (BB12)
            $unitPrice = (float)$servicesMap[$svcId]['price'];
            $subtotal  = round($qty * $unitPrice, 2);

            $validItems[] = [
                'service_id' => $svcId,
                'quantity'   => $qty,
                'unit_price' => $unitPrice,
                'subtotal'   => $subtotal,
            ];

            $totalAmount += $subtotal;
        }

        if (!$error && empty($validItems)) {
            $error = 'Pesanan harus memiliki minimal satu item layanan yang valid.';
        }

        if (!$error) {
            try {
                $pdo->beginTransaction();

                // Generate kode nota & token pelacakan
                $orderCode  = generate_order_code($pdo);
                $tokenPlain = generate_tracking_token();
                $tokenHash  = hash('sha256', $tokenPlain);

                // Insert Header Pesanan
                $insertOrder = $pdo->prepare("
                    INSERT INTO orders (
                        order_code, customer_id, created_by, tracking_token_hash, tracking_token_plain,
                        received_at, estimated_at, status, total_amount, notes, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'diterima', ?, ?, NOW(), NOW())
                ");
                $insertOrder->execute([
                    $orderCode,
                    $customerId,
                    $user['id'],
                    $tokenHash,
                    $tokenPlain,
                    $receivedAt,
                    !empty($estimatedAt) ? $estimatedAt : null,
                    $totalAmount,
                    !empty($notes) ? $notes : null,
                ]);
                $orderId = (int)$pdo->lastInsertId();

                // Insert Items dengan snapshot harga (BB05, BB12)
                $insertItem = $pdo->prepare("
                    INSERT INTO order_items (order_id, service_id, quantity, unit_price, subtotal, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ");
                foreach ($validItems as $vItem) {
                    $insertItem->execute([
                        $orderId,
                        $vItem['service_id'],
                        $vItem['quantity'],
                        $vItem['unit_price'],
                        $vItem['subtotal'],
                    ]);
                }

                // Insert Histori Status Awal (BB07)
                $insertHist = $pdo->prepare("
                    INSERT INTO order_status_histories (order_id, changed_by, from_status, to_status, changed_at, note, created_at)
                    VALUES (?, ?, NULL, 'diterima', NOW(), 'Pesanan baru dibuat', NOW())
                ");
                $insertHist->execute([$orderId, $user['id']]);

                // Jika ada pembayaran di muka
                if ($payDirectly && $payAmount > 0) {
                    if ($payAmount > $totalAmount) {
                        throw new InvalidArgumentException('Nominal pembayaran tidak boleh melebihi total tagihan.');
                    }
                    $insertPay = $pdo->prepare("
                        INSERT INTO payments (order_id, recorded_by, amount, method, paid_at, reference_no, created_at, updated_at)
                        VALUES (?, ?, ?, ?, NOW(), 'BAYAR-DI-MUKA', NOW(), NOW())
                    ");
                    $insertPay->execute([$orderId, $user['id'], $payAmount, in_array($payMethod, ['tunai', 'transfer']) ? $payMethod : 'tunai']);
                }

                $pdo->commit();

                set_flash('success', "Pesanan {$orderCode} berhasil dibuat! Token Lacak: {$tokenPlain}");
                redirect('/admin/orders/show.php?id=' . $orderId);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Gagal menyimpan pesanan: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-primary mb-1">Buat Pesanan Laundry</h3>
        <p class="text-muted small mb-0">Pilih pelanggan, tentukan layanan multi-item, dan hitung biaya secara instan.</p>
    </div>
    <a href="/admin/orders/index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= sanitize($error) ?>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/orders/create.php" id="orderForm">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Kolom Informasi Pelanggan & Waktu -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-bold text-dark">
                    <i class="bi bi-person me-2 text-primary"></i>Informasi Pelanggan
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="customer_id" class="form-label small fw-semibold text-secondary mb-0">Pelanggan <span class="text-danger">*</span></label>
                            <a href="/admin/customers/create.php?return_to_order=1" class="small text-decoration-none fw-semibold">
                                <i class="bi bi-plus"></i> Pelanggan Baru
                            </a>
                        </div>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">-- Pilih Pelanggan --</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $selectedCustomerId === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($c['name']) ?> <?= !empty($c['phone']) ? '(' . sanitize($c['phone']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="received_at" class="form-label small fw-semibold text-secondary">Waktu Masuk</label>
                        <input type="datetime-local" class="form-control" id="received_at" name="received_at" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="estimated_at" class="form-label small fw-semibold text-secondary">Estimasi Selesai</label>
                        <input type="datetime-local" class="form-control" id="estimated_at" name="estimated_at" value="<?= date('Y-m-d\TH:i', strtotime('+2 days')) ?>">
                    </div>

                    <div class="mb-0">
                        <label for="notes" class="form-label small fw-semibold text-secondary">Catatan Khusus</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Contoh: Pisahkan pakaian putih, parfum lavender..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Opsi Pembayaran di Muka -->
            <div class="card shadow-sm">
                <div class="card-header fw-bold text-dark">
                    <i class="bi bi-cash-coin me-2 text-success"></i>Pembayaran Awal (Opsional)
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="pay_directly" name="pay_directly" value="1">
                        <label class="form-check-label fw-semibold" for="pay_directly">Pelanggan bayar langsung</label>
                    </div>

                    <div id="paymentFields" style="display: none;">
                        <div class="mb-3">
                            <label for="pay_amount" class="form-label small fw-semibold text-secondary">Nominal Bayar (Rp)</label>
                            <input type="number" step="500" min="0" class="form-control" id="pay_amount" name="pay_amount" value="0">
                        </div>

                        <div class="mb-0">
                            <label for="pay_method" class="form-label small fw-semibold text-secondary">Metode Pembayaran</label>
                            <select class="form-select" id="pay_method" name="pay_method">
                                <option value="tunai">Tunai (Cash)</option>
                                <option value="transfer">Transfer Manual / QRIS</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Item Layanan & Kalkulasi Otomatis -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-cart3 me-2 text-primary"></i>Rincian Layanan Cucian</span>
                    <button type="button" class="btn btn-sm btn-teal rounded-pill" id="addItemBtn">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Baris Layanan
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 240px;">Paket Layanan</th>
                                    <th style="width: 140px;" class="text-center">Kuantitas</th>
                                    <th style="width: 150px;" class="text-end">Tarif Satuan</th>
                                    <th style="width: 160px;" class="text-end">Subtotal</th>
                                    <th style="width: 50px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- Baris item dibuat dinamis oleh JavaScript -->
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end fs-5">TOTAL BIAYA:</th>
                                    <th class="text-end text-primary fs-5" id="totalAmountText">Rp 0</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white p-3 d-flex justify-content-end gap-2">
                    <a href="/admin/orders/index.php" class="btn btn-light border px-4">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold" id="submitOrderBtn">
                        <i class="bi bi-check2-circle me-1"></i> Simpan & Buat Nota
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Data JSON paket layanan untuk client-side JS -->
<script>
    const availableServices = <?= json_encode($activeServices) ?>;
    const itemsBody = document.getElementById('itemsBody');
    const totalAmountText = document.getElementById('totalAmountText');
    const addItemBtn = document.getElementById('addItemBtn');
    const payDirectlyCheckbox = document.getElementById('pay_directly');
    const paymentFields = document.getElementById('paymentFields');
    const payAmountInput = document.getElementById('pay_amount');

    let rowCount = 0;

    function formatRupiahJs(number) {
        return 'Rp ' + Math.round(number).toLocaleString('id-ID');
    }

    function createRow(serviceId = '', quantity = 1) {
        rowCount++;
        const row = document.createElement('tr');
        row.id = 'row_' + rowCount;

        let optionsHtml = '<option value="">-- Pilih Layanan --</option>';
        availableServices.forEach(s => {
            const isSelected = String(s.id) === String(serviceId) ? 'selected' : '';
            optionsHtml += `<option value="${s.id}" data-price="${s.price}" data-unit="${s.unit}" ${isSelected}>
                ${s.name} (${s.unit} @ ${formatRupiahJs(s.price)})
            </option>`;
        });

        row.innerHTML = `
            <td>
                <select class="form-select service-select" name="items[${rowCount}][service_id]" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <div class="input-group">
                    <input type="number" step="0.1" min="0.1" class="form-control text-center qty-input" name="items[${rowCount}][quantity]" value="${quantity}" required>
                    <span class="input-group-text unit-label text-muted small">satuan</span>
                </div>
            </td>
            <td class="text-end">
                <span class="price-display fw-semibold text-muted">Rp 0</span>
            </td>
            <td class="text-end">
                <span class="subtotal-display fw-bold text-dark">Rp 0</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Hapus baris">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        itemsBody.appendChild(row);
        bindRowEvents(row);
        updateRow(row);
    }

    function bindRowEvents(row) {
        const svcSelect = row.querySelector('.service-select');
        const qtyInput  = row.querySelector('.qty-input');
        const removeBtn = row.querySelector('.remove-row-btn');

        svcSelect.addEventListener('change', () => {
            updateRow(row);
            calculateTotal();
        });

        qtyInput.addEventListener('input', () => {
            updateRow(row);
            calculateTotal();
        });

        removeBtn.addEventListener('click', () => {
            if (itemsBody.querySelectorAll('tr').length > 1) {
                row.remove();
                calculateTotal();
            } else {
                alert('Pesanan harus memiliki minimal 1 baris layanan.');
            }
        });
    }

    function updateRow(row) {
        const svcSelect = row.querySelector('.service-select');
        const qtyInput  = row.querySelector('.qty-input');
        const unitLabel = row.querySelector('.unit-label');
        const priceDisp = row.querySelector('.price-display');
        const subtDisp  = row.querySelector('.subtotal-display');

        const selectedOption = svcSelect.options[svcSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            const unit  = selectedOption.getAttribute('data-unit') || '';
            const qty   = parseFloat(qtyInput.value) || 0;
            const subt  = price * qty;

            unitLabel.textContent = unit;
            priceDisp.textContent = formatRupiahJs(price);
            subtDisp.textContent  = formatRupiahJs(subt);
        } else {
            unitLabel.textContent = '-';
            priceDisp.textContent = 'Rp 0';
            subtDisp.textContent  = 'Rp 0';
        }
    }

    function calculateTotal() {
        let grandTotal = 0;
        const rows = itemsBody.querySelectorAll('tr');

        rows.forEach(row => {
            const svcSelect = row.querySelector('.service-select');
            const qtyInput  = row.querySelector('.qty-input');
            const selectedOption = svcSelect.options[svcSelect.selectedIndex];

            if (selectedOption && selectedOption.value) {
                const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                const qty   = parseFloat(qtyInput.value) || 0;
                grandTotal += price * qty;
            }
        });

        totalAmountText.textContent = formatRupiahJs(grandTotal);

        if (payDirectlyCheckbox.checked && payAmountInput.value == 0) {
            payAmountInput.value = grandTotal;
        }
    }

    // Toggle bayar langsung
    payDirectlyCheckbox.addEventListener('change', () => {
        if (payDirectlyCheckbox.checked) {
            paymentFields.style.display = 'block';
            calculateTotal();
        } else {
            paymentFields.style.display = 'none';
        }
    });

    addItemBtn.addEventListener('click', () => {
        createRow();
    });

    // Buat baris pertama saat form dibuka
    document.addEventListener('DOMContentLoaded', () => {
        if (availableServices.length > 0) {
            createRow(availableServices[0].id, 1);
        } else {
            createRow();
        }
    });
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

