
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

    <title>Product Management | Customer Loyalty Rewards</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../../assets/css/style.css?v=20261125"
    >

    <style>
        body {
            background: #f3f6fc;
            color: #253b80;
        }

        /* Header */

        .admin-navbar {
            min-height: 82px;
            background: #ffffff;
            border-bottom: 1px solid #e8edf5;
            box-shadow: 0 4px 20px rgba(30, 41, 59, 0.04);
        }

        .admin-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #253b80;
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.6px;
            text-decoration: none;
        }

        .admin-brand:hover {
            color: #253b80;
        }

        .admin-brand-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 43px;
            height: 43px;
            border-radius: 13px;
            background: linear-gradient(135deg, #253b80, #4169e1);
            color: #ffffff;
            font-size: 1.4rem;
            box-shadow: 0 5px 12px rgba(37, 59, 128, 0.2);
        }

        .admin-brand small {
            display: block;
            margin-top: 5px;
            color: #94a3b8;
            font-size: 0.59rem;
            font-weight: 700;
            letter-spacing: 1.6px;
        }

        .back-dashboard {
            border: 1px solid #d8e1f2;
            border-radius: 9px;
            padding: 9px 16px;
            color: #253b80;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .back-dashboard:hover {
            background: #eef2ff;
            border-color: #c5d2f5;
            color: #253b80;
        }

        /* Main content */

        .admin-main {
            padding-top: 38px;
            padding-bottom: 55px;
        }

        .page-heading {
            margin-bottom: 28px;
        }

        .page-label {
            display: inline-block;
            margin-bottom: 12px;
            padding: 7px 15px;
            border-radius: 30px;
            background: #eaf0ff;
            color: #4169e1;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .page-heading h1 {
            color: #172b61;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 8px;
        }

        .page-heading p {
            color: #64748b;
            font-size: 0.95rem;
            margin: 0;
        }

        /* Content card */

        .content-card {
            padding: 26px;
            background: #ffffff;
            border: 1px solid #e8edf5;
            border-radius: 17px;
            box-shadow: 0 8px 25px rgba(37, 59, 128, 0.045);
        }

        .section-title {
            color: #253b80;
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .section-description {
            color: #64748b;
            font-size: 0.84rem;
            margin: 0;
        }

        .product-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 30px;
            margin-left: 7px;
            padding: 0 10px;
            border-radius: 20px;
            background: #eef2ff;
            color: #4169e1;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .add-product-btn {
            padding: 10px 17px;
            border: none;
            border-radius: 9px;
            background: linear-gradient(135deg, #253b80, #3655aa);
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .add-product-btn:hover {
            background: #1c2f6c;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 14px rgba(37, 59, 128, 0.2);
        }

        /* Product table */

        .product-table {
            margin-top: 25px;
            margin-bottom: 0;
            vertical-align: middle;
        }

        .product-table thead th {
            padding: 14px 13px;
            background: #f7f9fe;
            border-bottom: 1px solid #e5ebf5;
            color: #64748b;
            font-size: 0.77rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .product-table tbody td {
            padding: 17px 13px;
            border-bottom: 1px solid #edf1f7;
            color: #475569;
            font-size: 0.87rem;
        }

        .product-table tbody tr:last-child td {
            border-bottom: none;
        }

        .product-table tbody tr:hover {
            background: #f9faff;
        }

        .product-id {
            color: #94a3b8 !important;
            font-weight: 700;
        }

        .product-name {
            color: #253b80;
            font-weight: 800;
        }

        .product-description {
            max-width: 280px;
            color: #64748b !important;
            line-height: 1.6;
        }

        .points-value {
            color: #253b80;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 0.73rem;
            font-weight: 800;
        }

        .status-active {
            background: #e7f8ef;
            color: #15966b;
        }

        .status-inactive {
            background: #f1f3f6;
            color: #64748b;
        }

        .created-date {
            color: #64748b;
            white-space: nowrap;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        /* Empty state */

        .empty-state {
            padding: 55px 20px;
            text-align: center;
        }

        .empty-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 65px;
            height: 65px;
            margin: 0 auto 18px;
            border-radius: 18px;
            background: #eef2ff;
            color: #4169e1;
            font-size: 1.8rem;
        }

        .empty-state h5 {
            color: #253b80;
            font-weight: 800;
        }

        .empty-state p {
            color: #64748b;
            font-size: 0.88rem;
        }

        /* Responsive */

        @media (max-width: 767px) {

            .admin-navbar {
                padding: 13px 0;
            }

            .admin-brand {
                font-size: 1.05rem;
            }

            .admin-brand-icon {
                width: 39px;
                height: 39px;
            }

            .admin-main {
                padding-top: 27px;
            }

            .page-heading h1 {
                font-size: 1.65rem;
            }

            .content-card {
                padding: 19px;
            }

            .product-toolbar {
                align-items: flex-start !important;
                flex-direction: column;
                gap: 17px;
            }

            .add-product-btn {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {

            .admin-brand small {
                font-size: 0.53rem;
            }

            .back-dashboard {
                padding: 8px 10px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>

<body>

<header class="admin-navbar">
    <div class="container d-flex justify-content-between align-items-center">

        <a href="../../index.php" class="admin-brand">

            <span class="admin-brand-icon">★</span>

            <span>
                Loyalty<span style="color: #4169e1;">Rewards</span>
                <small>ADMINISTRATION PANEL</small>
            </span>

        </a>

        <a href="../dashboard.php" class="back-dashboard">
            ← Back to Dashboard
        </a>

    </div>
</header>


<main class="container admin-main">

    <!-- Page heading -->

    <div class="page-heading">

        <span class="page-label">PRODUCT MANAGEMENT</span>

        <h1>Product Management</h1>

        <p>
            View and manage products available in the loyalty rewards system.
        </p>

    </div>


    <!-- Success message -->

    <?php if ($successMessage !== ''): ?>

        <div class="alert alert-success alert-dismissible fade show rounded-3">
            <?= e($successMessage) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>
        </div>

    <?php endif; ?>


    <!-- Error message -->

    <?php if ($errorMessage !== ''): ?>

        <div class="alert alert-danger alert-dismissible fade show rounded-3">
            <?= e($errorMessage) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>
        </div>

    <?php endif; ?>


    <!-- Products listing -->

    <section class="content-card">

        <div class="product-toolbar d-flex justify-content-between align-items-center">

            <div>

                <h2 class="section-title">
                    All Products

                    <span class="product-count">
                        <?= count($products) ?>
                    </span>
                </h2>

                <p class="section-description">
                    View, update and manage your factory products.
                </p>

            </div>

            <a href="add.php" class="add-product-btn">
                + Add New Product
            </a>

        </div>


        <?php if (empty($products)): ?>

            <div class="empty-state">

                <div class="empty-icon">▣</div>

                <h5>No products found</h5>

                <p>
                    Products will appear here after they are added.
                </p>

                <a href="add.php" class="add-product-btn d-inline-block mt-2">
                    + Add Your First Product
                </a>

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-hover product-table">

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

                                <td class="product-id">
                                    #<?= (int) $product['id'] ?>
                                </td>

                                <td>
                                    <span class="product-name">
                                        <?= e($product['product_name']) ?>
                                    </span>
                                </td>

                                <td class="product-description">
                                    <?= e($product['description'] ?? '') ?>
                                </td>

                                <td class="points-value">
                                    <?= number_format((int) $product['points_per_box']) ?>
                                    points
                                </td>

                                <td>

                                    <?php if ($product['status'] === 'active'): ?>

                                        <span class="status-badge status-active">
                                            ● Active
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-inactive">
                                            ● Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td class="created-date">
                                    <?= e(date(
                                        'd M Y',
                                        strtotime($product['created_at'])
                                    )) ?>
                                </td>

                                <td>

                                    <div class="action-buttons">

                                        <a
                                            href="edit.php?id=<?= (int) $product['id'] ?>"
                                            class="btn btn-sm btn-outline-primary action-btn"
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
                                                    class="btn btn-sm btn-outline-danger action-btn"
                                                >
                                                    Deactivate
                                                </button>

                                            <?php else: ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-success action-btn"
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

    </section>

</main>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
