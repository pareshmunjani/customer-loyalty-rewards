
<?php

require_once __DIR__ . '/../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/csrf.php';
require_once __DIR__ . '/../../app/config/database.php';

requireAdmin();

$pdo = getPDO();

$stats = [
    'customers' => 0,
    'active_products' => 0,
    'active_rewards' => 0,
    'total_codes' => 0,
    'redeemed_codes' => 0,
    'unused_codes' => 0,
    'points_issued' => 0,
    'points_redeemed' => 0
];

$errorMessage = '';

try {
    $stats['customers'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'customer'"
    )->fetchColumn();

    $stats['active_products'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM products WHERE status = 'active'"
    )->fetchColumn();

    $stats['active_rewards'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM rewards WHERE status = 'active'"
    )->fetchColumn();

    $stats['total_codes'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM reward_codes"
    )->fetchColumn();

    $stats['redeemed_codes'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM reward_codes WHERE status = 'redeemed'"
    )->fetchColumn();

    $stats['unused_codes'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM reward_codes WHERE status = 'unused'"
    )->fetchColumn();

    $stats['points_issued'] = (int) $pdo->query(
        "SELECT COALESCE(SUM(points_delta), 0)
         FROM point_transactions
         WHERE transaction_type = 'earn'"
    )->fetchColumn();

    $stats['points_redeemed'] = (int) $pdo->query(
        "SELECT COALESCE(SUM(points_delta), 0)
         FROM point_transactions
         WHERE transaction_type = 'redeem'"
    )->fetchColumn();

} catch (PDOException $exception) {
    error_log('Admin dashboard error: ' . $exception->getMessage());
    $errorMessage = 'Unable to load dashboard statistics. Please try again.';
}

