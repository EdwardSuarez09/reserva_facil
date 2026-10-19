<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

$id_admin = $_SESSION['id'] ?? null;
if (!$id_admin) {
    echo "<p style='color:red;'>Sesión no válida. Por favor inicia sesión.</p>";
    exit;
}

// Obtener restaurante asociado al admin
$sql_rest = "SELECT id FROM restaurantes WHERE id_admin = ?";
$stmt_rest = $conn->prepare($sql_rest);
$stmt_rest->bind_param("i", $id_admin);
$stmt_rest->execute();
$res_rest = $stmt_rest->get_result();

if ($res_rest->num_rows === 0) {
    echo "No hay restaurante asociado al administrador.";
    exit;
}

$row_rest = $res_rest->fetch_assoc();
$id_restaurante = $row_rest['id'];

// Obtener el nombre desde el formulario
$nombre = trim($_POST['nombre'] ?? '');

if (empty($nombre)) {
    echo "Todos los campos son obligatorios.";
    exit;
}

// Insertar categoría
$sql = "INSERT INTO categorias (nombre, id_restaurante) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nombre, $id_restaurante);

if ($stmt->execute()) {
    echo "Categoría registrada correctamente.";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
