<?php
session_start();
echo "<h2>Debug de Sesión</h2>";

echo "<h3>Variables de sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Estado de la sesión:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

echo "<h3>Prueba de verificar_sesion_cliente.php:</h3>";
include __DIR__ . '/../../../src/helpers/verificar_sesion_cliente.php';
?> 