<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Paginación
$por_pagina = 15;
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Total de registros
$total_resultado = $conn->query("SELECT COUNT(*) as total FROM categorias");
$total_filas = $total_resultado->fetch_assoc()['total'];
$total_paginas = ceil($total_filas / $por_pagina);

// Obtener todas las categorías con información del restaurante y conteo de platos
$sql = "SELECT c.id, c.nombre, r.nombre AS restaurante,
               COUNT(p.id) AS total_platos
        FROM categorias c
        JOIN restaurantes r ON c.id_restaurante = r.id
        LEFT JOIN platosmenu p ON c.id = p.categoria_id
        GROUP BY c.id, c.nombre, r.nombre
        ORDER BY r.nombre, c.nombre
        LIMIT $inicio, $por_pagina";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<div class='gestion-container'>
        <div class='tabla-responsive'>
            <table class='tabla-moderna'>
                <thead>
                    <tr>
                        <th>Restaurante</th>
                        <th>Categoría</th>
                        <th>Total Platos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>";

    while ($row = $result->fetch_assoc()) {
        $estado_clase = $row['total_platos'] > 0 ? 'activa' : 'vacia';
        $estado_texto = $row['total_platos'] > 0 ? 'Activa' : 'Vacía';

        echo "<tr>
                <td><span class='badge badge-restaurante'>{$row['restaurante']}</span></td>
                <td class='nombre-categoria'>{$row['nombre']}</td>
                <td class='conteo-platos'>
                    <span class='badge badge-platos'>{$row['total_platos']} platos</span>
                </td>
                <td class='estado-celda'>
                    <span class='badge badge-estado badge-$estado_clase'>$estado_texto</span>
                </td>
                <td class='acciones-celda'>
                    <button onclick=\"verDetalleCategoria({$row['id']})\" class='btn btn-info btn-sm'>
                        <i class='icon-eye'></i> Ver
                    </button>
                    <button onclick=\"verPlatosCategoria({$row['id']})\" class='btn btn-success btn-sm'>
                        <i class='icon-list'></i> Platos
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
            min($pagina * $por_pagina, $total_filas) . " de $total_filas categorías totales</p>
          </div>";

    echo "</div>";
} else {
    echo "<div class='mensaje-vacio'>
            <div class='icono-vacio'></div>
            <h3>No hay categorías registradas</h3>
            <p>No se encontraron categorías en el sistema.</p>
          </div>";
}

$conn->close();
?> 