<?php
include __DIR__ . '/../../config/conexion.php';

$nombre_restaurante = $_GET['restaurante'] ?? '';
if (!$nombre_restaurante) {
    echo "Restaurante no especificado.";
    exit;
}

// Obtener ID del restaurante
$sql_rest = "SELECT id FROM restaurantes WHERE nombre = ?";
$stmt_rest = $conn->prepare($sql_rest);
$stmt_rest->bind_param("s", $nombre_restaurante);
$stmt_rest->execute();
$result_rest = $stmt_rest->get_result();
if ($result_rest->num_rows === 0) {
    echo "Restaurante no encontrado.";
    exit;
}
$id_restaurante = $result_rest->fetch_assoc()['id'];

// Obtener categorías con platos asignados
$sql = "SELECT c.id, c.nombre AS categoria
        FROM categorias c
        JOIN platosmenu p ON p.id_categoria = c.id
        WHERE p.id_restaurante = ?
        GROUP BY c.id, c.nombre";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_restaurante);
$stmt->execute();
$categorias = $stmt->get_result();

$html = "";

while ($cat = $categorias->fetch_assoc()) {
    $html .= "<h3 class='nombre-categoria'>{$cat['categoria']}</h3>";
    $html .= "<div class='carrusel'>";
    
    // Obtener platos de esta categoría
    $sql_platos = "SELECT nombre, descripcion, precio, imagen FROM platosmenu
                   WHERE id_restaurante = ? AND id_categoria = ?";
    $stmt_platos = $conn->prepare($sql_platos);
    $stmt_platos->bind_param("ii", $id_restaurante, $cat['id']);
    $stmt_platos->execute();
    $result_platos = $stmt_platos->get_result();

    while ($row = $result_platos->fetch_assoc()) {
        $img = $row['imagen']
            ? "<img src='data:image/jpeg;base64," . base64_encode($row['imagen']) . "' alt='Plato'>"
            : "<div class='sin-imagen'>Sin imagen</div>";

        $html .= "<div class='plato-carta'>
                    $img
                    <h4>{$row['nombre']}</h4>
                    <p>{$row['descripcion']}</p>
                    <span class='precio'>$ " . number_format($row['precio'], 0, ',', '.') . "</span>
                  </div>";
    }

    $html .= "</div>"; // .carrusel
}

echo $html;

$stmt->close();
$conn->close();
?>
