<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

$id = $_POST['id'] ?? null;
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['correo'] ?? '';
$contraseña = $_POST['contraseña'] ?? '';
$rol = $_POST['rol'] ?? '';

if (!$id || !$nombre || !$correo || !$rol) {
    echo "ID, nombre, correo y rol son obligatorios.";
    exit;
}

if (!empty($contraseña)) {
    $hash = password_hash($contraseña, PASSWORD_DEFAULT);
    $sql = "UPDATE admin SET nombre = ?, correo = ?, contraseña = ?, rol = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $correo, $hash, $rol, $id);
} else {
    $sql = "UPDATE admin SET nombre = ?, correo = ?, rol = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nombre, $correo, $rol, $id);
}

if ($stmt->execute()) {
    echo "Administrador actualizado correctamente.";
} else {
    echo "Error al actualizar administrador.";
}

$stmt->close();
$conn->close();
?>
