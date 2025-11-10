<?php
namespace Core\Helpers;

/**
 * Helper para registro de eventos de seguridad
 */
class SecurityLogger
{
    // Tipos de eventos de seguridad
    const LOGIN_SUCCESS = 'login_success';
    const LOGIN_FAIL = 'login_fail';
    const LOGOUT = 'logout';
    const PASSWORD_CHANGE = 'password_change';
    const PASSWORD_RESET = 'password_reset';
    const AUTH_ERROR = 'auth_error';
    const ACCESS_DENIED = 'access_denied';
    const CSRF_ERROR = 'csrf_error';
    const ACCOUNT_LOCKED = 'account_locked';
    const SESSION_EXPIRED = 'session_expired';
    
    /**
     * Registra un evento de seguridad
     * @param string $type Tipo de evento
     * @param string $message Descripción del evento
     * @param array $context Datos adicionales
     * @return bool Éxito del registro
     */
    public static function log($type, $message, array $context = [])
    {
        $userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 'anónimo';
        $userEmail = isset($_SESSION['user']['email']) ? $_SESSION['user']['email'] : 'desconocido';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
        
        // Generar datos base del evento
        $eventData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'user_id' => $userId,
            'user_email' => $userEmail,
            'ip' => $ip,
            'message' => $message,
            'context' => !empty($context) ? json_encode($context) : '{}'
        ];
        
        // Formatear el mensaje de log
        $logMessage = sprintf(
            "[%s] [%s] [Usuario:%s(%s)] [IP:%s] %s %s",
            $eventData['timestamp'],
            strtoupper($type),
            $userId,
            $userEmail,
            $ip,
            $message,
            !empty($context) ? " - Contexto: " . json_encode($context) : ""
        );
        
        // Log en el archivo de seguridad
        $success = error_log($logMessage . PHP_EOL, 3, self::getLogFile());
        
        // También enviarlo al log de errores del sistema por si acaso
        if (in_array($type, [
            self::LOGIN_FAIL, 
            self::AUTH_ERROR, 
            self::ACCESS_DENIED, 
            self::CSRF_ERROR, 
            self::ACCOUNT_LOCKED
        ])) {
            error_log($logMessage);
        }
        
        return $success;
    }
    
    /**
     * Obtiene la ruta del archivo de log
     * @return string
     */
    private static function getLogFile()
    {
        $logDir = dirname(dirname(__DIR__)) . '/logs';
        
        // Crear directorio de logs si no existe
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        return $logDir . '/security_' . date('Y-m-d') . '.log';
    }
}
