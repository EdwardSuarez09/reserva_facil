<?php
// Archivo de prueba para verificar el footer
include __DIR__ . '/../src/config/conexion.php';

echo "<h2>Test de conexión y footer</h2>";

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
} else {
    echo "<p>✅ Conexión exitosa</p>";
}

// Verificar restaurantes en la base de datos
$sql = "SELECT nombre FROM restaurantes LIMIT 5";
$resultado = $conn->query($sql);

if ($resultado->num_rows > 0) {
    echo "<p>✅ Restaurantes encontrados:</p><ul>";
    while($row = $resultado->fetch_assoc()) {
        echo "<li>" . $row['nombre'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ No se encontraron restaurantes</p>";
}

// Probar con un restaurante específico
$nombreRestaurante = $_GET['restaurante'] ?? 'CALIPOLLO';
echo "<h3>Probando con restaurante: $nombreRestaurante</h3>";

$sql = "SELECT nombre, direccion, telefono, correo, web, redes_sociales, horario, descripcion FROM restaurantes WHERE nombre = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nombreRestaurante);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    echo "<p>✅ Datos encontrados:</p>";
    echo "<pre>" . print_r($row, true) . "</pre>";
    
    echo "<h4>JSON que se enviaría al footer:</h4>";
    echo "<pre>" . json_encode([
        "nombre" => $row['nombre'],
        "direccion" => $row['direccion'],
        "telefono" => $row['telefono'],
        "correo" => $row['correo'],
        "web" => $row['web'],
        "redes" => $row['redes_sociales'],
        "horario" => $row['horario'],
        "descripcion" => $row['descripcion']
    ], JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<p>❌ No se encontró el restaurante '$nombreRestaurante'</p>";
}

$stmt->close();
$conn->close();
?> 