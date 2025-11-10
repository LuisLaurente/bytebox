<?php
namespace Core\Helpers;

/**
 * Helper para protección contra múltiples intentos fallidos de login (rate limiting)
 */
class LoginRateHelper
{
    // Número máximo de intentos permitidos
    const MAX_ATTEMPTS = 5;
    
    // Tiempo de bloqueo en segundos (15 minutos)
    const LOCKOUT_TIME = 900;
    
    /**
     * Registra un intento fallido de inicio de sesión
     * @param string $identifier Identificador (email o IP)
     * @return array Información sobre intentos y estado de bloqueo
     */
    public static function recordFailedAttempt($identifier)
    {
        if (empty($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        $now = time();
        $attempts = $_SESSION['login_attempts'][$identifier] ?? [
            'count' => 0,
            'first_attempt' => $now,
            'last_attempt' => $now,
            'lockout_until' => null
        ];
        
        // Si existe un bloqueo y ya pasó, lo reiniciamos
        if ($attempts['lockout_until'] !== null && $now > $attempts['lockout_until']) {
            $attempts = [
                'count' => 0,
                'first_attempt' => $now,
                'last_attempt' => $now,
                'lockout_until' => null
            ];
        }
        
        // Incrementar contador de intentos
        $attempts['count']++;
        $attempts['last_attempt'] = $now;
        
        // Si alcanzó el límite, establecer bloqueo
        if ($attempts['count'] >= self::MAX_ATTEMPTS) {
            $attempts['lockout_until'] = $now + self::LOCKOUT_TIME;
            
            // Registrar evento de seguridad
            error_log("🔒 Bloqueo de login para '{$identifier}' hasta " . 
                      date('Y-m-d H:i:s', $attempts['lockout_until']));
        }
        
        // Guardar en sesión
        $_SESSION['login_attempts'][$identifier] = $attempts;
        
        return $attempts;
    }
    
    /**
     * Verifica si un identificador está bloqueado
     * @param string $identifier Identificador (email o IP)
     * @return array|null Información de bloqueo o null si no está bloqueado
     */
    public static function isBlocked($identifier)
    {
        if (empty($_SESSION['login_attempts'][$identifier])) {
            return null;
        }
        
        $attempts = $_SESSION['login_attempts'][$identifier];
        $now = time();
        
        // Si hay un bloqueo activo
        if ($attempts['lockout_until'] !== null && $now < $attempts['lockout_until']) {
            $remainingTime = $attempts['lockout_until'] - $now;
            $minutes = ceil($remainingTime / 60);
            
            return [
                'blocked' => true,
                'remaining_seconds' => $remainingTime,
                'remaining_minutes' => $minutes,
                'message' => "Demasiados intentos fallidos. Por favor, intente de nuevo en {$minutes} minutos."
            ];
        }
        
        return null;
    }
    
    /**
     * Resetea los intentos fallidos para un identificador
     * @param string $identifier Identificador (email o IP)
     */
    public static function resetAttempts($identifier)
    {
        if (isset($_SESSION['login_attempts'][$identifier])) {
            unset($_SESSION['login_attempts'][$identifier]);
        }
    }
    
    /**
     * Limpia registros de intentos antiguos (mantenimiento)
     * @param int $olderThan Segundos
     */
    public static function cleanOldAttempts($olderThan = 86400) // 24 horas por defecto
    {
        if (empty($_SESSION['login_attempts'])) {
            return;
        }
        
        $now = time();
        foreach ($_SESSION['login_attempts'] as $id => $data) {
            // Si el último intento es más antiguo que el límite y no hay bloqueo activo
            if (($now - $data['last_attempt'] > $olderThan) && 
                ($data['lockout_until'] === null || $now > $data['lockout_until'])) {
                unset($_SESSION['login_attempts'][$id]);
            }
        }
    }
}
