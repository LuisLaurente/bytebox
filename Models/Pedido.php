<?php

namespace Models;

use Exception;
use Core\Database;
use PDO;

class Pedido
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::getConexion();
    }

    public function crear($usuario_id, $monto_total, $estado = 'pendiente', $pedido_data = null)
    {
        if ($pedido_data) {
            $stmt = $this->db->prepare("
            INSERT INTO pedidos (
                cliente_id, 
                monto_total, 
                estado, 
                cupon_id, 
                cupon_codigo, 
                descuento_cupon, 
                subtotal, 
                descuento_promocion,
                promociones_aplicadas,
                costo_envio,
                metodo_pago,  -- ← NUEVO CAMPO AQUÍ
                facturacion_tipo_documento,
                facturacion_numero_documento,
                facturacion_nombre,
                facturacion_direccion,
                facturacion_email,
                envio_nombre
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)  -- ← Un parámetro más aquí
        ");
            $stmt->execute([
                $usuario_id,
                $monto_total,
                $estado,
                $pedido_data['cupon_id'] ?? null,
                $pedido_data['cupon_codigo'] ?? null,
                $pedido_data['descuento_cupon'] ?? 0.00,
                $pedido_data['subtotal'] ?? 0.00,
                $pedido_data['descuento_promocion'] ?? 0.00,
                $pedido_data['promociones_aplicadas'] ?? null,
                $pedido_data['costo_envio'] ?? 0.00,
                $pedido_data['metodo_pago'] ?? 'contraentrega',  // ← NUEVO PARÁMETRO AQUÍ
                $pedido_data['facturacion_tipo_documento'] ?? null,
                $pedido_data['facturacion_numero_documento'] ?? null,
                $pedido_data['facturacion_nombre'] ?? null,
                $pedido_data['facturacion_direccion'] ?? null,
                $pedido_data['facturacion_email'] ?? null,
                $pedido_data['envio_nombre'] ?? null
            ]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO pedidos (cliente_id, monto_total, estado) VALUES (?, ?, ?)");
            $stmt->execute([$usuario_id, $monto_total, $estado]);
        }
        return $this->db->lastInsertId();
    }

    public function obtenerPorId($id)
    {
        try {
            // Obtener el pedido
            $stmt = $this->db->prepare("SELECT * FROM pedidos WHERE id = ?");
            $stmt->execute([$id]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pedido) {
                return null;
            }

            // DEBUG: Ver qué datos tiene el pedido
            error_log("DEBUG pedido datos: " . print_r($pedido, true));

            // Buscar el usuario en la tabla usuarios (CORREGIDO)
            if (!empty($pedido['usuario_id'])) {
                $stmtUsuario = $this->db->prepare("SELECT * FROM usuarios WHERE id = ?");
                $stmtUsuario->execute([$pedido['usuario_id']]);
                $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

                if ($usuario) {
                    // Usuario encontrado en tabla usuarios
                    $pedido['nombre_cliente'] = $usuario['nombre']; // ← Usar 'nombre' en lugar de 'nombre_completo'
                    $pedido['email_cliente'] = $usuario['email'] ?? null;
                    $pedido['telefono_cliente'] = $usuario['telefono'] ?? null;
                    $pedido['cliente_tipo'] = 'registrado';

                    error_log("DEBUG usuario encontrado: " . print_r($usuario, true));
                } else {
                    // Usuario NO encontrado - usar información de facturación
                    if (!empty($pedido['facturacion_nombre'])) {
                        $pedido['nombre_cliente'] = $pedido['facturacion_nombre'];
                        $pedido['email_cliente'] = $pedido['facturacion_email'] ?? null;
                        $pedido['cliente_tipo'] = 'facturacion';
                    } else {
                        $pedido['nombre_cliente'] = 'Usuario #' . $pedido['usuario_id'];
                        $pedido['cliente_tipo'] = 'no_encontrado';
                    }
                }
            } else {
                // Pedido sin usuario_id
                if (!empty($pedido['facturacion_nombre'])) {
                    $pedido['nombre_cliente'] = $pedido['facturacion_nombre'];
                    $pedido['email_cliente'] = $pedido['facturacion_email'] ?? null;
                    $pedido['cliente_tipo'] = 'facturacion';
                } else {
                    $pedido['nombre_cliente'] = 'Cliente no identificado';
                    $pedido['cliente_tipo'] = 'desconocido';
                }
            }

            return $pedido;
        } catch (Exception $e) {
            error_log("Error en obtenerPorId: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerTodos()
    {
        $sql = "SELECT * FROM pedidos ORDER BY creado_en DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosConDirecciones()
    {
        try {
            $sql = "
                SELECT p.*, 
                       COALESCE(
                           pd.direccion_temporal,
                           CONCAT(
                               d.direccion,
                               CASE 
                                   WHEN d.distrito IS NOT NULL OR d.provincia IS NOT NULL OR d.departamento IS NOT NULL 
                                   THEN CONCAT(', ', 
                                       COALESCE(CONCAT(d.distrito, CASE WHEN d.provincia IS NOT NULL OR d.departamento IS NOT NULL THEN ', ' ELSE '' END), ''),
                                       COALESCE(CONCAT(d.provincia, CASE WHEN d.departamento IS NOT NULL THEN ', ' ELSE '' END), ''),
                                       COALESCE(d.departamento, '')
                                   )
                                   ELSE ''
                               END,
                               CASE WHEN d.referencia IS NOT NULL THEN CONCAT(' - ', d.referencia) ELSE '' END
                           )
                       ) as direccion_envio
                FROM pedidos p
                LEFT JOIN pedido_direcciones pd ON p.id = pd.pedido_id
                LEFT JOIN direcciones d ON pd.direccion_id = d.id
                ORDER BY p.creado_en DESC
            ";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return $this->obtenerTodos();
        }
    }

    public function actualizarEstado($pedidoId, $nuevoEstado)
    {
        try {
            $conexion = \Core\Database::getConexion();
            $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevoEstado, $pedidoId]);
            return true;
        } catch (\Exception $e) {
            error_log("❌ Error actualizando estado del pedido: " . $e->getMessage());
            return false;
        }
    }

    public function eliminar($id)
    {
        $stmt = $this->db->prepare("DELETE FROM pedidos WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function actualizarObservacionesAdmin($id, $observacion)
    {
        $stmt = $this->db->prepare("UPDATE pedidos SET observaciones_admin = ? WHERE id = ?");
        return $stmt->execute([$observacion, $id]);
    }
    public function obtenerPorIdYUsuario($pedido_id, $usuario_id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM pedidos WHERE id = ? AND cliente_id = ?");
            $stmt->execute([$pedido_id, $usuario_id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo pedido: " . $e->getMessage());
            return false;
        }
    }

    // PARA MERCADO PAGO
    /**
     * ✅ NUEVO: Crear pedido desde el carrito de compras
     */
    public function crearDesdeCarrito($carrito, $usuarioId)
    {
        try {
            // Calcular totales usando PromocionHelper
            $carritoParaCalculo = [];
            foreach ($carrito as $item) {
                $producto = \Models\Producto::obtenerPorId($item['producto_id']);
                if ($producto) {
                    $carritoParaCalculo[] = [
                        'id' => $producto['id'],
                        'nombre' => $producto['nombre'],
                        'precio' => (float)$item['precio'],
                        'cantidad' => (int)$item['cantidad'],
                        'categoria_id' => $producto['categoria_id'] ?? null
                    ];
                }
            }

            // Aplicar promociones para calcular totales reales
            $resultadoPromociones = \Core\Helpers\PromocionHelper::aplicarPromociones($carritoParaCalculo, ['id' => $usuarioId]);

            // Preparar datos del pedido
            $pedidoData = [
                'subtotal' => $resultadoPromociones['subtotal'],
                'descuento_promocion' => $resultadoPromociones['descuento'],
                'monto_total' => $resultadoPromociones['total'],
                'promociones_aplicadas' => !empty($resultadoPromociones['promociones_aplicadas'])
                    ? json_encode($resultadoPromociones['promociones_aplicadas'])
                    : null
            ];

            // Crear pedido
            $pedidoId = $this->crear($usuarioId, $pedidoData['monto_total'], 'pendiente', $pedidoData);

            if ($pedidoId) {
                // Crear items del pedido
                $this->crearItemsPedido($pedidoId, $carrito);
                return $pedidoId;
            }

            return false;
        } catch (Exception $e) {
            error_log("Error crearDesdeCarrito: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ NUEVO: Crear items del pedido
     */
    private function crearItemsPedido($pedidoId, $carrito)
    {
        try {
            foreach ($carrito as $item) {
                $producto = \Models\Producto::obtenerPorId($item['producto_id']);
                if ($producto) {
                    $stmt = $this->db->prepare("
                        INSERT INTO pedido_items 
                        (pedido_id, producto_id, cantidad, precio_unitario, variante_id, talla, color) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $pedidoId,
                        $item['producto_id'],
                        $item['cantidad'],
                        $item['precio'],
                        $item['variante_id'] ?? null,
                        $item['talla'] ?? null,
                        $item['color'] ?? null
                    ]);
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Error crearItemsPedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ NUEVO: Actualizar preferencia de Mercado Pago en el pedido
     */
        public function actualizarPreferenciaMp($pedidoId, $preferenceId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE pedidos SET mp_preference_id = ? WHERE id = ?");
            return $stmt->execute([$preferenceId, $pedidoId]);
        } catch (Exception $e) {
            error_log("Error actualizarPreferenciaMp: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ NUEVO: Actualizar estado del pedido según pago de Mercado Pago
     */
    public function actualizarEstadoPorPago($pedidoId, $estadoMp)
    {
        try {
            $estados = [
                'approved' => 'pagado',
                'pending' => 'pendiente',
                'in_process' => 'procesando',
                'rejected' => 'cancelado',
                'cancelled' => 'cancelado',
                'refunded' => 'reembolsado'
            ];

            $estado = $estados[$estadoMp] ?? 'pendiente';

            $stmt = $this->db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
            $result = $stmt->execute([$estado, $pedidoId]);

            if ($result && $estado === 'pagado') {
                // Reducir stock si el pago fue aprobado
                $this->reducirStockPedido($pedidoId);
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error actualizarEstadoPorPago: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ NUEVO: Reducir stock cuando el pedido es pagado
     */
    private function reducirStockPedido($pedidoId)
    {
        try {
            // Obtener items del pedido
            $stmt = $this->db->prepare("
                SELECT producto_id, variante_id, cantidad 
                FROM pedido_items 
                WHERE pedido_id = ?
            ");
            $stmt->execute([$pedidoId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                if ($item['variante_id']) {
                    // Reducir stock de variante
                    $stmt = $this->db->prepare("
                        UPDATE variantes_productos 
                        SET stock = stock - ? 
                        WHERE id = ? AND stock >= ?
                    ");
                    $stmt->execute([$item['cantidad'], $item['variante_id'], $item['cantidad']]);
                } else {
                    // Reducir stock del producto general
                    $stmt = $this->db->prepare("
                        UPDATE productos 
                        SET stock = stock - ? 
                        WHERE id = ? AND stock >= ?
                    ");
                    $stmt->execute([$item['cantidad'], $item['producto_id'], $item['cantidad']]);
                }
            }

            return true;
        } catch (Exception $e) {
            error_log("Error reducirStockPedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ NUEVO: Obtener pedido por preference_id de Mercado Pago
     */
    public function obtenerPorPreferenceId($preferenceId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM pedidos WHERE mp_preference_id = ?");
            $stmt->execute([$preferenceId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obtenerPorPreferenceId: " . $e->getMessage());
            return null;
        }
    }

}
