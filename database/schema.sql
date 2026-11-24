
-- =====================================================
-- Customer Loyalty Rewards System
-- Database: customer_loyalty_db
-- =====================================================

USE customer_loyalty_db;

SET NAMES utf8mb4;

-- 1. USERS
-- Stores both customer and admin accounts

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    mobile VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- 2. PRODUCTS
-- Stores factory product information

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(150) NOT NULL,
    description TEXT,
    points_per_box INT UNSIGNED NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- 3. REWARDS
-- Stores rewards customers can claim

CREATE TABLE rewards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reward_name VARCHAR(150) NOT NULL,
    description TEXT,
    required_points INT UNSIGNED NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- 4. REWARD CODES
-- Stores hashed scratch-off codes
-- Each code has a fixed points value

CREATE TABLE reward_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    code_hash CHAR(64) CHARACTER SET ascii
        COLLATE ascii_bin NOT NULL UNIQUE,
    points_value INT UNSIGNED NOT NULL,
    status ENUM('unused', 'redeemed') NOT NULL DEFAULT 'unused',
    redeemed_by INT UNSIGNED DEFAULT NULL,
    redeemed_at DATETIME DEFAULT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_codes_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_codes_user
        FOREIGN KEY (redeemed_by)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    INDEX idx_codes_product (product_id),
    INDEX idx_codes_status (status),
    INDEX idx_codes_redeemed_by (redeemed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- 5. REDEMPTIONS
-- Stores customer reward redemptions and vouchers

CREATE TABLE redemptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    reward_id INT UNSIGNED NOT NULL,
    points_spent INT UNSIGNED NOT NULL,
    voucher_code VARCHAR(40) CHARACTER SET ascii
        COLLATE ascii_bin NOT NULL UNIQUE,
    status ENUM('generated', 'used', 'cancelled')
        NOT NULL DEFAULT 'generated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_redemptions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_redemptions_reward
        FOREIGN KEY (reward_id)
        REFERENCES rewards(id)
        ON DELETE RESTRICT,

    INDEX idx_redemptions_user (user_id),
    INDEX idx_redemptions_reward (reward_id),
    INDEX idx_redemptions_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- 6. POINT TRANSACTIONS
-- Maintains the complete points ledger
-- Positive points_delta means earned or deducted
-- transaction_type determines the operation

CREATE TABLE point_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    transaction_type ENUM('earn', 'redeem') NOT NULL,
    points_delta INT UNSIGNED NOT NULL,
    reward_code_id BIGINT UNSIGNED DEFAULT NULL,
    redemption_id BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_transactions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_transactions_code
        FOREIGN KEY (reward_code_id)
        REFERENCES reward_codes(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_transactions_redemption
        FOREIGN KEY (redemption_id)
        REFERENCES redemptions(id)
        ON DELETE RESTRICT,

    UNIQUE KEY uq_transaction_code (reward_code_id),
    UNIQUE KEY uq_transaction_redemption (redemption_id),

    INDEX idx_transactions_user (user_id),
    INDEX idx_transactions_date (created_at),
    INDEX idx_transactions_type (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- 7. CODE ATTEMPTS
-- Records invalid, reused and ineligible code attempts

CREATE TABLE code_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    submitted_code_hash CHAR(64) CHARACTER SET ascii
        COLLATE ascii_bin NOT NULL,
    attempt_status ENUM(
        'invalid',
        'already_redeemed',
        'inactive_product',
        'ineligible'
    ) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_attempts_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL,

    INDEX idx_attempts_user (user_id),
    INDEX idx_attempts_status (attempt_status),
    INDEX idx_attempts_date (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- END OF DATABASE SCHEMA
-- =====================================================
