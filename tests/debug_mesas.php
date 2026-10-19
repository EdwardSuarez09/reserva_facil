<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $host = '127.0.0.1';
    $port = 3307;
    $dbname = 'restaurantes_db';
    $username = 'root';
    $password = '';
    
    $conn = new mysqli($host, $username, $password, $dbname, $port);
    
    if ($conn->connect_error) {
        echo json_encode(['error' => 'Conexión fallida: ' . $conn->connect_error]);
        exit;
    }
    
    $conn->set_charset("utf8");
    
    // Test query
    $result = $conn->query("SELECT COUNT(*) as count FROM restaurantes");
    if (!$result) {
        echo json_encode(['error' => 'Error en consulta: ' . $conn->error]);
        exit;
    }
    
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa',
        'restaurantes_count' => $row['count'],
        'params' => $_GET
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Excepción: ' . $e->getMessage()]);
}
?> 