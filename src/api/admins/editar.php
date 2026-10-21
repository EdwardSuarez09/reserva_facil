<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../../config/conexion.php';
require __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$contraseña = $_POST['contraseña'] ?? '';
$rol = $_POST['rol'] ?? '';

if (
    !$id || $id < 1
    || $nombre === ''
    || !filter_var($correo, FILTER_VALIDATE_EMAIL)
    || !in_array($rol, ['admin', 'superadmin'], true)
    || ($contraseña !== '' && strlen($contraseña) < 12)
) {
    http_response_code(400);
    exit('Completa los datos del administrador con valores válidos. La contraseña debe tener al menos 12 caracteres.');
}

$limiteNombre = $pdo->prepare(
    'SELECT CHARACTER_MAXIMUM_LENGTH
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
);
$limiteNombre->execute(['admin', 'nombre']);
$maximoNombre = (int) $limiteNombre->fetchColumn();
$longitudNombre = function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre);
if ($maximoNombre > 0 && $longitudNombre > $maximoNombre) {
    http_response_code(400);
    exit('El nombre supera el límite permitido por la base de datos.');
}

$check = $conn->prepare(
    'SELECT correo AS email FROM admin WHERE correo = ? AND id <> ?
     UNION ALL
     SELECT email FROM usuarios_cliente WHERE email = ?
     LIMIT 1'
);
if (!$check) {
    http_response_code(500);
    exit('No se pudo validar el correo.');
}
$check->bind_param('sis', $correo, $id, $correo);
if (!$check->execute()) {
    $check->close();
    http_response_code(500);
    exit('No se pudo validar el correo.');
}
if ($check->get_result()->num_rows > 0) {
    $check->close();
    http_response_code(409);
    exit('El correo ya está registrado.');
}
$check->close();

$hash = null;
if ($contraseña !== '') {
    $hash = password_hash($contraseña, PASSWORD_DEFAULT);
    if ($hash === false) {
        http_response_code(500);
        exit('No se pudo proteger la contraseña.');
    }
}

$httpStatus = 500;
$errorMessage = 'No se pudo actualizar el administrador.';
try {
    $conn->begin_transaction();

    $superadmins = $conn->query(
        "SELECT id FROM admin WHERE rol = 'superadmin' ORDER BY id FOR UPDATE"
    );
    if (!$superadmins) {
        throw new RuntimeException('No se pudieron bloquear las cuentas superadministradoras.');
    }
    $superadminIds = [];
    while ($row = $superadmins->fetch_assoc()) {
        $superadminIds[] = (int) $row['id'];
    }

    $stmt = $conn->prepare('SELECT rol FROM admin WHERE id = ? FOR UPDATE');
    if (!$stmt) {
        throw new RuntimeException('No se pudo validar el administrador.');
    }
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        throw new RuntimeException('No se pudo validar el administrador.');
    }
    $administrador = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$administrador) {
        $httpStatus = 404;
        $errorMessage = 'Administrador no encontrado.';
        throw new DomainException($errorMessage);
    }
    if (
        $administrador['rol'] === 'superadmin'
        && $rol === 'admin'
        && count($superadminIds) <= 1
    ) {
        $httpStatus = 409;
        $errorMessage = 'No se puede cambiar el rol del último superadministrador.';
        throw new DomainException($errorMessage);
    }

    if ($hash !== null) {
        $stmt = $conn->prepare(
            'UPDATE admin SET nombre = ?, correo = ?, contraseña = ?, rol = ? WHERE id = ?'
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo preparar la actualización.');
        }
        $stmt->bind_param('ssssi', $nombre, $correo, $hash, $rol, $id);
    } else {
        $stmt = $conn->prepare('UPDATE admin SET nombre = ?, correo = ?, rol = ? WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException('No se pudo preparar la actualización.');
        }
        $stmt->bind_param('sssi', $nombre, $correo, $rol, $id);
    }
    if (!$stmt->execute()) {
        throw new RuntimeException('No se pudo actualizar el administrador.');
    }
    $stmt->close();

    if (!$conn->commit()) {
        throw new RuntimeException('No se pudo confirmar la actualización.');
    }
    echo 'Administrador actualizado correctamente.';
} catch (DomainException $exception) {
    $conn->rollback();
    http_response_code($httpStatus);
    echo $errorMessage;
} catch (Throwable $exception) {
    $conn->rollback();
    error_log('Error actualizando administrador: ' . $exception->getMessage());
    http_response_code(500);
    echo 'No se pudo actualizar el administrador.';
} finally {
    $conn->close();
}
