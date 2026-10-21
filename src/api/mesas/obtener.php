<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = $_GET['id'] ?? null;
$rol_usuario = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id'] ?? null;

if (!$id) {
    echo json_encode(["error" => "ID no válido."]);
    exit;
}

// Verificar permisos según el rol
if ($rol_usuario === 'admin') {
    // Admin solo puede obtener mesas de su restaurante
    $sql = "SELECT m.id, m.numero_mesa, m.capacidad, r.nombre AS restaurante
            FROM mesas m 
            INNER JOIN restaurantes r ON m.id_restaurante = r.id 
            WHERE m.id = ? AND r.id_admin = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $id_usuario);
} else {
    // Super admin puede obtener cualquier mesa
    $sql = "SELECT m.id, m.numero_mesa, m.capacidad, r.nombre AS restaurante
            FROM mesas m
            LEFT JOIN restaurantes r ON r.id = m.id_restaurante
            WHERE m.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    if ($rol_usuario === 'admin') {
        echo json_encode(["error" => "Mesa no encontrada o no tienes permisos para verla."]);
    } else {
        echo json_encode(["error" => "Mesa no encontrada."]);
    }
}

$stmt->close();
$conn->close();
?>

<?php