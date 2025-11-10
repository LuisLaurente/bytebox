<?php

namespace Controllers;

require_once __DIR__ . '/../vendor/autoload.php';

use Models\Pedido;
use Models\DetallePedido;
use Core\Helpers\PromocionHelper;
use Models\PedidoDireccion;
use Core\Helpers\CuponHelper;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use Core\Services\PedidoService;

class PagoController
{
    private $pedidoModel;
    private $detalleModel;
    private $pedidoDireccionModel;

    public function __construct()
    {
        $this->pedidoModel = new Pedido();
        $this->detalleModel = new DetallePedido();
        $this->pedidoDireccionModel = new PedidoDireccion();
    }

    /**
     * Crear preferencia de pago con Mercado Pago (FLUJO SIMPLIFICADO)
     */
    public function crearPagoMercadoPago()
    {
        header('Content-Type: application/json; charset=UTF-8');
        // ✅ LOGS DE CREDENCIALES (AQUÍ SÍ FUNCIONAN)
        error_log("🔑 CREDENCIALES USADAS EN BACKEND:");
        error_log("   - ACCESS_TOKEN: " . ($_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? 'NO ENCONTRADO'));
        error_log("   - PUBLIC_KEY: " . ($_ENV['MERCADOPAGO_PUBLIC_KEY'] ?? 'NO ENCONTRADO'));
        error_log("   - MODE: " . ($_ENV['MERCADOPAGO_MODE'] ?? 'NO DEFINIDO'));

        try {
            // ✅ VERIFICAR MÉTODO
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // ✅ INICIAR SESIÓN
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // ✅ VERIFICAR USUARIO AUTENTICADO
            if (!isset($_SESSION['usuario'])) {
                throw new \Exception('Usuario no autenticado');
            }

            // ✅ LEER DATOS
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['datos_checkout'])) {
                throw new \Exception('Datos incompletos');
            }

            $datosCheckout = $input['datos_checkout'];
            $usuario = $_SESSION['usuario'];
            $carrito = $_SESSION['carrito'] ?? [];

            if (empty($carrito)) {
                throw new \Exception('Carrito vacío');
            }

            // ✅ CALCULAR TOTALES
            $total = $this->calcularTotal($carrito, $usuario, $datosCheckout);
            if ($total <= 0) {
                throw new \Exception('Total inválido: ' . $total);
            }

            error_log("💰 TOTAL A PAGAR: " . $total);

            // ✅ CREAR PEDIDO EN BD (ESTADO: pendiente_pago)
            $pedidoId = $this->crearPedidoMercadoPago($usuario, $carrito, $total, $datosCheckout);
            if (!$pedidoId) {
                throw new \Exception('Error al crear el pedido');
            }

            error_log("✅ PEDIDO CREADO - ID: " . $pedidoId . " (pendiente_pago)");

            // ✅ CONFIGURAR MERCADO PAGO
            $accessToken = $_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? getenv('MERCADOPAGO_ACCESS_TOKEN');
            if (empty($accessToken)) {
                throw new \Exception('Access Token de MercadoPago no configurado');
            }

            MercadoPagoConfig::setAccessToken($accessToken);
            $client = new PreferenceClient();

            $baseUrl = 'https://bytebox.pe';

            // ✅ CREAR PREFERENCIA (SIMPLE COMO DOCUMENTACIÓN)
            $preference_data = [
                "items" => [
                    [
                        "id" => "pedido-" . $pedidoId,
                        "title" => "Compra en Bytebox - Pedido #" . $pedidoId,
                        "description" => "Productos tecnológicos",
                        "quantity" => 1,
                        "currency_id" => "PEN",
                        "unit_price" => $total
                    ]
                ],
                "payer" => [
                    "email" => $datosCheckout['datos_formulario']['facturacion_email'] ?? $usuario['email'],
                    "name" => $datosCheckout['datos_formulario']['facturacion_nombre'] ?? $usuario['nombre']
                ],
                "external_reference" => $pedidoId, // ← ID REAL DEL PEDIDO
                "back_urls" => [
                    "success" => $baseUrl . '/pago/exito',
                    "failure" => $baseUrl . '/pago/error',
                    "pending" => $baseUrl . '/pago/pendiente'
                ],
                "auto_return" => "approved",
                "notification_url" => $baseUrl . '/pago/webhook'
            ];

