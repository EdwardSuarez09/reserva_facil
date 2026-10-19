<?php
include __DIR__ . '/../../config/conexion.php';
include __DIR__ . '/../../helpers/verificar_rol.php';
verificarRol(['superadmin']);

$sql = "SELECT id, nombre, correo, rol FROM admin";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table>
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>";

    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $nombre = htmlspecialchars($row['nombre']);
        $correo = htmlspecialchars($row['correo']);
        $rol = htmlspecialchars($row['rol']);

        echo "<tr>
                <td>$nombre</td>
                <td>$correo</td>
                <td>$rol</td>
                <td>
                  <button onclick='editarAdmin($id)'>🖉 Editar</button>
                  <button onclick='eliminarAdmin($id)'>🗑️ Eliminar</button>
                </td>
              </tr>";
    }

    echo "</tbody></table>";
} else {
    echo "No hay administradores registrados.";
}

$conn->close();
?>
