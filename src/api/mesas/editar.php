<?php
// editar_mesa.php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = $_POST['id'] ?? null;
$numero = $_POST['numero_mesa'] ?? null;
$capacidad = $_POST['capacidad'] ?? null;
$rol_usuario = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id'] ?? null;

if (!$id || !$numero || !$capacidad) {
    echo "Todos los campos son obligatorios.";
    exit;
}

// Verificar que el admin solo pueda editar mesas de su restaurante
if ($rol_usuario === 'admin') {
    $sql_verificar = "SELECT m.id FROM mesas m 
                      INNER JOIN restaurantes r ON m.id_restaurante = r.id 
                      WHERE m.id = ? AND r.id_admin = ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param("ii", $id, $id_usuario);
    $stmt_verificar->execute();
    $result_verificar = $stmt_verificar->get_result();
    
    if ($result_verificar->num_rows == 0) {
        echo "Error: No tienes permisos para editar esta mesa.";
        exit;
    }
}

// Verificar si el nuevo número de mesa ya existe (excluyendo la mesa actual)
if ($rol_usuario === 'admin') {
    $sql_check_numero = "SELECT m.id FROM mesas m 
                         INNER JOIN restaurantes r ON m.id_restaurante = r.id 
                         WHERE m.numero_mesa = ? AND r.id_admin = ? AND m.id != ?";
    $stmt_check = $conn->prepare($sql_check_numero);
    $stmt_check->bind_param("iii", $numero, $id_usuario, $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        echo "Error: Ya existe otra mesa con ese número en tu restaurante.";
        exit;
    }
}

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
    $imagen = file_get_contents($_FILES['imagen']['tmp_name']);
    $sql = "UPDATE mesas SET numero_mesa = ?, capacidad = ?, imagen = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iibi", $numero, $capacidad, $imagen, $id);
} else {
    $sql = "UPDATE mesas SET numero_mesa = ?, capacidad = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $numero, $capacidad, $id);
}

if ($stmt->execute()) {
    echo "Mesa actualizada correctamente.";
} else {
    echo "Error al actualizar mesa: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
