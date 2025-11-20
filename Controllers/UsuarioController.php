<?php

namespace Controllers;

use Models\Usuario;
use Models\Rol;
use Core\Helpers\Validator;
use Core\Helpers\Sanitizer;
use Core\Database;
use Core\Helpers\MailHelper;

class UsuarioController extends BaseController
{
    private $usuarioModel;
    private $rolModel;

    public function __construct()
    {
        // Verificar autenticación y permisos
        $this->usuarioModel = new Usuario();
        $this->rolModel = new Rol();
    }

    /**
     * Mostrar lista de usuarios
     */
    public function index()
    {
        try {
            $usuarios = $this->usuarioModel->obtenerTodos();
            $estadisticas = $this->usuarioModel->obtenerEstadisticas();

            // Procesar mensajes de estado
            $success = $_GET['success'] ?? '';
            $error = $_GET['error'] ?? '';

            require_once __DIR__ . '/../views/usuario/index.php';
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::index: " . $e->getMessage());
            header('Location: ' . url('/error'));
            exit;
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function crear()
    {
        try {
            $roles = $this->rolModel->obtenerActivos();
            require_once __DIR__ . '/../views/usuario/crear.php';
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::crear: " . $e->getMessage());
            header('Location: ' . url('/error'));
            exit;
        }
    }

    /**
     * Procesar creación de usuario
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . url('/usuario'));
                exit;
            }

            // Sanitizar datos
            $datos = [
                'nombre' => Sanitizer::cleanString($_POST['nombre'] ?? ''),
                'email' => Sanitizer::sanitizeEmail($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'confirmar_password' => $_POST['confirmar_password'] ?? '',
                'rol_id' => (int)($_POST['rol'] ?? 2), // Por defecto rol 'usuario'
                'activo' => 0
            ];

            // Validar datos
            $errores = $this->validarDatos($datos);

            if (!empty($errores)) {
                $error = urlencode(implode(', ', $errores));
                header('Location: ' . url("/usuario/crear?error=$error"));
                exit;
            }

            // Verificar si ya existe en usuarios REALES
            if ($this->usuarioModel->existeEmail($datos['email'])) {
                $error = urlencode('El email ya está registrado y activo.');
                header('Location: ' . url("/usuario/crear?error=$error"));
                exit;
            }

            // Hash password
            $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);

            // Generar Código y Expiración (7 Días para invitaciones administrativas)
            $codigo = rand(100000, 999999);
            $expira = date('Y-m-d H:i:s', strtotime('+7 days'));

            // === INSERTAR EN REGISTROS PENDIENTES ===
            $db = Database::getInstance()->getConnection();

            // Limpiar previos
            $db->prepare("DELETE FROM registros_pendientes WHERE email = ?")->execute([$datos['email']]);

            $sql = "INSERT INTO registros_pendientes (nombre, email, password, rol_id, codigo, expira_en) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $datos['nombre'], 
                $datos['email'], 
                $passwordHash, 
                $datos['rol_id'], 
                $codigo, 
                $expira
            ]);

            // Enviar Correo
            if (MailHelper::enviarCodigoVerificacion($datos['email'], $datos['nombre'], $codigo)) {
                $success = urlencode('Usuario invitado. Se ha enviado un código de verificación a su correo.');
                header('Location: ' . url("/usuario?success=$success"));
            } else {
                $error = urlencode('Datos guardados, pero hubo un error al enviar el correo.');
                header('Location: ' . url("/usuario?error=$error"));
            }
            exit;
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::store: " . $e->getMessage());
            $error = urlencode('Error interno del servidor');
            header('Location: ' . url("/usuario/crear?error=$error"));
            exit;
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function editar($id)
    {
        try {
            $usuario = $this->usuarioModel->obtenerPorId($id);

            if (!$usuario) {
                $error = urlencode('Usuario no encontrado');
                header('Location: ' . url("/usuario?error=$error"));
                exit;
            }

            $roles = $this->rolModel->obtenerActivos();

            require_once __DIR__ . '/../views/usuario/editar.php';
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::editar: " . $e->getMessage());
            header('Location: ' . url('/error'));
            exit;
        }
    }

    /**
     * Procesar actualización de usuario
     */
    public function actualizar($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . url('/usuario'));
                exit;
            }

            // Verificar que el usuario existe
            $usuarioExistente = $this->usuarioModel->obtenerPorId($id);
            if (!$usuarioExistente) {
                $error = urlencode('Usuario no encontrado');
                header('Location: ' . url("/usuario?error=$error"));
                exit;
            }

            // Sanitizar datos
            $datos = [
                'nombre' => Sanitizer::cleanString($_POST['nombre'] ?? ''),
                'email' => Sanitizer::sanitizeEmail($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'confirmar_password' => $_POST['confirmar_password'] ?? '',
                'rol_id' => (int)($_POST['rol'] ?? 2),
                'activo' => isset($_POST['activo']) ? 1 : 0
            ];

            // Validar datos (para actualización)
            $errores = $this->validarDatos($datos, false, $id);

            if (!empty($errores)) {
                $error = urlencode(implode(', ', $errores));
                header('Location: ' . url("/usuario/editar/$id?error=$error"));
                exit;
            }

            // Verificar si el email ya existe (excluyendo el usuario actual)
            if ($this->usuarioModel->existeEmail($datos['email'], $id)) {
                $error = urlencode('El email ya está registrado por otro usuario');
                header('Location: ' . url("/usuario/editar/$id?error=$error"));
                exit;
            }

            // Actualizar usuario
            $resultado = $this->usuarioModel->actualizar($id, $datos);

            if ($resultado) {
                $success = urlencode('Usuario actualizado exitosamente');
                header('Location: ' . url("/usuario?success=$success"));
                exit;
            } else {
                $error = urlencode('Error al actualizar el usuario');
                header('Location: ' . url("/usuario/editar/$id?error=$error"));
                exit;
            }
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::actualizar: " . $e->getMessage());
            $error = urlencode('Error interno del servidor');
            header('Location: ' . url("/usuario/editar/$id?error=$error"));
            exit;
        }
    }

