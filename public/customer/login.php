
<?php

require_once __DIR__ . '/../../app/includes/functions.php';

startSecureSession();

require_once __DIR__ . '/../../app/includes/csrf.php';
require_once __DIR__ . '/../../app/config/database.php';

$pageTitle = 'Customer Login | Loyalty Rewards';

$errors = [];
$email = '';

$flashMessage = getFlashMessage();

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    }

    // Input validation
    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150
    ) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!is_string($password) || $password === '') {
        $errors[] = 'Please enter your password.';
    }

    // Verify customer credentials
    if (empty($errors)) {

        try {

            $pdo = getPDO();

            $query = "
                SELECT id, full_name, email, password, role
                FROM users
                WHERE email = :email
                LIMIT 1
            ";

            $stmt = $pdo->prepare($query);

            $stmt->execute([
                'email' => $email
            ]);

            $user = $stmt->fetch();

            if (
                $user &&
                $user['role'] === 'customer' &&
                password_verify($password, $user['password'])
            ) {

                // Prevent session fixation
                session_regenerate_id(true);

                // Store customer information in session
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect after successful login
                redirect(url('customer/dashboard.php'));

            } else {

                $errors[] = 'Invalid email or password.';

            }

        } catch (PDOException $e) {

            error_log('Login error: ' . $e->getMessage());

            $errors[] = 'Something went wrong. Please try again later.';
        }
    }
}

require_once __DIR__ . '/../../app/includes/header.php';

?>

<div class="registration-wrapper">

    <div class="container">

        <div class="registration-container">

            <!-- Left Branding Section -->

            <div class="registration-brand">

                <div class="brand-content">

                    <span class="brand-label">
                        LOYALTY REWARDS PROGRAM
                    </span>

                    <h1>
                        Welcome back.
                        Your rewards await.
                    </h1>

                    <p>
                        Sign in to your account, check your
                        points balance and enjoy your rewards.
                    </p>

                    <div class="brand-features">

                        <div class="brand-feature">
                            <span>01</span>
                            <p>Access your loyalty account</p>
                        </div>

                        <div class="brand-feature">
                            <span>02</span>
                            <p>Track your earned points</p>
                        </div>

                        <div class="brand-feature">
                            <span>03</span>
                            <p>Redeem exciting rewards</p>
                        </div>

                    </div>

                </div>

                <div class="brand-decoration"></div>

            </div>

            <!-- Right Login Form -->

            <div class="registration-form-section">

                <div class="registration-heading">

                    <span class="form-subtitle">
                        CUSTOMER LOGIN
                    </span>

                    <h2>Sign in to continue</h2>

                    <p>
                        Enter your registered email and password.
                    </p>

                </div>

                <!-- Flash Message -->

                <?php if ($flashMessage): ?>

                    <div class="alert alert-success" role="alert">
                        <?= e($flashMessage['message']) ?>
                    </div>

                <?php endif; ?>

                <!-- Error Messages -->

                <?php if (!empty($errors)): ?>

                    <div class="alert alert-danger" role="alert">

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

                        <label for="email" class="form-label">
                            Email Address
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            placeholder="you@example.com"
                            maxlength="150"
                            value="<?= e($email) ?>"
                            autocomplete="email"
                            required
                        >

                    </div>

                    <div class="mb-4">

                        <label for="password" class="form-label">
                            Password
                        </label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="password"
                                aria-label="Show password"
                            >
                                Show
                            </button>

                        </div>

                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary register-button"
                    >
                        Sign In
                        <span aria-hidden="true">&rarr;</span>
                    </button>

                </form>

                <div class="login-prompt">

                    Don't have an account?

                    <a href="<?= e(url('customer/register.php')) ?>">
                        Create account
                    </a>

                </div>

                <p class="registration-note">
                    Your account information is protected.
                </p>

            </div>

        </div>

    </div>

</div>

<script>
document.querySelectorAll('.password-toggle').forEach(button => {

    button.addEventListener('click', function () {

        const input = document.getElementById(
            this.dataset.target
        );

        if (input.type === 'password') {
            input.type = 'text';
            this.textContent = 'Hide';
            this.setAttribute('aria-label', 'Hide password');
        } else {
            input.type = 'password';
            this.textContent = 'Show';
            this.setAttribute('aria-label', 'Show password');
        }

    });

});
</script>

<?php

require_once __DIR__ . '/../../app/includes/footer.php';

?>
