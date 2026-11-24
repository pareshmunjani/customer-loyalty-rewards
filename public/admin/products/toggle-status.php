
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/includes/csrf.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

// Initialize database connection.
$pdo = getPDO();

// Only POST requests are allowed.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage('error', 'Invalid security token. Please try again.');
    redirect('/customer-loyalty-rewards/public/admin/products/index.php');
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    setFlashMessage('error', 'Invalid product ID.');
    redirect('/customer-loyalty-rewards/public/admin/products/index.php');
}

try {
    // Find current product status.
    $stmt = $pdo->prepare(
        "SELECT status
         FROM products
         WHERE id = :id"
    );

    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        setFlashMessage('error', 'Product not found.');
        redirect('/customer-loyalty-rewards/public/admin/products/index.php');
    }

    // Toggle product status.
    $newStatus = $product['status'] === 'active'
        ? 'inactive'
        : 'active';

    $updateStmt = $pdo->prepare(
        "UPDATE products
         SET status = :status
         WHERE id = :id"
    );

    $updateStmt->execute([
        'status' => $newStatus,
        'id' => $productId
    ]);

    $message = $newStatus === 'active'
        ? 'Product activated successfully.'
        : 'Product deactivated successfully.';

    setFlashMessage('success', $message);

} catch (PDOException $exception) {
    error_log('Product status update error: ' . $exception->getMessage());

    setFlashMessage(
        'error',
        'Unable to update product status. Please try again.'
    );
}

redirect('/customer-loyalty-rewards/public/admin/products/index.php');
