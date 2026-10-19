<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

include __DIR__ . '/../../config/conexion.php';

$restaurante = $_GET['restaurante'] ?? '';

if (empty($restaurante)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Nombre del restaurante requerido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT nombre, horario, horario_apertura, horario_cierre FROM restaurantes WHERE nombre = ?");
    $stmt->bind_param("s", $restaurante);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $datos = $resultado->fetch_assoc();
        
        // Convertir horarios a formato 12h para mostrar
        $apertura_12h = date('g:i A', strtotime($datos['horario_apertura']));
        $cierre_12h = date('g:i A', strtotime($datos['horario_cierre']));
        
        echo json_encode([
            'success' => true,
            'restaurante' => $datos['nombre'],
            'horario_texto' => $datos['horario'],
            'horario_apertura' => $datos['horario_apertura'], // Formato 24h para validación
            'horario_cierre' => $datos['horario_cierre'],     // Formato 24h para validación
            'horario_apertura_12h' => $apertura_12h,          // Formato 12h para mostrar
            'horario_cierre_12h' => $cierre_12h,              // Formato 12h para mostrar
            'mensaje_horario' => "Horario de atención: {$apertura_12h} - {$cierre_12h}"
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'error' => 'Restaurante no encontrado',
            'restaurante_buscado' => $restaurante
        ], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?> 