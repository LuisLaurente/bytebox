<?php

namespace Controllers;

use Models\Producto;
use Models\ImagenProducto;
// Asegúrate de que los namespaces de tus helpers son correctos.
// Si están en la raíz del namespace Core\Helpers, esta es la forma correcta.
use Core\Helpers\PromocionHelper;
use Core\Helpers\CuponHelper;
use Core\Helpers\CartPersistenceHelper;
use Models\CarritoTemporal;

class CarritoController
{
    // --- MÉTODOS PRIVADOS DE AYUDA PARA AJAX Y LÓGICA CENTRALIZADA ---

    /**
     * Verifica si la petición actual es una petición AJAX.
     * Esencial para diferenciar entre una recarga de página y una llamada de JavaScript.
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Prepara y envía una respuesta JSON estandarizada y finaliza la ejecución del script.
     * Garantiza que todas las respuestas AJAX tengan un formato consistente.
     */
    private function jsonResponse(bool $success, string $message, array $data = []): void
    {
        // ❌ ELIMINA completamente la limpieza de buffer
        // if (ob_get_level()) {
        //     ob_end_clean();
        // }

        // ✅ Asegurar que los headers se envíen correctamente
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $response = [
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ];

        $json = json_encode($response, JSON_UNESCAPED_UNICODE);

        // DEBUG temporal para ver qué se está enviando
        error_log("📤 ENVIANDO JSON: " . $json);

        if ($json === false) {
            // Fallback en caso de error
            echo '{"success":false,"message":"Error encoding JSON"}';
        } else {
            echo $json;
        }

        exit;
    }

