<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Total de registros
$total_resultado = $conn->query("SELECT COUNT(*) as total FROM platosmenu");
$total_filas = $total_resultado->fetch_assoc()['total'];
$total_paginas = ceil($total_filas / $por_pagina);

// Obtener todos los platos con información del restaurante y categoría
$sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen, 
               c.nombre AS categoria, r.nombre AS restaurante
        FROM platosmenu p
        JOIN categorias c ON p.categoria_id = c.id
        JOIN restaurantes r ON p.id_restaurante = r.id
        ORDER BY r.nombre, c.nombre, p.nombre
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
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Imagen</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>";

    while ($row = $result->fetch_assoc()) {
        $imagenHTML = $row['imagen']
            ? "<img src='data:image/jpeg;base64," . base64_encode($row['imagen']) . "' class='imagen-mini'>"
            : "<span class='sin-imagen'>Sin imagen</span>";

        echo "<tr>
                <td><span class='badge badge-restaurante'>{$row['restaurante']}</span></td>
                <td><span class='badge badge-categoria'>{$row['categoria']}</span></td>
                <td class='nombre-item'>{$row['nombre']}</td>
                <td class='descripcion-item'>{$row['descripcion']}</td>
                <td class='precio-item'>$ " . number_format($row['precio'], 0, ',', '.') . "</td>
                <td class='imagen-celda'>$imagenHTML</td>
                <td class='acciones-celda'>
                    <button onclick=\"verDetallePlato({$row['id']})\" class='btn btn-info btn-sm'>
                        <i class='icon-eye'></i> Ver
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
            min($pagina * $por_pagina, $total_filas) . " de $total_filas platos totales</p>
          </div>";

    echo "</div>";
} else {
    echo "<div class='mensaje-vacio'>
            <div class='icono-vacio'></div>
            <h3>No hay platos registrados</h3>
            <p>No se encontraron platos en el sistema.</p>
          </div>";
}

$conn->close();
?> 