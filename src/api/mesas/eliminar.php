<?php

include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = $_POST['id'] ?? null;
$rol_usuario = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id'] ?? null;

if (!$id) {
    echo "ID inválido.";
    exit;
}

// Verificar que el admin solo pueda eliminar mesas de su restaurante
if ($rol_usuario === 'admin') {
    $sql_verificar = "SELECT m.id FROM mesas m 
                      INNER JOIN restaurantes r ON m.id_restaurante = r.id 
                      WHERE m.id = ? AND r.id_admin = ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("ii", $id, $id_usuario);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();
    
    if ($result_verificar->num_rows == 0) {
        echo "Error: No tienes permisos para eliminar esta mesa.";
        exit;
    }
}

$sql = "DELETE FROM mesas WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "Mesa eliminada correctamente.";
} else {
    echo "Error al eliminar mesa: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
