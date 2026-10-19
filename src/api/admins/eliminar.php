<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

$id = $_POST['id'] ?? null;
if (!$id) {
    echo "ID requerido";
    exit;
}

$sql = "DELETE FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "Administrador eliminado correctamente.";
} else {
    echo "Error al eliminar administrador.";
}

$stmt->close();
$conn->close();
?>
