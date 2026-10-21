<?php
session_start();
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/reservas_rules.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$restaurante = trim($_POST['restaurante'] ?? '');
$mesaId = filter_var($_POST['id_mesa'] ?? null, FILTER_VALIDATE_INT);
$fecha = $_POST['fecha'] ?? '';
$hora = $_POST['hora'] ?? '';
$personas = filter_var($_POST['personas'] ?? null, FILTER_VALIDATE_INT);
$duracion = filter_var($_POST['duracion'] ?? 120, FILTER_VALIDATE_INT);
$nombre = trim($_POST['nombre_cliente'] ?? '');
$telefono = trim($_POST['telefono_cliente'] ?? '');
$email = trim($_POST['email_cliente'] ?? '');
$comentarios = trim($_POST['comentarios'] ?? '');
$transactionStarted = false;

try {
    if (
        $restaurante === ''
        || !$mesaId || $mesaId < 1
        || !$personas || $personas < 1
        || $nombre === '' || $telefono === ''
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($nombre) > 100 || strlen($telefono) > 20 || strlen($email) > 100
        || strlen($comentarios) > 65535
    ) {
        throw new InvalidArgumentException('Completa todos los datos de la reserva.');
    }

    [$inicio, $fin] = crearIntervaloReserva($fecha, $hora, $duracion);
    $stmtRestaurante = $conn->prepare(
        'SELECT id, nombre FROM restaurantes WHERE nombre = ? LIMIT 2'
    );
    if (!$stmtRestaurante) {
        throw new RuntimeException('No se pudo consultar el restaurante.');
    }
    $stmtRestaurante->bind_param('s', $restaurante);
    if (!$stmtRestaurante->execute()) {
        throw new RuntimeException('No se pudo consultar el restaurante.');
    }
    $resultadoRestaurante = $stmtRestaurante->get_result();
    $restauranteData = $resultadoRestaurante->fetch_assoc();
    $nombreDuplicado = $resultadoRestaurante->fetch_assoc() !== null;
    $stmtRestaurante->close();

    if (!$restauranteData || $nombreDuplicado) {
        http_response_code($restauranteData ? 409 : 404);
        throw new InvalidArgumentException(
            $restauranteData ? 'El nombre del restaurante no identifica una opción única.' : 'Restaurante no encontrado.'
        );
    }

    $clienteId = null;
    if (
        ($_SESSION['cliente_autenticado'] ?? false) === true
        && isset($_SESSION['cliente_id'])
        && !isset($_SESSION['rol'])
    ) {
        $clienteId = (int) $_SESSION['cliente_id'];
        $stmtCliente = $conn->prepare('SELECT id FROM usuarios_cliente WHERE id = ?');
        if (!$stmtCliente) {
            throw new RuntimeException('No se pudo validar la sesión del cliente.');
        }
        $stmtCliente->bind_param('i', $clienteId);
        if (!$stmtCliente->execute() || $stmtCliente->get_result()->num_rows !== 1) {
            $stmtCliente->close();
            throw new InvalidArgumentException('La sesión de cliente ya no es válida; vuelve a iniciar sesión.');
        }
        $stmtCliente->close();
    }

    $conn->begin_transaction();
    $transactionStarted = true;
    $stmtMesa = $conn->prepare(
        'SELECT m.capacidad, r.horario_apertura, r.horario_cierre
         FROM mesas m
         INNER JOIN restaurantes r ON r.id = m.id_restaurante
         WHERE m.id = ? AND r.id = ?
         FOR UPDATE'
    );
    if (!$stmtMesa) {
        throw new RuntimeException('No se pudo comprobar la disponibilidad de la mesa.');
    }
    $restauranteId = (int) $restauranteData['id'];
    $stmtMesa->bind_param('ii', $mesaId, $restauranteId);
    if (!$stmtMesa->execute()) {
        throw new RuntimeException('No se pudo comprobar la disponibilidad de la mesa.');
    }
    $mesa = $stmtMesa->get_result()->fetch_assoc();
    $stmtMesa->close();

    if (!$mesa) {
        http_response_code(400);
        throw new InvalidArgumentException('La mesa no pertenece al restaurante seleccionado.');
    }
    if ($personas > (int) $mesa['capacidad']) {
        throw new InvalidArgumentException('La cantidad de personas supera la capacidad de la mesa.');
    }
    if (!reservaDentroDeHorario($inicio, $fin, $mesa['horario_apertura'], $mesa['horario_cierre'])) {
        throw new InvalidArgumentException('La reserva debe quedar dentro del horario de atención.');
    }
    if (mesaTieneReservaSolapada($conn, $mesaId, $inicio, $fin)) {
        http_response_code(409);
        throw new InvalidArgumentException('Esta mesa ya tiene una reserva en ese intervalo.');
    }

    $stmtReserva = $conn->prepare(
        "INSERT INTO reservas
         (cliente_id, id_restaurante, restaurante_id, mesa_id, nombre_cliente, telefono_cliente,
          email_cliente, fecha_reserva, hora_reserva, numero_personas, duracion, comentarios, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')"
    );
    if (!$stmtReserva) {
        throw new RuntimeException('No se pudo preparar la reserva.');
    }

    $fechaSql = $inicio->format('Y-m-d');
    $horaSql = $inicio->format('H:i:s');
    $stmtReserva->bind_param(
        'iiiisssssiis',
        $clienteId,
        $restauranteId,
        $restauranteId,
        $mesaId,
        $nombre,
        $telefono,
        $email,
        $fechaSql,
        $horaSql,
        $personas,
        $duracion,
        $comentarios
    );
    if (!$stmtReserva->execute()) {
        throw new RuntimeException('No se pudo guardar la reserva.');
    }

    $reservaId = $conn->insert_id;
    $stmtReserva->close();
    if (!$conn->commit()) {
        throw new RuntimeException('No se pudo confirmar la reserva.');
    }
    $transactionStarted = false;

    echo json_encode([
        'success' => true,
        'message' => '¡Reserva realizada exitosamente!',
        'reserva_id' => $reservaId,
        'detalles' => [
            'cliente' => $nombre,
            'restaurante' => $restauranteData['nombre'],
            'fecha' => $fechaSql,
            'hora' => $horaSql,
            'personas' => $personas,
            'mesa' => $mesaId,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $exception) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    error_log('Error procesando reserva de cliente: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No fue posible procesar la reserva.']);
} finally {
    $conn->close();
}
