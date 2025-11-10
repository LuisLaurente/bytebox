<?php
namespace Core\Helpers;
use Models\CarritoTemporal;
class CartPersistenceHelper
{
    /**
     * Obtener ID de sesión para carrito (usa cookie si tiene consentimiento)
     */
    public static function getCartSessionId()
    {
        // Si tiene consentimiento de cookies, usar cookie persistente
        if (CookieHelper::hasConsent() && CookieHelper::exists('cart_session')) {
            return CookieHelper::get('cart_session');
        }
        
        // Si no hay consentimiento, usar sesión PHP normal
        if (SessionHelper::get('cart_session_id')) {
            return SessionHelper::get('cart_session_id');
        }
        
        // Generar nuevo ID de sesión para carrito
        $session_id = bin2hex(random_bytes(16));
        
        if (CookieHelper::hasConsent()) {
            // Guardar en cookie por 1 año
            CookieHelper::set('cart_session', $session_id, 365);
        } else {
            // Guardar solo en sesión PHP
            SessionHelper::set('cart_session_id', $session_id);
        }
        
        return $session_id;
    }
    
    /**
     * Transferir carrito de invitado a usuario registrado - CORREGIDO
     */
    public static function transferGuestCartToUser($user_id)
    {
        $session_id = self::getCartSessionId();
        
        // Usar el modelo CarritoTemporal correctamente
        $carritoModel = new \Models\CarritoTemporal(); 
        return $carritoModel->transferirAUsuario($session_id, $user_id);
    }
    
    /**
     * Obtener carrito actual (combinando sesión y usuario) - CORREGIDO
     */
    public static function getCurrentCart()
    {
        $session_id = self::getCartSessionId();
        $user_id = SessionHelper::getUserId();
        
        $carritoModel = new \Models\CarritoTemporal(); 
        return $carritoModel->obtenerCarrito($session_id, $user_id);
    }
}
?>