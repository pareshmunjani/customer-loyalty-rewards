
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/includes/csrf.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

$pdo = getPDO();

$rewards = [];
$errorMessage = '';
$successMessage = '';

$flashMessage = getFlashMessage();

if ($flashMessage) {
    if ($flashMessage['type'] === 'success') {
        $successMessage = $flashMessage['message'];
    } elseif ($flashMessage['type'] === 'error') {
        $errorMessage = $flashMessage['message'];
    }
}

try {
    $stmt = $pdo->query(
        "SELECT id, reward_name, description, required_points, status, created_at
         FROM rewards
         ORDER BY id DESC"
    );

    $rewards = $stmt->fetchAll();

} catch (PDOException $exception) {
    error_log('Reward listing error: ' . $exception->getMessage());
    $errorMessage = 'Unable to load rewards. Please try again.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reward Management | Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/style.css">

    <style>
        body {
            background-color: #f5f7fb;
        }

        .page-header,
        .content-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
        }

        .content-card {
            padding: 22px;
        }

        .table th {
            color: #6c757d;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .reward-description {
            max-width: 280px;
        }
    </style>
</head>

<body>

<header class="bg-white border-bottom py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Customer Loyalty Rewards</h5>
            <small class="text-muted">Reward Management</small>
        </div>

        <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">
            Back to Dashboard
        </a>
    </div>
</header>

<main class="container py-4">

    <div class="page-header p-4 mb-4">
        <h3 class="fw-bold mb-1">Reward Management</h3>
        <p class="text-muted mb-0">
            View and manage rewards available to customers.
        </p>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= e($successMessage) ?>
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= e($errorMessage) ?>
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="content-card">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">All Rewards</h5>
                <small class="text-muted">
                    Total rewards: <?= count($rewards) ?>
                </small>
            </div>

            <a href="add.php" class="btn btn-primary">
                + Add New Reward
            </a>
        </div>

        <?php if (empty($rewards)): ?>

            <div class="text-center py-5">
                <h5>No rewards found</h5>
                <p class="text-muted mb-0">
                    Rewards will appear here after they are added.
                </p>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reward Name</th>
                            <th>Description</th>
                            <th>Required Points</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($rewards as $reward): ?>

                            <tr>
                                <td><?= (int) $reward['id'] ?></td>

                                <td>
                                    <strong>
                                        <?= e($reward['reward_name']) ?>
                                    </strong>
                                </td>

                                <td class="reward-description">
                                    <?= e($reward['description'] ?? '') ?>
                                </td>

                                <td>
                                    <?= number_format((int) $reward['required_points']) ?>
                                    points
                                </td>

                                <td>
                                    <?php if ($reward['status'] === 'active'): ?>
                                        <span class="badge text-bg-success">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= e(date(
                                        'd M Y',
                                        strtotime($reward['created_at'])
                                    )) ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">

                                        <a
                                            href="edit.php?id=<?= (int) $reward['id'] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            action="toggle-status.php"
                                            method="POST"
                                            class="m-0"
                                            onsubmit="return confirm('Are you sure you want to change this reward status?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(generateCsrfToken()) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="reward_id"
                                                value="<?= (int) $reward['id'] ?>"
                                            >

                                            <?php if ($reward['status'] === 'active'): ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                >
                                                    Deactivate
                                                </button>

                                            <?php else: ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-success"
                                                >
                                                    Activate
                                                </button>

                                            <?php endif; ?>

                                        </form>

                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>
            </div>

        <?php endif; ?>

    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
