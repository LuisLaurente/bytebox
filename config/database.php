<?php
// config/database.php

// 1. Detectar entorno (Mantenemos tu lógica original)
$isProduction = !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8080']) 
                && !str_contains($_SERVER['HTTP_HOST'], 'xampp');

// 2. Devolver configuración según el entorno usando las variables del .env
if ($isProduction) {
    return [
        'host'     => $_ENV['DB_HOST'] ?? 'localhost',
        'dbname'   => $_ENV['DB_NAME'] ?? 'ylxfwfte_bytebox',
        'username' => $_ENV['DB_USER'] ?? 'ylxfwfte_user', 
        'password' => $_ENV['DB_PASS'] ?? '', 
        'port'     => $_ENV['DB_PORT'] ?? 3306
    ];
} else {
    return [
        'host'     => $_ENV['DB_HOST_LOCAL'] ?? '127.0.0.1',
        'dbname'   => $_ENV['DB_NAME_LOCAL'] ?? 'ylxfwfte_bytebox',
        'username' => $_ENV['DB_USER_LOCAL'] ?? 'root',
        'password' => $_ENV['DB_PASS_LOCAL'] ?? '',
        'port'     => $_ENV['DB_PORT_LOCAL'] ?? 3306
    ];
}