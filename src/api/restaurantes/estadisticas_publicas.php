<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../../config/conexion.php';

try {
    // Contar restaurantes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM restaurantes");
    $stmt->execute();
    $totalRestaurantes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contar reservas (si existe la tabla)
    $totalReservas = 0;
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'reservas'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reservas");
        $stmt->execute();
        $totalReservas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    // Contar clientes registrados (usar usuarios_cliente)
    $clientesRegistrados = 0;
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'usuarios_cliente'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios_cliente");
        $stmt->execute();
        $clientesRegistrados = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    echo json_encode([
        'success' => true,
        'total_restaurantes' => $totalRestaurantes,
        'total_reservas' => $totalReservas,
        'clientes_registrados' => $clientesRegistrados
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'total_restaurantes' => 0,
        'total_reservas' => 0,
        'clientes_registrados' => 0
    ]);
}
?> 