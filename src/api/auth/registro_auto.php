<?php
include __DIR__ . '/../../config/conexion.php';

// Obtener datos del formulario
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['correo'] ?? '';
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
    // Detectar el nombre de la columna de contraseña
    $columns_result = $conn->query("DESCRIBE admin");
    $password_column = null;
    
    while ($row = $columns_result->fetch_assoc()) {
        $column_name = strtolower($row['Field']);
        if (in_array($column_name, ['password', 'contrasena', 'contrasenha', 'contraseña', 'clave', 'pass', 'pwd'])) {
            $password_column = $row['Field'];
            break;
        }
    }
    
    // Si no encontró ninguna, usar la que más se parezca
    if (!$password_column) {
        $columns_result = $conn->query("DESCRIBE admin");
        while ($row = $columns_result->fetch_assoc()) {
            if (strpos(strtolower($row['Field']), 'contra') !== false || 
                strpos(strtolower($row['Field']), 'pass') !== false ||
                strpos(strtolower($row['Field']), 'senha') !== false) {
                $password_column = $row['Field'];
                break;
            }
        }
    }
    
    if (!$password_column) {
        echo "Error: No se encontró columna de contraseña en la tabla admin";
        exit;
    }
    
    // Verificar si el correo ya existe
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
    
    // Insertar nuevo usuario usando el nombre correcto de columna
    $sql = "INSERT INTO admin (nombre, correo, $password_column, rol) 
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