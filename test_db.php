<?php
// Simple Database Connection Test
$hostname = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP password
$database = 'finazapersonal';

try {
    $conn = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h1>Conexión Exitosa a la Base de Datos '$database'</h1>";
    
    // Try to fetch counts
    $tables = ['accounts', 'categories', 'transactions'];
    echo "<ul>";
    foreach($tables as $t) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM $t");
            $count = $stmt->fetchColumn();
            echo "<li>Tabla <strong>$t</strong>: $count registros</li>";
        } catch(Exception $e) {
            echo "<li>Tabla <strong>$t</strong>: No encontrada o vacía (" . $e->getMessage() . ")</li>";
        }
    }
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<h1>Error de Conexión:</h1>" . $e->getMessage();
}
