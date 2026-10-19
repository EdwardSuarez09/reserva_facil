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
    
    echo "<div class='formulario-reserva'>";
    echo "<h3>Reservar Mesa</h3>";
    echo "<form id='form-reserva' method='POST'>";
    
    echo "<div class='campo'>";
    echo "<label for='nombre_cliente'>Nombre completo:</label>";
    echo "<input type='text' id='nombre_cliente' name='nombre_cliente' required>";
    echo "</div>";
    
    echo "<div class='campo'>";
    echo "<label for='telefono_cliente'>Teléfono:</label>";
    echo "<input type='tel' id='telefono_cliente' name='telefono_cliente' required>";
    echo "</div>";
    
    echo "<div class='campo'>";
    echo "<label for='email_cliente'>Email:</label>";
    echo "<input type='email' id='email_cliente' name='email_cliente' required>";
    echo "</div>";
    
    echo "<div class='campo'>";
    echo "<label for='fecha_reserva'>Fecha de reserva:</label>";
    echo "<input type='date' id='fecha_reserva' name='fecha_reserva' required min='" . date('Y-m-d') . "'>";
    echo "</div>";
    
    echo "<div class='campo'>";
    echo "<label for='hora_reserva'>Hora de reserva:</label>";
    echo "<input type='time' id='hora_reserva' name='hora_reserva' required>";
    echo "</div>";
    
    echo "<div class='campo'>";
    echo "<label for='numero_personas'>Número de personas:</label>";
    echo "<select id='numero_personas' name='numero_personas' required>";
    echo "<option value=''>Seleccione...</option>";
    for ($i = 1; $i <= 12; $i++) {
        echo "<option value='$i'>$i persona" . ($i > 1 ? 's' : '') . "</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div class='campo'>";
    echo "<label for='comentarios'>Comentarios adicionales:</label>";
    echo "<textarea id='comentarios' name='comentarios' rows='3' placeholder='Ocasión especial, alergias, etc.'></textarea>";
    echo "</div>";
    
    echo "<input type='hidden' name='restaurante_id' value='$restaurante_id'>";
    echo "<button type='submit' class='btn'>Reservar Mesa</button>";
    echo "</form>";
    echo "</div>";
    
    // Script para manejar el envío del formulario
    echo "<script>";
    echo "document.getElementById('form-reserva').addEventListener('submit', function(e) {";
    echo "  e.preventDefault();";
    echo "  alert('¡Reserva enviada! Te contactaremos pronto para confirmar.');";
    echo "  this.reset();";
    echo "});";
    echo "</script>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error al cargar el formulario: " . htmlspecialchars($e->getMessage()) . "</p>";
}

$conn->close();
?> 