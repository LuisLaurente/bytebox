<?php
namespace Controllers;

use Core\Helpers\SessionHelper;
use Core\Helpers\CsrfHelper;
use Core\Helpers\Validator;
use Models\Usuario;
use Models\Rol;

class AdminAuthController
{
    private $usuarioModel;
    private $rolModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->rolModel = new Rol();
    }

    // Mostrar formulario de login
    public function login()
    {
        if (SessionHelper::isAdmin()) {
            header('Location: ' . url('/auth/profile'));
            exit;
        }

        $error = $_GET['error'] ?? '';
        require_once __DIR__ . '/../views/admin/auth/login.php';
    }

    public function authenticate()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . url('/admin/login'));
                exit;
            }

            SessionHelper::start();

            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $csrfToken = $_POST['csrf_token'] ?? '';
            $remember = isset($_POST['remember']);

            // Validar CSRF
            if (empty($csrfToken) || !CsrfHelper::validateToken($csrfToken, 'login_form')) {
                $error = urlencode('Error de seguridad: token inválido');
                header('Location: ' . url('/admin/login?error=' . $error));
                exit;
            }

            // Validar campos
            $errores = [];
            if (empty($email)) $errores[] = 'El email es requerido';
            if (!Validator::email($email)) $errores[] = 'El email no es válido';
            if (empty($password)) $errores[] = 'La contraseña es requerida';
            if (!empty($errores)) {
                $error = urlencode(implode(', ', $errores));
                header('Location: ' . url('/admin/login?error=' . $error));
                exit;
            }

            // Buscar usuario por email
            $usuario = $this->usuarioModel->obtenerPorEmail($email);

            if (!$usuario || !$usuario['activo']) {
                $error = urlencode('Credenciales inválidas o usuario desactivado');
                header('Location: ' . url('/admin/login?error=' . $error));
                exit;
            }

            // Verificar contraseña
            if (!password_verify($password, $usuario['password'])) {
                $error = urlencode('Credenciales inválidas');
                header('Location: ' . url('/admin/login?error=' . $error));
                exit;
            }

            // Obtener rol y verificar que sea admin
            $rol = $this->rolModel->obtenerPorId($usuario['rol_id']);
            if (!$rol || $rol['nombre'] !== 'admin' || !$rol['activo']) {
                $error = urlencode('No tienes permisos de administrador');
                header('Location: ' . url('/admin/login?error=' . $error));
                exit;
            }

            // Crear sesión
            SessionHelper::login($usuario, $rol);

            // Recordarme
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30*24*60*60), '/');
            }

            header('Location: ' . url('/auth/profile'));
            exit;
        } catch (\Exception $e) {
            error_log("Error en AdminAuthController::authenticate: " . $e->getMessage());
            $error = urlencode('Error interno del servidor');
            header('Location: ' . url('/admin/login?error=' . $error));
            exit;
        }
    }

    public function logout()
    {
        SessionHelper::logout();
        header('Location: ' . url('/admin/login'));
        exit;
    }
}