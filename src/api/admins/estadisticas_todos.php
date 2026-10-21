<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $estadisticas = [
        'total_admins' => 0,
        'total_superadmins' => 0,
        'total_usuarios' => 0,
        'ultimo_admin' => 'Ninguno'
    ];

    // Total administradores
    $sql_total = "SELECT COUNT(*) as total FROM admin WHERE rol IN ('admin', 'superadmin')";
    $result_total = $conn->query($sql_total);
    if ($result_total) {
        $row = $result_total->fetch_assoc();
        $estadisticas['total_admins'] = (int)($row['total'] ?? 0);
    }

    // Super administradores
    $sql_superadmins = "SELECT COUNT(*) as total FROM admin WHERE rol = 'superadmin'";
    $result_superadmins = $conn->query($sql_superadmins);
    if ($result_superadmins) {
        $row = $result_superadmins->fetch_assoc();
        $estadisticas['total_superadmins'] = (int)($row['total'] ?? 0);
    }

    // Usuarios normales
    $sql_usuarios = "SELECT COUNT(*) as total FROM usuarios_cliente";
    $result_usuarios = $conn->query($sql_usuarios);
    if ($result_usuarios) {
        $row = $result_usuarios->fetch_assoc();
        $estadisticas['total_usuarios'] = (int)($row['total'] ?? 0);
    }

    // Último administrador registrado
    $sql_ultimo = "SELECT nombre FROM admin WHERE rol IN ('admin', 'superadmin') ORDER BY id DESC LIMIT 1";
    $result_ultimo = $conn->query($sql_ultimo);
    if ($result_ultimo && $result_ultimo->num_rows > 0) {
        $ultimo = $result_ultimo->fetch_assoc();
        $estadisticas['ultimo_admin'] = $ultimo['nombre'];
    }

    header('Content-Type: application/json');
    echo json_encode($estadisticas);

} catch (Exception $e) {
    http_response_code(500);
    $error_stats = [
        'total_admins' => 0,
        'total_superadmins' => 0,
        'total_usuarios' => 0,
        'ultimo_admin' => 'Error'
    ];
    echo json_encode($error_stats);
}

$conn->close();
?> 