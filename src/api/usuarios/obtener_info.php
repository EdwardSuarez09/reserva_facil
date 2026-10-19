<?php
session_start();
include __DIR__ . '/../../config/conexion.php';

// Verificar que hay sesión activa
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'usuario') {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_usuario = $_SESSION['id'];

try {
    $sql = "SELECT nombre, correo FROM admin WHERE id = ?";
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