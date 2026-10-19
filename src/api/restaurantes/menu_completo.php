<?php
include __DIR__ . '/../../config/conexion.php';

$restaurante = $_GET['restaurante'] ?? '';

if (empty($restaurante)) {
    echo "<p style='color:red;'>Restaurante no especificado.</p>";
    exit;
}

try {
    // Obtener el ID del restaurante
    $stmt = $conn->prepare("SELECT id FROM restaurantes WHERE nombre = ?");
    $stmt->bind_param("s", $restaurante);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $restaurante_data = $resultado->fetch_assoc();
    
    if (!$restaurante_data) {
        echo "<p style='color:red;'>Restaurante no encontrado.</p>";
        exit;
    }
    
    $restaurante_id = $restaurante_data['id'];
    
    // Obtener categorías y platos organizados
    $stmt = $conn->prepare("
        SELECT DISTINCT c.nombre as categoria_nombre, c.id as categoria_id
        FROM categorias c 
        INNER JOIN platosmenu p ON c.id = p.categoria_id 
        WHERE p.id_restaurante = ?
        ORDER BY c.nombre
    ");
    $stmt->bind_param("i", $restaurante_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $categorias = $resultado->fetch_all(MYSQLI_ASSOC);
    
    if (empty($categorias)) {
        echo "<div class='menu-por-categorias'><p>No hay platos disponibles para este restaurante.</p></div>";
        exit;
    }
    
    echo "<div class='menu-por-categorias'>";
    
    foreach ($categorias as $categoria) {
        echo "<h3 class='nombre-categoria'>" . htmlspecialchars($categoria['categoria_nombre']) . "</h3>";
        
        // Obtener todos los platos de esta categoría
        $stmt = $conn->prepare("
            SELECT nombre, descripcion, precio, imagen 
            FROM platosmenu 
            WHERE categoria_id = ? AND id_restaurante = ?
            ORDER BY nombre
        ");
        $stmt->bind_param("ii", $categoria['categoria_id'], $restaurante_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $platos = $resultado->fetch_all(MYSQLI_ASSOC);
        
        if (!empty($platos)) {
            echo "<div class='carrusel'>";
            foreach ($platos as $plato) {
                echo "<div class='plato-carta plato-menu-completo'>";
                
                if (!empty($plato['imagen'])) {
                    echo "<img src='data:image/jpeg;base64," . base64_encode($plato['imagen']) . "' alt='" . htmlspecialchars($plato['nombre']) . "'>";
                } else {
                    echo "<img src='../../public/assets/img/Imagenes/DALL·E 2024-11-06 22.01.47 - A high-quality image of a gourmet restaurant dish with elegant plating, suitable for use in a restaurant website. The dish should be visually appealin.webp' alt='Imagen no disponible'>";
                }
                
                echo "<h4>" . htmlspecialchars($plato['nombre']) . "</h4>";
                echo "<p>" . htmlspecialchars($plato['descripcion']) . "</p>";
                echo "<span class='precio'>$" . number_format($plato['precio'], 2) . "</span>";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<p style='text-align: center; color: #7f8c8d; margin: 20px 0;'>No hay platos disponibles en esta categoría.</p>";
        }
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error al cargar el menú completo: " . htmlspecialchars($e->getMessage()) . "</p>";
}

$conn->close();
?> 