<?php
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);
include __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json');
try {
    $result = $conn->query("SELECT id, numero_mesa, capacidad FROM mesas ORDER BY numero_mesa");
    if (!$result) {
        throw new RuntimeException('No se pudo consultar la lista de mesas.');
    }

    $mesas = [];
    while ($row = $result->fetch_assoc()) {
        $mesas[] = [
            'id' => (int) $row['id'],
            'numero_mesa' => (int) $row['numero_mesa'],
            'capacidad' => (int) $row['capacidad'],
            'estado' => 'Disponible',
        ];
    }
    echo json_encode($mesas);
} catch (Exception $e) {
    error_log('Error listando mesas: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo consultar la lista de mesas.']);
}

$conn->close();
?> 