
<?php

require_once __DIR__ . '/../../app/includes/functions.php';

startSecureSession();

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/csrf.php';
require_once __DIR__ . '/../../app/config/database.php';

requireCustomer();

$pageTitle = 'My Dashboard | Loyalty Rewards';

$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Customer';

try {

    $pdo = getPDO();

    // Calculate customer points summary
    $summaryQuery = "
        SELECT
            COALESCE(
                SUM(
                    CASE
                        WHEN transaction_type = 'earn'
                        THEN points_delta
                        ELSE 0
                    END
                ), 0
            ) AS total_earned,

            COALESCE(
                SUM(
                    CASE
                        WHEN transaction_type = 'redeem'
                        THEN points_delta
                        ELSE 0
                    END
                ), 0
            ) AS total_redeemed

        FROM point_transactions
        WHERE user_id = :user_id
    ";

    $summaryStmt = $pdo->prepare($summaryQuery);

    $summaryStmt->execute([
        'user_id' => $userId
    ]);

    $summary = $summaryStmt->fetch();

    $totalEarned = (int) $summary['total_earned'];
    $totalRedeemed = (int) $summary['total_redeemed'];

    $availablePoints = $totalEarned - $totalRedeemed;

    // Count successfully redeemed reward codes
    $codeQuery = "
        SELECT COUNT(*)
        FROM reward_codes
        WHERE redeemed_by = :user_id
          AND status = 'redeemed'
    ";

    $codeStmt = $pdo->prepare($codeQuery);

    $codeStmt->execute([
        'user_id' => $userId
    ]);

    $redeemedCodes = (int) $codeStmt->fetchColumn();

    // Get recent points transactions
    $transactionQuery = "
        SELECT
            pt.transaction_type,
            pt.points_delta,
            pt.created_at,
            p.product_name,
            r.reward_name

        FROM point_transactions pt

        LEFT JOIN reward_codes rc
            ON pt.reward_code_id = rc.id

        LEFT JOIN products p
            ON rc.product_id = p.id

        LEFT JOIN redemptions rd
            ON pt.redemption_id = rd.id

        LEFT JOIN rewards r
            ON rd.reward_id = r.id

        WHERE pt.user_id = :user_id

        ORDER BY pt.created_at DESC, pt.id DESC

        LIMIT 5
    ";

    $transactionStmt = $pdo->prepare($transactionQuery);

    $transactionStmt->execute([
        'user_id' => $userId
    ]);

    $recentTransactions = $transactionStmt->fetchAll();

    // Get available active rewards
    $rewardQuery = "
        SELECT
            id,
            reward_name,
            description,
            required_points

        FROM rewards

        WHERE status = 'active'

        ORDER BY required_points ASC
    ";

    $rewardStmt = $pdo->prepare($rewardQuery);

    $rewardStmt->execute();

    $availableRewards = $rewardStmt->fetchAll();

} catch (PDOException $e) {

    error_log('Customer dashboard error: ' . $e->getMessage());

    http_response_code(500);

    exit('Unable to load your dashboard. Please try again later.');
}

require_once __DIR__ . '/../../app/includes/header.php';

?>

