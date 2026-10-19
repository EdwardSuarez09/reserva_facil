<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Paginación
$por_pagina = 12;
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Total de registros
$total_resultado = $conn->query("SELECT COUNT(*) as total FROM mesas");
$total_filas = $total_resultado->fetch_assoc()['total'];
$total_paginas = ceil($total_filas / $por_pagina);

// Obtener todas las mesas (sin restaurante por ahora si no existe la relación)
$sql = "SELECT m.id, m.numero_mesa, m.capacidad, m.imagen
        FROM mesas m
        ORDER BY m.numero_mesa
        LIMIT $inicio, $por_pagina";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<div class='gestion-container'>
        <div class='tabla-responsive'>
            <table class='tabla-moderna'>
                <thead>
                    <tr>
                        <th>N° Mesa</th>
                        <th>Capacidad</th>
                        <th>Imagen</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>";

    while ($row = $result->fetch_assoc()) {
        $imagenHTML = $row['imagen']
            ? "<img src='data:image/jpeg;base64," . base64_encode($row['imagen']) . "' class='imagen-mini'>"
            : "<span class='sin-imagen'>Sin imagen</span>";

        // Verificar si la mesa tiene reservas activas hoy
        $sql_reservas = "SELECT COUNT(*) as reservas FROM reservas 
                        WHERE mesa_id = {$row['id']} 
                        AND DATE(fecha_reserva) = CURDATE() 
                        AND estado IN ('pendiente', 'confirmada')";
        $result_reservas = $conn->query($sql_reservas);
        $reservas_hoy = $result_reservas->fetch_assoc()['reservas'];
        
        $estado_clase = $reservas_hoy > 0 ? 'ocupada' : 'disponible';
        $estado_texto = $reservas_hoy > 0 ? 'Ocupada' : 'Disponible';

        echo "<tr>
                <td class='numero-mesa'>Mesa {$row['numero_mesa']}</td>
                <td class='capacidad-mesa'>
                    <span class='badge badge-capacidad'>{$row['capacidad']} personas</span>
                </td>
                <td class='imagen-celda'>$imagenHTML</td>
                <td class='estado-celda'>
                    <span class='badge badge-estado badge-$estado_clase'>$estado_texto</span>
                </td>
                <td class='acciones-celda'>
                    <button onclick=\"verDetalleMesa({$row['id']})\" class='btn btn-info btn-sm'>
                        <i class='icon-eye'></i> Ver
                    </button>
                    <button onclick=\"verReservasMesa({$row['id']})\" class='btn btn-warning btn-sm'>
                        <i class='icon-calendar'></i> Reservas
                    </button>
                </td>
              </tr>";
    }

    echo "</tbody></table></div>";

    // Paginación moderna
    if ($total_paginas > 1) {
        echo "<div class='paginacion-moderna'>";
        
        if ($pagina > 1) {
            echo "<a href='?pagina=" . ($pagina - 1) . "' class='btn-paginacion prev'>← Anterior</a>";
        }
        
        // Mostrar páginas
        $inicio_pag = max(1, $pagina - 2);
        $fin_pag = min($total_paginas, $pagina + 2);
        
        if ($inicio_pag > 1) {
            echo "<a href='?pagina=1' class='btn-paginacion'>1</a>";
            if ($inicio_pag > 2) echo "<span class='puntos'>...</span>";
        }
        
        for ($i = $inicio_pag; $i <= $fin_pag; $i++) {
            $active = ($i == $pagina) ? 'active' : '';
            echo "<a href='?pagina=$i' class='btn-paginacion $active'>$i</a>";
        }
        
        if ($fin_pag < $total_paginas) {
            if ($fin_pag < $total_paginas - 1) echo "<span class='puntos'>...</span>";
            echo "<a href='?pagina=$total_paginas' class='btn-paginacion'>$total_paginas</a>";
        }
        
        if ($pagina < $total_paginas) {
            echo "<a href='?pagina=" . ($pagina + 1) . "' class='btn-paginacion next'>Siguiente →</a>";
        }
        
        echo "</div>";
    }

    echo "<div class='info-resultados'>
            <p>Mostrando " . (($pagina - 1) * $por_pagina + 1) . " - " . 
            min($pagina * $por_pagina, $total_filas) . " de $total_filas mesas totales</p>
          </div>";

    echo "</div>";
} else {
    echo "<div class='mensaje-vacio'>
            <div class='icono-vacio'></div>
            <h3>No hay mesas registradas</h3>
            <p>No se encontraron mesas en el sistema.</p>
          </div>";
}

$conn->close();
?> 