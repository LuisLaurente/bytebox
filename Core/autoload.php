<?php
spl_autoload_register(function($class) {
    // Convertir namespace a ruta de archivo
    $classPath = str_replace('\\', '/', $class);
    
    // Remover "Controllers/" duplicado si existe
    $classPath = preg_replace('/^Controllers\//', '', $classPath);
    
    // Posibles ubicaciones
    $possiblePaths = [
        __DIR__ . '/../Controllers/' . $classPath . '.php',
        __DIR__ . '/../Models/' . $classPath . '.php', 
        __DIR__ . '/../Core/' . $classPath . '.php',
        __DIR__ . '/../' . $classPath . '.php',
    ];
    
    foreach ($possiblePaths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    error_log("Autoload no pudo encontrar: " . $class);
    return false;
});

// Cargar helpers globales
require_once __DIR__ . '/Helpers/producto_helpers.php';