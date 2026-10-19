<?php
include __DIR__ . '/../../config/conexion.php';

$nombreRestaurante = $_GET['restaurante'] ?? '';
if (!$nombreRestaurante) {
    http_response_code(400);
    echo json_encode(["error" => "Restaurante no especificado"]);
    exit;
}

$sql = "SELECT nombre, direccion, telefono, correo, web, redes_sociales, horario, descripcion FROM restaurantes WHERE nombre = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nombreRestaurante);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    
    echo json_encode([
        "nombre" => $row['nombre'],
        "direccion" => $row['direccion'],
        "telefono" => $row['telefono'],
        "correo" => $row['correo'],
        "web" => $row['web'],
        "redes" => $row['redes_sociales'],
        "horario" => $row['horario'],
        "descripcion" => $row['descripcion']
    ]);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Restaurante no encontrado"]);
}

$stmt->close();
$conn->close();
