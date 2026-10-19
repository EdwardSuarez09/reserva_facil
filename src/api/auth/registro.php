<?php
include __DIR__ . '/../../config/conexion.php';

// Obtener datos del formulario
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['correo'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';
$confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

// Validaciones básicas
if (empty($nombre) || empty($correo) || empty($contrasena)) {
    echo "Los campos nombre, correo y contraseña son obligatorios";
    exit;
}

if ($contrasena !== $confirmar_contrasena) {
    echo "Las contraseñas no coinciden";
    exit;
}

if (strlen($contrasena) < 6) {
    echo "La contraseña debe tener al menos 6 caracteres";
    exit;
}

// Validar email
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo "El correo electrónico no es válido";
    exit;
}

try {
    // Verificar si el correo ya existe en la tabla admin
    $check_sql = "SELECT id FROM admin WHERE correo = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $correo);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo "Este correo electrónico ya está registrado";
        exit;
    }
    
    // Hashear la contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
    
    // Insertar nuevo usuario con rol 'usuario' en la tabla admin
    $sql = "INSERT INTO admin (nombre, correo, contraseña, rol) 
            VALUES (?, ?, ?, 'usuario')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nombre, $correo, $contrasena_hash);
    
    if ($stmt->execute()) {
        echo "✅ Usuario registrado exitosamente. Redirigiendo al login...";
    } else {
        echo "Error al registrar el usuario: " . $stmt->error;
    }
    
    $stmt->close();
    $check_stmt->close();
    
} catch (Exception $e) {
    echo "Error del servidor: " . $e->getMessage();
}

$conn->close();
?> 