<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
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
$nombre = $_POST['nombre'] ?? '';
$direccion = $_POST['direccion'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$horario = $_POST['horario'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$correo = $_POST['correo'] ?? '';
$web = $_POST['web'] ?? '';
$redes_sociales = $_POST['redes_sociales'] ?? '';
$imagen_path = null;

if (!$id || !$nombre) {
    echo "ID y nombre obligatorios.";
    exit;
}

// Procesar nueva imagen si se subió
if (isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name'] !== '') {
    $archivo = $_FILES['imagen'];
    
    // Validar tipo de archivo
    $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($archivo['type'], $tipos_permitidos)) {
        echo "Error: Solo se permiten archivos de imagen (JPG, PNG, GIF, WEBP).";
        exit;
    }
    
    // Validar tamaño del archivo (máximo 2MB)
    $tamaño_maximo = 2 * 1024 * 1024; // 2MB en bytes
    if ($archivo['size'] > $tamaño_maximo) {
        echo "Error: La imagen no puede ser mayor a 2MB.";
        exit;
    }
    
    // Obtener imagen actual para eliminarla después
    $sql_imagen_actual = "SELECT imagen_path FROM restaurantes WHERE id = ?";
    $stmt_imagen = $conn->prepare($sql_imagen_actual);
    $stmt_imagen->bind_param("i", $id);
    $stmt_imagen->execute();
    $result_imagen = $stmt_imagen->get_result();
    $imagen_actual = null;
    if ($result_imagen->num_rows > 0) {
        $row_imagen = $result_imagen->fetch_assoc();
        $imagen_actual = $row_imagen['imagen_path'];
    }
    $stmt_imagen->close();
    
    // Crear directorio si no existe
    $directorio_destino = dirname(__DIR__, 3) . '/public/assets/img/restaurantes/';
    if (!file_exists($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }
    
    // Generar nombre único para la nueva imagen
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'restaurante_' . time() . '_' . uniqid() . '.' . $extension;
    $ruta_completa = $directorio_destino . $nombre_archivo;
    
    // Mover archivo directamente
    if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        $imagen_path = 'public/assets/img/restaurantes/' . $nombre_archivo;
    } else {
        echo "Error al subir la nueva imagen.";
        exit;
    }
    
    // Actualizar con nueva imagen
    $sql = "UPDATE restaurantes 
            SET nombre = ?, direccion = ?, descripcion = ?, horario = ?, telefono = ?, correo = ?, web = ?, redes_sociales = ?, imagen_path = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssi", $nombre, $direccion, $descripcion, $horario, $telefono, $correo, $web, $redes_sociales, $imagen_path, $id);
    
    if ($stmt->execute()) {
        // Eliminar imagen anterior si existe
        if ($imagen_actual && file_exists('../' . $imagen_actual)) {
            unlink('../' . $imagen_actual);
        }
        echo "Restaurante actualizado correctamente con nueva imagen.";
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }
} else {
    // Actualizar sin cambiar imagen
    $sql = "UPDATE restaurantes 
            SET nombre = ?, direccion = ?, descripcion = ?, horario = ?, telefono = ?, correo = ?, web = ?, redes_sociales = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", $nombre, $direccion, $descripcion, $horario, $telefono, $correo, $web, $redes_sociales, $id);
    
    if ($stmt->execute()) {
        echo "Restaurante actualizado correctamente.";
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();
?>
