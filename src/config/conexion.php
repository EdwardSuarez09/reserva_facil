<?php
require_once __DIR__ . '/config_timezone.php';

$host = getenv('RESERVA_DB_HOST') ?: '127.0.0.1';
$port = filter_var(getenv('RESERVA_DB_PORT') ?: '3307', FILTER_VALIDATE_INT);
$dbname = getenv('RESERVA_DB_NAME') ?: 'restaurantes_db';
$username = getenv('RESERVA_DB_USER') ?: 'root';
$password = getenv('RESERVA_DB_PASSWORD');
$password = $password === false ? '' : $password;

if ($port === false || $port < 1 || $port > 65535) {
    throw new RuntimeException('El puerto de la base de datos no es válido.');
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $conn = new mysqli($host, $username, $password, $dbname, $port);
    if ($conn->connect_error) {
        throw new RuntimeException($conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
} catch (Throwable $exception) {
    error_log('Error al conectar con la base de datos: ' . $exception->getMessage());
    throw new RuntimeException('No fue posible conectar con la base de datos.', 0, $exception);
}
