<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/reservas_access.php';

header('Content-Type: application/json');

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de reserva requerido']);
    exit;
}

if (!autorizarGestionReserva($conn, $id)) {
    exit;
}

try {
    $sql = "SELECT r.*, 
                   COALESCE(r.nombre_cliente, '') as cliente_nombre,
                   COALESCE(r.telefono_cliente, '') as cliente_telefono,
                   COALESCE(r.email_cliente, '') as cliente_email,
                   COALESCE(r.estado, 'pendiente') as estado
            FROM reservas r 
            WHERE r.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new RuntimeException('No se pudo consultar la reserva.');
    }
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $reserva = $resultado->fetch_assoc();
        
        // Asegurar que el estado no esté vacío
        if (empty($reserva['estado'])) {
            $reserva['estado'] = 'pendiente';
        }
        
        echo json_encode($reserva);
    } else {
        echo json_encode(['error' => 'Reserva no encontrada']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

$conn->close();
?> 