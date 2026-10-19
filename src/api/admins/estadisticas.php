<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_admin = $_SESSION['id'] ?? null;
if (!$id_admin) {
    echo json_encode(['error' => 'Sesión no válida']);
    exit;
}

// Inicializar contadores
$estadisticas = [
    'total_categorias' => 0,
    'total_platos' => 0,
    'total_mesas' => 0,
    'total_reservas' => 0
];

try {
    // Obtener el ID del restaurante del admin
    $sql = "SELECT id FROM restaurantes WHERE id_admin = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_admin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode($estadisticas);
        exit;
    }
    
    $restaurante = $result->fetch_assoc();
    $id_restaurante = $restaurante['id'];

    // Contar categorías del restaurante
    $sql = "SELECT COUNT(*) as total FROM categorias WHERE id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_restaurante);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $estadisticas['total_categorias'] = $result->fetch_assoc()['total'];
    }

    // Contar platos del restaurante
    $sql = "SELECT COUNT(*) as total FROM platosmenu WHERE id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_restaurante);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $estadisticas['total_platos'] = $result->fetch_assoc()['total'];
    }

    // Contar mesas del restaurante
    $sql = "SELECT COUNT(*) as total FROM mesas WHERE id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_restaurante);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $estadisticas['total_mesas'] = $result->fetch_assoc()['total'];
    }

    // Contar reservas del restaurante
    $sql = "SELECT COUNT(*) as total FROM reservas WHERE id_restaurante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_restaurante);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $estadisticas['total_reservas'] = $result->fetch_assoc()['total'];
    }

} catch (Exception $e) {
    error_log("Error obteniendo estadísticas admin: " . $e->getMessage());
}

// Devolver datos como JSON
header('Content-Type: application/json');
echo json_encode($estadisticas);

$conn->close();
?> 