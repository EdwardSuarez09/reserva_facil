<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

$id = $_POST['id'] ?? null;

if (!$id) {
    echo "ID inválido.";
    exit;
}

$sql = "DELETE FROM platosmenu WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "Plato eliminado correctamente.";
} else {
    echo "Error al eliminar el plato.";
}

$stmt->close();
$conn->close();
?>
