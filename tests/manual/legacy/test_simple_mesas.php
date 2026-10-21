<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Test Simple de Mesas</h2>";

// Test 1: Conexión
echo "<h3>1. Test de Conexión</h3>";
try {
    include __DIR__ . '/../../../src/config/conexion.php';
    echo "✅ Conexión exitosa<br>";
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Consulta restaurante
echo "<h3>2. Test de Restaurante</h3>";
$restaurante = 'Calipollo';
$stmt = $conn->prepare("SELECT id, nombre FROM restaurantes WHERE nombre = ?");
$stmt->bind_param("s", $restaurante);
$stmt->execute();
$result = $stmt->get_result();
$rest_data = $result->fetch_assoc();

if ($rest_data) {
    echo "✅ Restaurante encontrado: ID=" . $rest_data['id'] . ", Nombre=" . $rest_data['nombre'] . "<br>";
} else {
    echo "❌ Restaurante no encontrado<br>";
}

// Test 3: Consulta mesas
echo "<h3>3. Test de Mesas</h3>";
$stmt = $conn->prepare("SELECT id, numero_mesa, capacidad FROM mesas WHERE capacidad >= ?");
$personas = 2;
$stmt->bind_param("i", $personas);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "✅ Mesas encontradas:<br>";
    while ($mesa = $result->fetch_assoc()) {
        echo "- Mesa " . $mesa['numero_mesa'] . " (ID: " . $mesa['id'] . ", Capacidad: " . $mesa['capacidad'] . ")<br>";
    }
} else {
    echo "❌ No se encontraron mesas<br>";
}

// Test 4: Respuesta JSON simple
echo "<h3>4. Test JSON</h3>";
$response = [
    'success' => true,
    'message' => 'Test exitoso',
    'restaurante' => $rest_data,
    'timestamp' => date('Y-m-d H:i:s')
];

echo "JSON que se generará:<br>";
echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";

$conn->close();
?> 