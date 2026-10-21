<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cliente_autenticado']) || $_SESSION['cliente_autenticado'] !== true || isset($_SESSION['rol'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Usuario no autenticado']);
    exit;
}

include_once __DIR__ . '/../../config/conexion.php';

try {
    // Verificar si existe la tabla reservas
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'reservas'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => true, 'reservas' => []]);
        exit;
    }
    
    $clienteId = $_SESSION['cliente_id'];
    
    // Obtener reservas del cliente con información del restaurante
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.fecha_reserva,
            r.hora_reserva,
            r.numero_personas,
            r.estado,
            r.comentarios,
            r.fecha_creacion,
            r.mesa_id,
            rest.nombre as restaurante_nombre,
            rest.direccion as restaurante_direccion,
            m.numero_mesa as mesa_numero
        FROM reservas r
        LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id
        LEFT JOIN mesas m ON r.mesa_id = m.id
        WHERE r.cliente_id = ?
        ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC
    ");
    $stmt->execute([$clienteId]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reservas' => $reservas
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener las reservas',
        'reservas' => []
    ]);
}
?> 