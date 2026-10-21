<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../src/config/conexion.php';

$duplicateAdmins = $pdo->query(
    'SELECT 1 FROM admin GROUP BY correo HAVING COUNT(*) > 1 LIMIT 1'
)->fetchColumn();
if ($duplicateAdmins !== false) {
    fwrite(STDERR, "Hay correos repetidos en admin. Resuelve los duplicados antes de migrar.\n");
    exit(1);
}

$duplicateAccounts = $pdo->query(
    'SELECT 1
     FROM admin a
     INNER JOIN usuarios_cliente c ON c.email = a.correo
     LIMIT 1'
)->fetchColumn();
if ($duplicateAccounts !== false) {
    fwrite(STDERR, "Hay correos compartidos entre admin y usuarios_cliente. Resuelve los conflictos antes de migrar.\n");
    exit(1);
}

$assignedAdmins = $pdo->query(
    "SELECT a.id, a.correo
     FROM admin a
     INNER JOIN restaurantes r ON r.id_admin = a.id
     WHERE a.rol = 'usuario'"
)->fetchAll();
if ($assignedAdmins) {
    $ids = implode(', ', array_column($assignedAdmins, 'id'));
    fwrite(
        STDERR,
        "Hay cuentas heredadas con restaurantes asignados (IDs: {$ids}). "
        . "Asigna esos restaurantes a un administrador antes de migrar.\n"
    );
    exit(1);
}

$pdo->beginTransaction();

try {
    $legacyAccounts = $pdo->query(
        "SELECT id, nombre, correo, `contraseña` FROM admin WHERE rol = 'usuario' FOR UPDATE"
    )->fetchAll();

    $findClient = $pdo->prepare('SELECT id FROM usuarios_cliente WHERE email = ? LIMIT 1');
    $insertClient = $pdo->prepare(
        'INSERT INTO usuarios_cliente (nombre, email, password) VALUES (?, ?, ?)'
    );
    $deleteLegacy = $pdo->prepare("DELETE FROM admin WHERE id = ? AND rol = 'usuario'");

    foreach ($legacyAccounts as $account) {
        if (strlen($account['correo']) > 100 || strlen($account['nombre']) > 100) {
            throw new RuntimeException(
                'Los datos del usuario heredado con ID ' . $account['id'] . ' superan el límite del esquema de clientes.'
            );
        }

        $findClient->execute([$account['correo']]);
        if ($findClient->fetchColumn() !== false) {
            throw new RuntimeException(
                'Hay un correo duplicado entre admin y usuarios_cliente (' . $account['correo'] . '). '
                . 'Resuelve ese conflicto antes de migrar; no se cambiaron cuentas.'
            );
        }

        $password = (string) $account['contraseña'];
        if (empty(password_get_info($password)['algo'])) {
            $password = password_hash($password, PASSWORD_DEFAULT);
            if ($password === false) {
                throw new RuntimeException(
                    'No se pudo proteger la contraseña del usuario heredado con ID ' . $account['id'] . '.'
                );
            }
        }

        $insertClient->execute([$account['nombre'], $account['correo'], $password]);
        $deleteLegacy->execute([$account['id']]);
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

$uniqueEmail = $pdo->query(
    "SELECT 1
     FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin'
       AND NON_UNIQUE = 0 AND COLUMN_NAME = 'correo'
     GROUP BY INDEX_NAME
     HAVING COUNT(*) = 1
     LIMIT 1"
)->fetchColumn();
$alterTable = "ALTER TABLE admin MODIFY rol ENUM('admin', 'superadmin') NOT NULL DEFAULT 'admin'";
if ($uniqueEmail === false) {
    $alterTable .= ', ADD UNIQUE KEY uq_admin_correo (correo)';
}
$pdo->exec($alterTable);

fwrite(STDOUT, count($legacyAccounts) . " cuentas de usuario migradas a usuarios_cliente.\n");
