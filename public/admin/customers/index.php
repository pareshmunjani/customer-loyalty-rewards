
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

        COALESCE(SUM(
            CASE
                WHEN pt.transaction_type = 'earn'
                THEN pt.points_delta
                ELSE 0
            END
        ), 0) AS total_earned,

        COALESCE(SUM(
            CASE
                WHEN pt.transaction_type = 'redeem'
                THEN pt.points_delta
                ELSE 0
            END
        ), 0) AS total_redeemed,

        COALESCE(SUM(
            CASE
                WHEN pt.transaction_type = 'earn'
                THEN pt.points_delta
                WHEN pt.transaction_type = 'redeem'
                THEN -pt.points_delta
                ELSE 0
            END
        ), 0) AS available_points

    FROM users u
    LEFT JOIN point_transactions pt ON pt.user_id = u.id
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
        :root {
            --navy: #253b80;
            --navy-dark: #172b61;
            --blue: #4169e1;
            --page-bg: #f3f6fc;
            --border: #e5eaf3;
            --muted: #718096;
        }

        body {
            background: var(--page-bg);
            color: #253047;
            font-family: Arial, sans-serif;
        }

        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 15px 0;
        }

        .brand-name {
    color: var(--navy-dark);
    font-size: 21px;
    font-weight: 800;
    letter-spacing: -0.5px;
    margin: 0;
}

.brand-name span {
    color: var(--blue);
}

.brand-star {
    color: #f5b82e;
    margin-right: 5px;
}

.brand-subtitle {
    color: var(--muted);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.5px;
}

        .page-container {
            width: 100%;
            padding: 30px 3%;
        }

        .page-title {
            color: var(--navy-dark);
            font-size: 27px;
            font-weight: 700;
        }

        .page-description {
            color: var(--muted);
            font-size: 14px;
        }

        .page-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 4px 18px rgba(37, 59, 128, 0.04);
        }

        .section-title {
            color: var(--navy-dark);
            font-size: 19px;
            font-weight: 700;
        }

        .customer-count {
            color: var(--blue);
            font-weight: 700;
        }

        .search-box {
            width: 100%;
            max-width: 520px;
        }

        .form-control {
            border: 1px solid #dce3ef;
            border-radius: 8px;
            min-height: 42px;
        }

        .form-control:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(65, 105, 225, 0.12);
        }

        .btn-primary {
            background: var(--blue);
            border-color: var(--blue);
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: var(--navy);
            border-color: var(--navy);
        }

        .btn-outline-secondary {
            border-radius: 8px;
        }

        .table {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table thead th {
            background: #f0f3fa;
            color: var(--navy-dark);
            font-size: 13px;
            font-weight: 700;
            padding: 15px 12px;
            white-space: nowrap;
            border-bottom: 1px solid var(--border);
        }

        .table tbody td {
            padding: 14px 12px;
            font-size: 13px;
            color: #45516a;
            white-space: nowrap;
        }

        .table tbody tr:hover {
            background: #f8faff;
        }

        .customer-name {
            color: var(--navy-dark);
            font-weight: 700;
        }

        .points-earned {
            color: #16845b;
            font-weight: 600;
        }

        .points-redeemed {
            color: #d35454;
            font-weight: 600;
        }

        .points-available {
            color: #16845b;
            font-size: 14px;
            font-weight: 700;
        }

        .view-btn {
            background: #edf2ff;
            color: var(--navy);
            border: 1px solid #dce5ff;
            border-radius: 7px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
        }

        .view-btn:hover {
            background: var(--navy);
            color: #ffffff;
        }

        .empty-state {
            background: #f5f8ff;
            border: 1px dashed #cbd7f2;
            border-radius: 10px;
            padding: 35px 20px;
            text-align: center;
            color: var(--muted);
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 22px 15px;
            }

            .page-card {
                padding: 17px;
            }

            .page-title {
                font-size: 23px;
            }

            .search-box {
                max-width: 100%;
            }

            .search-box input {
                min-width: 0;
            }
        }
    </style>
</head>

<body>

<header class="top-navbar">
    <div class="container-fluid px-4 d-flex justify-content-between align-items-center">

        <div>
    <h5 class="brand-name">
        <span class="brand-star">★</span>Loyalty<span>Rewards</span>
    </h5>
    <small class="brand-subtitle">EVERY PURCHASE MATTERS</small>
</div>

        <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">
            Admin Dashboard
        </a>

    </div>
</header>

<main class="page-container">

    <div class="mb-4">
        <h3 class="page-title mb-1">Customer Management</h3>

        <p class="page-description mb-0">
            View registered customers and monitor their loyalty points.
        </p>
    </div>

    <div class="page-card">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

            <div>
                <h5 class="section-title mb-1">Registered Customers</h5>

                <p class="text-muted small mb-0">
                    Total matching customers:
                    <span class="customer-count"><?= count($customers) ?></span>
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

                <button type="submit" class="btn btn-primary px-3">
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

            <div class="empty-state">
                <h6 class="fw-bold mb-2">No Customers Found</h6>

                <p class="mb-0">
                    <?php if ($search !== ''): ?>
                        No customers match your search. Try another name, mobile number or email.
                    <?php else: ?>
                        No customers have registered yet.
                    <?php endif; ?>
                </p>
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-hover">

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

                                <td>#<?= (int) $customer['id'] ?></td>

                                <td>
                                    <span class="customer-name">
                                        <?= e($customer['full_name']) ?>
                                    </span>
                                </td>

                                <td><?= e($customer['mobile']) ?></td>

                                <td><?= e($customer['email']) ?></td>

                                <td>
                                    <span class="points-earned">
                                        <?= number_format((int) $customer['total_earned']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="points-redeemed">
                                        <?= number_format((int) $customer['total_redeemed']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="points-available">
                                        <?= number_format((int) $customer['available_points']) ?>
                                    </span>
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
                                        class="view-btn"
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
