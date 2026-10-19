<?php
include __DIR__ . '/../src/config/conexion.php';

echo "<h2>ESTRUCTURA DE TABLA MESAS:</h2>";
$result = $conn->query("DESCRIBE mesas");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

echo "<h2>CONTENIDO DE TABLA MESAS:</h2>";
$result = $conn->query("SELECT * FROM mesas LIMIT 10");
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        // Obtener nombres de columnas
        $fields = $result->fetch_fields();
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<th>" . $field->name . "</th>";
        }
        echo "</tr>";
        
        // Mostrar datos
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay datos en la tabla mesas.</p>";
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?> 