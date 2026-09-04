<?php
$pdo = new PDO('mysql:host=localhost;dbname=finazapersonal;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "CREATE TABLE IF NOT EXISTS ai_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    messages JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $pdo->exec($sql);
    echo "✓ Tabla ai_conversations creada exitosamente\n";
    
    // Verificar
    $stmt = $pdo->query("SHOW TABLES LIKE 'ai_conversations'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Tabla verificada\n";
        
        // Mostrar estructura
        $stmt = $pdo->query("DESCRIBE ai_conversations");
        echo "\nEstructura de la tabla:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  {$row['Field']} - {$row['Type']}\n";
        }
    }
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
