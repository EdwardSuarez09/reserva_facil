<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarRol($rolesPermitidos = []) {
    $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $projectPath = preg_replace('~/src(?:/.*)?$~i', '', $scriptPath);
    $loginUrl = rtrim((string) $projectPath, '/') . '/public/html/login.html';

    if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
        header('Location: ' . $loginUrl);
        exit;
    }

    if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
        header('Location: ' . $loginUrl);
        exit;
    }
}
?>
