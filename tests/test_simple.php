<?php
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Conexión funcionando',
    'timestamp' => date('Y-m-d H:i:s'),
    'params_get' => $_GET,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'No disponible'
]);
?> 