<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../config/conexion.php';

$restaurante = $_GET['restaurante'] ?? '';
$hora = $_GET['hora'] ?? '';

if (empty($restaurante) || empty($hora)) {
    echo json_encode([
        'valido' => false, 
        'mensaje' => 'Faltan parámetros requeridos'
    ]);
    exit;
}

try {
    // Obtener horarios del restaurante
    $stmt = $conn->prepare("SELECT horario_apertura, horario_cierre, nombre FROM restaurantes WHERE nombre = ?");
    $stmt->bind_param("s", $restaurante);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode([
            'valido' => false,
            'mensaje' => 'Restaurante no encontrado'
        ]);
        exit;
    }
    
    $restaurante_data = $result->fetch_assoc();
    $horario_apertura = $restaurante_data['horario_apertura'];
    $horario_cierre = $restaurante_data['horario_cierre'];
    
    // Convertir hora solicitada a formato comparable
    $hora_solicitada = date('H:i:s', strtotime($hora));
    
    // Verificar si la hora está dentro del rango
    $valido = ($hora_solicitada >= $horario_apertura && $hora_solicitada <= $horario_cierre);
    
    if ($valido) {
        echo json_encode([
            'valido' => true,
            'mensaje' => 'Horario válido'
        ]);
    } else {
        // Convertir a formato de 12 horas para el mensaje
        $apertura_12h = date('g:i A', strtotime($horario_apertura));
        $cierre_12h = date('g:i A', strtotime($horario_cierre));
        
        echo json_encode([
            'valido' => false,
            'mensaje' => "Lo sentimos, {$restaurante_data['nombre']} solo atiende de {$apertura_12h} a {$cierre_12h}. Por favor selecciona un horario dentro de nuestras horas de servicio.",
            'horario_apertura' => $apertura_12h,
            'horario_cierre' => $cierre_12h
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'valido' => false,
        'mensaje' => 'Error verificando horarios: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 