
<?php

require_once __DIR__ . '/../../../app/includes/functions.php';
startSecureSession();

require_once __DIR__ . '/../../../app/includes/auth.php';
require_once __DIR__ . '/../../../app/includes/csrf.php';
require_once __DIR__ . '/../../../app/config/database.php';

requireAdmin();

// Allow POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlashMessage('error', 'Invalid security token. Please try again.');
    redirect('/customer-loyalty-rewards/public/admin/codes/index.php');
}

// Validate product ID.
$productId = filter_var(
    $_POST['product_id'] ?? null,
    FILTER_VALIDATE_INT
);

// Validate quantity.
$quantityInput = trim((string) ($_POST['quantity'] ?? ''));

if (!$productId || $productId < 1) {
    setFlashMessage('error', 'Please select a valid product.');
    redirect('/customer-loyalty-rewards/public/admin/codes/index.php');
}

if (
    $quantityInput === '' ||
    !ctype_digit($quantityInput) ||
    (int) $quantityInput < 1 ||
    (int) $quantityInput > 5000
) {
    setFlashMessage('error', 'Quantity must be between 1 and 5,000.');
    redirect('/customer-loyalty-rewards/public/admin/codes/index.php');
}

$quantity = (int) $quantityInput;

$pdo = getPDO();

try {

    $pdo->beginTransaction();

    // Lock and verify the selected product.
    $productStmt = $pdo->prepare(
        "SELECT id, product_name, points_per_box, status
         FROM products
         WHERE id = :id
         FOR UPDATE"
    );

    $productStmt->execute(['id' => $productId]);

    $product = $productStmt->fetch();

    if (!$product || $product['status'] !== 'active') {
        $pdo->rollBack();

        setFlashMessage(
            'error',
            'The selected product does not exist or is inactive.'
        );

        redirect('/customer-loyalty-rewards/public/admin/codes/index.php');
    }

    $pointsValue = (int) $product['points_per_box'];

    if ($pointsValue < 1) {
        $pdo->rollBack();

        setFlashMessage(
            'error',
            'The selected product has an invalid points value.'
        );

        redirect('/customer-loyalty-rewards/public/admin/codes/index.php');
    }

    // Prepare the insert statement once.
    $insertStmt = $pdo->prepare(
        "INSERT INTO reward_codes
            (product_id, code_hash, points_value, status)
         VALUES
            (:product_id, :code_hash, :points_value, 'unused')"
    );

    $generatedCodes = [];

    // Generate each code and store only its hash.
    for ($i = 0; $i < $quantity; $i++) {

        $inserted = false;

        // Retry if an extremely unlikely duplicate occurs.
        for ($attempt = 0; $attempt < 5; $attempt++) {

            // Generate 16 random hexadecimal characters.
            $randomPart = strtoupper(bin2hex(random_bytes(8)));

            // Format: XXXX-XXXX-XXXX-XXXX
            $code = implode('-', str_split($randomPart, 4));

            // Hash the complete formatted code.
            $codeHash = hash('sha256', $code);

            try {

                $insertStmt->execute([
                    'product_id' => $productId,
                    'code_hash' => $codeHash,
                    'points_value' => $pointsValue
                ]);

                $generatedCodes[] = $code;
                $inserted = true;

                break;

            } catch (PDOException $exception) {

                // SQLSTATE 23000 indicates an integrity constraint error.
                // Retry only when the code hash already exists.
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }

                $checkStmt = $pdo->prepare(
                    "SELECT id
                     FROM reward_codes
                     WHERE code_hash = :code_hash"
                );

                $checkStmt->execute(['code_hash' => $codeHash]);

                if (!$checkStmt->fetch()) {
                    // This was a different database constraint error.
                    throw $exception;
                }
            }
        }

        if (!$inserted) {
            throw new RuntimeException(
                'Unable to generate a unique code after multiple attempts.'
            );
        }
    }

    // Commit all generated codes together.
    $pdo->commit();

    // Keep original codes temporarily in the admin session.
    // They are not stored as plaintext in the database.
    $_SESSION['generated_codes'] = $generatedCodes;

    $_SESSION['generated_batch'] = [
        'product_name' => $product['product_name'],
        'quantity' => $quantity,
        'points_value' => $pointsValue,
        'generated_at' => date('Y-m-d H:i:s')
    ];

    redirect('/customer-loyalty-rewards/public/admin/codes/result.php');

} catch (Throwable $exception) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Reward code generation error: ' . $exception->getMessage());

    setFlashMessage(
        'error',
        'Code generation failed. No codes from this batch were saved. Please try again.'
    );

    redirect('/customer-loyalty-rewards/public/admin/codes/index.php');
}
