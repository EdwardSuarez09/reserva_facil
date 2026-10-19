<?php
include __DIR__ . '/../src/config/conexion.php';

// Agregar campos cliente_id y duracion a la tabla reservas si no existen
$sql_add_cliente_id = "ALTER TABLE reservas ADD COLUMN IF NOT EXISTS cliente_id INT NULL AFTER id";
$sql_add_duracion = "ALTER TABLE reservas ADD COLUMN IF NOT EXISTS duracion INT DEFAULT 120 AFTER numero_personas";

try {
    // Agregar campo cliente_id
    if ($conn->query($sql_add_cliente_id) === TRUE) {
        echo "Campo 'cliente_id' agregado correctamente.\n";
    } else {
        echo "Error al agregar campo 'cliente_id': " . $conn->error . "\n";
    }
    
    // Agregar campo duracion
    if ($conn->query($sql_add_duracion) === TRUE) {
        echo "Campo 'duracion' agregado correctamente.\n";
    } else {
        echo "Error al agregar campo 'duracion': " . $conn->error . "\n";
    }
    
    echo "Tabla 'reservas' actualizada correctamente.";
    
} catch (Exception $e) {
    echo "Error al actualizar la tabla: " . $e->getMessage();
}

$conn->close();
?> 