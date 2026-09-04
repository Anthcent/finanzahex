<?php
$db = new mysqli('localhost', 'root', '', 'finazapersonal');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS print_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price_bs DECIMAL(10, 2) DEFAULT 0,
    price_usd DECIMAL(10, 2) DEFAULT 0,
    category VARCHAR(50) DEFAULT 'General',
    icon VARCHAR(50) DEFAULT 'print',
    color VARCHAR(20) DEFAULT 'indigo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($db->query($sql) === TRUE) {
    echo "Table 'print_products' created successfully";
} else {
    echo "Error creating table: " . $db->error;
}

$db->close();
?>
