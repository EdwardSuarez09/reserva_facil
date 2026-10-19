<?php
include __DIR__ . '/../../config/conexion.php';

echo "<h3>Agregando campos de horario específicos...</h3>";

// Verificar si los campos ya existen
$check_sql = "SHOW COLUMNS FROM restaurantes LIKE 'horario_apertura'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    // Agregar campos de horario
    $sql1 = "ALTER TABLE restaurantes ADD COLUMN horario_apertura TIME DEFAULT '10:00:00'";
    $sql2 = "ALTER TABLE restaurantes ADD COLUMN horario_cierre TIME DEFAULT '22:00:00'";
    
    if ($conn->query($sql1)) {
        echo "✓ Campo horario_apertura agregado<br>";
    } else {
        echo "✗ Error agregando horario_apertura: " . $conn->error . "<br>";
    }
    
    if ($conn->query($sql2)) {
        echo "✓ Campo horario_cierre agregado<br>";
    } else {
        echo "✗ Error agregando horario_cierre: " . $conn->error . "<br>";
    }
    
    // Actualizar horarios basados en el campo existente
    $sql_update = "UPDATE restaurantes SET 
                   horario_apertura = '10:00:00', 
                   horario_cierre = '21:00:00' 
                   WHERE nombre = 'Calipollo'";
    
    if ($conn->query($sql_update)) {
        echo "✓ Horarios de Calipollo actualizados (10:00 - 21:00)<br>";
    } else {
        echo "✗ Error actualizando horarios: " . $conn->error . "<br>";
    }
    
} else {
    echo "✓ Los campos de horario ya existen<br>";
}

// Verificar resultado final
echo "<hr><h3>Horarios actualizados:</h3>";
$sql_check = "SELECT nombre, horario_apertura, horario_cierre FROM restaurantes";
$result = $conn->query($sql_check);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo $row['nombre'] . ": " . $row['horario_apertura'] . " - " . $row['horario_cierre'] . "<br>";
    }
}

$conn->close();
?> 