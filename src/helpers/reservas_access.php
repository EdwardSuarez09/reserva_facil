<?php
function autorizarGestionReserva(mysqli $conn, int $reservaId): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Content-Type: application/json');

    $idUsuario = filter_var($_SESSION['id'] ?? null, FILTER_VALIDATE_INT);
    $rol = $_SESSION['rol'] ?? '';

    if (!$idUsuario || !in_array($rol, ['admin', 'superadmin'], true)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        return false;
    }

    if ($rol === 'superadmin') {
        $stmt = $conn->prepare('SELECT id FROM reservas WHERE id = ?');
    } else {
        $stmt = $conn->prepare(
            'SELECT r.id
             FROM reservas r
             INNER JOIN restaurantes rest ON rest.id = r.id_restaurante
             WHERE r.id = ? AND rest.id_admin = ?'
        );
    }

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No se pudo validar el acceso a la reserva']);
        return false;
    }

    if ($rol === 'superadmin') {
        $stmt->bind_param('i', $reservaId);
    } else {
        $stmt->bind_param('ii', $reservaId, $idUsuario);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No se pudo validar el acceso a la reserva']);
        return false;
    }

    $result = $stmt->get_result();
    $authorized = $result && $result->num_rows === 1;
    $stmt->close();

    if (!$authorized) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Reserva no encontrada']);
        return false;
    }

    return true;
}
