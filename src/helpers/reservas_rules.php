<?php
function crearIntervaloReserva(string $fecha, string $hora, int $duracion, bool $exigirFuturo = true): array
{
    if ($duracion < 30 || $duracion > 480) {
        throw new InvalidArgumentException('La duración debe estar entre 30 y 480 minutos.');
    }

    $zonaHoraria = new DateTimeZone(date_default_timezone_get());
    $inicio = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $fecha . ' ' . $hora, $zonaHoraria);
    $errores = DateTimeImmutable::getLastErrors();
    if (
        $inicio === false
        || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))
        || $inicio->format('Y-m-d H:i') !== $fecha . ' ' . $hora
    ) {
        throw new InvalidArgumentException('La fecha o la hora no tienen un formato válido.');
    }

    if ($exigirFuturo && $inicio <= new DateTimeImmutable('now', $zonaHoraria)) {
        throw new InvalidArgumentException('La reserva debe ser para una hora futura.');
    }

    return [$inicio, $inicio->modify('+' . $duracion . ' minutes')];
}

function reservaDentroDeHorario(
    DateTimeImmutable $inicio,
    DateTimeImmutable $fin,
    string $apertura,
    string $cierre
): bool {
    $abre = DateTimeImmutable::createFromFormat('!H:i:s', $apertura);
    $cierra = DateTimeImmutable::createFromFormat('!H:i:s', $cierre);
    if ($abre === false || $cierra === false) {
        return false;
    }

    $diaReserva = $inicio->format('Y-m-d');
    $horaReserva = $inicio->format('H:i:s');
    $cruzaMedianoche = $cierra <= $abre;
    $diaApertura = $diaReserva;
    if ($cruzaMedianoche && $horaReserva < $cierre) {
        $diaApertura = $inicio->modify('-1 day')->format('Y-m-d');
    }

    $aperturaReserva = new DateTimeImmutable($diaApertura . ' ' . $apertura, $inicio->getTimezone());
    $cierreReserva = new DateTimeImmutable($diaApertura . ' ' . $cierre, $inicio->getTimezone());
    if ($cruzaMedianoche) {
        $cierreReserva = $cierreReserva->modify('+1 day');
    }

    return $inicio >= $aperturaReserva && $fin <= $cierreReserva;
}

function reservasSeSolapan(
    DateTimeImmutable $inicioNuevo,
    DateTimeImmutable $finNuevo,
    DateTimeImmutable $inicioExistente,
    int $duracionExistente
): bool {
    $finExistente = $inicioExistente->modify('+' . max(1, $duracionExistente) . ' minutes');
    return $inicioNuevo < $finExistente && $finNuevo > $inicioExistente;
}

function mesaTieneReservaSolapada(
    mysqli $conn,
    int $mesaId,
    DateTimeImmutable $inicio,
    DateTimeImmutable $fin,
    ?int $excluirReservaId = null
): bool {
    $fechaInicio = $inicio->modify('-1 day')->format('Y-m-d');
    $fechaFin = $fin->modify('+1 day')->format('Y-m-d');
    $sql = "SELECT id, fecha_reserva, hora_reserva, COALESCE(duracion, 90) AS duracion
            FROM reservas
            WHERE mesa_id = ?
              AND fecha_reserva BETWEEN ? AND ?
              AND LOWER(estado) IN ('pendiente', 'confirmada')";
    if ($excluirReservaId !== null) {
        $sql .= ' AND id <> ?';
    }
    $sql .= ' FOR UPDATE';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('No se pudo revisar la disponibilidad de la mesa.');
    }
    if ($excluirReservaId === null) {
        $stmt->bind_param('iss', $mesaId, $fechaInicio, $fechaFin);
    } else {
        $stmt->bind_param('issi', $mesaId, $fechaInicio, $fechaFin, $excluirReservaId);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('No se pudo revisar la disponibilidad de la mesa.');
    }

    $result = $stmt->get_result();
    while ($reserva = $result->fetch_assoc()) {
        $inicioExistente = new DateTimeImmutable(
            $reserva['fecha_reserva'] . ' ' . $reserva['hora_reserva'],
            $inicio->getTimezone()
        );
        if (
            reservasSeSolapan(
                $inicio,
                $fin,
                $inicioExistente,
                (int) $reserva['duracion']
            )
        ) {
            $stmt->close();
            return true;
        }
    }
    $stmt->close();
    return false;
}
