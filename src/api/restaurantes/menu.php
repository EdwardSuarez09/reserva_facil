<?php
include __DIR__ . '/../../config/conexion.php';

$restaurante = $_GET['restaurante'] ?? '';

if (empty($restaurante)) {
    echo "<p style='color:red;'>Restaurante no especificado.</p>";
    exit;
}

try {
    $projectUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'], 4), '/');
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
    
    echo "<div class='menu-por-categorias'>";
    
    // PLATOS DESTACADOS - Criterio: Los más caros (especialidades del restaurante)
    echo "<h3 class='nombre-categoria-destacada'>Nuestras Especialidades</h3>";
    echo "<p class='descripcion-destacados'>Los platos más exclusivos de nuestra cocina</p>";
    
    $stmt = $conn->prepare("
        SELECT nombre, descripcion, precio, imagen 
        FROM platosmenu 
        WHERE id_restaurante = ? AND imagen IS NOT NULL
        ORDER BY precio DESC 
        LIMIT 8
    ");
    $stmt->bind_param("i", $restaurante_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $platos_destacados = $resultado->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($platos_destacados)) {
        echo "<div class='carrusel carrusel-destacados'>";
        foreach ($platos_destacados as $plato) {
            echo "<div class='plato-carta plato-destacado plato-clickable' data-nombre='" . htmlspecialchars($plato['nombre']) . "' onclick=\"irAMenuCompleto()\">";
            
            if (!empty($plato['imagen'])) {
                echo "<img src='data:image/jpeg;base64," . base64_encode($plato['imagen']) . "' alt='" . htmlspecialchars($plato['nombre']) . "'>";
            } else {
                echo "<img src='{$projectUrl}/public/assets/img/Imagenes/DALL·E 2024-11-06 22.01.47 - A high-quality image of a gourmet restaurant dish with elegant plating, suitable for use in a restaurant website. The dish should be visually appealin.webp' alt='Imagen no disponible'>";
            }
            
            echo "<h4>" . htmlspecialchars($plato['nombre']) . "</h4>";
            echo "<p>" . htmlspecialchars($plato['descripcion']) . "</p>";
            echo "<span class='precio'>$" . number_format($plato['precio'], 2) . "</span>";
            echo "<div class='plato-destacado-badge'>Especialidad</div>";
            echo "<div class='click-overlay'>";
            echo "<div class='click-content'>";
            echo "<span class='click-text'>Ver Menú Completo</span>";
            echo "<div class='click-subtitle'>Click aquí</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p>No hay especialidades disponibles para este restaurante.</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error al cargar el menú: " . htmlspecialchars($e->getMessage()) . "</p>";
}

$conn->close();
?> 