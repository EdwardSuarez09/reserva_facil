<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

try {
    $rol = $_SESSION['rol'] ?? '';
    $idAdmin = filter_var($_SESSION['id'] ?? null, FILTER_VALIDATE_INT);
    if ($rol === 'admin' && (!$idAdmin || $idAdmin < 1)) {
        http_response_code(401);
        exit;
    }

    $sql = "SELECT r.*,
                   COALESCE(r.nombre_cliente, 'Cliente sin nombre') AS cliente_nombre,
                   COALESCE(r.telefono_cliente, '') AS cliente_telefono,
                   COALESCE(r.email_cliente, '') AS cliente_email,
                   COALESCE(r.estado, 'pendiente') AS estado
            FROM reservas r
            INNER JOIN restaurantes rest ON rest.id = r.id_restaurante";
    if ($rol === 'admin') {
        $sql .= ' WHERE rest.id_admin = ?';
    }
    $sql .= ' ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la consulta de reservas.');
    }
    if ($rol === 'admin') {
        $stmt->bind_param('i', $idAdmin);
    }
    if (!$stmt->execute()) {
        throw new RuntimeException('No se pudieron consultar las reservas.');
    }
    $result = $stmt->get_result();

    $reservas = [];
    while ($fila = $result->fetch_assoc()) {
        if (empty($fila['estado'])) {
            $fila['estado'] = 'pendiente';
        }
        $reservas[] = $fila;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'reservas' => $reservas,
        'total' => count($reservas)
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor.'
    ]);
}

$conn->close();
?> 