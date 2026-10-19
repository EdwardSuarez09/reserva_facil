<?php
include_once __DIR__ . '/../../config/conexion.php';
include_once __DIR__ . '/../../helpers/rutas_assets.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM restaurantes ORDER BY nombre");
    $stmt->execute();
    $restaurantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $projectUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'], 4), '/');
    
    if (count($restaurantes) > 0) {
        foreach ($restaurantes as $restaurante) {
            // Manejar imagen/logo del restaurante
            $imagen = $projectUrl . '/public/assets/img/Imagenes/DALL·E 2024-11-06 21.54.50 - An elegant logo for a restaurant, featuring a stylized fork and knife crossed over a plate with subtle decorative elements. The background should be s.webp';
            
            if (!empty($restaurante['imagen_path'])) {
                $imagePath = obtenerRutaImagenArchivo($restaurante['imagen_path']);
                if (file_exists($imagePath)) {
                    $imagen = obtenerUrlImagenPublica($restaurante['imagen_path']);
                }
            } 
            // Fallback para imágenes antiguas en BLOB
            elseif (!empty($restaurante['imagen'])) {
                // Si es una URL válida, usarla directamente
                if (filter_var($restaurante['imagen'], FILTER_VALIDATE_URL)) {
                    $imagen = $restaurante['imagen'];
                }
                // Si es una ruta de archivo que existe
                elseif (file_exists(obtenerRutaImagenArchivo($restaurante['imagen']))) {
                    $imagen = obtenerUrlImagenPublica($restaurante['imagen']);
                }
                // Si es data base64 almacenada
                elseif (strlen($restaurante['imagen']) > 100) {
                    $imagen = 'data:image/jpeg;base64,' . base64_encode($restaurante['imagen']);
                }
                // Si es solo el nombre del archivo
                else {
                    $possible_path = 'public/assets/img/Imagenes/' . $restaurante['imagen'];
                    if (file_exists(obtenerRutaImagenArchivo($possible_path))) {
                        $imagen = obtenerUrlImagenPublica($possible_path);
                    }
                }
            }
            
            $descripcion = !empty($restaurante['descripcion']) ? $restaurante['descripcion'] : 'Deliciosa experiencia gastronómica te espera en este establecimiento único.';
            $tipo_cocina = !empty($restaurante['tipo_cocina']) ? $restaurante['tipo_cocina'] : 'Cocina Variada';
            $telefono = !empty($restaurante['telefono']) ? $restaurante['telefono'] : '';
            $direccion = !empty($restaurante['direccion']) ? $restaurante['direccion'] : '';
            
            echo "<div class='restaurant-card' data-id='{$restaurante['id']}' onclick='seleccionarRestaurante({$restaurante['id']}, \"{$restaurante['nombre']}\")'>
                    <div class='restaurant-image'>
                        <img src='{$imagen}' alt='{$restaurante['nombre']}'>
                    </div>
                    <div class='restaurant-info'>
                        <h3>{$restaurante['nombre']}</h3>
                        <p class='restaurant-cuisine'>{$tipo_cocina}</p>
                        <p class='restaurant-description'>{$descripcion}</p>";
            
            if ($direccion) {
                echo "<p class='restaurant-address'>📍 {$direccion}</p>";
            }
            
            if ($telefono) {
                echo "<p class='restaurant-phone'>📞 {$telefono}</p>";
            }
            
            echo "  </div>
                  </div>";
        }
    } else {
        echo "<p style='text-align: center; color: #666; font-size: 18px; margin: 40px 0;'>No hay restaurantes disponibles en este momento.</p>";
    }
} catch (Exception $e) {
    echo "<p style='text-align: center; color: #e74c3c; font-size: 18px; margin: 40px 0;'>Error al cargar restaurantes: " . $e->getMessage() . "</p>";
}
?> 