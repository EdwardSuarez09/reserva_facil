<?php
include __DIR__ . '/../../config/conexion.php';

$nombreRestaurante = $_GET['restaurante'] ?? '';
if (!$nombreRestaurante) {
    echo "<p style='color:red;'>Nombre de restaurante no especificado.</p>";
    exit;
}

$sql = "SELECT nombre, direccion, imagen, descripcion, horario, telefono, correo, web, redes_sociales FROM restaurantes WHERE nombre = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nombreRestaurante);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    $projectUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'], 4), '/');
    
    // Imagen más grande para el hero
    $img = $row['imagen']
        ? "<img src='data:image/jpeg;base64," . base64_encode($row['imagen']) . "' alt='{$row['nombre']}' class='hero-imagen'>"
        : "<img src='{$projectUrl}/public/assets/img/Imagenes/DALL·E 2024-11-06 21.54.50 - An elegant logo for a restaurant, featuring a stylized fork and knife crossed over a plate with subtle decorative elements. The background should be s.webp' alt='Sin imagen' class='hero-imagen'>";

    echo "<div class='hero-restaurante'>
            <div class='hero-contenido'>
              $img
              <div class='hero-texto'>
                <h1 class='hero-titulo'>{$row['nombre']}</h1>
                <p class='hero-slogan'>El mejor ambiente</p>
                <p class='hero-descripcion'>{$row['descripcion']}</p>
                <div class='hero-acciones'>
                  <button class='btn-hero btn-reservar' onclick='
                    console.log(\"Haciendo scroll hacia sección de reservas...\");
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
                  '>Hacer Reserva</button>
                  <a href='#menu' class='btn-hero btn-menu'>Ver Menú</a>
                </div>
              </div>
            </div>
            <div class='hero-decoracion'>
              <div class='decoracion-elemento decoracion-1'></div>
              <div class='decoracion-elemento decoracion-2'></div>
              <div class='decoracion-elemento decoracion-3'></div>
            </div>
          </div>";

    // JSON para uso en JS (como window.restauranteInfo)
    echo "<script>
      window.restauranteInfo = " . json_encode([
        "nombre" => $row['nombre'],
        "direccion" => $row['direccion'],
        "descripcion" => $row['descripcion'],
        "horario" => $row['horario'],
        "telefono" => $row['telefono'],
        "correo" => $row['correo'],
        "web" => $row['web'],
        "redes" => $row['redes_sociales']
      ]) . ";
      
      console.log('📡 Datos del restaurante cargados:', window.restauranteInfo);
      
      // Actualizar footer inmediatamente usando DOM
      setTimeout(() => {
        console.log('🔄 Actualizando footer directamente...');
        
        // Actualizar dirección
        const elemDireccion = document.getElementById('footer-direccion');
        if (elemDireccion) {
          elemDireccion.innerHTML = '<strong>Dirección:</strong> " . addslashes($row['direccion']) . "';
          console.log('✅ Dirección actualizada');
        }
        
        // Actualizar horario
        const elemHorario = document.getElementById('footer-horario');
        if (elemHorario) {
          elemHorario.innerHTML = '<strong>Horario:</strong> " . addslashes($row['horario']) . "';
          console.log('✅ Horario actualizado');
        }
        
        // Actualizar contacto
        const elemContacto = document.getElementById('footer-contacto');
        if (elemContacto) {
          let contacto = '<strong>Contacto:</strong> ';";
    
    // Construir contacto en PHP
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
    
    echo "          contacto += '" . addslashes($contacto) . "';
          elemContacto.innerHTML = contacto;
          console.log('✅ Contacto actualizado');
        }
        
        // Actualizar web
        const elemWeb = document.getElementById('footer-web');
        if (elemWeb) {";
    
    if (!empty($row['web'])) {
        echo "          elemWeb.innerHTML = '<strong>Web:</strong> <a href=\"" . addslashes($row['web']) . "\" target=\"_blank\">" . addslashes($row['web']) . "</a>';
          console.log('✅ Web actualizada');";
    } else {
        echo "          elemWeb.innerHTML = '<strong>Web:</strong> No disponible';
          console.log('⚠️ Web no disponible');";
    }
    
    echo "        }
        
        // Actualizar redes sociales
        const elemRedes = document.getElementById('footer-redes-value');
        if (elemRedes) {";
    
    if (!empty($row['redes_sociales'])) {
        echo "          elemRedes.textContent = '" . addslashes($row['redes_sociales']) . "';
          console.log('✅ Redes actualizadas');";
    } else {
        echo "          elemRedes.textContent = 'No disponible';
          console.log('⚠️ Redes no disponibles');";
    }
    
    echo "        }
        
        console.log('🎉 Footer actualizado directamente desde PHP!');
      }, 100);
    </script>";

} else {
    echo "<p style='color:red;'>Restaurante no encontrado.</p>";
}

$stmt->close();
$conn->close();
