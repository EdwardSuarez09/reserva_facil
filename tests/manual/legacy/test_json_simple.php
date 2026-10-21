<?php
// Test muy simple para JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

// Limpiar cualquier salida previa
if (ob_get_level()) {
    ob_end_clean();
}

$response = [
    'success' => true,
    'message' => 'Conexión exitosa',
    'timestamp' => date('Y-m-d H:i:s'),
    'params' => $_GET
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?> 