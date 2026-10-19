<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_admin = $_SESSION['id'] ?? null;
if (!$id_admin) {
    echo "Sesión no válida.";
    exit;
}

// Obtener restaurante asociado
$sql_rest = "SELECT id FROM restaurantes WHERE id_admin = ?";
$stmt_rest = $conn->prepare($sql_rest);
$stmt_rest->bind_param("i", $id_admin);
$stmt_rest->execute();
$res_rest = $stmt_rest->get_result();

if ($res_rest->num_rows === 0) {
    echo "No hay restaurante asociado al administrador.";
    exit;
}

$id_restaurante = $res_rest->fetch_assoc()['id'];

// Obtener datos del formulario
$nombre = $_POST['nombre'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$precio = $_POST['precio'] ?? '';
$categoria_id = $_POST['id_categoria'] ?? '';
$imagen = null;

// Validaciones
if (!$nombre || !$descripcion || !$precio || !$categoria_id) {
    echo "Todos los campos son obligatorios.";
    exit;
}

if (!is_numeric($precio)) {
    echo "El precio debe ser un número válido.";
    exit;
}

// Validar imagen
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
    $imagen = file_get_contents($_FILES['imagen']['tmp_name']);
} else {
    echo "La imagen es obligatoria y debe estar correctamente cargada.";
    exit;
}

// Preparar consulta
$sql = "INSERT INTO platosmenu (nombre, descripcion, precio, imagen, categoria_id, id_restaurante)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

// IMPORTANTE: 's' para strings, 'd' para double/float, 'i' para int
$stmt->bind_param("ssdsii", $nombre, $descripcion, $precio, $imagen, $categoria_id, $id_restaurante);

// Ejecutar e informar
if ($stmt->execute()) {
    echo "Plato registrado correctamente.";
} else {
    echo "Error al registrar el plato: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
