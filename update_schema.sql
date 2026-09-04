USE finazapersonal;

ALTER TABLE transactions
ADD COLUMN amount_usd DECIMAL(15, 2) DEFAULT 0.00 AFTER amount,
ADD COLUMN exchange_rate DECIMAL(15, 2) DEFAULT 1.00 AFTER amount_usd;

ALTER TABLE transaction_items
ADD COLUMN price_usd DECIMAL(15, 2) DEFAULT 0.00 AFTER price,
ADD COLUMN description VARCHAR(255) NULL AFTER name;