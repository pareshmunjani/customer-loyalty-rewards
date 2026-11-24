
<?php

require_once __DIR__ . '/../../app/includes/functions.php';

startSecureSession();

require_once __DIR__ . '/../../app/includes/csrf.php';
require_once __DIR__ . '/../../app/config/database.php';

$pageTitle = 'Admin Login';

$errorMessage = '';
$email = '';

// Redirect an already authenticated admin.
if (
    isset($_SESSION['user_id'])
    && ($_SESSION['user_role'] ?? '') === 'admin'
) {
    redirect('/customer-loyalty-rewards/public/admin/dashboard.php');
}

// Process admin login.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {

        $errorMessage = 'Invalid request. Please refresh the page and try again.';

    } else {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {

            $errorMessage = 'Please enter your email and password.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $errorMessage = 'Please enter a valid email address.';

        } else {

            try {

                $pdo = getPDO();

                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        full_name,
                        email,
                        password,
                        role
                    FROM users
                    WHERE email = :email
                      AND role = 'admin'
                    LIMIT 1
                ");

                $stmt->execute([
                    'email' => $email
                ]);

                $admin = $stmt->fetch();

                if (
                    $admin
                    && password_verify($password, $admin['password'])
                ) {

                    // Regenerate session ID after successful login.
                    session_regenerate_id(true);

                    // Replace any existing customer session.
                    $_SESSION = [];

                    $_SESSION['user_id'] = (int) $admin['id'];
                    $_SESSION['user_name'] = $admin['full_name'];
                    $_SESSION['user_email'] = $admin['email'];
                    $_SESSION['user_role'] = $admin['role'];

                    redirect(
                        '/customer-loyalty-rewards/public/admin/dashboard.php'
                    );

                } else {

                    $errorMessage = 'Invalid email or password.';

                }

            } catch (PDOException $exception) {

                error_log(
                    'Admin login error: ' . $exception->getMessage()
                );

                $errorMessage = 'Unable to process your login. Please try again.';

            }
        }
    }
}

require_once __DIR__ . '/../../app/includes/header.php';

?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7 col-lg-5">

            <div class="card border-0 shadow-sm">

                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">

                        <h2 class="fw-bold">Admin Login</h2>

                        <p class="text-muted">
                            Sign in to manage the loyalty rewards system.
                        </p>

                    </div>

                    <?php if ($errorMessage !== ''): ?>

                        <div class="alert alert-danger">
                            <?= e($errorMessage) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST" action="">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(generateCsrfToken()) ?>"
                        >

                        <div class="mb-3">

                            <label for="email" class="form-label fw-semibold">
                                Admin Email
                            </label>

                            <input
                                type="email"
                                class="form-control form-control-lg"
                                id="email"
                                name="email"
                                placeholder="Enter admin email"
                                value="<?= e($email) ?>"
                                maxlength="150"
                                autocomplete="username"
                                required
                            >

                        </div>

                        <div class="mb-4">

                            <label for="password" class="form-label fw-semibold">
                                Password
                            </label>

                            <input
                                type="password"
                                class="form-control form-control-lg"
                                id="password"
                                name="password"
                                placeholder="Enter admin password"
                                autocomplete="current-password"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg w-100"
                        >
                            Login as Admin
                        </button>

                    </form>

                    <div class="text-center mt-4">

                        <a
                            href="/customer-loyalty-rewards/public/customer/login.php"
                            class="text-decoration-none"
                        >
                            Back to Customer Login
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../app/includes/footer.php'; ?>
