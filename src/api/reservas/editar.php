<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/reservas_access.php';
include __DIR__ . '/../../helpers/reservas_rules.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$numeroPersonas = filter_var($_POST['numero_personas'] ?? null, FILTER_VALIDATE_INT);
$duracion = filter_var($_POST['duracion'] ?? 120, FILTER_VALIDATE_INT);
$mesaId = filter_var($_POST['mesa_id'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim($_POST['cliente_nombre'] ?? '');
$telefono = trim($_POST['telefono_cliente'] ?? '');
$email = trim($_POST['email_cliente'] ?? '');
$fecha = $_POST['fecha_reserva'] ?? '';
$hora = $_POST['hora_reserva'] ?? '';
$estado = $_POST['estado'] ?? 'pendiente';
$comentarios = trim($_POST['comentarios'] ?? '');
$estadosPermitidos = [
    'Pendiente', 'pendiente', 'Confirmada', 'confirmada',
    'Completada', 'completada', 'Cancelada', 'cancelada', 'no_asistio', 'Disponible',
];
$transactionStarted = false;

try {
    if (
        !$id || $id < 1 || !$numeroPersonas || $numeroPersonas < 1
        || !$mesaId || $mesaId < 1
        || $duracion < 30 || $duracion > 480
        || $nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($nombre) > 100 || strlen($telefono) > 20 || strlen($email) > 100
        || strlen($comentarios) > 65535
        || !in_array($estado, $estadosPermitidos, true)
    ) {
        throw new InvalidArgumentException('Completa los datos de la reserva con valores válidos.');
    }
    [$inicio, $fin] = crearIntervaloReserva($fecha, $hora, $duracion, false);
    if (!autorizarGestionReserva($conn, $id)) {
        exit;
    }

    $conn->begin_transaction();
    $transactionStarted = true;
    $stmtReserva = $conn->prepare(
        'SELECT id_restaurante FROM reservas WHERE id = ? FOR UPDATE'
    );
    if (!$stmtReserva) {
        throw new RuntimeException('No se pudo bloquear la reserva para editar.');
    }
    $stmtReserva->bind_param('i', $id);
    if (!$stmtReserva->execute()) {
        throw new RuntimeException('No se pudo consultar la reserva.');
    }
    $reserva = $stmtReserva->get_result()->fetch_assoc();
    $stmtReserva->close();
    if (!$reserva) {
        http_response_code(404);
        throw new InvalidArgumentException('Reserva no encontrada.');
    }
    $restauranteId = (int) $reserva['id_restaurante'];

    $stmtMesa = $conn->prepare(
        'SELECT m.capacidad, r.horario_apertura, r.horario_cierre
         FROM mesas m
         INNER JOIN restaurantes r ON r.id = m.id_restaurante
         WHERE m.id = ? AND r.id = ?
         FOR UPDATE'
    );
    if (!$stmtMesa) {
        throw new RuntimeException('No se pudo validar la mesa.');
    }
    $stmtMesa->bind_param('ii', $mesaId, $restauranteId);
    if (!$stmtMesa->execute()) {
        throw new RuntimeException('No se pudo validar la mesa.');
    }
    $mesa = $stmtMesa->get_result()->fetch_assoc();
    $stmtMesa->close();
    if (!$mesa) {
        throw new InvalidArgumentException('La mesa no pertenece al restaurante de la reserva.');
    }
    if ($numeroPersonas > (int) $mesa['capacidad']) {
        throw new InvalidArgumentException('La cantidad de personas supera la capacidad de la mesa.');
    }
    if (!reservaDentroDeHorario($inicio, $fin, $mesa['horario_apertura'], $mesa['horario_cierre'])) {
        throw new InvalidArgumentException('La reserva debe quedar dentro del horario de atención.');
    }
    if (
        in_array(strtolower($estado), ['pendiente', 'confirmada'], true)
        && mesaTieneReservaSolapada($conn, $mesaId, $inicio, $fin, $id)
    ) {
        http_response_code(409);
        throw new InvalidArgumentException('La mesa ya tiene otra reserva en ese intervalo.');
    }

    $sql = 'UPDATE reservas SET
                nombre_cliente = ?, telefono_cliente = ?, email_cliente = ?,
                numero_personas = ?, fecha_reserva = ?, hora_reserva = ?, duracion = ?,
                estado = ?, mesa_id = ?, comentarios = ?
            WHERE id = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la actualización de la reserva.');
    }
    $fechaSql = $inicio->format('Y-m-d');
    $horaSql = $inicio->format('H:i:s');
    $stmt->bind_param(
        'sssissisisi',
        $nombre,
        $telefono,
        $email,
        $numeroPersonas,
        $fechaSql,
        $horaSql,
        $duracion,
        $estado,
        $mesaId,
        $comentarios,
        $id
    );
    if (!$stmt->execute()) {
        throw new RuntimeException('No se pudo actualizar la reserva.');
    }
    $stmt->close();
    if (!$conn->commit()) {
        throw new RuntimeException('No se pudo confirmar la actualización.');
    }
    $transactionStarted = false;

    echo json_encode(['success' => true, 'message' => 'Reserva actualizada correctamente']);
} catch (InvalidArgumentException $exception) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode(['success' => false, 'error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    error_log('Error editando reserva: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'No fue posible actualizar la reserva.']);
} finally {
    $conn->close();
}
