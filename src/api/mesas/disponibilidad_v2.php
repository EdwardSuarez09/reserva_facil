<?php
// Limpiar cualquier salida previa
if (ob_get_level()) {
    ob_end_clean();
}

// Headers JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

// Desactivar errores de PHP en la salida
ini_set('display_errors', 0);
error_reporting(0);

try {
    include __DIR__ . '/../../config/conexion.php';
    
    $restaurante = $_GET['restaurante'] ?? '';
    $fecha = $_GET['fecha'] ?? '';
    $hora = $_GET['hora'] ?? '';
    $personas = (int)($_GET['personas'] ?? 1);
    
    if (empty($restaurante) || empty($fecha) || empty($hora)) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan parámetros requeridos']);
        exit;
    }
    
    // Obtener información del restaurante
    $stmt_rest = $conn->prepare("SELECT id, nombre, horario FROM restaurantes WHERE nombre = ?");
    $stmt_rest->bind_param("s", $restaurante);
    $stmt_rest->execute();
    $resultado_rest = $stmt_rest->get_result();
    $restaurante_data = $resultado_rest->fetch_assoc();
    
    if (!$restaurante_data) {
        http_response_code(404);
        echo json_encode(['error' => 'Restaurante no encontrado']);
        exit;
    }
    
    $restaurante_id = $restaurante_data['id'];
    
    // Verificar fechas
    $fecha_actual = date('Y-m-d');
    if ($fecha < $fecha_actual) {
        http_response_code(400);
        echo json_encode(['error' => 'No se pueden hacer reservas en fechas pasadas']);
        exit;
    }
    
    if ($fecha == $fecha_actual) {
        $hora_actual = date('H:i');
        if ($hora <= $hora_actual) {
            http_response_code(400);
            echo json_encode(['error' => 'No se pueden hacer reservas en horas pasadas']);
            exit;
        }
    }
    
    // Obtener mesas disponibles
    $stmt_mesas = $conn->prepare("SELECT id, numero_mesa, capacidad, imagen FROM mesas WHERE capacidad >= ? ORDER BY numero_mesa");
    $stmt_mesas->bind_param("i", $personas);
    $stmt_mesas->execute();
    $resultado_mesas = $stmt_mesas->get_result();
    $mesas_totales = [];
    while ($row = $resultado_mesas->fetch_assoc()) {
        $mesas_totales[] = $row;
    }
    
    // Obtener mesas ocupadas
    $hora_inicio = date('H:i', strtotime($hora . ' -2 hours'));
    $hora_fin = date('H:i', strtotime($hora . ' +2 hours'));
    
    $stmt_ocupadas = $conn->prepare("
        SELECT DISTINCT mesa_id 
        FROM reservas 
        WHERE id_restaurante = ? 
        AND fecha_reserva = ? 
        AND hora_reserva BETWEEN ? AND ?
        AND estado IN ('pendiente', 'confirmada')
        AND mesa_id IS NOT NULL
    ");
    $stmt_ocupadas->bind_param("isss", $restaurante_id, $fecha, $hora_inicio, $hora_fin);
    $stmt_ocupadas->execute();
    $resultado_ocupadas = $stmt_ocupadas->get_result();
    
    $mesas_ocupadas = [];
    while ($row = $resultado_ocupadas->fetch_assoc()) {
        $mesas_ocupadas[] = (int)$row['mesa_id'];
    }
    
    // Filtrar mesas disponibles
    $mesas_disponibles = [];
    foreach ($mesas_totales as $mesa) {
        if (!in_array((int)$mesa['id'], $mesas_ocupadas)) {
            $mesas_disponibles[] = $mesa;
        }
    }
    
    $response = [
        'success' => true,
        'restaurante' => $restaurante_data,
        'mesas_disponibles' => $mesas_disponibles,
        'fecha' => $fecha,
        'hora' => $hora,
        'personas' => $personas,
        'debug' => [
            'total_mesas_encontradas' => count($mesas_totales),
            'mesas_ocupadas' => $mesas_ocupadas,
            'mesas_totales' => $mesas_totales,
            'hora_inicio_busqueda' => $hora_inicio,
            'hora_fin_busqueda' => $hora_fin,
            'restaurante_id' => $restaurante_id
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
}

if (isset($conn)) {
    $conn->close();
}

// Asegurar que no hay salida adicional
exit;
?> 