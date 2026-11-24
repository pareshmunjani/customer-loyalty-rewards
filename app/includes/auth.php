
<?php


require_once __DIR__ . '/functions.php';

/**
 * Check whether a user is logged in.
 */
function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {

        setFlashMessage(
            'danger',
            'Please log in to access your account.'
        );

        redirect(
            '/customer-loyalty-rewards/public/customer/login.php'
        );
    }
}

/**
 * Allow access only to customers.
 */
function requireCustomer(): void
{
    requireLogin();

    if (($_SESSION['user_role'] ?? '') !== 'customer') {

        http_response_code(403);
        exit('Access denied. Customer access only.');
    }
}

/**
 * Allow access only to administrators.
 */
function requireAdmin(): void
{
    requireLogin();

    if (($_SESSION['user_role'] ?? '') !== 'admin') {

        http_response_code(403);
        exit('Access denied. Administrator access only.');
    }
}
