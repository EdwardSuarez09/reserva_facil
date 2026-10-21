<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
include __DIR__ . '/../../helpers/reservas_rules.php';
verificarRol(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

$clienteId = filter_var($_POST['cliente_id'] ?? null, FILTER_VALIDATE_INT);
$restauranteId = filter_var($_POST['id_restaurante'] ?? null, FILTER_VALIDATE_INT);
$mesaId = filter_var($_POST['id_mesa'] ?? null, FILTER_VALIDATE_INT);
$personas = filter_var($_POST['numero_personas'] ?? null, FILTER_VALIDATE_INT);
$duracion = filter_var($_POST['duracion'] ?? 120, FILTER_VALIDATE_INT);
$nombre = trim($_POST['nombre_cliente'] ?? '');
$telefono = trim($_POST['telefono_cliente'] ?? '');
$email = trim($_POST['email_cliente'] ?? '');
$fecha = $_POST['fecha_reserva'] ?? '';
$hora = $_POST['hora_reserva'] ?? '';
$comentarios = trim($_POST['comentarios'] ?? '');
$estado = $_POST['estado'] ?? 'Pendiente';

if (
    !$clienteId || $clienteId < 1
    || !$restauranteId || $restauranteId < 1
    || !$mesaId || $mesaId < 1
    || !$personas || $personas < 1
    || $nombre === '' || $telefono === ''
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($nombre) > 100 || strlen($telefono) > 20 || strlen($email) > 100
    || strlen($comentarios) > 65535
    || !in_array($estado, ['Pendiente', 'pendiente', 'Confirmada', 'confirmada'], true)
) {
    http_response_code(400);
    exit('Completa los datos de reserva con valores válidos.');
}

try {
    [$inicio, $fin] = crearIntervaloReserva($fecha, $hora, $duracion);
    $idAdmin = (int) ($_SESSION['id'] ?? 0);
    $rol = $_SESSION['rol'] ?? '';
    $conn->begin_transaction();
    $transactionStarted = true;

    $stmtCliente = $conn->prepare('SELECT id FROM usuarios_cliente WHERE id = ?');
    if (!$stmtCliente) {
        throw new RuntimeException('No se pudo validar al cliente.');
    }
    $stmtCliente->bind_param('i', $clienteId);
    if (!$stmtCliente->execute()) {
        throw new RuntimeException('No se pudo validar al cliente.');
    }
    $clienteExiste = $stmtCliente->get_result()->num_rows === 1;
    $stmtCliente->close();
    if (!$clienteExiste) {
        throw new InvalidArgumentException('El cliente seleccionado no existe.');
    }

    $sqlMesa = 'SELECT m.capacidad, r.horario_apertura, r.horario_cierre
                FROM mesas m
                INNER JOIN restaurantes r ON r.id = m.id_restaurante
                WHERE m.id = ? AND r.id = ?';
    if ($rol !== 'superadmin') {
        $sqlMesa .= ' AND r.id_admin = ?';
    }
    $sqlMesa .= ' FOR UPDATE';
    $stmtMesa = $conn->prepare($sqlMesa);
    if (!$stmtMesa) {
        throw new RuntimeException('No se pudo validar la mesa y el restaurante.');
    }
    if ($rol === 'superadmin') {
        $stmtMesa->bind_param('ii', $mesaId, $restauranteId);
    } else {
        $stmtMesa->bind_param('iii', $mesaId, $restauranteId, $idAdmin);
    }
    if (!$stmtMesa->execute()) {
        throw new RuntimeException('No se pudo validar la mesa y el restaurante.');
    }
    $mesa = $stmtMesa->get_result()->fetch_assoc();
    $stmtMesa->close();

    if (!$mesa) {
        http_response_code(403);
        throw new InvalidArgumentException('La mesa no pertenece a un restaurante autorizado.');
    }
    if ($personas > (int) $mesa['capacidad']) {
        throw new InvalidArgumentException('La cantidad de personas supera la capacidad de la mesa.');
    }
    if (!reservaDentroDeHorario($inicio, $fin, $mesa['horario_apertura'], $mesa['horario_cierre'])) {
        throw new InvalidArgumentException('La reserva debe quedar dentro del horario de atención.');
    }
    if (mesaTieneReservaSolapada($conn, $mesaId, $inicio, $fin)) {
        http_response_code(409);
        throw new InvalidArgumentException('La mesa ya tiene una reserva en ese intervalo.');
    }

    $sql = "INSERT INTO reservas
            (cliente_id, id_restaurante, restaurante_id, mesa_id, nombre_cliente, telefono_cliente,
             email_cliente, fecha_reserva, hora_reserva, numero_personas, duracion, comentarios, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la creación de la reserva.');
    }
    $fechaSql = $inicio->format('Y-m-d');
    $horaSql = $inicio->format('H:i:s');
    $stmt->bind_param(
        'iiiisssssiiss',
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
        $comentarios,
        $estado
    );
    if (!$stmt->execute()) {
        throw new RuntimeException('No se pudo guardar la reserva.');
    }
    $stmt->close();

    if (!$conn->commit()) {
        throw new RuntimeException('No se pudo confirmar la reserva.');
    }
    $transactionStarted = false;
    echo 'Reserva registrada correctamente.';
} catch (InvalidArgumentException $exception) {
    if (!empty($transactionStarted)) {
        $conn->rollback();
        $transactionStarted = false;
    }
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo $exception->getMessage();
} catch (Throwable $exception) {
    if (!empty($transactionStarted)) {
        $conn->rollback();
        $transactionStarted = false;
    }
    error_log('Error creando reserva: ' . $exception->getMessage());
    http_response_code(500);
    echo 'No fue posible registrar la reserva.';
} finally {
    $conn->close();
}
