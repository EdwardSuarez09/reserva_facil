<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

include_once __DIR__ . '/../../config/conexion.php';
include_once __DIR__ . '/../../helpers/password.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'mensaje' => 'Email y contraseña son requeridos']);
    exit;
}

try {
    // Primero buscar en la tabla usuarios_cliente
    $stmt = $pdo->prepare("SELECT * FROM usuarios_cliente WHERE email = ?");
    $stmt->execute([$email]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cliente && verificarYActualizarContrasena(
        $conn,
        'usuarios_cliente',
        'password',
        (int) $cliente['id'],
        $password,
        (string) $cliente['password']
    )) {
        // Login exitoso como cliente
        $_SESSION['cliente_id'] = $cliente['id'];
        $_SESSION['cliente_nombre'] = $cliente['nombre'];
        $_SESSION['cliente_email'] = $cliente['email'];
        $_SESSION['cliente_autenticado'] = true;
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Login exitoso',
            'cliente' => [
                'id' => $cliente['id'],
                'nombre' => $cliente['nombre'],
                'email' => $cliente['email'],
                'telefono' => $cliente['telefono']
            ]
        ]);
        exit;
    }
    
    // Si no se encontró en usuarios_cliente, buscar en la tabla admin
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE correo = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && verificarYActualizarContrasena(
        $conn,
        'admin',
        'contraseña',
        (int) $admin['id'],
        $password,
        (string) $admin['contraseña']
    )) {
        // Login exitoso como admin - convertir a formato de cliente para unificar sesión
        $_SESSION['cliente_id'] = $admin['id'];
        $_SESSION['cliente_nombre'] = $admin['nombre'] ?: 'Usuario Admin';
        $_SESSION['cliente_email'] = $admin['correo'];
        $_SESSION['cliente_autenticado'] = true;
        
        // También mantener variables de admin para compatibilidad
        $_SESSION['id'] = $admin['id'];
        $_SESSION['rol'] = $admin['rol'];
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Login exitoso',
            'cliente' => [
                'id' => $admin['id'],
                'nombre' => $admin['nombre'] ?: 'Usuario Admin',
                'email' => $admin['correo'],
                'telefono' => ''
            ],
            'rol' => $admin['rol']
        ]);
        exit;
    }
    
    // No se encontró en ninguna tabla o credenciales incorrectas
    echo json_encode(['success' => false, 'mensaje' => 'Email o contraseña incorrectos']);
    
} catch (PDOException | RuntimeException | mysqli_sql_exception $e) {
    echo json_encode(['success' => false, 'mensaje' => 'Error de conexión a la base de datos']);
}
?> 