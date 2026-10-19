<?php
include __DIR__ . '/../../config/conexion.php';

try {
    $sql = "SELECT r.*, 
                   COALESCE(r.nombre_cliente, 'Cliente sin nombre') as cliente_nombre,
                   COALESCE(r.telefono_cliente, '') as cliente_telefono,
                   COALESCE(r.email_cliente, '') as cliente_email,
                   COALESCE(r.estado, 'pendiente') as estado
            FROM reservas r 
            ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";
    
    $result = $conn->query($sql);

    $reservas = [];
    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            // Asegurar que el estado no esté vacío
            if (empty($fila['estado'])) {
                $fila['estado'] = 'pendiente';
            }
            $reservas[] = $fila;
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'reservas' => $reservas,
        'total' => count($reservas)
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 