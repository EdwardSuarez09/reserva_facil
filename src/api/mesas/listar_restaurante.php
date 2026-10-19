<?php
include __DIR__ . '/../../config/conexion.php';

$restaurante = $_GET['restaurante'] ?? '';

if (empty($restaurante)) {
    echo "<p style='color:red;'>Restaurante no especificado.</p>";
    exit;
}

try {

  $projectUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'], 4), '/');
  $stmt_rest = $conn->prepare("SELECT id FROM restaurantes WHERE nombre = ?");
    $stmt_rest->bind_param("s", $restaurante);
    $stmt_rest->execute();
    $resultado_rest = $stmt_rest->get_result();
    $restaurante_data = $resultado_rest->fetch_assoc();
    
    if (!$restaurante_data) {
        echo "<div class='mesas-disponibles'><p style='color:red;'>Restaurante no encontrado.</p></div>";
        exit;
    }
    
    $restaurante_id = $restaurante_data['id'];
    
    $check_campo = $conn->query("SHOW COLUMNS FROM mesas LIKE 'id_restaurante'");
    $tiene_id_restaurante = ($check_campo && $check_campo->num_rows > 0);
    
    if ($tiene_id_restaurante) {

      $stmt = $conn->prepare("SELECT numero_mesa, capacidad, imagen FROM mesas WHERE id_restaurante = ? ORDER BY numero_mesa LIMIT 6");
        $stmt->bind_param("i", $restaurante_id);
    } else {

      $stmt = $conn->prepare("SELECT numero_mesa, capacidad, imagen FROM mesas ORDER BY numero_mesa LIMIT 6");
    }
    
    $stmt->execute();
    $resultado = $stmt->get_result();
    $mesas = $resultado->fetch_all(MYSQLI_ASSOC);
    
    if (empty($mesas)) {
        if ($tiene_id_restaurante) {
            echo "<div class='mesas-disponibles'><p>Este restaurante aún no tiene mesas configuradas.</p></div>";
        } else {
            echo "<div class='mesas-disponibles'><p>No hay mesas disponibles en este momento.</p></div>";
        }
        exit;
    }
    
    echo "<div class='mesas-disponibles'>";
    echo "<h3>Nuestras Mesas Disponibles</h3>";
    echo "<div class='galeria-mesas'>";
    
    foreach ($mesas as $mesa) {
        echo "<div class='mesa-carta'>";
        
        if (!empty($mesa['imagen'])) {
            echo "<img src='data:image/jpeg;base64," . base64_encode($mesa['imagen']) . "' alt='Mesa " . htmlspecialchars($mesa['numero_mesa']) . "'>";
        } else {
            echo "<img src='{$projectUrl}/public/assets/img/Imagenes/imagen 2.jpg' alt='Mesa sin imagen'>";
        }
        
        echo "<div class='mesa-info'>";
        echo "<h4>Mesa " . htmlspecialchars($mesa['numero_mesa']) . "</h4>";
        echo "<p><strong>Capacidad:</strong> " . htmlspecialchars($mesa['capacidad']) . " personas</p>";
        echo "<button class='btn-reservar-mesa' onclick='
          console.log(\" Haciendo scroll hacia reservas para Mesa " . htmlspecialchars($mesa['numero_mesa']) . "\");
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
        '>Reservar</button>";
        echo "</div>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error al cargar las mesas: " . htmlspecialchars($e->getMessage()) . "</p>";
}

$conn->close();
?> 