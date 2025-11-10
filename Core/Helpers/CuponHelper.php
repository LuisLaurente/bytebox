<?php

namespace Core\Helpers;

use Models\Cupon;

class CuponHelper
{
    /**
     * Validar y aplicar un cupón al carrito
     */
    public static function aplicarCupon($codigo, $cliente_id, $carrito, $productos = [])
    {
        $cuponModel = new Cupon();
        $cupon = $cuponModel->obtenerPorCodigo($codigo);

        if (!$cupon) {
            return [
                'exito' => false,
                'mensaje' => 'Cupón inválido o vencido'
            ];
        }

        // Calcular subtotal del carrito
        $subtotal = 0;
        foreach ($carrito as $item) {
            $subtotal += $item['precio'] * $item['cantidad'];
        }

        // Usar el método de validación del modelo con productos
        $validacion = $cuponModel->puedeUsarCupon($cupon['id'], $cliente_id, $subtotal, $productos);

        if (!$validacion['valido']) {
            return [
                'exito' => false,
                'mensaje' => $validacion['mensaje']
            ];
        }

        // Calcular descuento
        $descuento = 0;
        if ($cupon['tipo'] === 'descuento_porcentaje') {
            $descuento = $subtotal * ($cupon['valor'] / 100);
        } elseif ($cupon['tipo'] === 'descuento_fijo') {
            $descuento = min($cupon['valor'], $subtotal); // El descuento no puede ser mayor al subtotal
        } elseif ($cupon['tipo'] === 'envio_gratis') {
            // El envío gratis no afecta el descuento monetario directamente
            $descuento = 0;
        }

        $nuevo_total = max(0, $subtotal - $descuento);

        return [
            'exito' => true,
            'cupon' => $cupon,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $nuevo_total,
            'mensaje' => 'Cupón aplicado correctamente'
        ];
    }

    /**
     * Registrar el uso de un cupón después de confirmar el pedido
     */
    public static function registrarUso($cupon_id, $cliente_id, $pedido_id)
    {
        $cuponModel = new Cupon();
        return $cuponModel->registrarUso($cupon_id, $cliente_id, $pedido_id);
    }

    /**
     * Obtener cupones disponibles para un cliente
     */
    public static function obtenerCuponesDisponibles($cliente_id)
    {
        $cuponModel = new Cupon();
        $cupones = $cuponModel->obtenerTodos();
        $disponibles = [];

        foreach ($cupones as $cupon) {
            // Solo cupones activos y dentro del período de validez
            if (!$cupon['activo']) continue;

            $hoy = date('Y-m-d');
            if ($hoy < $cupon['fecha_inicio'] || $hoy > $cupon['fecha_fin']) continue;

            // Verificar límites de uso
            if ($cupon['limite_uso'] && $cuponModel->contarUsos($cupon['id']) >= $cupon['limite_uso']) continue;
            if ($cupon['limite_por_usuario'] && $cuponModel->contarUsos($cupon['id'], $cliente_id) >= $cupon['limite_por_usuario']) continue;

            // Verificar si el cliente está autorizado (si aplica)
            if (!empty($cupon['usuarios_autorizados'])) {
                $autorizados = json_decode($cupon['usuarios_autorizados'], true);
                if (is_array($autorizados) && !in_array($cliente_id, $autorizados)) continue;
            }

            $disponibles[] = $cupon;
        }

        return $disponibles;
    }

    /**
     * Formatear información del cupón para mostrar en el frontend
     */
    public static function formatearCupon($cupon)
    {
        $info = [
            'codigo' => $cupon['codigo'],
            'descripcion' => '',
            'valor_formateado' => '',
            'monto_minimo_formateado' => '',
            'vencimiento' => date('d/m/Y', strtotime($cupon['fecha_fin']))
        ];

        switch ($cupon['tipo']) {
            case 'descuento_porcentaje':
                $info['valor_formateado'] = $cupon['valor'] . '% de descuento';
                $info['descripcion'] = "Descuento del {$cupon['valor']}%";
                break;
            case 'descuento_fijo':
                $info['valor_formateado'] = 'S/. ' . number_format($cupon['valor'], 2) . ' de descuento';
                $info['descripcion'] = "Descuento de S/. " . number_format($cupon['valor'], 2);
                break;
            case 'envio_gratis':
                $info['valor_formateado'] = 'Envío gratis';
                $info['descripcion'] = "Envío gratis";
                break;
            default:
                $info['valor_formateado'] = 'Desconocido';
                $info['descripcion'] = "Tipo de descuento desconocido";
        }

        if ($cupon['monto_minimo'] > 0) {
            $info['monto_minimo_formateado'] = 'S/. ' . number_format($cupon['monto_minimo'], 2);
            $info['descripcion'] .= " en compras mayores a S/. " . number_format($cupon['monto_minimo'], 2);
        }

        return $info;
    }

    /**
     * Limpiar cupón aplicado de la sesión
     */
    public static function limpiarCuponSesion()
    {
        unset($_SESSION['cupon_aplicado']);
    }

    /**
     * Obtener cupón aplicado desde la sesión
     */
    public static function obtenerCuponAplicado()
    {
        return $_SESSION['cupon_aplicado'] ?? null;
    }
}
