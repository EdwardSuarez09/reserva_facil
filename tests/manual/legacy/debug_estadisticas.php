<?php
include __DIR__ . '/../../../src/config/conexion.php';
include __DIR__ . '/../../../src/helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol_usuario = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id'] ?? null;

echo "<h2>Debug de Estadísticas - ReservaFácil</h2>";
echo "<p><strong>Usuario:</strong> $rol_usuario (ID: $id_usuario)</p>";

try {
    // 1. Verificar tablas existentes
    echo "<h3>1. Tablas Existentes</h3>";
    $sql_tables = "SHOW TABLES";
    $result = $conn->query($sql_tables);
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "<br>";
    }

    // 2. Verificar estructura de categorias
    echo "<h3>2. Estructura de tabla 'categorias'</h3>";
    $sql_categorias_structure = "DESCRIBE categorias";
    $result = $conn->query($sql_categorias_structure);
    if ($result) {
        echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
        }
        echo "</table>";
    }

    // 3. Contar categorías
    echo "<h3>3. Datos de Categorías</h3>";
    $sql_count = "SELECT COUNT(*) as total FROM categorias";
    $result = $conn->query($sql_count);
    $count = $result->fetch_assoc()['total'];
    echo "<p><strong>Total categorías:</strong> $count</p>";

    if ($count > 0) {
        $sql_list = "SELECT * FROM categorias LIMIT 5";
        $result = $conn->query($sql_list);
        echo "<table border='1'><tr><th>ID</th><th>Nombre</th><th>Otros</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['nombre']}</td><td>" . json_encode($row) . "</td></tr>";
        }
        echo "</table>";
    }

    // 4. Verificar qué tabla de platos existe
    echo "<h3>4. Verificar Tablas de Platos</h3>";
    $sql_check_platos = "SHOW TABLES LIKE 'platos'";
    $result_platos = $conn->query($sql_check_platos);
    echo "<p><strong>Tabla 'platos':</strong> " . ($result_platos->num_rows > 0 ? "Existe" : "No existe") . "</p>";

    $sql_check_platosmenu = "SHOW TABLES LIKE 'platosmenu'";
    $result_platosmenu = $conn->query($sql_check_platosmenu);
    echo "<p><strong>Tabla 'platosmenu':</strong> " . ($result_platosmenu->num_rows > 0 ? "Existe" : "No existe") . "</p>";

    // Determinar tabla a usar
    $tabla_platos = 'platos';
    if ($result_platosmenu->num_rows > 0) {
        $tabla_platos = 'platosmenu';
    }
    echo "<p><strong>Usando tabla:</strong> $tabla_platos</p>";

    // 5. Verificar platos
    if ($result_platos->num_rows > 0 || $result_platosmenu->num_rows > 0) {
        echo "<h3>5. Datos de Platos ($tabla_platos)</h3>";
        $sql_count_platos = "SELECT COUNT(*) as total FROM $tabla_platos";
        $result = $conn->query($sql_count_platos);
        $count_platos = $result->fetch_assoc()['total'];
        echo "<p><strong>Total platos:</strong> $count_platos</p>";

        if ($count_platos > 0) {
            $sql_list_platos = "SELECT * FROM $tabla_platos LIMIT 3";
            $result = $conn->query($sql_list_platos);
            echo "<table border='1'><tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Otros</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>{$row['id']}</td><td>{$row['nombre']}</td><td>{$row['precio']}</td><td>" . json_encode($row) . "</td></tr>";
            }
            echo "</table>";
        }
    }

    // 6. Verificar mesas
    echo "<h3>6. Datos de Mesas</h3>";
    $sql_count_mesas = "SELECT COUNT(*) as total FROM mesas";
    $result = $conn->query($sql_count_mesas);
    $count_mesas = $result->fetch_assoc()['total'];
    echo "<p><strong>Total mesas:</strong> $count_mesas</p>";

    if ($count_mesas > 0) {
        echo "<h4>Estructura de tabla 'mesas'</h4>";
        $sql_mesas_structure = "DESCRIBE mesas";
        $result = $conn->query($sql_mesas_structure);
        if ($result) {
            echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
            }
            echo "</table>";
        }

        $sql_list_mesas = "SELECT * FROM mesas LIMIT 3";
        $result = $conn->query($sql_list_mesas);
        echo "<table border='1'><tr><th>ID</th><th>Número</th><th>Capacidad</th><th>Otros</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['numero_mesa']}</td><td>{$row['capacidad']}</td><td>" . json_encode($row) . "</td></tr>";
        }
        echo "</table>";
    }

    // 7. Verificar reservas
    echo "<h3>7. Datos de Reservas</h3>";
    $sql_count_reservas = "SELECT COUNT(*) as total FROM reservas";
    $result = $conn->query($sql_count_reservas);
    $count_reservas = $result->fetch_assoc()['total'];
    echo "<p><strong>Total reservas:</strong> $count_reservas</p>";

    // 8. Verificar restaurantes
    echo "<h3>8. Datos de Restaurantes</h3>";
    $sql_count_restaurantes = "SELECT COUNT(*) as total FROM restaurantes";
    $result = $conn->query($sql_count_restaurantes);
    $count_restaurantes = $result->fetch_assoc()['total'];
    echo "<p><strong>Total restaurantes:</strong> $count_restaurantes</p>";

    if ($count_restaurantes > 0 && $rol_usuario !== 'superadmin') {
        $sql_user_restaurants = "SELECT * FROM restaurantes WHERE id_admin = ?";
        $stmt = $conn->prepare($sql_user_restaurants);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        echo "<p><strong>Restaurantes del usuario:</strong> " . $result->num_rows . "</p>";
        while ($row = $result->fetch_assoc()) {
            echo "- ID: {$row['id']}, Nombre: {$row['nombre']}<br>";
        }
    }

    // 9. Probar consulta específica de categorías
    echo "<h3>9. Test Consulta Categorías (Superadmin)</h3>";
    $sql_test = "SELECT COUNT(*) as total FROM categorias";
    $result = $conn->query($sql_test);
    if ($result) {
        $total = $result->fetch_assoc()['total'];
        echo "<p><strong>Total categorías (test):</strong> $total</p>";
    } else {
        echo "<p style='color: red;'>Error en consulta: " . $conn->error . "</p>";
    }

    // 10. Test específico para admin normal
    if ($rol_usuario !== 'superadmin' && $id_usuario) {
        echo "<h3>10. Test Consulta Categorías (Admin Normal)</h3>";
        
        // Verificar relación de tablas
        $sql_test_admin = "SELECT COUNT(DISTINCT c.id) as total 
                          FROM categorias c 
                          INNER JOIN $tabla_platos p ON c.id = p.categoria_id 
                          INNER JOIN restaurantes r ON p.id_restaurante = r.id 
                          WHERE r.id_admin = ?";
        $stmt = $conn->prepare($sql_test_admin);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $total = $result->fetch_assoc()['total'];
            echo "<p><strong>Categorías del admin (test):</strong> $total</p>";
        } else {
            echo "<p style='color: red;'>Error en consulta admin: " . $conn->error . "</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='../html/gestion_categoria.html'>← Volver a Gestión de Categorías</a></p>";

$conn->close();
?> 