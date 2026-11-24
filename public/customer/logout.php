
<?php

require_once __DIR__ . '/../../app/includes/functions.php';

startSecureSession();

require_once __DIR__ . '/../../app/includes/csrf.php';

// Only logged-in customers can log out
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'customer'
) {
    redirect('/customer-loyalty-rewards/public/customer/login.php');
}

// Logout must use POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Validate CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage(
        'danger',
        'Invalid logout request. Please try again.'
    );

    redirect('/customer-loyalty-rewards/public/customer/login.php');
}

// Clear session data
$_SESSION = [];

// Destroy the current session
session_destroy();

// Start a fresh session for the flash message
startSecureSession();

setFlashMessage(
    'success',
    'You have been logged out successfully.'
);

// Redirect to login
redirect('/customer-loyalty-rewards/public/customer/login.php');
