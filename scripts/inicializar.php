<?php
include __DIR__ . '/../src/config/conexion.php';

// Verificar si la tabla reservas existe
$check_table = "SHOW TABLES LIKE 'reservas'";
$table_exists = $conn->query($check_table)->num_rows > 0;

if (!$table_exists) {
    // Crear la tabla reservas si no existe
    $create_table = "CREATE TABLE reservas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_restaurante INT NOT NULL,
        cliente_id INT DEFAULT NULL,
        id_mesa INT DEFAULT NULL,
        nombre_cliente VARCHAR(100) NOT NULL,
        telefono_cliente VARCHAR(20) NOT NULL,
        email_cliente VARCHAR(100) NOT NULL,
        fecha_reserva DATE NOT NULL,
        hora_reserva TIME NOT NULL,
        numero_personas INT NOT NULL,
        duracion INT DEFAULT 120,
        estado ENUM('pendiente', 'confirmada', 'completada', 'cancelada') DEFAULT 'pendiente',
        comentarios TEXT,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id_restaurante) REFERENCES restaurantes(id) ON DELETE CASCADE,
        FOREIGN KEY (id_mesa) REFERENCES mesas(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($create_table)) {
        echo "Tabla reservas creada correctamente.\n";
    } else {
        echo "Error al crear la tabla reservas: " . $conn->error . "\n";
        exit;
    }
} else {
    // Verificar estructura de la tabla existente
    $check_columns = "SHOW COLUMNS FROM reservas";
    $columns_result = $conn->query($check_columns);
    $existing_columns = [];
    while($col = $columns_result->fetch_assoc()) {
        $existing_columns[] = $col['Field'];
    }
    
    // Columnas requeridas
    $required_columns = [
        'id' => "INT AUTO_INCREMENT PRIMARY KEY",
        'id_restaurante' => "INT NOT NULL",
        'cliente_id' => "INT DEFAULT NULL",
        'id_mesa' => "INT DEFAULT NULL",
        'nombre_cliente' => "VARCHAR(100) NOT NULL",
        'telefono_cliente' => "VARCHAR(20) NOT NULL",
        'email_cliente' => "VARCHAR(100) NOT NULL",
        'fecha_reserva' => "DATE NOT NULL",
        'hora_reserva' => "TIME NOT NULL",
        'numero_personas' => "INT NOT NULL",
        'duracion' => "INT DEFAULT 120",
        'estado' => "ENUM('pendiente', 'confirmada', 'completada', 'cancelada') DEFAULT 'pendiente'",
        'comentarios' => "TEXT",
        'fecha_creacion' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'fecha_modificacion' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    // Agregar columnas faltantes
    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            $alter_sql = "ALTER TABLE reservas ADD COLUMN $column $definition";
            if ($conn->query($alter_sql)) {
                echo "Columna '$column' agregada correctamente.\n";
            } else {
                echo "Error al agregar la columna '$column': " . $conn->error . "\n";
            }
        }
    }
    
    // Verificar y agregar claves foráneas si no existen
    $check_fk = "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                 WHERE TABLE_NAME = 'reservas' AND COLUMN_NAME = 'id_restaurante' 
                 AND CONSTRAINT_SCHEMA = DATABASE()";
    $fk_exists = $conn->query($check_fk)->num_rows > 0;
    
    if (!$fk_exists && in_array('id_restaurante', $existing_columns)) {
        $add_fk = "ALTER TABLE reservas ADD FOREIGN KEY (id_restaurante) REFERENCES restaurantes(id) ON DELETE CASCADE";
        if ($conn->query($add_fk)) {
            echo "Clave foránea para id_restaurante agregada correctamente.\n";
        } else {
            echo "Error al agregar clave foránea para id_restaurante: " . $conn->error . "\n";
        }
    }
    
    $check_fk_mesa = "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                      WHERE TABLE_NAME = 'reservas' AND COLUMN_NAME = 'id_mesa' 
                      AND CONSTRAINT_SCHEMA = DATABASE()";
    $fk_mesa_exists = $conn->query($check_fk_mesa)->num_rows > 0;
    
    if (!$fk_mesa_exists && in_array('id_mesa', $existing_columns)) {
        $add_fk_mesa = "ALTER TABLE reservas ADD FOREIGN KEY (id_mesa) REFERENCES mesas(id) ON DELETE SET NULL";
        if ($conn->query($add_fk_mesa)) {
            echo "Clave foránea para id_mesa agregada correctamente.\n";
        } else {
            echo "Error al agregar clave foránea para id_mesa: " . $conn->error . "\n";
        }
    }
}

echo "Inicialización de la tabla reservas completada.\n";
$conn->close();
?> 