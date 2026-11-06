<?php
/**
 * Header Halaman Publik
 * Sistem Informasi Queen Laundry
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = $pageTitle ?? 'Queen Laundry';
$isLoggedIn = is_authenticated();
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
            --bg-slate: #F8FAFC;
        }
        body {
            background-color: var(--bg-slate);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1E293B;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-queen {
            background: linear-gradient(135deg, var(--primary-navy) 0%, var(--primary-navy-dark) 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        .card-custom {
            border: 1px solid #E2E8F0;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            background-color: #FFFFFF;
        }
        /* Stepper untuk tracking */
        .stepper-wrapper {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 2rem 0 1rem;
        }
        .stepper-item {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
        .stepper-item::before {
            position: absolute;
            content: "";
            border-bottom: 2px solid #CBD5E1;
            width: 100%;
            top: 20px;
            left: -50%;
            z-index: 1;
        }
        .stepper-item:first-child::before {
            content: none;
        }
        .stepper-item .step-counter {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #E2E8F0;
            color: #64748B;
            font-weight: 700;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        .stepper-item.active .step-counter {
            background: var(--accent-teal);
            color: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.2);
        }
        .stepper-item.completed .step-counter {
            background: #10B981;
            color: #FFFFFF;
        }
        .stepper-item.completed::before {
            border-bottom-color: #10B981;
        }
        .step-name {
            font-size: 0.825rem;
            font-weight: 600;
            color: #475569;
            text-align: center;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-queen py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="/index.php">
            <i class="bi bi-droplet-half text-info fs-4"></i>
            <span>Queen Laundry</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-2">
                <li class="nav-item">
                    <a class="nav-link <?= ($_SERVER['PHP_SELF'] ?? '') === '/index.php' ? 'active fw-semibold' : '' ?>" href="/index.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($_SERVER['PHP_SELF'] ?? '') === '/tracking.php' ? 'active fw-semibold' : '' ?>" href="/tracking.php">
                        <i class="bi bi-search me-1"></i> Lacak Cucian
                    </a>
                </li>
                <li class="nav-item ms-lg-2">
                    <?php if ($isLoggedIn): ?>
                        <a href="/admin/index.php" class="btn btn-teal btn-sm px-3 rounded-pill">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard Admin
                        </a>
                    <?php else: ?>
                        <a href="/login.php" class="btn btn-outline-light btn-sm px-3 rounded-pill">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login Admin
                        </a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
    <div class="container py-3">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>

