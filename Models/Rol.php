<?php

namespace Models;

use Core\Database;

class Rol
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtener todos los roles
     */
    public function obtenerTodos()
    {
        try {
            $sql = "SELECT * FROM roles ORDER BY nombre ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al obtener roles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener roles activos
     */
    public function obtenerActivos()
    {
        try {
            $sql = "SELECT * FROM roles WHERE activo = 1 ORDER BY nombre ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al obtener roles activos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener rol por ID
     */
    public function obtenerPorId($id)
    {
        try {
            $sql = "SELECT * FROM roles WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al obtener rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener rol por nombre
     */
    public function obtenerPorNombre($nombre)
    {
        try {
            $sql = "SELECT * FROM roles WHERE nombre = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$nombre]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al obtener rol por nombre: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear nuevo rol
     */
    public function crear($datos)
    {
        try {
            $sql = "INSERT INTO roles (nombre, descripcion, permisos, activo) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            
            $permisos = is_array($datos['permisos']) ? json_encode($datos['permisos']) : $datos['permisos'];
            
            return $stmt->execute([
                $datos['nombre'],
                $datos['descripcion'] ?? '',
                $permisos,
                $datos['activo'] ?? 1
            ]);
        } catch (\PDOException $e) {
            error_log("Error al crear rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar rol
     */
    public function actualizar($id, $datos)
    {
        try {
            $campos = [];
            $valores = [];

            if (isset($datos['nombre'])) {
                $campos[] = "nombre = ?";
                $valores[] = $datos['nombre'];
            }
            if (isset($datos['descripcion'])) {
                $campos[] = "descripcion = ?";
                $valores[] = $datos['descripcion'];
            }
            if (isset($datos['permisos'])) {
                $campos[] = "permisos = ?";
                $permisos = is_array($datos['permisos']) ? json_encode($datos['permisos']) : $datos['permisos'];
                $valores[] = $permisos;
            }
            if (isset($datos['activo'])) {
                $campos[] = "activo = ?";
                $valores[] = $datos['activo'];
            }

            if (empty($campos)) {
                return false;
            }

            $valores[] = $id;

            $sql = "UPDATE roles SET " . implode(', ', $campos) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($valores);
        } catch (\PDOException $e) {
            error_log("Error al actualizar rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar rol
     */
    public function eliminar($id)
    {
        try {
            // Verificar si hay usuarios con este rol
            $sqlCheck = "SELECT COUNT(*) FROM usuarios WHERE rol_id = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$id]);
            $count = $stmtCheck->fetchColumn();

            if ($count > 0) {
                return ['success' => false, 'message' => 'No se puede eliminar el rol porque hay usuarios asignados a él'];
            }

            $sql = "DELETE FROM roles WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id]);
            
            return ['success' => $result, 'message' => $result ? 'Rol eliminado exitosamente' : 'Error al eliminar el rol'];
        } catch (\PDOException $e) {
            error_log("Error al eliminar rol: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }

    /**
     * Cambiar estado activo del rol
     */
    public function cambiarEstado($id, $activo)
    {
        try {
            $sql = "UPDATE roles SET activo = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$activo, $id]);
        } catch (\PDOException $e) {
            error_log("Error al cambiar estado del rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un rol tiene un permiso específico
     */
    public function tienePermiso($rolId, $permiso)
    {
        try {
            $rol = $this->obtenerPorId($rolId);
            if (!$rol || !$rol['activo']) {
                return false;
            }

            $permisos = json_decode($rol['permisos'], true);
            return is_array($permisos) && in_array($permiso, $permisos);
        } catch (\Exception $e) {
            error_log("Error al verificar permiso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener permisos de un rol
     */
    public function obtenerPermisos($rolId)
    {
        try {
            $rol = $this->obtenerPorId($rolId);
            if (!$rol) {
                return [];
            }

            $permisos = json_decode($rol['permisos'], true);
            return is_array($permisos) ? $permisos : [];
        } catch (\Exception $e) {
            error_log("Error al obtener permisos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si el nombre del rol ya existe
     */
    public function existeNombre($nombre, $excludeId = null)
    {
        try {
            $sql = "SELECT COUNT(*) FROM roles WHERE nombre = ?";
            $params = [$nombre];
            
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Error al verificar nombre de rol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de roles
     */
    public function obtenerEstadisticas()
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
                        SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivos
                    FROM roles";
            $stmt = $this->db->query($sql);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al obtener estadísticas de roles: " . $e->getMessage());
            return ['total' => 0, 'activos' => 0, 'inactivos' => 0];
        }
    }

    /**
     * Obtener roles con conteo de usuarios
     */
    public function obtenerConUsuarios()
    {
        try {
            $sql = "SELECT 
                        r.*,
                        COUNT(u.id) as total_usuarios
                    FROM roles r
                    LEFT JOIN usuarios u ON r.id = u.rol_id
                    GROUP BY r.id
                    ORDER BY r.nombre ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error al obtener roles con usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener lista de permisos disponibles del sistema
     */
    public function obtenerPermisosDisponibles()
    {
        return [
            'usuarios' => 'Gestión de usuarios',
            'roles' => 'Gestión de roles y permisos',
            'productos' => 'Gestión de productos',
            'categorias' => 'Gestión de categorías',
            'pedidos' => 'Gestión de pedidos',
            'promociones' => 'Gestión de promociones',
            'cupones' => 'Gestión de cupones',
            'usuarios' => 'Gestión de usuarios',
            'reportes' => 'Acceso a reportes',
            'configuracion' => 'Configuración del sistema',
            'carga_masiva' => 'Carga masiva de productos',
            'estadisticas' => 'Ver estadísticas',
            'perfil' => 'Gestionar perfil propio',
            'pedidos_propios' => 'Ver pedidos propios'
        ];
    }
}
