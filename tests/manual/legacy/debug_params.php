<?php
// Archivo para capturar y debuggear los parámetros que llegan al servidor
include __DIR__ . '/../../../src/config/conexion.php';

// Capturar todos los parámetros
$allParams = [
    'GET' => $_GET,
    'POST' => $_POST,
    'REQUEST' => $_REQUEST,
    'SERVER_REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'No disponible'
];

// Guardar en archivo de log
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'params' => $allParams
];

file_put_contents('debug_params.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// También mostrar en pantalla si se llama directamente
if (php_sapi_name() === 'cli') {
    echo "=== DEBUG PARÁMETROS ===\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    echo "GET: " . json_encode($_GET, JSON_PRETTY_PRINT) . "\n";
    echo "POST: " . json_encode($_POST, JSON_PRETTY_PRINT) . "\n";
}

// Verificar restaurantes disponibles
try {
    $result = $pdo->query("SELECT id, nombre FROM restaurantes");
    $restaurantes = $result->fetchAll();
    
    $logData['restaurantes_disponibles'] = $restaurantes;
    
    if (php_sapi_name() === 'cli') {
        echo "\nRestaurantes disponibles:\n";
        foreach ($restaurantes as $rest) {
            echo "  - ID: {$rest['id']}, Nombre: '{$rest['nombre']}'\n";
        }
    }
} catch (Exception $e) {
    $logData['error_db'] = $e->getMessage();
}

// Actualizar log con info de restaurantes
file_put_contents('debug_params.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n" . str_repeat("=", 50) . "\n\n", FILE_APPEND);

echo "Debug info guardada en debug_params.log";
?> 