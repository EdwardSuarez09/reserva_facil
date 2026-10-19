<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_admin = $_SESSION['id'] ?? null;
if (!$id_admin) {
    echo "<p style='color:red;'>Sesión no válida. Por favor inicia sesión.</p>";
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

$row_rest = $res_rest->fetch_assoc();
$id_restaurante = $row_rest['id'];

// Consultar categorías
$sql = "SELECT id, nombre FROM categorias WHERE id_restaurante = ? ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_restaurante);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<div style='padding: 20px; max-width: 700px; margin: 0 auto;'>
        <table style='border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; box-shadow: 0 0 15px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden;'>
            <thead>
                <tr>
                    <th style='background-color: #f39c12; color: white; padding: 12px;'>ID</th>
                    <th style='background-color: #f39c12; color: white; padding: 12px;'>Nombre</th>
                    <th style='background-color: #f39c12; color: white; padding: 12px;'>Acciones</th>
                </tr>
            </thead>
            <tbody>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr style='text-align: center; background-color: #fff;'>
                <td style='padding: 12px;'>{$row['id']}</td>
                <td style='padding: 12px;'>{$row['nombre']}</td>
                <td style='padding: 12px; white-space: nowrap;'>
                    <button onclick=\"editarCategoria({$row['id']})\" style='background-color: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 5px; margin-right: 4px;'>Editar</button>
                    <button onclick=\"eliminarCategoria({$row['id']})\" style='background-color: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 5px;'>Eliminar</button>
                </td>
              </tr>";
    }

    echo "</tbody>
        </table>
      </div>";
} else {
    echo "<p style='padding: 20px;'>No hay categorías registradas.</p>";
}

$conn->close();
?>
