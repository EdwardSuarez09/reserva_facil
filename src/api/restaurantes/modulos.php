<?php
require __DIR__ . '/../../config/conexion.php';

$nombreRestaurante = trim($_GET['restaurante'] ?? '');
if ($nombreRestaurante === '') {
    http_response_code(400);
    exit('<p>Nombre de restaurante no especificado.</p>');
}

$stmt = $conn->prepare(
    'SELECT nombre, direccion, imagen, descripcion, horario, telefono, correo,
            web, redes_sociales
     FROM restaurantes
     WHERE nombre = ?'
);
if (!$stmt) {
    http_response_code(500);
    exit('<p>No se pudo cargar el restaurante.</p>');
}
$stmt->bind_param('s', $nombreRestaurante);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    exit('<p>No se pudo cargar el restaurante.</p>');
}
$restaurante = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$restaurante) {
    http_response_code(404);
    exit('<p>Restaurante no encontrado.</p>');
}

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$projectUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'], 4), '/');
$fallbackImage = $projectUrl
    . '/public/assets/img/Imagenes/DALL·E 2024-11-06 21.54.50 - An elegant logo for a restaurant, featuring a stylized fork and knife crossed over a plate with subtle decorative elements. The background should be s.webp';
$image = !empty($restaurante['imagen'])
    ? 'data:image/jpeg;base64,' . base64_encode($restaurante['imagen'])
    : $fallbackImage;
$restauranteJson = json_encode(
    [
        'nombre' => $restaurante['nombre'],
        'direccion' => $restaurante['direccion'],
        'descripcion' => $restaurante['descripcion'],
        'horario' => $restaurante['horario'],
        'telefono' => $restaurante['telefono'],
        'correo' => $restaurante['correo'],
        'web' => $restaurante['web'],
        'redes' => $restaurante['redes_sociales'],
    ],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
);
?>
<div class="hero-restaurante">
    <div class="hero-contenido">
        <img src="<?= $escape($image) ?>" alt="<?= $escape($restaurante['nombre']) ?>" class="hero-imagen">
        <div class="hero-texto">
            <h1 class="hero-titulo"><?= $escape($restaurante['nombre']) ?></h1>
            <p class="hero-slogan">El mejor ambiente</p>
            <p class="hero-descripcion"><?= $escape($restaurante['descripcion']) ?></p>
            <div class="hero-acciones">
                <button class="btn-hero btn-reservar" type="button"
                    onclick="document.getElementById('reservas')?.scrollIntoView({behavior: 'smooth', block: 'start'})">
                    Hacer Reserva
                </button>
                <a href="#menu" class="btn-hero btn-menu">Ver Menú</a>
            </div>
        </div>
    </div>
    <div class="hero-decoracion">
        <div class="decoracion-elemento decoracion-1"></div>
        <div class="decoracion-elemento decoracion-2"></div>
        <div class="decoracion-elemento decoracion-3"></div>
    </div>
</div>
<script type="application/json" id="restaurante-info-data"><?= $restauranteJson ?: '{}' ?></script>
