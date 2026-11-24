<?php

/**
 * Customer Loyalty Rewards System
 * Database Connection Configuration
 */

declare(strict_types=1);

// Local XAMPP database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'customer_loyalty_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create and return PDO connection
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {

        $dsn = "mysql:host=" . DB_HOST .
               ";port=" . DB_PORT .
               ";dbname=" . DB_NAME .
               ";charset=utf8mb4";

        try {

            $pdo = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

        } catch (PDOException $e) {

            error_log("Database connection error: " . $e->getMessage());

            throw new RuntimeException(
                "Database connection failed. Check your database configuration."
            );
        }
    }

    return $pdo;
}
