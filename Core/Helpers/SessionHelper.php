<?php

namespace Core\Helpers;

use Core\Helpers\SecurityLogger;

class SessionHelper
{
    private static $sessionStarted = false;

    public static function start()
    {
        if (self::$sessionStarted) {
            return true;
        }

        if (session_status() === PHP_SESSION_NONE) {
            // Configurar cookies de sesión más robustas
            session_set_cookie_params([
                'lifetime' => 0, // Hasta que el navegador se cierre
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
                'secure' => isset($_SERVER['HTTPS']), // Solo HTTPS si está disponible
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            session_start();
            self::$sessionStarted = true;
            
            // Inicializar array de sesión si no existe
            if (!isset($_SESSION['_initialized'])) {
                $_SESSION = [];
                $_SESSION['_initialized'] = true;
                $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            }
        }
        
        return true;
    }

    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get($key)
    {
        self::start();
        return $_SESSION[$key] ?? null;
    }

    public static function destroy()
    {
        self::start();
        
        // Limpiar todas las variables de sesión
        $_SESSION = [];

        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destruir la sesión
        session_destroy();
        self::$sessionStarted = false;
    }

    public static function remove($key)
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Cerrar sesión
     */
    public static function logout()
    {
        self::start();

        // Limpiar todas las variables de sesión
        $_SESSION = [];

        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destruir la sesión
        session_destroy();
        self::$sessionStarted = false;
    }

    /**
     * Iniciar sesión de usuario
     */
    public static function login($usuario, $rol)
    {
        self::start();

        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);

        // Guardar datos del usuario en la sesión (formato individual)
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_name'] = $usuario['nombre'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_role_id'] = $usuario['rol_id'];
        $_SESSION['user_role_name'] = $rol['nombre'];
        $_SESSION['user_role_description'] = $rol['descripcion'];
        
        // Decodificar permisos desde JSON
        $permisos = json_decode($rol['permisos'], true);
        $_SESSION['user_permissions'] = is_array($permisos) ? $permisos : [];
        
        $_SESSION['user_active'] = $usuario['activo'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['authenticated'] = true;
        
        // Almacenar la IP del usuario (solo la clase de red para ser menos estricto)
        $ipParts = explode('.', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $_SESSION['user_ip'] = implode('.', array_slice($ipParts, 0, 2)) . '.x.x'; // Solo primeros 2 octetos
        
        // Almacenar solo el tipo de navegador, no el user-agent completo
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (stripos($userAgent, 'Chrome') !== false) {
            $_SESSION['user_agent'] = 'Chrome';
        } elseif (stripos($userAgent, 'Firefox') !== false) {
            $_SESSION['user_agent'] = 'Firefox';
        } elseif (stripos($userAgent, 'Safari') !== false) {
            $_SESSION['user_agent'] = 'Safari';
        } elseif (stripos($userAgent, 'Edge') !== false) {
            $_SESSION['user_agent'] = 'Edge';
        } else {
            $_SESSION['user_agent'] = 'Other';
        }
        
        // AGREGADO: Guardar también el usuario completo para compatibilidad
        $_SESSION['usuario'] = $usuario;
        $_SESSION['rol'] = $rol;
        
        error_log("✅ Usuario {$usuario['email']} logueado con rol {$rol['nombre']}");
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public static function isAuthenticated()
    {
        self::start();

        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            return false;
        }
        
        // ✅ VERIFICACIÓN RELAJADA de IP - solo primeros 2 octetos
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sessionIP = $_SESSION['user_ip'] ?? 'unknown';
        
        if ($sessionIP !== 'unknown' && $currentIP !== 'unknown') {
            $sessionIPParts = explode('.', $sessionIP);
            $currentIPParts = explode('.', $currentIP);
            
            // Solo verificar primeros 2 octetos
            if (count($sessionIPParts) >= 2 && count($currentIPParts) >= 2) {
                if ($sessionIPParts[0] !== $currentIPParts[0] || $sessionIPParts[1] !== $currentIPParts[1]) {
                    SecurityLogger::log(SecurityLogger::AUTH_ERROR, 'Cambio significativo de IP detectado', [
                        'user_id' => $_SESSION['user_id'] ?? 'desconocido',
                        'original_ip' => $sessionIP,
                        'current_ip' => $currentIP
                    ]);
                    
                    self::logout();
                    return false;
                }
            }
        }
        
        // ✅ VERIFICACIÓN RELAJADA de User-Agent - solo tipo de navegador
        $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sessionAgent = $_SESSION['user_agent'] ?? '';
        
        if (!empty($sessionAgent) && !empty($currentAgent)) {
            $getBrowser = function($agent) {
                if (stripos($agent, 'Chrome') !== false) return 'Chrome';
                if (stripos($agent, 'Firefox') !== false) return 'Firefox';
                if (stripos($agent, 'Safari') !== false) return 'Safari';
                if (stripos($agent, 'Edge') !== false) return 'Edge';
                return 'Other';
            };
            
            if ($sessionAgent !== $getBrowser($currentAgent)) {
                SecurityLogger::log(SecurityLogger::AUTH_ERROR, 'Cambio significativo de navegador detectado', [
                    'user_id' => $_SESSION['user_id'] ?? 'desconocido',
                    'original_agent' => $sessionAgent,
                    'current_agent' => $getBrowser($currentAgent)
                ]);
                
                self::logout();
                return false;
            }
        }

        // Verificar timeout de sesión (2 horas)
        $timeout = 2 * 60 * 60;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            SecurityLogger::log(SecurityLogger::SESSION_EXPIRED, 'Sesión expirada por inactividad', [
                'user_id' => $_SESSION['user_id'] ?? 'desconocido',
                'elapsed_time' => time() - $_SESSION['last_activity']
            ]);
            
            self::logout();
            return false;
        }

        // Actualizar último tiempo de actividad
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Obtener datos del usuario actual
     */
    public static function getUser()
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        // Primero intentar obtener el usuario completo
        if (isset($_SESSION['usuario'])) {
            return $_SESSION['usuario'];
        }

        // Fallback: construir desde datos individuales (para compatibilidad)
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'nombre' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'rol_id' => $_SESSION['user_role_id'] ?? null,
            'activo' => $_SESSION['user_active'] ?? null,
        ];
    }

