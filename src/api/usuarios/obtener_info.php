<?php
session_start();
include __DIR__ . '/../../config/conexion.php';

// Verificar que hay sesión activa
if (
    ($_SESSION['cliente_autenticado'] ?? false) !== true
    || !isset($_SESSION['cliente_id'])
    || isset($_SESSION['rol'])
) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_usuario = (int) $_SESSION['cliente_id'];

try {
    $sql = "SELECT nombre, email AS correo FROM usuarios_cliente WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        echo json_encode($usuario);
    } else {
        echo json_encode(['error' => 'Usuario no encontrado']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error del servidor']);
}

$conn->close();
?> 