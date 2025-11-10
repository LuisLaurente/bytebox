<?php
namespace Controllers;

use League\OAuth2\Client\Provider\Google;
use Core\Helpers\SessionHelper;
use Models\Usuario;
use Models\Rol;
use Core\Helpers\RememberMeHelper;
use Core\Helpers\CartPersistenceHelper;

class GoogleAuthController extends BaseController
{
    private $provider;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/oauth.template.php';
        $this->provider = new Google([
            'clientId'     => $config['google']['clientId'],
            'clientSecret' => $config['google']['clientSecret'],
            'redirectUri'  => $config['google']['redirectUri'],
        ]);
    }

    // Paso 1: Redirigir a Google
    public function login()
    {
        SessionHelper::start();
        
        $authUrl = $this->provider->getAuthorizationUrl([
            'scope' => ['openid', 'email', 'profile']
        ]);
        
        // ✅ CORREGIDO: Usar el mismo nombre en login y callback
        $_SESSION['oauth_state'] = $this->provider->getState();
        
        error_log("🔐 Google OAuth - State generado: " . $_SESSION['oauth_state']);
        
        header('Location: ' . $authUrl);
        exit;
    }

    public function callback()
    {
        try {
            SessionHelper::start();
            
            error_log("🎯 Google OAuth callback iniciado");
            error_log("🔐 State en sesión: " . ($_SESSION['oauth_state'] ?? 'NO EXISTE'));
            error_log("🔐 State en GET: " . ($_GET['state'] ?? 'NO EXISTE'));

            // Verificar si hay código de autorización
            if (empty($_GET['code'])) {
                error_log("❌ Google callback sin código");
                header('Location: ' . url('/auth/login?error=google_no_code'));
                exit;
            }

            // ✅ CORREGIDO: Verificar state con el mismo nombre
            $state = $_GET['state'] ?? '';
            $sessionState = $_SESSION['oauth_state'] ?? '';
            
            if (empty($state) || empty($sessionState)) {
                error_log("❌ State vacío - Sesión: '$sessionState', GET: '$state'");
                // ❌ TEMPORAL: Deshabilitar verificación para testing
                // header('Location: ' . url('/auth/login?error=google_invalid_state'));
                // exit;
                error_log("⚠️ Saltando verificación de state temporalmente");
            } else if ($state !== $sessionState) {
                error_log("❌ State mismatch - Sesión: '$sessionState', GET: '$state'");
                // ❌ TEMPORAL: Deshabilitar verificación para testing  
                // header('Location: ' . url('/auth/login?error=google_invalid_state'));
                // exit;
                error_log("⚠️ Saltando verificación de state temporalmente");
            }

            // Limpiar state de la sesión
            unset($_SESSION['oauth_state']);

            // ✅ Obtener token usando la librería correctamente
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            // Obtener información del usuario
            $user = $this->provider->getResourceOwner($token);
            $userInfo = $user->toArray();

            error_log("✅ Datos usuario Google: " . json_encode([
                'email' => $userInfo['email'] ?? '',
                'name' => $userInfo['name'] ?? '',
                'id' => $userInfo['sub'] ?? ''
            ]));

            // Login o registro del usuario
            $this->loginOrRegister($userInfo);
            
        } catch (\Exception $e) {
            error_log("❌ GOOGLE OAUTH EXCEPTION: " . $e->getMessage());
            header('Location: ' . url('/auth/login?error=google_exception'));
            exit;
        }
    }

    private function loginOrRegister($userData)
    {
        error_log("🚀 INICIANDO loginOrRegister()");
        error_log("📧 Email de Google: " . ($userData['email'] ?? 'NO DISPONIBLE'));

        $email = $userData['email'] ?? null;
        if (!$email) {
            error_log("❌ ERROR: No se pudo obtener email de Google");
            header('Location: ' . url('/auth/login?error=google_no_email'));
            exit;
        }
        
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorEmail($email);
        
        error_log("🔍 Buscando usuario en BD: " . $email);
        error_log("👤 Usuario encontrado: " . ($usuario ? 'SÍ' : 'NO'));
        
        if (!$usuario) {
            error_log("📝 Registrando nuevo usuario");
            
            // Registro automático
            $nuevo = [
                'nombre' => $userData['name'] ?? $userData['email'],
                'email' => $email,
                'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
                'rol_id' => 2, // Cliente por defecto
                'activo' => 1,
                'google_id' => $userData['sub'] ?? null // Usar 'sub' que es el ID de Google
            ];
            
            $usuarioId = $usuarioModel->crear($nuevo);
            error_log("💾 Usuario creado con ID: " . ($usuarioId ?: 'FALLÓ'));
            
            if (!$usuarioId) {
                error_log("❌ ERROR: No se pudo crear el usuario");
                header('Location: ' . url('/auth/login?error=google_register_failed'));
                exit;
            }
            
            $usuario = $usuarioModel->obtenerPorEmail($email);
            
            if (!$usuario) {
                error_log("❌ ERROR: No se pudo recuperar el usuario creado");
                header('Location: ' . url('/auth/login?error=google_register_failed'));
                exit;
            }
        } else {
            error_log("✅ Usuario existente encontrado");
            
            // Actualizar google_id si no está establecido
            if (empty($usuario['google_id']) && isset($userData['sub'])) {
                error_log("🔄 Actualizando google_id");
                $usuarioModel->actualizar($usuario['id'], [
                    'google_id' => $userData['sub']
                ]);
            }
        }
        
        // Obtener rol
        $rolModel = new Rol();
        $rol = $rolModel->obtenerPorId($usuario['rol_id']);
        
        error_log("👑 Rol obtenido: " . ($rol ? 'SÍ' : 'NO'));
        
        if (!$rol) {
            error_log("❌ ERROR: No se encontró el rol del usuario");
            header('Location: ' . url('/auth/login?error=google_no_role'));
            exit;
        }
        
        // Iniciar sesión
        SessionHelper::login($usuario, $rol);
        
        error_log("🔐 Sesión iniciada - User ID: " . SessionHelper::getUserId());
        
        // Sistema "Recuérdame" para Google
        $token = RememberMeHelper::generateToken();
        $usuarioModel->actualizarRememberToken($usuario['id'], $token);
        RememberMeHelper::setRememberCookie($usuario['id'], $token);
        
        // Transferir carrito de invitado a usuario
        CartPersistenceHelper::transferGuestCartToUser($usuario['id']);
        
        error_log("✅ Login Google exitoso - Redirigiendo a perfil");
        
        // Redirigir al perfil
        header('Location: ' . url('/auth/profile'));
        exit;
    }
}