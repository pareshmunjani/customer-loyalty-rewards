
<?php

require_once __DIR__ . '/../app/config/database.php';

try {

    $pdo = getPDO();

    echo "Database connection successful!";

} catch (RuntimeException $e) {

    http_response_code(500);

    echo $e->getMessage();
}
