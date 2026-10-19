<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

include_once __DIR__ . '/../../config/conexion.php';

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($nombre) || empty($apellido) || empty($email) || empty($telefono) || empty($password)) {
    echo json_encode(['success' => false, 'mensaje' => 'Todos los campos son requeridos']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'mensaje' => 'Email no válido']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'mensaje' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}

try {
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios_cliente WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'mensaje' => 'El email ya está registrado']);
        exit;
    }
    
    // Encriptar contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Combinar nombre completo
    $nombreCompleto = $nombre . ' ' . $apellido;
    
    // Insertar nuevo cliente en usuarios_cliente
    $stmt = $pdo->prepare("INSERT INTO usuarios_cliente (nombre, email, telefono, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombreCompleto, $email, $telefono, $passwordHash]);
    
    $clienteId = $pdo->lastInsertId();
    
    // Iniciar sesión automáticamente
    $_SESSION['cliente_id'] = $clienteId;
    $_SESSION['cliente_nombre'] = $nombreCompleto;
    $_SESSION['cliente_email'] = $email;
    $_SESSION['cliente_autenticado'] = true;
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Registro exitoso',
        'cliente' => [
            'id' => $clienteId,
            'nombre' => $nombreCompleto,
            'email' => $email,
            'telefono' => $telefono
        ]
    ]);
    
} catch (PDOException $e) {
    if ($e->getCode() == '23000') {
        echo json_encode(['success' => false, 'mensaje' => 'El email ya está registrado']);
    } else {
        echo json_encode(['success' => false, 'mensaje' => 'Error de conexión a la base de datos']);
    }
}
?> 