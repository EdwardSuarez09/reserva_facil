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
        'total_mesas' => 0,
        'mesas_disponibles' => 0,
        'mesas_ocupadas' => 0,
        'capacidad_total' => 0
    ];

    // Verificar si la tabla mesas tiene el campo id_restaurante
    $sql_check_campo = "SHOW COLUMNS FROM mesas LIKE 'id_restaurante'";
    $result_check_campo = $conn->query($sql_check_campo);
    $tiene_id_restaurante = ($result_check_campo && $result_check_campo->num_rows > 0);

    if ($rol_usuario === 'superadmin') {
        // Super admin ve todas las mesas
        
        // Total mesas
        $sql_total = "SELECT COUNT(*) as total FROM mesas";
        $result_total = $conn->query($sql_total);
        if ($result_total) {
            $row = $result_total->fetch_assoc();
            $estadisticas['total_mesas'] = (int)($row['total'] ?? 0);
        }

        // Capacidad total
        $sql_capacidad = "SELECT SUM(capacidad) as total FROM mesas";
        $result_capacidad = $conn->query($sql_capacidad);
        if ($result_capacidad) {
            $row = $result_capacidad->fetch_assoc();
            $estadisticas['capacidad_total'] = (int)($row['total'] ?? 0);
        }

        // Mesas ocupadas (con reservas activas para hoy) - verificar si existe tabla reservas y campo duracion
        $sql_check_reservas = "SHOW TABLES LIKE 'reservas'";
        $result_check_reservas = $conn->query($sql_check_reservas);
        
        if ($result_check_reservas && $result_check_reservas->num_rows > 0) {
            $sql_check_duracion = "SHOW COLUMNS FROM reservas LIKE 'duracion'";
            $result_check_duracion = $conn->query($sql_check_duracion);
            
            if ($result_check_duracion && $result_check_duracion->num_rows > 0) {
                $sql_ocupadas = "SELECT COUNT(DISTINCT m.id) as total 
                                 FROM mesas m 
                                 INNER JOIN reservas r ON m.id = r.mesa_id 
                                 WHERE DATE(r.fecha_reserva) = CURDATE() 
                                 AND r.estado IN ('Confirmada', 'Pendiente')
                                 AND TIME(r.hora_reserva) <= CURTIME() 
                                 AND ADDTIME(TIME(r.hora_reserva), SEC_TO_TIME(r.duracion * 60)) >= CURTIME()";
            } else {
                // Sin campo duración, usar estimación de 2 horas
                $sql_ocupadas = "SELECT COUNT(DISTINCT m.id) as total 
                                 FROM mesas m 
                                 INNER JOIN reservas r ON m.id = r.mesa_id 
                                 WHERE DATE(r.fecha_reserva) = CURDATE() 
                                 AND r.estado IN ('Confirmada', 'Pendiente')
                                 AND TIME(r.hora_reserva) <= CURTIME() 
                                 AND ADDTIME(TIME(r.hora_reserva), '02:00:00') >= CURTIME()";
            }
            
            $result_ocupadas = $conn->query($sql_ocupadas);
            if ($result_ocupadas) {
                $row = $result_ocupadas->fetch_assoc();
                $estadisticas['mesas_ocupadas'] = (int)($row['total'] ?? 0);
            }
        }

        // Mesas disponibles
        $estadisticas['mesas_disponibles'] = $estadisticas['total_mesas'] - $estadisticas['mesas_ocupadas'];

    } else {
        // Admin normal
        
        if ($tiene_id_restaurante) {
            // Si tiene campo id_restaurante, filtrar por restaurantes del admin
            
            // Total mesas del admin
            $sql_total = "SELECT COUNT(*) as total 
                          FROM mesas m 
                          INNER JOIN restaurantes r ON m.id_restaurante = r.id 
                          WHERE r.id_admin = ?";
            $stmt_total = $conn->prepare($sql_total);
            $stmt_total->bind_param("i", $id_usuario);
            $stmt_total->execute();
            $result_total = $stmt_total->get_result();
            if ($result_total) {
                $row = $result_total->fetch_assoc();
                $estadisticas['total_mesas'] = (int)($row['total'] ?? 0);
            }

            // Capacidad total del admin
            $sql_capacidad = "SELECT SUM(m.capacidad) as total 
                              FROM mesas m 
                              INNER JOIN restaurantes r ON m.id_restaurante = r.id 
                              WHERE r.id_admin = ?";
            $stmt_capacidad = $conn->prepare($sql_capacidad);
            $stmt_capacidad->bind_param("i", $id_usuario);
            $stmt_capacidad->execute();
            $result_capacidad = $stmt_capacidad->get_result();
            if ($result_capacidad) {
                $row = $result_capacidad->fetch_assoc();
                $estadisticas['capacidad_total'] = (int)($row['total'] ?? 0);
            }

            // Mesas ocupadas del admin
            $sql_check_reservas = "SHOW TABLES LIKE 'reservas'";
            $result_check_reservas = $conn->query($sql_check_reservas);
            
            if ($result_check_reservas && $result_check_reservas->num_rows > 0) {
                $sql_check_duracion = "SHOW COLUMNS FROM reservas LIKE 'duracion'";
                $result_check_duracion = $conn->query($sql_check_duracion);
                
                if ($result_check_duracion && $result_check_duracion->num_rows > 0) {
                    $sql_ocupadas = "SELECT COUNT(DISTINCT m.id) as total 
                                     FROM mesas m 
                                     INNER JOIN restaurantes rest ON m.id_restaurante = rest.id 
                                     INNER JOIN reservas r ON m.id = r.mesa_id 
                                     WHERE rest.id_admin = ? 
                                     AND DATE(r.fecha_reserva) = CURDATE() 
                                     AND r.estado IN ('Confirmada', 'Pendiente')
                                     AND TIME(r.hora_reserva) <= CURTIME() 
                                     AND ADDTIME(TIME(r.hora_reserva), SEC_TO_TIME(r.duracion * 60)) >= CURTIME()";
                } else {
                    $sql_ocupadas = "SELECT COUNT(DISTINCT m.id) as total 
                                     FROM mesas m 
                                     INNER JOIN restaurantes rest ON m.id_restaurante = rest.id 
                                     INNER JOIN reservas r ON m.id = r.mesa_id 
                                     WHERE rest.id_admin = ? 
                                     AND DATE(r.fecha_reserva) = CURDATE() 
                                     AND r.estado IN ('Confirmada', 'Pendiente')
                                     AND TIME(r.hora_reserva) <= CURTIME() 
                                     AND ADDTIME(TIME(r.hora_reserva), '02:00:00') >= CURTIME()";
                }
                
                $stmt_ocupadas = $conn->prepare($sql_ocupadas);
                $stmt_ocupadas->bind_param("i", $id_usuario);
                $stmt_ocupadas->execute();
                $result_ocupadas = $stmt_ocupadas->get_result();
                if ($result_ocupadas) {
                    $row = $result_ocupadas->fetch_assoc();
                    $estadisticas['mesas_ocupadas'] = (int)($row['total'] ?? 0);
                }
            }

            // Mesas disponibles del admin
            $estadisticas['mesas_disponibles'] = $estadisticas['total_mesas'] - $estadisticas['mesas_ocupadas'];
            
        } else {
            // Si no tiene campo id_restaurante, mostrar todas las mesas (comportamiento anterior)
            
            // Total mesas
            $sql_total = "SELECT COUNT(*) as total FROM mesas";
            $result_total = $conn->query($sql_total);
            if ($result_total) {
                $row = $result_total->fetch_assoc();
                $estadisticas['total_mesas'] = (int)($row['total'] ?? 0);
            }

            // Capacidad total
            $sql_capacidad = "SELECT SUM(capacidad) as total FROM mesas";
            $result_capacidad = $conn->query($sql_capacidad);
            if ($result_capacidad) {
                $row = $result_capacidad->fetch_assoc();
                $estadisticas['capacidad_total'] = (int)($row['total'] ?? 0);
            }

            // Todas disponibles para admin sin restaurante específico
            $estadisticas['mesas_disponibles'] = $estadisticas['total_mesas'];
            $estadisticas['mesas_ocupadas'] = 0;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($estadisticas);

} catch (Exception $e) {
    http_response_code(500);
    $error_stats = [
        'total_mesas' => 0,
        'mesas_disponibles' => 0,
        'mesas_ocupadas' => 0,
        'capacidad_total' => 0
    ];
    echo json_encode($error_stats);
}

$conn->close();
?> 