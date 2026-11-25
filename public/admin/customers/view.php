
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
     INNER JOIN products p ON p.id = rc.product_id
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
     INNER JOIN rewards rw ON rw.id = r.reward_id
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
        :root {
            --navy: #253b80;
            --navy-dark: #172b61;
            --blue: #4169e1;
            --page-bg: #f3f6fc;
            --border: #e5eaf3;
            --muted: #718096;
        }

        body {
            background: var(--page-bg);
            color: #253047;
            font-family: Arial, sans-serif;
        }

        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 15px 0;
        }

        .brand-name {
            color: var(--navy-dark);
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
        }

        .brand-name span {
            color: var(--blue);
        }

        .brand-star {
            color: #f5b82e;
            margin-right: 5px;
        }

        .brand-subtitle {
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .page-container {
            width: 100%;
            padding: 30px 3%;
        }

        .page-title {
            color: var(--navy-dark);
            font-size: 27px;
            font-weight: 700;
        }

        .page-description {
            color: var(--muted);
            font-size: 14px;
        }

        .section-title {
            color: var(--navy-dark);
            font-size: 18px;
            font-weight: 700;
        }

        .page-card,
        .summary-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(37, 59, 128, 0.04);
        }

        .page-card {
            padding: 25px;
        }

        .summary-card {
            padding: 22px;
            height: 100%;
        }

        .summary-label {
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
        }

        .summary-value {
            font-size: 28px;
            font-weight: 800;
            margin-top: 8px;
        }

        .summary-earned {
            color: #16845b;
        }

        .summary-redeemed {
            color: #d35454;
        }

        .summary-available {
            color: var(--blue);
        }

        .profile-item {
            background: #f7f9fe;
            border: 1px solid #edf0f7;
            border-radius: 10px;
            padding: 15px;
            height: 100%;
        }

        .profile-label {
            color: var(--muted);
            display: block;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .profile-value {
            color: var(--navy-dark);
            font-size: 14px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .table {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table thead th {
            background: #f0f3fa;
            color: var(--navy-dark);
            font-size: 13px;
            font-weight: 700;
            padding: 14px 12px;
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
        }

        .table tbody td {
            color: #45516a;
            font-size: 13px;
            padding: 14px 12px;
            white-space: nowrap;
        }

        .table tbody tr:hover {
            background: #f8faff;
        }

        .btn-outline-secondary {
            border-radius: 8px;
        }

        .status-badge {
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 700;
        }

        .empty-state {
            background: #f5f8ff;
            border: 1px dashed #cbd7f2;
            border-radius: 10px;
            padding: 28px 18px;
            text-align: center;
            color: var(--muted);
            font-size: 14px;
        }

        .voucher-code {
            background: #f0f3fa;
            border-radius: 5px;
            color: var(--navy);
            padding: 5px 8px;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 22px 15px;
            }

            .page-card,
            .summary-card {
                padding: 18px;
            }

            .page-title {
                font-size: 23px;
            }

            .summary-value {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<header class="top-navbar">
    <div class="container-fluid px-4 d-flex justify-content-between align-items-center">

        <div>
            <h5 class="brand-name">
                <span class="brand-star">★</span>Loyalty<span>Rewards</span>
            </h5>
            <small class="brand-subtitle">EVERY PURCHASE MATTERS</small>
        </div>

        <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">
            Admin Dashboard
        </a>

    </div>
</header>

<main class="page-container">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <h3 class="page-title mb-1">Customer Details</h3>
            <p class="page-description mb-0">
                Complete customer profile and loyalty activity.
            </p>
        </div>

        <a href="index.php" class="btn btn-outline-secondary">
            ← Back to Customers
        </a>

    </div>

    <!-- Customer Profile -->

    <div class="page-card mb-4">

        <h5 class="section-title mb-3">Customer Profile</h5>

        <div class="row g-3">

            <div class="col-md-6 col-lg-4">
                <div class="profile-item">
                    <span class="profile-label">Customer ID</span>
                    <div class="profile-value">#<?= (int) $customer['id'] ?></div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="profile-item">
                    <span class="profile-label">Full Name</span>
                    <div class="profile-value"><?= e($customer['full_name']) ?></div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="profile-item">
                    <span class="profile-label">Mobile Number</span>
                    <div class="profile-value"><?= e($customer['mobile']) ?></div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="profile-item">
                    <span class="profile-label">Email Address</span>
                    <div class="profile-value"><?= e($customer['email']) ?></div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="profile-item">
                    <span class="profile-label">Registration Date</span>
                    <div class="profile-value">
                        <?= e(date(
                            'd M Y, h:i A',
                            strtotime($customer['created_at'])
                        )) ?>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Points Summary -->

    <h5 class="section-title mb-3">Loyalty Points Summary</h5>

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="summary-card">
                <div class="summary-label">Total Points Earned</div>
                <div class="summary-value summary-earned">
                    <?= number_format((int) $summary['total_earned']) ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card">
                <div class="summary-label">Total Points Redeemed</div>
                <div class="summary-value summary-redeemed">
                    <?= number_format((int) $summary['total_redeemed']) ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="summary-card">
                <div class="summary-label">Available Points</div>
                <div class="summary-value summary-available">
                    <?= number_format((int) $summary['available_points']) ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Reward Code Redemption History -->

    <div class="page-card mb-4">

        <h5 class="section-title mb-3">Reward Code Redemption History</h5>

        <?php if (empty($redeemedCodes)): ?>

            <div class="empty-state">
                This customer has not redeemed any reward codes yet.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-hover">

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
                                <td class="fw-semibold">
                                    <?= e($code['product_name']) ?>
                                </td>

                                <td class="summary-earned fw-bold">
                                    <?= number_format((int) $code['points_value']) ?>
                                </td>

                                <td>
                                    <?= e(date(
                                        'd M Y, h:i A',
                                        strtotime($code['redeemed_at'])
                                    )) ?>
                                </td>

                                <td>
                                    <span class="badge bg-success status-badge">
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

        <h5 class="section-title mb-3">Reward Redemption History</h5>

        <?php if (empty($redemptions)): ?>

            <div class="empty-state">
                This customer has not redeemed any rewards yet.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-hover">

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

                            <?php
                            $statusClass = match ($redemption['status']) {
                                'generated' => 'bg-success',
                                'used' => 'bg-primary',
                                'cancelled' => 'bg-secondary',
                                default => 'bg-warning text-dark'
                            };
                            ?>

                            <tr>

                                <td class="fw-semibold">
                                    <?= e($redemption['reward_name']) ?>
                                </td>

                                <td class="summary-redeemed fw-bold">
                                    <?= number_format((int) $redemption['points_spent']) ?>
                                </td>

                                <td>
                                    <code class="voucher-code">
                                        <?= e($redemption['voucher_code']) ?>
                                    </code>
                                </td>

                                <td>
                                    <span class="badge <?= $statusClass ?> status-badge">
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
