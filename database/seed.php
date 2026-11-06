<?php
/**
 * Skrip Seeder Basis Data Queen Laundry
 * Mengisi akun admin awal, data master paket layanan, pelanggan contoh, dan transaksi awal.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = get_db();
    $pdo->beginTransaction();

    // 1. Akun Admin Default
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@queenlaundry.test']);
    $admin = $stmt->fetch();

    if (!$admin) {
        $insertAdmin = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role, created_at, updated_at)
            VALUES (?, ?, ?, 'admin', NOW(), NOW())
        ");
        $insertAdmin->execute([
            'Admin Queen Laundry',
            'admin@queenlaundry.test',
            password_hash('admin123', PASSWORD_BCRYPT),
        ]);
        $adminId = $pdo->lastInsertId();
        echo " [+] User Admin dibuat: admin@queenlaundry.test / admin123\n";
    } else {
        $adminId = $admin['id'];
        echo " [i] User Admin sudah ada (ID: {$adminId})\n";
    }

    // 2. Paket Layanan Default
    $services = [
        ['name' => 'Cuci Setrika Reguler', 'unit' => 'kg',   'price' => 7000.00,  'is_active' => 1],
        ['name' => 'Cuci Kering Saja',     'unit' => 'kg',   'price' => 5000.00,  'is_active' => 1],
        ['name' => 'Setrika Saja',         'unit' => 'kg',   'price' => 4000.00,  'is_active' => 1],
        ['name' => 'Bedcover Besar',       'unit' => 'item', 'price' => 25000.00, 'is_active' => 1],
        ['name' => 'Selimut Tebal',        'unit' => 'item', 'price' => 18000.00, 'is_active' => 1],
    ];

    $checkSvc = $pdo->prepare("SELECT id FROM services WHERE name = ?");
    $insertSvc = $pdo->prepare("
        INSERT INTO services (name, unit, price, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");

    $serviceIds = [];
    foreach ($services as $svc) {
        $checkSvc->execute([$svc['name']]);
        $exist = $checkSvc->fetch();
        if (!$exist) {
            $insertSvc->execute([$svc['name'], $svc['unit'], $svc['price'], $svc['is_active']]);
            $serviceIds[$svc['name']] = $pdo->lastInsertId();
            echo " [+] Layanan dibuat: {$svc['name']} ({$svc['unit']} @ Rp " . number_format($svc['price'], 0, ',', '.') . ")\n";
        } else {
            $serviceIds[$svc['name']] = $exist['id'];
        }
    }

    // 3. Pelanggan Awal
    $customers = [
        ['name' => 'Siti Rahma',    'phone' => '081234567890', 'address' => 'Jl. Kaliurang KM 5, Sleman'],
        ['name' => 'Budi Santoso',  'phone' => '085678901234', 'address' => 'Jl. Palagan Tentara Pelajar No. 18, Sleman'],
        ['name' => 'Dewi Lestari',  'phone' => '087890123456', 'address' => 'Perum Griya Indah Blok B4, Sleman'],
    ];

    $checkCust = $pdo->prepare("SELECT id FROM customers WHERE name = ? AND phone = ?");
    $insertCust = $pdo->prepare("
        INSERT INTO customers (name, phone, address, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ");

    $customerIds = [];
    foreach ($customers as $c) {
        $checkCust->execute([$c['name'], $c['phone']]);
        $exist = $checkCust->fetch();
        if (!$exist) {
            $insertCust->execute([$c['name'], $c['phone'], $c['address']]);
            $customerIds[] = $pdo->lastInsertId();
            echo " [+] Pelanggan dibuat: {$c['name']} ({$c['phone']})\n";
        } else {
            $customerIds[] = $exist['id'];
        }
    }

    // 4. Contoh Transaksi Awal (Untuk demo dan pengujian)
    $orderCode = 'QL-' . date('Ym') . '-0001';
    $checkOrder = $pdo->prepare("SELECT id FROM orders WHERE order_code = ?");
    $checkOrder->execute([$orderCode]);

    if (!$checkOrder->fetch() && !empty($customerIds) && !empty($serviceIds)) {
        $tokenPlain = 'QL7X9K';
        $tokenHash  = hash('sha256', $tokenPlain);

        $insertOrder = $pdo->prepare("
            INSERT INTO orders (order_code, customer_id, created_by, tracking_token_hash, tracking_token_plain, received_at, estimated_at, status, total_amount, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 2 DAY), 'dicuci', 39000.00, 'Pewangi lavender, pisahkan pakaian putih', NOW(), NOW())
        ");
        $insertOrder->execute([
            $orderCode,
            $customerIds[0],
            $adminId,
            $tokenHash,
            $tokenPlain,
        ]);
        $orderId = $pdo->lastInsertId();

        // Items: 3 kg Cuci Setrika (Rp 21.000) + 1 Selimut Tebal (Rp 18.000) = Rp 39.000
        $insertItem = $pdo->prepare("
            INSERT INTO order_items (order_id, service_id, quantity, unit_price, subtotal, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $insertItem->execute([$orderId, $serviceIds['Cuci Setrika Reguler'], 3.00, 7000.00, 21000.00]);
        $insertItem->execute([$orderId, $serviceIds['Selimut Tebal'], 1.00, 18000.00, 18000.00]);

        // Status History
        $insertHistory = $pdo->prepare("
            INSERT INTO order_status_histories (order_id, changed_by, from_status, to_status, changed_at, note, created_at)
            VALUES (?, ?, ?, ?, NOW(), ?, NOW())
        ");
        $insertHistory->execute([$orderId, $adminId, null, 'diterima', 'Pesanan masuk']);
        $insertHistory->execute([$orderId, $adminId, 'diterima', 'dicuci', 'Pakaian mulai masuk mesin cuci']);

        // Payment (Lunas Rp 39.000 tunai)
        $insertPay = $pdo->prepare("
            INSERT INTO payments (order_id, recorded_by, amount, method, paid_at, reference_no, created_at, updated_at)
            VALUES (?, ?, 39000.00, 'tunai', NOW(), 'KW-DEMO-001', NOW(), NOW())
        ");
        $insertPay->execute([$orderId, $adminId]);

        echo " [+] Contoh Transaksi dibuat: {$orderCode} (Total: Rp 39.000, Token: {$tokenPlain})\n";
    }

    $pdo->commit();
    echo "[SUKSES] Seeding data awal Queen Laundry berhasil diselesaikan.\n";
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "[GAGAL] Error saat seeding: " . $e->getMessage() . "\n";
    exit(1);
}

