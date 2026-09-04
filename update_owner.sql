ALTER TABLE transactions
ADD COLUMN owner VARCHAR(50) DEFAULT 'Business' AFTER type;