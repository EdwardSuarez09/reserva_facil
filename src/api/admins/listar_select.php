<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

try {
    $sql = "SELECT id, nombre, rol FROM admin WHERE rol IN ('admin', 'superadmin') ORDER BY nombre";
    $result = $conn->query($sql);

    $options = '';
    while ($row = $result->fetch_assoc()) {
        $rol_badge = $row['rol'] === 'superadmin' ? ' (SuperAdmin)' : ' (Admin)';
        $options .= '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nombre']) . $rol_badge . '</option>';
    }

    echo $options;

} catch (Exception $e) {
    echo '<option value="">Error cargando administradores</option>';
}

$conn->close();
?> 