-- Database: finazapersonal (or educbogv_finanza)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 1. Base Tables (from InitialSchema)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM(
        'cash',
        'bank',
        'wallet',
        'investment'
    ) NOT NULL,
    `balance` DECIMAL(15, 2) DEFAULT 0.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM(
        'income',
        'expense',
        'investment',
        'transfer'
    ) NOT NULL,
    `icon` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `account_id` INT(11) NOT NULL,
    `category_id` INT(11) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `amount_usd` DECIMAL(15, 2) DEFAULT 0.00,
    `exchange_rate` DECIMAL(15, 4) DEFAULT 1.0000,
    `type` ENUM(
        'income',
        'expense',
        'investment',
        'transfer'
    ) NOT NULL,
    `description` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `account_id` (`account_id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `transactions_account_fk` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT `transactions_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `transaction_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `transaction_id` INT(11) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `quantity` INT(11) DEFAULT 1,
    `price` DECIMAL(15, 2) NOT NULL,
    `total` DECIMAL(15, 2) GENERATED ALWAYS AS (`quantity` * `price`) STORED,
    PRIMARY KEY (`id`),
    KEY `transaction_id` (`transaction_id`),
    CONSTRAINT `transaction_items_fk` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Base Seeds
INSERT INTO
    `accounts` (`name`, `type`, `balance`)
VALUES ('Efectivo', 'cash', 0.00),
    (
        'Banco Principal',
        'bank',
        0.00
    );

INSERT INTO
    `categories` (`name`, `type`, `icon`)
VALUES (
        'Comida',
        'expense',
        'fast-food'
    ),
    (
        'Transporte',
        'expense',
        'car'
    ),
    ('Salario', 'income', 'cash'),
    ('Ventas', 'income', 'store');

-- --------------------------------------------------------
-- 2. Transaction Enhancements
-- --------------------------------------------------------

-- Add Owner (from AddOwnerToTransactions)
-- Check if column exists logic is not standard SQL, so we use ADD COLUMN IF NOT EXISTS logic purely via SQL by ignoring error or just ADDing.
-- For a clean install, we just ADD it.
ALTER TABLE `transactions`
ADD COLUMN `owner` VARCHAR(50) DEFAULT 'Business' AFTER `type`;

-- Add Account Fields (from AddTempAccountFields)
ALTER TABLE `accounts`
ADD COLUMN `status` ENUM('active', 'closed') DEFAULT 'active' AFTER `balance`,
ADD COLUMN `parent_account_id` INT(11) DEFAULT NULL AFTER `status`;

-- --------------------------------------------------------
-- 3. Sales Module (from CreateSalesTables and AddSaleStatuses)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sales` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `date` DATE NOT NULL,
    `product` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `amount_usd` DECIMAL(15, 2) NOT NULL,
    `exchange_rate` DECIMAL(10, 4) NOT NULL,
    `customer` VARCHAR(255) NOT NULL,
    `status` ENUM('paid', 'partial') DEFAULT 'paid',
    `reference` VARCHAR(100) DEFAULT NULL,
    `description` TEXT,
    `paid_amount` DECIMAL(15, 2) DEFAULT 0.00,
    `paid_amount_usd` DECIMAL(15, 2) DEFAULT 0.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `sale_payments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `sale_id` INT(11) UNSIGNED NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL,
    `amount_usd` DECIMAL(15, 2) NOT NULL,
    `rate` DECIMAL(10, 4) NOT NULL,
    `date` DATE NOT NULL,
    `reference` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `sale_id` (`sale_id`),
    CONSTRAINT `sale_payments_sale_fk` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `sale_statuses` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(50) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Add order_status_id to sales
ALTER TABLE `sales`
ADD COLUMN `order_status_id` INT(11) UNSIGNED DEFAULT 1 AFTER `status`;

-- Seed Statuses
INSERT INTO
    `sale_statuses` (`name`, `color`)
VALUES (
        'Pendiente',
        'bg-slate-100 text-slate-600'
    ),
    (
        'En Proceso',
        'bg-indigo-100 text-indigo-600'
    ),
    (
        'Por Entregar',
        'bg-orange-100 text-orange-600'
    ),
    (
        'Entregado',
        'bg-emerald-100 text-emerald-600'
    ),
    (
        'Cancelado',
        'bg-rose-100 text-rose-600'
    );

-- --------------------------------------------------------
-- 4. Inventory Module (from CreateInventoryTables and AddCharacteristics)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `inventory_categories` (
    `id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('product', 'material') DEFAULT 'product',
    `created_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `inventory_items` (
    `id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT(5) UNSIGNED DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `price` DECIMAL(10, 2) DEFAULT 0.00,
    `cost` DECIMAL(10, 2) DEFAULT 0.00,
    `stock` DECIMAL(10, 2) DEFAULT 0.00,
    `unit` VARCHAR(20) DEFAULT 'unid',
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `inventory_items_category_fk` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

ALTER TABLE `inventory_items`
ADD COLUMN `characteristics` TEXT DEFAULT NULL AFTER `unit`;

CREATE TABLE IF NOT EXISTS `inventory_movements` (
    `id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id` INT(5) UNSIGNED NOT NULL,
    `type` ENUM(
        'in',
        'out',
        'sale',
        'adjustment'
    ) NOT NULL,
    `quantity` DECIMAL(10, 2) NOT NULL,
    `date` DATETIME NOT NULL,
    `reference` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `item_id` (`item_id`),
    CONSTRAINT `inventory_movements_item_fk` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS `sale_details` (
    `id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `sale_id` INT(5) UNSIGNED NOT NULL,
    `item_id` INT(5) UNSIGNED DEFAULT NULL,
    `quantity` DECIMAL(10, 2) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sale_id` (`sale_id`),
    KEY `item_id` (`item_id`),
    CONSTRAINT `sale_details_sale_fk` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `sale_details_item_fk` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------
-- 5. AI Module (from CreateAIConversations)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ai_conversations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `messages` JSON NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `created_at` (`created_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

COMMIT;