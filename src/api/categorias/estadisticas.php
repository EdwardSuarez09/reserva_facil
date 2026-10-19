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
        'total_categorias' => 0,
        'categorias_con_platos' => 0,
        'categorias_vacias' => 0,
        'ultima_categoria' => 'Nunca'
    ];

    // Verificar si existe tabla platosmenu
    $sql_check_table = "SHOW TABLES LIKE 'platosmenu'";
    $result_check = $conn->query($sql_check_table);
    $usa_platosmenu = ($result_check && $result_check->num_rows > 0);

    if ($rol_usuario === 'superadmin') {
        // Super admin ve todas las categorías
        
        // Total categorías
        $sql_total = "SELECT COUNT(*) as total FROM categorias";
        $result_total = $conn->query($sql_total);
        if ($result_total) {
            $row = $result_total->fetch_assoc();
            $estadisticas['total_categorias'] = (int)($row['total'] ?? 0);
        }

        // Categorías que realmente existen y tienen platos
        $sql_con_platos = "SELECT COUNT(DISTINCT c.id) as total 
                          FROM categorias c 
                          INNER JOIN platosmenu p ON c.id = p.categoria_id 
                          WHERE p.categoria_id IS NOT NULL";
        
        $result_con_platos = $conn->query($sql_con_platos);
        if ($result_con_platos) {
            $row = $result_con_platos->fetch_assoc();
            $estadisticas['categorias_con_platos'] = (int)($row['total'] ?? 0);
        }

        // Categorías vacías
        $estadisticas['categorias_vacias'] = $estadisticas['total_categorias'] - $estadisticas['categorias_con_platos'];

        // Última categoría - usar la más reciente por ID
        $sql_ultima = "SELECT nombre FROM categorias ORDER BY id DESC LIMIT 1";
        $result_ultima = $conn->query($sql_ultima);
        if ($result_ultima && $result_ultima->num_rows > 0) {
            $ultima = $result_ultima->fetch_assoc();
            $estadisticas['ultima_categoria'] = $ultima['nombre'];
        }

    } else {
        // Admin normal - filtrar por sus restaurantes
        
        // Obtener restaurantes del admin
        $sql_restaurantes_admin = "SELECT id FROM restaurantes WHERE id_admin = ?";
        $stmt_restaurantes = $conn->prepare($sql_restaurantes_admin);
        $stmt_restaurantes->bind_param("i", $id_usuario);
        $stmt_restaurantes->execute();
        $result_restaurantes = $stmt_restaurantes->get_result();
        
        $restaurantes_ids = [];
        while ($row = $result_restaurantes->fetch_assoc()) {
            $restaurantes_ids[] = $row['id'];
        }

        if (!empty($restaurantes_ids)) {
            $ids_str = implode(',', $restaurantes_ids);
            
            // Categorías usadas en restaurantes del admin
            $sql_total = "SELECT COUNT(DISTINCT p.categoria_id) as total 
                          FROM platosmenu p 
                          INNER JOIN categorias c ON p.categoria_id = c.id
                          WHERE p.id_restaurante IN ($ids_str) 
                          AND p.categoria_id IS NOT NULL";
            
            $result_total = $conn->query($sql_total);
            if ($result_total) {
                $row = $result_total->fetch_assoc();
                $estadisticas['total_categorias'] = (int)($row['total'] ?? 0);
            }

            // Para admin, todas las categorías contadas tienen platos
            $estadisticas['categorias_con_platos'] = $estadisticas['total_categorias'];
            $estadisticas['categorias_vacias'] = 0;

            // Última categoría usada
            if ($estadisticas['total_categorias'] > 0) {
                $sql_ultima = "SELECT c.nombre 
                               FROM categorias c 
                               INNER JOIN platosmenu p ON c.id = p.categoria_id 
                               WHERE p.id_restaurante IN ($ids_str) 
                               ORDER BY p.id DESC 
                               LIMIT 1";
                
                $result_ultima = $conn->query($sql_ultima);
                if ($result_ultima && $result_ultima->num_rows > 0) {
                    $ultima = $result_ultima->fetch_assoc();
                    $estadisticas['ultima_categoria'] = $ultima['nombre'];
                }
            }
        } else {
            // Si no tiene restaurantes, valores por defecto
            $estadisticas['total_categorias'] = 0;
            $estadisticas['categorias_con_platos'] = 0;
            $estadisticas['categorias_vacias'] = 0;
            $estadisticas['ultima_categoria'] = 'Sin restaurante';
        }
    }

    header('Content-Type: application/json');
    echo json_encode($estadisticas);

} catch (Exception $e) {
    http_response_code(500);
    $error_stats = [
        'total_categorias' => 0,
        'categorias_con_platos' => 0,
        'categorias_vacias' => 0,
        'ultima_categoria' => 'Error'
    ];
    echo json_encode($error_stats);
}

$conn->close();
?> 