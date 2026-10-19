<?php
// cargar_footer_restaurante.php - Genera el HTML del footer con datos del restaurante
include __DIR__ . '/../../config/conexion.php';

$nombreRestaurante = $_GET['restaurante'] ?? '';
if (!$nombreRestaurante) {
    echo "<p style='color:red;'>Nombre de restaurante no especificado.</p>";
    exit;
}

$sql = "SELECT nombre, direccion, horario, telefono, correo, web, redes_sociales FROM restaurantes WHERE nombre = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nombreRestaurante);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    
    // Construir contacto
    $contacto = '';
    if (!empty($row['telefono']) && !empty($row['correo'])) {
        $contacto = $row['telefono'] . ' | ' . $row['correo'];
    } elseif (!empty($row['telefono'])) {
        $contacto = $row['telefono'];
    } elseif (!empty($row['correo'])) {
        $contacto = $row['correo'];
    } else {
        $contacto = 'No disponible';
    }
    
    // Construir web
    $web = '';
    if (!empty($row['web'])) {
        $web = '<a href="' . htmlspecialchars($row['web']) . '" target="_blank">' . htmlspecialchars($row['web']) . '</a>';
    } else {
        $web = 'No disponible';
    }
    
    // Construir redes sociales
    $redes = !empty($row['redes_sociales']) ? htmlspecialchars($row['redes_sociales']) : 'No disponible';
    
    // Generar HTML del footer
    echo '<div class="footer-descripcion">El mejor ambiente gastronómico</div>
          <div class="footer-info-detalle">
           <div class="footer-direccion" id="footer-direccion"><strong>Dirección:</strong> ' . htmlspecialchars($row['direccion']) . '</div>
           <div class="footer-horario" id="footer-horario"><strong>Horario:</strong> ' . htmlspecialchars($row['horario']) . '</div>
           <div class="footer-telefonos" id="footer-contacto"><strong>Contacto:</strong> ' . htmlspecialchars($contacto) . '</div>
           <div class="footer-web" id="footer-web"><strong>Web:</strong> ' . $web . '</div>
          </div>';
    
    // También generar la parte de redes sociales
    echo '<script>
    setTimeout(() => {
        const footerRedesValue = document.getElementById("footer-redes-value");
        if (footerRedesValue) {
            footerRedesValue.textContent = "' . addslashes($redes) . '";
            console.log("✅ Footer cargado directamente desde PHP con datos de ' . addslashes($row['nombre']) . '");
        }
    }, 100);
    </script>';

} else {
    echo '<div class="footer-descripcion">Restaurante no encontrado</div>
          <div class="footer-info-detalle">
           <div class="footer-direccion" id="footer-direccion"><strong>Dirección:</strong> No disponible</div>
           <div class="footer-horario" id="footer-horario"><strong>Horario:</strong> No disponible</div>
           <div class="footer-telefonos" id="footer-contacto"><strong>Contacto:</strong> No disponible</div>
           <div class="footer-web" id="footer-web"><strong>Web:</strong> No disponible</div>
          </div>';
}

$stmt->close();
$conn->close();
?> 