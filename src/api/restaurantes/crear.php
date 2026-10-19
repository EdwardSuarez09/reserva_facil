<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

$id_admin     = $_POST['id_admin'] ?? null;
$nombre       = $_POST['nombre'] ?? '';
$direccion    = $_POST['direccion'] ?? '';
$descripcion  = $_POST['descripcion'] ?? '';
$horario      = $_POST['horario'] ?? '';
$telefono     = $_POST['telefono'] ?? '';
$correo       = $_POST['correo'] ?? '';
$web          = $_POST['web'] ?? '';
$redes        = $_POST['redes_sociales'] ?? '';
$imagen_path  = null;

if (!$id_admin || !$nombre) {
    echo "Administrador y nombre son obligatorios.";
    exit;
}

// Procesar imagen si se subió
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
    
    // Crear directorio si no existe
    $directorio_destino = dirname(__DIR__, 3) . '/public/assets/img/restaurantes/';
    if (!file_exists($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }
    
    // Generar nombre único para la imagen
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'restaurante_' . time() . '_' . uniqid() . '.' . $extension;
    $ruta_completa = $directorio_destino . $nombre_archivo;
    
    // Mover archivo directamente
    if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        $imagen_path = 'public/assets/img/restaurantes/' . $nombre_archivo;
    } else {
        echo "Error al subir la imagen.";
        exit;
    }
}

$sql = "INSERT INTO restaurantes 
        (id_admin, nombre, direccion, descripcion, horario, telefono, correo, web, redes_sociales, imagen_path) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssssssss", 
    $id_admin, 
    $nombre, 
    $direccion, 
    $descripcion, 
    $horario, 
    $telefono, 
    $correo, 
    $web, 
    $redes, 
    $imagen_path
);

if ($stmt->execute()) {
    echo "Restaurante registrado correctamente.";
} else {
    echo "Error al registrar el restaurante: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
