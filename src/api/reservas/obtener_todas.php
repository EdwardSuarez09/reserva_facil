<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

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
                       COALESCE(r.email_cliente, '') as cliente_email,
                       COALESCE(r.estado, 'pendiente') as estado
                FROM reservas r 
                LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id 
                LEFT JOIN mesas m ON r.mesa_id = m.id
                ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";
        $stmt = $conn->prepare($sql);
    } else {
        // Admin solo puede ver reservas de sus restaurantes
        $sql = "SELECT r.*, 
                       COALESCE(rest.nombre, 'Mi restaurante') as restaurante_nombre,
                       COALESCE(m.numero_mesa, 'Sin asignar') as numero_mesa, 
                       COALESCE(m.capacidad, 0) as capacidad,
                       COALESCE(r.nombre_cliente, 'Cliente sin nombre') as cliente_nombre,
                       COALESCE(r.telefono_cliente, '') as cliente_telefono,
                       COALESCE(r.email_cliente, '') as cliente_email,
                       COALESCE(r.estado, 'pendiente') as estado
                FROM reservas r 
                LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id 
                LEFT JOIN mesas m ON r.mesa_id = m.id
                WHERE (rest.id_admin = ? OR rest.id_admin IS NULL)
                ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    $reservas = [];
    while ($fila = $resultado->fetch_assoc()) {
        // Asegurar que el estado no esté vacío
        if (empty($fila['estado'])) {
            $fila['estado'] = 'pendiente';
        }
        $reservas[] = $fila;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'reservas' => $reservas,
        'total' => count($reservas)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
?> 