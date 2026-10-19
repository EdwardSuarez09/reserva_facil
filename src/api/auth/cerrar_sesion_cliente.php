<?php
session_start();
header('Content-Type: application/json');

// Limpiar variables de sesión de cliente
unset($_SESSION['cliente_id']);
unset($_SESSION['cliente_nombre']);
unset($_SESSION['cliente_email']);
unset($_SESSION['cliente_autenticado']);

// Limpiar variables de sesión de admin (por si viene de login principal)
unset($_SESSION['id']);
unset($_SESSION['rol']);

// Destruir completamente la sesión para asegurar limpieza total
session_destroy();

echo json_encode(['success' => true, 'mensaje' => 'Sesión cerrada correctamente']);
?> 