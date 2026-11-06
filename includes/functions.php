<?php
/**
 * Fungsi Pembantu Umum (Helpers)
 * Sistem Informasi Queen Laundry
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sanitize(?string $string): string
{
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function format_rupiah($nominal): string
{
    $val = (float)$nominal;
    return 'Rp ' . number_format($val, 0, ',', '.');
}

function format_tanggal(?string $datetime, bool $withTime = true): string
{
    if (empty($datetime)) {
        return '-';
    }

    $timestamp = strtotime($datetime);
    if (!$timestamp) {
        return '-';
    }

    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $hari  = date('d', $timestamp);
    $bulan = $bulanIndo[(int)date('m', $timestamp)];
    $tahun = date('Y', $timestamp);

    $hasil = "{$hari} {$bulan} {$tahun}";
    if ($withTime) {
        $hasil .= ', ' . date('H:i', $timestamp) . ' WIB';
    }

    return $hasil;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type'    => in_array($type, ['success', 'danger', 'warning', 'info']) ? $type : 'info',
        'message' => $message,
    ];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

function redirect(string $path): void
{
    header("Location: " . $path);
    exit;
}

function base_url(string $path = ''): string
{
    $trimmed = ltrim($path, '/');
    return '/' . $trimmed;
}

