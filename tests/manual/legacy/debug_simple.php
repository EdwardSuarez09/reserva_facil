<?php
include __DIR__ . '/../../../src/config/conexion.php';

echo "=== DEBUG SIMPLE - RESERVA FACIL ===\n";

try {
    // 1. Verificar conexión
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    echo "✓ Conexión a base de datos exitosa\n";

    // 2. Verificar tablas existentes
    echo "\n--- TABLAS EXISTENTES ---\n";
    $sql_tables = "SHOW TABLES";
    $result = $conn->query($sql_tables);
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }

    // 3. Verificar categorías
    echo "\n--- CATEGORIAS ---\n";
    $sql_count = "SELECT COUNT(*) as total FROM categorias";
    $result = $conn->query($sql_count);
    if ($result) {
        $count = $result->fetch_assoc()['total'];
        echo "Total categorías: $count\n";
        
        if ($count > 0) {
            echo "Listando categorías...\n";
            $sql_list = "SELECT id, nombre FROM categorias LIMIT 5";
            $result = $conn->query($sql_list);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    echo "  - ID: {$row['id']}, Nombre: {$row['nombre']}\n";
                }
            } else {
                echo "  ERROR listando categorías: " . $conn->error . "\n";
            }
        }
    } else {
        echo "ERROR: " . $conn->error . "\n";
    }

    // 4. Verificar estructura de categorías
    echo "\n--- ESTRUCTURA CATEGORIAS ---\n";
    $sql_structure = "DESCRIBE categorias";
    $result = $conn->query($sql_structure);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "ERROR: " . $conn->error . "\n";
    }

    // 5. Verificar tabla de platos
    echo "\n--- PLATOS ---\n";
    $sql_check_platos = "SHOW TABLES LIKE 'platos'";
    $result_platos = $conn->query($sql_check_platos);
    echo "Tabla 'platos': " . ($result_platos->num_rows > 0 ? "Existe" : "No existe") . "\n";

    $sql_check_platosmenu = "SHOW TABLES LIKE 'platosmenu'";
    $result_platosmenu = $conn->query($sql_check_platosmenu);
    echo "Tabla 'platosmenu': " . ($result_platosmenu->num_rows > 0 ? "Existe" : "No existe") . "\n";

    // Determinar tabla a usar
    $tabla_platos = 'platos';
    if ($result_platosmenu->num_rows > 0) {
        $tabla_platos = 'platosmenu';
    }
    echo "Usando tabla: $tabla_platos\n";

    // 6. Verificar estructura de platos
    echo "\n--- ESTRUCTURA PLATOSMENU ---\n";
    $sql_structure = "DESCRIBE platosmenu";
    $result = $conn->query($sql_structure);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "ERROR: " . $conn->error . "\n";
    }

    // 7. Verificar datos de platos
    echo "\n--- DATOS PLATOSMENU ---\n";
    $sql_count_platos = "SELECT COUNT(*) as total FROM platosmenu";
    $result = $conn->query($sql_count_platos);
    if ($result) {
        $count_platos = $result->fetch_assoc()['total'];
        echo "Total platos: $count_platos\n";
        
        if ($count_platos > 0) {
            $sql_sample = "SELECT id, nombre, categoria_id FROM platosmenu LIMIT 3";
            $result = $conn->query($sql_sample);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    echo "  - ID: {$row['id']}, Nombre: {$row['nombre']}, Categoría: {$row['categoria_id']}\n";
                }
            }
        }
    } else {
        echo "ERROR: " . $conn->error . "\n";
    }

    // 8. Test JOIN categorías con platos
    echo "\n--- TEST JOIN CATEGORIAS-PLATOS ---\n";
    $sql_join_test = "SELECT c.id as cat_id, c.nombre as cat_nombre, COUNT(p.id) as num_platos
                      FROM categorias c 
                      LEFT JOIN platosmenu p ON c.id = p.categoria_id 
                      GROUP BY c.id, c.nombre";
    $result = $conn->query($sql_join_test);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  - Categoría {$row['cat_id']}: {$row['cat_nombre']} ({$row['num_platos']} platos)\n";
        }
    } else {
        echo "ERROR en JOIN: " . $conn->error . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n--- FIN DEBUG ---\n";
$conn->close();
?> 