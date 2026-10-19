<?php
session_start();
include __DIR__ . '/../../config/conexion.php';
include_once __DIR__ . '/../../helpers/password.php';

$correo = $_POST['correo'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

if (empty($correo) || empty($contrasena)) {
    echo "Todos los campos son obligatorios.";
    exit;
}

try {
    $sql = "SELECT id, contraseña, rol FROM admin WHERE correo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin) {
        if (verificarYActualizarContrasena(
            $conn,
            'admin',
            'contraseña',
            (int) $admin['id'],
            $contrasena,
            (string) $admin['contraseña']
        )) {
            $_SESSION['id'] = $admin['id'];
            $_SESSION['rol'] = $admin['rol'];

            echo "ok|" . $admin['rol'];
        } else {
            echo "Contraseña incorrecta.";
        }
    } else {
        echo "Usuario no encontrado.";
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo "Error en el sistema. Por favor, intenta más tarde.";
}

$conn->close();
?>
