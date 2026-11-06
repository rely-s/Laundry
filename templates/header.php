<?php
/**
 * Header Admin Layout
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

require_admin();
$user = current_user();
$pageTitle = $pageTitle ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> - Queen Laundry</title>
    <!-- Favicon Queen Laundry -->
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-navy: #1E3A8A;
            --primary-navy-dark: #172554;
            --accent-teal: #0D9488;
            --accent-teal-light: #CCFBF1;
            --bg-slate: #F8FAFC;
            --sidebar-width: 260px;
        }
        body {
            background-color: var(--bg-slate);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1E293B;
            min-height: 100vh;
        }
        /* Sidebar styling */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-navy) 0%, var(--primary-navy-dark) 100%);
            min-height: 100vh;
            color: #E2E8F0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1020;
            transition: transform 0.3s ease-in-out;
        }
        .admin-sidebar .brand-box {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .admin-sidebar .brand-title {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
            color: #FFFFFF;
        }
        .admin-sidebar .nav-link {
            color: #CBD5E1;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            margin: 0.2rem 0.75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
        }
        .admin-sidebar .nav-link:hover {
            color: #FFFFFF;
            background-color: rgba(255, 255, 255, 0.12);
        }
        .admin-sidebar .nav-link.active {
            color: #FFFFFF;
            background-color: var(--accent-teal);
            font-weight: 600;
            box-shadow: 0 4px 6px -1px rgba(13, 148, 136, 0.3);
        }
        /* Main wrapper */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease-in-out;
        }
        .admin-topbar {
            background-color: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1010;
        }
        .content-area {
            padding: 1.75rem;
            flex: 1;
        }
        .card {
            border: 1px solid #E2E8F0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            background-color: #FFFFFF;
        }
        .card-header {
            background-color: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        .btn-primary {
            background-color: var(--primary-navy);
            border-color: var(--primary-navy);
        }
        .btn-primary:hover {
            background-color: var(--primary-navy-dark);
            border-color: var(--primary-navy-dark);
        }
        .btn-teal {
            background-color: var(--accent-teal);
            border-color: var(--accent-teal);
            color: #FFFFFF;
        }
        .btn-teal:hover {
            background-color: #0F766E;
            border-color: #0F766E;
            color: #FFFFFF;
        }
        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 0;
            }
        }
        @media print {
            .admin-sidebar, .admin-topbar, .no-print, .btn, form.filter-form {
                display: none !important;
            }
            .main-wrapper {
                margin-left: 0 !important;
            }
            .content-area {
                padding: 0 !important;
            }
            body {
                background-color: #FFFFFF !important;
            }
            .card {
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar Navigasi -->
<?php require_once __DIR__ . '/sidebar.php'; ?>

<!-- Konten Utama -->
<div class="main-wrapper">
    <!-- Topbar -->
    <header class="admin-topbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" id="sidebarToggle" aria-label="Toggle Navigation">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h5 class="mb-0 fw-semibold text-secondary d-none d-sm-block"><?= sanitize($pageTitle) ?></h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="/tracking.php" target="_blank" class="btn btn-sm btn-outline-info d-none d-md-inline-flex align-items-center gap-1">
                <i class="bi bi-search"></i> Lacak Cucian
            </a>
            <div class="dropdown">
                <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle text-primary"></i>
                    <span class="fw-medium"><?= sanitize($user['name'] ?? 'Admin') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="/admin/profile.php"><i class="bi bi-shield-lock me-2"></i> Ganti Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="/logout.php" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Area Konten Halaman -->
    <main class="content-area">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>

