
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/includes/csrf.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

$pdo = getPDO();

$products = [];
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
        "SELECT id, product_name, description, points_per_box, status, created_at
         FROM products
         ORDER BY id DESC"
    );

    $products = $stmt->fetchAll();

} catch (PDOException $exception) {
    error_log('Product listing error: ' . $exception->getMessage());
    $errorMessage = 'Unable to load products. Please try again.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Product Management | Admin</title>

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

        .product-description {
            max-width: 280px;
        }
    </style>
</head>

<body>

<header class="bg-white border-bottom py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Customer Loyalty Rewards</h5>
            <small class="text-muted">Product Management</small>
        </div>

        <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">
            Back to Dashboard
        </a>
    </div>
</header>

<main class="container py-4">

    <div class="page-header p-4 mb-4">
        <h3 class="fw-bold mb-1">Product Management</h3>
        <p class="text-muted mb-0">
            View and manage products available in the loyalty rewards system.
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
                <h5 class="mb-1">All Products</h5>
                <small class="text-muted">
                    Total products: <?= count($products) ?>
                </small>
            </div>

            <a href="add.php" class="btn btn-primary">
                + Add New Product
            </a>
        </div>

        <?php if (empty($products)): ?>

            <div class="text-center py-5">
                <h5>No products found</h5>
                <p class="text-muted mb-0">
                    Products will appear here after they are added.
                </p>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Points / Box</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($products as $product): ?>

                            <tr>
                                <td><?= (int) $product['id'] ?></td>

                                <td>
                                    <strong>
                                        <?= e($product['product_name']) ?>
                                    </strong>
                                </td>

                                <td class="product-description">
                                    <?= e($product['description'] ?? '') ?>
                                </td>

                                <td>
                                    <?= number_format((int) $product['points_per_box']) ?>
                                    points
                                </td>

                                <td>
                                    <?php if ($product['status'] === 'active'): ?>
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
                                        strtotime($product['created_at'])
                                    )) ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">

                                        <a
                                            href="edit.php?id=<?= (int) $product['id'] ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            action="toggle-status.php"
                                            method="POST"
                                            class="m-0"
                                            onsubmit="return confirm('Are you sure you want to change this product status?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(generateCsrfToken()) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int) $product['id'] ?>"
                                            >

                                            <?php if ($product['status'] === 'active'): ?>

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
