<?php

namespace Core\Helpers;

use Core\Helpers\SecurityLogger;

class AuthMiddleware
{
    /**
     * Rutas públicas que no requieren autenticación
     */
    private static $publicRoutes = [
        'home/index',
        'home/buscar',
        'home/detalleproducto',
        'home/busqueda',
        'auth/login',
        'auth/authenticate',
        'auth/getCsrfToken',  // ← Permitir obtener token CSRF sin autenticación
        'auth/registro',
        'auth/reenviarCodigo',
        'auth/procesarRegistro',
        'googleauth/login',
        'auth/oauth/google',
        'auth/oauth/google/callback',
        'auth/google-callback',
        'googleauth/callback',
        'auth/registrar',
        'error/notFound',
        'error/forbidden',
        'carrito/agregar',
        'carrito/ver',
        'carrito/actualizar',
        'carrito/eliminar',
        'carrito/aumentar',
        'carrito/disminuir',
        //PARÁMETROS
        'carrito/aumentar/',
        'carrito/disminuir/', 
        'carrito/eliminar/',
        // Rutas públicas para OAuth
        'googleauth/login',
        'auth/google-callback',
        // Buscador de productos público
        'producto/autocomplete',
        'producto/busqueda',
        // Rutas de pedidos públicas
        'pedido/precheckout',
        'pedido/aplicarCupon',
        'pedido/quitarCupon',
        // Permitir ver productos sin login
        'producto/ver',
        'producto/guardarComentario',
        //cookies
        'auth/aceptar-cookies',
        'auth/rechazar-cookies',
        'pago/direcciones',
        //'pago/crearPago',
        'pago/crear-pago-mercado-pago',
        'pago/exito',
        'pago/procesar-exito', // ✅ NUEVA
        'pago/error',
        'pago/pendiente',
        //RUTAS DE ADMIN
        'admin/login',
        'admin/authenticate',

        //  RUTAS DE FACEBOOK
        'facebookauth/login',
        'auth/facebook/login',
        'auth/facebook/callback',
    ];

    /**
     * Mapeo específico de controladores/acciones a permisos requeridos
     */
    private static $permissionMap = [
        'usuario' => 'usuarios',
        'rol' => 'usuarios',
        'categoria' => 'categorias',
        'producto' => 'productos',
        'etiqueta' => 'productos',
        'pedido' => 'pedidos',
        'cupon' => 'cupones',
        'promocion' => 'promociones',
        'adminpopup' => 'promociones', // ← AGREGAR
        'cargamasiva' => 'productos',  // ← AGREGAR  
        'adminreclamacion' => 'reportes', // ← AGREGAR
        'estadisticas' => 'reportes',
        'banner' => 'promociones' // ← SI USAS BANNERS
    ];