            error_log("🎪 CREANDO PREFERENCIA MP PARA PEDIDO: " . $pedidoId);

            $preference = $client->create($preference_data);

            if (!$preference || !$preference->id) {
                throw new \Exception('Error al crear preferencia en MercadoPago');
            }

            // ✅ GUARDAR PREFERENCE_ID EN PEDIDO
            $this->pedidoModel->actualizarPreferenciaMp($pedidoId, $preference->id);

            error_log("✅ PREFERENCIA CREADA - ID: " . $preference->id);

            // ✅ RESPONDER AL FRONTEND
            $response = [
                'success' => true,
                'preference_id' => $preference->id,
                'init_point' => $preference->init_point,
                'pedido_id' => $pedidoId
            ];

            echo json_encode($response);
        } catch (\Exception $e) {
            error_log("❌ ERROR crearPagoMercadoPago: " . $e->getMessage());
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Pago exitoso - Usuario regresa de Mercado Pago
     */
    public function exito()
    {
        error_log("🎯 PagoController::exito() INICIADO");

        // ✅ OBTENER DATOS DE MP
        $payment_id = $_GET['payment_id'] ?? null;
        $external_reference = $_GET['external_reference'] ?? null;
        $collection_status = $_GET['collection_status'] ?? null;

        error_log("📦 DATOS MP - payment_id: " . $payment_id . ", external_reference: " . $external_reference);

        if (!$external_reference) {
            error_log("❌ NO HAY external_reference");
            $this->redirigirError('No se pudo identificar el pedido');
        }

        // ✅ VERIFICAR SI EL PEDIDO YA FUE PROCESADO (evitar duplicados)
        if (PedidoService::pedidoYaProcesado($external_reference)) {
            error_log("⚠️ Pedido ya procesado, redirigiendo a confirmación");
            $this->redirigirAConfirmacion($external_reference);
            return;
        }

        // ✅ VERIFICAR PAGO CON SDK (CONFIRMACIÓN REAL)
        try {
            $estadoPago = $this->verificarPagoMercadoPago($payment_id);
            error_log("✅ ESTADO REAL DEL PAGO: " . $estadoPago);

            if ($estadoPago === 'approved') {
                // ✅ PAGO APROBADO - PROCESAR PEDIDO COMPLETO CON EL SERVICIO
                $this->procesarPagoExitoso($external_reference, $payment_id);
            } else {
                // ❌ PAGO NO APROBADO
                error_log("❌ PAGO NO APROBADO - Estado: " . $estadoPago);
                $this->redirigirError('El pago no fue aprobado. Estado: ' . $estadoPago);
            }
        } catch (\Exception $e) {
            error_log("❌ ERROR verificando pago: " . $e->getMessage());
            $this->redirigirError('Error al verificar el pago: ' . $e->getMessage());
        }
    }

    private function procesarPagoExitoso($pedidoId, $paymentId)
    {
        error_log("💰 PROCESANDO PAGO EXITOSO - Pedido: " . $pedidoId . ", Payment: " . $paymentId);

        try {
            // ✅ 1. OBTENER DATOS DE SESIÓN PARA LIMPIAR CARRITO
            session_start();
            $usuarioId = $_SESSION['usuario']['id'] ?? null;
            $sessionId = session_id();

            error_log("   👤 Usuario ID: " . ($usuarioId ?? 'No autenticado'));
            error_log("   🆔 Session ID: " . $sessionId);

            // ✅ 2. PROCESAR PEDIDO COMPLETO (STOCK + CARRITO + ESTADO)
            PedidoService::procesarPedidoCompleto($pedidoId, $usuarioId, $sessionId);
            error_log("   ✅ PedidoService ejecutado exitosamente");

            // ✅ 3. GUARDAR ID DE PAGO MP (opcional, para tracking)
            $this->guardarIdPagoMp($pedidoId, $paymentId);

            // ✅ 4. LIMPIAR CARRITO EN BD Y SESIONES ADICIONALES + DESACTIVAR SINCRONIZACIÓN
            $carritoModel = new \Models\CarritoTemporal();
            $carritoModel->limpiarCarrito($sessionId, $usuarioId);
            unset($_SESSION['carrito']);
            unset($_SESSION['cupon_aplicado']);
            unset($_SESSION['promociones']);

            // ✅ NUEVO: Desactivar sincronización temporalmente
            $_SESSION['carrito_vaciado'] = true;

            error_log("   ✅ Carrito limpiado en BD y sesiones - Sincronización desactivada");

            // ✅ 5. REDIRIGIR A CONFIRMACIÓN
            $this->redirigirAConfirmacion($pedidoId);
        } catch (\Exception $e) {
            error_log("❌ ERROR en procesarPagoExitoso: " . $e->getMessage());

            // ✅ MANEJO GRACIOSO DE ERRORES - Redirigir con mensaje informativo
            $_SESSION['flash_error'] = 'El pago fue exitoso, pero hubo un error procesando tu pedido. ' .
                'Por favor contacta a soporte con el número de pedido: ' . $pedidoId;

            $this->redirigirAConfirmacion($pedidoId);
        }
    }

    private function redirigirAConfirmacion($pedidoId)
    {
        $confirmacionUrl = url('pedido/confirmacion/' . $pedidoId);
        error_log("🎉 REDIRIGIENDO A CONFIRMACIÓN: " . $confirmacionUrl);
        header('Location: ' . $confirmacionUrl);
        exit;
    }

    /**
     * Webhook - Mercado Pago notifica automáticamente
     */
    public function webhook()
    {
        error_log("🔔 WEBHOOK MP RECIBIDO");

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            error_log("📦 DATOS WEBHOOK: " . json_encode($input));

            if (isset($input['type']) && $input['type'] === 'payment') {
                $payment_id = $input['data']['id'] ?? null;

                if ($payment_id) {
                    $estadoPago = $this->verificarPagoMercadoPago($payment_id);
                    error_log("🔔 WEBHOOK - Payment ID: " . $payment_id . " - Estado: " . $estadoPago);

                    // Obtener pedido por payment_id
                    $pedido = $this->obtenerPedidoPorPaymentId($payment_id);

                    if ($pedido && $estadoPago === 'approved') {
                        // ✅ USAR SERVICIO PARA PROCESAMIENTO COMPLETO (como respaldo)
                        if (!PedidoService::pedidoYaProcesado($pedido['id'])) {
                            error_log("🔔 WEBHOOK - Procesando pedido desde webhook: " . $pedido['id']);
                            PedidoService::procesarPedidoCompleto($pedido['id']);
                        } else {
                            error_log("🔔 WEBHOOK - Pedido ya procesado: " . $pedido['id']);
                        }
                    }
                }
            }

            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            error_log("❌ ERROR webhook: " . $e->getMessage());
            http_response_code(400);
        }
        exit;
    }

    /**
     * Pago pendiente
     */
    public function pendiente()
    {
        $external_reference = $_GET['external_reference'] ?? null;

        if ($external_reference) {
            error_log("⏳ PAGO PENDIENTE - Pedido: " . $external_reference);

            // ✅ VACIAR CARRITO EN BD Y SESIÓN + DESACTIVAR SINCRONIZACIÓN
            error_log("🧹 Vaciando carrito desde pendiente() - Pedido: " . $external_reference);

            // 1. Vaciar carrito en base de datos
            $carritoModel = new \Models\CarritoTemporal();
            $sessionId = session_id();
            $usuarioId = $_SESSION['usuario']['id'] ?? null;

            $carritoModel->limpiarCarrito($sessionId, $usuarioId);

            // 2. Vaciar sesión
            unset($_SESSION['carrito']);
            unset($_SESSION['cupon_aplicado']);
            unset($_SESSION['promociones']);

            // 3. ✅ NUEVO: Desactivar sincronización temporalmente
            $_SESSION['carrito_vaciado'] = true;

            error_log("✅ Carrito vaciado en BD y sesión - Sincronización desactivada");
        }

        header('Location: ' . url('/usuario/pedidos?payment_status=pending&external_ref=' . urlencode($external_reference)));
        exit;
    }

    /**
     * Error en el pago
     */
    public function error()
    {
        $external_reference = $_GET['external_reference'] ?? null;
        $collection_status = $_GET['collection_status'] ?? null;

        $mensaje = 'El pago no pudo ser procesado. Por favor, intenta nuevamente.';

        if ($collection_status === 'null' || $collection_status === null) {
            $mensaje = 'El pago fue cancelado. Puedes intentar nuevamente cuando lo desees.';
        } elseif ($collection_status === 'rejected') {
            $mensaje = 'El pago fue rechazado. Por favor, verifica los datos de tu tarjeta.';
        }

        if ($external_reference) {
            // Opcional: cambiar estado a "cancelado" después de cierto tiempo
            error_log("❌ PAGO FALLIDO - Pedido: " . $external_reference . " - Estado: " . $collection_status);
        }

        $_SESSION['flash_error'] = $mensaje;
        header('Location: ' . url('pedido/checkout'));
        exit;
    }

    /**
     * ========== MÉTODOS PRIVADOS ==========
     */

    private function calcularTotal($carrito, $usuario, $datosCheckout)
    {
        // ✅ CONVERTIR CARRITO PARA PROMOCIONES
        $carritoParaPromociones = [];
        foreach ($carrito as $item) {
            $producto = \Models\Producto::obtenerPorId($item['producto_id']);
            if ($producto) {
                $carritoParaPromociones[] = [
                    'id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'precio' => (float)$item['precio'],
                    'cantidad' => (int)$item['cantidad'],
                    'categoria_id' => $producto['categoria_id'] ?? null
                ];
            }
        }

        // ✅ APLICAR PROMOCIONES
        $resultado = PromocionHelper::aplicarPromociones($carritoParaPromociones, $usuario);

        // ✅ CALCULAR COSTO ENVÍO
        $costo_envio = $this->calcularCostoEnvio($datosCheckout, $resultado['envio_gratis']);

        // ✅ APLICAR CUPÓN
        $descuento_cupon = 0;
        $cupon_aplicado = $_SESSION['cupon_aplicado'] ?? null;
        if ($cupon_aplicado) {
            if ($cupon_aplicado['tipo'] === 'descuento_porcentaje') {
                $descuento_cupon = $resultado['subtotal'] * ($cupon_aplicado['valor'] / 100);
            } elseif ($cupon_aplicado['tipo'] === 'descuento_fijo') {
                $descuento_cupon = min($cupon_aplicado['valor'], $resultado['subtotal']);
            }
        }

        // ✅ CALCULAR TOTAL FINAL
        $total = max(0, $resultado['total'] - $descuento_cupon + $costo_envio);

        return $total;
    }

    private function calcularCostoEnvio($datosCheckout, $envio_gratis)
    {
        if ($envio_gratis) {
            return 0;
        }

        $departamento = $datosCheckout['datos_formulario']['departamento'] ?? '';

        // Lógica simple de envío (igual que tu sistema actual)
        if ($departamento === '15' || stripos($departamento, 'Lima') !== false) {
            return 8.00;
        } else {
            return 12.00;
        }
    }
    /**
     * ✅ CALCULAR DESCUENTO DE CUPÓN (igual que contraentrega)
     */
    private function calcularDescuentoCupon($subtotal)
    {
        $cupon_aplicado = $_SESSION['cupon_aplicado'] ?? null;
        if (!$cupon_aplicado) return 0;

        if ($cupon_aplicado['tipo'] === 'descuento_porcentaje') {
            return $subtotal * ($cupon_aplicado['valor'] / 100);
        } elseif ($cupon_aplicado['tipo'] === 'descuento_fijo') {
            return min($cupon_aplicado['valor'], $subtotal);
        }

        return 0;
    }

    private function crearPedidoMercadoPago($usuario, $carrito, $total, $datosCheckout)
    {
        $datos_formulario = $datosCheckout['datos_formulario'] ?? [];

        // ✅ CALCULAR SI HAY ENVÍO GRATIS (código existente)
        $carritoParaPromociones = [];
        foreach ($carrito as $item) {
            $producto = \Models\Producto::obtenerPorId($item['producto_id']);
            if ($producto) {
                $carritoParaPromociones[] = [
                    'id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'precio' => (float)$item['precio'],
                    'cantidad' => (int)$item['cantidad'],
                    'categoria_id' => $producto['categoria_id'] ?? null
                ];
            }
        }

        $resultadoPromociones = PromocionHelper::aplicarPromociones($carritoParaPromociones, $usuario);
        $envio_gratis = $resultadoPromociones['envio_gratis'];

        // ✅ PREPARAR DATOS DEL PEDIDO COMPLETOS (igual que contraentrega)
        $pedido_data = [
            'metodo_pago' => 'tarjeta',
            'estado' => 'pendiente_pago',
            'subtotal' => $this->calcularSubtotal($carrito),
            'total' => $total,
            'costo_envio' => $this->calcularCostoEnvio($datosCheckout, $envio_gratis),

            // ✅ CAMPOS DE FACTURACIÓN COMPLETOS
            'facturacion_tipo_documento' => $datos_formulario['facturacion_tipo_documento'] ?? '',
            'facturacion_numero_documento' => $datos_formulario['facturacion_numero_documento'] ?? '',
            'facturacion_nombre' => $datos_formulario['facturacion_nombre'] ?? '',
            'facturacion_direccion' => $datos_formulario['facturacion_direccion'] ?? '',
            'facturacion_email' => $datos_formulario['facturacion_email'] ?? '',

            // ✅ CAMPOS DE ENVÍO
            'envio_nombre' => $datos_formulario['nombre'] ?? $usuario['nombre'],

            // ✅ CAMPOS DE DESCUENTOS Y PROMOCIONES
            'descuento_promocion' => $resultadoPromociones['descuento'] ?? 0
        ];

        // ✅ AGREGAR CAMPOS DE CUPÓN SI EXISTE
        $cupon_aplicado = $_SESSION['cupon_aplicado'] ?? null;
        if ($cupon_aplicado) {
            $descuento_cupon = $this->calcularDescuentoCupon($resultadoPromociones['subtotal']);
            $pedido_data['cupon_id'] = $cupon_aplicado['id'] ?? null;
            $pedido_data['cupon_codigo'] = $cupon_aplicado['codigo'] ?? null;
            $pedido_data['descuento_cupon'] = $descuento_cupon;
        }

        // ✅ AGREGAR PROMOCIONES APLICADAS
        if (!empty($resultadoPromociones['promociones_aplicadas'])) {
            $pedido_data['promociones_aplicadas'] = json_encode($resultadoPromociones['promociones_aplicadas']);
        }

        // ✅ CREAR PEDIDO
        $pedidoId = $this->pedidoModel->crear($usuario['id'], $total, 'pendiente_pago', $pedido_data);

        if (!$pedidoId) {
            throw new \Exception('No se pudo crear el pedido en la base de datos');
        }

        error_log("✅ PEDIDO CREADO - ID: " . $pedidoId . " - INICIANDO CREACIÓN DE DIRECCIÓN");

        // ✅ NUEVO: CREAR REGISTRO EN PEDIDO_DIRECCIONES
        $this->crearDireccionPedidoMercadoPago($pedidoId, $usuario, $datos_formulario);

        // ✅ CREAR DETALLES DEL PEDIDO
        foreach ($carrito as $item) {
            $this->detalleModel->crear(
                $pedidoId,
                $item['producto_id'],
                $item['cantidad'],
                $item['precio'],
                $item['variante_id'] ?? null
            );
        }

        error_log("🎉 PROCESO COMPLETADO - Pedido MP creado: " . $pedidoId);
        return $pedidoId;
    }
    /**
     * ✅ CREAR REGISTRO EN PEDIDO_DIRECCIONES PARA MERCADO PAGO
     * Maneja tanto direcciones guardadas como nuevas
     */
    private function crearDireccionPedidoMercadoPago($pedidoId, $usuario, $datos_formulario)
    {
        try {
            error_log("📍 INICIANDO crearDireccionPedidoMercadoPago para pedido: " . $pedidoId);

            $direccion_id = $datos_formulario['direccion_id'] ?? '';
            $telefono_contacto = $datos_formulario['telefono'] ?? '';

            // ✅ LOG PARA DEBUG
            error_log("🔍 DATOS DIRECCIÓN MP:");
            error_log("   - direccion_id: " . $direccion_id);
            error_log("   - telefono: " . $telefono_contacto);
            error_log("   - datos_formulario keys: " . implode(', ', array_keys($datos_formulario)));

            // ✅ SI HAY DIRECCIÓN SELECCIONADA (GUARDADA)
            if (!empty($direccion_id) && $direccion_id !== '') {
                error_log("✅ Usando dirección guardada ID: " . $direccion_id);

                $resultado = $this->pedidoDireccionModel->crear($pedidoId, $direccion_id, $telefono_contacto);

                if ($resultado) {
                    error_log("✅ Dirección guardada vinculada al pedido MP: " . $pedidoId);
                    return true;
                } else {
                    throw new \Exception('Error al vincular dirección guardada');
                }
            }
            // ✅ SI ES NUEVA DIRECCIÓN (FORMULARIO)
            else {
                error_log("📍 Creando dirección temporal para pedido MP");
                return $this->crearDireccionTemporalMercadoPago($pedidoId, $usuario, $datos_formulario, $telefono_contacto);
            }
        } catch (\Exception $e) {
            error_log("❌ ERROR CRÍTICO crearDireccionPedidoMercadoPago: " . $e->getMessage());
            // ❌ NO relanzar la excepción - la dirección no debe bloquear el pago
            return false;
        }
    }
    /**
     * ✅ CREAR DIRECCIÓN TEMPORAL COMPLETA PARA PEDIDOS DE MERCADO PAGO
     * Crea registro en tabla direcciones igual que contraentrega
     */
    private function crearDireccionTemporalMercadoPago($pedidoId, $usuario, $datos_formulario, $telefono_contacto)
    {
        try {
            error_log("🏠 CREANDO DIRECCIÓN COMPLETA para pedido MP: " . $pedidoId);

            $conexion = \Core\Database::getConexion();

            // ✅ CONVERTIR IDs A NOMBRES
            $departamento_nombre = $this->convertirIdDepartamento($datos_formulario['departamento'] ?? '');
            $provincia_nombre = $this->convertirIdProvincia($datos_formulario['provincia'] ?? '');

            // ✅ CREAR REGISTRO COMPLETO EN DIRECCIONES (igual que contraentrega)
            $stmtDireccion = $conexion->prepare("
            INSERT INTO direcciones 
            (usuario_id, tipo, nombre_direccion, direccion, distrito, provincia, departamento, referencia, es_principal, activa) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

            $nombre_direccion = $datos_formulario['nombre_direccion'] ?? 'Envío pedido #' . $pedidoId;

            $stmtDireccion->execute([
                $usuario['id'],
                $datos_formulario['tipo_direccion'] ?? 'envio',
                $nombre_direccion,
                $datos_formulario['direccion'] ?? '',
                $datos_formulario['distrito'] ?? '',
                $provincia_nombre,
                $departamento_nombre,
                $datos_formulario['referencia'] ?? '',
                0, // No es principal
                0  // Temporal (no activa)
            ]);

            $direccion_id = $conexion->lastInsertId();
            error_log("✅ DIRECCIÓN CREADA EN TABLA direcciones - ID: " . $direccion_id);

            // ✅ CREAR EN PEDIDO_DIRECCIONES CON DIRECCION_ID
            $stmtPedidoDir = $conexion->prepare("
            INSERT INTO pedido_direcciones 
            (pedido_id, direccion_id, telefono_contacto) 
            VALUES (?, ?, ?)
        ");

            $resultado = $stmtPedidoDir->execute([
                $pedidoId,
                $direccion_id,
                $telefono_contacto
            ]);

            if ($resultado) {
                error_log("✅ PEDIDO_DIRECCIONES CREADA EXITOSAMENTE para pedido: " . $pedidoId);
                return true;
            } else {
                throw new \Exception('Error en ejecución SQL pedido_direcciones');
            }
        } catch (\Exception $e) {
            error_log("❌ ERROR crearDireccionTemporalMercadoPago: " . $e->getMessage());

            // ✅ FALLBACK: Crear solo dirección temporal (método anterior)
            return $this->crearDireccionTemporalFallback($pedidoId, $datos_formulario, $telefono_contacto);
        }
    }
    /**
     * ✅ FALLBACK: Crear solo dirección temporal si falla la creación completa
     */
    private function crearDireccionTemporalFallback($pedidoId, $datos_formulario, $telefono_contacto)
    {
        try {
            $conexion = \Core\Database::getConexion();

            // ✅ CONVERTIR IDs A NOMBRES
            $departamento_nombre = $this->convertirIdDepartamento($datos_formulario['departamento'] ?? '');
            $provincia_nombre = $this->convertirIdProvincia($datos_formulario['provincia'] ?? '');

            // ✅ CONSTRUIR DIRECCIÓN TEMPORAL CON NOMBRES
            $partes_direccion = array_filter([
                $datos_formulario['direccion'] ?? '',
                $datos_formulario['distrito'] ?? '',
                $provincia_nombre,
                $departamento_nombre
            ]);

            $direccion_temporal = implode(', ', $partes_direccion);

            if (!empty($datos_formulario['referencia'] ?? '')) {
                $direccion_temporal .= " - Referencia: " . $datos_formulario['referencia'];
            }

            // ✅ INSERTAR SOLO DIRECCIÓN TEMPORAL
            $stmt = $conexion->prepare("
            INSERT INTO pedido_direcciones 
            (pedido_id, direccion_temporal, telefono_contacto) 
            VALUES (?, ?, ?)
        ");

            $resultado = $stmt->execute([
                $pedidoId,
                $direccion_temporal,
                $telefono_contacto
            ]);

            if ($resultado) {
                error_log("✅ DIRECCIÓN TEMPORAL FALLBACK CREADA para pedido: " . $pedidoId);
                return true;
            } else {
                throw new \Exception('Error en fallback');
            }
        } catch (\Exception $e) {
            error_log("❌ ERROR FALLBACK: " . $e->getMessage());
            return false;
        }
    }

    private function calcularSubtotal($carrito)
    {
        $subtotal = 0;
        foreach ($carrito as $item) {
            $subtotal += $item['precio'] * $item['cantidad'];
        }
        return $subtotal;
    }

    private function verificarPagoMercadoPago($payment_id)
    {
        if (!$payment_id) {
            throw new \Exception('Payment ID no proporcionado');
        }

        $accessToken = $_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? getenv('MERCADOPAGO_ACCESS_TOKEN');
        MercadoPagoConfig::setAccessToken($accessToken);

        $client = new PaymentClient();
        $payment = $client->get($payment_id);

        return $payment->status;
    }

    private function guardarIdPagoMp($pedidoId, $paymentId)
    {
        try {
            $conexion = \Core\Database::getConexion();
            $stmt = $conexion->prepare("UPDATE pedidos SET mp_payment_id = ? WHERE id = ?");
            $stmt->execute([$paymentId, $pedidoId]);
            error_log("✅ Payment ID guardado: " . $paymentId . " para pedido: " . $pedidoId);
        } catch (\Exception $e) {
            error_log("⚠️ Error guardando payment_id: " . $e->getMessage());
        }
    }

    private function obtenerPedidoPorPaymentId($paymentId)
    {
        try {
            $conexion = \Core\Database::getConexion();
            $stmt = $conexion->prepare("SELECT * FROM pedidos WHERE mp_payment_id = ?");
            $stmt->execute([$paymentId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("❌ Error obteniendo pedido por payment_id: " . $e->getMessage());
            return null;
        }
    }

    private function redirigirError($mensaje)
    {
        $_SESSION['flash_error'] = $mensaje;
        header('Location: ' . url('pedido/checkout'));
        exit;
    }
    /**
     * ✅ CONVERTIR ID DE DEPARTAMENTO A NOMBRE (igual que PedidoController)
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
     * ✅ CONVERTIR ID DE PROVINCIA A NOMBRE (igual que PedidoController) 
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
