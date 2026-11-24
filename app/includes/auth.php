
<?php

declare(strict_types=1);

/**
 * Generate or retrieve CSRF token.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {

        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['csrf_token'];
}

/**
 * Validate submitted CSRF token.
 */
function verifyCsrfToken(?string $token): bool
{
    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($token)
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $token
    );
}
