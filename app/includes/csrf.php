
<?php

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($token)
    ) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}
