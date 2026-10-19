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
    $estadisticas = [
        'total_reservas' => 0,
        'reservas_pendientes' => 0,
        'reservas_confirmadas' => 0,
        'reservas_hoy' => 0
    ];

    if ($rol_usuario === 'superadmin') {
        // Super admin ve todas las reservas
        
        // Total reservas
        $sql_total = "SELECT COUNT(*) as total FROM reservas";
        $result_total = $conn->query($sql_total);
        $estadisticas['total_reservas'] = $result_total->fetch_assoc()['total'];

        // Reservas pendientes (estados en español)
        $sql_pendientes = "SELECT COUNT(*) as total FROM reservas WHERE estado = 'Pendiente'";
        $result_pendientes = $conn->query($sql_pendientes);
        $estadisticas['reservas_pendientes'] = $result_pendientes->fetch_assoc()['total'];

        // Reservas confirmadas (estados en español)
        $sql_confirmadas = "SELECT COUNT(*) as total FROM reservas WHERE estado = 'Confirmada'";
        $result_confirmadas = $conn->query($sql_confirmadas);
        $estadisticas['reservas_confirmadas'] = $result_confirmadas->fetch_assoc()['total'];

        // Reservas para hoy
        $sql_hoy = "SELECT COUNT(*) as total FROM reservas WHERE DATE(fecha_reserva) = CURDATE()";
        $result_hoy = $conn->query($sql_hoy);
        $estadisticas['reservas_hoy'] = $result_hoy->fetch_assoc()['total'];

    } else {
        // Admin normal solo ve reservas de sus restaurantes
        // Simplificar consultas para evitar errores de columnas inexistentes
        
        // Total reservas del admin - usar solo la tabla reservas con id_restaurante
        $sql_total = "SELECT COUNT(*) as total 
                      FROM reservas r 
                      LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id 
                      WHERE rest.id_admin = ? OR r.id_restaurante IS NULL";
        $stmt_total = $conn->prepare($sql_total);
        $stmt_total->bind_param("i", $id_usuario);
        $stmt_total->execute();
        $result_total = $stmt_total->get_result();
        $estadisticas['total_reservas'] = $result_total->fetch_assoc()['total'];

        // Reservas pendientes del admin
        $sql_pendientes = "SELECT COUNT(*) as total 
                           FROM reservas r 
                           LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id 
                           WHERE (rest.id_admin = ? OR r.id_restaurante IS NULL) 
                           AND r.estado = 'Pendiente'";
        $stmt_pendientes = $conn->prepare($sql_pendientes);
        $stmt_pendientes->bind_param("i", $id_usuario);
        $stmt_pendientes->execute();
        $result_pendientes = $stmt_pendientes->get_result();
        $estadisticas['reservas_pendientes'] = $result_pendientes->fetch_assoc()['total'];

        // Reservas confirmadas del admin
        $sql_confirmadas = "SELECT COUNT(*) as total 
                            FROM reservas r 
                            LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id 
                            WHERE (rest.id_admin = ? OR r.id_restaurante IS NULL) 
                            AND r.estado = 'Confirmada'";
        $stmt_confirmadas = $conn->prepare($sql_confirmadas);
        $stmt_confirmadas->bind_param("i", $id_usuario);
        $stmt_confirmadas->execute();
        $result_confirmadas = $stmt_confirmadas->get_result();
        $estadisticas['reservas_confirmadas'] = $result_confirmadas->fetch_assoc()['total'];

        // Reservas para hoy del admin
        $sql_hoy = "SELECT COUNT(*) as total 
                    FROM reservas r 
                    LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id 
                    WHERE (rest.id_admin = ? OR r.id_restaurante IS NULL) 
                    AND DATE(r.fecha_reserva) = CURDATE()";
        $stmt_hoy = $conn->prepare($sql_hoy);
        $stmt_hoy->bind_param("i", $id_usuario);
        $stmt_hoy->execute();
        $result_hoy = $stmt_hoy->get_result();
        $estadisticas['reservas_hoy'] = $result_hoy->fetch_assoc()['total'];
    }

    header('Content-Type: application/json');
    echo json_encode($estadisticas);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

$conn->close();
?> 