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
        'total_platos' => 0,
        'precio_promedio' => '$0',
        'platos_populares' => 0,
        'ultimo_plato' => 'Nunca'
    ];

    if ($rol_usuario === 'superadmin') {
        // Super admin ve todos los platos
        
        // Determinar qué tabla usar
        $tabla_platos = 'platos';
        $sql_check_table = "SHOW TABLES LIKE 'platosmenu'";
        $result_check = $conn->query($sql_check_table);
        if ($result_check && $result_check->num_rows > 0) {
            $tabla_platos = 'platosmenu';
        }
        
        // Total platos
        $sql_total = "SELECT COUNT(*) as total FROM $tabla_platos";
        $result_total = $conn->query($sql_total);
        $estadisticas['total_platos'] = $result_total->fetch_assoc()['total'];

        // Precio promedio
        $sql_precio = "SELECT AVG(precio) as promedio FROM $tabla_platos WHERE precio > 0";
        $result_precio = $conn->query($sql_precio);
        if ($result_precio && $result_precio->num_rows > 0) {
            $promedio = $result_precio->fetch_assoc()['promedio'];
            if ($promedio) {
                $estadisticas['precio_promedio'] = '$' . number_format($promedio, 0, ',', '.');
            }
        }

        // Platos populares (platos con precio superior al promedio)
        $sql_populares = "SELECT COUNT(*) as total FROM $tabla_platos WHERE precio > (SELECT AVG(precio) FROM $tabla_platos WHERE precio > 0)";
        $result_populares = $conn->query($sql_populares);
        $estadisticas['platos_populares'] = $result_populares->fetch_assoc()['total'];

        // Último plato agregado - verificar si existe campo fecha_creacion
        $sql_check_fecha = "SHOW COLUMNS FROM $tabla_platos LIKE 'fecha_creacion'";
        $result_check_fecha = $conn->query($sql_check_fecha);
        
        if ($result_check_fecha && $result_check_fecha->num_rows > 0) {
            $sql_ultimo = "SELECT nombre, fecha_creacion FROM $tabla_platos ORDER BY fecha_creacion DESC LIMIT 1";
        } else {
            $sql_ultimo = "SELECT nombre, NOW() as fecha_creacion FROM $tabla_platos ORDER BY id DESC LIMIT 1";
        }
        
        $result_ultimo = $conn->query($sql_ultimo);
        if ($result_ultimo && $result_ultimo->num_rows > 0) {
            $ultimo = $result_ultimo->fetch_assoc();
            if ($ultimo['fecha_creacion']) {
                $fecha = new DateTime($ultimo['fecha_creacion']);
                $hoy = new DateTime();
                $diff = $hoy->diff($fecha);
                
                if ($diff->days == 0) {
                    $estadisticas['ultimo_plato'] = 'Hoy';
                } elseif ($diff->days == 1) {
                    $estadisticas['ultimo_plato'] = 'Ayer';
                } elseif ($diff->days < 7) {
                    $estadisticas['ultimo_plato'] = $diff->days . ' días';
                } else {
                    $estadisticas['ultimo_plato'] = $fecha->format('d/m/Y');
                }
            } else {
                $estadisticas['ultimo_plato'] = 'Sin fecha';
            }
        }

    } else {
        // Admin normal solo ve platos de sus restaurantes
        
        // Determinar qué tabla usar
        $tabla_platos = 'platos';
        $sql_check_table = "SHOW TABLES LIKE 'platosmenu'";
        $result_check = $conn->query($sql_check_table);
        if ($result_check && $result_check->num_rows > 0) {
            $tabla_platos = 'platosmenu';
        }
        
        // Total platos del admin
        if ($tabla_platos === 'platosmenu') {
            $sql_total = "SELECT COUNT(*) as total 
                          FROM $tabla_platos p 
                          INNER JOIN restaurantes r ON p.id_restaurante = r.id 
                          WHERE r.id_admin = ?";
        } else {
            $sql_total = "SELECT COUNT(*) as total 
                          FROM $tabla_platos p 
                          INNER JOIN restaurantes r ON p.id_restaurante = r.id 
                          WHERE r.id_admin = ?";
        }
        
        $stmt_total = $conn->prepare($sql_total);
        $stmt_total->bind_param("i", $id_usuario);
        $stmt_total->execute();
        $result_total = $stmt_total->get_result();
        $estadisticas['total_platos'] = $result_total->fetch_assoc()['total'];

        // Precio promedio de platos del admin
        $sql_precio = "SELECT AVG(p.precio) as promedio 
                       FROM $tabla_platos p 
                       INNER JOIN restaurantes r ON p.id_restaurante = r.id 
                       WHERE r.id_admin = ? AND p.precio > 0";
        $stmt_precio = $conn->prepare($sql_precio);
        $stmt_precio->bind_param("i", $id_usuario);
        $stmt_precio->execute();
        $result_precio = $stmt_precio->get_result();
        if ($result_precio && $result_precio->num_rows > 0) {
            $promedio = $result_precio->fetch_assoc()['promedio'];
            if ($promedio) {
                $estadisticas['precio_promedio'] = '$' . number_format($promedio, 0, ',', '.');
            }
        }

        // Platos populares del admin (platos con precio superior al promedio)
        $sql_populares = "SELECT COUNT(*) as total 
                          FROM $tabla_platos p 
                          INNER JOIN restaurantes r ON p.id_restaurante = r.id 
                          WHERE r.id_admin = ? AND p.precio > (
                              SELECT AVG(p2.precio) 
                              FROM $tabla_platos p2 
                              INNER JOIN restaurantes r2 ON p2.id_restaurante = r2.id 
                              WHERE r2.id_admin = ? AND p2.precio > 0
                          )";
        $stmt_populares = $conn->prepare($sql_populares);
        $stmt_populares->bind_param("ii", $id_usuario, $id_usuario);
        $stmt_populares->execute();
        $result_populares = $stmt_populares->get_result();
        $estadisticas['platos_populares'] = $result_populares->fetch_assoc()['total'];

        // Último plato agregado por el admin - verificar si existe campo fecha_creacion
        $sql_check_fecha = "SHOW COLUMNS FROM $tabla_platos LIKE 'fecha_creacion'";
        $result_check_fecha = $conn->query($sql_check_fecha);
        
        if ($result_check_fecha && $result_check_fecha->num_rows > 0) {
            $sql_ultimo = "SELECT p.nombre, p.fecha_creacion 
                           FROM $tabla_platos p 
                           INNER JOIN restaurantes r ON p.id_restaurante = r.id 
                           WHERE r.id_admin = ? 
                           ORDER BY p.fecha_creacion DESC 
                           LIMIT 1";
        } else {
            $sql_ultimo = "SELECT p.nombre, NOW() as fecha_creacion 
                           FROM $tabla_platos p 
                           INNER JOIN restaurantes r ON p.id_restaurante = r.id 
                           WHERE r.id_admin = ? 
                           ORDER BY p.id DESC 
                           LIMIT 1";
        }
        
        $stmt_ultimo = $conn->prepare($sql_ultimo);
        $stmt_ultimo->bind_param("i", $id_usuario);
        $stmt_ultimo->execute();
        $result_ultimo = $stmt_ultimo->get_result();
        
        if ($result_ultimo && $result_ultimo->num_rows > 0) {
            $ultimo = $result_ultimo->fetch_assoc();
            if ($ultimo['fecha_creacion']) {
                $fecha = new DateTime($ultimo['fecha_creacion']);
                $hoy = new DateTime();
                $diff = $hoy->diff($fecha);
                
                if ($diff->days == 0) {
                    $estadisticas['ultimo_plato'] = 'Hoy';
                } elseif ($diff->days == 1) {
                    $estadisticas['ultimo_plato'] = 'Ayer';
                } elseif ($diff->days < 7) {
                    $estadisticas['ultimo_plato'] = $diff->days . ' días';
                } else {
                    $estadisticas['ultimo_plato'] = $fecha->format('d/m/Y');
                }
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($estadisticas);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

$conn->close();
?> 