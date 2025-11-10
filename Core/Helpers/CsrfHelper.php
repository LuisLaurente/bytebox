<?php
namespace Core\Helpers;

/**
 * Helper para manejar la protección contra ataques CSRF (Cross-Site Request Forgery)
 */
class CsrfHelper
{
    /**
     * Genera un token CSRF y lo guarda en sesión
     * @param string $formName Nombre opcional del formulario para tener múltiples tokens
     * @return string Token generado
     */
    public static function generateToken($formName = 'default')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        // Generar un token aleatorio único
        $token = bin2hex(random_bytes(32));
        
        // Guardar el token en la sesión con timestamp para posible expiración
        $_SESSION['csrf_tokens'][$formName] = [
            'token' => $token,
            'timestamp' => time()
        ];
        
        return $token;
    }
    
    /**
     * Verifica si un token CSRF es válido
     * @param string $token Token a verificar
     * @param string $formName Nombre del formulario asociado al token
     * @param bool $removeAfterValidation Si true, elimina el token después de validarlo (recomendado para formularios)
     * @param int $maxAge Edad máxima del token en segundos (0 = sin expiración)
     * @return bool True si el token es válido
     */
    public static function validateToken($token, $formName = 'default', $removeAfterValidation = true, $maxAge = 3600)
    {
        if (empty($_SESSION['csrf_tokens'][$formName])) {
            return false;
        }
        
        $storedData = $_SESSION['csrf_tokens'][$formName];
        $valid = hash_equals($storedData['token'], $token);
        
        // Verificar si el token ha expirado (si se especifica maxAge > 0)
        if ($maxAge > 0 && (time() - $storedData['timestamp']) > $maxAge) {
            // El token ha expirado
            unset($_SESSION['csrf_tokens'][$formName]);
            return false;
        }
        
        // Si se solicitó, eliminar el token después de usarlo (one-time use)
        if ($valid && $removeAfterValidation) {
            unset($_SESSION['csrf_tokens'][$formName]);
        }
        
        return $valid;
    }
    
    /**
     * Genera un campo input hidden con el token CSRF
     * @param string $formName Nombre del formulario 
     * @return string HTML del campo input
     */
    public static function tokenField($formName = 'default')
    {
        $token = self::generateToken($formName);
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Limpia tokens CSRF antiguos (para mantenimiento)
     * @param int $maxAge Edad máxima en segundos
     */
    public static function cleanExpiredTokens($maxAge = 3600)
    {
        if (empty($_SESSION['csrf_tokens'])) {
            return;
        }
        
        $now = time();
        foreach ($_SESSION['csrf_tokens'] as $form => $data) {
            if (($now - $data['timestamp']) > $maxAge) {
                unset($_SESSION['csrf_tokens'][$form]);
            }
        }
    }
}
