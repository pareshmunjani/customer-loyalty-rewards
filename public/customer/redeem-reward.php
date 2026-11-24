
<?php

require_once __DIR__ . '/../../app/includes/functions.php';

startSecureSession();

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/csrf.php';
require_once __DIR__ . '/../../app/config/database.php';

requireCustomer();

$pageTitle = 'Redeem Reward';

$pdo = getPDO();
$userId = (int) $_SESSION['user_id'];

$errorMessage = '';

// Get reward ID from GET or POST
$rewardId = filter_var(
    $_POST['reward_id'] ?? $_GET['reward_id'] ?? null,
    FILTER_VALIDATE_INT
);

$reward = null;
$availablePoints = 0;

if (!$rewardId || $rewardId <= 0) {

    $errorMessage = 'Invalid reward selection. Please choose a reward.';

} else {

    // Load the selected active reward
    $rewardStmt = $pdo->prepare("
        SELECT
            id,
            reward_name,
            description,
            required_points
        FROM rewards
        WHERE id = :reward_id
          AND status = 'active'
    ");

    $rewardStmt->execute([
        'reward_id' => $rewardId
    ]);

    $reward = $rewardStmt->fetch();

    if (!$reward) {
        $errorMessage = 'This reward is unavailable.';
    }
}

// Process reward confirmation
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $reward
    && verifyCsrfToken($_POST['csrf_token'] ?? null)
) {

    try {

        // Start transaction
        $pdo->beginTransaction();

        // Lock customer row to prevent simultaneous spending
        $userStmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE id = :user_id
            FOR UPDATE
        ");

        $userStmt->execute([
            'user_id' => $userId
        ]);

        if (!$userStmt->fetch()) {
            throw new RuntimeException('Customer account not found.');
        }

        // Lock and recheck reward status
        $lockedRewardStmt = $pdo->prepare("
            SELECT
                id,
                reward_name,
                required_points
            FROM rewards
            WHERE id = :reward_id
              AND status = 'active'
            FOR UPDATE
        ");

        $lockedRewardStmt->execute([
            'reward_id' => $rewardId
        ]);

        $lockedReward = $lockedRewardStmt->fetch();

        if (!$lockedReward) {
            throw new RuntimeException('This reward is no longer available.');
        }

        // Calculate latest points balance
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
                )
            FROM point_transactions
            WHERE user_id = :user_id
        ");

        $balanceStmt->execute([
            'user_id' => $userId
        ]);

        $availablePoints = (int) $balanceStmt->fetchColumn();

        $requiredPoints = (int) $lockedReward['required_points'];

        // Check sufficient points
        if ($availablePoints < $requiredPoints) {
            throw new RuntimeException(
                'You do not have enough points to redeem this reward.'
            );
        }

        // Generate a secure unique voucher code
        $voucherCode = 'VCH-' . strtoupper(
            bin2hex(random_bytes(8))
        );

        // Create reward redemption record
        $redemptionStmt = $pdo->prepare("
            INSERT INTO redemptions (
                user_id,
                reward_id,
                points_spent,
                voucher_code,
                status
            )
            VALUES (
                :user_id,
                :reward_id,
                :points_spent,
                :voucher_code,
                'generated'
            )
        ");

        $redemptionStmt->execute([
            'user_id' => $userId,
            'reward_id' => $rewardId,
            'points_spent' => $requiredPoints,
            'voucher_code' => $voucherCode
        ]);

        $redemptionId = (int) $pdo->lastInsertId();

        // Record points deduction in the ledger
        $transactionStmt = $pdo->prepare("
            INSERT INTO point_transactions (
                user_id,
                transaction_type,
                points_delta,
                redemption_id
            )
            VALUES (
                :user_id,
                'redeem',
                :points,
                :redemption_id
            )
        ");

        $transactionStmt->execute([
            'user_id' => $userId,
            'points' => $requiredPoints,
            'redemption_id' => $redemptionId
        ]);

        // Commit all operations together
        $pdo->commit();

        setFlashMessage(
            'success',
            'Reward redeemed successfully! Your voucher code is: ' . $voucherCode
        );

        redirect(
            '/customer-loyalty-rewards/public/customer/redeem-reward.php?reward_id='
            . $rewardId
        );

    } catch (Throwable $exception) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Reward redemption error: ' . $exception->getMessage()
        );

        $errorMessage = $exception->getMessage();
    }

} elseif (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && !verifyCsrfToken($_POST['csrf_token'] ?? null)
) {

    $errorMessage = 'Invalid request. Please refresh the page and try again.';
}

// Get success message after redirect
$flashMessage = getFlashMessage();

// Get current points balance for display
if ($reward) {

    $currentBalanceStmt = $pdo->prepare("
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
            )
        FROM point_transactions
        WHERE user_id = :user_id
    ");

    $currentBalanceStmt->execute([
        'user_id' => $userId
    ]);

    $availablePoints = (int) $currentBalanceStmt->fetchColumn();
}

require_once __DIR__ . '/../../app/includes/header.php';

?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-8 col-lg-6">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">

                        <h2 class="fw-bold">Confirm Reward Redemption</h2>

                        <p class="text-muted">
                            Review your reward details before confirming.
                        </p>

                    </div>

                    <?php if ($flashMessage): ?>

                        <div class="alert alert-success">

                            <?php echo e($flashMessage['message']); ?>

                        </div>

                    <?php endif; ?>

                    <?php if ($errorMessage !== ''): ?>

                        <div class="alert alert-danger">
                            <?php echo e($errorMessage); ?>
                        </div>

                    <?php endif; ?>

                    <?php if ($reward): ?>

                        <div class="bg-light rounded p-4 mb-4">

                            <h4 class="fw-bold mb-2">
                                <?php echo e($reward['reward_name']); ?>
                            </h4>

                            <p class="text-muted mb-3">
                                <?php
                                    echo e(
                                        $reward['description']
                                        ?: 'Redeem your loyalty points for this reward.'
                                    );
                                ?>
                            </p>

                            <div class="d-flex justify-content-between mb-2">

                                <span>Required Points</span>

                                <strong>
                                    <?php
                                        echo number_format(
                                            (int) $reward['required_points']
                                        );
                                    ?>
                                </strong>

                            </div>

                            <div class="d-flex justify-content-between">

                                <span>Available Points</span>

                                <strong class="text-primary">
                                    <?php echo number_format($availablePoints); ?>
                                </strong>

                            </div>

                        </div>

                        <?php if (
                            $availablePoints >= (int) $reward['required_points']
                        ): ?>

                            <form method="POST" action="">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo e(generateCsrfToken()); ?>"
                                >

                                <input
                                    type="hidden"
                                    name="reward_id"
                                    value="<?php echo (int) $reward['id']; ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-primary btn-lg w-100"
                                >
                                    Confirm Redemption
                                </button>

                            </form>

                        <?php else: ?>

                            <div class="alert alert-warning">
                                You do not have enough points for this reward.
                            </div>

                        <?php endif; ?>

                    <?php endif; ?>

                    <div class="text-center mt-4">

                        <a
                            href="/customer-loyalty-rewards/public/customer/rewards.php"
                            class="text-decoration-none"
                        >
                            Back to Rewards
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
