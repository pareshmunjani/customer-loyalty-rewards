
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? APP_NAME;

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? null;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?></title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=20261124">
</head>

<body>

<nav class="navbar navbar-expand-lg site-navbar">

    <div class="container">

        <a class="navbar-brand site-brand" href="<?= e(url()) ?>">
            <span class="brand-icon">★</span>
            <span>
                Loyalty<span class="brand-highlight">Rewards</span>
                <small>EVERY PURCHASE MATTERS</small>
            </span>
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">

                <?php if (!$isLoggedIn): ?>

                    <li class="nav-item">
                        <a class="nav-link nav-login"
                           href="<?= e(url('customer/login.php')) ?>">
                            Customer Login
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link nav-register"
                           href="<?= e(url('customer/register.php')) ?>">
                            Create Account <span aria-hidden="true">→</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link nav-admin"
                           href="<?= e(url('admin/login.php')) ?>">
                            Admin Portal
                        </a>
                    </li>

                <?php elseif ($userRole === 'customer'): ?>

                    <li class="nav-item">
                        <a class="nav-link nav-dashboard"
                           href="<?= e(url('customer/dashboard.php')) ?>">
                            My Dashboard
                        </a>
                    </li>

                <?php elseif ($userRole === 'admin'): ?>

                    <li class="nav-item">
                        <a class="nav-link nav-dashboard"
                           href="<?= e(url('admin/dashboard.php')) ?>">
                            Admin Dashboard
                        </a>
                    </li>

                <?php endif; ?>

            </ul>

        </div>

    </div>

</nav>

<main>
