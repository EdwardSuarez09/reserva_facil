<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
include __DIR__ . '/../../helpers/rutas_assets.php';
verificarRol(['superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('ID inválido.');
}

$imagenPath = null;
$transactionStarted = false;

try {
    $conn->begin_transaction();
    $transactionStarted = true;

    $stmt = $conn->prepare('SELECT imagen_path FROM restaurantes WHERE id = ? FOR UPDATE');
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la consulta del restaurante.');
    }
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        throw new RuntimeException('No se pudo consultar el restaurante.');
    }
    $result = $stmt->get_result();
    $restaurante = $result->fetch_assoc();
    $stmt->close();

    if (!$restaurante) {
        $conn->rollback();
        $transactionStarted = false;
        http_response_code(404);
        exit('Restaurante no encontrado.');
    }
    $imagenPath = $restaurante['imagen_path'];

    $eliminar = static function (string $sql, int $parametros) use ($conn, $id): void {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('No se pudo preparar la eliminación relacionada.');
        }

        if ($parametros === 2) {
            $stmt->bind_param('ii', $id, $id);
        } else {
            $stmt->bind_param('i', $id);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('No se pudo eliminar la información relacionada.');
        }
        $stmt->close();
    };

    $eliminar(
        'DELETE dr
         FROM detalle_reserva dr
         LEFT JOIN reservas r ON r.id = dr.reserva_id
         LEFT JOIN platosmenu p ON p.id = dr.plato_id
         WHERE r.id_restaurante = ? OR p.id_restaurante = ?',
        2
    );
    $eliminar(
        'DELETE dp FROM detalle_pedido dp
         INNER JOIN pedidos p ON p.id = dp.pedido_id
         WHERE p.id_restaurante = ?',
        1
    );
    $eliminar('DELETE FROM pedidos WHERE id_restaurante = ?', 1);
    $eliminar('DELETE FROM reservas WHERE id_restaurante = ? OR restaurante_id = ?', 2);
    $eliminar(
        'DELETE FROM platosmenu
         WHERE id_restaurante = ?
            OR categoria_id IN (SELECT id FROM categorias WHERE id_restaurante = ?)',
        2
    );
    $eliminar('DELETE FROM categorias WHERE id_restaurante = ?', 1);
    $eliminar('DELETE FROM cliente WHERE id_restaurante = ?', 1);
    $eliminar('DELETE FROM estilos_restaurante WHERE restaurante_id = ?', 1);
    $eliminar('DELETE FROM restaurantes WHERE id = ?', 1);

    if (!$conn->commit()) {
        throw new RuntimeException('No se pudo confirmar la eliminación.');
    }
    $transactionStarted = false;
} catch (Throwable $exception) {
    if ($transactionStarted) {
        $conn->rollback();
        $transactionStarted = false;
    }
    error_log('Error al eliminar restaurante ' . $id . ': ' . $exception->getMessage());
    http_response_code(500);
    $conn->close();
    exit('No fue posible eliminar el restaurante. No se confirmaron cambios parciales.');
}

if ($imagenPath) {
    $rutaImagen = obtenerRutaImagenArchivo($imagenPath);
    if (is_file($rutaImagen) && !unlink($rutaImagen)) {
        error_log('No se pudo eliminar la imagen del restaurante: ' . $rutaImagen);
    }
}

echo 'Restaurante eliminado correctamente.';
