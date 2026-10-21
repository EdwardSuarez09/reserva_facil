<?php
include __DIR__ . '/../../../src/config/conexion.php';

try {
    // Mostrar todas las columnas que encuentra
    $columns_result = $conn->query("DESCRIBE admin");
    
    echo "<h3>🔍 DEBUG: Columnas encontradas en tabla admin:</h3>";
    echo "<ul>";
    
    $all_columns = [];
    while ($row = $columns_result->fetch_assoc()) {
        $all_columns[] = $row['Field'];
        echo "<li><strong>" . $row['Field'] . "</strong> (lowercase: " . strtolower($row['Field']) . ")</li>";
    }
    
    echo "</ul>";
    
    // Intentar detectar columna de contraseña
    echo "<h3>🔍 Búsqueda de columna de contraseña:</h3>";
    $possible_names = ['password', 'contrasena', 'contrasenha', 'clave', 'pass', 'contraseña'];
    
    $found = false;
    foreach ($all_columns as $column) {
        $column_lower = strtolower($column);
        echo "<p>Revisando: <strong>$column</strong> (lowercase: $column_lower)</p>";
        
        if (in_array($column_lower, $possible_names)) {
            echo "✅ <strong>ENCONTRADA!</strong> Columna de contraseña: $column<br>";
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo "❌ <strong>NO ENCONTRADA</strong> en la lista de nombres conocidos<br>";
    }
    
    echo "<h3>📋 Lista de nombres que busco:</h3>";
    echo "<p>" . implode(", ", $possible_names) . "</p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 