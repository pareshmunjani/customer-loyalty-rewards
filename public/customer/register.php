
<?php

require_once __DIR__ . '/../../app/includes/functions.php';

startSecureSession();

require_once __DIR__ . '/../../app/includes/csrf.php';
require_once __DIR__ . '/../../app/config/database.php';

$pageTitle = 'Create Account | Loyalty Rewards';

$errors = [];
$fullName = '';
$mobile = '';
$email = '';

$flashMessage = getFlashMessage();

// Process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form submission. Please refresh and try again.';
    }

    // Name validation
    if (
        $fullName === '' ||
        strlen($fullName) > 100
    ) {
        $errors[] = 'Please enter a valid full name.';
    }

    // Mobile validation
    if (!preg_match('/^\+?[0-9]{10,15}$/', $mobile)) {
        $errors[] = 'Enter a valid mobile number (10-15 digits).';
    }

    // Email validation
    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150
    ) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Password validation
    if (!is_string($password) || strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    }

    // Confirm password
    if (
        !is_string($confirmPassword) ||
        $password !== $confirmPassword
    ) {
        $errors[] = 'Passwords do not match.';
    }

    // Database operation
    if (empty($errors)) {

        try {

            $pdo = getPDO();

            $checkQuery = "
                SELECT id
                FROM users
                WHERE email = :email OR mobile = :mobile
                LIMIT 1
            ";

            $checkStmt = $pdo->prepare($checkQuery);

            $checkStmt->execute([
                'email' => $email,
                'mobile' => $mobile
            ]);

            if ($checkStmt->fetch()) {

                $errors[] = 'This email or mobile number is already registered.';

            } else {

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $insertQuery = "
                    INSERT INTO users (
                        full_name,
                        mobile,
                        email,
                        password,
                        role
                    )
                    VALUES (
                        :full_name,
                        :mobile,
                        :email,
                        :password,
                        'customer'
                    )
                ";

                $insertStmt = $pdo->prepare($insertQuery);

                $insertStmt->execute([
                    'full_name' => $fullName,
                    'mobile' => $mobile,
                    'email' => $email,
                    'password' => $hashedPassword
                ]);

                setFlashMessage(
                    'success',
                    'Your account has been created successfully! You can now log in.'
                );

                redirect(
                    '/customer-loyalty-rewards/public/customer/register.php'
                );
            }

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $errors[] = 'This email or mobile number is already registered.';

            } else {

                error_log('Registration error: ' . $e->getMessage());

                $errors[] = 'Something went wrong. Please try again later.';
            }
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
                        Rewards begin
                        with every purchase.
                    </h1>

                    <p>
                        Join our loyalty community, collect
                        points on genuine product purchases
                        and enjoy exciting rewards.
                    </p>

                    <div class="brand-features">

                        <div class="brand-feature">
                            <span>01</span>
                            <p>Purchase our products</p>
                        </div>

                        <div class="brand-feature">
                            <span>02</span>
                            <p>Reveal your reward code</p>
                        </div>

                        <div class="brand-feature">
                            <span>03</span>
                            <p>Earn and redeem points</p>
                        </div>

                    </div>

                </div>

                <div class="brand-decoration"></div>

            </div>


            <!-- Right Registration Form -->

            <div class="registration-form-section">

                <div class="registration-heading">

                    <span class="form-subtitle">
                        CREATE YOUR ACCOUNT
                    </span>

                    <h2>Get started today</h2>

                    <p>
                        Enter your details to join our rewards program.
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

                        <label for="full_name" class="form-label">
                            Full Name
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="full_name"
                            name="full_name"
                            placeholder="Enter your full name"
                            maxlength="100"
                            value="<?= e($fullName) ?>"
                            autocomplete="name"
                            required
                        >

                    </div>


                    <div class="mb-3">

                        <label for="mobile" class="form-label">
                            Mobile Number
                        </label>

                        <input
                            type="tel"
                            class="form-control"
                            id="mobile"
                            name="mobile"
                            placeholder="e.g. 9876543210"
                            maxlength="16"
                            value="<?= e($mobile) ?>"
                            autocomplete="tel"
                            required
                        >

                    </div>


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


                    <div class="mb-3">

                        <label for="password" class="form-label">
                            Password
                        </label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Minimum 8 characters"
                                minlength="8"
                                autocomplete="new-password"
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


                    <div class="mb-4">

                        <label for="confirm_password" class="form-label">
                            Confirm Password
                        </label>

                        <div class="password-wrapper">

                            <input
                                type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Re-enter your password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="confirm_password"
                                aria-label="Show confirm password"
                            >
                                Show
                            </button>

                        </div>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary register-button"
                    >
                        Create Account
                        <span aria-hidden="true">&rarr;</span>
                    </button>

                </form>


                <div class="login-prompt">

                    Already have an account?

                    <a href="login.php">
                        Sign in
                    </a>

                </div>

                <p class="registration-note">
                    Your information is protected and used
                    only for your loyalty account.
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
