<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['admin', 'superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol_usuario = $_SESSION['rol'] ?? '';
$id_usuario = $_SESSION['id'] ?? null;

// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

try {
    // Construir consulta según el rol
    if ($rol_usuario === 'superadmin') {
        // Super admin ve todas las reservas de todos los restaurantes
        $sql_count = "SELECT COUNT(*) as total FROM reservas";
        $sql = "SELECT r.id, r.id_restaurante, r.cliente_id, r.mesa_id, r.fecha_reserva, 
                       r.hora_reserva, r.estado, r.duracion,
                       COALESCE(rest.nombre, 'Sin restaurante') as restaurante_nombre,
                       COALESCE(m.numero_mesa, 'Sin asignar') as numero_mesa,
                       COALESCE(m.capacidad, 0) as capacidad_mesa
                FROM reservas r 
                LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id 
                LEFT JOIN mesas m ON r.mesa_id = m.id 
                ORDER BY r.id DESC 
                LIMIT $inicio, $por_pagina";
        
        $total_resultado = $conn->query($sql_count);
        $result = $conn->query($sql);
    } else {
        // Admin normal solo ve reservas de SU restaurante
        $sql_count = "SELECT COUNT(*) as total FROM reservas r 
                      LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id 
                      WHERE rest.id_admin = ?";
        $sql = "SELECT r.id, r.id_restaurante, r.cliente_id, r.mesa_id, r.fecha_reserva, 
                       r.hora_reserva, r.estado, r.duracion,
                       COALESCE(rest.nombre, 'Mi restaurante') as restaurante_nombre,
                       COALESCE(m.numero_mesa, 'Sin asignar') as numero_mesa,
                       COALESCE(m.capacidad, 0) as capacidad_mesa
                FROM reservas r 
                LEFT JOIN restaurantes rest ON r.id_restaurante = rest.id 
                LEFT JOIN mesas m ON r.mesa_id = m.id 
                WHERE rest.id_admin = ? 
                ORDER BY r.id DESC 
                LIMIT $inicio, $por_pagina";
        
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->bind_param("i", $id_usuario);
        $stmt_count->execute();
        $total_resultado = $stmt_count->get_result();
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    $total_filas = $total_resultado->fetch_assoc()['total'];
    $total_paginas = ceil($total_filas / $por_pagina);

} catch (Exception $e) {
    echo "<div class='mensaje-error'>";
    echo "<h3>Error al cargar las reservas</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Por favor, verifique que la tabla de reservas esté configurada correctamente.</p>";
    echo "</div>";
    $conn->close();
    exit;
}

if ($result && $result->num_rows > 0) {
    echo "<div class='tabla-container'>";
    echo "<table class='tabla-datos'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>ID</th>";
    if ($rol_usuario === 'superadmin') {
        echo "<th>Restaurante</th>";
    }
    echo "<th>Cliente ID</th>";
    echo "<th>Mesa</th>";
    echo "<th>Fecha</th>";
    echo "<th>Hora</th>";
    echo "<th>Estado</th>";
    echo "<th>Duración</th>";
    echo "<th>Acciones</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        if ($rol_usuario === 'superadmin') {
            echo "<td>" . htmlspecialchars($row['restaurante_nombre']) . "</td>";
        }
        echo "<td>" . ($row['cliente_id'] ?? 'Sin cliente') . "</td>";
        
        // Información de la mesa
        if ($row['numero_mesa'] && $row['numero_mesa'] !== 'Sin asignar') {
            echo "<td>Mesa " . $row['numero_mesa'];
            if ($row['capacidad_mesa'] > 0) {
                echo " (" . $row['capacidad_mesa'] . " pers.)";
            }
            echo "</td>";
        } else {
            echo "<td>Sin asignar</td>";
        }
        
        echo "<td>" . date('d/m/Y', strtotime($row['fecha_reserva'])) . "</td>";
        echo "<td>" . date('H:i', strtotime($row['hora_reserva'])) . "</td>";
        
        // Estado con colores (usando estados en español)
        $estado_class = '';
        switch(strtolower($row['estado'])) {
            case 'pendiente': $estado_class = 'estado-pendiente'; break;
            case 'confirmada': $estado_class = 'estado-confirmada'; break;
            case 'completada': $estado_class = 'estado-completada'; break;
            case 'cancelada': $estado_class = 'estado-cancelada'; break;
            case 'no_asistio': $estado_class = 'estado-no-asistio'; break;
            default: $estado_class = 'estado-pendiente';
        }
        echo "<td><span class='$estado_class'>" . ucfirst($row['estado']) . "</span></td>";
        
        echo "<td>" . ($row['duracion'] ?? '120') . " min</td>";
        
        echo "<td class='acciones'>";
        echo "<button onclick='editarReserva({$row['id']})' class='btn-editar' title='Editar reserva'>Editar</button>";
        echo "<button onclick='eliminarReserva({$row['id']})' class='btn-eliminar' title='Eliminar reserva'>Eliminar</button>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "</div>";

    // Paginación
    if ($total_paginas > 1) {
        echo "<div class='paginacion'>";
        
        if ($pagina > 1) {
            echo "<a href='?pagina=" . ($pagina - 1) . "' class='btn-paginacion'>« Anterior</a>";
        }
        
        for ($i = 1; $i <= $total_paginas; $i++) {
            $active = ($i == $pagina) ? 'active' : '';
            echo "<a href='?pagina=$i' class='btn-paginacion $active'>$i</a>";
        }
        
        if ($pagina < $total_paginas) {
            echo "<a href='?pagina=" . ($pagina + 1) . "' class='btn-paginacion'>Siguiente »</a>";
        }
        
        echo "</div>";
    }
    
    // Mostrar información de resultados
    echo "<div class='info-resultados'>";
    echo "<p>Mostrando " . (($pagina - 1) * $por_pagina + 1) . " - " . 
         min($pagina * $por_pagina, $total_filas) . " de $total_filas reservas";
    if ($rol_usuario !== 'superadmin') {
        echo " de su restaurante";
    }
    echo "</p>";
    echo "</div>";
    
} else {
    echo "<div class='mensaje-vacio'>";
    echo "<h3>No hay reservas registradas</h3>";
    echo "<p>No se encontraron reservas en el sistema.";
    if ($rol_usuario !== 'superadmin') {
        echo " Verifique que su restaurante tenga reservas registradas.";
    }
    echo "</p>";
    echo "</div>";
}

$conn->close();
?> 