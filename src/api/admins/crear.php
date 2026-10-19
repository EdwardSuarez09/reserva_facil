<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['correo'] ?? '';
$contraseña = $_POST['contraseña'] ?? '';
$rol = $_POST['rol'] ?? '';

if (!$nombre || !$correo || !$contraseña || !$rol) {
    echo "Todos los campos son obligatorios.";
    exit;
}

$hash = password_hash($contraseña, PASSWORD_DEFAULT);
$sql = "INSERT INTO admin (nombre, correo, contraseña, rol) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $nombre, $correo, $hash, $rol);

if ($stmt->execute()) {
    echo "Administrador registrado correctamente.";
} else {
    echo "Error al registrar administrador.";
}

$stmt->close();
$conn->close();
?>