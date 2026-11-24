
<?php

require_once __DIR__ . '/../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../app/includes/csrf.php';

// Logout must be submitted using POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Verify the CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid security token. Please try again.');
}

// Clear all session data.
$_SESSION = [];

// Remove the session cookie.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session.
session_destroy();

// Redirect to admin login.
redirect('/customer-loyalty-rewards/public/admin/login.php');
