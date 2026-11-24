
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

$pdo = getPDO();

// Search customers.
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        u.id,
        u.full_name,
        u.mobile,
        u.email,
        u.created_at,

        COALESCE(
            SUM(
                CASE
                    WHEN pt.transaction_type = 'earn'
                    THEN pt.points_delta
                    ELSE 0
                END
            ), 0
        ) AS total_earned,

        COALESCE(
            SUM(
                CASE
                    WHEN pt.transaction_type = 'redeem'
                    THEN pt.points_delta
                    ELSE 0
                END
            ), 0
        ) AS total_redeemed,

        COALESCE(
            SUM(
                CASE
                    WHEN pt.transaction_type = 'earn'
                    THEN pt.points_delta
                    WHEN pt.transaction_type = 'redeem'
                    THEN -pt.points_delta
                    ELSE 0
                END
            ), 0
        ) AS available_points

    FROM users u

    LEFT JOIN point_transactions pt
        ON pt.user_id = u.id

    WHERE u.role = 'customer'
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            u.full_name LIKE :search_name
            OR u.mobile LIKE :search_mobile
            OR u.email LIKE :search_email
        )
    ";

    $searchTerm = '%' . $search . '%';

    $params = [
        'search_name' => $searchTerm,
        'search_mobile' => $searchTerm,
        'search_email' => $searchTerm
    ];
}

$sql .= "
    GROUP BY
        u.id,
        u.full_name,
        u.mobile,
        u.email,
        u.created_at

    ORDER BY u.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$customers = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Management | Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f5f7fb;
        }

        .page-card {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 24px;
        }

        .table th {
            white-space: nowrap;
            background-color: #f8f9fa;
        }

        .table td {
            vertical-align: middle;
        }

        .search-box {
            max-width: 450px;
        }
    </style>
</head>

<body>

<header class="bg-white border-bottom py-3">

    <div class="container d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-0">Customer Loyalty Rewards</h5>
            <small class="text-muted">Admin / Customer Management</small>
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

    <div class="mb-4">

        <h3 class="fw-bold mb-1">Customer Management</h3>

        <p class="text-muted mb-0">
            View registered customers and their loyalty points.
        </p>

    </div>

    <div class="page-card">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

            <div>
                <h5 class="fw-bold mb-1">Registered Customers</h5>

                <p class="text-muted mb-0">
                    Total matching customers:
                    <strong><?= count($customers) ?></strong>
                </p>
            </div>

            <form method="GET" class="d-flex gap-2 search-box">

                <input
                    type="search"
                    name="search"
                    class="form-control"
                    placeholder="Search name, mobile or email"
                    value="<?= e($search) ?>"
                >

                <button type="submit" class="btn btn-primary">
                    Search
                </button>

                <?php if ($search !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary">
                        Clear
                    </a>
                <?php endif; ?>

            </form>

        </div>

        <?php if (empty($customers)): ?>

            <div class="alert alert-info mb-0">
                <?php if ($search !== ''): ?>
                    No customers found matching your search.
                <?php else: ?>
                    No customers have registered yet.
                <?php endif; ?>
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-bordered table-hover">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Total Earned</th>
                            <th>Total Redeemed</th>
                            <th>Available Points</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($customers as $customer): ?>

                            <tr>

                                <td><?= (int) $customer['id'] ?></td>

                                <td>
                                    <strong>
                                        <?= e($customer['full_name']) ?>
                                    </strong>
                                </td>

                                <td><?= e($customer['mobile']) ?></td>

                                <td><?= e($customer['email']) ?></td>

                                <td>
                                    <?= number_format((int) $customer['total_earned']) ?>
                                </td>

                                <td>
                                    <?= number_format((int) $customer['total_redeemed']) ?>
                                </td>

                                <td>
                                    <strong class="text-success">
                                        <?= number_format((int) $customer['available_points']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= e(date(
                                        'd M Y',
                                        strtotime($customer['created_at'])
                                    )) ?>
                                </td>

                                <td>
                                    <a
                                        href="view.php?id=<?= (int) $customer['id'] ?>"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        View Details
                                    </a>
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
