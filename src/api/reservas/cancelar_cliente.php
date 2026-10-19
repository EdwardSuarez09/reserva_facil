<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cliente_autenticado']) || $_SESSION['cliente_autenticado'] !== true) {
    echo json_encode(['success' => false, 'mensaje' => 'Usuario no autenticado']);
    exit;
}

include_once __DIR__ . '/../../config/conexion.php';

$input = json_decode(file_get_contents('php://input'), true);
$reservaId = $input['reserva_id'] ?? null;

if (!$reservaId) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de reserva requerido']);
    exit;
}

try {
    $clienteId = $_SESSION['cliente_id'];
    
    // Verificar que la reserva pertenece al cliente y está en estado pendiente
    $stmt = $pdo->prepare("
        SELECT id, estado, fecha_reserva, hora_reserva 
        FROM reservas 
        WHERE id = ? AND cliente_id = ?
    ");
    $stmt->execute([$reservaId, $clienteId]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        echo json_encode(['success' => false, 'mensaje' => 'Reserva no encontrada']);
        exit;
    }
    
    if (strtolower($reserva['estado']) !== 'pendiente') {
        echo json_encode(['success' => false, 'mensaje' => 'Solo se pueden cancelar reservas pendientes']);
        exit;
    }
    
    // Verificar que la reserva no sea del pasado
    $fechaHoraReserva = $reserva['fecha_reserva'] . ' ' . $reserva['hora_reserva'];
    if (strtotime($fechaHoraReserva) < time()) {
        echo json_encode(['success' => false, 'mensaje' => 'No se pueden cancelar reservas pasadas']);
        exit;
    }
    
    // Actualizar estado a cancelada
    $stmt = $pdo->prepare("UPDATE reservas SET estado = 'Cancelada' WHERE id = ?");
    $stmt->execute([$reservaId]);
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Reserva cancelada correctamente'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al cancelar la reserva'
    ]);
}
?> 