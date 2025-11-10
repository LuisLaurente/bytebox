<?php

namespace Core\Services;

use Core\Database;
use Models\Producto;
use Models\CarritoTemporal;
use Exception;
use PDO;

class PedidoService
{
    /**
     * ✅ PROCESAR PEDIDO COMPLETO - Actualiza stock y limpia carrito
     * Este método se usará en AMBOS flujos: Contraentrega y Mercado Pago
     */
    public static function procesarPedidoCompleto($pedidoId, $usuarioId = null, $sessionId = null)
    {
        error_log("🎯 PedidoService::procesarPedidoCompleto INICIADO");
        error_log("   - Pedido ID: " . $pedidoId);
        error_log("   - Usuario ID: " . ($usuarioId ?? 'No proporcionado'));
        error_log("   - Session ID: " . ($sessionId ?? 'No proporcionado'));

        $db = Database::getConexion();
        
        try {
            $db->beginTransaction();
            error_log("   ✅ Transacción iniciada");

            // ✅ 1. OBTENER DETALLES DEL PEDIDO
            $detallesPedido = self::obtenerDetallesPedido($pedidoId);
            
            if (empty($detallesPedido)) {
                throw new Exception("No se encontraron detalles para el pedido ID: " . $pedidoId);
            }

            error_log("   📦 Productos en pedido: " . count($detallesPedido));

            // ✅ 2. ACTUALIZAR STOCK DE PRODUCTOS
            self::actualizarStockProductos($detallesPedido);
            error_log("   ✅ Stock actualizado correctamente");

            // ✅ 3. LIMPIAR CARRITO DEL USUARIO
            if ($usuarioId || $sessionId) {
                self::limpiarCarritoUsuario($usuarioId, $sessionId);
                error_log("   ✅ Carrito limpiado correctamente");
            }

            // ✅ 4. ACTUALIZAR ESTADO DEL PEDIDO A "completado"
            self::actualizarEstadoPedido($pedidoId, 'completado');
            error_log("   ✅ Estado del pedido actualizado a 'completado'");

            $db->commit();
            error_log("🎉 PedidoService::procesarPedidoCompleto EXITOSO");

            return true;

        } catch (Exception $e) {
            $db->rollBack();
            error_log("❌ ERROR en PedidoService: " . $e->getMessage());
            throw $e; // Relanzar para manejo en el controlador
        }
    }

    /**
     * ✅ OBTENER DETALLES DEL PEDIDO (productos y cantidades)
     */
    private static function obtenerDetallesPedido($pedidoId)
    {
        $db = Database::getConexion();
        
        $sql = "
            SELECT 
                producto_id,
                variante_id,
                cantidad
            FROM detalle_pedido 
            WHERE pedido_id = ?
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$pedidoId]);
        
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("   🔍 Detalles obtenidos: " . count($detalles));
        foreach ($detalles as $detalle) {
            error_log("     - Producto: " . $detalle['producto_id'] . 
                     ", Variante: " . ($detalle['variante_id'] ?? 'Ninguna') . 
                     ", Cantidad: " . $detalle['cantidad']);
        }
        
        return $detalles;
    }

    /**
     * ✅ ACTUALIZAR STOCK DE PRODUCTOS Y VARIANTES
     */
    private static function actualizarStockProductos($detallesPedido)
    {
        $db = Database::getConexion();

        foreach ($detallesPedido as $detalle) {
            $productoId = $detalle['producto_id'];
            $varianteId = $detalle['variante_id'];
            $cantidad = (int)$detalle['cantidad'];

            error_log("   🔄 Actualizando stock - Producto: $productoId, Variante: " . ($varianteId ?? 'Ninguna') . ", Cantidad: $cantidad");

            if ($varianteId) {
                // ✅ ACTUALIZAR STOCK DE VARIANTE
                $sql = "UPDATE variantes_producto SET stock = stock - ? WHERE id = ? AND stock >= ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$cantidad, $varianteId, $cantidad]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception("Stock insuficiente para la variante ID: $varianteId");
                }

                error_log("     ✅ Variante $varianteId - Stock reducido en $cantidad");

            } else {
                // ✅ ACTUALIZAR STOCK DE PRODUCTO GENERAL
                $sql = "UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$cantidad, $productoId, $cantidad]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception("Stock insuficiente para el producto ID: $productoId");
                }

                error_log("     ✅ Producto $productoId - Stock reducido en $cantidad");
            }
        }
    }

    /**
     * ✅ LIMPIAR CARRITO DEL USUARIO
     */
    private static function limpiarCarritoUsuario($usuarioId, $sessionId)
    {
        $carritoModel = new CarritoTemporal();
        
        if ($usuarioId) {
            // Usuario autenticado - limpiar por usuario_id
            error_log("   🧹 Limpiando carrito para usuario ID: $usuarioId");
            $carritoModel->limpiarCarrito($sessionId, $usuarioId);
        } else {
            // Usuario invitado - limpiar por session_id
            error_log("   🧹 Limpiando carrito para sesión: $sessionId");
            $carritoModel->limpiarCarrito($sessionId);
        }
    }

    /**
     * ✅ ACTUALIZAR ESTADO DEL PEDIDO
     */
    private static function actualizarEstadoPedido($pedidoId, $estado)
    {
        $db = Database::getConexion();
        
        $sql = "UPDATE pedidos SET estado = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$estado, $pedidoId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("No se pudo actualizar el estado del pedido ID: $pedidoId");
        }
    }

    /**
     * ✅ VERIFICAR SI UN PEDIDO YA FUE PROCESADO
     * (Para evitar procesamiento duplicado)
     */
    public static function pedidoYaProcesado($pedidoId)
    {
        $db = Database::getConexion();
        
        $sql = "SELECT estado FROM pedidos WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$pedidoId]);
        
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Considerar procesado si está en 'completado' o 'pagado'
        return $pedido && in_array($pedido['estado'], ['completado', 'pagado', 'procesando']);
    }
}