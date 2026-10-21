<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/reservas_access.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de reserva requerido']);
    exit;
}

$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de reserva inválido']);
    exit;
}

try {
    if (!autorizarGestionReserva($conn, $id)) {
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