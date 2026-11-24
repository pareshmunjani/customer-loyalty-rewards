
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background-color: #f5f7fb;
        }

        .dashboard-header {
            background: #ffffff;
            border-bottom: 1px solid #e9ecef;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 22px;
            height: 100%;
        }

        .stat-label {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            color: #212529;
        }

        .module-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 22px;
            height: 100%;
        }

        .module-card h5 {
            font-size: 17px;
            margin-bottom: 8px;
        }

        .module-card p {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 14px;
        }

        .coming-soon {
            font-size: 12px;
            color: #6c757d;
            background: #f1f3f5;
            padding: 5px 10px;
            border-radius: 20px;
        }
    </style>
</head>

<body>

<header class="dashboard-header py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Customer Loyalty Rewards</h5>
            <small class="text-muted">Administration Panel</small>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <span class="text-muted small">Welcome,</span>
                <strong><?= e($adminName) ?></strong>
            </div>

            <form action="logout.php" method="POST" class="m-0">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(generateCsrfToken()) ?>"
                >
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    Logout
                </button>
            </form>
        </div>
    </div>
</header>

<main class="container py-4">

    <div class="mb-4">
        <h3 class="fw-bold">Admin Dashboard</h3>
        <p class="text-muted mb-0">
            Overview of your customer loyalty rewards system.
        </p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger">
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Main statistics -->
    <h5 class="mb-3">System Overview</h5>

    <div class="row g-3 mb-4">

        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <p class="stat-label">Registered Customers</p>
                <p class="stat-value"><?= number_format($stats['customers']) ?></p>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <p class="stat-label">Active Products</p>
                <p class="stat-value"><?= number_format($stats['active_products']) ?></p>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <p class="stat-label">Total Reward Codes</p>
                <p class="stat-value"><?= number_format($stats['total_codes']) ?></p>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <p class="stat-label">Total Points Issued</p>
                <p class="stat-value"><?= number_format($stats['points_issued']) ?></p>
            </div>
        </div>

    </div>

    <!-- Reward code statistics -->
    <h5 class="mb-3">Reward Code Statistics</h5>

    <div class="row g-3 mb-4">

        <div class="col-md-6 col-lg-4">
            <div class="stat-card">
                <p class="stat-label">Generated Codes</p>
                <p class="stat-value"><?= number_format($stats['total_codes']) ?></p>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="stat-card">
                <p class="stat-label">Redeemed Codes</p>
                <p class="stat-value text-success">
                    <?= number_format($stats['redeemed_codes']) ?>
                </p>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="stat-card">
                <p class="stat-label">Unused Codes</p>
                <p class="stat-value text-primary">
                    <?= number_format($stats['unused_codes']) ?>
                </p>
            </div>
        </div>

    </div>

    <!-- Points statistics -->
    <h5 class="mb-3">Points Overview</h5>

    <div class="row g-3 mb-5">

        <div class="col-md-6">
            <div class="stat-card">
                <p class="stat-label">Total Points Issued to Customers</p>
                <p class="stat-value text-success">
                    <?= number_format($stats['points_issued']) ?>
                </p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="stat-card">
                <p class="stat-label">Total Points Redeemed by Customers</p>
                <p class="stat-value text-danger">
                    <?= number_format($stats['points_redeemed']) ?>
                </p>
            </div>
        </div>

    </div>

    <!-- Admin modules -->
    <h5 class="mb-3">Management Modules</h5>

    <div class="row g-3">

        <div class="col-md-6 col-lg-4">
            <div class="module-card">
                <h5>Product Management</h5>
                <p>Add, edit, view and manage factory products.</p>
                <span class="coming-soon">Coming Soon</span>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="module-card">
                <h5>Reward Management</h5>
                <p>Create and manage rewards that customers can redeem.</p>
                <span class="coming-soon">Coming Soon</span>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="module-card">
                <h5>Reward Code Generation</h5>
                <p>Generate unique reward codes for product packaging.</p>
                <span class="coming-soon">Coming Soon</span>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="module-card">
                <h5>Customer Management</h5>
                <p>Search customers and view their points and activity.</p>
                <span class="coming-soon">Coming Soon</span>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="module-card">
                <h5>Activity Monitoring</h5>
                <p>Monitor code attempts, successful redemptions and reward activity.</p>
                <span class="coming-soon">Coming Soon</span>
            </div>
        </div>

    </div>

    <div class="text-center mt-5">
        <a href="../index.php" class="btn btn-outline-secondary">
            Back to Main Website
        </a>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
