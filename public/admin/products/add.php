
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/includes/csrf.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

$pdo = getPDO();

$productName = '';
$description = '';
$pointsPerBox = '';
$status = 'active';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    }

    $productName = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $pointsPerBox = trim($_POST['points_per_box'] ?? '');
    $status = $_POST['status'] ?? 'active';

    // Validate product name.
    if ($productName === '') {
        $errors[] = 'Product name is required.';
    } elseif (mb_strlen($productName) > 150) {
        $errors[] = 'Product name cannot exceed 150 characters.';
    }

    // Validate description.
    if (mb_strlen($description) > 1000) {
        $errors[] = 'Description cannot exceed 1000 characters.';
    }

    // Validate points per box.
    if (
        $pointsPerBox === '' ||
        !ctype_digit($pointsPerBox) ||
        (int) $pointsPerBox < 1 ||
        (int) $pointsPerBox > 1000000
    ) {
        $errors[] = 'Points per box must be a whole number between 1 and 1,000,000.';
    }

    // Validate status.
    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Please select a valid product status.';
    }

    // Insert product if validation succeeds.
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO products
                    (product_name, description, points_per_box, status)
                 VALUES
                    (:product_name, :description, :points_per_box, :status)"
            );

            $stmt->execute([
                'product_name' => $productName,
                'description' => $description,
                'points_per_box' => (int) $pointsPerBox,
                'status' => $status
            ]);

            setFlashMessage('success', 'Product added successfully.');

            redirect('/customer-loyalty-rewards/public/admin/products/index.php');

        } catch (PDOException $exception) {
            error_log('Add product error: ' . $exception->getMessage());
            $errors[] = 'Unable to save the product. Please try again.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product | Admin</title>

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
            <small class="text-muted">Add Product</small>
        </div>

        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            Back to Products
        </a>
    </div>
</header>

<main class="container py-4">

    <div class="page-card">

        <h3 class="fw-bold mb-1">Add New Product</h3>
        <p class="text-muted mb-4">
            Enter product details and the fixed points awarded for each box.
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
                <label for="product_name" class="form-label">
                    Product Name
                </label>

                <input
                    type="text"
                    class="form-control"
                    id="product_name"
                    name="product_name"
                    maxlength="150"
                    value="<?= e($productName) ?>"
                    placeholder="Example: Premium Tea Box"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">
                    Product Description
                </label>

                <textarea
                    class="form-control"
                    id="description"
                    name="description"
                    rows="4"
                    maxlength="1000"
                    placeholder="Enter a short product description"
                ><?= e($description) ?></textarea>

                <div class="form-text">
                    Description is optional.
                </div>
            </div>

            <div class="mb-3">
                <label for="points_per_box" class="form-label">
                    Points Per Box
                </label>

                <input
                    type="number"
                    class="form-control"
                    id="points_per_box"
                    name="points_per_box"
                    min="1"
                    max="1000000"
                    step="1"
                    value="<?= e($pointsPerBox) ?>"
                    placeholder="Example: 20"
                    required
                >

                <div class="form-text">
                    Each successfully redeemed code for this product
                    will award this fixed number of points.
                </div>
            </div>

            <div class="mb-4">
                <label for="status" class="form-label">
                    Product Status
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
                    Save Product
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
