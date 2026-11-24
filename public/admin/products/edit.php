
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/includes/csrf.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

$pdo = getPDO();

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    setFlashMessage('error', 'Invalid product ID.');
    redirect('/customer-loyalty-rewards/public/admin/products/index.php');
}

// Fetch the existing product.
$stmt = $pdo->prepare(
    "SELECT id, product_name, description, points_per_box, status
     FROM products
     WHERE id = :id"
);

$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlashMessage('error', 'Product not found.');
    redirect('/customer-loyalty-rewards/public/admin/products/index.php');
}

$productName = $product['product_name'];
$description = $product['description'] ?? '';
$pointsPerBox = (string) $product['points_per_box'];
$status = $product['status'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    }

    $productName = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $pointsPerBox = trim($_POST['points_per_box'] ?? '');
    $status = $_POST['status'] ?? '';

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

    // Validate points.
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

    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare(
                "UPDATE products
                 SET product_name = :product_name,
                     description = :description,
                     points_per_box = :points_per_box,
                     status = :status
                 WHERE id = :id"
            );

            $updateStmt->execute([
                'product_name' => $productName,
                'description' => $description,
                'points_per_box' => (int) $pointsPerBox,
                'status' => $status,
                'id' => $productId
            ]);

            setFlashMessage('success', 'Product updated successfully.');

            redirect('/customer-loyalty-rewards/public/admin/products/index.php');

        } catch (PDOException $exception) {
            error_log('Edit product error: ' . $exception->getMessage());
            $errors[] = 'Unable to update the product. Please try again.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Product | Admin</title>

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
            <small class="text-muted">Edit Product</small>
        </div>

        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            Back to Products
        </a>
    </div>
</header>

<main class="container py-4">

    <div class="page-card">

        <h3 class="fw-bold mb-1">Edit Product</h3>
        <p class="text-muted mb-4">
            Update the product details and points awarded per box.
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
                ><?= e($description) ?></textarea>
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
                    required
                >

                <div class="form-text">
                    This value will be used for newly generated reward codes.
                    Existing codes retain their saved points value.
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
                    Update Product
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