    /**
     * Obtiene el estado completo y actualizado del carrito.
     * Centraliza toda la lógica de cálculo (promociones, cupones, totales)
     * para ser usada tanto por las vistas normales como por las respuestas AJAX.
     */
    private function getCartState(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $carrito = $_SESSION['carrito'] ?? [];
        $usuario = $_SESSION['usuario'] ?? null;

        // Si el carrito está vacío, retornar estado vacío
        if (empty($carrito)) {
            return [
                'items' => [],
                'itemDetails' => [],
                'totals' => [
                    'subtotal' => 0,
                    'descuento' => 0,
                    'descuento_cupon' => 0,
                    'total' => 0,
                    'envio_gratis' => false
                ],
                'promotions' => [],
                'coupon' => null,
                'itemCount' => 0
            ];
        }

        // 1. Convertir carrito de sesión al formato que espera PromocionHelper
        $carritoParaPromociones = [];
        $productosDetallados = [];

        foreach ($carrito as $clave => $item) {
            $productoIdCarrito = $item['producto_id'];
            $producto = Producto::obtenerPorId($item['producto_id']);


            if ($producto) {
                // Para PromocionHelper - CREAR COPIA INDEPENDIENTE
                $nuevoItem = [
                    'id' => (int)$producto['id'],
                    'nombre' => (string)$producto['nombre'],
                    'precio' => (float)$item['precio'],
                    'cantidad' => (int)$item['cantidad'],
                    'categoria_id' => isset($producto['categoria_id']) ? (int)$producto['categoria_id'] : null,
                    'precio_final' => 0,
                    'descuento_aplicado' => 0,
                    'promociones' => []
                ];

                $carritoParaPromociones[] = $nuevoItem;

                // Para la respuesta detallada
                $primera = ImagenProducto::obtenerPrimeraPorProducto((int)$item['producto_id']);
                $imagenUrl = ($primera && !empty($primera['nombre_imagen']))
                    ? url('uploads/' . $primera['nombre_imagen'])
                    : null;

                $productosDetallados[$clave] = [
                    'clave' => $clave,
                    'producto_id' => $item['producto_id'],
                    'variante_id' => $item['variante_id'] ?? null,
                    'nombre' => $producto['nombre'],
                    'cantidad' => $item['cantidad'],
                    'precio' => (float)$item['precio'],
                    'subtotal' => (float)$item['precio'] * $item['cantidad'],
                    'imagen' => $imagenUrl,
                    'talla' => $item['talla'] ?? null,
                    'color' => $item['color'] ?? null,
                    'precio_final' => (float)$item['precio'],
                    'descuento_aplicado' => 0,
                    'promociones_aplicadas' => []
                ];
            }
        }

        // 2. VERIFICAR EXCLUSIVIDADES ANTES DE APLICAR PROMOCIONES
        $cupon_aplicado = CuponHelper::obtenerCuponAplicado();
        $aplicarPromociones = true;
        $motivoExclusion = '';

        // Verificar si hay un cupón exclusivo aplicado
        if ($cupon_aplicado && isset($cupon_aplicado['acumulable_promociones']) && !$cupon_aplicado['acumulable_promociones']) {
            $aplicarPromociones = false;
            $motivoExclusion = 'cupon_exclusivo';
            error_log("🎯 CUPÓN EXCLUSIVO DETECTADO - No se aplicarán promociones");
        }

        // 3. Aplicar promociones SOLO si no hay conflicto de exclusividad
        $resultadoPromociones = [
            'carrito' => $carritoParaPromociones,
            'subtotal' => array_sum(array_map(fn($p) => $p['precio'] * $p['cantidad'], $carritoParaPromociones)),
            'descuento' => 0,
            'total' => array_sum(array_map(fn($p) => $p['precio'] * $p['cantidad'], $carritoParaPromociones)),
            'envio_gratis' => false,
            'promociones_aplicadas' => []
        ];

        if ($aplicarPromociones) {
            $resultadoPromociones = PromocionHelper::aplicarPromociones($carritoParaPromociones, $usuario);
            error_log("🎯 PROMOCIONES APLICADAS - Total descuento: " . $resultadoPromociones['descuento']);
        } else {
            error_log("🎯 PROMOCIONES BLOQUEADAS - Motivo: " . $motivoExclusion);
        }

        // 4. Actualizar productos detallados con información de promociones (si se aplicaron)
        foreach ($productosDetallados as $clave => &$productoDetallado) {
            $productoId = $productoDetallado['producto_id'];
            $encontrado = false;

            // Buscar el mismo producto en carritoParaPromociones
            foreach ($carritoParaPromociones as $itemConPromocion) {
                if ($itemConPromocion['id'] == $productoId) {
                    $productoDetallado['precio_final'] = $itemConPromocion['precio_final'];
                    $productoDetallado['descuento_aplicado'] = $itemConPromocion['descuento_aplicado'];
                    $productoDetallado['promociones_aplicadas'] = $itemConPromocion['promociones'] ?? [];
                    $encontrado = true;
                    break;
                }
            }
        }

        // 5. Preparar totales desde el resultado de promociones
        $totales = [
            'subtotal' => $resultadoPromociones['subtotal'],
            'descuento' => $resultadoPromociones['descuento'],
            'total' => $resultadoPromociones['total'],
            'envio_gratis' => $resultadoPromociones['envio_gratis'],
            'descuento_cupon' => 0
        ];

        // 6. Aplicar cupón si existe y es válido
        $descuento_cupon = 0;

        if ($cupon_aplicado && $usuario) {
            $productosParaValidacion = $this->getProductosDetalladosParaValidacion($carrito);
            $aplicacionCupon = CuponHelper::aplicarCupon(
                $cupon_aplicado['codigo'],
                $usuario['id'],
                $carrito,
                $productosParaValidacion
            );

            if ($aplicacionCupon['exito']) {
                $descuento_cupon = $aplicacionCupon['descuento'];
                $totales['descuento_cupon'] = $descuento_cupon;
                $totales['total'] = max(0, $totales['total'] - $descuento_cupon);

                // Si el cupón es exclusivo, limpiar cualquier promoción que se haya aplicado por error
                if (!$cupon_aplicado['acumulable_promociones'] && $resultadoPromociones['descuento'] > 0) {
                    error_log("🎯 LIMPIANDO PROMOCIONES POR CUPÓN EXCLUSIVO");
                    $totales['descuento'] = 0;
                    $totales['total'] = max(0, $totales['subtotal'] - $descuento_cupon);
                    $resultadoPromociones['promociones_aplicadas'] = [];
                }
            } else {
                CuponHelper::limpiarCuponSesion();
                $cupon_aplicado = null;
            }
        } elseif ($cupon_aplicado && !$usuario) {
            CuponHelper::limpiarCuponSesion();
            $cupon_aplicado = null;
        }

        // 7. Guardar promociones en sesión para acceso posterior
        $_SESSION['promociones'] = $resultadoPromociones['promociones_aplicadas'];

        // 8. Devolver el estado completo
        return [
            'items' => array_values($productosDetallados),
            'itemDetails' => $productosDetallados,
            'totals' => $totales,
            'promotions' => $resultadoPromociones['promociones_aplicadas'],
            'coupon' => $cupon_aplicado,
            'itemCount' => array_sum(array_column($carrito, 'cantidad'))
        ];
    }

