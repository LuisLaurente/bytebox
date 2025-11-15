<?php
// Detectar entorno automáticamente
$isProduction = !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8080']) 
                && !str_contains($_SERVER['HTTP_HOST'], 'xampp');

if ($isProduction) {
    // Configuración para producción (cPanel)
    return [
        'host' => 'localhost',
        'dbname' => 'ylxfwfte_bytebox',
        'username' => 'ylxfwfte_user', 
        'password' => '@Bytebox555',            // CAMBIAR: por tu contraseña
        'port' => 3306
    ];
} else {
    // Configuración para desarrollo local
    return [
        'host' => 'localhost:3307',    // 'host' => '127.0.0.1',
        'dbname' => 'ylxfwfte_bytebox', // 'dbname' => 'tecnovedades',
        'username' => 'root',   // 'username' => 'root
        'password' => '', // 'password' => '',
        'port' => 3307
    ];
}