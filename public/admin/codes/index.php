
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/includes/csrf.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

$pdo = getPDO();

// Fetch active products.
$stmt = $pdo->query(
    "SELECT id, product_name, points_per_box
     FROM products
     WHERE status = 'active'
     ORDER BY product_name ASC"
);

$products = $stmt->fetchAll();

$flashMessage = getFlashMessage();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Generate Reward Codes | Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

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

        .info-box {
            background-color: #eef5ff;
            border: 1px solid #d7e7ff;
            border-radius: 8px;
            padding: 15px;
        }
    </style>
</head>

<body>

<header class="bg-white border-bottom py-3">
    <div class="container d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-0">Customer Loyalty Rewards</h5>
            <small class="text-muted">Admin / Reward Code Generation</small>
        </div>

        <a
            href="../dashboard.php"
            class="btn btn-outline-secondary btn-sm"
        >
            Admin Dashboard
        </a>

    </div>
</header>

<main class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-1">Generate Reward Codes</h3>
            <p class="text-muted mb-0">
                Generate unique reward codes for physical product boxes.
            </p>
        </div>

        <a
            href="../dashboard.php"
            class="btn btn-outline-secondary"
        >
            Back
        </a>

    </div>

    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= e($flashMessage['type']) ?>">
            <?= e($flashMessage['message']) ?>
        </div>
    <?php endif; ?>

    <div class="page-card">

        <h5 class="fw-bold mb-3">New Code Generation</h5>

        <?php if (empty($products)): ?>

            <div class="alert alert-warning mb-0">
                No active products are available.
                Please add or activate a product before generating codes.
            </div>

        <?php else: ?>

            <form method="POST" action="generate.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(generateCsrfToken()) ?>"
                >

                <div class="mb-4">

                    <label for="product_id" class="form-label">
                        Select Product
                    </label>

                    <select
                        name="product_id"
                        id="product_id"
                        class="form-select"
                        required
                    >

                        <option value="">Choose a product</option>

                        <?php foreach ($products as $product): ?>

                            <option
                                value="<?= (int) $product['id'] ?>"
                                data-points="<?= (int) $product['points_per_box'] ?>"
                            >
                                <?= e($product['product_name']) ?>
                                (<?= (int) $product['points_per_box'] ?> points per box)
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="mb-4">

                    <label for="quantity" class="form-label">
                        Number of Codes
                    </label>

                    <input
                        type="number"
                        name="quantity"
                        id="quantity"
                        class="form-control"
                        min="1"
                        max="5000"
                        step="1"
                        placeholder="Enter quantity (1–5000)"
                        required
                    >

                    <div class="form-text">
                        You can generate a maximum of 5,000 codes in one batch.
                    </div>

                </div>

                <div class="info-box mb-4">

                    <h6 class="fw-bold">Important Information</h6>

                    <ul class="mb-0">
                        <li>Every generated code will be unique.</li>
                        <li>Each code will be linked to the selected product.</li>
                        <li>Points will be saved with each generated code.</li>
                        <li>Only code hashes will be stored in the database.</li>
                        <li>Original codes will be available for download after generation.</li>
                    </ul>

                </div>

                <button type="submit" class="btn btn-primary">
                    Generate Reward Codes
                </button>

            </form>

        <?php endif; ?>

    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
