<?php
function normalizarRutaImagenPublica(string $ruta): string
{
    $ruta = str_replace('\\', '/', ltrim($ruta, '/\\'));

    if (strpos($ruta, 'Recursos/Imagenes/') === 0) {
        return 'public/assets/img/Imagenes/' . substr($ruta, strlen('Recursos/Imagenes/'));
    }

    if (strpos($ruta, 'imagenes/') === 0) {
        return 'public/assets/img/' . substr($ruta, strlen('imagenes/'));
    }

    return $ruta;
}

function obtenerRutaImagenArchivo(string $ruta): string
{
    $ruta = normalizarRutaImagenPublica($ruta);
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $ruta);
}

function obtenerUrlImagenPublica(string $ruta): string
{
    $projectUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/', 4), '/');
    return $projectUrl . '/' . normalizarRutaImagenPublica($ruta);
}
