CREATE DATABASE IF NOT EXISTS finazapersonal;

USE finazapersonal;

CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM(
        'cash',
        'bank',
        'wallet',
        'investment'
    ) NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM(
        'income',
        'expense',
        'investment',
        'transfer'
    ) NOT NULL,
    icon VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    type ENUM(
        'income',
        'expense',
        'investment',
        'transfer'
    ) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts (id),
    FOREIGN KEY (category_id) REFERENCES categories (id)
);

CREATE TABLE IF NOT EXISTS transaction_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(15, 2) NOT NULL,
    total DECIMAL(15, 2) GENERATED ALWAYS AS (quantity * price) STORED,
    FOREIGN KEY (transaction_id) REFERENCES transactions (id) ON DELETE CASCADE
);

-- Seed defaults
INSERT INTO
    accounts (name, type, balance)
VALUES ('Efectivo', 'cash', 0),
    ('Banco Principal', 'bank', 0);

INSERT INTO
    categories (name, type, icon)
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