<?php
include_once __DIR__ . '/../../config/conexion.php';
include_once __DIR__ . '/../../helpers/rutas_assets.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM restaurantes ORDER BY nombre");
    $stmt->execute();
    $restaurantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $projectUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'], 4), '/');
    $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    
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
            
            $descripcion = !empty($restaurante['descripcion'])
                ? $restaurante['descripcion']
                : 'Una experiencia gastronómica para descubrir.';
            $tipoCocina = !empty($restaurante['tipo_cocina'])
                ? $restaurante['tipo_cocina']
                : 'Cocina variada';
            $telefono = trim((string) ($restaurante['telefono'] ?? ''));
            $direccion = trim((string) ($restaurante['direccion'] ?? ''));
            $nombre = (string) ($restaurante['nombre'] ?? '');
            $detalleUrl = $projectUrl . '/public/html/restaurante.html?restaurante=' . rawurlencode($nombre);

            echo "<a class='restaurante-card' data-id='" . (int) $restaurante['id'] . "' data-tipo='" . $escape($tipoCocina) . "' href='" . $escape($detalleUrl) . "'>
                    <div class='restaurante-image-container'>
                        <img class='restaurante-image' src='" . $escape($imagen) . "' alt='" . $escape($nombre) . "' loading='lazy'>
                    </div>
                    <div class='restaurante-info'>
                        <span class='restaurante-tipo'>" . $escape($tipoCocina) . "</span>
                        <h3 class='restaurante-nombre'>" . $escape($nombre) . "</h3>
                        <p class='restaurante-descripcion'>" . $escape($descripcion) . "</p>
                        <div class='restaurante-detalles'>";

            if ($direccion !== '') {
                echo "<div class='detalle-item'><span class='detalle-etiqueta'>Dirección</span><span class='detalle-texto'>" . $escape($direccion) . "</span></div>";
            }

            if ($telefono !== '') {
                echo "<div class='detalle-item'><span class='detalle-etiqueta'>Teléfono</span><span class='detalle-texto'>" . $escape($telefono) . "</span></div>";
            }

            echo "      </div>
                        <span class='btn-ver-restaurante'>Conocer restaurante</span>
                    </div>
                  </a>";
        }
    } else {
        echo "<p style='text-align: center; color: #666; font-size: 18px; margin: 40px 0;'>No hay restaurantes disponibles en este momento.</p>";
    }
} catch (Exception $e) {
    echo "<p style='text-align: center; color: #e74c3c; font-size: 18px; margin: 40px 0;'>Error al cargar restaurantes: " . $e->getMessage() . "</p>";
}
?> 