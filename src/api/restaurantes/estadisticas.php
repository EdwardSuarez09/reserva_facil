<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $estadisticas = [
        'total_restaurantes' => 0,
        'restaurantes_activos' => 0,
        'con_admin' => 0,
        'ultimo_restaurante' => 'Ninguno'
    ];

    // Total restaurantes
    $sql_total = "SELECT COUNT(*) as total FROM restaurantes";
    $result_total = $conn->query($sql_total);
    if ($result_total) {
        $row = $result_total->fetch_assoc();
        $estadisticas['total_restaurantes'] = (int)($row['total'] ?? 0);
    }

    // Restaurantes activos (consideramos activos a todos por ahora, 
    // se puede agregar un campo 'activo' en el futuro)
    $estadisticas['restaurantes_activos'] = $estadisticas['total_restaurantes'];

    // Restaurantes con administrador asignado
    $sql_con_admin = "SELECT COUNT(*) as total FROM restaurantes WHERE id_admin IS NOT NULL";
    $result_con_admin = $conn->query($sql_con_admin);
    if ($result_con_admin) {
        $row = $result_con_admin->fetch_assoc();
        $estadisticas['con_admin'] = (int)($row['total'] ?? 0);
    }

    // Último restaurante registrado
    $sql_ultimo = "SELECT nombre FROM restaurantes ORDER BY id DESC LIMIT 1";
    $result_ultimo = $conn->query($sql_ultimo);
    if ($result_ultimo && $result_ultimo->num_rows > 0) {
        $ultimo = $result_ultimo->fetch_assoc();
        $estadisticas['ultimo_restaurante'] = $ultimo['nombre'];
    }

    header('Content-Type: application/json');
    echo json_encode($estadisticas);

} catch (Exception $e) {
    http_response_code(500);
    $error_stats = [
        'total_restaurantes' => 0,
        'restaurantes_activos' => 0,
        'con_admin' => 0,
        'ultimo_restaurante' => 'Error'
    ];
    echo json_encode($error_stats);
}

$conn->close();
?> 