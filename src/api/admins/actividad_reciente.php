<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Obtener actividad reciente combinando varias fuentes
    $actividades = [];

    // Últimas reservas (máximo 3)
    $sql = "SELECT r.fecha_creacion, r.nombre_cliente, rest.nombre as restaurante_nombre
            FROM reservas r
            JOIN restaurantes rest ON r.id_restaurante = rest.id
            ORDER BY r.fecha_creacion DESC
            LIMIT 3";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tiempo_transcurrido = calcularTiempoTranscurrido($row['fecha_creacion']);
            $actividades[] = [
                'tiempo' => $tiempo_transcurrido,
                'texto' => "Nueva reserva de {$row['nombre_cliente']} en \"{$row['restaurante_nombre']}\"",
                'tipo' => 'reserva'
            ];
        }
    }

    // Últimos administradores registrados (máximo 2)
    $sql = "SELECT fecha_creacion, nombre FROM admin ORDER BY fecha_creacion DESC LIMIT 2";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tiempo_transcurrido = calcularTiempoTranscurrido($row['fecha_creacion']);
            $actividades[] = [
                'tiempo' => $tiempo_transcurrido,
                'texto' => "Admin registrado: {$row['nombre']}",
                'tipo' => 'admin'
            ];
        }
    }

    // Últimos restaurantes registrados (máximo 2)
    $sql = "SELECT fecha_creacion, nombre FROM restaurantes ORDER BY fecha_creacion DESC LIMIT 2";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tiempo_transcurrido = calcularTiempoTranscurrido($row['fecha_creacion']);
            $actividades[] = [
                'tiempo' => $tiempo_transcurrido,
                'texto' => "Restaurante \"{$row['nombre']}\" registrado",
                'tipo' => 'restaurante'
            ];
        }
    }

    // Ordenar por tiempo (más reciente primero) y limitar a 8 actividades
    usort($actividades, function($a, $b) {
        return strcmp($a['tiempo'], $b['tiempo']);
    });
    $actividades = array_slice($actividades, 0, 8);

    // Generar HTML
    $html = "";
    if (!empty($actividades)) {
        foreach ($actividades as $actividad) {
            $html .= "<div class='activity-item'>";
            $html .= "<span class='activity-time'>{$actividad['tiempo']}</span>";
            $html .= "<span class='activity-text'>{$actividad['texto']}</span>";
            $html .= "</div>";
        }
    } else {
        $html = "<div class='activity-item'>";
        $html .= "<span class='activity-time'>--</span>";
        $html .= "<span class='activity-text'>No hay actividad reciente</span>";
        $html .= "</div>";
    }

    echo $html;

} catch (Exception $e) {
    echo "<div class='activity-item'>";
    echo "<span class='activity-time'>Error</span>";
    echo "<span class='activity-text'>No se pudo cargar la actividad reciente</span>";
    echo "</div>";
    error_log("Error obteniendo actividad reciente: " . $e->getMessage());
}

function calcularTiempoTranscurrido($fecha_creacion) {
    $ahora = new DateTime();
    $fecha = new DateTime($fecha_creacion);
    $diferencia = $ahora->diff($fecha);

    if ($diferencia->days > 0) {
        return "Hace " . $diferencia->days . " día" . ($diferencia->days > 1 ? "s" : "");
    } elseif ($diferencia->h > 0) {
        return "Hace " . $diferencia->h . " hora" . ($diferencia->h > 1 ? "s" : "");
    } elseif ($diferencia->i > 0) {
        return "Hace " . $diferencia->i . " min";
    } else {
        return "Hace un momento";
    }
}

$conn->close();
?> 