    /**
     * Función auxiliar para obtener los modelos de producto para la validación de cupones.
     */
    private function getProductosDetalladosParaValidacion(array $carrito): array
    {
        $productos = [];
        if (empty($carrito)) return $productos;

        foreach ($carrito as $item) {
            $producto = Producto::obtenerPorId($item['producto_id']);
            if ($producto) {
                $productos[] = $producto;
            }
        }
        return $productos;
    }

    // --- MÉTODOS PÚBLICOS DEL CONTROLADOR (ACCIONES) ---

    /**
     * Aumenta la cantidad de un producto.
     * Responde con JSON si es AJAX, de lo contrario redirige.
     */
    public function aumentar($clave)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (isset($_SESSION['carrito'][$clave])) {
                $item = $_SESSION['carrito'][$clave];
                $producto = Producto::obtenerPorId($item['producto_id']);

                // Verificación de stock
                $stock = null;
                if ($item['variante_id'] !== null) {
                    require_once __DIR__ . '/../models/VarianteProducto.php';
                    $variante = \Models\VarianteProducto::obtenerPorId($item['variante_id']);
                    $stock = $variante ? (int)$variante['stock'] : null;
                } else {
                    $stock = isset($producto['stock']) ? (int)$producto['stock'] : null;
                }

                if ($stock !== null && $item['cantidad'] >= $stock) {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(false, 'No hay más stock disponible para este producto.', $this->getCartState());
                        return;
                    }
                    $_SESSION['flash_error'] = 'No hay más stock disponible para este producto.';
                    header('Location: ' . url('carrito/ver'));
                    exit;
                }

                // ✅ 1. Actualizar en sesión
                $_SESSION['carrito'][$clave]['cantidad']++;