    /**
     * Verificar si la ruta actual requiere autenticación
     */
    public static function requiresAuth($url)
    {
        if (empty($url)) {
            return true;
        }

        $url = trim($url, '/');

        // Verificar si es una ruta pública
        foreach (self::$publicRoutes as $publicRoute) {
            if ($url === $publicRoute || strpos($url, $publicRoute) === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verificar autenticación y permisos
     */
    public static function checkAuth($url)
    {
        error_log("🔍 AuthMiddleware checking: " . $url);
        error_log("🔍 Public routes: " . json_encode(self::$publicRoutes));
        // Si la ruta no requiere autenticación, permitir acceso
        if (!self::requiresAuth($url)) {
            return true;
        }

        // Verificar si el usuario está autenticado
        if (!SessionHelper::isAuthenticated()) {
            header('Location: ' . url('/auth/login'));
            exit;
        }

        // Verificar permisos específicos
        return self::checkPermissions($url);
    }

    /**
     * Verificar permisos específicos para rutas administrativas
     */
    private static function checkPermissions($url)
    {
        $segments = explode('/', trim($url, '/'));
        $controller = strtolower($segments[0] ?? '');
        $action = strtolower($segments[1] ?? '');

        // Normalizar action: convertir guiones a camelCase sin guiones
        $action = str_replace('-', '', $action);

        // Rutas que solo requieren autenticación (sin permisos específicos)
        $authOnlyRoutes = ['auth', 'home'];
        if (in_array($controller, $authOnlyRoutes)) {
            return true;
        }

        // Rutas de pedido que solo requieren autenticación (no permisos administrativos)
        if ($controller === 'pedido') {
            $userActions = ['precheckout', 'checkout', 'registrar', 'confirmacion', 'aplicarcupon', 'quitarcupon'];
            if (in_array($action, $userActions)) {
                return true; // Solo requiere estar logueado, no permisos especiales
            }
            // Las demás acciones de pedido (listar, cambiarestado, etc.) sí requieren permisos
        }

        // Rutas de usuario específicas que solo requieren autenticación
        if ($controller === 'usuario') {
            // Rutas de gestión de direcciones - cualquier usuario autenticado puede gestionar sus propias direcciones
            $direccionesActions = ['misdirecciones', 'editardireccion', 'actualizardireccion', 'eliminardireccion'];
            if (in_array($action, $direccionesActions)) {
                return true; // Solo requiere estar autenticado
            }

            // Rutas de pedidos - requieren verificación adicional de rol
            $userActions = ['pedidos', 'detallepedido'];
            if (in_array($action, $userActions)) {
                // Verificación adicional: solo usuarios con rol 'usuario' o admins pueden ver pedidos
                $userRole = SessionHelper::getRole();
                $userPermissions = SessionHelper::getPermissions();

                // Permitir si es admin (tiene permiso usuarios) o si es cliente (rol usuario)
                $isAdmin = in_array('usuarios', $userPermissions ?: []);
                $isCliente = false;

                if (is_array($userRole) && isset($userRole['nombre'])) {
                    $isCliente = ($userRole['nombre'] === 'usuario');
                } elseif (is_string($userRole)) {
                    $isCliente = ($userRole === 'usuario');
                } else {
                    // Verificar por permisos - clientes típicamente solo tienen 'perfil'
                    $isCliente = in_array('perfil', $userPermissions ?: []) &&
                        !in_array('productos', $userPermissions ?: []);
                }

                if ($isAdmin || $isCliente) {
                    return true; // Admin puede ver todos los pedidos, cliente solo los suyos
                } else {
                    // Usuario staff sin permisos de usuarios no puede ver pedidos
                    SecurityLogger::log(SecurityLogger::ACCESS_DENIED, "Acceso denegado a pedidos para usuario staff", [
                        'user_id' => SessionHelper::getUser()['id'] ?? 'desconocido',
                        'rol' => $userRole,
                        'permissions' => $userPermissions,
                        'url' => $url
                    ]);
                    header('Location: ' . url('/error/forbidden'));
                    exit;
                }
            }
            // Las demás acciones de usuario (index, crear, editar, etc.) sí requieren permisos
        }

        // Verificar permisos específicos según el mapeo
        if (isset(self::$permissionMap[$controller])) {
            $requiredPermission = self::$permissionMap[$controller];

            // Verificar si el usuario tiene el permiso requerido
            if (!SessionHelper::hasPermission($requiredPermission)) {
                $usuario = SessionHelper::getUser();
                $rol = SessionHelper::getRole();

                // Registrar intento de acceso denegado
                SecurityLogger::log(SecurityLogger::ACCESS_DENIED, "Acceso denegado a '{$controller}'", [
                    'user_id' => $usuario['id'] ?? 'desconocido',
                    'email' => $usuario['email'] ?? 'desconocido',
                    'rol' => $rol['nombre'] ?? 'desconocido',
                    'permission_required' => $requiredPermission,
                    'url' => $url
                ]);

                error_log("❌ Usuario sin permiso '$requiredPermission' para acceder a '$controller'");
                header('Location: ' . url('/error/forbidden'));
                exit;
            }
        } else {
            // Para controladores no mapeados explícitamente, registrar una advertencia
            error_log("⚠️ Controlador no mapeado en permisos: '$controller'");
        }

        return true;
    }

    /**
     * Verificar si el usuario puede acceder a un recurso específico
     */
    public static function canAccess($resource, $action = 'read')
    {
        if (!SessionHelper::isAuthenticated()) {
            return false;
        }

        // Mapear recursos a permisos
        $resourcePermissions = [
            'usuarios' => 'gestionar_usuarios',
            'roles' => 'gestionar_roles',
            'categorias' => 'gestionar_categorias',
            'productos' => 'gestionar_productos',
            'pedidos' => 'gestionar_pedidos',
            'cupones' => 'gestionar_cupones',
            'promociones' => 'gestionar_promociones',
            'etiquetas' => 'gestionar_etiquetas',
            'carga_masiva' => 'carga_masiva',
            'carrito' => 'gestionar_carrito'
        ];

        $permission = $resourcePermissions[$resource] ?? null;

        if ($permission) {
            return SessionHelper::hasPermission($permission);
        }

        // Si no se especifica permiso, permitir acceso si está autenticado
        return true;
    }

    /**
     * Verificar si el usuario tiene rol de administrador
     */
    public static function isAdmin()
    {
        if (!SessionHelper::isAuthenticated()) {
            return false;
        }

        $user = SessionHelper::getUser();
        $rol = SessionHelper::getRole();

        return $rol && (
            $rol['nombre'] === 'admin' ||
            $rol['nombre'] === 'administrador' ||
            SessionHelper::hasPermission('administrar_sistema')
        );
    }

    /**
     * Middleware para proteger rutas de administrador
     */
    public static function requireAdmin()
    {
        if (!self::isAdmin()) {
            $usuario = SessionHelper::getUser();

            SecurityLogger::log(SecurityLogger::ACCESS_DENIED, "Intento de acceso a área de administrador", [
                'user_id' => $usuario['id'] ?? 'desconocido',
                'email' => $usuario['email'] ?? 'desconocido',
                'url' => $_SERVER['REQUEST_URI'] ?? 'desconocida'
            ]);

            header('Location: ' . url('/error/forbidden'));
            exit;
        }
    }

    /**
     * Verificar si el usuario puede modificar el recurso
     */
    public static function canModify($resource, $resourceId = null)
    {
        if (!SessionHelper::isAuthenticated()) {
            return false;
        }

        // Los administradores pueden modificar todo
        if (self::isAdmin()) {
            return true;
        }

        // Para usuarios normales, verificar permisos específicos
        return self::canAccess($resource, 'write');
    }
}
