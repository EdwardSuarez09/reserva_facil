<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../src/config/conexion.php';
require __DIR__ . '/../src/helpers/reservas_rules.php';

$expect = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$passed = 0;
$failed = 0;
$test = static function (string $name, callable $assertion) use (&$passed, &$failed): void {
    try {
        $assertion();
        $passed++;
        fwrite(STDOUT, "PASS $name\n");
    } catch (Throwable $exception) {
        $failed++;
        fwrite(STDERR, "FAIL $name: {$exception->getMessage()}\n");
    }
};

$mesa = $conn->query(
    'SELECT id, id_restaurante
     FROM mesas
     WHERE id_restaurante IS NOT NULL
     ORDER BY id
     LIMIT 1'
)->fetch_assoc();
if (!$mesa) {
    fwrite(STDERR, "No hay una mesa existente para probar las reglas de disponibilidad.\n");
    exit(2);
}

$fecha = (new DateTimeImmutable('+90 days'))->format('Y-m-d');
$hora = '12:00:00';
$duracion = 60;
$reservaId = null;

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare(
        "INSERT INTO reservas
         (cliente_id, id_restaurante, restaurante_id, mesa_id, fecha_reserva, hora_reserva,
          estado, duracion, numero_personas, nombre_cliente, telefono_cliente, email_cliente)
         VALUES (NULL, ?, ?, ?, ?, ?, 'Pendiente', ?, 1, 'Prueba automatizada',
                 '0000000000', 'reserva-automatizada@example.invalid')"
    );
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la reserva temporal.');
    }
    $restauranteId = (int) $mesa['id_restaurante'];
    $mesaId = (int) $mesa['id'];
    $stmt->bind_param('iiissi', $restauranteId, $restauranteId, $mesaId, $fecha, $hora, $duracion);
    if (!$stmt->execute()) {
        throw new RuntimeException('No se pudo crear la reserva temporal.');
    }
    $reservaId = (int) $conn->insert_id;
    $stmt->close();

    $inicio = new DateTimeImmutable($fecha . ' ' . $hora, new DateTimeZone(date_default_timezone_get()));
    $fin = $inicio->modify('+' . $duracion . ' minutes');

    $test('detects a committed reservation interval in MariaDB', static function () use (
        $expect,
        $conn,
        $mesaId,
        $inicio,
        $fin
    ): void {
        $expect(
            mesaTieneReservaSolapada($conn, $mesaId, $inicio->modify('+30 minutes'), $fin->modify('+30 minutes')),
            'The overlapping interval was not detected'
        );
    });

    $test('allows a reservation immediately after the existing one', static function () use (
        $expect,
        $conn,
        $mesaId,
        $inicio,
        $duracion
    ): void {
        $adjacentStart = $inicio->modify('+' . $duracion . ' minutes');
        $adjacentEnd = $adjacentStart->modify('+60 minutes');
        $expect(
            !mesaTieneReservaSolapada($conn, $mesaId, $adjacentStart, $adjacentEnd),
            'An adjacent interval was incorrectly rejected'
        );
    });
} catch (Throwable $exception) {
    $failed++;
    fwrite(STDERR, "FAIL MariaDB fixture: {$exception->getMessage()}\n");
} finally {
    $conn->rollback();
    if ($reservaId !== null) {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM reservas WHERE id = ?');
        $stmt->bind_param('i', $reservaId);
        $stmt->execute();
        $stillExists = (int) $stmt->get_result()->fetch_row()[0] > 0;
        $stmt->close();
        if ($stillExists) {
            $failed++;
            fwrite(STDERR, "FAIL temporary reservation rollback: fixture row remained in database\n");
        } else {
            $passed++;
            fwrite(STDOUT, "PASS temporary reservation rolled back; database data unchanged\n");
        }
    }
    $conn->close();
}

fwrite(STDOUT, "\n$passed passed; $failed failed.\n");
exit($failed === 0 ? 0 : 1);
