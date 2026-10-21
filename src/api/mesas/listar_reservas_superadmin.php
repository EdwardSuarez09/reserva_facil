<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);
require __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de mesa no válido.']);
    exit;
}

$stmt = $conn->prepare(
    'SELECT nombre_cliente, fecha_reserva, hora_reserva, numero_personas, estado
     FROM reservas
     WHERE mesa_id = ?
     ORDER BY fecha_reserva DESC, hora_reserva DESC
     LIMIT 100'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo preparar la consulta de reservas.']);
    exit;
}
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron consultar las reservas de la mesa.']);
    exit;
}
$reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode($reservations, JSON_UNESCAPED_UNICODE);
