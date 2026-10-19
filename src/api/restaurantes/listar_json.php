<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol_usuario = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id'] ?? null;

// Construir consulta según el rol
if ($rol_usuario === 'superadmin') {
    // Super admin ve todos los restaurantes
    $sql = "SELECT id, nombre FROM restaurantes ORDER BY nombre";
    $result = $conn->query($sql);
} else {
    // Admin solo ve sus restaurantes
    $sql = "SELECT id, nombre FROM restaurantes WHERE id_admin = ? ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
}

$restaurantes = [];
while ($row = $result->fetch_assoc()) {
    $restaurantes[] = $row;
}

header('Content-Type: application/json');
echo json_encode($restaurantes);

$conn->close();
?> 