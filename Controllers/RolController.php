<?php

namespace Controllers;

use Models\Rol;
use Core\Helpers\Validator;
use Core\Helpers\Sanitizer;

class RolController extends BaseController
{
    private $rolModel;

    public function __construct()
    {
        // Verificar autenticación y permisos
        
        $this->rolModel = new Rol();
    }

    /**
     * Mostrar lista de roles
     */
    public function index()
    {
        try {
            $roles = $this->rolModel->obtenerConUsuarios();
            $estadisticas = $this->rolModel->obtenerEstadisticas();
            
            // Procesar mensajes de estado
            $success = $_GET['success'] ?? '';
            $error = $_GET['error'] ?? '';
            
            require_once __DIR__ . '/../views/rol/index.php';
        } catch (\Exception $e) {
            error_log("Error en RolController::index: " . $e->getMessage());
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
            $permisosDisponibles = $this->rolModel->obtenerPermisosDisponibles();
            require_once __DIR__ . '/../views/rol/crear.php';
        } catch (\Exception $e) {
            error_log("Error en RolController::crear: " . $e->getMessage());
            header('Location: ' . url('/error'));
            exit;
        }
    }

    /**
     * Procesar creación de rol
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . url('/rol'));
                exit;
            }

            // Sanitizar datos
            $datos = [
                'nombre' => Sanitizer::cleanString($_POST['nombre'] ?? ''),
                'descripcion' => Sanitizer::cleanString($_POST['descripcion'] ?? ''),
                'permisos' => $_POST['permisos'] ?? [],
                'activo' => isset($_POST['activo']) ? 1 : 0
            ];

            // Validar datos
            $errores = $this->validarDatos($datos);
            
            if (!empty($errores)) {
                $error = urlencode(implode(', ', $errores));
                header('Location: ' . url("/rol/crear?error=$error"));
                exit;
            }

            // Verificar si el nombre ya existe
            if ($this->rolModel->existeNombre($datos['nombre'])) {
                $error = urlencode('El nombre del rol ya existe');
                header('Location: ' . url("/rol/crear?error=$error"));
                exit;
            }

            // Crear rol
            $resultado = $this->rolModel->crear($datos);
            
            if ($resultado) {
                $success = urlencode('Rol creado exitosamente');
                header('Location: ' . url("/rol?success=$success"));
                exit;
            } else {
                $error = urlencode('Error al crear el rol');
                header('Location: ' . url("/rol/crear?error=$error"));
                exit;
            }
            
        } catch (\Exception $e) {
            error_log("Error en RolController::store: " . $e->getMessage());
            $error = urlencode('Error interno del servidor');
            header('Location: ' . url("/rol/crear?error=$error"));
            exit;
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function editar($id)
    {
        try {
            $rol = $this->rolModel->obtenerPorId($id);
            
            if (!$rol) {
                $error = urlencode('Rol no encontrado');
                header('Location: ' . url("/rol?error=$error"));
                exit;
            }

            // Decodificar permisos JSON
            $rol['permisos'] = json_decode($rol['permisos'], true) ?: [];
            
            $permisosDisponibles = $this->rolModel->obtenerPermisosDisponibles();
            
            require_once __DIR__ . '/../views/rol/editar.php';
        } catch (\Exception $e) {
            error_log("Error en RolController::editar: " . $e->getMessage());
            header('Location: ' . url('/error'));
            exit;
        }
    }

    /**
     * Procesar actualización de rol
     */
    public function actualizar($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . url('/rol'));
                exit;
            }

            // Verificar que el rol existe
            $rolExistente = $this->rolModel->obtenerPorId($id);
            if (!$rolExistente) {
                $error = urlencode('Rol no encontrado');
                header('Location: ' . url("/rol?error=$error"));
                exit;
            }

            // Sanitizar datos
            $datos = [
                'nombre' => Sanitizer::cleanString($_POST['nombre'] ?? ''),
                'descripcion' => Sanitizer::cleanString($_POST['descripcion'] ?? ''),
                'permisos' => $_POST['permisos'] ?? [],
                'activo' => isset($_POST['activo']) ? 1 : 0
            ];

            // Validar datos
            $errores = $this->validarDatos($datos, false, $id);
            
            if (!empty($errores)) {
                $error = urlencode(implode(', ', $errores));
                header('Location: ' . url("/rol/editar/$id?error=$error"));
                exit;
            }

            // Verificar si el nombre ya existe (excluyendo el rol actual)
            if ($this->rolModel->existeNombre($datos['nombre'], $id)) {
                $error = urlencode('El nombre del rol ya existe');
                header('Location: ' . url("/rol/editar/$id?error=$error"));
                exit;
            }

            // Actualizar rol
            $resultado = $this->rolModel->actualizar($id, $datos);
            
            if ($resultado) {
                $success = urlencode('Rol actualizado exitosamente');
                header('Location: ' . url("/rol?success=$success"));
                exit;
            } else {
                $error = urlencode('Error al actualizar el rol');
                header('Location: ' . url("/rol/editar/$id?error=$error"));
                exit;
            }
            
        } catch (\Exception $e) {
            error_log("Error en RolController::actualizar: " . $e->getMessage());
            $error = urlencode('Error interno del servidor');
            header('Location: ' . url("/rol/editar/$id?error=$error"));
            exit;
        }
    }

    /**
     * Eliminar rol
     */
    public function eliminar($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . url('/rol'));
                exit;
            }

            // Verificar que el rol existe
            $rol = $this->rolModel->obtenerPorId($id);
            if (!$rol) {
                $error = urlencode('Rol no encontrado');
                header('Location: ' . url("/rol?error=$error"));
                exit;
            }

            // No permitir eliminar roles por defecto
            if (in_array($rol['nombre'], ['admin', 'usuario'])) {
                $error = urlencode('No se puede eliminar un rol del sistema');
                header('Location: ' . url("/rol?error=$error"));
                exit;
            }

            // Eliminar rol
            $resultado = $this->rolModel->eliminar($id);
            
            if ($resultado['success']) {
                $success = urlencode($resultado['message']);
                header('Location: ' . url("/rol?success=$success"));
                exit;
            } else {
                $error = urlencode($resultado['message']);
                header('Location: ' . url("/rol?error=$error"));
                exit;
            }
            
        } catch (\Exception $e) {
            error_log("Error en RolController::eliminar: " . $e->getMessage());
            $error = urlencode('Error interno del servidor');
            header('Location: ' . url("/rol?error=$error"));
            exit;
        }
    }

    /**
     * Cambiar estado activo del rol
     */
    public function cambiarEstado($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . url('/rol'));
                exit;
            }

            // Verificar que el rol existe
            $rol = $this->rolModel->obtenerPorId($id);
            if (!$rol) {
                $error = urlencode('Rol no encontrado');
                header('Location: ' . url("/rol?error=$error"));
                exit;
            }

            // No permitir desactivar roles críticos
            if (in_array($rol['nombre'], ['admin', 'usuario']) && $rol['activo']) {
                $error = urlencode('No se puede desactivar un rol crítico del sistema');
                header('Location: ' . url("/rol?error=$error"));
                exit;
            }

            // Cambiar estado
            $nuevoEstado = $rol['activo'] ? 0 : 1;
            $resultado = $this->rolModel->cambiarEstado($id, $nuevoEstado);
            
            if ($resultado) {
                $estado = $nuevoEstado ? 'activado' : 'desactivado';
                $success = urlencode("Rol $estado exitosamente");
                header('Location: ' . url("/rol?success=$success"));
                exit;
            } else {
                $error = urlencode('Error al cambiar el estado del rol');
                header('Location: ' . url("/rol?error=$error"));
                exit;
            }
            
        } catch (\Exception $e) {
            error_log("Error en RolController::cambiarEstado: " . $e->getMessage());
            $error = urlencode('Error interno del servidor');
            header('Location: ' . url("/rol?error=$error"));
            exit;
        }
    }

    /**
     * Ver detalles de un rol
     */
    public function ver($id)
    {
        try {
            $rol = $this->rolModel->obtenerPorId($id);
            
            if (!$rol) {
                $error = urlencode('Rol no encontrado');
                header('Location: ' . url("/rol?error=$error"));
                exit;
            }

            // Decodificar permisos JSON
            $rol['permisos'] = json_decode($rol['permisos'], true) ?: [];
            $permisosDisponibles = $this->rolModel->obtenerPermisosDisponibles();
            
            require_once __DIR__ . '/../views/rol/ver.php';
        } catch (\Exception $e) {
            error_log("Error en RolController::ver: " . $e->getMessage());
            header('Location: ' . url('/error'));
            exit;
        }
    }

    /**
     * Validar datos de rol
     */
    private function validarDatos($datos, $esCreacion = true, $idRol = null)
    {
        $errores = [];

        // Validar nombre
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es requerido';
        } elseif (strlen($datos['nombre']) < 2) {
            $errores[] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $datos['nombre'])) {
            $errores[] = 'El nombre solo puede contener letras, números y guiones bajos';
        }

        // Validar descripción
        if (strlen($datos['descripcion']) > 255) {
            $errores[] = 'La descripción no puede exceder 255 caracteres';
        }

        // Validar permisos
        if (empty($datos['permisos']) || !is_array($datos['permisos'])) {
            $errores[] = 'Debe seleccionar al menos un permiso';
        } else {
            $permisosValidos = array_keys($this->rolModel->obtenerPermisosDisponibles());
            foreach ($datos['permisos'] as $permiso) {
                if (!in_array($permiso, $permisosValidos)) {
                    $errores[] = 'Permiso inválido: ' . $permiso;
                }
            }
        }

        return $errores;
    }
}
