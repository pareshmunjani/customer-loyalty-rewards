
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

$pdo = getPDO();

// Validate customer ID.
$customerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$customerId || $customerId < 1) {
    setFlashMessage('error', 'Invalid customer ID.');
    redirect('/customer-loyalty-rewards/public/admin/customers/index.php');
}

// Fetch customer profile.
$customerStmt = $pdo->prepare(
    "SELECT id, full_name, mobile, email, created_at
     FROM users
     WHERE id = :id AND role = 'customer'"
);

$customerStmt->execute(['id' => $customerId]);
$customer = $customerStmt->fetch();

if (!$customer) {
    setFlashMessage('error', 'Customer not found.');
    redirect('/customer-loyalty-rewards/public/admin/customers/index.php');
}

// Calculate points summary.
$summaryStmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(
            CASE
                WHEN transaction_type = 'earn'
                THEN points_delta ELSE 0
            END
        ), 0) AS total_earned,

        COALESCE(SUM(
            CASE
                WHEN transaction_type = 'redeem'
                THEN points_delta ELSE 0
            END
        ), 0) AS total_redeemed,

        COALESCE(SUM(
            CASE
                WHEN transaction_type = 'earn'
                THEN points_delta
                WHEN transaction_type = 'redeem'
                THEN -points_delta
                ELSE 0
            END
        ), 0) AS available_points

     FROM point_transactions
     WHERE user_id = :user_id"
);

$summaryStmt->execute(['user_id' => $customerId]);
$summary = $summaryStmt->fetch();

// Fetch reward code redemption history.
$codeStmt = $pdo->prepare(
    "SELECT
        rc.id,
        rc.points_value,
        rc.redeemed_at,
        p.product_name

     FROM reward_codes rc

     INNER JOIN products p
        ON p.id = rc.product_id

     WHERE rc.redeemed_by = :user_id
       AND rc.status = 'redeemed'

     ORDER BY rc.redeemed_at DESC"
);

$codeStmt->execute(['user_id' => $customerId]);
$redeemedCodes = $codeStmt->fetchAll();

// Fetch reward redemption history.
$redemptionStmt = $pdo->prepare(
    "SELECT
        r.id,
        r.points_spent,
        r.voucher_code,
        r.status,
        r.created_at,
        rw.reward_name

     FROM redemptions r

     INNER JOIN rewards rw
        ON rw.id = r.reward_id

     WHERE r.user_id = :user_id

     ORDER BY r.created_at DESC"
);

$redemptionStmt->execute(['user_id' => $customerId]);
$redemptions = $redemptionStmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Details | Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f5f7fb;
        }

        .page-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 24px;
        }

        .summary-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            height: 100%;
        }

        .summary-value {
            font-size: 25px;
            font-weight: 700;
        }

        .table th {
            background-color: #f8f9fa;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }
    </style>
</head>

<body>

<header class="bg-white border-bottom py-3">

    <div class="container d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-0">Customer Loyalty Rewards</h5>
            <small class="text-muted">Admin / Customer Details</small>
        </div>

        <a
            href="../dashboard.php"
            class="btn btn-outline-secondary btn-sm"
        >
            Admin Dashboard
        </a>

    </div>

</header>

<main class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-1">Customer Details</h3>
            <p class="text-muted mb-0">
                Complete customer profile and loyalty activity.
            </p>
        </div>

        <a href="index.php" class="btn btn-outline-secondary">
            Back to Customers
        </a>

    </div>

    <!-- Customer Profile -->

    <div class="page-card mb-4">

        <h5 class="fw-bold mb-3">Customer Profile</h5>

        <div class="row g-3">

            <div class="col-md-6">
                <small class="text-muted">Customer ID</small>
                <p class="fw-semibold mb-0">
                    #<?= (int) $customer['id'] ?>
                </p>
            </div>

            <div class="col-md-6">
                <small class="text-muted">Full Name</small>
                <p class="fw-semibold mb-0">
                    <?= e($customer['full_name']) ?>
                </p>
            </div>

            <div class="col-md-6">
                <small class="text-muted">Mobile Number</small>
                <p class="fw-semibold mb-0">
                    <?= e($customer['mobile']) ?>
                </p>
            </div>

            <div class="col-md-6">
                <small class="text-muted">Email Address</small>
                <p class="fw-semibold mb-0">
                    <?= e($customer['email']) ?>
                </p>
            </div>

            <div class="col-md-6">
                <small class="text-muted">Registration Date</small>
                <p class="fw-semibold mb-0">
                    <?= e(date(
                        'd M Y, h:i A',
                        strtotime($customer['created_at'])
                    )) ?>
                </p>
            </div>

        </div>

    </div>

    <!-- Points Summary -->

    <h5 class="fw-bold mb-3">Loyalty Points Summary</h5>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="summary-card">
                <small class="text-muted">Total Points Earned</small>
                <div class="summary-value text-primary">
                    <?= number_format((int) $summary['total_earned']) ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card">
                <small class="text-muted">Total Points Redeemed</small>
                <div class="summary-value text-danger">
                    <?= number_format((int) $summary['total_redeemed']) ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card">
                <small class="text-muted">Available Points</small>
                <div class="summary-value text-success">
                    <?= number_format((int) $summary['available_points']) ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Reward Code History -->

    <div class="page-card mb-4">

        <h5 class="fw-bold mb-3">Reward Code Redemption History</h5>

        <?php if (empty($redeemedCodes)): ?>

            <div class="alert alert-info mb-0">
                This customer has not redeemed any reward codes yet.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-bordered table-hover mb-0">

                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Points Earned</th>
                            <th>Redeemed Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($redeemedCodes as $code): ?>

                            <tr>

                                <td><?= e($code['product_name']) ?></td>

                                <td>
                                    <?= number_format((int) $code['points_value']) ?>
                                </td>

                                <td>
                                    <?= e(date(
                                        'd M Y, h:i A',
                                        strtotime($code['redeemed_at'])
                                    )) ?>
                                </td>

                                <td>
                                    <span class="badge bg-success">
                                        Redeemed
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

    <!-- Reward Redemption History -->

    <div class="page-card">

        <h5 class="fw-bold mb-3">Reward Redemption History</h5>

        <?php if (empty($redemptions)): ?>

            <div class="alert alert-info mb-0">
                This customer has not redeemed any rewards yet.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-bordered table-hover mb-0">

                    <thead>
                        <tr>
                            <th>Reward</th>
                            <th>Points Spent</th>
                            <th>Voucher Code</th>
                            <th>Status</th>
                            <th>Redemption Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($redemptions as $redemption): ?>

                            <tr>

                                <td><?= e($redemption['reward_name']) ?></td>

                                <td>
                                    <?= number_format((int) $redemption['points_spent']) ?>
                                </td>

                                <td>
                                    <code><?= e($redemption['voucher_code']) ?></code>
                                </td>

                                <td>
                                    <?php
                                    $statusClass = match ($redemption['status']) {
                                        'generated' => 'bg-success',
                                        'used' => 'bg-primary',
                                        'cancelled' => 'bg-secondary',
                                        default => 'bg-warning text-dark'
                                    };
                                    ?>

                                    <span class="badge <?= $statusClass ?>">
                                        <?= e(ucfirst($redemption['status'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e(date(
                                        'd M Y, h:i A',
                                        strtotime($redemption['created_at'])
                                    )) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
