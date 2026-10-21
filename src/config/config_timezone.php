<?php
/**
 * Configuración de Zona Horaria para Colombia
 * Incluir este archivo al inicio de todos los scripts PHP
 */

// Configurar zona horaria de Colombia (GMT-5)
date_default_timezone_set('America/Bogota');

// Verificar que se configuró correctamente
if (date_default_timezone_get() !== 'America/Bogota') {
    // Fallback a GMT-5 si no funciona America/Bogota
    date_default_timezone_set('Etc/GMT+5');
}

// Variables útiles para debugging
define('TIMEZONE_COLOMBIA', 'America/Bogota');
define('TIMEZONE_OFFSET', '-05:00');

// Función para obtener fecha/hora actual en Colombia
function fecha_colombia($formato = 'Y-m-d') {
    return date($formato);
}

function hora_colombia($formato = 'H:i:s') {
    return date($formato);
}

function datetime_colombia($formato = 'Y-m-d H:i:s') {
    return date($formato);
}