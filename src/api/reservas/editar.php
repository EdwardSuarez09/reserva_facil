<?php
include __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json');

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener todos los datos del formulario
$id = $_POST['id'] ?? null;
$cliente_nombre = $_POST['cliente_nombre'] ?? '';
$telefono_cliente = $_POST['telefono_cliente'] ?? '';
$email_cliente = $_POST['email_cliente'] ?? '';
$numero_personas = $_POST['numero_personas'] ?? 1;
$fecha_reserva = $_POST['fecha_reserva'] ?? '';
$hora_reserva = $_POST['hora_reserva'] ?? '';
$duracion = $_POST['duracion'] ?? 120;
$estado = $_POST['estado'] ?? 'pendiente';
$mesa_id = $_POST['mesa_id'] ?? null;
$comentarios = $_POST['comentarios'] ?? '';

// Debug para desarrollo (comentar en producción)
error_log("🔍 DEBUG - Datos recibidos: ID=$id, Estado='$estado', Cliente='$cliente_nombre'");

// Validar campos obligatorios
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID de reserva requerido']);
    exit;
}

if (empty($cliente_nombre)) {
    echo json_encode(['success' => false, 'error' => 'Nombre del cliente es requerido']);
    exit;
}

if (empty($fecha_reserva)) {
    echo json_encode(['success' => false, 'error' => 'Fecha de reserva es requerida']);
    exit;
}

if (empty($hora_reserva)) {
    echo json_encode(['success' => false, 'error' => 'Hora de reserva es requerida']);
    exit;
}

// Si mesa_id está vacío, convertir a NULL
$mesa_id = ($mesa_id === '' || $mesa_id === '0') ? null : intval($mesa_id);

try {
    // Verificar que la reserva existe
    $check_sql = "SELECT id FROM reservas WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Reserva no encontrada']);
        exit;
    }
    
    // Actualizar todos los campos de la reserva
    $sql = "UPDATE reservas SET 
            nombre_cliente = ?, 
            telefono_cliente = ?, 
            email_cliente = ?, 
            numero_personas = ?, 
            fecha_reserva = ?, 
            hora_reserva = ?, 
            duracion = ?,
            estado = ?, 
            mesa_id = ?,
            comentarios = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisssissi", 
        $cliente_nombre,
        $telefono_cliente, 
        $email_cliente, 
        $numero_personas, 
        $fecha_reserva, 
        $hora_reserva, 
        $duracion,
        $estado,
        $mesa_id,
        $comentarios,
        $id
    );
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Reserva actualizada correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'message' => 'No se detectaron cambios en los datos'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Error SQL: ' . $stmt->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 