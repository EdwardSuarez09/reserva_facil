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
    echo json_encode(['error' => 'ID de plato no válido.']);
    exit;
}

$stmt = $conn->prepare(
    'SELECT p.id, p.nombre, p.descripcion, p.precio,
            c.nombre AS categoria, r.nombre AS restaurante
     FROM platosmenu p
     LEFT JOIN categorias c ON c.id = p.categoria_id
     LEFT JOIN restaurantes r ON r.id = p.id_restaurante
     WHERE p.id = ?'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo preparar la consulta del plato.']);
    exit;
}
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron consultar los detalles del plato.']);
    exit;
}
$dish = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$dish) {
    http_response_code(404);
    echo json_encode(['error' => 'Plato no encontrado.']);
    exit;
}

echo json_encode($dish, JSON_UNESCAPED_UNICODE);
