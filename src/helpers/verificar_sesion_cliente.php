<?php
session_start();
header('Content-Type: application/json');

// Verificar si hay sesión de cliente
if (isset($_SESSION['cliente_autenticado']) && $_SESSION['cliente_autenticado'] === true) {
    echo json_encode([
        'autenticado' => true,
        'cliente' => [
            'id' => $_SESSION['cliente_id'],
            'nombre' => $_SESSION['cliente_nombre'],
            'email' => $_SESSION['cliente_email']
        ]
    ]);
    exit;
}

// Verificar si hay sesión de admin (para usuarios que inician sesión desde el login principal)
if (isset($_SESSION['id']) && isset($_SESSION['rol'])) {
    // Si es admin, obtener información del admin desde la base de datos
    include_once __DIR__ . '/../config/conexion.php';
    
    try {
        $stmt = $pdo->prepare("SELECT nombre, correo FROM admin WHERE id = ?");
        $stmt->execute([$_SESSION['id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo json_encode([
                'autenticado' => true,
                'cliente' => [
                    'id' => $_SESSION['id'],
                    'nombre' => $admin['nombre'] ?: 'Usuario Admin',
                    'email' => $admin['correo']
                ]
            ]);
            exit;
        }
    } catch (PDOException $e) {
        // Error al consultar base de datos, continuar con autenticado = false
    }
}

// No hay ninguna sesión válida
echo json_encode(['autenticado' => false]);
?> 