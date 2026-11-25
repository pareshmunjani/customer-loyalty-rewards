
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

    if ($productName === '') {
        $errors[] = 'Product name is required.';
    } elseif (mb_strlen($productName) > 150) {
        $errors[] = 'Product name cannot exceed 150 characters.';
    }

    if (mb_strlen($description) > 1000) {
        $errors[] = 'Description cannot exceed 1000 characters.';
    }

    if (
        $pointsPerBox === '' ||
        !ctype_digit($pointsPerBox) ||
        (int) $pointsPerBox < 1 ||
        (int) $pointsPerBox > 1000000
    ) {
        $errors[] = 'Points per box must be a whole number between 1 and 1,000,000.';
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Please select a valid product status.';
    }

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

    <title>Add Product | Customer Loyalty Rewards</title>

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

        .back-products {
            border: 1px solid #d8e1f2;
            border-radius: 9px;
            padding: 9px 16px;
            color: #253b80;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .back-products:hover {
            background: #eef2ff;
            border-color: #c5d2f5;
            color: #253b80;
        }

        /* Main content */

        .admin-main {
            max-width: 1050px;
            padding-top: 38px;
            padding-bottom: 55px;
        }

        .page-heading {
            margin-bottom: 27px;
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

        /* Form card */

        .form-card {
            padding: 30px;
            background: #ffffff;
            border: 1px solid #e8edf5;
            border-radius: 17px;
            box-shadow: 0 8px 25px rgba(37, 59, 128, 0.045);
        }

        .form-card-heading {
            padding-bottom: 20px;
            margin-bottom: 25px;
            border-bottom: 1px solid #edf1f7;
        }

        .form-card-heading h2 {
            color: #253b80;
            font-size: 1.18rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .form-card-heading p {
            color: #64748b;
            font-size: 0.85rem;
            margin: 0;
        }

        .form-label {
            color: #33466f;
            font-size: 0.86rem;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .form-control,
        .form-select {
            min-height: 47px;
            border: 1px solid #dce4f0;
            border-radius: 9px;
            color: #253b80;
            font-size: 0.88rem;
            box-shadow: none;
        }

        textarea.form-control {
            min-height: 115px;
            resize: vertical;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #7894ed;
            box-shadow: 0 0 0 3px rgba(65, 105, 225, 0.12);
        }

        .form-control::placeholder {
            color: #a0aec0;
        }

        .form-text {
            color: #8492a8;
            font-size: 0.78rem;
            margin-top: 8px;
        }

        .field-group {
            margin-bottom: 24px;
        }

        /* Points information */

        .points-info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px;
            margin-top: 10px;
            border: 1px solid #dce7ff;
            border-radius: 11px;
            background: #f4f7ff;
        }

        .points-info-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #e5ecff;
            color: #4169e1;
            font-weight: 800;
        }

        .points-info p {
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.6;
            margin: 0;
        }

        .points-info strong {
            color: #253b80;
        }

        /* Status selection */

        .status-help {
            color: #8492a8;
            font-size: 0.78rem;
            margin-top: 8px;
        }

        /* Buttons */

        .save-btn {
            padding: 11px 22px;
            border: none;
            border-radius: 9px;
            background: linear-gradient(135deg, #253b80, #3655aa);
            color: #ffffff;
            font-size: 0.86rem;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .save-btn:hover {
            background: #1c2f6c;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 14px rgba(37, 59, 128, 0.2);
        }

        .cancel-btn {
            padding: 11px 22px;
            border: 1px solid #d8e1f2;
            border-radius: 9px;
            background: #ffffff;
            color: #64748b;
            font-size: 0.86rem;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .cancel-btn:hover {
            background: #f4f6fb;
            color: #253b80;
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

            .form-card {
                padding: 21px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn,
            .form-actions a {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {

            .admin-brand small {
                font-size: 0.53rem;
            }

            .back-products {
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

        <a href="index.php" class="back-products">
            ← Back to Products
        </a>

    </div>

</header>


<main class="container admin-main">

    <!-- Page heading -->

    <div class="page-heading">

        <span class="page-label">PRODUCT MANAGEMENT</span>

        <h1>Add New Product</h1>

        <p>
            Add a factory product and configure the loyalty points
            customers will earn for each box.
        </p>

    </div>


    <!-- Validation errors -->

    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger rounded-3 mb-4">

            <div class="fw-bold mb-2">
                Please correct the following:
            </div>

            <ul class="mb-0">

                <?php foreach ($errors as $error): ?>

                    <li><?= e($error) ?></li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <!-- Product form -->

    <section class="form-card">

        <div class="form-card-heading">

            <h2>Product Information</h2>

            <p>
                Enter the details below to add a product to your loyalty program.
            </p>

        </div>


        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(generateCsrfToken()) ?>"
            >


            <!-- Product name -->

            <div class="field-group">

                <label for="product_name" class="form-label">
                    Product Name <span class="text-danger">*</span>
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

                <div class="form-text">
                    Enter the name of the product manufactured by your factory.
                </div>

            </div>


            <!-- Description -->

            <div class="field-group">

                <label for="description" class="form-label">
                    Product Description
                </label>

                <textarea
                    class="form-control"
                    id="description"
                    name="description"
                    rows="4"
                    maxlength="1000"
                    placeholder="Enter a short description of the product..."
                ><?= e($description) ?></textarea>

                <div class="form-text">
                    Optional. Maximum 1000 characters.
                </div>

            </div>


            <!-- Points per box -->

            <div class="field-group">

                <label for="points_per_box" class="form-label">
                    Points Per Box <span class="text-danger">*</span>
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
                    placeholder="Example: 25"
                    required
                >

                <div class="points-info">

                    <div class="points-info-icon">★</div>

                    <p>
                        <strong>How points work:</strong>
                        Every successfully redeemed reward code belonging
                        to this product awards the configured number of points
                        to the customer.
                    </p>

                </div>

            </div>


            <!-- Product status -->

            <div class="field-group">

                <label for="status" class="form-label">
                    Product Status <span class="text-danger">*</span>
                </label>

                <select
                    class="form-select"
                    id="status"
                    name="status"
                    required
                >

                    <option
                        value="active"
                        <?= $status === 'active' ? 'selected' : '' ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?= $status === 'inactive' ? 'selected' : '' ?>
                    >
                        Inactive
                    </option>

                </select>

                <div class="status-help">
                    Active products can be used for reward code generation.
                </div>

            </div>


            <!-- Form actions -->

            <div class="form-actions d-flex flex-wrap gap-3 pt-3">

                <button type="submit" class="btn save-btn">
                    ✓ Save Product
                </button>

                <a href="index.php" class="btn cancel-btn">
                    Cancel
                </a>

            </div>

        </form>

    </section>

</main>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
