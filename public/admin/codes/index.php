
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

        .section-icon {
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #edf2ff;
            color: var(--primary-blue);
            border-radius: 12px;
            font-size: 21px;
            flex-shrink: 0;
        }

        .page-card {
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
            padding: 11px 21px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: var(--navy);
            border-color: var(--navy);
        }

        .btn-outline-secondary {
            border-radius: 9px;
            font-weight: 600;
        }

        .info-box {
            background: #f5f8ff;
            border: 1px solid #e3eaff;
            border-radius: 12px;
            padding: 20px;
        }

        .info-box h6 {
            color: var(--navy-dark);
        }

        .info-box ul {
            padding-left: 20px;
            color: #53617a;
        }

        .info-box li {
            margin-bottom: 7px;
        }

        .info-box li:last-child {
            margin-bottom: 0;
        }

        .points-preview {
            display: none;
            background: #edf2ff;
            border: 1px solid #dce6ff;
            color: var(--navy);
            border-radius: 9px;
            padding: 12px 15px;
            font-weight: 600;
        }

        .alert {
            border-radius: 10px;
        }

        @media (max-width: 576px) {
            .page-card {
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

            <div class="brand-subtitle">
                Admin Panel / Reward Code Generation
            </div>
        </div>

        <a href="../dashboard.php"
           class="btn btn-outline-secondary btn-sm">
            Admin Dashboard
        </a>

    </div>
</header>

<main class="container py-4 py-md-5">

    <div class="mb-4">
        <div class="d-flex align-items-center gap-3">

            <div class="section-icon">
                &#128273;
            </div>

            <div>
                <h2 class="page-heading mb-1">
                    Generate Reward Codes
                </h2>

                <p class="page-description mb-0">
                    Generate unique reward codes for physical product boxes.
                </p>
            </div>

        </div>
    </div>

    <?php if ($flashMessage): ?>
        <div class="alert alert-<?= e($flashMessage['type']) ?> border-0">
            <?= e($flashMessage['message']) ?>
        </div>
    <?php endif; ?>

    <div class="page-card">

        <div class="mb-4">
            <h5 class="fw-bold mb-1">New Code Generation</h5>

            <p class="text-muted small mb-0">
                Select a product and specify how many unique reward codes you want to generate.
            </p>
        </div>

        <?php if (empty($products)): ?>

            <div class="alert alert-warning mb-0">
                <strong>No active products available.</strong>
                <p class="mb-0 mt-1">
                    Please add or activate a product before generating reward codes.
                </p>
            </div>

        <?php else: ?>

            <form method="POST" action="generate.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(generateCsrfToken()) ?>"
                >

                <div class="row">

                    <div class="col-lg-8">

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

                            <div class="form-text">
                                Only active products are displayed here.
                            </div>

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

                        <div id="pointsPreview" class="points-preview mb-4">
                            Points per generated code:
                            <span id="selectedPoints">0</span> points
                        </div>

                    </div>

                </div>

                <div class="info-box mb-4">

                    <h6 class="fw-bold mb-3">
                        Important Information
                    </h6>

                    <ul class="mb-0">
                        <li>Every generated code will be unique.</li>
                        <li>Each code will be linked to the selected product.</li>
                        <li>Points will be saved with each generated code.</li>
                        <li>Only code hashes will be stored in the database.</li>
                        <li>Original codes will be available for download after generation.</li>
                    </ul>

                </div>

                <button type="submit" class="btn btn-primary">
                    &#10010; Generate Reward Codes
                </button>

            </form>

        <?php endif; ?>

    </div>

</main>

<script>
    const productSelect = document.getElementById('product_id');
    const pointsPreview = document.getElementById('pointsPreview');
    const selectedPoints = document.getElementById('selectedPoints');

    if (productSelect) {
        productSelect.addEventListener('change', function () {

            const selectedOption = this.options[this.selectedIndex];

            if (!selectedOption.value) {
                pointsPreview.style.display = 'none';
                return;
            }

            const points = selectedOption.dataset.points;

            selectedPoints.textContent = points;
            pointsPreview.style.display = 'block';
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
