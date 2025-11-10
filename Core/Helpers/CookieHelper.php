<?php
namespace Core\Helpers;

class CookieHelper
{
    /**
     * Establecer una cookie segura
     */
    public static function set($name, $value, $expiry_days = 30, $path = '/')
    {
        $expiry = time() + ($expiry_days * 24 * 60 * 60);
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return setcookie($name, $value, $expiry, $path, $domain, $secure, true);
    }
    
    /**
     * Obtener valor de cookie
     */
    public static function get($name)
    {
        return $_COOKIE[$name] ?? null;
    }
    
    /**
     * Verificar si existe una cookie
     */
    public static function exists($name)
    {
        return isset($_COOKIE[$name]);
    }
    
    /**
     * Eliminar una cookie
     */
    public static function delete($name, $path = '/')
    {
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        setcookie($name, '', time() - 3600, $path, $domain);
        unset($_COOKIE[$name]);
    }
    
    /**
     * Verificar consentimiento de cookies
     */
    public static function hasConsent()
    {
        return self::exists('cookies_consent') && self::get('cookies_consent') === '1';
    }
}
?>