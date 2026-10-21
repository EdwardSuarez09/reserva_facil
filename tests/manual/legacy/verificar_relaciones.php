<?php
include __DIR__ . '/../../../src/config/conexion.php';

echo "<h2>ANÁLISIS DE RELACIONES</h2>";

echo "<h3>1. Restaurantes disponibles:</h3>";
$result = $conn->query("SELECT id, nombre FROM restaurantes LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Nombre</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row['id'] . "</td><td>" . $row['nombre'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "No hay restaurantes o error: " . $conn->error;
}

echo "<h3>2. Estructura tabla RESERVAS:</h3>";
$result = $conn->query("DESCRIBE reservas");
if ($result) {
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td><strong>" . $row['Field'] . "</strong></td><td>" . $row['Type'] . "</td></tr>";
    }
    echo "</table>";
}

echo "<h3>3. Estructura tabla MESAS:</h3>";
$result = $conn->query("DESCRIBE mesas");
if ($result) {
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td><strong>" . $row['Field'] . "</strong></td><td>" . $row['Type'] . "</td></tr>";
    }
    echo "</table>";
}

echo "<h3>4. Contenido tabla MESAS:</h3>";
$result = $conn->query("SELECT * FROM mesas");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    // Headers
    $fields = $result->fetch_fields();
    echo "<tr>";
    foreach ($fields as $field) {
        echo "<th>" . $field->name . "</th>";
    }
    echo "</tr>";
    
    // Data
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No hay mesas registradas o error: " . $conn->error;
}

echo "<h3>5. Test de búsqueda como en el código:</h3>";
$restaurante_nombre = 'Calipollo';
$personas = 2;

echo "<p><strong>Buscando restaurante '$restaurante_nombre':</strong></p>";
$stmt = $conn->prepare("SELECT id, nombre FROM restaurantes WHERE nombre = ?");
$stmt->bind_param("s", $restaurante_nombre);
$stmt->execute();
$result = $stmt->get_result();
$restaurante_data = $result->fetch_assoc();

if ($restaurante_data) {
    echo "<p>✅ Restaurante encontrado: ID=" . $restaurante_data['id'] . ", Nombre=" . $restaurante_data['nombre'] . "</p>";
    
    echo "<p><strong>Buscando mesas con capacidad >= $personas:</strong></p>";
    $stmt2 = $conn->prepare("SELECT id, numero_mesa, capacidad FROM mesas WHERE capacidad >= ?");
    $stmt2->bind_param("i", $personas);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    if ($result2->num_rows > 0) {
        echo "<table border='1'><tr><th>ID Mesa</th><th>Número</th><th>Capacidad</th></tr>";
        while ($mesa = $result2->fetch_assoc()) {
            echo "<tr><td>" . $mesa['id'] . "</td><td>" . $mesa['numero_mesa'] . "</td><td>" . $mesa['capacidad'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No se encontraron mesas con capacidad >= $personas</p>";
    }
} else {
    echo "<p>❌ Restaurante '$restaurante_nombre' no encontrado</p>";
}

$conn->close();
?> 