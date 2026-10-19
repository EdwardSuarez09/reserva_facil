<?php
include __DIR__ . '/../src/config/conexion.php';

echo "Verificando tabla mesas...\n";

// Verificar si existen mesas
$result = $conn->query("SELECT * FROM mesas LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "Mesas existentes:\n";
    while ($row = $result->fetch_assoc()) {
        echo "Mesa ID: " . $row['id'] . " - Número: " . ($row['numero_mesa'] ?? 'N/A') . "\n";
    }
} else {
    echo "No hay mesas en la tabla. Creando mesas de ejemplo...\n";
    
    $mesas = [
        "INSERT INTO mesas (numero_mesa, capacidad, estado, descripcion) VALUES (1, 2, 'disponible', 'Mesa para 2 personas')",
        "INSERT INTO mesas (numero_mesa, capacidad, estado, descripcion) VALUES (2, 4, 'disponible', 'Mesa para 4 personas')",
        "INSERT INTO mesas (numero_mesa, capacidad, estado, descripcion) VALUES (3, 6, 'disponible', 'Mesa para 6 personas')",
        "INSERT INTO mesas (numero_mesa, capacidad, estado, descripcion) VALUES (4, 8, 'disponible', 'Mesa para 8 personas')"
    ];
    
    foreach ($mesas as $sql) {
        if ($conn->query($sql)) {
            echo "Mesa creada exitosamente\n";
        } else {
            echo "Error creando mesa: " . $conn->error . "\n";
        }
    }
}

$conn->close();
echo "Proceso completado.\n";
?> 