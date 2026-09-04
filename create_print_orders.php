<?php
$db = new mysqli('localhost', 'root', '', 'finazapersonal');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS print_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) DEFAULT 'Cliente',
    details TEXT, 
    total_bs DECIMAL(10, 2) DEFAULT 0,
    total_usd DECIMAL(10, 2) DEFAULT 0,
    paid_bs DECIMAL(10, 2) DEFAULT 0,
    paid_usd DECIMAL(10, 2) DEFAULT 0,
    status ENUM('paid', 'partial', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($db->query($sql) === TRUE) {
    echo "Table 'print_orders' created/checked successfully";
} else {
    echo "Error creating table: " . $db->error;
}

$db->close();
?>
