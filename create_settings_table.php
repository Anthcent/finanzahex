<?php
$db = new mysqli('localhost', 'root', '', 'finazapersonal');
if ($db->connect_error) die("Connection failed: " . $db->connect_error);

$sql = "CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($db->query($sql) === TRUE) {
    // Insert default if not exists
    $db->query("INSERT IGNORE INTO settings (`key`, `value`) VALUES ('default_print_account', '1')");
    echo "Table 'settings' ready.";
} else {
    echo "Error: " . $db->error;
}
$db->close();
?>
