<?php
// public/pruebaMercado.php

// ===========================
// CONFIGURACIÓN INICIAL
// ===========================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Zona horaria
date_default_timezone_set('America/Lima');

// ===========================
// CARGAR VARIABLES DE ENTORNO (.env)
// ===========================
require_once __DIR__ . '/../Core/LoadEnv.php';
try {
    Core\LoadEnv::load();
} catch (Exception $e) {
    error_log("Error loading .env: " . $e->getMessage());
}

// ===========================
// AUTOLOAD
// ===========================
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Core/autoload.php';

echo "🔍 Iniciando prueba de MercadoPago...<br>";

use Core\Services\MercadoPagoService;

try {
    $mpService = new MercadoPagoService();
    
    echo "✅ MercadoPagoService instanciado correctamente<br>";
    
    // Obtener información del SDK
    $info = $mpService->getSDKInfo();
    
    echo "<h3>Información del SDK:</h3>";
    echo "<pre>";
    print_r($info);
    echo "</pre>";
    
    // Probar conexión
    echo "<h3>Probando conexión con MercadoPago...</h3>";
    $result = $mpService->probarConexion();
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "🎉 ¡Conexión exitosa!<br>";
    } else {
        echo "❌ Error en conexión: " . $result['error'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}