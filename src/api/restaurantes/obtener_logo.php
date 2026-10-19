<?php
include __DIR__ . '/../../config/conexion.php';

$restaurante = $_GET['restaurante'] ?? '';

if (empty($restaurante)) {
    exit;
}

try {
    // Obtener el logo del restaurante
    $stmt = $conn->prepare("SELECT imagen FROM restaurantes WHERE nombre = ?");
    $stmt->bind_param("s", $restaurante);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $restaurante_data = $resultado->fetch_assoc();
    
    if ($restaurante_data && !empty($restaurante_data['imagen'])) {
        echo "<img src='data:image/jpeg;base64," . base64_encode($restaurante_data['imagen']) . "' alt='Logo " . htmlspecialchars($restaurante) . "'>";
    }
    // Si no hay logo, no mostramos nada
    
} catch (Exception $e) {
    // En caso de error, no mostramos nada
}

$conn->close();
?> 