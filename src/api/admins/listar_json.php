<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

$sql = "SELECT id, nombre FROM admin";
$result = $conn->query($sql);

$admins = [];

while ($row = $result->fetch_assoc()) {
    $admins[] = [
        'id' => $row['id'],
        'nombre' => $row['nombre']
    ];
}

header('Content-Type: application/json');
echo json_encode($admins);

$conn->close();
?>
