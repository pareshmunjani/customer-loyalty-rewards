
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
        'Successful', 'generated' => 'bg-success',
        'invalid',
        'already_redeemed',
        'inactive_product',
        'ineligible',
        'cancelled' => 'bg-danger',
        'used' => 'bg-primary',
        default => 'bg-secondary'
    };
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Activity Monitoring | Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f5f7fb;
        }

        .summary-card,
        .page-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 22px;
        }

        .summary-card {
            height: 100%;
        }

        .summary-value {
            font-size: 26px;
            font-weight: 700;
        }

        .table th {
            background-color: #f8f9fa;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .filter-label {
            font-weight: 600;
        }
    </style>
</head>

<body>

<header class="bg-white border-bottom py-3">

    <div class="container d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-0">Customer Loyalty Rewards</h5>
            <small class="text-muted">Admin / Activity Monitoring</small>
        </div>

        <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">
            Admin Dashboard
        </a>

    </div>

</header>

<main class="container py-4">

    <div class="mb-4">
        <h3 class="fw-bold mb-1">Activity Monitoring</h3>
        <p class="text-muted mb-0">
            Monitor customer reward activity and failed code attempts.
        </p>
    </div>

    <!-- Summary Cards -->

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="summary-card">
                <small class="text-muted">Successful Code Redemptions</small>
                <div class="summary-value text-success">
                    <?= number_format($successfulCodeCount) ?>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <small class="text-muted">Failed Code Attempts</small>
                <div class="summary-value text-danger">
                    <?= number_format($failedAttemptCount) ?>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <small class="text-muted">Reward Redemptions</small>
                <div class="summary-value text-primary">
                    <?= number_format($rewardRedemptionCount) ?>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card">
                <small class="text-muted">Total Points Issued</small>
                <div class="summary-value">
                    <?= number_format($totalPointsIssued) ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Filters -->

    <div class="page-card mb-4">

        <h5 class="fw-bold mb-3">Filter Activities</h5>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-warning">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="GET" action="index.php">

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label filter-label" for="activity_type">
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

                <div class="col-md-4">
                    <label class="form-label filter-label" for="status">
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

                <div class="col-md-4">
                    <label class="form-label filter-label" for="customer">
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

                <div class="col-md-6">
                    <label class="form-label filter-label" for="date_from">
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

                <div class="col-md-6">
                    <label class="form-label filter-label" for="date_to">
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

            <div class="d-flex gap-2 mt-3">

                <button type="submit" class="btn btn-primary">
                    Apply Filters
                </button>

                <a href="index.php" class="btn btn-outline-secondary">
                    Clear Filters
                </a>

            </div>

        </form>

    </div>

    <!-- Activity Table -->

    <div class="page-card">

        <div class="mb-3">
            <h5 class="fw-bold mb-1">Activity Records</h5>

            <p class="text-muted mb-0">
                Matching records: <strong><?= count($activities) ?></strong>
                (maximum 100 displayed)
            </p>
        </div>

        <?php if (empty($activities)): ?>

            <div class="alert alert-info mb-0">
                No activities match the selected filters.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-bordered table-hover mb-0">

                    <thead>
                        <tr>
                            <th>Activity Type</th>
                            <th>Customer</th>
                            <th>Details</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($activities as $activity): ?>

                            <tr>

                                <td>
                                    <strong><?= e($activity['activity_type']) ?></strong>
                                </td>

                                <td>
                                    <?= e($activity['customer_name']) ?>
                                </td>

                                <td>
                                    <?= e($activity['activity_details']) ?>
                                </td>

                                <td>
                                    <?php if ((int) $activity['points'] > 0): ?>
                                        <?= number_format((int) $activity['points']) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php $currentStatus = (string) $activity['activity_status']; ?>

                                    <span class="badge <?= e(activityBadgeClass($currentStatus)) ?>">
                                        <?= e(formatActivityStatus($currentStatus)) ?>
                                    </span>
                                </td>

                                <td>
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

        <p class="text-muted small mt-3 mb-0">
            Submitted code hashes and original reward codes are not displayed.
        </p>

    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
