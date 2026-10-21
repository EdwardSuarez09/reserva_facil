<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

$nombre = trim($_POST['nombre'] ?? '');
$correo = $_POST['correo'] ?? '';
$contraseña = $_POST['contraseña'] ?? '';
$rol = $_POST['rol'] ?? '';

$limiteNombre = $pdo->prepare(
    'SELECT CHARACTER_MAXIMUM_LENGTH
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
);
$limiteNombre->execute(['admin', 'nombre']);
$maximoNombre = (int) $limiteNombre->fetchColumn();
$longitudNombre = function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre);

if (
    $nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)
    || strlen($contraseña) < 12
    || ($maximoNombre > 0 && $longitudNombre > $maximoNombre)
    || !in_array($rol, ['admin', 'superadmin'], true)
) {
    http_response_code(400);
    echo "Completa los datos del administrador con valores válidos. La contraseña debe tener al menos 12 caracteres.";
    exit;
}

$check = $conn->prepare(
    'SELECT correo AS email FROM admin WHERE correo = ?
     UNION ALL
     SELECT email FROM usuarios_cliente WHERE email = ?
     LIMIT 1'
);
if (!$check) {
    http_response_code(500);
    exit('No se pudo validar el correo.');
}
$check->bind_param('ss', $correo, $correo);
if (!$check->execute()) {
    $check->close();
    http_response_code(500);
    exit('No se pudo validar el correo.');
}
if ($check->get_result()->num_rows > 0) {
    http_response_code(409);
    echo 'El correo ya está registrado.';
    $check->close();
    $conn->close();
    exit;
}
$check->close();

$hash = password_hash($contraseña, PASSWORD_DEFAULT);
if ($hash === false) {
    http_response_code(500);
    exit('No se pudo proteger la contraseña.');
}
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