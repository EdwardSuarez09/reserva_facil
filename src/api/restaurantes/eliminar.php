<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
include __DIR__ . '/../../helpers/rutas_assets.php';
verificarRol(['superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_admin = $_SESSION['id'] ?? null;
if (!$id_admin) {
    echo "<p style='color:red;'>Sesión no válida. Por favor inicia sesión.</p>";
    exit;
}

$id = $_POST['id'] ?? null;

if (!$id) {
    echo "ID inválido.";
    exit;
}

// PASO 0: Obtener la ruta de la imagen para eliminarla después
$sql_imagen = "SELECT imagen_path FROM restaurantes WHERE id = ?";
$stmt_imagen = $conn->prepare($sql_imagen);
$stmt_imagen->bind_param("i", $id);
$stmt_imagen->execute();
$result_imagen = $stmt_imagen->get_result();
$imagen_path = null;
if ($result_imagen->num_rows > 0) {
    $row_imagen = $result_imagen->fetch_assoc();
    $imagen_path = $row_imagen['imagen_path'];
}
$stmt_imagen->close();

// PASO 1: Eliminar platos de las categorías de este restaurante
$conn->query("DELETE FROM platosmenu WHERE categoria_id IN (SELECT id FROM categorias WHERE id_restaurante = $id)");

// PASO 2: Eliminar categorías del restaurante
$conn->query("DELETE FROM categorias WHERE id_restaurante = $id");

// PASO 3: Eliminar el restaurante
$sql = "DELETE FROM restaurantes WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // PASO 4: Eliminar la imagen del sistema de archivos si existe
    if ($imagen_path) {
        $rutaImagenArchivo = obtenerRutaImagenArchivo($imagen_path);
        if (is_file($rutaImagenArchivo)) {
            unlink($rutaImagenArchivo);
        }
    }
    echo "Restaurante eliminado correctamente.";
} else {
    echo "Error al eliminar el restaurante: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
