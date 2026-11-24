
<?php

require_once __DIR__ . '/../../app/includes/functions.php';

startSecureSession();

require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/csrf.php';
require_once __DIR__ . '/../../app/config/database.php';

requireCustomer();

$pageTitle = 'Redeem Reward Code';
$pdo = getPDO();

$errorMessage = '';
$submittedCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {

        $errorMessage = 'Invalid request. Please refresh the page and try again.';

    } else {

        // 2. Normalize the submitted code
        $submittedCode = strtoupper(trim($_POST['reward_code'] ?? ''));

        if ($submittedCode === '') {

            $errorMessage = 'Please enter your reward code.';

        } elseif (!preg_match('/^[A-Z0-9-]{4,64}$/', $submittedCode)) {

            $errorMessage = 'Please enter a valid reward code format.';

        } else {

            $codeHash = hash('sha256', $submittedCode);
            $userId = (int) $_SESSION['user_id'];

            try {

                // 3. Start database transaction
                $pdo->beginTransaction();

                // 4. Find and lock the reward code
                $stmt = $pdo->prepare("
                    SELECT
                        rc.id,
                        rc.points_value,
                        rc.status,
                        p.status AS product_status
                    FROM reward_codes rc
                    INNER JOIN products p
                        ON p.id = rc.product_id
                    WHERE rc.code_hash = :code_hash
                    FOR UPDATE
                ");

                $stmt->execute([
                    'code_hash' => $codeHash
                ]);

                $code = $stmt->fetch();

                // Helper to record unsuccessful attempts
                $recordAttempt = function (
                    string $attemptStatus
                ) use ($pdo, $userId, $codeHash): void {

                    $attemptStmt = $pdo->prepare("
                        INSERT INTO code_attempts (
                            user_id,
                            submitted_code_hash,
                            attempt_status
                        )
                        VALUES (
                            :user_id,
                            :code_hash,
                            :attempt_status
                        )
                    ");

                    $attemptStmt->execute([
                        'user_id' => $userId,
                        'code_hash' => $codeHash,
                        'attempt_status' => $attemptStatus
                    ]);
                };

                // 5. Check whether code exists
                if (!$code) {

                    $recordAttempt('invalid');

                    $pdo->commit();

                    $errorMessage = 'This reward code is invalid.';

                }

                // 6. Check whether code was already redeemed
                elseif ($code['status'] === 'redeemed') {

                    $recordAttempt('already_redeemed');

                    $pdo->commit();

                    $errorMessage = 'This reward code has already been redeemed.';

                }

                // 7. Check whether associated product is active
                elseif ($code['product_status'] !== 'active') {

                    $recordAttempt('inactive_product');

                    $pdo->commit();

                    $errorMessage = 'This product is currently inactive. The code cannot be redeemed.';

                }

                else {

                    // 8. Mark code as redeemed
                    $updateStmt = $pdo->prepare("
                        UPDATE reward_codes
                        SET
                            status = 'redeemed',
                            redeemed_by = :user_id,
                            redeemed_at = NOW()
                        WHERE
                            id = :code_id
                            AND status = 'unused'
                    ");

                    $updateStmt->execute([
                        'user_id' => $userId,
                        'code_id' => $code['id']
                    ]);

                    if ($updateStmt->rowCount() !== 1) {
                        throw new RuntimeException(
                            'Reward code could not be updated.'
                        );
                    }

                    // 9. Add earned points to the points ledger
                    $points = (int) $code['points_value'];

                    $transactionStmt = $pdo->prepare("
                        INSERT INTO point_transactions (
                            user_id,
                            transaction_type,
                            points_delta,
                            reward_code_id
                        )
                        VALUES (
                            :user_id,
                            'earn',
                            :points,
                            :code_id
                        )
                    ");

                    $transactionStmt->execute([
                        'user_id' => $userId,
                        'points' => $points,
                        'code_id' => $code['id']
                    ]);

                    // 10. Save both operations together
                    $pdo->commit();

                    setFlashMessage(
                        'success',
                        'Success! You earned ' . $points . ' points.'
                    );

                    redirect(
                        '/customer-loyalty-rewards/public/customer/redeem-code.php'
                    );
                }

            } catch (Throwable $exception) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log(
                    'Reward code redemption error: ' .
                    $exception->getMessage()
                );

                $errorMessage = 'Something went wrong while redeeming your code. Please try again.';
            }
        }
    }
}

// Retrieve success message after redirect
$flashMessage = getFlashMessage();

require_once __DIR__ . '/../../app/includes/header.php';

?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-8 col-lg-6">

            <div class="card shadow-sm border-0">

                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">

                        <h2 class="fw-bold">Redeem Your Reward Code</h2>

                        <p class="text-muted">
                            Enter the unique code found inside your product box
                            to earn loyalty points.
                        </p>

                    </div>

                    <?php if ($flashMessage): ?>

                        <div class="alert alert-<?php echo e($flashMessage['type']); ?>">
                            <?php echo e($flashMessage['message']); ?>
                        </div>

                    <?php endif; ?>

                    <?php if ($errorMessage !== ''): ?>

                        <div class="alert alert-danger">
                            <?php echo e($errorMessage); ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST" action="">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo e(generateCsrfToken()); ?>"
                        >

                        <div class="mb-4">

                            <label
                                for="reward_code"
                                class="form-label fw-semibold"
                            >
                                Reward Code
                            </label>

                            <input
                                type="text"
                                class="form-control form-control-lg text-uppercase"
                                id="reward_code"
                                name="reward_code"
                                placeholder="Enter your scratch-off code"
                                value="<?php echo e($submittedCode); ?>"
                                maxlength="64"
                                autocomplete="off"
                                required
                            >

                            <div class="form-text">
                                Enter the code exactly as printed inside your box.
                            </div>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg w-100"
                        >
                            Redeem Code
                        </button>

                    </form>

                    <div class="text-center mt-4">

                        <a
                            href="/customer-loyalty-rewards/public/customer/dashboard.php"
                            class="text-decoration-none"
                        >
                            Back to Dashboard
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
