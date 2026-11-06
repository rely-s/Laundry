<?php
/**
 * Sidebar Admin Layout
 * Sistem Informasi Queen Laundry
 */
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="brand-box d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-droplet-half fs-4 text-info"></i>
            <div>
                <div class="brand-title">Queen Laundry</div>
                <small class="text-white-50" style="font-size: 0.75rem;">Sistem Manajemen</small>
            </div>
        </div>
        <button class="btn-close btn-close-white d-lg-none" type="button" id="sidebarClose" aria-label="Tutup"></button>
    </div>

    <nav class="py-3">
        <div class="px-3 pb-2 text-uppercase text-white-50" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.8px;">Menu Utama</div>
        
        <a href="/admin/index.php" class="nav-link <?= str_starts_with($currentUri, '/admin/index.php') || $currentUri === '/admin/' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <a href="/admin/orders/index.php" class="nav-link <?= str_contains($currentUri, '/admin/orders/') ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i>
            <span>Pesanan Laundry</span>
        </a>

        <a href="/admin/customers/index.php" class="nav-link <?= str_contains($currentUri, '/admin/customers/') ? 'active' : '' ?>">
            <i class="bi bi-people"></i>
            <span>Data Pelanggan</span>
        </a>

        <a href="/admin/services/index.php" class="nav-link <?= str_contains($currentUri, '/admin/services/') ? 'active' : '' ?>">
            <i class="bi bi-tags"></i>
            <span>Layanan & Tarif</span>
        </a>

        <a href="/admin/reports/index.php" class="nav-link <?= str_contains($currentUri, '/admin/reports/') ? 'active' : '' ?>">
            <i class="bi bi-cash-stack"></i>
            <span>Laporan Pendapatan</span>
        </a>

        <div class="px-3 pt-3 pb-2 text-uppercase text-white-50" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.8px;">Pengaturan & Publik</div>

        <a href="/admin/profile.php" class="nav-link <?= str_contains($currentUri, '/admin/profile.php') ? 'active' : '' ?>">
            <i class="bi bi-shield-lock"></i>
            <span>Ganti Password</span>
        </a>

        <a href="/tracking.php" target="_blank" class="nav-link">
            <i class="bi bi-box-arrow-up-right"></i>
            <span>Lacak Cucian (Publik)</span>
        </a>

        <form action="/logout.php" method="POST" class="mt-4 px-3">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-light w-100 btn-sm text-start d-flex align-items-center gap-2">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar (Logout)</span>
            </button>
        </form>
    </nav>
</aside>

