<?php

namespace Core\Helpers;

use Models\Promocion;

class PromocionHelper
{
    /**
     * Evaluar promociones activas según el carrito y el usuario
     * @param array $carrito - Lista de productos en el carrito
     * @param array|null $usuario - Datos del usuario logueado
     * @return array - Promociones aplicables con detalle de acción
     */
    public static function evaluar($carrito, $usuario = null)
    {
        $promocionModel = new Promocion();
        $promociones = $promocionModel->obtenerPromocionesActivas();
        $aplicables = [];

        error_log("📋 PROMOCIONES ACTIVAS ENCONTRADAS: " . count($promociones));
        foreach ($promociones as $promo) {
            error_log(" - Evaluando: " . $promo['nombre'] . " (ID: " . $promo['id'] . ")");
            $cond = json_decode($promo['condicion'], true);
            $accion = json_decode($promo['accion'], true);

            $cumple = self::cumpleCondiciones($cond, $carrito, $usuario);
            error_log("   Cumple condiciones: " . ($cumple ? "SÍ" : "NO"));

            if ($cumple) {
                $aplicables[] = [
                    'promocion' => $promo,
                    'accion' => $accion
                ];
            }
        }

        return self::filtrarPromociones($aplicables);
    }

    /**
     * Aplica las promociones al carrito y devuelve un resumen de los totales y descuentos.
     * @param array $carrito - Lista de productos en el carrito (por referencia: &$carrito)
     * @param array|null $usuario - Datos del usuario
     * @return array - Array con el carrito modificado y los totales
     */
    public static function aplicarPromociones(&$carrito, $usuario = null)
    {

        
        $subtotal_original = array_sum(array_map(fn($p) => $p['precio'] * $p['cantidad'], $carrito));

        // 1. Resetear cualquier descuento previo
        foreach ($carrito as $index => $item) {
            $carrito[$index]['precio_final'] = $item['precio'] * $item['cantidad'];
            $carrito[$index]['descuento_aplicado'] = 0;
            $carrito[$index]['promociones'] = [];
        }
        // ✅ DEBUG CRÍTICO: Ver carrito DESPUÉS del reset
        error_log("🎯 CARRITO DESPUÉS DEL RESET:");
        foreach ($carrito as $index => $item) {
            error_log("  [$index] ID: {$item['id']} - {$item['nombre']} - S/ {$item['precio']} x {$item['cantidad']}");
        }

        // 2. Obtener promociones aplicables
        // Crear copia del carrito para evaluación (evita bucle infinito)
        $carritoParaEvaluacion = [];
        foreach ($carrito as $item) {
            $carritoParaEvaluacion[] = [
                'id' => $item['id'],
                'nombre' => $item['nombre'],
                'precio' => $item['precio'],
                'cantidad' => $item['cantidad'],
                'categoria_id' => $item['categoria_id']
            ];
        }
        $promocionesAplicables = self::evaluar($carritoParaEvaluacion, $usuario);

        // 3. Inicializar variables
        $descuentoTotal = 0;
        $envioGratis = false;
        $descuentosPorPromocion = [];

        // 4. Aplicar promociones por item primero y acumular descuentos
        foreach ($promocionesAplicables as $promoData) {
            $accion = $promoData['accion'];
            $tipoAccion = $accion['tipo'] ?? '';
            $montoDescuento = 0;

            switch ($tipoAccion) {
                case 'compra_n_paga_m':
                    $montoDescuento = self::aplicarNxM($carrito, $accion, $promoData);
                    break;

                case 'descuento_enesima_unidad':
                    $montoDescuento = self::aplicarDescuentoUnidad($carrito, $accion, $promoData);
                    break;

                case 'descuento_menor_valor':
                    $montoDescuento = self::aplicarDescuentoMenorValor($carrito, $accion, $promoData);
                    break;

                case 'compra_n_paga_m_general':
                    $montoDescuento = self::aplicarNxMGeneral($carrito, $accion, $promoData);
                    break;

                case 'descuento_producto_mas_barato':
                    $montoDescuento = self::aplicarDescuentoEnesimoProducto($carrito, $accion, $promoData);
                    break;

                case 'envio_gratis':
                    // ✅ VERIFICAR que realmente cumple las condiciones
                    $condicion = $promoData['promocion']['condicion'] ?? [];
                    if (is_string($condicion)) {
                        $condicion = json_decode($condicion, true);
                    }

                    $cumpleCondicion = self::cumpleCondiciones($condicion, $carritoParaEvaluacion, $usuario);

                    if ($cumpleCondicion) {
                        $envioGratis = true;
                        $descuentosPorPromocion[] = [
                            'nombre' => $promoData['promocion']['nombre'],
                            'monto' => 0, // ✅ Cambiar de 'Gratis' a 0 para consistencia
                            'envio_gratis' => true // ✅ Flag crítico para la vista
                        ];
                        error_log("✅ ENVÍO GRATIS APLICADO: " . $promoData['promocion']['nombre']);
                    }
                    break;
            }

            if ($montoDescuento > 0) {
                $descuentosPorPromocion[] = [
                    'nombre' => $promoData['promocion']['nombre'],
                    'monto' => $montoDescuento
                ];
                $descuentoTotal += $montoDescuento;
            }
        }

        // ✅ CORRECCIÓN: Usar subtotal_original en lugar de recalcular
        $subtotal = $subtotal_original;

        // 6. Aplicar descuentos generales (porcentaje o fijo)
        foreach ($promocionesAplicables as $promoData) {
            $accion = $promoData['accion'];
            $tipoAccion = $accion['tipo'] ?? '';
            $montoDescuentoGeneral = 0;

            switch ($tipoAccion) {
                case 'descuento_porcentaje':
                    $montoDescuentoGeneral = $subtotal * ($accion['valor'] / 100);
                    self::distribuirDescuentoGeneral($carrito, $montoDescuentoGeneral, $subtotal);
                    $descuentosPorPromocion[] = [
                        'nombre' => $promoData['promocion']['nombre'],
                        'monto' => $montoDescuentoGeneral
                    ];
                    $descuentoTotal += $montoDescuentoGeneral;
                    break;

                case 'descuento_fijo':
                    $montoDescuentoGeneral = min($accion['valor'], $subtotal);
                    self::distribuirDescuentoGeneral($carrito, $montoDescuentoGeneral, $subtotal);
                    $descuentosPorPromocion[] = [
                        'nombre' => $promoData['promocion']['nombre'],
                        'monto' => $montoDescuentoGeneral
                    ];
                    $descuentoTotal += $montoDescuentoGeneral;
                    break;
            }
        }

        // 7. Calcular total final
        $total = max($subtotal - $descuentoTotal, 0);

        // ✅ DEBUG: Ver resultado
        $debug_resultado = [
            'mensaje' => '=== PROMOCIONHELPER RESULTADO ===',
            'subtotal_original' => $subtotal_original,
            'subtotal_usado' => $subtotal,
            'descuento_total' => $descuentoTotal,
            'total_final' => $total,
            'envio_gratis' => $envioGratis
        ];

        error_log("PROMOCIONHELPER DEBUG RESULTADO: " . json_encode($debug_resultado));
        return [
            'carrito' => $carrito,
            'subtotal' => $subtotal,
            'descuento' => $descuentoTotal,
            'total' => $total,
            'envio_gratis' => $envioGratis,
            'promociones_aplicadas' => $descuentosPorPromocion
        ];
    }