$adminName = $_SESSION['user_name'] ?? 'Administrator';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Customer Loyalty Rewards</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css?v=20261124">

    <style>
        /* Admin dashboard */

        .admin-navbar {
            min-height: 82px;
            background: rgba(255, 255, 255, 0.98);
            border-bottom: 1px solid #e8edf5;
            box-shadow: 0 4px 20px rgba(30, 41, 59, 0.04);
        }

        .admin-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #253b80;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.6px;
            text-decoration: none;
        }

        .admin-brand:hover {
            color: #253b80;
        }

        .admin-brand-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 43px;
            height: 43px;
            border-radius: 13px;
            background: linear-gradient(135deg, #253b80, #4169e1);
            color: #ffffff;
            font-size: 1.4rem;
            box-shadow: 0 5px 12px rgba(37, 59, 128, 0.2);
        }

        .admin-brand small {
            display: block;
            margin-top: 5px;
            color: #94a3b8;
            font-size: 0.59rem;
            font-weight: 700;
            letter-spacing: 1.6px;
        }

        .admin-welcome {
            color: #64748b;
            font-size: 0.88rem;
        }

        .admin-welcome strong {
            color: #253b80;
        }

        .admin-logout {
            border-radius: 9px;
            font-weight: 600;
            padding: 8px 16px;
        }

        .admin-main {
            padding-top: 42px;
            padding-bottom: 55px;
        }

        .admin-page-title {
            color: #172b61;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .admin-page-description {
            color: #64748b;
            font-size: 0.95rem;
        }

        .admin-section-title {
            color: #253b80;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.3px;
            margin-bottom: 18px;
        }

        /* Statistics cards */

        .admin-stat-card {
            position: relative;
            overflow: hidden;
            height: 100%;
            padding: 23px;
            background: #ffffff;
            border: 1px solid #e8edf5;
            border-radius: 17px;
            box-shadow: 0 8px 25px rgba(37, 59, 128, 0.045);
            transition: all 0.22s ease;
        }

        .admin-stat-card:hover {
            transform: translateY(-4px);
            border-color: #d2ddfa;
            box-shadow: 0 14px 32px rgba(37, 59, 128, 0.09);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.84rem;
            font-weight: 600;
            margin: 0;
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 43px;
            height: 43px;
            flex-shrink: 0;
            border-radius: 13px;
            background: #eef2ff;
            color: #4169e1;
            font-size: 1.25rem;
            font-weight: 800;
        }

        .stat-value {
            color: #172b61;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1.2;
            margin: 0;
        }

        .stat-blue .stat-icon {
            background: #eef2ff;
            color: #4169e1;
        }

        .stat-green .stat-icon {
            background: #e9f8f1;
            color: #15966b;
        }

        .stat-red .stat-icon {
            background: #fff0f1;
            color: #dc3545;
        }

        .stat-green .stat-value {
            color: #15966b;
        }

        .stat-red .stat-value {
            color: #dc3545;
        }

        /* Management cards */

        .admin-module-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 25px;
            background: #ffffff;
            border: 1px solid #e8edf5;
            border-radius: 17px;
            box-shadow: 0 8px 25px rgba(37, 59, 128, 0.045);
            transition: all 0.22s ease;
        }

        .admin-module-card:hover {
            transform: translateY(-5px);
            border-color: #cbd9ff;
            box-shadow: 0 15px 35px rgba(37, 59, 128, 0.09);
        }

        .module-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 49px;
            height: 49px;
            margin-bottom: 19px;
            border-radius: 14px;
            background: #eef2ff;
            color: #253b80;
            font-size: 1.4rem;
        }

        .admin-module-card h5 {
            color: #253b80;
            font-size: 1.02rem;
            font-weight: 800;
            margin-bottom: 9px;
        }

        .admin-module-card p {
            flex-grow: 1;
            color: #64748b;
            font-size: 0.86rem;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .module-button {
            align-self: flex-start;
            border-radius: 9px;
            padding: 9px 16px;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .admin-back-link {
            color: #4169e1;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .admin-back-link:hover {
            color: #253b80;
        }

        /* Responsive */

        @media (max-width: 767px) {

            .admin-navbar {
                padding-top: 13px;
                padding-bottom: 13px;
            }

            .admin-brand {
                font-size: 1.05rem;
            }

            .admin-brand-icon {
                width: 39px;
                height: 39px;
            }

            .admin-welcome {
                font-size: 0.76rem;
            }

            .admin-main {
                padding-top: 30px;
            }

            .admin-page-title {
                font-size: 1.65rem;
            }

            .admin-stat-card {
                padding: 19px;
            }

            .admin-module-card {
                padding: 22px;
            }
        }

        @media (max-width: 480px) {

            .admin-navbar .container {
                align-items: flex-start !important;
                gap: 12px;
            }

            .admin-navbar-right {
                align-items: flex-end !important;
                flex-direction: column;
                gap: 8px !important;
            }

            .admin-welcome {
                text-align: right;
            }
        }
    </style>
</head>

<body>

<header class="admin-navbar">
    <div class="container d-flex justify-content-between align-items-center">

        <a href="../index.php" class="admin-brand">

            <span class="admin-brand-icon">★</span>

            <span>
                Loyalty<span style="color: #4169e1;">Rewards</span>
                <small>ADMINISTRATION PANEL</small>
            </span>

        </a>

        <div class="admin-navbar-right d-flex align-items-center gap-3">

            <div class="admin-welcome">
                Welcome,
                <strong><?= e($adminName) ?></strong>
            </div>

            <form action="logout.php" method="POST" class="m-0">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(generateCsrfToken()) ?>"
                >

                <button
                    type="submit"
                    class="btn btn-outline-danger btn-sm admin-logout"
                >
                    Logout
                </button>

            </form>

        </div>

    </div>
</header>

<main class="container admin-main">

    <!-- Page heading -->

    <div class="mb-5">

        <div class="mb-2">
            <span class="badge rounded-pill px-3 py-2"
                  style="background: #eaf0ff; color: #4169e1;">
                ADMINISTRATION
            </span>
        </div>

        <h1 class="admin-page-title mb-2">
            Admin Dashboard
        </h1>

        <p class="admin-page-description mb-0">
            Manage your loyalty program and monitor system performance.
        </p>

    </div>

    <?php if ($errorMessage !== ''): ?>

        <div class="alert alert-danger">
            <?= e($errorMessage) ?>
        </div>

    <?php endif; ?>


    <!-- System Overview -->

    <section class="mb-5">

        <h2 class="admin-section-title">
            System Overview
        </h2>

        <div class="row g-3">

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="admin-stat-card stat-blue">

                    <div class="stat-top">
                        <p class="stat-label">Registered Customers</p>
                        <span class="stat-icon">♙</span>
                    </div>

                    <p class="stat-value">
                        <?= number_format($stats['customers']) ?>
                    </p>

                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="admin-stat-card stat-blue">

                    <div class="stat-top">
                        <p class="stat-label">Active Products</p>
                        <span class="stat-icon">▣</span>
                    </div>

                    <p class="stat-value">
                        <?= number_format($stats['active_products']) ?>
                    </p>

                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="admin-stat-card stat-blue">

                    <div class="stat-top">
                        <p class="stat-label">Active Rewards</p>
                        <span class="stat-icon">★</span>
                    </div>

                    <p class="stat-value">
                        <?= number_format($stats['active_rewards']) ?>
                    </p>

                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <div class="admin-stat-card stat-blue">

                    <div class="stat-top">
                        <p class="stat-label">Total Reward Codes</p>
                        <span class="stat-icon">⌑</span>
                    </div>

                    <p class="stat-value">
                        <?= number_format($stats['total_codes']) ?>
                    </p>

                </div>
            </div>

        </div>

    </section>


    <!-- Reward Code Statistics -->

    <section class="mb-5">

        <h2 class="admin-section-title">
            Reward Code Statistics
        </h2>

        <div class="row g-3">

            <div class="col-12 col-md-4">
                <div class="admin-stat-card stat-blue">

                    <div class="stat-top">
                        <p class="stat-label">Generated Codes</p>
                        <span class="stat-icon">▤</span>
                    </div>

                    <p class="stat-value">
                        <?= number_format($stats['total_codes']) ?>
                    </p>

                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="admin-stat-card stat-green">

                    <div class="stat-top">
                        <p class="stat-label">Redeemed Codes</p>
                        <span class="stat-icon">✓</span>
                    </div>

                    <p class="stat-value">
                        <?= number_format($stats['redeemed_codes']) ?>
                    </p>

                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="admin-stat-card stat-blue">

                    <div class="stat-top">
                        <p class="stat-label">Unused Codes</p>
                        <span class="stat-icon">◷</span>
                    </div>

                    <p class="stat-value">
                        <?= number_format($stats['unused_codes']) ?>
                    </p>

                </div>
            </div>

        </div>

    </section>


    <!-- Points Overview -->

    <section class="mb-5">

        <h2 class="admin-section-title">
            Points Overview
        </h2>

        <div class="row g-3">

            <div class="col-12 col-md-6">
                <div class="admin-stat-card stat-green">

                    <div class="stat-top">
                        <p class="stat-label">
                            Total Points Issued to Customers
                        </p>
                        <span class="stat-icon">↗</span>
                    </div>

                    <p class="stat-value">
                        <?= number_format($stats['points_issued']) ?>
                    </p>

                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="admin-stat-card stat-red">

                    <div class="stat-top">
                        <p class="stat-label">
                            Total Points Redeemed by Customers
                        </p>
                        <span class="stat-icon">↘</span>
                    </div>

                    <p class="stat-value">
                        <?= number_format($stats['points_redeemed']) ?>
                    </p>

                </div>
            </div>

        </div>

    </section>


    <!-- Management Modules -->

    <section>

        <h2 class="admin-section-title">
            Management Modules
        </h2>

        <div class="row g-3">

            <div class="col-12 col-md-6 col-lg-4">
                <div class="admin-module-card">

                    <div class="module-icon">▣</div>

                    <h5>Product Management</h5>

                    <p>
                        Add, edit, view and manage factory products and
                        their points per box.
                    </p>

                    <a href="products/index.php"
                       class="btn btn-primary module-button">
                        Manage Products →
                    </a>

                </div>
            </div>


            <div class="col-12 col-md-6 col-lg-4">
                <div class="admin-module-card">

                    <div class="module-icon">★</div>

                    <h5>Reward Management</h5>

                    <p>
                        Create and manage rewards that customers can
                        redeem using their loyalty points.
                    </p>

                    <a href="rewards/index.php"
                       class="btn btn-primary module-button">
                        Manage Rewards →
                    </a>

                </div>
            </div>


            <div class="col-12 col-md-6 col-lg-4">
                <div class="admin-module-card">

                    <div class="module-icon">⌑</div>

                    <h5>Reward Code Generation</h5>

                    <p>
                        Generate unique reward codes in bulk for
                        physical product packaging.
                    </p>

                    <a href="codes/index.php"
                       class="btn btn-primary module-button">
                        Generate Codes →
                    </a>

                </div>
            </div>


            <div class="col-12 col-md-6 col-lg-4">
                <div class="admin-module-card">

                    <div class="module-icon">♙</div>

                    <h5>Customer Management</h5>

                    <p>
                        Search registered customers and view their
                        profiles, points and redemption history.
                    </p>

                    <a href="customers/index.php"
                       class="btn btn-primary module-button">
                        Manage Customers →
                    </a>

                </div>
            </div>


            <div class="col-12 col-md-6 col-lg-4">
                <div class="admin-module-card">

                    <div class="module-icon">◷</div>

                    <h5>Activity Monitoring</h5>

                    <p>
                        Monitor successful code redemptions, failed
                        attempts and customer reward activity.
                    </p>

                    <a href="activity/index.php"
                       class="btn btn-primary module-button">
                        Monitor Activities →
                    </a>

                </div>
            </div>

        </div>

    </section>


    <!-- Back to website -->

    <div class="text-center mt-5">

        <a href="../index.php" class="admin-back-link">
            ← Back to Main Website
        </a>

    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
