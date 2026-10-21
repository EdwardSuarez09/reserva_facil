<?php
include __DIR__ . '/../../../src/config/conexion.php';

try {
    // Mostrar la estructura de la tabla admin
    $result = $conn->query("DESCRIBE admin");
    
    echo "<h3>Columnas de la tabla 'admin':</h3>";
    echo "<ul>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<li><strong>" . $row['Field'] . "</strong> - " . $row['Type'] . "</li>";
    }
    
    echo "</ul>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 