<?php

namespace Controllers;

use League\OAuth2\Client\Provider\Facebook;
use Core\Helpers\SessionHelper;
use Models\Usuario;
use Models\Rol;
use Core\Helpers\RememberMeHelper;
use Core\Helpers\CartPersistenceHelper;

class FacebookAuthController extends BaseController
{
    private $provider;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/oauth.template.php';
        $this->provider = new Facebook([
            'clientId'          => $config['facebook']['appId'],
            'clientSecret'      => $config['facebook']['appSecret'],
            'redirectUri'       => $config['facebook']['redirectUri'],
            'graphApiVersion'   => 'v18.0' // o la versión más reciente
        ]);
    }

    // Paso 1: Redirigir a Facebook
    public function login()
    {
        error_log("🎯 FacebookAuthController::login() EJECUTADO");
        error_log("🎯 URL solicitada: " . ($_SERVER['REQUEST_URI'] ?? 'desconocida'));
        error_log("🎯 Usuario autenticado: " . (\Core\Helpers\SessionHelper::isAuthenticated() ? 'SÍ' : 'NO'));

        SessionHelper::start();

        $authUrl = $this->provider->getAuthorizationUrl([
            'scope' => ['email', 'public_profile']
        ]);

        $_SESSION['oauth2state'] = $this->provider->getState();

        error_log("🔗 Redirigiendo a Facebook: " . $authUrl);
        error_log("🔐 State generado: " . $_SESSION['oauth2state']);

        header('Location: ' . $authUrl);
        exit;
    }

    public function callback()
    {
        try {
            SessionHelper::start();

            error_log("🎯 Facebook OAuth callback iniciado");
            error_log("🔐 State en sesión: " . ($_SESSION['oauth2state'] ?? 'NO EXISTE'));
            error_log("🔐 State en GET: " . ($_GET['state'] ?? 'NO EXISTE'));

            // Verificar si hay código de autorización
            if (empty($_GET['code'])) {
                error_log("❌ Facebook callback sin código");
                header('Location: ' . url('/auth/login?error=facebook_no_code'));
                exit;
            }

            // Verificar state
            $state = $_GET['state'] ?? '';
            $sessionState = $_SESSION['oauth2state'] ?? '';

            if (empty($state) || empty($sessionState) || $state !== $sessionState) {
                error_log("❌ State inválido - Sesión: '$sessionState', GET: '$state'");
                // Para testing puedes comentar esta línea temporalmente
                header('Location: ' . url('/auth/login?error=facebook_invalid_state'));
                exit;
            }

            // Limpiar state de la sesión
            unset($_SESSION['oauth2state']);

            // Obtener token
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            // Obtener información del usuario
            $user = $this->provider->getResourceOwner($token);
            $userInfo = $user->toArray();

            error_log("✅ Datos usuario Facebook: " . json_encode([
                'email' => $userInfo['email'] ?? '',
                'name' => $userInfo['name'] ?? '',
                'id' => $userInfo['id'] ?? ''
            ]));

            // Login o registro del usuario
            $this->loginOrRegister($userInfo);
        } catch (\Exception $e) {
            error_log("❌ FACEBOOK OAUTH EXCEPTION: " . $e->getMessage());
            header('Location: ' . url('/auth/login?error=facebook_exception'));
            exit;
        }
    }

    private function loginOrRegister($userData)
    {
        error_log("🚀 INICIANDO loginOrRegister() Facebook");
        error_log("📧 Email de Facebook: " . ($userData['email'] ?? 'NO DISPONIBLE'));

        $email = $userData['email'] ?? null;
        if (!$email) {
            error_log("❌ ERROR: No se pudo obtener email de Facebook");
            header('Location: ' . url('/auth/login?error=facebook_no_email'));
            exit;
        }

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorEmail($email);

        error_log("🔍 Buscando usuario en BD: " . $email);
        error_log("👤 Usuario encontrado: " . ($usuario ? 'SÍ' : 'NO'));

        if (!$usuario) {
            error_log("📝 Registrando nuevo usuario desde Facebook");

            // Registro automático
            $nuevo = [
                'nombre' => $userData['name'] ?? $userData['email'],
                'email' => $email,
                'password' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
                'rol_id' => 2, // Cliente por defecto
                'activo' => 1,
                'facebook_id' => $userData['id'] ?? null // Usar 'id' que es el ID de Facebook
            ];

            $usuarioId = $usuarioModel->crear($nuevo);
            error_log("💾 Usuario creado con ID: " . ($usuarioId ?: 'FALLÓ'));

            if (!$usuarioId) {
                error_log("❌ ERROR: No se pudo crear el usuario");
                header('Location: ' . url('/auth/login?error=facebook_register_failed'));
                exit;
            }

            $usuario = $usuarioModel->obtenerPorEmail($email);

            if (!$usuario) {
                error_log("❌ ERROR: No se pudo recuperar el usuario creado");
                header('Location: ' . url('/auth/login?error=facebook_register_failed'));
                exit;
            }
        } else {
            error_log("✅ Usuario existente encontrado");

            // Actualizar facebook_id si no está establecido
            if (empty($usuario['facebook_id']) && isset($userData['id'])) {
                error_log("🔄 Actualizando facebook_id");
                $usuarioModel->actualizar($usuario['id'], [
                    'facebook_id' => $userData['id']
                ]);
            }
        }

        // Obtener rol
        $rolModel = new Rol();
        $rol = $rolModel->obtenerPorId($usuario['rol_id']);

        error_log("👑 Rol obtenido: " . ($rol ? 'SÍ' : 'NO'));

        if (!$rol) {
            error_log("❌ ERROR: No se encontró el rol del usuario");
            header('Location: ' . url('/auth/login?error=facebook_no_role'));
            exit;
        }

        // Iniciar sesión
        SessionHelper::login($usuario, $rol);

        error_log("🔐 Sesión iniciada - User ID: " . SessionHelper::getUserId());

        // Sistema "Recuérdame" para Facebook
        $token = RememberMeHelper::generateToken();
        $usuarioModel->actualizarRememberToken($usuario['id'], $token);
        RememberMeHelper::setRememberCookie($usuario['id'], $token);

        // Transferir carrito de invitado a usuario
        CartPersistenceHelper::transferGuestCartToUser($usuario['id']);

        error_log("✅ Login Facebook exitoso - Redirigiendo a perfil");

        // Redirigir al perfil
        header('Location: ' . url('/auth/profile'));
        exit;
    }
}
