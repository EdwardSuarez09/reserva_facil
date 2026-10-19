<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializar contadores
$estadisticas = [
    'total_restaurantes' => 0,
    'total_admins' => 0,
    'total_reservas' => 0,
    'total_mesas' => 0,
    'reservas_hoy' => 0,
    'reservas_semana' => 0,
    'restaurantes_activos' => 0,
    'admins_registrados' => 0
];

try {
    // Contar total de restaurantes
    $sql = "SELECT COUNT(*) as total FROM restaurantes";
    $result = $conn->query($sql);
    if ($result) {
        $estadisticas['total_restaurantes'] = $result->fetch_assoc()['total'];
    }

    // Contar total de administradores
    $sql = "SELECT COUNT(*) as total FROM admin";
    $result = $conn->query($sql);
    if ($result) {
        $estadisticas['total_admins'] = $result->fetch_assoc()['total'];
    }

    // Contar total de reservas
    $sql = "SELECT COUNT(*) as total FROM reservas";
    $result = $conn->query($sql);
    if ($result) {
        $estadisticas['total_reservas'] = $result->fetch_assoc()['total'];
    }

    // Contar total de mesas
    $sql = "SELECT COUNT(*) as total FROM mesas";
    $result = $conn->query($sql);
    if ($result) {
        $estadisticas['total_mesas'] = $result->fetch_assoc()['total'];
    }

    // Contar reservas de hoy
    $sql = "SELECT COUNT(*) as total FROM reservas WHERE DATE(fecha_reserva) = CURDATE()";
    $result = $conn->query($sql);
    if ($result) {
        $estadisticas['reservas_hoy'] = $result->fetch_assoc()['total'];
    }

    // Contar reservas de esta semana
    $sql = "SELECT COUNT(*) as total FROM reservas WHERE WEEK(fecha_reserva) = WEEK(CURDATE()) AND YEAR(fecha_reserva) = YEAR(CURDATE())";
    $result = $conn->query($sql);
    if ($result) {
        $estadisticas['reservas_semana'] = $result->fetch_assoc()['total'];
    }

    // Contar restaurantes con al menos una mesa
    $sql = "SELECT COUNT(DISTINCT r.id) as total FROM restaurantes r";
    $result = $conn->query($sql);
    if ($result) {
        $estadisticas['restaurantes_activos'] = $result->fetch_assoc()['total'];
    }

    // Admins registrados (mismo que total_admins)
    $estadisticas['admins_registrados'] = $estadisticas['total_admins'];

} catch (Exception $e) {
    // En caso de error, mantener valores por defecto en 0
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
}

// Devolver datos como JSON
header('Content-Type: application/json');
echo json_encode($estadisticas);

$conn->close();
?> 