<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = $_POST['id'] ?? null;
$nombre = $_POST['nombre'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$precio = $_POST['precio'] ?? '';
$categoria_id = $_POST['id_categoria'] ?? '';
$imagen = null;

// Validar datos obligatorios
if (!$id || !$nombre || !$descripcion || !$precio || !$categoria_id) {
    echo "Todos los campos son obligatorios.";
    exit;
}

if (!is_numeric($precio)) {
    echo "El precio debe ser un número válido.";
    exit;
}

// Si se envió nueva imagen
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
    $imagen = file_get_contents($_FILES['imagen']['tmp_name']);

    $sql = "UPDATE platosmenu 
            SET nombre = ?, descripcion = ?, precio = ?, imagen = ?, categoria_id = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdsii", $nombre, $descripcion, $precio, $imagen, $categoria_id, $id);
} else {
    // Si no se envió imagen
    $sql = "UPDATE platosmenu 
            SET nombre = ?, descripcion = ?, precio = ?, categoria_id = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $categoria_id, $id);
}

if ($stmt->execute()) {
    echo "Plato actualizado correctamente.";
} else {
    echo "Error al actualizar plato: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
