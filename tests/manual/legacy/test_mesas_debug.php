<?php
echo "<h2>Test Debug - Obtener Mesas</h2>";

// Test directo con los mismos parámetros
$restaurante = 'Calipollo';
$fecha = date('Y-m-d', strtotime('+1 day')); // Mañana
$hora = '20:00';
$personas = '2';

$url = "../../../src/api/mesas/disponibilidad.php?restaurante=" . urlencode($restaurante) .
       "&fecha=" . $fecha . "&hora=" . $hora . "&personas=" . $personas;

echo "<p><strong>URL de prueba:</strong> $url</p>";

// Simular $_GET
$_GET['restaurante'] = $restaurante;
$_GET['fecha'] = $fecha;
$_GET['hora'] = $hora;
$_GET['personas'] = $personas;

echo "<h3>Parámetros:</h3>";
echo "<ul>";
echo "<li>Restaurante: $restaurante</li>";
echo "<li>Fecha: $fecha</li>";
echo "<li>Hora: $hora</li>";
echo "<li>Personas: $personas</li>";
echo "</ul>";

echo "<h3>Resultado del PHP:</h3>";
echo "<pre>";

// Capturar la salida
ob_start();
try {
    include __DIR__ . '/../../../src/api/mesas/disponibilidad.php';
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
$output = ob_get_clean();

echo htmlspecialchars($output);
echo "</pre>";

echo "<h3>JSON parseado:</h3>";
$data = json_decode($output, true);
if ($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>ERROR: No se pudo parsear el JSON</p>";
    echo "<p>Error JSON: " . json_last_error_msg() . "</p>";
}
?> 