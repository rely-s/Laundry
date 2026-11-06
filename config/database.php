<?php
/**
 * Konfigurasi Database dan Koneksi PDO Singleton
 * Sistem Informasi Queen Laundry
 */

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Queen Laundry');
}
if (!defined('APP_URL')) {
    define('APP_URL', 'http://127.0.0.1:8000');
}
date_default_timezone_set('Asia/Jakarta');

function get_db_config(): array
{
    return [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('DB_PORT') ?: '3307',
        'dbname'   => getenv('DB_DATABASE') ?: 'queen_laundry',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
        'charset'  => 'utf8mb4',
    ];
}

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $cfg = get_db_config();
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $options);
        } catch (PDOException $e) {
            // Coba fallback port 3306 jika port 3307 tidak merespons
            if ($cfg['port'] !== '3306') {
                try {
                    $fallbackDsn = "mysql:host={$cfg['host']};port=3306;dbname={$cfg['dbname']};charset={$cfg['charset']}";
                    $pdo = new PDO($fallbackDsn, $cfg['username'], $cfg['password'], $options);
                    return $pdo;
                } catch (PDOException $fallbackException) {
                    // Lanjut throw error asli
                }
            }
            throw new PDOException("Koneksi database gagal: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    return $pdo;
}

