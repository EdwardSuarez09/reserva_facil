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
    echo json_encode(['error' => 'ID de categoría no válido.']);
    exit;
}

$stmt = $conn->prepare(
    'SELECT c.id, c.nombre, r.nombre AS restaurante, COUNT(p.id) AS total_platos
     FROM categorias c
     LEFT JOIN restaurantes r ON r.id = c.id_restaurante
     LEFT JOIN platosmenu p ON p.categoria_id = c.id
     WHERE c.id = ?
     GROUP BY c.id, c.nombre, r.nombre'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo preparar la consulta de categoría.']);
    exit;
}
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron consultar los detalles de la categoría.']);
    exit;
}
$category = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$category) {
    http_response_code(404);
    echo json_encode(['error' => 'Categoría no encontrada.']);
    exit;
}

echo json_encode($category, JSON_UNESCAPED_UNICODE);
