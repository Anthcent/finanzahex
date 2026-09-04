<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "finazapersonal";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully\n";

// 1. Create sale_statuses table
$sql = "CREATE TABLE IF NOT EXISTS sale_statuses (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table sale_statuses created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// 2. Add order_status_id to sales table
// Check if column exists first
$checkCol = "SHOW COLUMNS FROM sales LIKE 'order_status_id'";
$result = $conn->query($checkCol);

if ($result->num_rows == 0) {
    $sql = "ALTER TABLE sales ADD COLUMN order_status_id INT(11) UNSIGNED DEFAULT 1";
    if ($conn->query($sql) === TRUE) {
        echo "Column order_status_id added successfully\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column order_status_id already exists\n";
}

// 3. Seed Statuses
// Use REPLACE INTO or similar to avoid duplicates if re-run, or check count
$seedData = [
    ['Pendiente', 'bg-slate-100 text-slate-600'],
    ['En Proceso', 'bg-indigo-100 text-indigo-600'],
    ['Por Entregar', 'bg-orange-100 text-orange-600'],
    ['Entregado', 'bg-emerald-100 text-emerald-600'],
    ['Cancelado', 'bg-rose-100 text-rose-600']
];

foreach ($seedData as $status) {
    $name = $status[0];
    $color = $status[1];
    
    // Simple check if exists
    $check = "SELECT id FROM sale_statuses WHERE name = '$name'";
    if ($conn->query($check)->num_rows == 0) {
        $insert = "INSERT INTO sale_statuses (name, color) VALUES ('$name', '$color')";
        if ($conn->query($insert) === TRUE) {
            echo "Inserted status: $name\n";
        } else {
            echo "Error inserting status: " . $conn->error . "\n";
        }
    }
}

$conn->close();
echo "Migration finished.";
