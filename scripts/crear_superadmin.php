<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$email = trim(getenv('SUPERADMIN_EMAIL') ?: '');
$nombre = trim(getenv('SUPERADMIN_NAME') ?: '');
$password = getenv('SUPERADMIN_PASSWORD');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Define SUPERADMIN_EMAIL con un correo válido.\n");
    exit(1);
}
if ($nombre === '') {
    fwrite(STDERR, "Define SUPERADMIN_NAME con el nombre del superadministrador.\n");
    exit(1);
}
if ($password === false || strlen($password) < 12) {
    fwrite(STDERR, "Define SUPERADMIN_PASSWORD con al menos 12 caracteres.\n");
    exit(1);
}

require __DIR__ . '/../src/config/conexion.php';

$stmt = $pdo->prepare(
    "SELECT CHARACTER_MAXIMUM_LENGTH
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin' AND COLUMN_NAME = 'nombre'"
);
$stmt->execute();
$nombreMaximo = (int) $stmt->fetchColumn();
if ($nombreMaximo < 1 || strlen($nombre) > $nombreMaximo) {
    fwrite(STDERR, "El nombre supera el límite de la columna admin.nombre.\n");
    exit(1);
}

$existing = $pdo->query("SELECT id FROM admin WHERE rol = 'superadmin' LIMIT 1")->fetchColumn();
if ($existing !== false) {
    fwrite(STDERR, "Ya existe un superadministrador; no se creó otra cuenta.\n");
    exit(1);
}

$stmt = $pdo->prepare('SELECT id FROM admin WHERE correo = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetchColumn() !== false) {
    fwrite(STDERR, "El correo ya está registrado. No se modificó ninguna cuenta.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
if ($passwordHash === false) {
    throw new RuntimeException('No se pudo generar el hash de la contraseña.');
}

$stmt = $pdo->prepare(
    'INSERT INTO admin (nombre, correo, `contraseña`, rol) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$nombre, $email, $passwordHash, 'superadmin']);

fwrite(STDOUT, "Superadministrador creado correctamente.\n");
