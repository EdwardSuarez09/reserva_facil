<?php
date_default_timezone_set('America/Bogota');
require __DIR__ . '/../src/helpers/reservas_rules.php';

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

$expect = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$expectInvalid = static function (callable $action): void {
    try {
        $action();
    } catch (InvalidArgumentException $exception) {
        return;
    }
    throw new RuntimeException('Expected InvalidArgumentException');
};

$tomorrow = (new DateTimeImmutable('tomorrow 12:00'))->format('Y-m-d');

$test('parses future reservation interval and duration', static function () use ($expect, $tomorrow): void {
    [$start, $end] = crearIntervaloReserva($tomorrow, '18:30', 90);
    $expect($start->format('H:i:s') === '18:30:00', 'Unexpected start time');
    $expect($end->format('H:i:s') === '20:00:00', 'Unexpected end time');
});

$test('rejects invalid calendar date', static function () use ($expectInvalid): void {
    $expectInvalid(static function (): void {
        crearIntervaloReserva('2026-02-30', '18:00', 90);
    });
});

$test('rejects invalid time', static function () use ($expectInvalid, $tomorrow): void {
    $expectInvalid(static function () use ($tomorrow): void {
        crearIntervaloReserva($tomorrow, '25:00', 90);
    });
});

$test('rejects duration outside supported range', static function () use ($expectInvalid, $tomorrow): void {
    $expectInvalid(static function () use ($tomorrow): void {
        crearIntervaloReserva($tomorrow, '18:00', 900);
    });
});

$test('allows adjacent reservations without overlap', static function () use ($expect, $tomorrow): void {
    [$newStart, $newEnd] = crearIntervaloReserva($tomorrow, '20:00', 60);
    $existingStart = new DateTimeImmutable($tomorrow . ' 18:30:00');
    $expect(!reservasSeSolapan($newStart, $newEnd, $existingStart, 90), 'Adjacent intervals overlapped');
});

$test('detects overlapping reservations of different durations', static function () use ($expect, $tomorrow): void {
    [$newStart, $newEnd] = crearIntervaloReserva($tomorrow, '19:45', 90);
    $existingStart = new DateTimeImmutable($tomorrow . ' 19:00:00');
    $expect(reservasSeSolapan($newStart, $newEnd, $existingStart, 60), 'Overlap was missed');
});

$test('enforces closing time for the full reservation', static function () use ($expect, $tomorrow): void {
    [$start, $end] = crearIntervaloReserva($tomorrow, '21:30', 60);
    $expect(!reservaDentroDeHorario($start, $end, '10:00:00', '22:00:00'), 'Reservation past closing was accepted');
});

$test('supports restaurant hours crossing midnight', static function () use ($expect, $tomorrow): void {
    [$start, $end] = crearIntervaloReserva($tomorrow, '01:00', 30);
    $expect(reservaDentroDeHorario($start, $end, '18:00:00', '02:00:00'), 'Overnight service window was rejected');
});

fwrite(STDOUT, "\n$passed passed; $failed failed.\n");
exit($failed === 0 ? 0 : 1);
