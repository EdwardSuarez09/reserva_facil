<?php
// Simular parámetros GET como los enviaría el formulario web
$_GET['restaurante'] = 'Calipollo';
$_GET['fecha'] = date('Y-m-d', strtotime('+1 day'));
$_GET['hora'] = '18:00';
$_GET['personas'] = '2';

echo "Probando búsqueda de mesas disponibles para el formulario web...\n";
echo "Parámetros: " . json_encode($_GET) . "\n\n";

// Incluir el archivo real
include __DIR__ . '/../src/api/mesas/disponibilidad_clean.php';
?> 