<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
include __DIR__ . '/../../helpers/rutas_assets.php';
verificarRol(['superadmin']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_admin = $_SESSION['id'] ?? null;
if (!$id_admin) {
    echo "<p style='color:red;'>Sesión no válida. Por favor inicia sesión.</p>";
    exit;
}

// Paginación
$por_pagina = 5;
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Total de registros
$total_resultado = $conn->query("SELECT COUNT(*) as total FROM restaurantes");
$total_filas = $total_resultado->fetch_assoc()['total'];
$total_paginas = ceil($total_filas / $por_pagina);

// Consulta con JOIN y LIMIT para paginar
$sql = "SELECT r.id, r.nombre, r.direccion, r.descripcion, r.horario, r.telefono, r.correo, r.web, r.redes_sociales, r.imagen_path,
               a.nombre AS admin_nombre 
        FROM restaurantes r 
        JOIN admin a ON r.id_admin = a.id 
        ORDER BY r.id DESC
        LIMIT $inicio, $por_pagina";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<div class='tabla-container' style='overflow-x: auto; background-color: #f9f9f9; border-radius: 12px; padding: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);'>
            <table style='width: 100%; border-collapse: collapse; background-color: white; border-radius: 8px; overflow: hidden;'>
              <thead>
                <tr style='background-color: #667eea; color: white;'>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>ID</th>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>Nombre</th>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>Dirección</th>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>Descripción</th>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>Horario</th>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>Teléfono</th>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>Correo</th>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>Web</th>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>Redes</th>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>Administrador</th>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>Imagen</th>
                  <th style='padding: 15px; text-align: center; font-weight: 600;'>Acciones</th>
                </tr>
              </thead>
              <tbody>";

    while ($row = $result->fetch_assoc()) {
        // Procesar imagen
        $imagenHTML = 'Sin imagen';
        if (!empty($row['imagen_path'])) {
            $imagen_url = '../../' . normalizarRutaImagenPublica($row['imagen_path']);
            $imagen_path = obtenerRutaImagenArchivo($row['imagen_path']);
            if (file_exists($imagen_path)) {
                $imagenHTML = "<img src='{$imagen_url}' alt='Imagen del restaurante' style='width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid #e0e0e0;'>";
            }
        }

        echo "<tr style='text-align: center; background-color: #fff;'>
                <td style='padding: 12px;'>{$row['id']}</td>
                <td style='padding: 12px;'>{$row['nombre']}</td>
                <td style='padding: 12px;'>{$row['direccion']}</td>
                <td style='padding: 12px;'>{$row['descripcion']}</td>
                <td style='padding: 12px;'>{$row['horario']}</td>
                <td style='padding: 12px;'>{$row['telefono']}</td>
                <td style='padding: 12px;'>{$row['correo']}</td>
                <td style='padding: 12px;'><a href='{$row['web']}' target='_blank'>Sitio</a></td>
                <td style='padding: 12px;'>{$row['redes_sociales']}</td>
                <td style='padding: 12px;'>{$row['admin_nombre']}</td>
                <td style='padding: 12px;'>$imagenHTML</td>
                <td style='padding: 12px; white-space: nowrap;'>
                    <button onclick=\"editarRest({$row['id']})\" style='background-color: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 5px; margin-right: 4px;'>Editar</button>
                    <button onclick=\"eliminarRest({$row['id']})\" style='background-color: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 5px;'>Eliminar</button>
                </td>
              </tr>";
    }

    echo "</tbody>
        </table>";

    // Navegación
    echo "<div class='paginacion' style='margin-top: 20px; text-align: center;'>";
    for ($i = 1; $i <= $total_paginas; $i++) {
        $active = ($i == $pagina) ? "font-weight: bold; color: #f39c12;" : "color: #3498db;";
        echo "<a href='#' class='paginacion-link' data-pagina='$i' style='margin: 0 5px; text-decoration: none; $active'>$i</a>";
    }
    echo "</div></div>";
} else {
    echo "<p style='padding: 20px;'>No hay restaurantes registrados.</p>";
}

$conn->close();
