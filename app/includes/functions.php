
<?php

require_once __DIR__ . '/../config/app.php';

/**
 * Start a secure PHP session.
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS'])
                && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();
    }
}

/**
 * Safely escape output before displaying it in HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Redirect to another application page.
 */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * Store a temporary flash message.
 */
function setFlashMessage(
    string $type,
    string $message
): void {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and remove a flash message.
 */
function getFlashMessage(): ?array
{
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }

    $message = $_SESSION['flash_message'];

    unset($_SESSION['flash_message']);

    return $message;
}
