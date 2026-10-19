<?php
include __DIR__ . '/../../config/conexion.php';

try {
    // Array de mesas por defecto
    $mesas = [
        ['id' => 1, 'numero_mesa' => 1, 'capacidad' => 2, 'estado' => 'Disponible'],
        ['id' => 2, 'numero_mesa' => 2, 'capacidad' => 2, 'estado' => 'Disponible'],
        ['id' => 3, 'numero_mesa' => 3, 'capacidad' => 4, 'estado' => 'Disponible'],
        ['id' => 4, 'numero_mesa' => 4, 'capacidad' => 4, 'estado' => 'Disponible'],
        ['id' => 5, 'numero_mesa' => 5, 'capacidad' => 6, 'estado' => 'Disponible'],
        ['id' => 6, 'numero_mesa' => 6, 'capacidad' => 6, 'estado' => 'Disponible'],
        ['id' => 7, 'numero_mesa' => 7, 'capacidad' => 8, 'estado' => 'Disponible'],
        ['id' => 8, 'numero_mesa' => 8, 'capacidad' => 8, 'estado' => 'Disponible']
    ];

    // Intentar obtener mesas de la BD si existe la tabla
    $result = $conn->query("SELECT id, numero_mesa, capacidad FROM mesas ORDER BY numero_mesa");
    
    if ($result && $result->num_rows > 0) {
        $mesas = [];
        while ($row = $result->fetch_assoc()) {
            $mesas[] = [
                'id' => (int)$row['id'],
                'numero_mesa' => (int)$row['numero_mesa'],
                'capacidad' => (int)$row['capacidad'],
                'estado' => 'Disponible'
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($mesas);

} catch (Exception $e) {
    // En caso de error, devolver mesas por defecto
    $mesas_default = [
        ['id' => 1, 'numero_mesa' => 1, 'capacidad' => 2, 'estado' => 'Disponible'],
        ['id' => 2, 'numero_mesa' => 2, 'capacidad' => 4, 'estado' => 'Disponible'],
        ['id' => 3, 'numero_mesa' => 3, 'capacidad' => 6, 'estado' => 'Disponible']
    ];
    
    header('Content-Type: application/json');
    echo json_encode($mesas_default);
}

$conn->close();
?> 