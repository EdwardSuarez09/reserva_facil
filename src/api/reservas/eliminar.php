<?php
include __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de reserva requerido']);
    exit;
}

$id = intval($_POST['id']);

try {
    // Verificar que la reserva existe
    $check_sql = "SELECT id FROM reservas WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Reserva no encontrada']);
        exit;
    }
    
    // Eliminar la reserva
    $delete_sql = "DELETE FROM reservas WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Reserva eliminada exitosamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Error al eliminar la reserva: ' . $conn->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 