<div class="container py-5">

    <!-- Dashboard Heading -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <h1 class="fw-bold mb-1">
                Welcome, <?= e($userName) ?>!
            </h1>

            <p class="text-muted mb-0">
                Here's an overview of your loyalty rewards account.
            </p>
        </div>

        <form
            method="POST"
            action="logout.php"
            class="mt-3 mt-md-0"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(generateCsrfToken()) ?>"
            >

            <button
                type="submit"
                class="btn btn-outline-danger"
            >
                Logout
            </button>

        </form>

    </div>

    <!-- Customer Quick Actions -->
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body p-4">

            <h4 class="fw-bold mb-3">
                Quick Actions
            </h4>

            <p class="text-muted mb-4">
                Access your loyalty rewards features.
            </p>

            <div class="row g-3">

                <div class="col-md-6 col-lg-3">

                    <a
                        href="redeem-code.php"
                        class="btn btn-primary w-100 py-3"
                    >
                        Redeem Code
                    </a>

                </div>

                <div class="col-md-6 col-lg-3">

                    <a
                        href="points-history.php"
                        class="btn btn-outline-primary w-100 py-3"
                    >
                        Points History
                    </a>

                </div>

                <div class="col-md-6 col-lg-3">

                    <a
                        href="rewards.php"
                        class="btn btn-outline-primary w-100 py-3"
                    >
                        Browse Rewards
                    </a>

                </div>

                <div class="col-md-6 col-lg-3">

                    <a
                        href="redemption-history.php"
                        class="btn btn-outline-primary w-100 py-3"
                    >
                        Redemption History
                    </a>

                </div>

            </div>

        </div>

    </div>

    <!-- Points Summary Cards -->
    <div class="row g-4 mb-5">

        <div class="col-md-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <p class="text-muted mb-2">
                        Available Points
                    </p>

                    <h2 class="fw-bold text-primary mb-0">
                        <?= number_format($availablePoints) ?>
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <p class="text-muted mb-2">
                        Total Points Earned
                    </p>

                    <h2 class="fw-bold text-success mb-0">
                        <?= number_format($totalEarned) ?>
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <p class="text-muted mb-2">
                        Points Redeemed
                    </p>

                    <h2 class="fw-bold text-warning mb-0">
                        <?= number_format($totalRedeemed) ?>
                    </h2>

                </div>

            </div>

        </div>

        <div class="col-md-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <p class="text-muted mb-2">
                        Codes Redeemed
                    </p>

                    <h2 class="fw-bold text-info mb-0">
                        <?= number_format($redeemedCodes) ?>
                    </h2>

                </div>

            </div>

        </div>

    </div>

    <!-- Recent Transactions -->
    <div class="card border-0 shadow-sm mb-5">

        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h4 class="fw-bold mb-0">
                    Recent Transactions
                </h4>

                <a
                    href="points-history.php"
                    class="text-decoration-none"
                >
                    View All
                </a>

            </div>

            <?php if (empty($recentTransactions)): ?>

                <div class="text-center py-4">

                    <p class="text-muted mb-0">
                        No transactions yet. Redeem a product code
                        to start earning points!
                    </p>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>Details</th>
                                <th>Points</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($recentTransactions as $transaction): ?>

                                <tr>

                                    <td>

                                        <?php if ($transaction['transaction_type'] === 'earn'): ?>

                                            <span class="badge bg-success">
                                                Earned
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-warning text-dark">
                                                Redeemed
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if ($transaction['transaction_type'] === 'earn'): ?>

                                            <?= e($transaction['product_name'] ?? 'Product purchase') ?>

                                        <?php else: ?>

                                            <?= e($transaction['reward_name'] ?? 'Reward redemption') ?>

                                        <?php endif; ?>

                                    </td>

                                    <td class="fw-semibold">

                                        <?php if ($transaction['transaction_type'] === 'earn'): ?>

                                            <span class="text-success">
                                                +<?= number_format((int) $transaction['points_delta']) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="text-danger">
                                                -<?= number_format((int) $transaction['points_delta']) ?>
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?= e(date(
                                            'd M Y, h:i A',
                                            strtotime($transaction['created_at'])
                                        )) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <!-- Available Rewards -->
    <div class="card border-0 shadow-sm">

        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h4 class="fw-bold mb-0">
                    Available Rewards
                </h4>

                <a
                    href="rewards.php"
                    class="text-decoration-none"
                >
                    View All
                </a>

            </div>

            <?php if (empty($availableRewards)): ?>

                <p class="text-muted mb-0">
                    No active rewards are available right now.
                </p>

            <?php else: ?>

                <div class="row g-3">

                    <?php foreach ($availableRewards as $reward): ?>

                        <div class="col-md-6 col-lg-4">

                            <div class="border rounded-3 p-3 h-100">

                                <h5 class="fw-semibold">
                                    <?= e($reward['reward_name']) ?>
                                </h5>

                                <p class="text-muted small">
                                    <?= e($reward['description']) ?>
                                </p>

                                <p class="fw-bold text-primary mb-0">
                                    <?= number_format((int) $reward['required_points']) ?>
                                    Points Required
                                </p>

                                <?php if ((int) $reward['required_points'] <= $availablePoints): ?>

                                    <span class="badge bg-success mt-2">
                                        Points Available
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-secondary mt-2">
                                        Keep Earning
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php

require_once __DIR__ . '/../../app/includes/footer.php';

?>
