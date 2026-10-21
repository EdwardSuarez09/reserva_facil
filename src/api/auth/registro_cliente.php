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
$email = trim($_POST['email'] ?? $_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$password = $_POST['password'] ?? $_POST['contrasena'] ?? '';
$confirmarPassword = $_POST['confirmar_contrasena'] ?? '';

if ($nombre === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Nombre, email y contraseña son requeridos']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Email no válido']);
    exit;
}

$nombreCompleto = trim($nombre . ' ' . $apellido);
if (strlen($nombreCompleto) > 100 || strlen($email) > 100 || strlen($telefono) > 20) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Uno o más campos superan el límite permitido']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}
if ($confirmarPassword !== '' && $password !== $confirmarPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Las contraseñas no coinciden']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id FROM admin WHERE correo = ?');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() !== false) {
        http_response_code(409);
        echo json_encode(['success' => false, 'mensaje' => 'El email ya está registrado']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM usuarios_cliente WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() !== false) {
        http_response_code(409);
        echo json_encode(['success' => false, 'mensaje' => 'El email ya está registrado']);
        exit;
    }
    
    // Encriptar contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('No se pudo proteger la contraseña.');
    }
    
    // Insertar nuevo cliente en usuarios_cliente
    $stmt = $pdo->prepare("INSERT INTO usuarios_cliente (nombre, email, telefono, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombreCompleto, $email, $telefono, $passwordHash]);
    
    $clienteId = $pdo->lastInsertId();
    
    // Iniciar sesión automáticamente
    session_regenerate_id(true);
    unset($_SESSION['id'], $_SESSION['rol']);
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
    
} catch (PDOException | RuntimeException $e) {
    if ($e->getCode() == '23000') {
        http_response_code(409);
        echo json_encode(['success' => false, 'mensaje' => 'El email ya está registrado']);
    } else {
        error_log('Error registrando cliente: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'mensaje' => 'Error de conexión a la base de datos']);
    }
}
?> 