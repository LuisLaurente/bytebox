<?php
// debug_timezone_detallado.php en la carpeta public

echo "=== RASTREO DETALLADO ZONA HORARIA ===\n\n";

// Paso 1: Estado inicial
echo "1. INICIO - Zona horaria: " . date_default_timezone_get() . "\n";

// Paso 2: Después de session_start()
session_start();
echo "2. Después de session_start(): " . date_default_timezone_get() . "\n";

// Paso 3: Después de composer autoload
require_once __DIR__ . '/../vendor/autoload.php';
echo "3. Después de vendor/autoload: " . date_default_timezone_get() . "\n";

// Paso 4: Después de core autoload  
require_once __DIR__ . '/../Core/autoload.php';
echo "4. Después de Core/autoload: " . date_default_timezone_get() . "\n";

// Paso 5: Después de cada helper
$helpers = [
    '/../Core/Helpers/urlHelper.php',
    '/../Core/Helpers/Sanitizer.php', 
    '/../Core/Helpers/SessionHelper.php',
    '/../Core/Helpers/AuthMiddleware.php',
    '/../Core/Helpers/Validator.php',
    '/../Core/Helpers/CsrfHelper.php',
    '/../Core/Helpers/CuponHelper.php',
    '/../Core/Helpers/PromocionHelper.php'
];

foreach ($helpers as $helper) {
    require_once __DIR__ . $helper;
    echo "5. Después de " . basename($helper) . ": " . date_default_timezone_get() . "\n";
}

// Paso 6: Después de cambiar zona horaria
date_default_timezone_set('America/Lima');
echo "6. Después de cambiar a America/Lima: " . date_default_timezone_get() . "\n";

// Paso 7: Después del router
use Core\Router;
$router = new Router();
echo "7. Después de crear Router: " . date_default_timezone_get() . "\n";

echo "\n🎯 PUNTO DONDE SE CAMBIA: Buscar después del paso donde cambia de America/Lima a Europe/Berlin\n";