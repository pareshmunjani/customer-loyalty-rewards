
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/includes/csrf.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

$pdo = getPDO();

$rewardId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$rewardId || $rewardId < 1) {
    setFlashMessage('error', 'Invalid reward ID.');
    redirect('/customer-loyalty-rewards/public/admin/rewards/index.php');
}

$stmt = $pdo->prepare(
    "SELECT id, reward_name, description, required_points, status
     FROM rewards
     WHERE id = :id"
);

$stmt->execute(['id' => $rewardId]);
$reward = $stmt->fetch();

if (!$reward) {
    setFlashMessage('error', 'Reward not found.');
    redirect('/customer-loyalty-rewards/public/admin/rewards/index.php');
}

$rewardName = $reward['reward_name'];
$description = $reward['description'] ?? '';
$requiredPoints = (string) $reward['required_points'];
$status = $reward['status'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    }

    $rewardName = trim($_POST['reward_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requiredPoints = trim($_POST['required_points'] ?? '');
    $status = $_POST['status'] ?? '';

    if ($rewardName === '') {
        $errors[] = 'Reward name is required.';
    } elseif (mb_strlen($rewardName) > 150) {
        $errors[] = 'Reward name cannot exceed 150 characters.';
    }

    if (mb_strlen($description) > 1000) {
        $errors[] = 'Description cannot exceed 1000 characters.';
    }

    if (
        $requiredPoints === '' ||
        !ctype_digit($requiredPoints) ||
        (int) $requiredPoints < 1 ||
        (int) $requiredPoints > 1000000
    ) {
        $errors[] = 'Required points must be a whole number between 1 and 1,000,000.';
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Please select a valid reward status.';
    }

    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare(
                "UPDATE rewards
                 SET reward_name = :reward_name,
                     description = :description,
                     required_points = :required_points,
                     status = :status
                 WHERE id = :id"
            );

            $updateStmt->execute([
                'reward_name' => $rewardName,
                'description' => $description,
                'required_points' => (int) $requiredPoints,
                'status' => $status,
                'id' => $rewardId
            ]);

            setFlashMessage('success', 'Reward updated successfully.');

            redirect('/customer-loyalty-rewards/public/admin/rewards/index.php');

        } catch (PDOException $exception) {
            error_log('Edit reward error: ' . $exception->getMessage());
            $errors[] = 'Unable to update the reward. Please try again.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Reward | Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/style.css?v=20261125">

    <style>
        :root {
            --navy: #253b80;
            --navy-dark: #172b61;
            --primary-blue: #4169e1;
            --page-bg: #f3f6fc;
            --border-color: #e5eaf3;
            --muted: #6b7280;
        }

        body {
            background: var(--page-bg);
            color: #26334d;
            min-height: 100vh;
        }

        .admin-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 2px 10px rgba(23, 43, 97, 0.04);
        }

        .brand-name {
            color: var(--navy-dark);
            font-weight: 750;
            letter-spacing: -0.3px;
        }

        .brand-subtitle {
            color: var(--muted);
            font-size: 12px;
        }

        .page-heading {
            color: var(--navy-dark);
            font-weight: 750;
            letter-spacing: -0.5px;
        }

        .page-description {
            color: var(--muted);
        }

        .form-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 24px rgba(23, 43, 97, 0.05);
        }

        .form-label {
            color: #26334d;
            font-weight: 650;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border: 1px solid #dce3ef;
            border-radius: 9px;
            padding: 11px 13px;
            color: #26334d;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(65, 105, 225, 0.12);
        }

        .form-text {
            color: var(--muted);
            font-size: 13px;
        }

        .btn-primary {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            border-radius: 9px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: var(--navy);
            border-color: var(--navy);
        }

        .btn-outline-secondary {
            border-radius: 9px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .section-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #edf2ff;
            color: var(--primary-blue);
            border-radius: 11px;
            font-size: 20px;
        }

        .info-panel {
            background: #f5f8ff;
            border: 1px solid #e3eaff;
            border-radius: 12px;
            padding: 16px;
        }

        @media (max-width: 576px) {
            .form-card {
                padding: 21px;
            }

            .page-heading {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<header class="admin-navbar py-3">
    <div class="container d-flex justify-content-between align-items-center gap-3">
        <div>
            <a href="../../index.php"
               class="text-decoration-none brand-name">
                Customer Loyalty Rewards
            </a>
            <div class="brand-subtitle">Admin Panel / Edit Reward</div>
        </div>

        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            &larr; Back to Rewards
        </a>
    </div>
</header>

<main class="container py-4 py-md-5">

    <div class="mb-4">
        <div class="d-flex align-items-center gap-3 mb-2">
            <div class="section-icon">
                &#9998;
            </div>

            <div>
                <h2 class="page-heading mb-1">Edit Reward</h2>
                <p class="page-description mb-0">
                    Update reward details and redemption requirements.
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">

            <div class="form-card">

                <div class="mb-4">
                    <h5 class="fw-bold mb-1">Reward Information</h5>
                    <p class="text-muted small mb-0">
                        Modify the reward details below and save your changes.
                    </p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 rounded-3">
                        <strong>Please check the following:</strong>
                        <ul class="mb-0 mt-2">
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

                    <div class="mb-4">
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

                    <div class="mb-4">
                        <label for="description" class="form-label">
                            Reward Description
                            <span class="text-muted fw-normal">(Optional)</span>
                        </label>

                        <textarea
                            class="form-control"
                            id="description"
                            name="description"
                            rows="4"
                            maxlength="1000"
                            placeholder="Enter a short reward description..."
                        ><?= e($description) ?></textarea>

                        <div class="form-text">
                            Briefly explain what the customer will receive.
                        </div>
                    </div>

                    <div class="mb-4">
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
                            required
                        >

                        <div class="form-text">
                            Points required for future reward redemptions.
                            Existing redemption records remain unchanged.
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

                        <div class="form-text">
                            Only active rewards are available to customers.
                        </div>
                    </div>

                    <div class="info-panel mb-4">
                        <div class="d-flex gap-2">
                            <span>&#8505;&#65039;</span>
                            <div>
                                <strong class="d-block mb-1">Important</strong>
                                <small class="text-muted">
                                    Changing the required points affects future
                                    redemptions. Previously completed redemptions
                                    keep their original points spent.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            Update Reward
                        </button>

                        <a href="index.php" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>

                </form>

            </div>

        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
