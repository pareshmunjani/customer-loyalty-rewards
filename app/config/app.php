
<?php

/**
 * Customer Loyalty Rewards System
 * Application Configuration
 */

define('APP_NAME', 'Customer Loyalty Rewards System');

define('APP_ENV', 'development');

// Base URL of the application
define('BASE_URL', '/customer-loyalty-rewards/public');

/**
 * Generate application URLs.
 *
 * Example:
 * url('customer/login.php')
 */
function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}
