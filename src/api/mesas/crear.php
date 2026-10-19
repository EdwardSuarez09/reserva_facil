<?php
// insertar_mesa.php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$numero = $_POST['numero_mesa'] ?? null;
$capacidad = $_POST['capacidad'] ?? null;
$rol_usuario = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id'] ?? null;

if (!$numero || !$capacidad || !isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== 0) {
    echo "Todos los campos son obligatorios y se requiere una imagen.";
    exit;
}

// Verificar si el número de mesa ya existe para este restaurante
if ($rol_usuario === 'admin') {
    // Admin solo puede agregar mesas a sus restaurantes
    $sql_check = "SELECT m.id FROM mesas m 
                  INNER JOIN restaurantes r ON m.id_restaurante = r.id 
                  WHERE m.numero_mesa = ? AND r.id_admin = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $numero, $id_usuario);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        echo "Error: Ya existe una mesa con ese número en tu restaurante.";
        exit;
    }
    
    // Obtener el ID del restaurante del admin
    $sql_restaurante = "SELECT id FROM restaurantes WHERE id_admin = ? LIMIT 1";
    $stmt_restaurante = $conn->prepare($sql_restaurante);
    $stmt_restaurante->bind_param("i", $id_usuario);
    $stmt_restaurante->execute();
    $result_restaurante = $stmt_restaurante->get_result();
    
    if ($result_restaurante->num_rows == 0) {
        echo "Error: No tienes un restaurante asignado.";
        exit;
    }
    
    $restaurante_data = $result_restaurante->fetch_assoc();
    $id_restaurante = $restaurante_data['id'];
    
} else {
    // Super admin necesita especificar el restaurante o crear sin asignar
    $id_restaurante = $_POST['id_restaurante'] ?? null;
    
    if (!$id_restaurante) {
        echo "Error: Super admin debe especificar el restaurante.";
        exit;
    }
}

$imagen = file_get_contents($_FILES['imagen']['tmp_name']);

// Verificar si la tabla mesas tiene el campo id_restaurante
$sql_check_campo = "SHOW COLUMNS FROM mesas LIKE 'id_restaurante'";
$result_campo = $conn->query($sql_check_campo);

if ($result_campo->num_rows > 0) {
    // Tabla actualizada - insertar con id_restaurante
    $sql = "INSERT INTO mesas (numero_mesa, capacidad, imagen, id_restaurante) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $numero, $capacidad, $imagen, $id_restaurante);
} else {
    // Tabla antigua - insertar sin id_restaurante
    $sql = "INSERT INTO mesas (numero_mesa, capacidad, imagen) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $numero, $capacidad, $imagen);
}

if ($stmt->execute()) {
    echo "Mesa registrada correctamente para tu restaurante.";
} else {
    echo "Error al registrar mesa: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>