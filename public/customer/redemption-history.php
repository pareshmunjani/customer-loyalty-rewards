
<?php

require_once __DIR__ . '/../../app/includes/functions.php';

startSecureSession();

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/config/database.php';

requireCustomer();

$pageTitle = 'Redemption History';

$pdo = getPDO();
$userId = (int) $_SESSION['user_id'];

// Get customer's redemption history
$historyStmt = $pdo->prepare("
    SELECT
        r.id,
        r.points_spent,
        r.voucher_code,
        r.status,
        r.created_at,
        rw.reward_name,
        rw.description
    FROM redemptions r
    INNER JOIN rewards rw
        ON rw.id = r.reward_id
    WHERE r.user_id = :user_id
    ORDER BY r.created_at DESC, r.id DESC
");

$historyStmt->execute([
    'user_id' => $userId
]);

$redemptions = $historyStmt->fetchAll();

// Calculate redemption summary
$summaryStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_redemptions,
        COALESCE(SUM(points_spent), 0) AS total_points_spent,
        SUM(CASE WHEN status = 'generated' THEN 1 ELSE 0 END)
            AS active_vouchers,
        SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END)
            AS used_vouchers
    FROM redemptions
    WHERE user_id = :user_id
");

$summaryStmt->execute([
    'user_id' => $userId
]);

$summary = $summaryStmt->fetch();

require_once __DIR__ . '/../../app/includes/header.php';

?>

<div class="container py-5">

    <!-- Page heading -->
    <div class="mb-4">

        <h2 class="fw-bold mb-1">Redemption History</h2>

        <p class="text-muted mb-0">
            View your claimed rewards and voucher details.
        </p>

    </div>

    <!-- Summary cards -->
    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <small class="text-muted">Total Redemptions</small>

                    <h3 class="fw-bold text-primary mb-0">
                        <?php echo number_format((int) $summary['total_redemptions']); ?>
                    </h3>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <small class="text-muted">Total Points Spent</small>

                    <h3 class="fw-bold text-primary mb-0">
                        <?php echo number_format((int) $summary['total_points_spent']); ?>
                    </h3>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <small class="text-muted">Generated Vouchers</small>

                    <h3 class="fw-bold text-success mb-0">
                        <?php echo number_format((int) $summary['active_vouchers']); ?>
                    </h3>

                </div>

            </div>

        </div>

    </div>

    <!-- Redemption history -->
    <div class="card border-0 shadow-sm">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-4">Your Claimed Rewards</h5>

            <?php if (empty($redemptions)): ?>

                <div class="text-center py-5">

                    <h5 class="fw-semibold">No Redemptions Yet</h5>

                    <p class="text-muted mb-0">
                        You haven't redeemed any rewards.
                        Start earning points to claim rewards.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-light">

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
                                        'generated' => 'success',
                                        'used' => 'primary',
                                        'cancelled' => 'secondary',
                                        default => 'dark'
                                    };
                                ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php echo e($redemption['reward_name']); ?>
                                        </strong>

                                        <?php if (!empty($redemption['description'])): ?>

                                            <div class="small text-muted">
                                                <?php echo e($redemption['description']); ?>
                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?php echo number_format((int) $redemption['points_spent']); ?>
                                    </td>

                                    <td>

                                        <code>
                                            <?php echo e($redemption['voucher_code']); ?>
                                        </code>

                                    </td>

                                    <td>

                                        <span class="badge bg-<?php echo e($statusClass); ?>">
                                            <?php echo e(ucfirst($redemption['status'])); ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?php
                                            echo e(
                                                date(
                                                    'd M Y, h:i A',
                                                    strtotime($redemption['created_at'])
                                                )
                                            );
                                        ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <!-- Navigation -->
    <div class="mt-4">

        <a
            href="/customer-loyalty-rewards/public/customer/dashboard.php"
            class="btn btn-outline-primary"
        >
            Back to Dashboard
        </a>

        <a
            href="/customer-loyalty-rewards/public/customer/rewards.php"
            class="btn btn-primary ms-2"
        >
            Browse Rewards
        </a>

    </div>

</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