    // --- MÉTODOS PRIVADOS CORREGIDOS PARA DEVOLVER EL MONTO DE DESCUENTO ---

    /**
     * Aplicar promoción NxM a un producto específico
     * @return float
     */
    private static function aplicarNxM(&$carrito, $accion, $promoData)
    {
        // ✅ OBTENER product_id DE LA CONDICIÓN en lugar de la acción
        $condicion = $promoData['promocion']['condicion'] ?? [];
        if (is_string($condicion)) {
            $condicion = json_decode($condicion, true);
        }

        $productoId = $condicion['producto_id'] ?? 0; // ✅ Cambiar a $condicion
        $lleva = $accion['cantidad_lleva'] ?? 0;
        $paga = $accion['cantidad_paga'] ?? 0;
        $descuento = 0;

        if ($lleva <= 0 || $paga <= 0 || $productoId <= 0) return 0;

        foreach ($carrito as &$item) {
            if ($item['id'] == $productoId && $item['cantidad'] >= $lleva) {
                $grupos = floor($item['cantidad'] / $lleva);
                $unidadesAPagar = ($grupos * $paga) + ($item['cantidad'] % $lleva);

                $precioOriginal = $item['precio'] * $item['cantidad'];
                $nuevoPrecio = $unidadesAPagar * $item['precio'];
                $descuento = $precioOriginal - $nuevoPrecio;

                $item['precio_final'] -= $descuento;
                $item['descuento_aplicado'] += $descuento;
                $item['promociones'][] = $promoData['promocion']['nombre'];
                break;
            }
        }
        return $descuento;
    }

