<?php
// Limpiar completamente la salida
ob_start();
ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

// Desactivar errores de PHP en la salida
ini_set('display_errors', 0);

// Conexión
$host = '127.0.0.1';
$port = 3307;
$dbname = 'restaurantes_db';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    ob_end_clean();
    echo json_encode(['error' => 'Conexión fallida']);
    exit;
}

$conn->set_charset("utf8");

$restaurante = $_GET['restaurante'] ?? '';
$fecha = $_GET['fecha'] ?? '';
$hora = $_GET['hora'] ?? '';
$personas = (int)($_GET['personas'] ?? 1);

if (empty($restaurante) || empty($fecha) || empty($hora)) {
    ob_end_clean();
    echo json_encode(['error' => 'Faltan parámetros']);
    exit;
}

// Verificar restaurante
$stmt = $conn->prepare("SELECT id, nombre FROM restaurantes WHERE nombre = ?");
$stmt->bind_param("s", $restaurante);
$stmt->execute();
$result = $stmt->get_result();
$restaurante_data = $result->fetch_assoc();

if (!$restaurante_data) {
    ob_end_clean();
    echo json_encode(['error' => 'Restaurante no encontrado']);
    exit;
}

// Obtener mesas
$stmt = $conn->prepare("SELECT id, numero_mesa, capacidad, imagen FROM mesas WHERE capacidad >= ?");
$stmt->bind_param("i", $personas);
$stmt->execute();
$result = $stmt->get_result();

$mesas = [];
while ($row = $result->fetch_assoc()) {
    $mesas[] = $row;
}

$response = [
    'success' => true,
    'restaurante' => $restaurante_data,
    'mesas_disponibles' => $mesas,
    'fecha' => $fecha,
    'hora' => $hora,
    'personas' => $personas
];

ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
$conn->close();
exit;
?> 