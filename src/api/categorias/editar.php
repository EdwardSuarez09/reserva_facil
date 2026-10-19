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

$id = $_POST['id'] ?? null;
$nombre = trim($_POST['nombre'] ?? '');

if (!$id || empty($nombre)) {
    echo "Todos los campos son obligatorios.";
    exit;
}

$stmt = $conn->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
$stmt->bind_param("si", $nombre, $id);

if ($stmt->execute()) {
    echo "Categoría actualizada correctamente.";
} else {
    echo "Error al actualizar categoría.";
}

$stmt->close();
$conn->close();
?>