                // ✅ 2. NUEVO: Actualizar en BD persistente
                if (\Core\Helpers\CookieHelper::hasConsent()) {
                    $session_id = \Core\Helpers\CartPersistenceHelper::getCartSessionId();
                    $usuario_id = \Core\Helpers\SessionHelper::getUserId();

                    $carritoModel = new \Models\CarritoTemporal();

                    // Buscar el registro en BD y actualizar cantidad
                    $carritoBD = $carritoModel->obtenerCarrito($session_id, $usuario_id);

                    foreach ($carritoBD as $itemBD) {
                        if (
                            $itemBD['producto_id'] == $item['producto_id'] &&
                            $itemBD['variante_id'] == $item['variante_id']
                        ) {

                            // Actualizar cantidad en BD
                            $carritoModel->agregarProducto(
                                $session_id,
                                $item['producto_id'],
                                1, // Aumentar en 1
                                $item['variante_id'],
                                $usuario_id
                            );
                            error_log("✅ Cantidad aumentada en carrito persistente: Producto {$item['producto_id']}, Nueva cantidad: {$_SESSION['carrito'][$clave]['cantidad']}");
                            break;
                        }
                    }
                }
            }

            if ($this->isAjaxRequest()) {
                $this->jsonResponse(true, 'Cantidad aumentada.', $this->getCartState());
                return;
            }

            header('Location: ' . url('carrito/ver'));
            exit;
        } catch (\Exception $e) {
            error_log("❌ Error aumentando cantidad en carrito: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                $this->jsonResponse(false, 'Error al actualizar la cantidad.');
            } else {
                $_SESSION['flash_error'] = 'Error al actualizar la cantidad';
                header('Location: ' . url('carrito/ver'));
            }
            exit;
        }
    }

    /**
     * Disminuye la cantidad de un producto. Si llega a 0, lo elimina.
     * Responde con JSON si es AJAX, de lo contrario redirige.
     */
    public function disminuir($clave)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (isset($_SESSION['carrito'][$clave])) {
                $item = $_SESSION['carrito'][$clave];

                // ✅ 1. Actualizar en sesión
                $_SESSION['carrito'][$clave]['cantidad']--;

                if ($_SESSION['carrito'][$clave]['cantidad'] <= 0) {
                    // ✅ 2. NUEVO: Eliminar de BD persistente si llega a 0
                    if (\Core\Helpers\CookieHelper::hasConsent()) {
                        $session_id = \Core\Helpers\CartPersistenceHelper::getCartSessionId();
                        $usuario_id = \Core\Helpers\SessionHelper::getUserId();

                        $carritoModel = new \Models\CarritoTemporal();

                        // Buscar el registro en BD
                        $carritoBD = $carritoModel->obtenerCarrito($session_id, $usuario_id);

                        foreach ($carritoBD as $itemBD) {
                            if (
                                $itemBD['producto_id'] == $item['producto_id'] &&
                                $itemBD['variante_id'] == $item['variante_id']
                            ) {

                                $carritoModel->eliminarProducto($itemBD['id'], $session_id, $usuario_id);
                                error_log("✅ Producto eliminado de carrito persistente (cantidad 0): Producto {$item['producto_id']}");
                                break;
                            }
                        }
                    }

                    unset($_SESSION['carrito'][$clave]);
                } else {
                    // ✅ 3. NUEVO: Actualizar cantidad en BD si no llega a 0
                    if (\Core\Helpers\CookieHelper::hasConsent()) {
                        $session_id = \Core\Helpers\CartPersistenceHelper::getCartSessionId();
                        $usuario_id = \Core\Helpers\SessionHelper::getUserId();

                        $carritoModel = new \Models\CarritoTemporal();

                        // Buscar el registro en BD y actualizar
                        $carritoBD = $carritoModel->obtenerCarrito($session_id, $usuario_id);

                        foreach ($carritoBD as $itemBD) {
                            if (
                                $itemBD['producto_id'] == $item['producto_id'] &&
                                $itemBD['variante_id'] == $item['variante_id']
                            ) {

                                // Actualizar cantidad en BD
                                $carritoModel->agregarProducto(
                                    $session_id,
                                    $item['producto_id'],
                                    -1, // Disminuir en 1
                                    $item['variante_id'],
                                    $usuario_id
                                );
                                error_log("✅ Cantidad disminuida en carrito persistente: Producto {$item['producto_id']}, Nueva cantidad: {$_SESSION['carrito'][$clave]['cantidad']}");
                                break;
                            }
                        }
                    }
                }
            }

            // Si el carrito queda vacío, limpiar el cupón
            if (empty($_SESSION['carrito'])) {
                CuponHelper::limpiarCuponSesion();
            }

            if ($this->isAjaxRequest()) {
                $this->jsonResponse(true, 'Cantidad disminuida.', $this->getCartState());
            }

            header('Location: ' . url('carrito/ver'));
            exit;
        } catch (\Exception $e) {
            error_log("❌ Error disminuyendo cantidad en carrito: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                $this->jsonResponse(false, 'Error al actualizar la cantidad.');
            } else {
                $_SESSION['flash_error'] = 'Error al actualizar la cantidad';
                header('Location: ' . url('carrito/ver'));
            }
            exit;
        }
    }

    /**
     * Elimina un producto del carrito.
     * Responde con JSON si es AJAX, de lo contrario redirige.
     */
    public function eliminar($clave)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (isset($_SESSION['carrito'][$clave])) {
                // ✅ 1. Obtener información del producto antes de eliminarlo
                $productoInfo = $_SESSION['carrito'][$clave];
                $producto_id = $productoInfo['producto_id'] ?? null;
                $variante_id = $productoInfo['variante_id'] ?? null;

                // 2. Eliminar de la sesión
                unset($_SESSION['carrito'][$clave]);

                // ✅ 3. NUEVO: Eliminar de la BD persistente
                if (\Core\Helpers\CookieHelper::hasConsent() && $producto_id) {
                    $session_id = \Core\Helpers\CartPersistenceHelper::getCartSessionId();
                    $usuario_id = \Core\Helpers\SessionHelper::getUserId();

                    $carritoModel = new \Models\CarritoTemporal();

                    // Buscar el ID en carrito_temporal que corresponde a este producto
                    $carritoBD = $carritoModel->obtenerCarrito($session_id, $usuario_id);

                    foreach ($carritoBD as $item) {
                        if (
                            $item['producto_id'] == $producto_id &&
                            $item['variante_id'] == $variante_id
                        ) {

                            $carritoModel->eliminarProducto($item['id'], $session_id, $usuario_id);
                            error_log("✅ Producto eliminado de carrito persistente: Producto $producto_id, Variante $variante_id");
                            break;
                        }
                    }
                }
            }

            // Si el carrito queda vacío, limpiar el cupón
            if (empty($_SESSION['carrito'])) {
                CuponHelper::limpiarCuponSesion();
            }

            if ($this->isAjaxRequest()) {
                $this->jsonResponse(true, 'Producto eliminado.', $this->getCartState());
            }

            $_SESSION['flash_success'] = 'Producto eliminado del carrito';
        } catch (\Exception $e) {
            error_log("❌ Error eliminando producto del carrito: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                $this->jsonResponse(false, 'Error al eliminar el producto.');
            } else {
                $_SESSION['flash_error'] = 'Error al eliminar el producto';
            }
        }

        header('Location: ' . url('carrito/ver'));
        exit;
    }

    /**
     * Agrega un producto al carrito.
     * Este método mantiene la redirección, ya que se suele llamar desde la página de un producto.
     */
    public function agregar()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Detectar si es una solicitud AJAX
        $isAjax = (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );

        $producto_id = isset($_POST['producto_id']) ? (int) $_POST['producto_id'] : 0;
        $variante_id = isset($_POST['variante_id']) && $_POST['variante_id'] !== '' ? (int) $_POST['variante_id'] : null;
        $talla = isset($_POST['talla']) ? trim((string) $_POST['talla']) : null;
        $color = isset($_POST['color']) ? trim((string) $_POST['color']) : null;
        $cantidad = isset($_POST['cantidad']) ? (int) $_POST['cantidad'] : 1;
        $referer = $_SERVER['HTTP_REFERER'] ?? url('carrito/ver');

        if ($producto_id <= 0 || $cantidad <= 0) {
            $mensaje = 'Datos de producto inválidos.';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $mensaje]);
                return;
            }
            $_SESSION['flash_error'] = $mensaje;
            header('Location: ' . $referer);
            exit;
        }

        $producto = Producto::obtenerPorId($producto_id);
        if (!$producto) {
            $mensaje = 'Producto no encontrado.';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $mensaje]);
                return;
            }
            $_SESSION['flash_error'] = $mensaje;
            header('Location: ' . $referer);
            exit;
        }

        $precio = (float)($producto['precio'] ?? 0.0);

        // Si hay variante_id, obtener stock de la variante específica
        $stock = null;
        if ($variante_id !== null) {
            require_once __DIR__ . '/../models/VarianteProducto.php';
            $variante = \Models\VarianteProducto::obtenerPorId($variante_id);
            if ($variante) {
                $stock = (int)$variante['stock'];
                $talla = $variante['talla'] ?? $talla;
                $color = $variante['color'] ?? $color;
            } else {
                $mensaje = 'Variante no encontrada.';
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => $mensaje]);
                    return;
                }
                $_SESSION['flash_error'] = $mensaje;
                header('Location: ' . $referer);
                exit;
            }
        } else {
            $stock = is_numeric($producto['stock'] ?? null) ? (int)$producto['stock'] : null;
        }

        // Clave única: incluir variante_id si existe
        $clave = $producto_id . '_' . ($variante_id ?? '') . '_' . ($talla ?? '') . '_' . ($color ?? '');

        if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
        $cantidadActual = $_SESSION['carrito'][$clave]['cantidad'] ?? 0;
        $nuevaCantidad = $cantidadActual + $cantidad;

        if ($stock !== null) {
            if ($nuevaCantidad > $stock) {
                $cantidad = max(0, $stock - $cantidadActual);
                if ($cantidad > 0) {
                    $_SESSION['flash_warning'] = "Stock limitado. Solo se agregaron {$cantidad} unidades.";
                    $nuevaCantidad = $stock;
                } else {
                    $mensaje = 'No hay más stock disponible para este producto.';
                    if ($isAjax) {
                        echo json_encode(['success' => false, 'message' => $mensaje]);
                        return;
                    }
                    $_SESSION['flash_error'] = $mensaje;
                    header('Location: ' . $referer);
                    exit;
                }
            }
        }

        // ✅ ✅ ✅ NUEVO CÓDIGO: GUARDAR EN CARRITO PERSISTENTE ✅ ✅ ✅
        try {
            // Solo guardar en BD si el usuario aceptó cookies
            if (\Core\Helpers\CookieHelper::hasConsent()) {
                $session_id = \Core\Helpers\CartPersistenceHelper::getCartSessionId();
                $usuario_id = \Core\Helpers\SessionHelper::getUserId();

                $carritoModel = new \Models\CarritoTemporal();
                $carritoModel->agregarProducto(
                    $session_id,
                    $producto_id,
                    $cantidad,
                    $variante_id,
                    $usuario_id
                );

                error_log("✅ Producto guardado en carrito persistente: Producto $producto_id, Cantidad $cantidad");
            }
        } catch (\Exception $e) {
            error_log("❌ Error guardando en carrito persistente: " . $e->getMessage());
            // No romper el flujo si falla el carrito persistente
        }

        // Agregar o actualizar producto (SESSION ORIGINAL - MANTENER)
        if (isset($_SESSION['carrito'][$clave])) {
            $_SESSION['carrito'][$clave]['cantidad'] = $nuevaCantidad;
        } else {
            $_SESSION['carrito'][$clave] = [
                'producto_id' => $producto_id,
                'variante_id' => $variante_id,
                'talla' => $talla,
                'color' => $color,
                'cantidad' => $cantidad,
                'precio' => $precio
            ];
        }

        // Calcular total actual del carrito
        $totalItems = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $totalItems += (int)$item['cantidad'];
        }

        if ($isAjax) {
            // ✅ Respuesta sin redirección
            echo json_encode([
                'success' => true,
                'message' => ' Agregado con éxito.',
                'itemCount' => $this->getTotalItems(),
                'total_items' => $this->getTotalItems()
            ]);
            return;
        }

        // Modo normal (recarga)
        $_SESSION['mensaje_carrito'] = ' Agregado con éxito.';
        header('Location: ' . $referer);
        exit;
    }

    /*NUEVO MÉTODO*/
    /**
     * Sincronizar carrito de BD a sesión (ejecutar al iniciar la app)
     */
    public static function sincronizarCarritoDesdeBD()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // ✅ NUEVO: Verificar si el carrito fue vaciado recientemente
        if (isset($_SESSION['carrito_vaciado']) && $_SESSION['carrito_vaciado'] === true) {
            error_log("🚫 Sincronización desactivada - carrito fue vaciado recientemente");
            unset($_SESSION['carrito_vaciado']); // Limpiar flag después de usarlo
            return;
        }

        // Solo si el usuario aceptó cookies
        if (!\Core\Helpers\CookieHelper::hasConsent()) {
            return;
        }

        try {
            $session_id = \Core\Helpers\CartPersistenceHelper::getCartSessionId();
            $usuario_id = \Core\Helpers\SessionHelper::getUserId();

            $carritoModel = new \Models\CarritoTemporal();
            $carritoBD = $carritoModel->obtenerCarrito($session_id, $usuario_id);

            // Limpiar carrito de sesión actual
            $_SESSION['carrito'] = [];

            // Sincronizar desde BD a sesión
            foreach ($carritoBD as $item) {
                $clave = $item['producto_id'] . '_' . ($item['variante_id'] ?? '') . '_' . ($item['talla'] ?? '') . '_' . ($item['color'] ?? '');

                $_SESSION['carrito'][$clave] = [
                    'producto_id' => $item['producto_id'],
                    'variante_id' => $item['variante_id'],
                    'talla' => $item['talla'] ?? null,
                    'color' => $item['color'] ?? null,
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio']
                ];
            }

            error_log("✅ Carrito sincronizado desde BD: " . count($carritoBD) . " items");
        } catch (\Exception $e) {
            error_log("❌ Error sincronizando carrito desde BD: " . $e->getMessage());
        }
    }


    private function getTotalItems()
    {
        $total = 0;
        if (!empty($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $item) {
                $total += $item['cantidad'];
            }
        }
        return $total;
    }


    /**
     * Muestra la vista del carrito para usuarios con sesión iniciada.
     */
    public function ver()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['usuario'])) {
            return $this->verSinSesion();
        }

        $cartState = $this->getCartState();
        $productosDetallados = [];
        $carrito = $_SESSION['carrito'] ?? [];

        if (!empty($carrito)) {
            foreach ($carrito as $clave => $item) {
                $producto = Producto::obtenerPorId($item['producto_id']);
                if ($producto) {
                    $producto['cantidad'] = $item['cantidad'];
                    $producto['talla'] = $item['talla'];
                    $producto['color'] = $item['color'];
                    $producto['clave'] = $clave;
                    $producto['subtotal'] = $producto['precio'] * $item['cantidad'];

                    $primera = ImagenProducto::obtenerPrimeraPorProducto((int)$item['producto_id']);
                    $producto['imagen'] = ($primera && !empty($primera['nombre_imagen']))
                        ? url('uploads/' . $primera['nombre_imagen'])
                        : null;
                    $productosDetallados[] = $producto;
                }
            }
        }

        $totales = $cartState['totals'];
        $promocionesAplicadas = $cartState['promotions'];
        $cupon_aplicado = $cartState['coupon'];

        require __DIR__ . '/../views/carrito/ver.php';
    }

    /**
     * Muestra la vista del carrito para usuarios sin sesión (invitados).
     */
    public function verSinSesion()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $cartState = $this->getCartState();
        $productosDetallados = [];
        $carrito = $_SESSION['carrito'] ?? [];

        if (!empty($carrito)) {
            foreach ($carrito as $clave => $item) {
                $producto = Producto::obtenerPorId($item['producto_id']);
                if ($producto) {
                    $producto['cantidad'] = $item['cantidad'];
                    $producto['talla'] = $item['talla'];
                    $producto['color'] = $item['color'];
                    $producto['clave'] = $clave;
                    $producto['subtotal'] = $producto['precio'] * $item['cantidad'];
                    $primera = ImagenProducto::obtenerPrimeraPorProducto((int)$item['producto_id']);
                    $producto['imagen'] = ($primera && !empty($primera['nombre_imagen']))
                        ? url('uploads/' . $primera['nombre_imagen'])
                        : null;
                    $productosDetallados[] = $producto;
                }
            }
        }

        $totales = $cartState['totals'];
        $promocionesAplicadas = $cartState['promotions'];
        $error = $_SESSION['auth_error'] ?? null;
        if ($error) unset($_SESSION['auth_error']);

        if (empty($carrito)) {
            // Caso A: Invitado y carrito vacío → mostrar solo el bloque vacío de ver
            require __DIR__ . '/../views/carrito/ver.php';
        } else {
            // Caso B: Invitado con productos → mostrar la vista completa
            require __DIR__ . '/../views/carrito/ver-sin-sesion.php';
        }
    }

    /**
     * Redirige al checkout o a la vista de login dependiendo del estado de la sesión.
     */
    public function finalizarCompra()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (empty($_SESSION['carrito'])) {
            $_SESSION['flash_error'] = 'Tu carrito está vacío.';
            header('Location: ' . url('/'));
            exit;
        }

        if (isset($_SESSION['usuario'])) {
            header('Location: ' . url('pedido/checkout'));
        } else {
            header('Location: ' . url('carrito/ver'));
        }
        exit;
    }

    /**
     * Aplica un cupón de descuento al carrito.
     */
    public function aplicarCupon()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('carrito/ver'));
            exit;
        }
        if (session_status() === PHP_SESSION_NONE) session_start();

        $codigo = trim($_POST['codigo'] ?? '');
        $carrito = $_SESSION['carrito'] ?? [];
        $usuario = $_SESSION['usuario'] ?? null;

        if (empty($codigo)) {
            $_SESSION['mensaje_cupon_error'] = 'Código de cupón requerido.';
        } elseif (empty($carrito)) {
            $_SESSION['mensaje_cupon_error'] = 'El carrito está vacío.';
        } elseif (!$usuario) {
            $_SESSION['mensaje_cupon_error'] = 'Debes iniciar sesión para aplicar un cupón.';
        } else {
            $productosParaValidacion = $this->getProductosDetalladosParaValidacion($carrito);
            $resultado = CuponHelper::aplicarCupon($codigo, $usuario['id'], $carrito, $productosParaValidacion);

            if ($resultado['exito']) {
                $_SESSION['cupon_aplicado'] = $resultado['cupon'];
                $_SESSION['mensaje_cupon_exito'] = $resultado['mensaje'];
            } else {
                $_SESSION['mensaje_cupon_error'] = $resultado['mensaje'];
            }
        }

        header('Location: ' . url('carrito/ver'));
        exit;
    }

    /**
     * Quita cualquier cupón aplicado del carrito.
     */
    public function quitarCupon()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        CuponHelper::limpiarCuponSesion();
        $_SESSION['mensaje_cupon_exito'] = 'Cupón removido correctamente.';
        header('Location: ' . url('carrito/ver'));
        exit;
    }
    public function contador()
    /*Del carrito */
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $count = 0;
        if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $item) {
                $count += (int)($item['cantidad'] ?? 0);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['count' => $count]);
        exit;
    }
}
