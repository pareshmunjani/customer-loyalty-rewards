
<?php

require_once __DIR__ . '/../../app/includes/functions.php';

startSecureSession();

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/config/database.php';

requireCustomer();

$pageTitle = 'Available Rewards';

$pdo = getPDO();
$userId = (int) $_SESSION['user_id'];

// Calculate the customer's current points balance
$balanceStmt = $pdo->prepare("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN transaction_type = 'earn'
                        THEN points_delta
                    WHEN transaction_type = 'redeem'
                        THEN -points_delta
                    ELSE 0
                END
            ),
            0
        ) AS available_points
    FROM point_transactions
    WHERE user_id = :user_id
");

$balanceStmt->execute([
    'user_id' => $userId
]);

$availablePoints = (int) $balanceStmt->fetchColumn();

// Get all active rewards
$rewardsStmt = $pdo->prepare("
    SELECT
        id,
        reward_name,
        description,
        required_points
    FROM rewards
    WHERE status = 'active'
    ORDER BY required_points ASC
");

$rewardsStmt->execute();

$rewards = $rewardsStmt->fetchAll();

require_once __DIR__ . '/../../app/includes/header.php';

?>

<div class="container py-5">

    <!-- Page heading -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">Available Rewards</h2>

            <p class="text-muted mb-0">
                Explore rewards and use your earned loyalty points.
            </p>
        </div>

        <div class="mt-3 mt-md-0">

            <div class="card border-0 shadow-sm">
                <div class="card-body px-4 py-3 text-center">

                    <small class="text-muted">Your Available Points</small>

                    <h3 class="fw-bold text-primary mb-0">
                        <?php echo number_format($availablePoints); ?>
                    </h3>

                </div>
            </div>

        </div>

    </div>

    <!-- Rewards list -->
    <?php if (empty($rewards)): ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">

                <h5 class="fw-semibold">No Rewards Available</h5>

                <p class="text-muted mb-0">
                    There are currently no active rewards.
                    Please check again later.
                </p>

            </div>
        </div>

    <?php else: ?>

        <div class="row g-4">

            <?php foreach ($rewards as $reward): ?>

                <?php
                    $requiredPoints = (int) $reward['required_points'];

                    $hasEnoughPoints =
                        $availablePoints >= $requiredPoints;

                    $pointsNeeded = max(
                        0,
                        $requiredPoints - $availablePoints
                    );
                ?>

                <div class="col-md-6 col-lg-4">

                    <div class="card h-100 border-0 shadow-sm">

                        <div class="card-body p-4">

                            <div class="mb-3">

                                <span class="badge bg-light text-primary">
                                    Reward
                                </span>

                            </div>

                            <h5 class="fw-bold mb-2">
                                <?php echo e($reward['reward_name']); ?>
                            </h5>

                            <p class="text-muted">
                                <?php
                                    echo e(
                                        $reward['description']
                                        ?: 'Redeem your loyalty points for this reward.'
                                    );
                                ?>
                            </p>

                            <div class="border-top pt-3 mt-3">

                                <div class="d-flex justify-content-between align-items-center">

                                    <span class="text-muted">
                                        Required Points
                                    </span>

                                    <strong class="fs-5 text-primary">
                                        <?php echo number_format($requiredPoints); ?>
                                    </strong>

                                </div>

                            </div>

                            <div class="mt-3">

                                <?php if ($hasEnoughPoints): ?>

                                    <span class="badge bg-success-subtle text-success p-2">
                                        You have enough points
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-warning-subtle text-dark p-2">
                                        Need <?php echo number_format($pointsNeeded); ?>
                                        more points
                                    </span>

                                <?php endif; ?>

                            </div>

                            <!-- Redeem button -->
                            <div class="mt-4">

                                <?php if ($hasEnoughPoints): ?>

                                    <a
                                        href="redeem-reward.php?reward_id=<?php echo (int) $reward['id']; ?>"
                                        class="btn btn-primary w-100"
                                    >
                                        Redeem Reward
                                    </a>

                                <?php else: ?>

                                    <button
                                        type="button"
                                        class="btn btn-secondary w-100"
                                        disabled
                                    >
                                        Not Enough Points
                                    </button>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <!-- Navigation -->
    <div class="mt-4">

        <a
            href="/customer-loyalty-rewards/public/customer/dashboard.php"
            class="btn btn-outline-primary"
        >
            Back to Dashboard
        </a>

    </div>

</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
