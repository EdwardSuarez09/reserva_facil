<?php
function verificarYActualizarContrasena(
    mysqli $conn,
    string $tabla,
    string $columna,
    int $id,
    string $contrasena,
    string $contrasenaGuardada
): bool {
    $columnasPermitidas = [
        'admin' => 'contraseña',
        'usuarios_cliente' => 'password',
    ];

    if (!isset($columnasPermitidas[$tabla]) || $columnasPermitidas[$tabla] !== $columna) {
        throw new InvalidArgumentException('Columna de contraseña no permitida.');
    }

    $passwordInfo = password_get_info($contrasenaGuardada);
    $esHash = !empty($passwordInfo['algo']);
    $esValida = $esHash
        ? password_verify($contrasena, $contrasenaGuardada)
        : hash_equals($contrasenaGuardada, $contrasena);

    if (!$esValida) {
        return false;
    }

    if (!$esHash) {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new RuntimeException('No se pudo proteger la contraseña.');
        }

        $stmt = $conn->prepare("UPDATE `$tabla` SET `$columna` = ? WHERE id = ?");
        if (!$stmt) {
            throw new RuntimeException('No se pudo preparar la actualización de contraseña.');
        }

        $stmt->bind_param('si', $hash, $id);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('No se pudo actualizar la contraseña.');
        }
        $stmt->close();
    }

    return true;
}
