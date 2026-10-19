<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_admin = $_SESSION['id'] ?? null;
if (!$id_admin) {
    echo json_encode([]);
    exit;
}

// Obtener restaurante asociado al admin
$sql_rest = "SELECT id FROM restaurantes WHERE id_admin = ?";
$stmt_rest = $conn->prepare($sql_rest);
$stmt_rest->bind_param("i", $id_admin);
$stmt_rest->execute();
$res_rest = $stmt_rest->get_result();

if ($res_rest->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$row_rest = $res_rest->fetch_assoc();
$id_restaurante = $row_rest['id'];

// Consultar categorías para ese restaurante
$sql = "SELECT id, nombre FROM categorias WHERE id_restaurante = ? ORDER BY nombre ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_restaurante);
$stmt->execute();
$result = $stmt->get_result();

$categorias = [];
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}

echo json_encode($categorias);

$stmt->close();
$conn->close();
?>
