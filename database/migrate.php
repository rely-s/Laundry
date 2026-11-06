<?php
/**
 * Skrip Migrasi Basis Data Queen Laundry
 * Menjalankan schema.sql untuk menginisialisasi tabel-tabel database.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = get_db();
    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        throw new RuntimeException("File schema.sql tidak ditemukan di: " . $sqlFile);
    }

    $sql = file_get_contents($sqlFile);
    // Eksekusi multi query
    $pdo->exec($sql);

    echo "[SUKSES] Seluruh tabel database Queen Laundry berhasil dibuat sesuai schema.sql\n";
} catch (Exception $e) {
    echo "[GAGAL] Error saat migrasi: " . $e->getMessage() . "\n";
    exit(1);
}

