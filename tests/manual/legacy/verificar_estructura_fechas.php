<?php
include __DIR__ . '/../../../src/config/conexion.php';
include __DIR__ . '/../../../src/helpers/verificar_rol.php';
verificarRol(['superadmin']);

echo "<h2>Verificación y Actualización de Estructura de Base de Datos</h2>";

try {
    // Verificar si la tabla categorias tiene fecha_creacion
    $sql_check_categorias = "SHOW COLUMNS FROM categorias LIKE 'fecha_creacion'";
    $result = $conn->query($sql_check_categorias);
    
    if ($result->num_rows == 0) {
        echo "<p>Agregando campo fecha_creacion a tabla categorias...</p>";
        $sql_alter_categorias = "ALTER TABLE categorias ADD COLUMN fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        if ($conn->query($sql_alter_categorias)) {
            echo "<p style='color: green;'>✓ Campo fecha_creacion agregado a categorias exitosamente.</p>";
        } else {
            echo "<p style='color: red;'>✗ Error al agregar fecha_creacion a categorias: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Tabla categorias ya tiene campo fecha_creacion.</p>";
    }

    // Verificar si la tabla platos tiene fecha_creacion
    $sql_check_platos = "SHOW COLUMNS FROM platos LIKE 'fecha_creacion'";
    $result = $conn->query($sql_check_platos);
    
    if ($result->num_rows == 0) {
        echo "<p>Agregando campo fecha_creacion a tabla platos...</p>";
        $sql_alter_platos = "ALTER TABLE platos ADD COLUMN fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        if ($conn->query($sql_alter_platos)) {
            echo "<p style='color: green;'>✓ Campo fecha_creacion agregado a platos exitosamente.</p>";
        } else {
            echo "<p style='color: red;'>✗ Error al agregar fecha_creacion a platos: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Tabla platos ya tiene campo fecha_creacion.</p>";
    }

    // Verificar también platosmenu si existe
    $sql_check_table = "SHOW TABLES LIKE 'platosmenu'";
    $result = $conn->query($sql_check_table);
    
    if ($result->num_rows > 0) {
        $sql_check_platosmenu = "SHOW COLUMNS FROM platosmenu LIKE 'fecha_creacion'";
        $result = $conn->query($sql_check_platosmenu);
        
        if ($result->num_rows == 0) {
            echo "<p>Agregando campo fecha_creacion a tabla platosmenu...</p>";
            $sql_alter_platosmenu = "ALTER TABLE platosmenu ADD COLUMN fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            if ($conn->query($sql_alter_platosmenu)) {
                echo "<p style='color: green;'>✓ Campo fecha_creacion agregado a platosmenu exitosamente.</p>";
            } else {
                echo "<p style='color: red;'>✗ Error al agregar fecha_creacion a platosmenu: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: green;'>✓ Tabla platosmenu ya tiene campo fecha_creacion.</p>";
        }
    }

    // Verificar si la tabla mesas tiene id_restaurante
    $sql_check_mesas = "SHOW COLUMNS FROM mesas LIKE 'id_restaurante'";
    $result = $conn->query($sql_check_mesas);
    
    if ($result->num_rows == 0) {
        echo "<p>Agregando campo id_restaurante a tabla mesas...</p>";
        $sql_alter_mesas = "ALTER TABLE mesas ADD COLUMN id_restaurante INT";
        if ($conn->query($sql_alter_mesas)) {
            echo "<p style='color: green;'>✓ Campo id_restaurante agregado a mesas exitosamente.</p>";
            
            // Asignar un restaurante por defecto a las mesas existentes
            $sql_update_mesas = "UPDATE mesas SET id_restaurante = 1 WHERE id_restaurante IS NULL";
            $conn->query($sql_update_mesas);
            echo "<p style='color: blue;'>ℹ Mesas existentes asignadas al restaurante ID 1.</p>";
        } else {
            echo "<p style='color: red;'>✗ Error al agregar id_restaurante a mesas: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Tabla mesas ya tiene campo id_restaurante.</p>";
    }

    // Verificar si la tabla reservas tiene duracion
    $sql_check_reservas = "SHOW COLUMNS FROM reservas LIKE 'duracion'";
    $result = $conn->query($sql_check_reservas);
    
    if ($result->num_rows == 0) {
        echo "<p>Agregando campo duracion a tabla reservas...</p>";
        $sql_alter_reservas = "ALTER TABLE reservas ADD COLUMN duracion INT DEFAULT 120";
        if ($conn->query($sql_alter_reservas)) {
            echo "<p style='color: green;'>✓ Campo duracion agregado a reservas exitosamente.</p>";
        } else {
            echo "<p style='color: red;'>✗ Error al agregar duracion a reservas: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Tabla reservas ya tiene campo duracion.</p>";
    }

    echo "<h3>Verificación completada.</h3>";
    echo "<p><a href='../html/gestion_categoria.html'>← Volver a Gestión de Categorías</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?> 