
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

$pdo = getPDO();

// Summary statistics.
$successfulCodeCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM reward_codes WHERE status = 'redeemed'"
)->fetchColumn();

$failedAttemptCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM code_attempts"
)->fetchColumn();

$rewardRedemptionCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM redemptions"
)->fetchColumn();

$totalPointsIssued = (int) $pdo->query(
    "SELECT COALESCE(SUM(points_delta), 0)
     FROM point_transactions
     WHERE transaction_type = 'earn'"
)->fetchColumn();

// Read filters.
$activityType = trim($_GET['activity_type'] ?? '');
$status = trim($_GET['status'] ?? '');
$customerSearch = trim($_GET['customer'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$errors = [];

$allowedTypes = [
    'Code Redeemed',
    'Code Attempt',
    'Reward Redeemed'
];

$allowedStatuses = [
    'Successful',
    'invalid',
    'already_redeemed',
    'inactive_product',
    'ineligible',
    'generated',
    'used',
    'cancelled'
];

// Validate activity type.
if ($activityType !== '' && !in_array($activityType, $allowedTypes, true)) {
    $errors[] = 'Invalid activity type selected.';
    $activityType = '';
}

// Validate status.
if ($status !== '' && !in_array($status, $allowedStatuses, true)) {
    $errors[] = 'Invalid activity status selected.';
    $status = '';
}

// Validate customer search length.
if (mb_strlen($customerSearch) > 150) {
    $errors[] = 'Customer search cannot exceed 150 characters.';
    $customerSearch = '';
}

// Validate dates.
function isValidDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);

    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

if ($dateFrom !== '' && !isValidDate($dateFrom)) {
    $errors[] = 'Invalid start date.';
    $dateFrom = '';
}

if ($dateTo !== '' && !isValidDate($dateTo)) {
    $errors[] = 'Invalid end date.';
    $dateTo = '';
}

if (
    $dateFrom !== '' &&
    $dateTo !== '' &&
    $dateFrom > $dateTo
) {
    $errors[] = 'Start date cannot be later than end date.';
    $dateFrom = '';
    $dateTo = '';
}

// Build activity query.
$sql = "
    SELECT *
    FROM (
        SELECT
            'Code Redeemed' AS activity_type,
            u.full_name AS customer_name,
            u.email AS customer_email,
            u.mobile AS customer_mobile,
            p.product_name AS activity_details,
            rc.points_value AS points,
            'Successful' AS activity_status,
            rc.redeemed_at AS activity_date
        FROM reward_codes rc
        INNER JOIN users u
            ON u.id = rc.redeemed_by
        INNER JOIN products p
            ON p.id = rc.product_id
        WHERE rc.status = 'redeemed'

        UNION ALL

        SELECT
            'Code Attempt' AS activity_type,
            COALESCE(u.full_name, 'Guest / Unknown') AS customer_name,
            COALESCE(u.email, '') AS customer_email,
            COALESCE(u.mobile, '') AS customer_mobile,
            CONCAT(
                'Failed code attempt: ',
                CASE ca.attempt_status
                    WHEN 'invalid' THEN 'Invalid code'
                    WHEN 'already_redeemed' THEN 'Already redeemed code'
                    WHEN 'inactive_product' THEN 'Inactive product'
                    WHEN 'ineligible' THEN 'Ineligible code'
                    ELSE ca.attempt_status
                END
            ) AS activity_details,
            0 AS points,
            ca.attempt_status AS activity_status,
            ca.attempted_at AS activity_date
        FROM code_attempts ca
        LEFT JOIN users u
            ON u.id = ca.user_id

        UNION ALL

        SELECT
            'Reward Redeemed' AS activity_type,
            u.full_name AS customer_name,
            u.email AS customer_email,
            u.mobile AS customer_mobile,
            rw.reward_name AS activity_details,
            r.points_spent AS points,
            r.status AS activity_status,
            r.created_at AS activity_date
        FROM redemptions r
        INNER JOIN users u
            ON u.id = r.user_id
        INNER JOIN rewards rw
            ON rw.id = r.reward_id

    ) AS activities
";

$conditions = [];
$params = [];

// Filter by activity type.
if ($activityType !== '') {
    $conditions[] = 'activity_type = :activity_type';
    $params['activity_type'] = $activityType;
}

// Filter by status.
if ($status !== '') {
    $conditions[] = 'activity_status = :activity_status';
    $params['activity_status'] = $status;
}

// Search customer.
if ($customerSearch !== '') {
    $conditions[] = "
        (
            customer_name LIKE :customer_name
            OR customer_email LIKE :customer_email
            OR customer_mobile LIKE :customer_mobile
        )
    ";

    $searchTerm = '%' . $customerSearch . '%';

    $params['customer_name'] = $searchTerm;
    $params['customer_email'] = $searchTerm;
    $params['customer_mobile'] = $searchTerm;
}

// Filter start date.
if ($dateFrom !== '') {
    $conditions[] = 'activity_date >= :date_from';
    $params['date_from'] = $dateFrom . ' 00:00:00';
}

// Filter end date (inclusive).
if ($dateTo !== '') {
    $conditions[] = 'activity_date < DATE_ADD(:date_to, INTERVAL 1 DAY)';
    $params['date_to'] = $dateTo;
}

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY activity_date DESC LIMIT 100';

$activityStmt = $pdo->prepare($sql);
$activityStmt->execute($params);
$activities = $activityStmt->fetchAll();

// Format activity status.
function formatActivityStatus(string $status): string
{
    return match ($status) {
        'invalid' => 'Invalid Code',
        'already_redeemed' => 'Already Redeemed',
        'inactive_product' => 'Inactive Product',
        'ineligible' => 'Ineligible',
        'generated' => 'Generated',
        'used' => 'Used',
        'cancelled' => 'Cancelled',
        'Successful' => 'Successful',
        default => ucfirst(str_replace('_', ' ', $status))
    };
}

// Badge styling.
function activityBadgeClass(string $status): string
{
    return match ($status) {
        'Successful', 'generated' => 'success',
        'invalid',
        'already_redeemed',
        'inactive_product',
        'ineligible',
        'cancelled' => 'danger',
        'used' => 'primary',
        default => 'secondary'
    };
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Activity Monitoring | LoyaltyRewards Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --navy: #10243e;
            --navy-light: #19395d;
            --blue: #2878e5;
            --blue-light: #edf4ff;
            --page-bg: #f4f7fb;
            --text-main: #1d2939;
            --text-muted: #718096;
            --border: #e7edf4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--page-bg);
            color: var(--text-main);
            font-family: Inter, -apple-system, BlinkMacSystemFont,
                "Segoe UI", sans-serif;
            min-height: 100vh;
        }

        /* Header */
        .topbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 15px 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--navy);
            color: #ffffff;
            font-size: 21px;
        }

        .brand-name {
            color: var(--navy);
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -0.7px;
            line-height: 1.1;
        }

        .brand-name span {
            color: var(--blue);
        }

        .brand-tagline {
            display: block;
            color: #8995a5;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.7px;
            margin-top: 4px;
        }

        .dashboard-btn {
            border: 1px solid #dce5f0;
            border-radius: 9px;
            color: var(--navy);
            font-size: 13px;
            font-weight: 600;
            padding: 9px 15px;
            text-decoration: none;
            transition: 0.2s;
        }

        .dashboard-btn:hover {
            background: var(--navy);
            border-color: var(--navy);
            color: #ffffff;
        }

        /* Main content */
        .main-content {
            padding-top: 34px;
            padding-bottom: 45px;
        }

        .breadcrumb-text {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 9px;
        }

        .breadcrumb-text a {
            color: var(--blue);
            text-decoration: none;
        }

        .page-title {
            color: var(--navy);
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.8px;
            margin-bottom: 7px;
        }

        .page-description {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 0;
        }

        /* Summary cards */
        .summary-card {
            height: 100%;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 21px;
            box-shadow: 0 3px 12px rgba(16, 36, 62, 0.025);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(16, 36, 62, 0.07);
        }

        .summary-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .summary-label {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
        }

        .summary-icon {
            width: 39px;
            height: 39px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            font-size: 17px;
        }

        .icon-green {
            background: #eaf8f0;
            color: #159653;
        }

        .icon-red {
            background: #fff0ef;
            color: #dc4c45;
        }

        .icon-blue {
            background: #edf4ff;
            color: #2878e5;
        }

        .icon-purple {
            background: #f3efff;
            color: #7956c9;
        }

        .summary-value {
            color: var(--navy);
            font-size: 29px;
            font-weight: 800;
            letter-spacing: -1px;
            margin-top: 15px;
        }

        /* Content panels */
        .content-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 3px 12px rgba(16, 36, 62, 0.025);
        }

        .section-title {
            color: var(--navy);
            font-size: 16px;
            font-weight: 750;
            margin-bottom: 5px;
        }

        .section-description {
            color: var(--text-muted);
            font-size: 12px;
            margin-bottom: 20px;
        }

        /* Filters */
        .form-label {
            color: #34445a;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .form-control,
        .form-select {
            min-height: 43px;
            border: 1px solid #dce4ed;
            border-radius: 9px;
            color: var(--text-main);
            font-size: 13px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(40, 120, 229, 0.1);
        }

        .btn-primary {
            background: var(--blue);
            border-color: var(--blue);
            border-radius: 9px;
            font-size: 13px;
            font-weight: 650;
            padding: 10px 18px;
        }

        .btn-primary:hover {
            background: #1766ce;
            border-color: #1766ce;
        }

        .btn-outline-secondary {
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            padding: 10px 17px;
        }

        /* Activity table */
        .activity-table {
            margin-bottom: 0;
            min-width: 850px;
        }

        .activity-table thead th {
            background: #f5f8fc;
            border-bottom: 1px solid var(--border);
            color: #65758a;
            font-size: 11px;
            font-weight: 750;
            letter-spacing: 0.45px;
            padding: 14px 13px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .activity-table tbody td {
            border-bottom: 1px solid #edf1f6;
            color: #34445a;
            font-size: 12px;
            padding: 15px 13px;
            vertical-align: middle;
        }

        .activity-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .activity-table tbody tr:hover {
            background: #f9fbfe;
        }

        .customer-name {
            color: var(--navy);
            font-weight: 700;
        }

        .activity-type {
            color: var(--navy);
            font-weight: 650;
        }

        .points-value {
            color: #159653;
            font-weight: 750;
        }

        .status-badge {
            border-radius: 20px;
            display: inline-block;
            font-size: 10px;
            font-weight: 750;
            padding: 6px 10px;
            white-space: nowrap;
        }

        .status-success {
            background: #e7f7ee;
            color: #13864b;
        }

        .status-danger {
            background: #fff0ef;
            color: #c83e38;
        }

        .status-primary {
            background: #edf4ff;
            color: #2468c7;
        }

        .status-secondary {
            background: #eef1f5;
            color: #637083;
        }

        .empty-state {
            padding: 45px 15px;
            text-align: center;
        }

        .empty-icon {
            color: #9aabc0;
            font-size: 35px;
            margin-bottom: 12px;
        }

        .empty-title {
            color: var(--navy);
            font-size: 15px;
            font-weight: 750;
        }

        .empty-description {
            color: var(--text-muted);
            font-size: 12px;
        }

        .security-note {
            background: #f5f8fc;
            border-radius: 8px;
            color: #718096;
            font-size: 11px;
            padding: 11px 13px;
        }

        @media (max-width: 767px) {
            .main-content {
                padding-top: 25px;
            }

            .page-title {
                font-size: 23px;
            }

            .content-card {
                padding: 17px;
            }

            .summary-card {
                padding: 17px;
            }

            .summary-value {
                font-size: 25px;
            }

            .brand-name {
                font-size: 19px;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="container-fluid px-3 px-lg-4 d-flex justify-content-between align-items-center">

        <a href="../dashboard.php" class="brand">
            <div class="brand-icon">
                <i class="bi bi-star-fill"></i>
            </div>

            <div>
                <div class="brand-name">
                    Loyalty<span>Rewards</span>
                </div>
                <span class="brand-tagline">EVERY PURCHASE MATTERS</span>
            </div>
        </a>

        <a href="../dashboard.php" class="dashboard-btn">
            <i class="bi bi-grid me-1"></i>
            Admin Dashboard
        </a>

    </div>
</header>

<main class="container-fluid px-3 px-lg-4 main-content">

    <!-- Page heading -->
    <div class="mb-4">
        <div class="breadcrumb-text">
            <a href="../dashboard.php">Admin Dashboard</a>
            <span class="mx-1">/</span>
            Activity Monitoring
        </div>

        <h1 class="page-title">Activity Monitoring</h1>

        <p class="page-description">
            Monitor customer reward activity, code redemptions and failed code attempts.
        </p>
    </div>

    <!-- Summary cards -->
    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-top">
                    <span class="summary-label">Successful Code Redemptions</span>
                    <div class="summary-icon icon-green">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>

                <div class="summary-value text-success">
                    <?= number_format($successfulCodeCount) ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-top">
                    <span class="summary-label">Failed Code Attempts</span>
                    <div class="summary-icon icon-red">
                        <i class="bi bi-shield-exclamation"></i>
                    </div>
                </div>

                <div class="summary-value text-danger">
                    <?= number_format($failedAttemptCount) ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-top">
                    <span class="summary-label">Reward Redemptions</span>
                    <div class="summary-icon icon-blue">
                        <i class="bi bi-gift"></i>
                    </div>
                </div>

                <div class="summary-value" style="color: var(--blue);">
                    <?= number_format($rewardRedemptionCount) ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-top">
                    <span class="summary-label">Total Points Issued</span>
                    <div class="summary-icon icon-purple">
                        <i class="bi bi-coin"></i>
                    </div>
                </div>

                <div class="summary-value">
                    <?= number_format($totalPointsIssued) ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Filters -->
    <section class="content-card mb-4">

        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-funnel text-primary"></i>
            <h2 class="section-title mb-0">Filter Activities</h2>
        </div>

        <p class="section-description">
            Narrow down activity records using type, status, customer or date.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-warning py-2 small">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="GET" action="index.php">

            <div class="row g-3">

                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label" for="activity_type">
                        Activity Type
                    </label>

                    <select
                        name="activity_type"
                        id="activity_type"
                        class="form-select"
                    >
                        <option value="">All Activities</option>

                        <?php foreach ($allowedTypes as $type): ?>
                            <option
                                value="<?= e($type) ?>"
                                <?= $activityType === $type ? 'selected' : '' ?>
                            >
                                <?= e($type) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label" for="status">
                        Status
                    </label>

                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>

                        <?php foreach ($allowedStatuses as $option): ?>
                            <option
                                value="<?= e($option) ?>"
                                <?= $status === $option ? 'selected' : '' ?>
                            >
                                <?= e(formatActivityStatus($option)) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label" for="customer">
                        Search Customer
                    </label>

                    <input
                        type="search"
                        name="customer"
                        id="customer"
                        class="form-control"
                        placeholder="Name, email or mobile"
                        value="<?= e($customerSearch) ?>"
                        maxlength="150"
                    >
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label" for="date_from">
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        id="date_from"
                        class="form-control"
                        value="<?= e($dateFrom) ?>"
                    >
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label" for="date_to">
                        End Date
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        id="date_to"
                        class="form-control"
                        value="<?= e($dateTo) ?>"
                    >
                </div>

            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i>
                    Apply Filters
                </button>

                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                    Clear Filters
                </a>

            </div>

        </form>

    </section>

    <!-- Activity records -->
    <section class="content-card">

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">

            <div>
                <h2 class="section-title">
                    <i class="bi bi-clock-history me-1 text-primary"></i>
                    Activity Records
                </h2>

                <p class="section-description mb-0">
                    Recent customer and reward activities recorded by the system.
                </p>
            </div>

            <div class="small text-muted">
                <strong class="text-dark"><?= count($activities) ?></strong>
                matching records
            </div>

        </div>

        <?php if (empty($activities)): ?>

            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-inbox"></i>
                </div>

                <div class="empty-title">No activities found</div>

                <p class="empty-description mb-0">
                    No activities match the selected filters. Try changing or clearing your filters.
                </p>
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table activity-table align-middle">

                    <thead>
                        <tr>
                            <th>Activity Type</th>
                            <th>Customer</th>
                            <th>Details</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Date &amp; Time</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($activities as $activity): ?>

                            <tr>

                                <td>
                                    <span class="activity-type">
                                        <?= e($activity['activity_type']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="customer-name">
                                        <?= e($activity['customer_name']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($activity['activity_details']) ?>
                                </td>

                                <td>
                                    <?php if ((int) $activity['points'] > 0): ?>
                                        <span class="points-value">
                                            +<?= number_format((int) $activity['points']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php $currentStatus = (string) $activity['activity_status']; ?>

                                    <span class="status-badge status-<?= e(activityBadgeClass($currentStatus)) ?>">
                                        <?= e(formatActivityStatus($currentStatus)) ?>
                                    </span>
                                </td>

                                <td class="text-nowrap">
                                    <?= e(date(
                                        'd M Y, h:i A',
                                        strtotime($activity['activity_date'])
                                    )) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

        <div class="security-note mt-3">
            <i class="bi bi-shield-lock me-1"></i>
            Submitted code hashes and original reward codes are not displayed.
            Maximum 100 activity records are displayed.
        </div>

    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
