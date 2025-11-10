<?php

namespace Models;

class CarritoTemporal
{
    private $db;

    public function __construct()
    {
        // ✅ CORREGIDO: Usar getConexion() en lugar de new Database()
        $this->db = \Core\Database::getConexion();
    }

    /**
     * Agregar producto al carrito temporal
     */
    public function agregarProducto($session_id, $producto_id, $cantidad = 1, $variante_id = null, $usuario_id = null)
    {
        // Verificar si ya existe el producto en el carrito
        $query = "SELECT id, cantidad FROM carrito_temporal 
                 WHERE session_id = :session_id 
                 AND producto_id = :producto_id 
                 AND (variante_id = :variante_id OR (:variante_id IS NULL AND variante_id IS NULL))
                 AND (usuario_id = :usuario_id OR (:usuario_id IS NULL AND usuario_id IS NULL))";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':session_id' => $session_id,
            ':producto_id' => $producto_id,
            ':variante_id' => $variante_id,
            ':usuario_id' => $usuario_id
        ]);

        if ($stmt->rowCount() > 0) {
            // Actualizar cantidad
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $nueva_cantidad = $row['cantidad'] + $cantidad;

            $update_query = "UPDATE carrito_temporal SET cantidad = :cantidad WHERE id = :id";
            $update_stmt = $this->db->prepare($update_query);
            return $update_stmt->execute([
                ':cantidad' => $nueva_cantidad,
                ':id' => $row['id']
            ]);
        } else {
            // Insertar nuevo item
            $insert_query = "INSERT INTO carrito_temporal (session_id, usuario_id, producto_id, variante_id, cantidad) 
                           VALUES (:session_id, :usuario_id, :producto_id, :variante_id, :cantidad)";
            $insert_stmt = $this->db->prepare($insert_query);
            return $insert_stmt->execute([
                ':session_id' => $session_id,
                ':usuario_id' => $usuario_id,
                ':producto_id' => $producto_id,
                ':variante_id' => $variante_id,
                ':cantidad' => $cantidad
            ]);
        }
    }

    /**
     * Obtener carrito completo
     */
    public function obtenerCarrito($session_id, $usuario_id = null)
{
    if ($usuario_id) {
        // Usuario logueado: obtener por usuario_id O session_id
        $query = "SELECT ct.*, p.nombre, p.precio, p.stock,
                     (SELECT img.nombre_imagen 
                      FROM imagenes_producto img 
                      WHERE img.producto_id = ct.producto_id 
                      ORDER BY img.id ASC 
                      LIMIT 1) as imagen_principal,
                     v.talla, v.color, v.imagen as variante_imagen
             FROM carrito_temporal ct
             JOIN productos p ON ct.producto_id = p.id
             LEFT JOIN variantes_producto v ON ct.variante_id = v.id
             WHERE ct.usuario_id = :usuario_id OR ct.session_id = :session_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':session_id' => $session_id
        ]);
    } else {
        // Usuario invitado: obtener solo por session_id
        $query = "SELECT ct.*, p.nombre, p.precio, p.stock,
                     (SELECT img.nombre_imagen 
                      FROM imagenes_producto img 
                      WHERE img.producto_id = ct.producto_id 
                      ORDER BY img.id ASC 
                      LIMIT 1) as imagen_principal,
                     v.talla, v.color, v.imagen as variante_imagen
             FROM carrito_temporal ct
             JOIN productos p ON ct.producto_id = p.id
             LEFT JOIN variantes_producto v ON ct.variante_id = v.id
             WHERE ct.session_id = :session_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':session_id' => $session_id]);
    }

    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

    /**
     * Transferir carrito de sesión a usuario
     */
    public function transferirAUsuario($session_id, $user_id)
    {
        try {
            $query = "UPDATE carrito_temporal 
                     SET usuario_id = :user_id, session_id = NULL
                     WHERE session_id = :session_id AND usuario_id IS NULL";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':user_id' => $user_id,
                ':session_id' => $session_id
            ]);
        } catch (\Exception $e) {
            error_log("Error transferiendo carrito: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar producto del carrito
     */
    public function eliminarProducto($id, $session_id, $usuario_id = null)
    {
        if ($usuario_id) {
            $query = "DELETE FROM carrito_temporal 
                     WHERE id = :id AND (usuario_id = :usuario_id OR session_id = :session_id)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':id' => $id,
                ':usuario_id' => $usuario_id,
                ':session_id' => $session_id
            ]);
        } else {
            $query = "DELETE FROM carrito_temporal 
                     WHERE id = :id AND session_id = :session_id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':id' => $id,
                ':session_id' => $session_id
            ]);
        }
    }

    /**
     * Limpiar carrito
     */
    public function limpiarCarrito($session_id, $usuario_id = null)
    {
        if ($usuario_id) {
            $query = "DELETE FROM carrito_temporal 
                     WHERE usuario_id = :usuario_id OR session_id = :session_id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':session_id' => $session_id
            ]);
        } else {
            $query = "DELETE FROM carrito_temporal WHERE session_id = :session_id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':session_id' => $session_id]);
        }
    }
}