    /**
     * Aplicar descuento a una unidad específica (mejorado)
     * @return float
     */
    private static function aplicarDescuentoUnidad(&$carrito, $accion, $promoData)
    {
        // ✅ CORREGIDO: Obtener producto_id desde la condición, no desde la acción
        $condicion = $promoData['promocion']['condicion'] ?? [];
        if (is_string($condicion)) {
            $condicion = json_decode($condicion, true);
        }

        $productoId = $condicion['producto_id'] ?? 0; // ← Ahora desde la condición
        $numeroUnidad = $accion['numero_unidad'] ?? 1;
        $descuentoPorcentaje = $accion['descuento_unidad'] ?? 0;
        $descuento = 0;

        foreach ($carrito as &$item) {
            if ($item['id'] == $productoId && $item['cantidad'] >= $numeroUnidad) {
                // Calcular cuántas unidades elegibles hay (cada N-ésima unidad)
                $unidadesElegibles = floor($item['cantidad'] / $numeroUnidad);
                $descuentoPorUnidad = $item['precio'] * ($descuentoPorcentaje / 100);
                $descuento = $descuentoPorUnidad * $unidadesElegibles;

                $item['precio_final'] -= $descuento;
                $item['descuento_aplicado'] += $descuento;
                $item['promociones'][] = $promoData['promocion']['nombre'];
                break;
            }
        }
        return $descuento;
    }
    /**
     * Contar pedidos reales de un usuario
     */
    private static function contarPedidosUsuario($usuarioId)
    {
        try {
            $pedidoModel = new \Models\Pedido();

            // Usar reflexión o método público para contar
            $db = \Core\Database::getConexion();
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM pedidos WHERE cliente_id = ?");
            $stmt->execute([$usuarioId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result['total'] ?? 0;
        } catch (\Exception $e) {
            error_log("Error contando pedidos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Aplicar descuento al producto más barato de una categoría específica (Promoción 6)
     * @return float
     */
    private static function aplicarDescuentoMenorValor(&$carrito, $accion, $promoData)
    {
        // DEBUG EXTENSIVO
        error_log("=== DEBUG PROMOCIÓN 6 - aplicarDescuentoMenorValor ===");

        // ✅ CORREGIDO: Obtener y decodificar la condición correctamente
        $condicion = $promoData['promocion']['condicion'] ?? [];
        if (is_string($condicion)) {
            $condicion = json_decode($condicion, true);
        }

        $categoriaId = $condicion['categoria_id'] ?? 0;
        $cantidadMinima = $condicion['cantidad_min'] ?? 0;
        $descuentoPorcentaje = $accion['valor'] ?? 0;

        error_log("Categoria ID desde condición: " . $categoriaId);
        error_log("Cantidad mínima requerida: " . $cantidadMinima);
        error_log("Descuento porcentaje: " . $descuentoPorcentaje);
        error_log("Condición completa: " . print_r($condicion, true)); // ← Cambié a print_r

        $descuento = 0;

        // 1. Primero verificar si se cumple la condición de cantidad mínima
        $totalEnCategoria = 0;
        foreach ($carrito as $item) {
            if (($item['categoria_id'] ?? 0) == $categoriaId) {
                $totalEnCategoria += ($item['cantidad'] ?? 0);
            }
        }

        error_log("Total productos en categoría $categoriaId: $totalEnCategoria (mínimo requerido: $cantidadMinima)");

        if ($totalEnCategoria < $cantidadMinima) {
            error_log("❌ No se cumple condición - Cantidad: $totalEnCategoria < Mínimo: $cantidadMinima");
            return 0;
        }

        error_log("✅ Condición CUMPLIDA - Cantidad: $totalEnCategoria >= Mínimo: $cantidadMinima");

        if ($categoriaId <= 0 || $descuentoPorcentaje <= 0) {
            error_log("ERROR: Categoria ID o descuento inválido");
            return 0;
        }

        // DEBUG: Ver productos en carrito
        error_log("Productos en carrito:");
        foreach ($carrito as $item) {
            $enCategoria = (($item['categoria_id'] ?? 0) == $categoriaId) ? "✅ EN CATEGORÍA" : "❌ NO";
            error_log(" - ID: " . $item['id'] . ", Categoria: " . ($item['categoria_id'] ?? 'NULL') . ", Precio: " . $item['precio'] . " - $enCategoria");
        }

        // Encontrar el producto más barato de la categoría específica
        $productoMasBarato = null;
        $precioMasBajo = PHP_FLOAT_MAX;

        foreach ($carrito as &$item) {
            if (($item['categoria_id'] ?? 0) == $categoriaId) {
                error_log("Producto encontrado en categoría " . $categoriaId . ": " . $item['nombre'] . " - Precio: " . $item['precio']);
                $precioUnitario = $item['precio'];
                if ($precioUnitario < $precioMasBajo) {
                    $precioMasBajo = $precioUnitario;
                    $productoMasBarato = &$item;
                }
            }
        }

        if ($productoMasBarato) {
            // Calcular descuento en TODAS las unidades del producto más barato
            $descuento = ($productoMasBarato['precio'] * $productoMasBarato['cantidad']) * ($descuentoPorcentaje / 100);

            error_log("✅ DESCUENTO APLICADO AL PRODUCTO MÁS BARATO");
            error_log("   Producto: " . $productoMasBarato['nombre']);
            error_log("   Precio unitario: S/ " . $productoMasBarato['precio']);
            error_log("   Cantidad: " . $productoMasBarato['cantidad']);
            error_log("   Subtotal original: S/ " . ($productoMasBarato['precio'] * $productoMasBarato['cantidad']));
            error_log("   Descuento aplicado: S/ $descuento ($descuentoPorcentaje%)");
            error_log("   Nuevo subtotal: S/ " . (($productoMasBarato['precio'] * $productoMasBarato['cantidad']) - $descuento));

            // Aplicar el descuento
            $productoMasBarato['precio_final'] -= $descuento;
            $productoMasBarato['descuento_aplicado'] += $descuento;
            $productoMasBarato['promociones'][] = $promoData['promocion']['nombre'];
        } else {
            error_log("❌ NO se encontró producto en la categoría " . $categoriaId);
        }

        return $descuento;
    }

    /**
     * Aplicar NxM general a cualquier combinación de productos
     * @return float
     */
    /**
     * Aplicar NxM general a cualquier combinación de productos - CORREGIDO
     * @return float
     */
    private static function aplicarNxMGeneral(&$carrito, $accion, $promoData)
    {
        $lleva = $accion['cantidad_lleva'] ?? 0;
        $paga = $accion['cantidad_paga'] ?? 0;
        $descuento = 0;

        if ($lleva <= 0 || $paga <= 0 || $lleva <= $paga) return 0;

        $cantidadTotal = array_sum(array_column($carrito, 'cantidad'));

        if ($cantidadTotal >= $lleva) {
            // ✅ DEBUG CRÍTICO: Ver carrito ANTES de expandir
            error_log("🎯 CARRITO EN aplicarNxMGeneral ANTES DE EXPANDIR:");
            foreach ($carrito as $index => $item) {
                error_log("  [$index] ID: {$item['id']} - {$item['nombre']} - S/ {$item['precio']} x {$item['cantidad']}");
            }

            $unidades = [];
            $unidadIndex = 0;

            foreach ($carrito as $carritoIndex => &$item) {

                for ($i = 0; $i < $item['cantidad']; $i++) {
                    $unidades[] = [
                        'carrito_index' => $carritoIndex,
                        'precio_unitario' => $item['precio'],
                        'unidad_id' => $unidadIndex++,
                        'producto_nombre' => $item['nombre']
                    ];
                }
            }

            // ✅ DEBUG: Ver unidades antes de ordenar
            error_log("=== UNIDADES ANTES DE ORDENAR ===");
            foreach ($unidades as $u) {
                error_log("Unidad {$u['unidad_id']}: {$u['producto_nombre']} - S/ {$u['precio_unitario']}");
            }

            // Ordenar por precio unitario (más baratos primero)
            usort($unidades, fn($a, $b) => $a['precio_unitario'] <=> $b['precio_unitario']);

            // ✅ DEBUG: Ver unidades después de ordenar
            error_log("=== UNIDADES DESPUÉS DE ORDENAR ===");
            foreach ($unidades as $u) {
                error_log("Unidad {$u['unidad_id']}: {$u['producto_nombre']} - S/ {$u['precio_unitario']}");
            }

            $gruposCompletos = floor($cantidadTotal / $lleva);
            $unidadesGratisPorGrupo = $lleva - $paga;
            $totalUnidadesGratis = $gruposCompletos * $unidadesGratisPorGrupo;

            error_log("🎯 CÁLCULO: Unidades totales: $cantidadTotal, Grupos: $gruposCompletos, Unidades gratis: $totalUnidadesGratis");

            // Aplicar descuento
            $unidadesDescontadas = [];

            for ($i = 0; $i < $totalUnidadesGratis && $i < count($unidades); $i++) {
                $unidad = $unidades[$i];
                $carritoIndex = $unidad['carrito_index'];
                $precioUnitario = $unidad['precio_unitario'];

                $descuento += $precioUnitario;
                $unidadesDescontadas[] = $unidad['producto_nombre'] . " (S/ $precioUnitario)";

                $carrito[$carritoIndex]['precio_final'] -= $precioUnitario;
                $carrito[$carritoIndex]['descuento_aplicado'] += $precioUnitario;
                $carrito[$carritoIndex]['promociones'][] = $promoData['promocion']['nombre'];
            }

            if (!empty($unidadesDescontadas)) {
                error_log("🎯 UNIDADES DESCONTADAS: " . implode(", ", $unidadesDescontadas));
                error_log("🎯 DESCUENTO TOTAL: S/ $descuento");
            }
        }

        return $descuento;
    }


    /**
     * Aplicar descuento al producto más barato al llevar N productos
     * @return float
     */
    private static function aplicarDescuentoMasBarato(&$carrito, $accion, $promoData)
    {
        $descuentoPorcentaje = $accion['valor'] ?? 0;
        $descuento = 0;

        // Encontrar el producto más barato
        $productoMasBarato = null;
        $precioMasBajo = PHP_FLOAT_MAX;

        foreach ($carrito as &$item) {
            $precioUnitario = $item['precio'];
            if ($precioUnitario < $precioMasBajo) {
                $precioMasBajo = $precioUnitario;
                $productoMasBarato = &$item;
            }
        }

        if ($productoMasBarato) {
            $descuento = ($productoMasBarato['precio'] * $productoMasBarato['cantidad']) * ($descuentoPorcentaje / 100);
            $productoMasBarato['precio_final'] -= $descuento;
            $productoMasBarato['descuento_aplicado'] += $descuento;
            $productoMasBarato['promociones'][] = $promoData['promocion']['nombre'];
        }
        return $descuento;
    }

    /**
     * Aplicar descuento al producto más barato cuando se lleva N productos
     * @return float
     */
    private static function aplicarDescuentoEnesimoProducto(&$carrito, $accion, $promoData)
    {
        $descuentoPorcentaje = $accion['valor'] ?? 0;
        $descuento = 0;

        if ($descuentoPorcentaje <= 0) return 0;

        // Encontrar el producto más barato de todo el carrito
        $productoMasBarato = null;
        $precioMasBajo = PHP_FLOAT_MAX;

        foreach ($carrito as &$item) {
            $precioUnitario = $item['precio'];
            if ($precioUnitario < $precioMasBajo) {
                $precioMasBajo = $precioUnitario;
                $productoMasBarato = &$item;
            }
        }

        if ($productoMasBarato) {
            $descuento = ($productoMasBarato['precio'] * $productoMasBarato['cantidad']) * ($descuentoPorcentaje / 100);
            $productoMasBarato['precio_final'] -= $descuento;
            $productoMasBarato['descuento_aplicado'] += $descuento;
            $productoMasBarato['promociones'][] = $promoData['promocion']['nombre'];
        }

        return $descuento;
    }
    /**
     * Distribuir descuento general proporcionalmente entre items
     * @return void
     */
    private static function distribuirDescuentoGeneral(&$carrito, $descuentoTotal, $subtotalOriginal)
    {
        if ($subtotalOriginal <= 0) return;

        $descuentoRestante = $descuentoTotal;

        foreach ($carrito as &$item) {
            $proporcion = ($item['precio'] * $item['cantidad']) / $subtotalOriginal;
            $descuentoItem = $descuentoTotal * $proporcion;

            $descuentoItem = min($descuentoItem, $item['precio_final']);

            $item['precio_final'] -= $descuentoItem;
            $item['descuento_aplicado'] += $descuentoItem;
            $descuentoRestante -= $descuentoItem;
        }

        if ($descuentoRestante > 0) {
            foreach ($carrito as &$item) {
                if ($item['precio_final'] > 0) {
                    $descuentoExtra = min($descuentoRestante, $item['precio_final']);
                    $item['precio_final'] -= $descuentoExtra;
                    $item['descuento_aplicado'] += $descuentoExtra;
                    $descuentoRestante -= $descuentoExtra;

                    if ($descuentoRestante <= 0) break;
                }
            }
        }
    }

    // --- MÉTODOS DE SOPORTE SIN CAMBIOS ---

    public static function describirPromocion($condicion, $accion, $tipo)
    {
        $cond = json_decode($condicion, true);
        $acc = json_decode($accion, true);
        if (!$cond || !$acc || empty($cond['tipo']) || empty($acc['tipo'])) {
            return "Regla no válida";
        }
        switch ($cond['tipo']) {
            case 'subtotal_minimo':
                if ($acc['tipo'] === 'descuento_porcentaje') {
                    return "Descuento del {$acc['valor']}% por compras sobre S/{$cond['valor']}";
                } elseif ($acc['tipo'] === 'envio_gratis') {
                    return "Envío gratis por compras sobre S/{$cond['valor']}";
                }
                break;
            case 'primera_compra':
                if ($acc['tipo'] === 'envio_gratis') {
                    return "Envío gratis en la primera compra";
                }
                break;
            case 'cantidad_producto_identico':
                if ($acc['tipo'] === 'compra_n_paga_m') {
                    return "Lleva {$acc['cantidad_lleva']}, paga {$acc['cantidad_paga']} en producto ID {$cond['producto_id']}";
                } elseif ($acc['tipo'] === 'descuento_enesima_unidad') {
                    return "Descuento del {$acc['descuento_unidad']}% en la {$acc['numero_unidad']}ª unidad del producto ID {$cond['producto_id']}";
                }
                break;
            case 'cantidad_producto_categoria':
                if ($acc['tipo'] === 'descuento_menor_valor') {
                    return "Descuento del {$acc['valor']}% en el producto de menor valor de la categoría {$cond['categoria_id']} al comprar {$cond['cantidad_min']} productos";
                }
                break;
            case 'cantidad_total_productos':
                if ($acc['tipo'] === 'compra_n_paga_m_general') {
                    return "Lleva {$acc['cantidad_lleva']}, paga {$acc['cantidad_paga']} en cualquier combinación de productos";
                } elseif ($acc['tipo'] === 'descuento_producto_mas_barato') {
                    return "Descuento del {$acc['valor']}% en el producto de menor valor al comprar {$cond['cantidad_min']} productos";
                }
                break;
            case 'todos':
                if ($acc['tipo'] === 'envio_gratis') {
                    return "Envío gratis para todos los pedidos";
                }
                break;
            default:
                return "Regla personalizada";
        }
        return "Regla no válida";
    }

    private static function cumpleCondiciones($cond, $carrito, $usuario)
    {
        // ✅ CORRECCIÓN: Validar y decodificar JSON si es string
        if (is_string($cond)) {
            $cond = json_decode($cond, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON inválido en condición: " . $cond);
                return false;
            }
        }

        // ✅ CORRECCIÓN: Verificar que $cond es array después de decodificar
        if (!is_array($cond)) {
            error_log("Condición no es array válido: " . print_r($cond, true));
            return false;
        }

        $cantidadTotal = array_sum(array_column($carrito, 'cantidad'));
        $subtotal = array_sum(array_map(fn($p) => $p['precio'] * $p['cantidad'], $carrito));

        $tipoCondicion = $cond['tipo'] ?? '';

        switch ($tipoCondicion) {
            case 'todos':
                return true;

            case 'subtotal_minimo':
                $valorMinimo = $cond['valor'] ?? 0;
                return $subtotal >= $valorMinimo;

            case 'primera_compra':
                if (!$usuario || !isset($usuario['id'])) {
                    return false;
                }

                // ✅ CONTAR PEDIDOS REALES en la base de datos
                $pedidoModel = new \Models\Pedido();
                $totalPedidos = self::contarPedidosUsuario($usuario['id']);

                error_log("Usuario ID {$usuario['id']} - Pedidos totales: {$totalPedidos}");

                return $totalPedidos == 0; // True solo si es primera compra
            case 'cantidad_producto_identico':
                $productoId = $cond['producto_id'] ?? 0;
                // ✅ CORRECCIÓN: Buscar AMBOS campos por compatibilidad
                $cantidadRequerida = $cond['cantidad_min'] ?? $cond['cantidad'] ?? 0;

                foreach ($carrito as $producto) {
                    if ($producto['id'] == $productoId && $producto['cantidad'] >= $cantidadRequerida) {
                        return true;
                    }
                }
                return false;

            case 'cantidad_producto_categoria':
                $categoriaId = $cond['categoria_id'] ?? 0;
                // ✅ CORRECCIÓN: Buscar AMBOS campos por compatibilidad  
                $cantidadRequerida = $cond['cantidad_min'] ?? $cond['cantidad'] ?? 0;

                $cantidadEnCategoria = 0;
                foreach ($carrito as $item) {
                    if (($item['categoria_id'] ?? 0) == $categoriaId) {
                        $cantidadEnCategoria += ($item['cantidad'] ?? 0);
                    }
                }
                return $cantidadEnCategoria >= $cantidadRequerida;

            case 'cantidad_total_productos':
                // ✅ CORRECCIÓN: Buscar AMBOS campos por compatibilidad
                $cantidadRequerida = $cond['cantidad_min'] ?? $cond['cantidad'] ?? 0;
                return $cantidadTotal >= $cantidadRequerida;
        }
    }

    private static function filtrarPromociones($aplicables)
    {
        usort($aplicables, fn($a, $b) => $a['promocion']['prioridad'] <=> $b['promocion']['prioridad']);
        $resultado = [];
        foreach ($aplicables as $promo) {
            if ($promo['promocion']['exclusivo'] && !empty($resultado)) {
                break;
            }
            $resultado[] = $promo;
            if (!$promo['promocion']['acumulable']) {
                break;
            }
        }
        return $resultado;
    }
}
