
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/includes/csrf.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage('error', 'Invalid security token. Please try again.');
    redirect('/customer-loyalty-rewards/public/admin/rewards/index.php');
}

$rewardId = filter_input(INPUT_POST, 'reward_id', FILTER_VALIDATE_INT);

if (!$rewardId || $rewardId < 1) {
    setFlashMessage('error', 'Invalid reward ID.');
    redirect('/customer-loyalty-rewards/public/admin/rewards/index.php');
}

$pdo = getPDO();

try {
    // Find the reward.
    $stmt = $pdo->prepare(
        "SELECT id, status
         FROM rewards
         WHERE id = :id"
    );

    $stmt->execute(['id' => $rewardId]);
    $reward = $stmt->fetch();

    if (!$reward) {
        setFlashMessage('error', 'Reward not found.');
        redirect('/customer-loyalty-rewards/public/admin/rewards/index.php');
    }

    // Toggle the current status.
    $newStatus = $reward['status'] === 'active'
        ? 'inactive'
        : 'active';

    $updateStmt = $pdo->prepare(
        "UPDATE rewards
         SET status = :status
         WHERE id = :id"
    );

    $updateStmt->execute([
        'status' => $newStatus,
        'id' => $rewardId
    ]);

    $message = $newStatus === 'active'
        ? 'Reward activated successfully.'
        : 'Reward deactivated successfully.';

    setFlashMessage('success', $message);

} catch (PDOException $exception) {
    error_log('Toggle reward status error: ' . $exception->getMessage());
    setFlashMessage('error', 'Unable to update reward status.');
}

redirect('/customer-loyalty-rewards/public/admin/rewards/index.php');
