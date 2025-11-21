<?php
ob_start();
// ===========================
// CONFIGURACIÓN INICIAL
// ===========================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuración segura de cookies de sesión
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', '1');
}

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
// AUTOLOAD (PRIMERO PARA PODER USAR LAS CLASES)
// ===========================
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Core/autoload.php';

// DEBUG: Verificar autoload
error_log("=== AUTOLOAD DEBUG ===");
error_log("Vendor autoload: " . (file_exists(__DIR__ . '/../vendor/autoload.php') ? 'EXISTE' : 'NO EXISTE'));
error_log("Core autoload: " . (file_exists(__DIR__ . '/../Core/autoload.php') ? 'EXISTE' : 'NO EXISTE'));

// Probar si una clase existe
if (class_exists('Controllers\HomeController')) {
    error_log("✅ HomeController cargado correctamente");
} else {
    error_log("❌ HomeController NO se puede cargar");
}

// ===========================
// HELPERS (AHORA SÍ PODEMOS USAR LAS CLASES)
// ===========================
require_once __DIR__ . '/../Core/Helpers/urlHelper.php';
require_once __DIR__ . '/../Core/Helpers/Sanitizer.php';
require_once __DIR__ . '/../Core/Helpers/AuthMiddleware.php';
require_once __DIR__ . '/../Core/Helpers/Validator.php';
require_once __DIR__ . '/../Core/Helpers/CsrfHelper.php';
require_once __DIR__ . '/../Core/Helpers/CuponHelper.php';
require_once __DIR__ . '/../Core/Helpers/PromocionHelper.php';
require_once __DIR__ . '/../Core/Helpers/CartPersistenceHelper.php';

// ===========================
// INICIALIZAR SESIÓN (DESPUÉS DE CARGAR HELPERS)
// ===========================
require_once __DIR__ . '/../Core/Helpers/SessionHelper.php';
\Core\Helpers\SessionHelper::start();

// ===========================
// SISTEMA DE COOKIES PERSISTENTES (DESPUÉS DE SESIÓN)
// ===========================
use Core\Helpers\RememberMeHelper;
use Controllers\CarritoController;

// 1. Primero auto-login con cookies "Recordarme"
RememberMeHelper::processAutoLogin();

// ===========================
// OBTENER RUTA LIMPIA
// ===========================
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
if ($url === '') {
    $url = 'home/index';
}

// ===========================
// SINCRONIZACIÓN INTELIGENTE DE CARRITO
// ===========================
// 2. Sincronizar carrito persistente SOLO cuando:
// - No acabamos de vaciar el carrito (post-pago)
// - Y es una ruta donde el usuario espera ver su carrito
$rutasParaSincronizar = [
    'carrito/ver', 
    'home/index', 
    'producto/ver',
    'producto/busqueda',
    'home/busqueda',
    'home/buscar',
    'home/detalleproducto'
];

// Rutas que NO deben sincronizar (pagos, admin, etc.)
$rutasExcluidas = [
    'pago/crear-pago-mercado-pago',
    'pago/exito',
    'pago/procesar-exito',
    'pago/error',
    'pago/pendiente',
    'pago/webhook',
    'pedido/confirmacion',
    'admin/'
];

$debeSincronizar = false;

// Verificar si la ruta actual debe sincronizar
foreach ($rutasParaSincronizar as $rutaValida) {
    if ($url === $rutaValida || strpos($url, $rutaValida . '/') === 0) {
        $debeSincronizar = true;
        break;
    }
}

// Verificar si es una ruta de producto
if (strpos($url, 'producto/') === 0) {
    $debeSincronizar = true;
}

// Excluir rutas específicas
foreach ($rutasExcluidas as $rutaExcluida) {
    if ($url === $rutaExcluida || strpos($url, $rutaExcluida) === 0) {
        $debeSincronizar = false;
        break;
    }
}

// Ejecutar sincronización solo si cumple todas las condiciones
if ($debeSincronizar && (!isset($_SESSION['carrito_vaciado']) || $_SESSION['carrito_vaciado'] !== true)) {
    CarritoController::sincronizarCarritoDesdeBD();
    error_log("🔄 Sincronización inteligente ejecutada para: " . $url);
}

// ===========================
// MIDDLEWARE DE AUTENTICACIÓN
// ===========================
// 📢 CRÍTICO: Lista de rutas que NO requieren que el usuario esté logueado (públicas)
$public_unauthenticated_routes = [
    'admin/login',
    'admin/authenticate', // Permitir que el admin se autentique
    'auth/reenviarCodigo', // Permitir el POST de reenvío
    'auth/verificarCodigoRegistro',
    'auth/iniciarRecuperacion',
    'auth/verificarCodigoRecuperacion',
    'auth/finalizarRecuperacion',
    'auth/registro',       // Permitir el GET a la página de registro
    'auth/login',          // Permitir el GET a la página de login
    'auth/authenticate',   // Permitir el POST de login
    'home/index',          // Home (generalmente pública)
    // Agregue aquí cualquier otra ruta que deba ser pública (ej. 'producto/ver')
];

// Comprobación: Si la URL NO está en la lista de rutas públicas y NO es una ruta de administración
if (!in_array($url, $public_unauthenticated_routes) && strpos($url, 'admin/') !== 0) {
    \Core\Helpers\AuthMiddleware::checkAuth($url);
}

// ===========================
// ROUTER
// ===========================
use Core\Router;

$router = new Router();
$router->handleRequest($url);