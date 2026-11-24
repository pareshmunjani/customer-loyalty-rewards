
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/includes/csrf.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

$pdo = getPDO();

$rewardName = '';
$description = '';
$requiredPoints = '';
$status = 'active';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    }

    $rewardName = trim($_POST['reward_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requiredPoints = trim($_POST['required_points'] ?? '');
    $status = $_POST['status'] ?? 'active';

    // Validate reward name.
    if ($rewardName === '') {
        $errors[] = 'Reward name is required.';
    } elseif (mb_strlen($rewardName) > 150) {
        $errors[] = 'Reward name cannot exceed 150 characters.';
    }

    // Validate description.
    if (mb_strlen($description) > 1000) {
        $errors[] = 'Description cannot exceed 1000 characters.';
    }

    // Validate required points.
    if (
        $requiredPoints === '' ||
        !ctype_digit($requiredPoints) ||
        (int) $requiredPoints < 1 ||
        (int) $requiredPoints > 1000000
    ) {
        $errors[] = 'Required points must be a whole number between 1 and 1,000,000.';
    }

    // Validate status.
    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Please select a valid reward status.';
    }

    // Insert reward if validation succeeds.
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO rewards
                    (reward_name, description, required_points, status)
                 VALUES
                    (:reward_name, :description, :required_points, :status)"
            );

            $stmt->execute([
                'reward_name' => $rewardName,
                'description' => $description,
                'required_points' => (int) $requiredPoints,
                'status' => $status
            ]);

            setFlashMessage('success', 'Reward added successfully.');

            redirect('/customer-loyalty-rewards/public/admin/rewards/index.php');

        } catch (PDOException $exception) {
            error_log('Add reward error: ' . $exception->getMessage());
            $errors[] = 'Unable to save the reward. Please try again.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Reward | Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/style.css">

    <style>
        body {
            background-color: #f5f7fb;
        }

        .page-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 28px;
        }

        .form-label {
            font-weight: 600;
        }

        .form-text {
            color: #6c757d;
        }
    </style>
</head>

<body>

<header class="bg-white border-bottom py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Customer Loyalty Rewards</h5>
            <small class="text-muted">Add Reward</small>
        </div>

        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            Back to Rewards
        </a>
    </div>
</header>

<main class="container py-4">

    <div class="page-card">

        <h3 class="fw-bold mb-1">Add New Reward</h3>
        <p class="text-muted mb-4">
            Enter reward details and the points required for redemption.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(generateCsrfToken()) ?>"
            >

            <div class="mb-3">
                <label for="reward_name" class="form-label">
                    Reward Name
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="reward_name"
                    name="reward_name"
                    maxlength="150"
                    value="<?= e($rewardName) ?>"
                    placeholder="Example: Shopping Voucher - ₹500"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">
                    Reward Description
                </label>

                <textarea
                    class="form-control"
                    id="description"
                    name="description"
                    rows="4"
                    maxlength="1000"
                    placeholder="Enter a short reward description"
                ><?= e($description) ?></textarea>

                <div class="form-text">
                    Description is optional.
                </div>
            </div>

            <div class="mb-3">
                <label for="required_points" class="form-label">
                    Required Points
                </label>

                <input
                    type="number"
                    class="form-control"
                    id="required_points"
                    name="required_points"
                    min="1"
                    max="1000000"
                    step="1"
                    value="<?= e($requiredPoints) ?>"
                    placeholder="Example: 1000"
                    required
                >

                <div class="form-text">
                    Number of customer points needed to redeem this reward.
                </div>
            </div>

            <div class="mb-4">
                <label for="status" class="form-label">
                    Reward Status
                </label>

                <select
                    class="form-select"
                    id="status"
                    name="status"
                    required
                >
                    <option value="active"
                        <?= $status === 'active' ? 'selected' : '' ?>>
                        Active
                    </option>

                    <option value="inactive"
                        <?= $status === 'inactive' ? 'selected' : '' ?>>
                        Inactive
                    </option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    Save Reward
                </button>

                <a href="index.php" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>

        </form>

    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
