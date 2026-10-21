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
    'SELECT id, nombre, precio
     FROM platosmenu
     WHERE categoria_id = ?
     ORDER BY nombre'
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo preparar la consulta de platos.']);
    exit;
}
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron consultar los platos de la categoría.']);
    exit;
}
$dishes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode($dishes, JSON_UNESCAPED_UNICODE);
