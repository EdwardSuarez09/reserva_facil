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
$idSesion = filter_var($_SESSION['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID de administrador no válido.');
}
if ($idSesion === $id) {
    http_response_code(409);
    exit('No puedes eliminar la cuenta con la que has iniciado sesión.');
}

$httpStatus = 500;
$errorMessage = 'No se pudo eliminar el administrador.';

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
        && count($superadminIds) <= 1
    ) {
        $httpStatus = 409;
        $errorMessage = 'No se puede eliminar el último superadministrador.';
        throw new DomainException($errorMessage);
    }

    $stmt = $conn->prepare('SELECT id FROM restaurantes WHERE id_admin = ? LIMIT 1 FOR UPDATE');
    if (!$stmt) {
        throw new RuntimeException('No se pudo validar los restaurantes asignados.');
    }
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        throw new RuntimeException('No se pudo validar los restaurantes asignados.');
    }
    $tieneRestaurantes = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if ($tieneRestaurantes) {
        $httpStatus = 409;
        $errorMessage = 'Transfiere o elimina los restaurantes asignados antes de eliminar esta cuenta.';
        throw new DomainException($errorMessage);
    }

    $stmt = $conn->prepare('DELETE FROM admin WHERE id = ?');
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la eliminación.');
    }
    $stmt->bind_param('i', $id);
    if (!$stmt->execute() || $stmt->affected_rows !== 1) {
        throw new RuntimeException('No se pudo eliminar el administrador.');
    }
    $stmt->close();

    if (!$conn->commit()) {
        throw new RuntimeException('No se pudo confirmar la eliminación.');
    }
    echo 'Administrador eliminado correctamente.';
} catch (DomainException $exception) {
    $conn->rollback();
    http_response_code($httpStatus);
    echo $errorMessage;
} catch (Throwable $exception) {
    $conn->rollback();
    error_log('Error eliminando administrador: ' . $exception->getMessage());
    http_response_code(500);
    echo 'No se pudo eliminar el administrador.';
} finally {
    $conn->close();
}
