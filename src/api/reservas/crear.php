<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

// Obtener datos del formulario
$cliente_id = $_POST['cliente_id'] ?? null;
$id_restaurante = $_POST['id_restaurante'] ?? null;
$id_mesa = $_POST['id_mesa'] ?? null;
$nombre_cliente = $_POST['nombre_cliente'] ?? '';
$telefono_cliente = $_POST['telefono_cliente'] ?? '';
$email_cliente = $_POST['email_cliente'] ?? '';
$fecha_reserva = $_POST['fecha_reserva'] ?? '';
$hora_reserva = $_POST['hora_reserva'] ?? '';
$numero_personas = $_POST['numero_personas'] ?? null;
$duracion = $_POST['duracion'] ?? 120;
$comentarios = $_POST['comentarios'] ?? '';
$estado = $_POST['estado'] ?? 'pendiente';

// Validar campos obligatorios
if (!$cliente_id || !$id_restaurante || !$nombre_cliente || !$telefono_cliente || !$email_cliente || 
    !$fecha_reserva || !$hora_reserva || !$numero_personas) {
    echo "Todos los campos obligatorios deben ser completados.";
    exit;
}

// Validar que la fecha sea futura
$fecha_actual = date('Y-m-d');
if ($fecha_reserva < $fecha_actual) {
    echo "La fecha de reserva debe ser igual o posterior a hoy.";
    exit;
}

// Validar duración
if ($duracion < 30 || $duracion > 480) {
    echo "La duración debe estar entre 30 y 480 minutos.";
    exit;
}

// Si se especifica una mesa, verificar que no esté ocupada en esa fecha y hora
if ($id_mesa) {
    $check_mesa = "SELECT id FROM reservas 
                   WHERE id_mesa = ? AND fecha_reserva = ? AND hora_reserva = ? 
                   AND estado IN ('pendiente', 'confirmada')";
    $stmt_check = $conn->prepare($check_mesa);
    $stmt_check->bind_param("iss", $id_mesa, $fecha_reserva, $hora_reserva);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        echo "La mesa seleccionada ya está reservada para esa fecha y hora.";
        exit;
    }
    $stmt_check->close();
}

// Si no se especifica mesa, el valor será NULL
$id_mesa = $id_mesa ?: NULL;

// Insertar reserva
$sql = "INSERT INTO reservas (cliente_id, id_restaurante, id_mesa, nombre_cliente, telefono_cliente, 
        email_cliente, fecha_reserva, hora_reserva, numero_personas, duracion, comentarios, estado) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiisssssiiss", 
    $cliente_id,
    $id_restaurante, 
    $id_mesa, 
    $nombre_cliente, 
    $telefono_cliente, 
    $email_cliente, 
    $fecha_reserva, 
    $hora_reserva, 
    $numero_personas, 
    $duracion,
    $comentarios, 
    $estado
);

if ($stmt->execute()) {
    echo "Reserva registrada correctamente.";
} else {
    echo "Error al registrar la reserva: " . $stmt->error;
}

$stmt->close();
$conn->close();
?> 