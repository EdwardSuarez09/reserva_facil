<?php
include __DIR__ . '/../src/config/conexion.php';

// Crear tabla reservas si no existe
$sql = "CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_restaurante INT NOT NULL,
    id_mesa INT NULL,
    nombre_cliente VARCHAR(100) NOT NULL,
    telefono_cliente VARCHAR(20) NOT NULL,
    email_cliente VARCHAR(100) NOT NULL,
    fecha_reserva DATE NOT NULL,
    hora_reserva TIME NOT NULL,
    numero_personas INT NOT NULL,
    comentarios TEXT,
    estado ENUM('pendiente', 'confirmada', 'completada', 'cancelada') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_restaurante) REFERENCES restaurantes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_mesa) REFERENCES mesas(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabla 'reservas' creada correctamente.";
} else {
    echo "Error al crear la tabla: " . $conn->error;
}

$conn->close();
?> 