<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol_usuario = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id'] ?? null;

// Procesar asignación si se envió el formulario
if ($_POST['accion'] ?? '' === 'asignar') {
    $mesa_id = $_POST['mesa_id'] ?? null;
    $restaurante_id = $_POST['restaurante_id'] ?? null;
    
    if ($mesa_id && $restaurante_id) {
        // Verificar que el admin solo pueda asignar a sus restaurantes
        if ($rol_usuario === 'admin') {
            $sql_verificar = "SELECT id FROM restaurantes WHERE id = ? AND id_admin = ?";
            $stmt_verificar = $conn->prepare($sql_verificar);
            $stmt_verificar->bind_param("ii", $restaurante_id, $id_usuario);
            $stmt_verificar->execute();
            $result_verificar = $stmt_verificar->get_result();
            
            if ($result_verificar->num_rows == 0) {
                echo "<p style='color: red;'>❌ No tienes permisos para asignar mesas a este restaurante.</p>";
            } else {
                // Proceder con la asignación
                $sql_asignar = "UPDATE mesas SET id_restaurante = ? WHERE id = ?";
                $stmt_asignar = $conn->prepare($sql_asignar);
                $stmt_asignar->bind_param("ii", $restaurante_id, $mesa_id);
                
                if ($stmt_asignar->execute()) {
                    echo "<p style='color: green;'>✅ Mesa asignada correctamente.</p>";
                } else {
                    echo "<p style='color: red;'>❌ Error al asignar mesa: " . $stmt_asignar->error . "</p>";
                }
            }
        } else {
            // Super admin puede asignar a cualquier restaurante
            $sql_asignar = "UPDATE mesas SET id_restaurante = ? WHERE id = ?";
            $stmt_asignar = $conn->prepare($sql_asignar);
            $stmt_asignar->bind_param("ii", $restaurante_id, $mesa_id);
            
            if ($stmt_asignar->execute()) {
                echo "<p style='color: green;'>✅ Mesa asignada correctamente.</p>";
            } else {
                echo "<p style='color: red;'>❌ Error al asignar mesa: " . $stmt_asignar->error . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Faltan datos para la asignación.</p>";
    }
}

echo "<h2>Asignar Mesas a Restaurantes</h2>";

// Obtener mesas
if ($rol_usuario === 'admin') {
    // Admin solo ve mesas de sus restaurantes o sin asignar
    $sql_mesas = "SELECT m.id, m.numero_mesa, m.capacidad, m.id_restaurante, r.nombre as restaurante_nombre 
                  FROM mesas m 
                  LEFT JOIN restaurantes r ON m.id_restaurante = r.id 
                  WHERE m.id_restaurante IS NULL OR r.id_admin = ? 
                  ORDER BY m.numero_mesa";
    $stmt_mesas = $conn->prepare($sql_mesas);
    $stmt_mesas->bind_param("i", $id_usuario);
    $stmt_mesas->execute();
    $result_mesas = $stmt_mesas->get_result();
} else {
    // Super admin ve todas las mesas
    $sql_mesas = "SELECT m.id, m.numero_mesa, m.capacidad, m.id_restaurante, r.nombre as restaurante_nombre 
                  FROM mesas m 
                  LEFT JOIN restaurantes r ON m.id_restaurante = r.id 
                  ORDER BY m.numero_mesa";
    $result_mesas = $conn->query($sql_mesas);
}

// Obtener restaurantes
if ($rol_usuario === 'admin') {
    $sql_restaurantes = "SELECT id, nombre FROM restaurantes WHERE id_admin = ? ORDER BY nombre";
    $stmt_restaurantes = $conn->prepare($sql_restaurantes);
    $stmt_restaurantes->bind_param("i", $id_usuario);
    $stmt_restaurantes->execute();
    $result_restaurantes = $stmt_restaurantes->get_result();
} else {
    $sql_restaurantes = "SELECT id, nombre FROM restaurantes ORDER BY nombre";
    $result_restaurantes = $conn->query($sql_restaurantes);
}

if ($result_mesas && $result_mesas->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th>Mesa</th><th>Capacidad</th><th>Restaurante Actual</th><th>Asignar a</th><th>Acción</th></tr>";
    
    while ($mesa = $result_mesas->fetch_assoc()) {
        echo "<tr>";
        echo "<td>Mesa " . $mesa['numero_mesa'] . "</td>";
        echo "<td>" . $mesa['capacidad'] . " personas</td>";
        echo "<td>" . ($mesa['restaurante_nombre'] ?? 'Sin asignar') . "</td>";
        echo "<td>";
        
        echo "<form method='POST' style='display: inline;'>";
        echo "<input type='hidden' name='accion' value='asignar'>";
        echo "<input type='hidden' name='mesa_id' value='" . $mesa['id'] . "'>";
        echo "<select name='restaurante_id' required>";
        echo "<option value=''>Seleccionar restaurante</option>";
        
        // Reset para el siguiente bucle
        if ($rol_usuario === 'admin') {
            $stmt_restaurantes->execute();
            $result_restaurantes = $stmt_restaurantes->get_result();
        } else {
            $result_restaurantes = $conn->query($sql_restaurantes);
        }
        
        while ($restaurante = $result_restaurantes->fetch_assoc()) {
            $selected = ($restaurante['id'] == $mesa['id_restaurante']) ? 'selected' : '';
            echo "<option value='" . $restaurante['id'] . "' $selected>" . $restaurante['nombre'] . "</option>";
        }
        
        echo "</select>";
        echo "</td>";
        echo "<td>";
        echo "<button type='submit' style='background: #f39c12; color: white; border: none; padding: 5px 10px; border-radius: 3px;'>Asignar</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No hay mesas disponibles para asignar.</p>";
}

// Mostrar resumen de asignaciones
echo "<h3>Resumen de Asignaciones:</h3>";
if ($rol_usuario === 'admin') {
    $sql_resumen = "SELECT r.nombre as restaurante, COUNT(m.id) as total_mesas 
                    FROM restaurantes r 
                    LEFT JOIN mesas m ON r.id = m.id_restaurante 
                    WHERE r.id_admin = ?
                    GROUP BY r.id, r.nombre 
                    ORDER BY r.nombre";
    $stmt_resumen = $conn->prepare($sql_resumen);
    $stmt_resumen->bind_param("i", $id_usuario);
    $stmt_resumen->execute();
    $result_resumen = $stmt_resumen->get_result();
} else {
    $sql_resumen = "SELECT r.nombre as restaurante, COUNT(m.id) as total_mesas 
                    FROM restaurantes r 
                    LEFT JOIN mesas m ON r.id = m.id_restaurante 
                    GROUP BY r.id, r.nombre 
                    ORDER BY r.nombre";
    $result_resumen = $conn->query($sql_resumen);
}

if ($result_resumen && $result_resumen->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Restaurante</th><th>Total Mesas</th></tr>";
    
    while ($row = $result_resumen->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['restaurante'] . "</td>";
        echo "<td>" . $row['total_mesas'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

$conn->close();
?> 