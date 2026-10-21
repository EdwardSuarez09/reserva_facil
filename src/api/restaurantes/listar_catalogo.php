<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
include __DIR__ . '/../../helpers/rutas_assets.php';

$sql = "SELECT id, nombre, direccion, imagen_path, descripcion, horario FROM restaurantes ORDER BY id DESC";
$res = $conn->query($sql);

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $nombre = (string) ($row['nombre'] ?? '');
        $nombreEncoded = rawurlencode($nombre);
        $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $direccion = trim((string) ($row['direccion'] ?? ''));
        $horario = trim((string) ($row['horario'] ?? ''));
        $horario = preg_replace('/\s*[-–]\s*/u', ' – ', $horario) ?? $horario;
        $descripcion = trim((string) ($row['descripcion'] ?? ''));
        $descripcionesMejoradas = [
            'el mejor ambiente' => 'Un ambiente acogedor para compartir buenos momentos.',
            'el mejor pollo' => 'Pollo preparado con el sabor que nos distingue.',
        ];
        $descripcion = $descripcionesMejoradas[strtolower($descripcion)]
            ?? ($descripcion !== '' ? $descripcion : 'Conoce la propuesta gastronómica de este restaurante.');
        
        // Procesar imagen
        $imagePath = !empty($row['imagen_path'])
            ? obtenerRutaImagenArchivo($row['imagen_path'])
            : '';
        if (!empty($row['imagen_path']) && file_exists($imagePath)) {
            $imageUrl = '../' . normalizarRutaImagenPublica($row['imagen_path']);
            $img = "<img src='" . $escape($imageUrl) . "' alt='" . $escape($nombre) . "' style='width:100%; height:200px; object-fit:cover; border-radius:10px;'>";
        } else {
            $img = "<img src='../public/assets/img/Imagenes/DALL·E 2024-11-06 22.01.47 - A high-quality image of a gourmet restaurant dish with elegant plating, suitable for use in a restaurant website. The dish should be visually appealin.webp' alt='Imagen del restaurante' style='width:100%; height:200px; object-fit:cover; border-radius:10px;'>";
        }

        $projectUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'], 4), '/');
        echo "<a href='" . $escape($projectUrl . "/src/api/restaurantes/obtener_vista.php?restaurante={$nombreEncoded}") . "' style='text-decoration: none; color: inherit;'>
                <div class='restaurant-card' style='border: 1px solid #ddd; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin: 16px; transition: transform 0.3s ease;'>
                    $img
                    <div class='restaurant-info' style='padding: 16px; background-color: white;'>
                        <h3 style='margin: 0 0 8px; color: #333;'>" . $escape($nombre) . "</h3>
                        <div class='restaurant-card-details'>
                            <p class='restaurant-card-detail'><span class='restaurant-card-label'>Ubicación</span><span class='restaurant-card-value'>" . $escape($direccion !== '' ? $direccion : 'No especificada') . "</span></p>
                            <p class='restaurant-card-detail'><span class='restaurant-card-label'>Horario de atención</span><span class='restaurant-card-value'>" . $escape($horario !== '' ? $horario : 'Consultar con el restaurante') . "</span></p>
                        </div>
                        <p class='restaurant-card-description'>" . $escape($descripcion) . "</p>
                    </div>
                </div>
              </a>";
    }
} else {
    echo "<p>No hay restaurantes disponibles.</p>";
}

$conn->close();