    /**
     * Eliminar usuario
     */
    public function eliminar($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . url('/usuario'));
                exit;
            }

            // Verificar que el usuario existe
            $usuario = $this->usuarioModel->obtenerPorId($id);
            if (!$usuario) {
                $error = urlencode('Usuario no encontrado');
                header('Location: ' . url("/usuario?error=$error"));
                exit;
            }

            // Eliminar usuario
            $resultado = $this->usuarioModel->eliminar($id);

            if ($resultado) {
                $success = urlencode('Usuario eliminado exitosamente');
                header('Location: ' . url("/usuario?success=$success"));
                exit;
            } else {
                $error = urlencode('Error al eliminar el usuario');
                header('Location: ' . url("/usuario?error=$error"));
                exit;
            }
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::eliminar: " . $e->getMessage());
            $error = urlencode('Error interno del servidor');
            header('Location: ' . url("/usuario?error=$error"));
            exit;
        }
    }

    /**
     * Cambiar estado activo del usuario
     */
    public function cambiarEstado($id)
    {
        try {
            error_log("UsuarioController::cambiarEstado llamado con ID: " . $id);
            error_log("Método HTTP: " . $_SERVER['REQUEST_METHOD']);

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log("Método no es POST, redirigiendo");
                header('Location: ' . url('/usuario'));
                exit;
            }

            // Verificar que el usuario existe
            $usuario = $this->usuarioModel->obtenerPorId($id);
            error_log("Usuario obtenido: " . ($usuario ? json_encode($usuario) : 'null'));

            if (!$usuario) {
                $error = urlencode('Usuario no encontrado');
                error_log("Usuario no encontrado con ID: " . $id);
                header('Location: ' . url("/usuario?error=$error"));
                exit;
            }

            // Cambiar estado
            $nuevoEstado = $usuario['activo'] ? 0 : 1;
            error_log("Estado actual: " . $usuario['activo'] . ", nuevo estado: " . $nuevoEstado);

            $resultado = $this->usuarioModel->cambiarEstado($id, $nuevoEstado);
            error_log("Resultado del cambio: " . ($resultado ? 'true' : 'false'));

            if ($resultado) {
                $estado = $nuevoEstado ? 'activado' : 'desactivado';
                $success = urlencode("Usuario $estado exitosamente");
                header('Location: ' . url("/usuario?success=$success"));
                exit;
            } else {
                $error = urlencode('Error al cambiar el estado del usuario');
                header('Location: ' . url("/usuario?error=$error"));
                exit;
            }
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::cambiarEstado: " . $e->getMessage());
            $error = urlencode('Error interno del servidor');
            header('Location: ' . url("/usuario?error=$error"));
            exit;
        }
    }

    /**
     * Validar datos de usuario
     */
    private function validarDatos($datos, $esCreacion = true, $idUsuario = null)
    {
        $errores = [];

        // Validar nombre
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es requerido';
        } elseif (strlen($datos['nombre']) < 2) {
            $errores[] = 'El nombre debe tener al menos 2 caracteres';
        }

        // Validar email
        if (empty($datos['email'])) {
            $errores[] = 'El email es requerido';
        } elseif (!Validator::isEmail($datos['email'])) {
            $errores[] = 'El email no tiene un formato válido';
        }

        // Validar password (solo en creación o si se proporciona en edición)
        if ($esCreacion || !empty($datos['password'])) {
            if (empty($datos['password'])) {
                $errores[] = 'La contraseña es requerida';
            } elseif (strlen($datos['password']) < 6) {
                $errores[] = 'La contraseña debe tener al menos 6 caracteres';
            } elseif ($datos['password'] !== $datos['confirmar_password']) {
                $errores[] = 'Las contraseñas no coinciden';
            }
        }

        // Validar rol_id
        if (empty($datos['rol_id']) || !is_numeric($datos['rol_id'])) {
            $errores[] = 'Debe seleccionar un rol válido';
        } else {
            // Verificar que el rol existe y está activo
            $rol = $this->rolModel->obtenerPorId($datos['rol_id']);
            if (!$rol) {
                $errores[] = 'El rol seleccionado no existe';
            } elseif (!$rol['activo']) {
                $errores[] = 'El rol seleccionado no está activo';
            }
        }

        return $errores;
    }

    /**
     * Ver pedidos del usuario actual
     */
    public function pedidos()
    {
        if (!\Core\Helpers\SessionHelper::isAuthenticated()) {
            header('Location: ' . url('/auth/login'));
            exit;
        }

        // Verificación adicional de seguridad
        $userRole = \Core\Helpers\SessionHelper::getRole();
        $userPermissions = \Core\Helpers\SessionHelper::getPermissions();

        // Solo permitir acceso a admins o usuarios con rol 'usuario'
        $isAdmin = in_array('usuarios', $userPermissions ?: []);
        $isCliente = false;

        if (is_array($userRole) && isset($userRole['nombre'])) {
            $isCliente = ($userRole['nombre'] === 'usuario');
        } elseif (is_string($userRole)) {
            $isCliente = ($userRole === 'usuario');
        } else {
            // Verificar por permisos - clientes típicamente solo tienen 'perfil'
            $isCliente = in_array('perfil', $userPermissions ?: []) &&
                !in_array('productos', $userPermissions ?: []);
        }

        if (!$isAdmin && !$isCliente) {
            error_log("❌ Acceso denegado a pedidos: usuario no es admin ni cliente");
            header('Location: ' . url('/error/forbidden'));
            exit;
        }

        // ✅ NUEVO: Verificar si viene de un pago pendiente
        $paymentStatus = $_GET['payment_status'] ?? null;
        $externalRef = $_GET['external_ref'] ?? null;

        $showPaymentModal = false;
        $externalReference = '';

        if ($paymentStatus === 'pending' && $externalRef) {
            $showPaymentModal = true;
            $externalReference = $externalRef;

            // Solo loguear para tracking - sin actualizar base de datos
            error_log("⏳ PAGO PENDIENTE - Redirigido a pedidos - Referencia: " . $externalRef);
        }

        try {
            $usuario = \Core\Helpers\SessionHelper::getUser();

            // Obtener pedidos del usuario
            $pedidoModel = new \Models\Pedido();
            $pedidos = [];

            if ($isAdmin) {
                // Los admins pueden ver todos los pedidos
                try {
                    $pedidos = $pedidoModel->obtenerTodosConDirecciones();
                } catch (\Exception $e) {
                    $db = \Core\Database::getConexion();
                    $stmt = $db->query("SELECT * FROM pedidos ORDER BY creado_en DESC");
                    $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                }
            } else {
                // Los clientes solo pueden ver sus propios pedidos
                try {
                    $todosLosPedidos = $pedidoModel->obtenerTodosConDirecciones();
                    // Filtrar solo los pedidos del usuario actual
                    foreach ($todosLosPedidos as $pedido) {
                        if ($pedido['cliente_id'] == $usuario['id']) {
                            $pedidos[] = $pedido;
                        }
                    }
                } catch (\Exception $e) {
                    // Fallback: obtener pedidos básicos
                    $db = \Core\Database::getConexion();
                    $stmt = $db->prepare("SELECT * FROM pedidos WHERE cliente_id = ? ORDER BY creado_en DESC");
                    $stmt->execute([$usuario['id']]);
                    $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                }
            }

            // Obtener detalles de cada pedido
            $detalleModel = new \Models\DetallePedido();
            $pedidoDireccionModel = new \Models\PedidoDireccion();

            foreach ($pedidos as &$pedido) {
                // Obtener detalles del pedido con información de productos
                try {
                    $pedido['detalles'] = $detalleModel->obtenerPorPedidoConImagenes($pedido['id']);
                } catch (\Exception $e) {
                    // Fallback al método original si hay error
                    try {
                        $pedido['detalles'] = $detalleModel->obtenerPorPedidoConImagenes($pedido['id']);
                    } catch (\Exception $e2) {
                        $pedido['detalles'] = [];
                    }
                }

                // Calcular total si no está presente o es 0
                if (!isset($pedido['total']) || $pedido['total'] == 0) {
                    $total = 0;
                    if (isset($pedido['detalles']) && is_array($pedido['detalles'])) {
                        foreach ($pedido['detalles'] as $detalle) {
                            $precio = floatval($detalle['precio_unitario'] ?? 0);
                            $cantidad = intval($detalle['cantidad'] ?? 0);
                            $total += $precio * $cantidad;
                        }
                    }
                    $pedido['total'] = $total;
                }

                // Obtener dirección del pedido
                try {
                    $pedido['direccion_envio'] = $pedidoDireccionModel->obtenerDireccionCompleta($pedido['id']);
                } catch (\Exception $e) {
                    $pedido['direccion_envio'] = 'Dirección no disponible';
                }
            }

            // ✅ NUEVO: Pasar variables a la vista para el modal
            require_once __DIR__ . '/../views/usuario/pedidos.php';
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::pedidos: " . $e->getMessage());
            header('Location: ' . url('/error'));
            exit;
        }
    }

    /**
     * Obtener detalles de un pedido específico (AJAX)
     */
    public function detallePedido($pedidoId = null)
    {
        // Asegurar que sea una petición AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Acceso no autorizado']);
            exit;
        }

        if (!\Core\Helpers\SessionHelper::isAuthenticated()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No autenticado']);
            exit;
        }

        if (!$pedidoId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID de pedido requerido']);
            exit;
        }

        try {
            $usuario = \Core\Helpers\SessionHelper::getUser();
            $userRole = \Core\Helpers\SessionHelper::getRole();
            $userPermissions = \Core\Helpers\SessionHelper::getPermissions();

            // Verificar permisos
            $isAdmin = in_array('usuarios', $userPermissions ?: []);
            $isCliente = false;

            if (is_array($userRole) && isset($userRole['nombre'])) {
                $isCliente = ($userRole['nombre'] === 'usuario');
            } elseif (is_string($userRole)) {
                $isCliente = ($userRole === 'usuario');
            } else {
                $isCliente = in_array('perfil', $userPermissions ?: []) &&
                    !in_array('productos', $userPermissions ?: []);
            }

            if (!$isAdmin && !$isCliente) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Sin permisos']);
                exit;
            }

            // Obtener el pedido
            $db = \Core\Database::getConexion();

            if ($isAdmin) {
                // Admin puede ver cualquier pedido
                $stmt = $db->prepare("SELECT * FROM pedidos WHERE id = ?");
                $stmt->execute([$pedidoId]);
            } else {
                // Cliente solo puede ver sus propios pedidos
                $stmt = $db->prepare("SELECT * FROM pedidos WHERE id = ? AND cliente_id = ?");
                $stmt->execute([$pedidoId, $usuario['id']]);
            }

            $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$pedido) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Pedido no encontrado']);
                exit;
            }

            // Obtener detalles del pedido
            $detalleModel = new \Models\DetallePedido();
            $pedidoDireccionModel = new \Models\PedidoDireccion();

            try {
                $pedido['detalles'] = $detalleModel->obtenerPorPedido($pedido['id']);
            } catch (\Exception $e) {
                $pedido['detalles'] = [];
            }

            // Calcular total si no está presente o es 0
            if (!isset($pedido['total']) || $pedido['total'] == 0) {
                $total = 0;
                if (isset($pedido['detalles']) && is_array($pedido['detalles'])) {
                    foreach ($pedido['detalles'] as $detalle) {
                        $precio = floatval($detalle['precio_unitario'] ?? 0);
                        $cantidad = intval($detalle['cantidad'] ?? 0);
                        $total += $precio * $cantidad;
                    }
                }
                $pedido['total'] = $total;
            }

            try {
                $pedido['direccion_envio'] = $pedidoDireccionModel->obtenerDireccionCompleta($pedido['id']);
            } catch (\Exception $e) {
                $pedido['direccion_envio'] = 'Dirección no disponible';
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'pedido' => $pedido
            ]);
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::detallePedido: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Error interno del servidor']);
        }
    }

    /**
     * Eliminar una dirección del usuario
     */
    public function eliminarDireccion()
    {
        header('Content-Type: application/json');

        try {
            // Verificar que el usuario esté autenticado
            if (!isset($_SESSION['usuario'])) {
                echo json_encode(['success' => false, 'message' => 'No autenticado']);
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit;
            }

            $usuario = $_SESSION['usuario'];
            $direccionId = $_POST['id'] ?? '';

            if (empty($direccionId)) {
                echo json_encode(['success' => false, 'message' => 'ID de dirección no proporcionado']);
                exit;
            }

            // Verificar que la dirección pertenece al usuario
            $conexion = \Core\Database::getConexion();
            $stmt = $conexion->prepare("SELECT id FROM direcciones WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$direccionId, $usuario['id']]);

            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Dirección no encontrada']);
                exit;
            }

            // Marcar como inactiva en lugar de eliminar
            $stmt = $conexion->prepare("UPDATE direcciones SET activa = 0 WHERE id = ? AND usuario_id = ?");
            $success = $stmt->execute([$direccionId, $usuario['id']]);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Dirección eliminada correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar la dirección']);
            }
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::eliminarDireccion: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Actualizar una dirección del usuario
     */
    public function actualizarDireccion()
    {
        header('Content-Type: application/json');

        try {
            // Verificar que el usuario esté autenticado
            if (!isset($_SESSION['usuario'])) {
                echo json_encode(['success' => false, 'message' => 'No autenticado']);
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit;
            }

            $usuario = $_SESSION['usuario'];
            $direccionId = $_POST['direccion_id'] ?? '';

            if (empty($direccionId)) {
                echo json_encode(['success' => false, 'message' => 'ID de dirección no proporcionado']);
                exit;
            }

            // Verificar que la dirección pertenece al usuario
            $conexion = \Core\Database::getConexion();
            $stmt = $conexion->prepare("SELECT id FROM direcciones WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$direccionId, $usuario['id']]);

            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Dirección no encontrada']);
                exit;
            }

            // Validar campos requeridos
            $direccion = $_POST['direccion'] ?? '';
            $distrito = $_POST['distrito'] ?? '';
            $provincia = $_POST['provincia'] ?? '';
            $departamento = $_POST['departamento'] ?? '';

            if (empty($direccion) || empty($distrito) || empty($provincia) || empty($departamento)) {
                echo json_encode(['success' => false, 'message' => 'Campos obligatorios faltantes']);
                exit;
            }

            // Si se marca como principal, desmarcar las demás
            $esPrincipal = isset($_POST['es_principal']) && $_POST['es_principal'] == '1' ? 1 : 0;
            if ($esPrincipal) {
                $stmt = $conexion->prepare("UPDATE direcciones SET es_principal = 0 WHERE usuario_id = ?");
                $stmt->execute([$usuario['id']]);
            }

            // Obtener el nombre del departamento si se envió el ID
            $nombreDepartamento = $departamento;
            /*if (is_numeric($departamento)) {
                $departamentosMap = [
                    '01' => 'Amazonas',
                    '02' => 'Áncash',
                    '03' => 'Apurímac',
                    '04' => 'Arequipa',
                    '05' => 'Ayacucho',
                    '06' => 'Cajamarca',
                    '07' => 'Callao',
                    '08' => 'Cusco',
                    '09' => 'Huancavelica',
                    '10' => 'Huánuco',
                    '11' => 'Ica',
                    '12' => 'Junín',
                    '13' => 'La Libertad',
                    '14' => 'Lambayeque',
                    '15' => 'Lima',
                    '16' => 'Loreto',
                    '17' => 'Madre de Dios',
                    '18' => 'Moquegua',
                    '19' => 'Pasco',
                    '20' => 'Piura',
                    '21' => 'Puno',
                    '22' => 'San Martín',
                    '23' => 'Tacna',
                    '24' => 'Tumbes',
                    '25' => 'Ucayali'
                ];
                $nombreDepartamento = $departamentosMap[$departamento] ?? $departamento;
            }*/

            $stmt = $conexion->prepare("
                UPDATE direcciones 
                SET 
                    nombre_direccion = ?,
                    tipo = ?,
                    direccion = ?,
                    distrito = ?,
                    provincia = ?,
                    departamento = ?,
                    referencia = ?,
                    es_principal = ?,
                    updated_at = NOW()
                WHERE id = ? AND usuario_id = ?
            ");

            // ✅ CORREGIDO: Convertir IDs a nombres antes de guardar
            $nombreProvincia = $this->convertirIdProvincia($provincia);
            $nombreDepartamento = $this->convertirIdDepartamento($departamento);

            $success = $stmt->execute([
                $_POST['nombre_direccion'] ?? '',
                $_POST['tipo'] ?? 'casa',
                $direccion,
                $distrito,
                $nombreProvincia,      // ← Usar nombre convertido, no ID
                $nombreDepartamento,   // ← Usar nombre convertido, no ID
                $_POST['referencia'] ?? '',
                $esPrincipal,
                $direccionId,
                $usuario['id']
            ]);

            if ($success) {
                $_SESSION['ultima_direccion_editada'] = $direccionId;
                echo json_encode(['success' => true, 'message' => 'Dirección actualizada correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la dirección']);
            }
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::actualizarDireccion: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Mostrar direcciones del usuario
     */
    public function misDirecciones()
    {
        try {
            if (!isset($_SESSION['usuario'])) {
                header('Location: ' . url('/auth/login'));
                exit;
            }

            $usuario = $_SESSION['usuario'];
            $direcciones = $this->usuarioModel->obtenerDirecciones($usuario['id']);

            // Procesar direcciones para normalizar los datos
            foreach ($direcciones as &$dir) {
                // Los nombres ya están guardados como texto (VARCHAR)
                $dir['departamento_nombre'] = $dir['departamento'] ?? '';
                $dir['provincia_nombre'] = $dir['provincia'] ?? '';

                // Normalizar el campo 'tipo' a 'tipo_direccion' para la vista
                $dir['tipo_direccion'] = $dir['tipo'] ?? 'casa';

                // Asegurar que los campos opcionales existan
                $dir['nombre_direccion'] = $dir['nombre_direccion'] ?? 'Mi dirección';
                $dir['referencia'] = $dir['referencia'] ?? '';
            }

            require_once __DIR__ . '/../views/usuario/direcciones.php';
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::misDirecciones: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            header('Location: ' . url('/auth/profile?error=' . urlencode('Error al cargar direcciones')));
            exit;
        }
    }

    /**
     * Editar dirección del usuario
     */
    public function editarDireccion()
    {
        try {
            if (!isset($_SESSION['usuario'])) {
                header('Location: ' . url('/auth/login'));
                exit;
            }

            $usuario = $_SESSION['usuario'];
            $direccionId = $_GET['id'] ?? '';

            if (empty($direccionId)) {
                header('Location: ' . url('/usuario/mis-direcciones?error=' . urlencode('ID de dirección no válido')));
                exit;
            }

            $direccion = $this->usuarioModel->obtenerDireccion($direccionId, $usuario['id']);

            if (!$direccion) {
                header('Location: ' . url('/usuario/mis-direcciones?error=' . urlencode('Dirección no encontrada')));
                exit;
            }

            // Normalizar el campo 'tipo' a 'tipo_direccion' para el formulario
            $direccion['tipo_direccion'] = $direccion['tipo'] ?? 'casa';

            // No necesitamos consultar departamentos/provincias ya que son campos de texto libre
            $departamentos = [];
            $provincias = [];

            require_once __DIR__ . '/../views/usuario/editar_direccion.php';
        } catch (\Exception $e) {
            error_log("Error en UsuarioController::editarDireccion: " . $e->getMessage());
            header('Location: ' . url('/usuario/mis-direcciones?error=' . urlencode('Error al cargar dirección')));
            exit;
        }
    }

    /**
     * Convertir ID de departamento a nombre
     */
    private function convertirIdDepartamento($id)
    {
        $departamentos = [
            '01' => 'Amazonas',
            '02' => 'Áncash',
            '03' => 'Apurímac',
            '04' => 'Arequipa',
            '05' => 'Ayacucho',
            '06' => 'Cajamarca',
            '07' => 'Callao',
            '08' => 'Cusco',
            '09' => 'Huancavelica',
            '10' => 'Huánuco',
            '11' => 'Ica',
            '12' => 'Junín',
            '13' => 'La Libertad',
            '14' => 'Lambayeque',
            '15' => 'Lima',
            '16' => 'Loreto',
            '17' => 'Madre de Dios',
            '18' => 'Moquegua',
            '19' => 'Pasco',
            '20' => 'Piura',
            '21' => 'Puno',
            '22' => 'San Martín',
            '23' => 'Tacna',
            '24' => 'Tumbes',
            '25' => 'Ucayali'
        ];
        return $departamentos[$id] ?? $id;
    }

    /**
     * Convertir ID de provincia a nombre
     */
    private function convertirIdProvincia($id)
    {
        $provincias = [
            // Amazonas (01)
            '0101' => 'Chachapoyas',
            '0102' => 'Bagua',
            '0103' => 'Bongará',
            '0104' => 'Condorcanqui',
            '0105' => 'Luya',
            '0106' => 'Rodríguez de Mendoza',
            '0107' => 'Utcubamba',

            // Áncash (02)
            '0201' => 'Huaraz',
            '0202' => 'Aija',
            '0203' => 'Antonio Raymondi',
            '0204' => 'Asunción',
            '0205' => 'Bolognesi',
            '0206' => 'Carhuaz',
            '0207' => 'Carlos Fermín Fitzcarrald',
            '0208' => 'Casma',
            '0209' => 'Corongo',
            '0210' => 'Huari',
            '0211' => 'Huarmey',
            '0212' => 'Huaylas',
            '0213' => 'Mariscal Luzuriaga',
            '0214' => 'Ocros',
            '0215' => 'Pallasca',
            '0216' => 'Pomabamba',
            '0217' => 'Recuay',
            '0218' => 'Santa',
            '0219' => 'Sihuas',
            '0220' => 'Yungay',

            // Apurímac (03)
            '0301' => 'Abancay',
            '0302' => 'Andahuaylas',
            '0303' => 'Antabamba',
            '0304' => 'Aymaraes',
            '0305' => 'Cotabambas',
            '0306' => 'Chincheros',
            '0307' => 'Grau',

            // Arequipa (04)
            '0401' => 'Arequipa',
            '0402' => 'Camaná',
            '0403' => 'Caravelí',
            '0404' => 'Castilla',
            '0405' => 'Caylloma',
            '0406' => 'Condesuyos',
            '0407' => 'Islay',
            '0408' => 'La Uniòn',

            // Ayacucho (05)
            '0501' => 'Huamanga',
            '0502' => 'Cangallo',
            '0503' => 'Huanca Sancos',
            '0504' => 'Huanta',
            '0505' => 'La Mar',
            '0506' => 'Lucanas',
            '0507' => 'Parinacochas',
            '0508' => 'Pàucar del Sara Sara',
            '0509' => 'Sucre',
            '0510' => 'Víctor Fajardo',
            '0511' => 'Vilcas Huamán',

            // Cajamarca (06)
            '0601' => 'Cajamarca',
            '0602' => 'Cajabamba',
            '0603' => 'Celendín',
            '0604' => 'Chota',
            '0605' => 'Contumazá',
            '0606' => 'Cutervo',
            '0607' => 'Hualgayoc',
            '0608' => 'Jaén',
            '0609' => 'San Ignacio',
            '0610' => 'San Marcos',
            '0611' => 'San Miguel',
            '0612' => 'San Pablo',
            '0613' => 'Santa Cruz',

            // Callao (07)
            '0701' => 'Callao',

            // Cusco (08)
            '0801' => 'Cusco',
            '0802' => 'Acomayo',
            '0803' => 'Anta',
            '0804' => 'Calca',
            '0805' => 'Canas',
            '0806' => 'Canchis',
            '0807' => 'Chumbivilcas',
            '0808' => 'Espinar',
            '0809' => 'La Convención',
            '0810' => 'Paruro',
            '0811' => 'Paucartambo',
            '0812' => 'Quispicanchi',
            '0813' => 'Urubamba',

            // Huancavelica (09)
            '0901' => 'Huancavelica',
            '0902' => 'Acobamba',
            '0903' => 'Angaraes',
            '0904' => 'Castrovirreyna',
            '0905' => 'Churcampa',
            '0906' => 'Huaytará',
            '0907' => 'Tayacaja',

            // Huánuco (10)
            '1001' => 'Huánuco',
            '1002' => 'Ambo',
            '1003' => 'Dos de Mayo',
            '1004' => 'Huacaybamba',
            '1005' => 'Huamalíes',
            '1006' => 'Leoncio Prado',
            '1007' => 'Marañón',
            '1008' => 'Pachitea',
            '1009' => 'Puerto Inca',
            '1010' => 'Lauricocha',
            '1011' => 'Yarowilca',

            // Ica (11)
            '1101' => 'Ica',
            '1102' => 'Chincha',
            '1103' => 'Nazca',
            '1104' => 'Palpa',
            '1105' => 'Pisco',

            // Junín (12)
            '1201' => 'Huancayo',
            '1202' => 'Concepción',
            '1203' => 'Chanchamayo',
            '1204' => 'Jauja',
            '1205' => 'Junín',
            '1206' => 'Satipo',
            '1207' => 'Tarma',
            '1208' => 'Yauli',
            '1209' => 'Chupaca',

            // La Libertad (13)
            '1301' => 'Trujillo',
            '1302' => 'Ascope',
            '1303' => 'Bolívar',
            '1304' => 'Chepén',
            '1305' => 'Julcán',
            '1306' => 'Otuzco',
            '1307' => 'Pacasmayo',
            '1308' => 'Pataz',
            '1309' => 'Sánchez Carrión',
            '1310' => 'Santiago de Chuco',
            '1311' => 'Gran Chimú',
            '1312' => 'Virú',

            // Lambayeque (14)
            '1401' => 'Chiclayo',
            '1402' => 'Ferreñafe',
            '1403' => 'Lambayeque',

            // Lima (15)
            '1501' => 'Lima',
            '1502' => 'Barranca',
            '1503' => 'Cajatambo',
            '1504' => 'Canta',
            '1505' => 'Cañete',
            '1506' => 'Huaral',
            '1507' => 'Huaura',
            '1508' => 'Huarochirí',
            '1509' => 'Oyón',
            '1510' => 'Yauyos',

            // Loreto (16)
            '1601' => 'Maynas',
            '1602' => 'Alto Amazonas',
            '1603' => 'Loreto',
            '1604' => 'Mariscal Ramón Castilla',
            '1605' => 'Requena',
            '1606' => 'Ucayali',
            '1607' => 'Datem del Marañón',
            '1608' => 'Putumayo',

            // Madre de Dios (17)
            '1701' => 'Tambopata',
            '1702' => 'Manu',
            '1703' => 'Tahuamanu',

            // Moquegua (18)
            '1801' => 'Mariscal Nieto',
            '1802' => 'General Sánchez Cerro',
            '1803' => 'Ilo',

            // Pasco (19)
            '1901' => 'Pasco',
            '1902' => 'Daniel Alcides Carrión',
            '1903' => 'Oxapampa',

            // Piura (20)
            '2001' => 'Piura',
            '2002' => 'Ayabaca',
            '2003' => 'Huancabamba',
            '2004' => 'Morropón',
            '2005' => 'Paita',
            '2006' => 'Sullana',
            '2007' => 'Talara',
            '2008' => 'Sechura',

            // Puno (21)
            '2101' => 'Puno',
            '2102' => 'Azángaro',
            '2103' => 'Carabaya',
            '2104' => 'Chucuito',
            '2105' => 'El Collao',
            '2106' => 'Huancané',
            '2107' => 'Lampa',
            '2108' => 'Melgar',
            '2109' => 'Moho',
            '2110' => 'San Antonio de Putina',
            '2111' => 'San Román',
            '2112' => 'Sandia',
            '2113' => 'Yunguyo',

            // San Martín (22)
            '2201' => 'Moyobamba',
            '2202' => 'Bellavista',
            '2203' => 'El Dorado',
            '2204' => 'Huallaga',
            '2205' => 'Lamas',
            '2206' => 'Mariscal Cáceres',
            '2207' => 'Picota',
            '2208' => 'Rioja',
            '2209' => 'San Martín',
            '2210' => 'Tocache',

            // Tacna (23)
            '2301' => 'Tacna',
            '2302' => 'Candarave',
            '2303' => 'Jorge Basadre',
            '2304' => 'Tarata',

            // Tumbes (24)
            '2401' => 'Tumbes',
            '2402' => 'Contralmirante Villar',
            '2403' => 'Zarumilla',

            // Ucayali (25)
            '2501' => 'Coronel Portillo',
            '2502' => 'Atalaya',
            '2503' => 'Padre Abad',
            '2504' => 'Purús'
        ];
        return $provincias[$id] ?? $id;
    }
}
