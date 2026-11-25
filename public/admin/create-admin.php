
<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/includes/functions.php';
require_once __DIR__ . '/../../app/includes/csrf.php';

startSecureSession();

$pdo = getPDO();
$error = '';
$success = '';
$adminExists = false;

$adminCheck = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
);
$adminExists = (int) $adminCheck->fetchColumn() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists) {
    $name = trim($_POST['full_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } elseif ($name === '' || $mobile === '' || $email === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $mobile)) {
        $error = 'Mobile number must contain 10 to 15 digits.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Prevent two simultaneous setup requests.
            $lock = $pdo->query(
                "SELECT GET_LOCK('customer_loyalty_admin_setup', 10)"
            )->fetchColumn();

            if ((int) $lock !== 1) {
                throw new RuntimeException(
                    'Could not start administrator setup. Please try again.'
                );
            }

            try {
                $check = $pdo->query(
                    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
                );

                if ((int) $check->fetchColumn() > 0) {
                    $adminExists = true;
                    $error = 'An administrator already exists. Setup is disabled.';
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO users
                        (full_name, mobile, email, password, role)
                        VALUES
                        (:full_name, :mobile, :email, :password, 'admin')"
                    );

                    $stmt->execute([
                        'full_name' => $name,
                        'mobile' => $mobile,
                        'email' => $email,
                        'password' => password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        )
                    ]);

                    $adminExists = true;
                    $success = 'Administrator account created successfully. You can now log in.';
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }
            } finally {
                $pdo->query(
                    "SELECT RELEASE_LOCK('customer_loyalty_admin_setup')"
                );
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'This email address or mobile number is already registered.';
            } else {
                $error = 'A database error occurred. Please check your database configuration.';
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

$csrfToken = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Setup | Customer Loyalty Rewards</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 15px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            font-family: Arial, sans-serif;
            color: #1e293b;
        }

        .setup-card {
            width: 100%;
            max-width: 480px;
            padding: 32px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 25px;
        }

        .description {
            margin: 0 0 24px;
            color: #64748b;
            line-height: 1.5;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            font-weight: 600;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #93c5fd;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: 0;
            border-radius: 7px;
            background: #1d4ed8;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #1e40af;
        }

        .message {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 7px;
            line-height: 1.5;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .disabled {
            padding: 15px;
            border-radius: 8px;
            background: #fef3c7;
            color: #92400e;
            line-height: 1.6;
        }

        .login-link {
            display: inline-block;
            margin-top: 18px;
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>
<div class="setup-card">

    <h1>Administrator Setup</h1>
    <p class="description">
        Create the initial administrator account for the Customer Loyalty Rewards System.
    </p>

    <?php if ($error !== ''): ?>
        <div class="message error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="message success">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($adminExists): ?>

        <div class="disabled">
            An administrator account already exists.
            Initial setup is disabled for security.
        </div>

        <a class="login-link" href="<?= e(url('admin/login.php')) ?>">
            Go to Administrator Login
        </a>

    <?php else: ?>

        <form method="POST" action="">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrfToken) ?>"
            >

            <label for="full_name">Full Name</label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                maxlength="100"
                required
            >

            <label for="mobile">Mobile Number</label>
            <input
                type="tel"
                id="mobile"
                name="mobile"
                maxlength="15"
                pattern="[0-9]{10,15}"
                required
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >

            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                required
            >

            <button type="submit">Create Administrator Account</button>
        </form>

    <?php endif; ?>

</div>
</body>
</html>
