<?php
function url($path = '') {
    // Detecta si estás en localhost o en un dominio
    $isLocalhost = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);

    // Establece la base según el entorno
    $base = $isLocalhost 
        ? '/TECNOVEDADES/public/'    // Tu base en local
        : '/';                       // En producción, asume raíz del subdominio

    // Devuelve la URL completa
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}