    /**
     * Obtener datos del rol actual
     */
    public static function getRole()
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        // Primero intentar obtener el rol completo
        if (isset($_SESSION['rol'])) {
            $rol = $_SESSION['rol'];
            
            // Asegurar que los permisos estén decodificados
            if (isset($rol['permisos']) && is_string($rol['permisos'])) {
                $rol['permisos'] = json_decode($rol['permisos'], true) ?: [];
            }
            
            return $rol;
        }

        // Fallback: construir desde datos individuales (para compatibilidad)
        return [
            'id' => $_SESSION['user_role_id'] ?? null,
            'nombre' => $_SESSION['user_role_name'] ?? null,
            'descripcion' => $_SESSION['user_role_description'] ?? null,
            'permisos' => $_SESSION['user_permissions'] ?? [],
        ];
    }

    /**
     * Obtener permisos del usuario actual
     */
    public static function getPermissions()
    {
        if (!self::isAuthenticated()) {
            return [];
        }

        return $_SESSION['user_permissions'] ?? [];
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public static function hasPermission($permiso)
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        $permisos = self::getPermissions();
        $hasPermission = in_array($permiso, $permisos);
        
        error_log("🔍 Verificando permiso '$permiso' para usuario: " . ($hasPermission ? 'SÍ' : 'NO'));
        
        return $hasPermission;
    }

    /**
     * Verificar si el usuario es administrador
     */
    public static function isAdmin()
    {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        // Verificar si es admin por nombre de rol o tiene permiso de admin
        return $_SESSION['user_role_name'] === 'admin' || 
               self::hasPermission('admin') || 
               self::hasPermission('usuarios');  // Los usuarios con permiso de gestión de usuarios se consideran admin
    }

    /**
     * Obtener ID del usuario actual
     */
    public static function getUserId()
    {
        $user = self::getUser();
        return $user['id'] ?? null;
    }

    /**
     * Obtener nombre del usuario actual
     */
    public static function getUserName()
    {
        $user = self::getUser();
        return $user['nombre'] ?? null;
    }

    /**
     * Obtener email del usuario actual
     */
    public static function getUserEmail()
    {
        $user = self::getUser();
        return $user['email'] ?? null;
    }

    /**
     * Obtener ID del rol actual
     */
    public static function getRoleId()
    {
        return $_SESSION['user_role_id'] ?? null;
    }

    /**
     * Obtener nombre del rol actual
     */
    public static function getRoleName()
    {
        return $_SESSION['user_role_name'] ?? null;
    }

    /**
     * Actualizar datos del usuario en la sesión
     */
    public static function updateUser($usuario)
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        $_SESSION['user_name'] = $usuario['nombre'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_active'] = $usuario['activo'];

        return true;
    }

    /**
     * Verificar si el usuario puede acceder a una sección
     */
    public static function canAccess($seccion)
    {
        if (!self::isAuthenticated()) {
            return false;
        }

        // Los administradores pueden acceder a todo
        if (self::isAdmin()) {
            return true;
        }

        // Verificar permisos específicos
        return self::hasPermission($seccion);
    }

    /**
     * Obtener información completa de la sesión
     */
    public static function getSessionInfo()
    {
        return [
            'user_id' => self::getUserId(),
            'user_name' => self::getUserName(),
            'user_email' => self::getUserEmail(),
            'role_id' => self::getRoleId(),
            'role_name' => self::getRoleName(),
            'permissions' => self::getPermissions(),
            'is_authenticated' => self::isAuthenticated(),
            'login_time' => $_SESSION['login_time'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null
        ];
    }
}
