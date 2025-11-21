<?php

namespace Core;

require_once __DIR__ . '/../Controllers/BaseController.php';
require_once __DIR__ . '/Helpers/AuthMiddleware.php'; // ✅ Añadir esta línea

use Core\Helpers\SessionHelper;
use Core\Helpers\AuthMiddleware;

class Router
{
    // TODAS las rutas en un solo lugar
    private $routes = [
        // Google Auth
        'googleauth/login' => ['controller' => 'GoogleAuthController', 'method' => 'login'],
        'auth/oauth/google' => ['controller' => 'GoogleAuthController', 'method' => 'login'],
        'auth/oauth/google/callback' => ['controller' => 'GoogleAuthController', 'method' => 'callback'],

        // ========== FACEBOOK AUTH ==========
        'facebookauth/login' => ['controller' => 'FacebookAuthController', 'method' => 'login'], // ← NUEVA
        'auth/facebook/login' => ['controller' => 'FacebookAuthController', 'method' => 'login'],
        'auth/facebook/callback' => ['controller' => 'FacebookAuthController', 'method' => 'callback'],

        // === NUEVAS RUTAS DE REGISTRO AJAX ===
        'auth/iniciarRegistro' => ['controller' => 'AuthController', 'method' => 'iniciarRegistro'],
        'auth/verificarCodigoRegistro' => ['controller' => 'AuthController', 'method' => 'verificarCodigoRegistro'],
        'auth/reenviarCodigo' => ['controller' => 'AuthController', 'method' => 'reenviarCodigo'], // <-- NUEVA RUTA SÍNCRONA
        // ======================================

        // === NUEVAS RUTAS PARA RECUPERACION DE CUENTA ===
        'auth/iniciarRecuperacion' => ['controller' => 'AuthController', 'method' => 'iniciarRecuperacion'],
        'auth/verificarCodigoRecuperacion' => ['controller' => 'AuthController', 'method' => 'verificarCodigoRecuperacion'],
        'auth/finalizarRecuperacion' => ['controller' => 'AuthController', 'method' => 'finalizarRecuperacion'],
        // ======================================

        // Pagos
        'pago/crear-pago-mercado-pago' => ['controller' => 'PagoController', 'method' => 'crearPagoMercadoPago'],
        'pago/exito' => ['controller' => 'PagoController', 'method' => 'exito'],
        'pago/procesar-exito' => ['controller' => 'PagoController', 'method' => 'procesarExito'],
        'pago/error' => ['controller' => 'PagoController', 'method' => 'error'],
        'pago/pendiente' => ['controller' => 'PagoController', 'method' => 'pendiente'],

        'pago/webhook' => ['controller' => 'PagoController', 'method' => 'webhook'],

        // Admin
        'admin/login' => ['controller' => 'AdminAuthController', 'method' => 'login'],
        'admin/authenticate' => ['controller' => 'AdminAuthController', 'method' => 'authenticate'],
        'admin/dashboard' => ['controller' => 'AdminDashboardController', 'method' => 'index'],
        'admin/logout' => ['controller' => 'AdminAuthController', 'method' => 'logout'],

        // ========== RUTAS FALTANTES ==========
        'adminpopup' => ['controller' => 'AdminPopupController', 'method' => 'index'],
        'cargamasiva' => ['controller' => 'CargaMasivaController', 'method' => 'index'],
        'adminreclamacion' => ['controller' => 'AdminReclamacionController', 'method' => 'index'],
    ];

    public function handleRequest($url)
    {
        $url = trim($url, '/');

        error_log("🔄 === ROUTER DEBUG ===");
        error_log("🔄 URL solicitada: " . $url);
        error_log("🔄 Todas las rutas definidas: " . json_encode(array_keys($this->routes)));

        error_log("🔄 === INICIO ROUTER ===");
        error_log("🔄 URL recibida: " . $url);

        // ✅ PRIMERO: Aplicar AuthMiddleware ANTES de routing
        error_log("🔐 Ejecutando AuthMiddleware...");
        AuthMiddleware::checkAuth($url);
        error_log("✅ AuthMiddleware pasado");

        $segments = explode('/', $url);
        error_log("🔍 Segmentos: " . implode(', ', $segments));

        // ==========================
        // ✅ PRIMERO: Verificar rutas definidas
        // ==========================
        if (isset($this->routes[$url])) {
            $route = $this->routes[$url];
            $this->loadController($route['controller'], $route['method']);
            return;
        }

        // ==========================
        // ✅ RUTAS CON PARÁMETROS
        // ==========================
        if (str_starts_with($url, 'pago/exito/')) {
            $pedidoId = str_replace('pago/exito/', '', $url);
            $this->loadController('PagoController', 'exito', [$pedidoId]);
            return;
        }

        if (str_starts_with($url, 'pedido/confirmacion/')) {
            $pedidoId = str_replace('pedido/confirmacion/', '', $url);
            $this->loadController('PedidoController', 'confirmacion', [$pedidoId]);
            return;
        }

        // ==========================
        // ✅ RUTA NORMAL MVC
        // ==========================
        $controllerName = ucfirst($segments[0] ?? 'home') . 'Controller';
        $methodName = $segments[1] ?? 'index';
        $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $methodName))));
        $params = array_slice($segments, 2);

        $this->loadController($controllerName, $methodName, $params);
    }

    private function loadController($controllerName, $methodName, $params = [])
    {
        $controllerClass = 'Controllers\\' . $controllerName;
        $controllerFile = __DIR__ . '/../Controllers/' . $controllerName . '.php';

        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                if (method_exists($controllerInstance, $methodName)) {
                    call_user_func_array([$controllerInstance, $methodName], $params);
                    return;
                }
            }
        }

        require_once __DIR__ . '/../Controllers/ErrorController.php';
        $errorController = new \Controllers\ErrorController();
        $errorController->notFound();
    }
}
