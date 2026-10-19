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
    
    // Obtener platos especiales para la sección de reservas (máximo 6)
    $stmt = $conn->prepare("
        SELECT nombre, descripcion, precio, imagen 
        FROM platosmenu 
        WHERE id_restaurante = ?
        ORDER BY RAND() 
        LIMIT 6
    ");
    $stmt->bind_param("i", $restaurante_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $platos_especiales = $resultado->fetch_all(MYSQLI_ASSOC);
    
    if (empty($platos_especiales)) {
        echo "<div class='platos-especiales'><p>No hay platos especiales disponibles en este momento.</p></div>";
        exit;
    }
    
    echo "<div class='platos-especiales'>";
    echo "<h3>Platos Especiales para Tu Reserva</h3>";
    echo "<p class='descripcion-especiales'>Descubre nuestros platos más destacados que puedes disfrutar durante tu visita</p>";
    echo "<div class='galeria-platos-especiales'>";
    
    foreach ($platos_especiales as $plato) {
        echo "<div class='plato-especial-carta'>";
        
        if (!empty($plato['imagen'])) {
            echo "<img src='data:image/jpeg;base64," . base64_encode($plato['imagen']) . "' alt='" . htmlspecialchars($plato['nombre']) . "'>";
        } else {
            echo "<img src='{$projectUrl}/public/assets/img/Imagenes/DALL·E 2024-11-06 22.01.47 - A high-quality image of a gourmet restaurant dish with elegant plating, suitable for use in a restaurant website. The dish should be visually appealin.webp' alt='Imagen no disponible'>";
        }
        
        echo "<div class='plato-especial-info'>";
        echo "<h4>" . htmlspecialchars($plato['nombre']) . "</h4>";
        echo "<p class='plato-descripcion'>" . htmlspecialchars($plato['descripcion']) . "</p>";
        echo "<div class='plato-precio-accion'>";
        echo "<span class='precio-especial'>$" . number_format($plato['precio'], 2) . "</span>";
        echo "<button class='btn-reservar-plato' onclick='
          console.log(\"🍽️ Haciendo scroll hacia reservas para plato: " . htmlspecialchars($plato['nombre']) . "\");
          const seccionReservas = document.getElementById(\"reservas\");
          if (seccionReservas) {
            seccionReservas.scrollIntoView({ 
              behavior: \"smooth\",
              block: \"start\"
            });
            setTimeout(() => {
              seccionReservas.style.transition = \"all 0.3s ease\";
              seccionReservas.style.transform = \"scale(1.02)\";
              setTimeout(() => {
                seccionReservas.style.transform = \"scale(1)\";
              }, 300);
            }, 500);
          } else {
            console.error(\"Sección de reservas no encontrada\");
          }
        '>¡RESERVAR MESA!</button>";
        echo "</div>";
        echo "</div>";
        echo "<div class='plato-especial-badge'>Especial</div>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error al cargar los platos especiales: " . htmlspecialchars($e->getMessage()) . "</p>";
}

$conn->close();
?> 