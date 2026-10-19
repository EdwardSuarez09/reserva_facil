<?php
// Mostrar errores durante desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include __DIR__ . '/../../config/conexion.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin', 'superadmin'])) {
    echo "<p style='color: red; padding: 20px;'>Acceso no autorizado. Sesión o rol inválido.</p>";
    exit;
}

$rol_usuario = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id'] ?? null;

// Verificar si la tabla mesas tiene el campo id_restaurante
$sql_check_campo = "SHOW COLUMNS FROM mesas LIKE 'id_restaurante'";
$result_campo = $conn->query($sql_check_campo);
$tiene_id_restaurante = ($result_campo && $result_campo->num_rows > 0);

if ($rol_usuario === 'admin') {
    // Admin solo ve mesas de sus restaurantes
    if ($tiene_id_restaurante) {
        $sql = "SELECT m.*, r.nombre as restaurante_nombre 
                FROM mesas m 
                INNER JOIN restaurantes r ON m.id_restaurante = r.id 
                WHERE r.id_admin = ? 
                ORDER BY m.numero_mesa ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Si no tiene el campo, mostrar todas (comportamiento temporal)
        $sql = "SELECT * FROM mesas ORDER BY numero_mesa ASC";
        $result = $conn->query($sql);
    }
} else {
    // Super admin ve todas las mesas
    if ($tiene_id_restaurante) {
        $sql = "SELECT m.*, r.nombre as restaurante_nombre 
                FROM mesas m 
                LEFT JOIN restaurantes r ON m.id_restaurante = r.id 
                ORDER BY m.numero_mesa ASC";
    } else {
        $sql = "SELECT * FROM mesas ORDER BY numero_mesa ASC";
    }
    $result = $conn->query($sql);
}

if ($result && $result->num_rows > 0) {
    echo "<table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden;'>
            <thead>
              <tr>
                <th style='background: #f39c12; color: white; padding: 10px;'>N° Mesa</th>
                <th style='background: #f39c12; color: white; padding: 10px;'>Capacidad</th>";
    
    // Solo mostrar columna de restaurante para super admin
    if ($rol_usuario === 'superadmin' && $tiene_id_restaurante) {
        echo "<th style='background: #f39c12; color: white; padding: 10px;'>Restaurante</th>";
    }
    
    echo "      <th style='background: #f39c12; color: white; padding: 10px;'>Imagen</th>
                <th style='background: #f39c12; color: white; padding: 10px;'>Acciones</th>
              </tr>
            </thead>
            <tbody>";

    while ($row = $result->fetch_assoc()) {
        $imagen = $row['imagen']
            ? "<img src='data:image/jpeg;base64," . base64_encode($row['imagen']) . "' style='width:60px; height:60px; border-radius:8px; object-fit:cover;'>"
            : "<span style='color:#888;'>Sin imagen</span>";

        echo "<tr style='text-align: center; background: white;'>
                <td style='padding: 10px;'>{$row['numero_mesa']}</td>
                <td style='padding: 10px;'>{$row['capacidad']} personas</td>";
        
        // Solo mostrar restaurante para super admin
        if ($rol_usuario === 'superadmin' && $tiene_id_restaurante) {
            $restaurante_nombre = $row['restaurante_nombre'] ?? 'Sin asignar';
            echo "<td style='padding: 10px;'>$restaurante_nombre</td>";
        }
        
        echo "    <td style='padding: 10px;'>$imagen</td>
                <td style='padding: 10px; white-space: nowrap;'>
                  <button onclick='editarMesa({$row['id']})' style='background:#3498db; color:white; border:none; padding:5px 10px; border-radius:5px;'>Editar</button>
                  <button onclick='eliminarMesa({$row['id']})' style='background:#e74c3c; color:white; border:none; padding:5px 10px; border-radius:5px; margin-left:5px;'>Eliminar</button>
                </td>
              </tr>";
    }

    echo "</tbody></table>";
} else {
    if ($rol_usuario === 'admin') {
        echo "<p style='padding: 20px; text-align: center;'>No tienes mesas registradas aún. <br><strong>¡Agrega tu primera mesa usando el formulario de arriba!</strong></p>";
    } else {
        echo "<p style='padding: 20px; text-align: center;'>No hay mesas registradas en el sistema.</p>";
    }
}

$conn->close();
?>
