<?php
namespace Core\Helpers;

use Core\Database;
use Models\CarritoTemporal;

class RememberMeHelper
{
    /**
     * Generar token seguro
     */
    public static function generateToken()
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Crear cookie de "Recordarme"
     */
    public static function setRememberCookie($user_id, $token)
    {
        $cookie_value = $user_id . ':' . $token;
        return CookieHelper::set('remember_me', $cookie_value, 30); // 30 días
    }
    
    /**
     * Validar token de recordarme - CORREGIDO
     */
    public static function validateToken($user_id, $token)
    {
        try {
            // Usar tu conexión existente de Database
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Buscar token válido (no expirado)
            $query = "SELECT ut.*, u.activo 
                     FROM usuario_tokens ut
                     JOIN usuarios u ON ut.usuario_id = u.id
                     WHERE ut.usuario_id = :user_id 
                     AND ut.token = :token 
                     AND (ut.expira_en IS NULL OR ut.expira_en > NOW())
                     AND u.activo = 1";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':user_id' => $user_id,
                ':token' => $token
            ]);
            
            return $stmt->rowCount() > 0;
            
        } catch (\Exception $e) {
            error_log("❌ Error validando remember token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Procesar auto-login con cookie remember_me - CORREGIDO
     */
    public static function processAutoLogin()
    {
        // Solo si no hay sesión activa pero existe cookie remember_me
        if (!SessionHelper::isAuthenticated() && CookieHelper::exists('remember_me')) {
            
            $cookie_value = CookieHelper::get('remember_me');
            $parts = explode(':', $cookie_value);
            
            if (count($parts) === 2) {
                list($user_id, $token) = $parts;
                
                if (self::validateToken($user_id, $token)) {
                    // Usar tu conexión existente
                    $db = Database::getInstance();
                    $conn = $db->getConnection();
                    
                    // Obtener usuario
                    $userQuery = "SELECT * FROM usuarios WHERE id = :id AND activo = 1";
                    $userStmt = $conn->prepare($userQuery);
                    $userStmt->execute([':id' => $user_id]);
                    $usuario = $userStmt->fetch(\PDO::FETCH_ASSOC);
                    
                    // Obtener rol
                    $rolQuery = "SELECT * FROM roles WHERE id = :id";
                    $rolStmt = $conn->prepare($rolQuery);
                    $rolStmt->execute([':id' => $usuario['rol_id']]);
                    $rol = $rolStmt->fetch(\PDO::FETCH_ASSOC);
                    
                    if ($usuario && $rol) {
                        // Iniciar sesión
                        SessionHelper::login($usuario, $rol);
                        
                        // Transferir carrito de invitado a usuario
                        CartPersistenceHelper::transferGuestCartToUser($user_id);
                        
                        return true;
                    }
                }
            }
            
            // Token inválido, eliminar cookie
            CookieHelper::delete('remember_me');
        }
        
        return false;
    }
}
?>