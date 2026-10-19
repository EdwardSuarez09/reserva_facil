<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_admin = $_SESSION['id'] ?? null;
if (!$id_admin) {
    echo "Sesión no válida.";
    exit;
}

// Obtener restaurante asociado
$sql_rest = "SELECT id FROM restaurantes WHERE id_admin = ?";
$stmt_rest = $conn->prepare($sql_rest);
$stmt_rest->bind_param("i", $id_admin);
$stmt_rest->execute();
$res_rest = $stmt_rest->get_result();

if ($res_rest->num_rows === 0) {
    echo "<p>No hay restaurante asociado al administrador.</p>";
    exit;
}

$id_restaurante = $res_rest->fetch_assoc()['id'];

// Determinar qué tabla usar para platos
$tabla_platos = 'platos';
$sql_check_table = "SHOW TABLES LIKE 'platosmenu'";
$result_check = $conn->query($sql_check_table);
if ($result_check && $result_check->num_rows > 0) {
    $tabla_platos = 'platosmenu';
}

// Obtener platos del restaurante
if ($tabla_platos === 'platosmenu') {
    $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen, c.nombre AS categoria
            FROM $tabla_platos p
            JOIN categorias c ON p.categoria_id = c.id
            WHERE p.id_restaurante = ?
            ORDER BY p.id DESC";
} else {
    $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen, c.nombre AS categoria
            FROM $tabla_platos p
            JOIN categorias c ON p.id_categoria = c.id
            WHERE p.id_restaurante = ?
            ORDER BY p.id DESC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_restaurante);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<div style='padding: 20px; max-width: 1100px; margin: 0 auto;'>
        <table style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; box-shadow: 0 0 15px rgba(0,0,0,0.1); border-radius: 12px; overflow: hidden;'>
            <thead>
                <tr>
                    <th style='background-color: #f39c12; color: white; padding: 12px;'>Nombre</th>
                    <th style='background-color: #f39c12; color: white; padding: 12px;'>Descripción</th>
                    <th style='background-color: #f39c12; color: white; padding: 12px;'>Precio</th>
                    <th style='background-color: #f39c12; color: white; padding: 12px;'>Categoría</th>
                    <th style='background-color: #f39c12; color: white; padding: 12px;'>Imagen</th>
                    <th style='background-color: #f39c12; color: white; padding: 12px;'>Acciones</th>
                </tr>
            </thead>
            <tbody>";

    while ($row = $result->fetch_assoc()) {
        $imagenHTML = $row['imagen']
            ? "<img src='data:image/jpeg;base64," . base64_encode($row['imagen']) . "' style='width: 60px; height: 60px; object-fit: cover; border-radius: 8px;'>"
            : "<span style='color: #999;'>Sin imagen</span>";

        echo "<tr style='text-align: center; background-color: #fff;'>
                <td style='padding: 12px;'>{$row['nombre']}</td>
                <td style='padding: 12px;'>{$row['descripcion']}</td>
                <td style='padding: 12px;'>$ " . number_format($row['precio'], 0, ',', '.') . "</td>
                <td style='padding: 12px;'>{$row['categoria']}</td>
                <td style='padding: 12px;'>$imagenHTML</td>
                <td style='padding: 12px; white-space: nowrap;'>
                    <button onclick=\"editarPlato({$row['id']})\" 
                            style='background-color: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 5px; margin-right: 6px; cursor: pointer;'>
                        Editar
                    </button>
                    <button onclick=\"eliminarPlato({$row['id']})\" 
                            style='background-color: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer;'>
                        Eliminar
                    </button>
                </td>
              </tr>";
    }

    echo "</tbody></table></div>";
} else {
    echo "<p style='padding: 20px; text-align:center;'>No hay platos registrados.</p>";
}

$conn->close();
?>
