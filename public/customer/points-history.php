
<?php

require_once __DIR__ . '/../../app/includes/functions.php';

startSecureSession();

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/config/database.php';

requireCustomer();

$pageTitle = 'Points History | Loyalty Rewards';

$userId = (int) $_SESSION['user_id'];

try {

    $pdo = getPDO();

    // Fetch all transactions belonging to this customer
    $query = "
        SELECT
            pt.id,
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
    ";

    $stmt = $pdo->prepare($query);

    $stmt->execute([
        'user_id' => $userId
    ]);

    $transactions = $stmt->fetchAll();

    // Calculate transaction totals
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

} catch (PDOException $e) {

    error_log('Points history error: ' . $e->getMessage());

    http_response_code(500);

    exit('Unable to load points history. Please try again later.');
}

require_once __DIR__ . '/../../app/includes/header.php';

?>

<div class="container py-5">

    <!-- Page Heading -->

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>

            <h1 class="fw-bold mb-1">
                Points History
            </h1>

            <p class="text-muted mb-0">
                View all your earned and redeemed points.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="btn btn-outline-primary mt-3 mt-md-0"
        >
            &larr; Back to Dashboard
        </a>

    </div>


    <!-- Points Summary -->

    <div class="row g-4 mb-5">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <p class="text-muted mb-2">
                        Total Earned
                    </p>

                    <h2 class="fw-bold text-success mb-0">
                        <?= number_format($totalEarned) ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <p class="text-muted mb-2">
                        Total Redeemed
                    </p>

                    <h2 class="fw-bold text-danger mb-0">
                        <?= number_format($totalRedeemed) ?>
                    </h2>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <p class="text-muted mb-2">
                        Available Balance
                    </p>

                    <h2 class="fw-bold text-primary mb-0">
                        <?= number_format($availablePoints) ?>
                    </h2>

                </div>

            </div>

        </div>

    </div>


    <!-- Complete Transaction History -->

    <div class="card border-0 shadow-sm">

        <div class="card-body p-4">

            <h4 class="fw-bold mb-4">
                All Transactions
            </h4>

            <?php if (empty($transactions)): ?>

                <div class="text-center py-5">

                    <h5 class="text-muted">
                        No transactions yet
                    </h5>

                    <p class="text-muted mb-0">
                        Your points activity will appear here
                        after you redeem your first product code.
                    </p>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>

                            <tr>
                                <th>Transaction ID</th>
                                <th>Activity</th>
                                <th>Details</th>
                                <th>Points</th>
                                <th>Date & Time</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($transactions as $transaction): ?>

                                <tr>

                                    <td>
                                        #<?= (int) $transaction['id'] ?>
                                    </td>

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

</div>

<?php

require_once __DIR__ . '/../../app/includes/footer.php';

?>
