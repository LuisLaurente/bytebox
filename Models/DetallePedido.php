<?php

namespace Models;

use Core\Database;
use PDO;

class DetallePedido
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::getConexion();
    }

    public function crear($pedido_id, $producto_id, $cantidad, $precio_unitario, $variante_id = null)
    {
        $stmt = $this->db->prepare("INSERT INTO detalle_pedido (pedido_id, producto_id, variante_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$pedido_id, $producto_id, $variante_id, $cantidad, $precio_unitario]);
    }

    public function obtenerPorPedido($pedido_id)
    {
        $sql = "SELECT DISTINCT
                dp.id,
                dp.pedido_id,
                dp.producto_id, 
                dp.variante_id,
                dp.cantidad,
                dp.precio_unitario,
                p.nombre as producto_nombre,
                p.descripcion as producto_descripcion,
                vp.talla as variante_talla,
                vp.color as variante_color
            FROM detalle_pedido dp
            LEFT JOIN productos p ON dp.producto_id = p.id
            LEFT JOIN variantes_producto vp ON dp.variante_id = vp.id
            WHERE dp.pedido_id = ?
            ORDER BY dp.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pedido_id]);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // DEBUG CRÍTICO
        error_log("=== DEBUG DETALLE PEDIDO CON DISTINCT ===");
        error_log("Pedido ID: " . $pedido_id);
        error_log("Registros encontrados: " . count($resultado));
        foreach ($resultado as $index => $fila) {
            error_log("Registro {$index}:");
            error_log("  - ID detalle: " . $fila['id']);
            error_log("  - Producto ID: " . $fila['producto_id']);
            error_log("  - Variante ID: " . $fila['variante_id']);
            error_log("  - Cantidad: " . $fila['cantidad']);
            error_log("  - Precio: " . $fila['precio_unitario']);
            error_log("  - Nombre: " . $fila['producto_nombre']);
        }

        return $resultado;
    }

    public function obtenerPorPedidoConProductos($pedido_id)
    {
        $sql = "SELECT 
                    dp.*,
                    p.nombre as producto_nombre,
                    p.descripcion as producto_descripcion,
                    vp.talla as variante_talla,
                    vp.color as variante_color
                FROM detalle_pedido dp
                LEFT JOIN productos p ON dp.producto_id = p.id
                LEFT JOIN variantes_producto vp ON dp.variante_id = vp.id
                WHERE dp.pedido_id = ?
                ORDER BY dp.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pedido_id]);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);


        return $resultado;
    }

    public function eliminarPorPedido($pedido_id)
    {
        $stmt = $this->db->prepare("DELETE FROM detalle_pedido WHERE pedido_id = ?");
        return $stmt->execute([$pedido_id]);
    }
    public function existePedido($pedido_id)
    {
        $sql = "SELECT COUNT(*) FROM detalle_pedido WHERE pedido_id = :pedido_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pedido_id', $pedido_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    // En Models/DetallePedido.php - agregar este método
    public function obtenerPorPedidoConImagenes($pedido_id)
    {
        $sql = "SELECT DISTINCT
            dp.*,
            p.nombre as producto_nombre,
            p.descripcion as producto_descripcion,
            vp.talla as variante_talla,
            vp.color as variante_color,
            (SELECT ip.nombre_imagen 
             FROM imagenes_producto ip 
             WHERE ip.producto_id = dp.producto_id 
             ORDER BY ip.id LIMIT 1) as imagen_producto
        FROM detalle_pedido dp
        LEFT JOIN productos p ON dp.producto_id = p.id
        LEFT JOIN variantes_producto vp ON dp.variante_id = vp.id
        WHERE dp.pedido_id = ?
        ORDER BY dp.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pedido_id]);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // DEBUG: Verificar que no hay duplicados
        error_log("=== DEBUG DETALLE PEDIDO CON IMÁGENES (SIN DUPLICADOS) ===");
        error_log("Pedido ID: " . $pedido_id);
        error_log("Registros encontrados: " . count($resultado));
        foreach ($resultado as $index => $fila) {
            error_log("Registro {$index}: Producto ID: " . $fila['producto_id'] . " - " . $fila['producto_nombre']);
        }

        return $resultado;
    }
}
