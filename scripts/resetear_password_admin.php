<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$email = trim(getenv('ADMIN_EMAIL') ?: '');
$password = getenv('ADMIN_NEW_PASSWORD');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Define ADMIN_EMAIL con el correo de la cuenta que vas a actualizar.\n");
    exit(1);
}
if ($password === false || strlen($password) < 12) {
    fwrite(STDERR, "Define ADMIN_NEW_PASSWORD con al menos 12 caracteres.\n");
    exit(1);
}

require __DIR__ . '/../src/config/conexion.php';

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
if ($passwordHash === false) {
    throw new RuntimeException('No se pudo generar el hash de la contraseña.');
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id FROM admin WHERE correo = ? FOR UPDATE');
    $stmt->execute([$email]);
    $adminId = $stmt->fetchColumn();
    if ($adminId === false) {
        $pdo->rollBack();
        fwrite(STDERR, "No existe una cuenta administrativa con ese correo.\n");
        exit(1);
    }

    $stmt = $pdo->prepare('UPDATE admin SET `contraseña` = ? WHERE id = ?');
    $stmt->execute([$passwordHash, $adminId]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('No se pudo actualizar la contraseña de la cuenta.');
    }
    $pdo->commit();
    fwrite(STDOUT, "Contraseña actualizada correctamente.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error actualizando contraseña administrativa: ' . $exception->getMessage());
    fwrite(STDERR, "No se pudo actualizar la contraseña administrativa.\n");
    exit(1);
}
