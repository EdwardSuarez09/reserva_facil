<?php
// Test directo sin JavaScript
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$url_params = "?restaurante=Calipollo&fecha=$tomorrow&hora=20:00&personas=2";

echo "<h2>Test Directo de Mesas</h2>";
echo "<p><strong>URL que se probará:</strong> ../../../src/api/mesas/disponibilidad.php$url_params</p>";

// Simular $_GET
$_GET['restaurante'] = 'Calipollo';
$_GET['fecha'] = $tomorrow;
$_GET['hora'] = '20:00';
$_GET['personas'] = '2';

echo "<h3>Parámetros:</h3>";
echo "<ul>";
echo "<li>Restaurante: " . $_GET['restaurante'] . "</li>";
echo "<li>Fecha: " . $_GET['fecha'] . "</li>";
echo "<li>Hora: " . $_GET['hora'] . "</li>";
echo "<li>Personas: " . $_GET['personas'] . "</li>";
echo "</ul>";

echo "<h3>Resultado:</h3>";
echo "<pre>";

// Capturar la salida del script
ob_start();
include __DIR__ . '/../../../src/api/mesas/disponibilidad.php';
$output = ob_get_clean();

echo htmlspecialchars($output);
echo "</pre>";

// Mostrar el archivo de debug si existe
if (file_exists('mesas_debug.log')) {
    echo "<h3>Debug Log:</h3>";
    echo "<pre>";
    echo htmlspecialchars(file_get_contents('mesas_debug.log'));
    echo "</pre>";
} else {
    echo "<p><em>No se creó archivo de debug.</em></p>";
}
?> 