<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de reserva requerido']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol_usuario = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id'] ?? null;

try {
    // Construir consulta según el rol
    if ($rol_usuario === 'superadmin') {
        $sql = "SELECT r.*, 
                       COALESCE(rest.nombre, 'Sin restaurante') as restaurante_nombre,
                       COALESCE(m.numero_mesa, 'Sin asignar') as numero_mesa, 
                       COALESCE(m.capacidad, 0) as capacidad,
                       COALESCE(r.nombre_cliente, 'Cliente sin nombre') as cliente_nombre,
                       COALESCE(r.telefono_cliente, '') as cliente_telefono,
                       COALESCE(r.email_cliente, '') as cliente_email
                FROM reservas r 
                LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id 
                LEFT JOIN mesas m ON r.mesa_id = m.id
                WHERE r.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
    } else {
        // Admin solo puede ver reservas de sus restaurantes
        $sql = "SELECT r.*, 
                       COALESCE(rest.nombre, 'Mi restaurante') as restaurante_nombre,
                       COALESCE(m.numero_mesa, 'Sin asignar') as numero_mesa, 
                       COALESCE(m.capacidad, 0) as capacidad,
                       COALESCE(r.nombre_cliente, 'Cliente sin nombre') as cliente_nombre,
                       COALESCE(r.telefono_cliente, '') as cliente_telefono,
                       COALESCE(r.email_cliente, '') as cliente_email
                FROM reservas r 
                LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id 
                LEFT JOIN mesas m ON r.mesa_id = m.id
                WHERE r.id = ? AND rest.id_admin = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $id_usuario);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $reserva = $resultado->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($reserva);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Reserva no encontrada o no tiene permisos para verla']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?> 