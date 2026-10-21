<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/reservas_rules.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$nombreRestaurante = trim($_GET['restaurante'] ?? '');
$fecha = $_GET['fecha'] ?? '';
$hora = $_GET['hora'] ?? '';
$personas = filter_var($_GET['personas'] ?? null, FILTER_VALIDATE_INT);
$duracion = filter_var($_GET['duracion'] ?? 120, FILTER_VALIDATE_INT);

try {
    if ($nombreRestaurante === '' || !$personas || $personas < 1) {
        throw new InvalidArgumentException('Completa restaurante y cantidad de personas.');
    }
    [$inicio, $fin] = crearIntervaloReserva($fecha, $hora, (int) $duracion);

    $stmtRestaurante = $conn->prepare(
        'SELECT id, nombre, horario_apertura, horario_cierre
         FROM restaurantes WHERE nombre = ? LIMIT 2'
    );
    if (!$stmtRestaurante) {
        throw new RuntimeException('No se pudo consultar el restaurante.');
    }
    $stmtRestaurante->bind_param('s', $nombreRestaurante);
    if (!$stmtRestaurante->execute()) {
        throw new RuntimeException('No se pudo consultar el restaurante.');
    }
    $resultadoRestaurante = $stmtRestaurante->get_result();
    $restaurante = $resultadoRestaurante->fetch_assoc();
    $nombreDuplicado = $resultadoRestaurante->fetch_assoc() !== null;
    $stmtRestaurante->close();

    if (!$restaurante) {
        http_response_code(404);
        throw new InvalidArgumentException('Restaurante no encontrado.');
    }
    if ($nombreDuplicado) {
        http_response_code(409);
        throw new InvalidArgumentException('El nombre del restaurante no identifica una opción única.');
    }
    if (!reservaDentroDeHorario($inicio, $fin, $restaurante['horario_apertura'], $restaurante['horario_cierre'])) {
        throw new InvalidArgumentException('La reserva debe quedar dentro del horario de atención.');
    }

    $stmtMesas = $conn->prepare(
        'SELECT id, numero_mesa, capacidad, imagen
         FROM mesas
         WHERE id_restaurante = ? AND capacidad >= ?
         ORDER BY numero_mesa'
    );
    if (!$stmtMesas) {
        throw new RuntimeException('No se pudo consultar las mesas.');
    }
    $restauranteId = (int) $restaurante['id'];
    $stmtMesas->bind_param('ii', $restauranteId, $personas);
    if (!$stmtMesas->execute()) {
        throw new RuntimeException('No se pudo consultar las mesas.');
    }

    $mesasDisponibles = [];
    $resultadoMesas = $stmtMesas->get_result();
    while ($mesa = $resultadoMesas->fetch_assoc()) {
        if (!mesaTieneReservaSolapada($conn, (int) $mesa['id'], $inicio, $fin)) {
            $mesa['imagen'] = $mesa['imagen'] === null ? null : base64_encode($mesa['imagen']);
            $mesasDisponibles[] = $mesa;
        }
    }
    $stmtMesas->close();

    echo json_encode([
        'success' => true,
        'restaurante' => ['id' => $restauranteId, 'nombre' => $restaurante['nombre']],
        'mesas_disponibles' => $mesasDisponibles,
        'fecha' => $inicio->format('Y-m-d'),
        'hora' => $inicio->format('H:i:s'),
        'personas' => $personas,
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $exception) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode(['success' => false, 'error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Error consultando disponibilidad: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'No fue posible consultar la disponibilidad.']);
} finally {
    $conn->close();
}
