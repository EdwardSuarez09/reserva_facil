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


$id = $_POST['id'] ?? null;

if (!$id) {
    echo "ID inválido.";
    exit;
}

$stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "Categoría eliminada correctamente.";
} else {
    echo "Error al eliminar.";
}

$stmt->close();
$conn->close();
?>
