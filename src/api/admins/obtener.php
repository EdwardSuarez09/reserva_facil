<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(["error" => "ID requerido"]);
    exit;
}

$sql = "SELECT id, nombre, correo, rol FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode($result->fetch_assoc());
} else {
    echo json_encode(["error" => "Administrador no encontrado"]);
}

$stmt->close();
$conn->close();
?>