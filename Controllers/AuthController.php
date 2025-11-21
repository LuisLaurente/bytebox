<?php

namespace Controllers;

use Models\Usuario;
use Models\Rol;
use Core\Helpers\SessionHelper;
use Core\Helpers\Validator;
use Core\Helpers\CsrfHelper;
use Core\Helpers\LoginRateHelper;
use Core\Helpers\SecurityLogger;
use Core\Helpers\MailHelper;
use Core\Database;

use Core\Helpers\CookieHelper;
use Core\Helpers\RememberMeHelper;
use Core\Helpers\CartPersistenceHelper;

class AuthController extends BaseController
{
    private $usuarioModel;
    private $rolModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->rolModel = new Rol();

        // ✅ SOLUCIÓN: ELIMINAR session_start() - SessionHelper lo maneja automáticamente
        // SessionHelper::start() se llamará cuando sea necesario
    }

    /**
     * Mostrar formulario de login
     */
    public function login()
    {
        // Si ya está autenticado, redirigir al perfil o a la URL de redirección
        if (SessionHelper::isAuthenticated()) {
            $redirect = $_GET['redirect'] ?? '';
            if (!empty($redirect)) {
                header('Location: ' . url($redirect));
            } else {
                header('Location: ' . url('/auth/profile'));
            }
            exit;
        }

        // Limpiar intentos antiguos para mantener la sesión ligera
        LoginRateHelper::cleanOldAttempts();

        $error = $_GET['error'] ?? '';
        $redirect = $_GET['redirect'] ?? '';

        // Verificar si hay una IP bloqueada
        $ip = $_SERVER['REMOTE_ADDR'];
        $blockInfo = LoginRateHelper::isBlocked($ip);
        if ($blockInfo) {
            $error = $blockInfo['message'];
        }

        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Procesar login
     */
    public function authenticate()
    {
        error_log("🎯 AUTHENTICATE METHOD CALLED");
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . url('/auth/login'));
                exit;
            }

            // ✅ Asegurar que la sesión esté iniciada para usar $_SESSION
            SessionHelper::start();

            // Verificar token CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            $redirect = $_POST['redirect'] ?? '';

            if (empty($csrfToken) || !CsrfHelper::validateToken($csrfToken, 'login_form')) {
                SecurityLogger::log(SecurityLogger::CSRF_ERROR, 'Token CSRF inválido en intento de login', [
                    'email' => $email ?? 'no proporcionado'
                ]);

                // Si viene del carrito, redirigir con error en sesión
                if (!empty($redirect) && $redirect === 'carrito/ver') {
                    $_SESSION['auth_error'] = 'Error de seguridad: Token inválido o expirado. Por favor, intente nuevamente.';
                    header('Location: ' . url('carrito/ver'));
                } else {
                    $error = urlencode('Error de seguridad: Token inválido o expirado. Por favor, intente nuevamente.');
                    header('Location: ' . url("/auth/login?error=$error"));
                }
                exit;
            }

            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);

            // DEBUG CRÍTICO
            error_log("🎯 REMEMBER DEBUG: remember = " . ($remember ? 'TRUE' : 'FALSE'));
            error_log("🎯 REMEMBER DEBUG: POST data = " . json_encode($_POST));
            error_log("🎯 REMEMBER DEBUG: Checkbox value = " . ($_POST['remember'] ?? 'NOT SET'));

            // Verificar bloqueo por intentos fallidos (usando IP si el email no existe)
            $identifier = $email ?: $_SERVER['REMOTE_ADDR'];
            $blockInfo = LoginRateHelper::isBlocked($identifier);

            if ($blockInfo) {
                SecurityLogger::log(SecurityLogger::ACCOUNT_LOCKED, 'Intento de acceso a cuenta bloqueada', [
                    'email' => $email,
                    'remaining_time' => $blockInfo['remaining_seconds']
                ]);

                // Si viene del carrito, redirigir con error en sesión
                if (!empty($redirect) && $redirect === 'carrito/ver') {
                    $_SESSION['auth_error'] = $blockInfo['message'];
                    header('Location: ' . url('carrito/ver'));
                } else {
                    $error = urlencode($blockInfo['message']);
                    header('Location: ' . url("/auth/login?error=$error"));
                }
                exit;
            }

            // Validar datos
            $errores = [];
            if (empty($email)) {
                $errores[] = 'El email es requerido';
            } elseif (!Validator::email($email)) {
                $errores[] = 'El email no es válido';
            }

            if (empty($password)) {
                $errores[] = 'La contraseña es requerida';
            }

            if (!empty($errores)) {
                // Si viene del carrito, redirigir con error en sesión
                if (!empty($redirect) && $redirect === 'carrito/ver') {
                    $_SESSION['auth_error'] = implode(', ', $errores);
                    header('Location: ' . url('carrito/ver'));
                } else {
                    $error = urlencode(implode(', ', $errores));
                    header('Location: ' . url("/auth/login?error=$error"));
                }
                exit;
            }

            // Buscar usuario por email
            $usuario = $this->usuarioModel->obtenerPorEmail($email);

            if (!$usuario) {
                // Verificar si está en pendientes (Invitación de Admin o Registro no completado)
                $db = \Core\Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT * FROM registros_pendientes WHERE email = ? AND expira_en > NOW()");
                $stmt->execute([$email]);
                $pendiente = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($pendiente && password_verify($password, $pendiente['password'])) {
                    // ¡Encontrado! Activar el modal en la vista de login
                    $_SESSION['login_verificacion_pendiente'] = true;
                    $_SESSION['login_email_temp'] = $email;
                    
                    $error = urlencode('Tu cuenta requiere verificación. Ingresa el código enviado a tu correo.');
                    header('Location: ' . url("/auth/login?error=$error"));
                    exit;
                }

                // Si no está en pendientes tampoco, es un fallo normal
                // Registrar intento fallido
                $attempts = LoginRateHelper::recordFailedAttempt($identifier);

                SecurityLogger::log(SecurityLogger::LOGIN_FAIL, 'Intento de login con email inexistente', [
                    'email' => $email,
                    'attempt_count' => $attempts['count']
                ]);

                // Si viene del carrito, redirigir con error en sesión
                if (!empty($redirect) && $redirect === 'carrito/ver') {
                    $_SESSION['auth_error'] = 'Credenciales incorrectas';
                    header('Location: ' . url('carrito/ver'));
                } else {
                    $error = urlencode('Credenciales incorrectas');
                    header('Location: ' . url("/auth/login?error=$error"));
                }
                exit;
            }

            // Verificar si el usuario está activo
            if (!$usuario['activo']) {
                SecurityLogger::log(SecurityLogger::LOGIN_FAIL, 'Intento de login con cuenta desactivada', [
                    'email' => $email,
                    'user_id' => $usuario['id']
                ]);

                // Si viene del carrito, redirigir con error en sesión
                if (!empty($redirect) && $redirect === 'carrito/ver') {
                    $_SESSION['auth_error'] = 'Tu cuenta está desactivada. Contacta al administrador.';
                    header('Location: ' . url('carrito/ver'));
                } else {
                    $error = urlencode('Tu cuenta está desactivada. Contacta al administrador.');
                    header('Location: ' . url("/auth/login?error=$error"));
                }
                exit;
            }

            // Verificar contraseña
            if (!password_verify($password, $usuario['password'])) {
                // Registrar intento fallido
                $attempts = LoginRateHelper::recordFailedAttempt($email);

                SecurityLogger::log(SecurityLogger::LOGIN_FAIL, 'Contraseña incorrecta', [
                    'email' => $email,
                    'user_id' => $usuario['id'],
                    'attempt_count' => $attempts['count']
                ]);

                // Si viene del carrito, redirigir con error en sesión
                if (!empty($redirect) && $redirect === 'carrito/ver') {
                    $_SESSION['auth_error'] = 'Credenciales incorrectas';
                    header('Location: ' . url('carrito/ver'));
                } else {
                    $error = urlencode('Credenciales incorrectas');
                    header('Location: ' . url("/auth/login?error=$error"));
                }
                exit;
            }

            // Éxito: resetear intentos fallidos
            LoginRateHelper::resetAttempts($email);

            // Obtener información del rol
            $rol = $this->rolModel->obtenerPorId($usuario['rol_id']);
            if (!$rol || !$rol['activo']) {
                // Si viene del carrito, redirigir con error en sesión
                if (!empty($redirect) && $redirect === 'carrito/ver') {
                    $_SESSION['auth_error'] = 'Tu rol está desactivado. Contacta al administrador.';
                    header('Location: ' . url('carrito/ver'));
                } else {
                    $error = urlencode('Tu rol está desactivado. Contacta al administrador.');
                    header('Location: ' . url("/auth/login?error=$error"));
                }
                exit;
            }

            // Crear sesión
            SessionHelper::login($usuario, $rol);
            //Mostrar popup en esta nueva sesión
            $_SESSION['mostrar_popup'] = true;

            // Registrar login exitoso
            SecurityLogger::log(SecurityLogger::LOGIN_SUCCESS, 'Login exitoso', [
                'user_id' => $usuario['id'],
                'email' => $usuario['email'],
                'rol' => $rol['nombre'],
                'remember_me' => $remember ? 'sí' : 'no'
            ]);
            // Si marcó "recordarme", crear cookie
            // ✅ NUEVO:
            if ($remember) {
                $token = \Core\Helpers\RememberMeHelper::generateToken();
                $this->usuarioModel->actualizarRememberToken($usuario['id'], $token);
                \Core\Helpers\RememberMeHelper::setRememberCookie($usuario['id'], $token);
                error_log("✅ Token remember_me creado para usuario: " . $usuario['id']);
            }

            // Redirigir según el parámetro redirect o al perfil por defecto
            if (!empty($redirect)) {
                // Si redirect ya contiene la base, redirigir directamente
                // Si no, usar url() para construir la ruta completa
                if (strpos($redirect, '/bytebox/public/') === 0) {
                    header('Location: ' . $redirect);
                } else {
                    header('Location: ' . url($redirect));
                }
            } else {
                header('Location: ' . url('auth/profile'));
            }
            exit;
        } catch (\Exception $e) {
            error_log("Error en AuthController::authenticate: " . $e->getMessage());
            $error = urlencode('Error interno del servidor');
            header('Location: ' . url("/auth/login?error=$error"));
            exit;
        }
    }

    /**
     * Dashboard principal - redirige al perfil
     */
    public function dashboard()
    {
        // Redirigir al perfil que es la nueva página principal
        header('Location: ' . url('/auth/profile'));
        exit;
    }

    /**
     * Cerrar sesión
     */
    public function logout()
    {
        // Registrar logout antes de destruir la sesión para tener la información del usuario
        if (SessionHelper::isAuthenticated()) {
            $usuario = SessionHelper::getUser();
            SecurityLogger::log(SecurityLogger::LOGOUT, 'Cierre de sesión', [
                'user_id' => $usuario['id'] ?? 'desconocido',
                'email' => $usuario['email'] ?? 'desconocido'
            ]);

            // ✅ NUEVO: Eliminar token de la base de datos
            $usuarioModel = new Usuario();
            $usuarioModel->actualizarRememberToken($usuario['id'], null); // o eliminar el registro
        }

        SessionHelper::logout();

        // ✅ CORREGIDO: Eliminar cookie correcta 'remember_me'
        if (CookieHelper::exists('remember_me')) {
            CookieHelper::delete('remember_me');
        }

        // ✅ También eliminar la antigua por si acaso
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }

        header('Location: ' . url('/'));
        exit;
    }

    /**
     * Perfil del usuario
     */
    public function profile()
    {
        if (!SessionHelper::isAuthenticated()) {
            header('Location: ' . url('/auth/login'));
            exit;
        }

        $usuario = SessionHelper::getUser();
        $rol = SessionHelper::getRole();

        require_once __DIR__ . '/../views/auth/profile.php';
    }

    /**
     * Actualizar perfil
     */
    public function updateProfile()
    {
        if (!SessionHelper::isAuthenticated()) {
            header('Location: ' . url('/auth/login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/auth/profile'));
            exit;
        }

        try {
            $usuario = SessionHelper::getUser();
            $datos = [
                'nombre' => $_POST['nombre'] ?? '',
                'email' => $_POST['email'] ?? ''
            ];

            // Validar datos
            $errores = [];
            if (empty($datos['nombre'])) {
                $errores[] = 'El nombre es requerido';
            }

            if (empty($datos['email'])) {
                $errores[] = 'El email es requerido';
            } elseif (!Validator::email($datos['email'])) {
                $errores[] = 'El email no es válido';
            }

            // Verificar si el email ya existe (excluyendo el usuario actual)
            if ($this->usuarioModel->existeEmail($datos['email'], $usuario['id'])) {
                $errores[] = 'El email ya está en uso';
            }

            if (!empty($errores)) {
                $error = urlencode(implode(', ', $errores));
                header('Location: ' . url("/auth/profile?error=$error"));
                exit;
            }

            // Actualizar usuario
            $resultado = $this->usuarioModel->actualizar($usuario['id'], $datos);

            if ($resultado) {
                // Actualizar datos en la sesión
                $usuarioActualizado = $this->usuarioModel->obtenerPorId($usuario['id']);
                SessionHelper::updateUser($usuarioActualizado);

                $success = urlencode('Perfil actualizado exitosamente');
                header('Location: ' . url("/auth/profile?success=$success"));
                exit;
            } else {
                $error = urlencode('Error al actualizar el perfil');
                header('Location: ' . url("/auth/profile?error=$error"));
                exit;
            }
        } catch (\Exception $e) {
            error_log("Error en AuthController::updateProfile: " . $e->getMessage());
            $error = urlencode('Error interno del servidor');
            header('Location: ' . url("/auth/profile?error=$error"));
            exit;
        }
    }

    /**
     * Mostrar formulario de registro
     */
    public function registro()
    {
        // Si ya está autenticado, redirigir al perfil o a la URL de redirección
        if (SessionHelper::isAuthenticated()) {
            $redirect = $_GET['redirect'] ?? '';
            if (!empty($redirect)) {
                header('Location: ' . url($redirect));
            } else {
                header('Location: ' . url('/auth/profile'));
            }
            exit;
        }

        $error = $_GET['error'] ?? '';
        $success = $_GET['success'] ?? '';
        $redirect = $_GET['redirect'] ?? '';

        require_once __DIR__ . '/../views/auth/registro.php';
    }

    public function procesarRegistro()
    {
        // 1. Detección de AJAX (Método estándar para JavaScript fetch)
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        // DEBE ser para iniciar el proceso de verificación.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // El JS llama a esta URL. Re-enrutamos internamente al nuevo endpoint AJAX.
            return $this->iniciarRegistro(); 
        }

        // Si es GET o no es POST, se asume que es una navegación normal de fallback.
        header('Location: ' . url('/auth/registro'));
        exit;
    }

    /**
     * Recibe datos (vía AJAX), valida, guarda en temporal y envía email.
     * Este método reemplaza la lógica principal del anterior procesarRegistro.
     */
    public function iniciarRegistro() {
        
        ob_start();
        
        header('Content-Type: application/json');
        
        // Inicializar la respuesta con un error por defecto
        $response = ['success' => false, 'message' => 'Error interno del servidor.'];

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $response['message'] = 'Método no permitido';
                goto send_response;
            }

            // 1. Validar CSRF
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (empty($csrfToken) || !\Core\Helpers\CsrfHelper::validateToken($csrfToken, 'registro_form', false)) { 
                $response['message'] = 'Error de seguridad: Token inválido o expirado. Recarga la página.';
                goto send_response;
            }

            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            
            // 2. Validaciones básicas
            $errores = [];

            if (empty($nombre) || strlen($nombre) < 2) {
                $errores[] = 'El nombre debe tener al menos 2 caracteres';
            }
            if (empty($email) || !\Core\Helpers\Validator::email($email)) {
                $errores[] = 'El email no es válido';
            }
            if (empty($password) || strlen($password) < 6) {
                $errores[] = 'La contraseña debe tener al menos 6 caracteres';
            }
            if ($password !== $confirm) {
                $errores[] = 'Las contraseñas no coinciden';
            }

            // Si hay errores de validación de campos
            if (!empty($errores)) {
                $response['message'] = implode(', ', $errores);
                goto send_response;
            }
            
            // 3. Verificar si el email ya existe en la tabla REAL
            if ($this->usuarioModel->obtenerPorEmail($email)) {
                $response['message'] = 'Este correo ya está registrado. Intenta iniciar sesión.';
                goto send_response; 
            }

            // 4. Generar Código, Hash de Password y Expiración
            $codigo = rand(100000, 999999); 
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $expira = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // 5. Guardar en tabla TEMPORAL (registros_pendientes)
            $db = \Core\Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("DELETE FROM registros_pendientes WHERE email = ?");
            $stmt->execute([$email]);

            $sql = "INSERT INTO registros_pendientes (nombre, email, password, codigo, expira_en) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$nombre, $email, $passwordHash, $codigo, $expira]);

            // 6. Enviar Email con el código de verificación
            if (\Core\Helpers\MailHelper::enviarCodigoVerificacion($email, $nombre, $codigo)) {
                $response = ['success' => true, 'message' => 'Código enviado'];
            } else {
                // Fallo controlado del envío de correo
                $response['message'] = 'Error al enviar el correo. Verifica tu dirección y reintenta.';
            }

        } catch (\Exception $e) {
            
            // 📢 3. FALLO CRÍTICO: Capturado por excepción
            error_log("Error en iniciarRegistro (CRÍTICO): " . $e->getMessage());
            http_response_code(500); 
            $response['message'] = 'Error interno del servidor.';
            $response['success'] = false;
        }

        // 📢 4. BLOQUE DE RESPUESTA GARANTIZADO
        send_response:
            // 📢 CRÍTICO: Limpiar el buffer de salida final (CUALQUIER HTML capturado)
            if (ob_get_length() > 0) {
                ob_end_clean();
            }
            
            // Aquí garantizamos que solo se envíe JSON
            echo json_encode($response);
        
        exit;
    }

    /**
     * Método SÍNCRONO para verificar código y crear usuario.
     * Reemplaza la versión AJAX para evitar errores de JSON.
     */
    public function verificarCodigoRegistro() {
        // Asegurar sesión iniciada
        if (session_status() === PHP_SESSION_NONE) {
            \Core\Helpers\SessionHelper::start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/auth/registro'));
            exit;
        }
        
        $email = $_POST['email'] ?? '';
        $codigo = $_POST['codigo'] ?? '';
        $redirect = $_POST['redirect'] ?? '';

        // 1. Validaciones básicas
        if (empty($email) || empty($codigo)) {
            $_SESSION['flash_error'] = 'Datos incompletos.';
            goto error_redirect;
        }

        $db = \Core\Database::getInstance()->getConnection();

        // 2. Buscar el registro pendiente
        $stmt = $db->prepare("SELECT * FROM registros_pendientes WHERE email = ? AND expira_en > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$email]);
        $pendiente = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pendiente) {
            $_SESSION['flash_error'] = 'El código ha expirado o el email no es válido. Regístrate de nuevo.';
            goto error_redirect;
        }

        // 3. Verificar Código
        if ($pendiente['codigo'] !== $codigo) {
            // Incrementar intentos (seguridad básica)
            $db->prepare("UPDATE registros_pendientes SET intentos = intentos + 1 WHERE id = ?")->execute([$pendiente['id']]);
            $_SESSION['flash_error'] = 'Código incorrecto. Inténtalo de nuevo.';
            // FLAG IMPORTANTE: Para que la vista sepa que debe abrir el modal de nuevo
            $_SESSION['abrir_modal_verificacion'] = true; 
            $_SESSION['registro_email_temp'] = $email; // Guardar email para rellenarlo

            goto error_redirect;
        }

        // 4. ÉXITO: Crear usuario REAL
        try {
            $usuarioData = [
                'nombre' => $pendiente['nombre'],
                'email' => $pendiente['email'],
                'password' => $pendiente['password'], // Se pasa el hash, el modelo lo inserta directamente.
                'rol_id' => $pendiente['rol_id'] ?? 2, // Cliente por defecto
                'activo' => 1
                // Se omiten datos como 'telefono' porque no se recogieron en el formulario de registro
            ];

            // Usamos el modelo para crear
            $usuarioId = $this->usuarioModel->crear($usuarioData);

            if (!$usuarioId) {
                throw new \Exception("No se pudo insertar el usuario.");
            }

            // 4. Borrar registro pendiente
            // Este DELETE debe hacerse aquí (en el controlador) ya que la tabla 'registros_pendientes' es lógica temporal del proceso.
            $db->prepare("DELETE FROM registros_pendientes WHERE email = ?")->execute([$email]);
            
            // Auto-Login
            $usuario = $this->usuarioModel->obtenerPorId($usuarioId);
            $rol = $this->rolModel->obtenerPorId($usuario['rol_id']);
            \Core\Helpers\SessionHelper::login($usuario, $rol);

            // Transferir carrito
            \Core\Helpers\CartPersistenceHelper::transferGuestCartToUser($usuarioId);
            
            // ✅ REDIRECCIÓN FINAL (Éxito)
            $targetUrl = !empty($redirect) ? url($redirect) : url('/auth/profile');
            header('Location: ' . $targetUrl);
            exit;
        } catch (\Exception $e) {
            error_log("Error finalizando registro: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error del servidor al crear la cuenta. Intenta de nuevo.';
            goto error_redirect;
        }
        
        error_redirect:
        // Redirigir al registro para mostrar el error
        header('Location: ' . url('/auth/registro'));
        exit;
    }

    /* ================================
     * LOGIN GOOGLE
     * ================================ */
    public function loginGoogle()
    {
        $google = new \Controllers\GoogleAuthController();
        $google->login();
    }
    /**
     * Procesar aceptación de cookies
     */
    public function aceptarCookies()
    {
        \Core\Helpers\CookieHelper::set('cookies_consent', '1', 365);

        // Si el usuario está logueado, guardar en base de datos
        if (\Core\Helpers\SessionHelper::isAuthenticated()) {
            $user_id = \Core\Helpers\SessionHelper::getUserId();
            // Aquí puedes guardar en tu tabla de clientes si es necesario
        }

        echo json_encode(['success' => true]);
    }

    /**
     * Procesar rechazo de cookies
     */
    public function rechazarCookies()
    {
        \Core\Helpers\CookieHelper::set('cookies_consent', '0', 365);
        echo json_encode(['success' => true]);
    }

    /**
     * Callback de Google
     */
    public function googleCallback()
    {
        $google = new \Controllers\GoogleAuthController();
        $google->callback();
    }

    // --- MÉTODOS PARA RECUPERACIÓN DE CONTRASEÑA (AJAX) ---

    /**
     * Paso 1: Recibe email, genera código y lo envía
     */
    public function iniciarRecuperacion() {
        ob_start();
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Error interno.'];

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') goto send_resp;

            $email = trim($_POST['email'] ?? '');
            
            // Verificar si el usuario existe
            $usuario = $this->usuarioModel->obtenerPorEmail($email);
            if (!$usuario) {
                // Por seguridad, no decimos si el correo existe o no, pero simulamos éxito
                // O si prefieres UX sobre seguridad estricta:
                $response['message'] = 'No encontramos una cuenta con ese correo.';
                goto send_resp;
            }

            // Generar código
            $codigo = rand(100000, 999999);
            $expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Guardar en password_resets
            $db = \Core\Database::getInstance()->getConnection();
            $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
            
            $stmt = $db->prepare("INSERT INTO password_resets (email, codigo, expira_en) VALUES (?, ?, ?)");
            $stmt->execute([$email, $codigo, $expira]);

            // Enviar Correo
            if (\Core\Helpers\MailHelper::enviarCorreoRecuperacion($email, $usuario['nombre'], $codigo)) {
                $response = ['success' => true, 'message' => 'Código enviado.'];
            } else {
                $response['message'] = 'Error al enviar el correo.';
            }

        } catch (\Exception $e) {
            error_log("Error iniciarRecuperacion: " . $e->getMessage());
            $response['message'] = 'Error del servidor.';
        }

        send_resp:
        if (ob_get_length() > 0) ob_end_clean();
        echo json_encode($response);
        exit;
    }

    /**
     * Paso 2: Verifica que el código sea correcto
     */
    public function verificarCodigoRecuperacion() {
        ob_start();
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Código inválido.'];

        try {
            $email = $_POST['email'] ?? '';
            $codigo = $_POST['codigo'] ?? '';

            $db = \Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE email = ? AND expira_en > NOW() ORDER BY id DESC LIMIT 1");
            $stmt->execute([$email]);
            $reset = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($reset && $reset['codigo'] === $codigo) {
                $response = ['success' => true, 'message' => 'Código verificado.'];
            } else {
                $response['message'] = 'Código incorrecto o expirado.';
            }
        } catch (\Exception $e) {
            error_log("Error verificarCodigoRecuperacion: " . $e->getMessage());
        }

        if (ob_get_length() > 0) ob_end_clean();
        echo json_encode($response);
        exit;
    }

    /**
     * Paso 3: Cambia la contraseña final
     */
    public function finalizarRecuperacion() {
        ob_start();
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => 'Error al actualizar.'];

        try {
            $email = $_POST['email'] ?? '';
            $codigo = $_POST['codigo'] ?? ''; // Re-verificamos por seguridad
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (strlen($password) < 6) {
                $response['message'] = 'La contraseña debe tener al menos 6 caracteres.';
                goto final_resp;
            }
            if ($password !== $confirm) {
                $response['message'] = 'Las contraseñas no coinciden.';
                goto final_resp;
            }

            // Re-verificar código (para evitar que alguien se salte el paso 2)
            $db = \Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM password_resets WHERE email = ? AND codigo = ? AND expira_en > NOW()");
            $stmt->execute([$email, $codigo]);
            if (!$stmt->fetch()) {
                $response['message'] = 'Sesión de recuperación inválida.';
                goto final_resp;
            }

            // Actualizar Usuario
            $usuario = $this->usuarioModel->obtenerPorEmail($email);
            if ($usuario) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $this->usuarioModel->actualizar($usuario['id'], ['password' => $hashedPassword]);
                
                // Borrar token usado
                $db->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                
                // Opcional: Iniciar sesión automáticamente
                $rol = $this->rolModel->obtenerPorId($usuario['rol_id']);
                \Core\Helpers\SessionHelper::login($usuario, $rol);

                $response = ['success' => true, 'message' => 'Contraseña actualizada.'];
            }

        } catch (\Exception $e) {
            error_log("Error finalizarRecuperacion: " . $e->getMessage());
        }

        final_resp:
        if (ob_get_length() > 0) ob_end_clean();
        echo json_encode($response);
        exit;
    }

    /* ================================
     * CAMBIO DE CONTRASEÑA
     * ================================ */

    /**
     * Mostrar formulario de cambio de contraseña
     */
    public function changePassword()
    {
        if (!SessionHelper::isAuthenticated()) {
            header('Location: ' . url('/auth/login'));
            exit;
        }

        $usuario = SessionHelper::getUser();
        $usuarioDb = $this->usuarioModel->obtenerPorId($usuario['id']);

        // Detección de cuenta social o password hash largo
        $isSocial = false;
        $checks = ['google_id', 'facebook_id', 'auth_provider', 'provider', 'oauth_provider', 'provider_name', 'social_provider'];
        foreach ($checks as $k) {
            if (isset($usuarioDb[$k]) && !empty($usuarioDb[$k]) && $usuarioDb[$k] !== 'local') {
                $isSocial = true;
                break;
            }
        }

        require_once __DIR__ . '/../views/auth/changePassword.php';
    }
    public function updatePassword()
    {
        if (!SessionHelper::isAuthenticated()) {
            header('Location: ' . url('/auth/login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/auth/profile'));
            exit;
        }

        try {
            $usuario = SessionHelper::getUser();
            $usuarioDb = $this->usuarioModel->obtenerPorId($usuario['id']);

            // aceptar distintos nombres de input en caso la vista varíe
            $passwordActual = $_POST['password_actual'] ?? $_POST['actual'] ?? '';
            $passwordNueva = $_POST['password_nueva'] ?? $_POST['nueva'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? $_POST['confirmar'] ?? '';

            $errores = [];

            // 1. Validar contraseña actual (LÓGICA INTELIGENTE)
            // Solo validamos la contraseña actual SI el usuario YA TIENE una contraseña en la BD.
            // Si entró con Google y nunca puso clave, este paso se salta para permitirle crear una.
            if (!empty($usuarioDb['password'])) {
                if (empty($passwordActual) || !password_verify($passwordActual, $usuarioDb['password'])) {
                    $errores[] = 'La contraseña actual es incorrecta';
                }
            }

            // 2. Validar nueva contraseña
            if (empty($passwordNueva) || strlen($passwordNueva) < 6) {
                $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres';
            }

            if ($passwordNueva !== $passwordConfirm) {
                $errores[] = 'Las contraseñas nuevas no coinciden';
            }

            if (!empty($errores)) {
                $error = urlencode(implode(', ', $errores));
                header('Location: ' . url("/auth/changePassword?error=$error"));
                exit;
            }

            // 3. Actualizar contraseña
            $hashedPassword = password_hash($passwordNueva, PASSWORD_DEFAULT);
            $this->usuarioModel->actualizar($usuario['id'], [
                'password' => $hashedPassword
            ]);

            SecurityLogger::log(SecurityLogger::PASSWORD_CHANGE, 'Cambio de contraseña exitoso', [
                'user_id' => $usuario['id'],
                'email' => $usuario['email']
            ]);

            $success = urlencode('Contraseña cambiada correctamente');
            header('Location: ' . url("/auth/profile?success=$success"));
            exit;
        } catch (\Exception $e) {
            error_log("Error en AuthController::updatePassword: " . $e->getMessage());
            $error = urlencode('Error interno al cambiar contraseña');
            header('Location: ' . url("/auth/changePassword?error=$error"));
            exit;
        }
    }

    /**
     * Generar un nuevo token CSRF (para AJAX)
     * Usado por el modal de login para obtener un token fresco
     */
    public function getCsrfToken()
    {
        header('Content-Type: application/json');

        try {
            // Asegurar que la sesión está iniciada
            SessionHelper::start();

            // Generar nuevo token
            $token = CsrfHelper::generateToken('login_form');

            echo json_encode([
                'success' => true,
                'token' => $token
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar token'
            ]);
        }
        exit;
    }

    public function reenviarCodigo() {
        // SessionHelper::start() se llama en index.php
        
        if (session_status() === PHP_SESSION_NONE) {
            \Core\Helpers\SessionHelper::start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/auth/registro'));
            exit;
        }

        $email = $_POST['email'] ?? '';
        $redirect = $_POST['redirect'] ?? '';
        
        // 1. Validaciones básicas y CSRF (simplificado, pero necesario)
        if (empty($email)) {
            $_SESSION['flash_error'] = 'Email no proporcionado.';
            header('Location: ' . url('/auth/registro'));
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($csrfToken) || !\Core\Helpers\CsrfHelper::validateToken($csrfToken, 'registro_form', false)) { 
            $_SESSION['flash_error'] = 'Error de seguridad: Token inválido o expirado. Por favor, inténtelo de nuevo.';
            header('Location: ' . url('/auth/registro'));
            exit;
        }

        // 2. Buscamos el registro pendiente
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM registros_pendientes WHERE email = ? AND expira_en > NOW()");
        $stmt->execute([$email]);
        $pendiente = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // 3. Generar y Guardar nuevo código
        $nuevoCodigo = rand(100000, 999999);
        $fechaCreacion = strtotime($pendiente['created_at']);
        $fechaExpiracionOriginal = strtotime($pendiente['expira_en']);
        $ventanaOriginal = $fechaExpiracionOriginal - $fechaCreacion;

        // Si la ventana original era mayor a 24 horas (86400 segundos), asumimos que es invitación de Admin
        // y mantenemos los 7 días desde AHORA. De lo contrario, usamos los 10 minutos estándar.
        if ($ventanaOriginal > 86400) {
            $expira = date('Y-m-d H:i:s', strtotime('+7 days'));
        } else {
            $expira = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        }

        $nombre = $pendiente['nombre'] ?? 'Usuario'; // Fallback si no se encuentra
        
        // Actualizar o Insertar (Upsert simplificado)
        // Si ya existe, actualizamos. Si no (caso raro de expiración total), insertamos de nuevo si tenemos datos.
        if ($pendiente) {
            $sql = "UPDATE registros_pendientes SET codigo = ?, expira_en = ? WHERE email = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$nuevoCodigo, $expira, $email]);
        } else {
             // Si no hay pendiente, redirigimos con error para que se registre de cero
            $_SESSION['flash_error'] = 'Tu sesión de registro ha expirado. Por favor regístrate nuevamente.';
            header('Location: ' . url('/auth/registro'));
            exit;
        }

        // 4. Enviar Email (Usamos el MailHelper, flujo normal)
        if (\Core\Helpers\MailHelper::enviarCodigoVerificacion($email, $pendiente['nombre'], $nuevoCodigo)) {
            $_SESSION['registro_reenvio_exito'] = true;
            $_SESSION['registro_email_temp'] = $email;
            $tiempoMensaje = ($ventanaOriginal > 86400) ? '7 días' : '10 minutos';
            $_SESSION['flash_success'] = "Código reenviado. Tienes $tiempoMensaje para verificarlo.";
        } else {
            $_SESSION['flash_error'] = 'Error al enviar el correo. Intenta más tarde.';
        }

        // 5. Redirigir (Recargar página)
        $urlDestino = url('/auth/registro');
        if (!empty($redirect)) {
            $urlDestino .= '?redirect=' . urlencode($redirect);
        }
        header('Location: ' . $urlDestino);
        exit;
    }
}