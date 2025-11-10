<?php

namespace Controllers;

use Models\Pedido;
use Models\Usuario;
use Models\DetallePedido;
use Models\PedidoDireccion;
use Models\Producto;
use Core\Helpers\PromocionHelper;
use Core\Helpers\CuponHelper;
use Exception;

class PedidoController
{
    private $pedidoModel;
    private $usuarioModel;
    private $detalleModel;
    private $pedidoDireccionModel;

    public function __construct()
    {
        $this->pedidoModel = new Pedido();
        $this->usuarioModel = new Usuario();
        $this->detalleModel = new DetallePedido();
        $this->pedidoDireccionModel = new PedidoDireccion();
    }

    private function convertirCarritoParaPromociones($carrito)
    {
        $carritoParaPromociones = [];

        error_log("=== DEBUG convertirCarritoParaPromociones ===");

        foreach ($carrito as $item) {
            // Obtener datos del producto
            $producto = \Models\Producto::obtenerPorId($item['producto_id']);
            if ($producto) {
                error_log("Producto ID: " . $producto['id']);
                error_log("  Precio en BD: " . $producto['precio']);
                error_log("  Precio en carrito: " . $item['precio']);
                error_log("  Cantidad: " . $item['cantidad']);

                $carritoParaPromociones[] = [
                    'id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'precio' => (float)$producto['precio'], // ← USAR PRECIO DE BD
                    'cantidad' => (int)$item['cantidad'],
                    'categoria_id' => $producto['categoria_id'] ?? null,
                    'precio_final' => 0,
                    'descuento_aplicado' => 0,
                    'promociones' => []
                ];
            }
        }

        return $carritoParaPromociones;
    }


    // Página de pre-checkout para usuarios no autenticados
    public function precheckout()
    {
        // Si ya está logueado, redirigir directo al checkout
        if (isset($_SESSION['usuario'])) {
            header('Location: ' . url('pedido/checkout'));
            exit;
        }

        $carrito = $_SESSION['carrito'] ?? [];
        if (empty($carrito)) {
            header('Location: ' . url('carrito/ver'));
            exit;
        }

        // Calcular totales para mostrar en la página
        $usuario = null; // Usuario no autenticado
        $carritoParaPromociones = $this->convertirCarritoParaPromociones($carrito);
        $resultado = PromocionHelper::aplicarPromociones($carritoParaPromociones, $usuario);
        $totales = [
            'subtotal' => $resultado['subtotal'],
            'descuento' => $resultado['descuento'],
            'total' => $resultado['total'],
            'envio_gratis' => $resultado['envio_gratis']
        ];

        require __DIR__ . '/../views/pedido/precheckout.php';
    }

    // Muestra formulario de checkout
    public function checkout()
    {
        // Verificar que el usuario esté autenticado
        if (!isset($_SESSION['usuario'])) {
            header('Location: ' . url('pedido/precheckout'));
            exit;
        }

        $carrito = $_SESSION['carrito'] ?? [];
        $usuario = $_SESSION['usuario'] ?? null;

        $carritoParaPromociones = $this->convertirCarritoParaPromociones($carrito);
        $resultado = PromocionHelper::aplicarPromociones($carritoParaPromociones, $usuario);

        $totales = [
            'subtotal' => $resultado['subtotal'],
            'descuento' => $resultado['descuento'],
            'total' => $resultado['total'],
            'envio_gratis' => $resultado['envio_gratis']
        ];

        require __DIR__ . '/../views/pedido/checkout_nuevo.php';
    }

    // Procesa y guarda el pedido completo
    // Procesa y guarda el pedido completo
    public function registrar()
    {
        // DEBUG: Verificar datos recibidos
        error_log("=== DEBUG PEDIDO REGISTRAR ===");
        error_log("POST datos: " . print_r($_POST, true));
        error_log("metodo_pago: " . ($_POST['metodo_pago'] ?? 'NO DEFINIDO'));
        error_log("metodo_pago_seleccionado: " . ($_POST['metodo_pago_seleccionado'] ?? 'NO DEFINIDO'));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar que el usuario esté autenticado
            if (!isset($_SESSION['usuario'])) {
                header('Location: ' . url('pedido/precheckout'));
                exit;
            }

            $usuario = $_SESSION['usuario'];

            // ==================== DATOS DE DIRECCIÓN ====================
            $direccion_id = $_POST['direccion_id'] ?? '';

            // El teléfono siempre viene del campo visible del formulario
            $envio_celular = trim($_POST['telefono'] ?? '');
            $envio_nombre = $usuario['nombre'];

            // Si se seleccionó una dirección guardada, cargar sus datos
            if (!empty($direccion_id)) {
                try {
                    $conexion = \Core\Database::getConexion();
                    $stmt = $conexion->prepare("SELECT * FROM direcciones WHERE id = ? AND usuario_id = ? AND activa = 1");
                    $stmt->execute([$direccion_id, $usuario['id']]);
                    $direccion_guardada = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if ($direccion_guardada) {
                        // Usar datos de la dirección guardada
                        $envio_distrito = $direccion_guardada['distrito'];
                        $envio_provincia = $direccion_guardada['provincia'];
                        $envio_departamento = $direccion_guardada['departamento'];
                        $envio_direccion = $direccion_guardada['direccion'];
                        $envio_referencia = $direccion_guardada['referencia'] ?? '';
                    } else {
                        // Dirección no encontrada, procesar como nueva
                        $direccion_id = '';
                    }
                } catch (Exception $e) {
                    error_log("Error al cargar dirección guardada: " . $e->getMessage());
                    $direccion_id = '';
                }
            }

            // Si no hay dirección guardada, usar datos del formulario
            if (empty($direccion_id)) {
                $envio_distrito = trim($_POST['distrito'] ?? '');
                $envio_provincia = trim($_POST['provincia'] ?? '');
                $envio_departamento = trim($_POST['departamento'] ?? '');
                $envio_direccion = trim($_POST['direccion'] ?? '');
                $envio_referencia = trim($_POST['referencia'] ?? '');

                // Si viene un nombre específico en el formulario, usarlo
                if (!empty($_POST['nombre'])) {
                    $envio_nombre = trim($_POST['nombre']);
                }
            }

            // Construir ubicación completa desde los campos separados
            $ubicacion_parts = array_filter([
                $envio_distrito,
                $envio_provincia,
                $envio_departamento
            ]);
            $envio_ubicacion = implode(', ', $ubicacion_parts);

            // ==================== DATOS DE FACTURACIÓN ====================
            $facturacion_tipo_documento = trim($_POST['facturacion_tipo_documento'] ?? '');
            $facturacion_numero_documento = trim($_POST['facturacion_numero_documento'] ?? '');
            $facturacion_nombre = trim($_POST['facturacion_nombre'] ?? '');
            $facturacion_direccion = trim($_POST['facturacion_direccion'] ?? '');
            $facturacion_email = trim($_POST['facturacion_email'] ?? '');

            // ==================== DATOS ADICIONALES ====================
            $guardar_direccion = isset($_POST['guardar_direccion']);
            $tipo_direccion = $_POST['tipo_direccion'] ?? 'casa';
            $nombre_direccion = trim($_POST['nombre_direccion'] ?? '');

            $carrito = isset($_SESSION['carrito']) && is_array($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
            $errores = [];

            // ==================== VALIDACIONES ====================
            // Validaciones de envío
            if ($envio_nombre === '') $errores[] = 'El nombre de envío es obligatorio.';
            if ($envio_celular === '') $errores[] = 'El celular de envío es obligatorio.';
            if ($envio_departamento === '') $errores[] = 'El departamento es obligatorio.';
            // La provincia es opcional - algunos departamentos solo tienen distritos
            if ($envio_distrito === '') $errores[] = 'El distrito es obligatorio.';
            if ($envio_direccion === '') $errores[] = 'La dirección de envío es obligatoria.';

            // Validaciones de facturación
            if ($facturacion_tipo_documento === '') $errores[] = 'El tipo de documento es obligatorio.';
            if ($facturacion_numero_documento === '') $errores[] = 'El número de documento es obligatorio.';
            if ($facturacion_nombre === '') $errores[] = 'El nombre o razón social es obligatorio.';
            if ($facturacion_direccion === '') $errores[] = 'La dirección fiscal es obligatoria.';
            if ($facturacion_email === '') $errores[] = 'El correo electrónico es obligatorio.';

            if (!filter_var($facturacion_email, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'El formato del correo electrónico no es válido.';
            }

            if (!preg_match('/^[0-9\s\+\-\(\)]+$/', $envio_celular)) {
                $errores[] = 'El celular solo debe contener números, espacios y símbolos válidos.';
            }

            if (empty($carrito)) $errores[] = 'El carrito está vacío.';

            // Validar que todos los productos tengan precio
            foreach ($carrito as $item) {
                if (!isset($item['precio'])) {
                    $errores[] = 'Falta el precio de un producto en el carrito. Vuelve a agregar los productos.';
                    break;
                }
            }

            if (!empty($errores)) {
                $_SESSION['errores_checkout'] = $errores;
                header('Location: ' . url('pedido/checkout'));
                exit;
            }

            try {
                $conexion = \Core\Database::getConexion();
                $conexion->beginTransaction();

                // ==================== CALCULAR COSTO DE ENVÍO ====================
                $costo_envio = 0;
                // Verificar si es Lima (departamento ID 15 o nombre contiene "Lima")
                if ($envio_departamento === '15' || stripos($envio_ubicacion, 'Lima') !== false) {
                    $costo_envio = 8.00;
                } else {
                    $costo_envio = 12.00; // Provincia
                }

                // ==================== MANEJAR DIRECCIÓN DE ENVÍO ====================
                // NUEVA LÓGICA: Siempre crear dirección en tabla direcciones
                $direccion_id_para_pedido = null;

                if (!empty($direccion_id)) {
                    // Usar dirección existente seleccionada
                    $direccion_id_para_pedido = $direccion_id;
                } else {
                    // Crear nueva dirección (temporal o permanente)
                    try {
                        $activa = $guardar_direccion ? 1 : 0; // 1=permanente, 0=temporal
                        $nombre_final = $guardar_direccion ?
                            ($nombre_direccion ?: ucfirst($tipo_direccion)) :
                            'Envío temporal';

                        $stmt = $conexion->prepare("
                        INSERT INTO direcciones 
                        (usuario_id, tipo, nombre_direccion, direccion, distrito, provincia, departamento, referencia, es_principal, activa) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                        // ✅ CORREGIDO: Convertir IDs a nombres antes de guardar
                        $nombreProvincia = $this->convertirIdProvincia($envio_provincia);
                        $nombreDepartamento = $this->convertirIdDepartamento($envio_departamento);

                        $stmt->execute([
                            $usuario['id'],
                            $tipo_direccion,
                            $nombre_final,
                            $envio_direccion,
                            $envio_distrito,
                            $nombreProvincia,      // ← Usar nombre convertido
                            $nombreDepartamento,   // ← Usar nombre convertido
                            $envio_referencia,
                            0, // No es principal
                            $activa
                        ]);

                        $direccion_id_para_pedido = $conexion->lastInsertId();

                        error_log("✅ Dirección creada - ID: $direccion_id_para_pedido, Tipo: " . ($activa ? 'PERMANENTE' : 'TEMPORAL'));
                    } catch (Exception $e) {
                        error_log("❌ Error al crear dirección: " . $e->getMessage());
                        throw new Exception('No se pudo guardar la dirección de envío');
                    }
                }

                $carritoParaPromociones = $this->convertirCarritoParaPromociones($carrito);
                $resultado = PromocionHelper::aplicarPromociones($carritoParaPromociones, $usuario);

                // DEBUG CRÍTICO - VER SUMA REAL
                $subtotal_real = 0;
                foreach ($carrito as $item) {
                    $subtotal_real += $item['precio'] * $item['cantidad'];
                }

                error_log("=== DEBUG SUBTOTAL PROBLEMA ===");
                error_log("Carrito original: " . count($carrito) . " productos");
                error_log("Carrito para promociones: " . count($carritoParaPromociones) . " productos");
                error_log("Subtotal real (carrito): " . $subtotal_real);
                error_log("Subtotal calculado por PromocionHelper: " . $resultado['subtotal']);
                error_log("Diferencia: " . ($resultado['subtotal'] - $subtotal_real));

                foreach ($carrito as $item) {
                    error_log("Carrito original - ID: " . $item['producto_id'] . ", Precio: " . $item['precio'] . ", Cantidad: " . $item['cantidad']);
                }

                foreach ($carritoParaPromociones as $item) {
                    error_log("Carrito promociones - ID: " . $item['id'] . ", Precio: " . $item['precio'] . ", Cantidad: " . $item['cantidad']);
                }

                // CORREGIDO - usar el subtotal real en lugar del del PromocionHelper
                $totales = [
                    'subtotal' => $subtotal_real, // ← CORREGIDO AQUÍ
                    'descuento' => $resultado['descuento'],
                    'total' => $resultado['total'],
                    'envio_gratis' => $resultado['envio_gratis']
                ];

                // ==================== APLICAR CUPÓN ====================
                $cupon_aplicado = $_SESSION['cupon_aplicado'] ?? null;
                $descuento_cupon = 0;
                $cupon_id = null;
                $cupon_codigo = null;

                if ($cupon_aplicado) {
                    $aplicacion = CuponHelper::aplicarCupon($cupon_aplicado['codigo'], $usuario['id'], $carrito);
                    if ($aplicacion['exito']) {
                        $descuento_cupon = $aplicacion['descuento'];
                        $cupon_id = $aplicacion['cupon']['id'];
                        $cupon_codigo = $aplicacion['cupon']['codigo'];
                    }
                }

                // Considerar envío gratis por promoción
                if ($resultado['envio_gratis']) {
                    $costo_envio = 0;
                }

                // Agregar costo de envío al total (puede ser 0 si hay envío gratis)
                $totales['costo_envio'] = $costo_envio;

                // CALCULAR TOTAL FINAL CORRECTAMENTE
                $totales['total'] = max(
                    $totales['subtotal'] - $totales['descuento'] - $descuento_cupon + $costo_envio,
                    0
                );

                // ==================== PREPARAR DATOS DEL PEDIDO ====================
                $pedido_data = [
                    'cupon_id' => $cupon_id,
                    'cupon_codigo' => $cupon_codigo,
                    'descuento_cupon' => $descuento_cupon,
                    'subtotal' => $subtotal_real,
                    'descuento_promocion' => $totales['descuento'],
                    'costo_envio' => $costo_envio,
                    'promociones_aplicadas' => json_encode($resultado['promociones_aplicadas']),
                    'metodo_pago' => $_POST['metodo_pago'] ?? 'contraentrega',
                    'envio_nombre' => $envio_nombre,
                    // Datos de facturación
                    'facturacion_tipo_documento' => $facturacion_tipo_documento,
                    'facturacion_numero_documento' => $facturacion_numero_documento,
                    'facturacion_nombre' => $facturacion_nombre,
                    'facturacion_direccion' => $facturacion_direccion,
                    'facturacion_email' => $facturacion_email
                ];

                // ==================== CREAR PEDIDO ====================
                $pedido_id = $this->pedidoModel->crear($usuario['id'], $totales['total'], 'pendiente', $pedido_data);
                if (!$pedido_id) {
                    throw new Exception('No se pudo crear el pedido');
                }

                // ==================== GUARDAR DIRECCIÓN DEL PEDIDO ====================
                try {
                    // NUEVO: Solo pasamos direccion_id, no direccion_temporal
                    $this->pedidoDireccionModel->crear($pedido_id, $direccion_id_para_pedido, $envio_celular);
                } catch (Exception $e) {
                    error_log("No se pudo guardar la dirección del pedido: " . $e->getMessage());
                }

                // ==================== GUARDAR DETALLE DEL PEDIDO ====================
                foreach ($carrito as $item) {
                    $ok = $this->detalleModel->crear(
                        $pedido_id,
                        $item['producto_id'],
                        $item['cantidad'],
                        $item['precio'],
                        $item['variante_id'] ?? null
                    );
                    if (!$ok) {
                        throw new Exception('No se pudo guardar el detalle del pedido');
                    }
                }

                // ==================== DESCONTAR STOCK ====================
                foreach ($carrito as $item) {
                    $cantidad = (int)$item['cantidad'];

                    // Si hay variante_id, descontar del stock de la variante
                    if (!empty($item['variante_id'])) {
                        require_once __DIR__ . '/../models/VarianteProducto.php';
                        $variante = \Models\VarianteProducto::obtenerPorId($item['variante_id']);
                        if ($variante) {
                            $nuevoStock = max(0, (int)$variante['stock'] - $cantidad);
                            \Models\VarianteProducto::actualizar(
                                $item['variante_id'],
                                $variante['talla'],
                                $variante['color'],
                                $nuevoStock
                            );
                        }
                    } else {
                        // Sin variante, descontar del stock general del producto
                        $producto = Producto::obtenerPorId($item['producto_id']);
                        if ($producto && $producto['stock'] !== null) {
                            $nuevoStock = max(0, (int)$producto['stock'] - $cantidad);
                            $stmtStock = $conexion->prepare("UPDATE productos SET stock = ? WHERE id = ?");
                            $stmtStock->execute([$nuevoStock, $item['producto_id']]);
                        }
                    }
                }

                // ==================== REGISTRAR USO DEL CUPÓN ====================
                if ($cupon_id) {
                    CuponHelper::registrarUso($cupon_id, $usuario['id'], $pedido_id);
                    unset($_SESSION['cupon_aplicado']);
                }

                $conexion->commit();

                // ==================== LIMPIAR Y REDIRIGIR ====================
                $_SESSION['carrito'] = [];
                unset($_SESSION['promociones']);

                // ✅ NUEVO: Desactivar sincronización automática del carrito
                $_SESSION['carrito_vaciado'] = true;

                header('Location: ' . url('pedido/confirmacion/' . $pedido_id));
                exit;
            } catch (Exception $e) {
                $conexion->rollback();
                error_log("Error en PedidoController::registrar: " . $e->getMessage());
                $_SESSION['errores_checkout'] = ['Error al procesar el pedido: ' . $e->getMessage()];
                header('Location: ' . url('pedido/checkout'));
                exit;
            }
        }
    }

    // ==================== MÉTODOS EXISTENTES (sin cambios) ====================

    // Muestra un pedido específico
    // En Controllers/PedidoController.php

    public function ver($id)
    {
        $pedido = $this->pedidoModel->obtenerPorId($id);
        $detalles = $this->detalleModel->obtenerPorPedido($id);

        // Obtener dirección y teléfono del pedido
        $direccion_pedido = null;
        $telefono_contacto = null;
        $direccion_detalle = []; // NUEVO: Para datos separados

        try {
            $pedido_direccion = $this->pedidoDireccionModel->obtenerPorPedido($id);

            // DEBUG TEMPORAL - Ver qué datos viene de la base de datos
            echo "<!-- DEBUG pedido_direccion: " . print_r($pedido_direccion, true) . " -->";
            error_log("DEBUG pedido_direccion: " . print_r($pedido_direccion, true));

            if ($pedido_direccion) {
                $telefono_contacto = $pedido_direccion['telefono_contacto'] ?? null;
                $direccion_pedido = $this->pedidoDireccionModel->obtenerDireccionCompleta($id);

                // NUEVO: Obtener datos de dirección por separado
                $direccion_detalle = [
                    'direccion' => $pedido_direccion['direccion'] ?? '',
                    'distrito' => $pedido_direccion['distrito'] ?? '',
                    'provincia' => $pedido_direccion['provincia'] ?? '',
                    'departamento' => $pedido_direccion['departamento'] ?? '',
                    'referencia' => $pedido_direccion['referencia'] ?? '',
                    'nombre_direccion' => $pedido_direccion['nombre_direccion'] ?? ''
                ];
                // Convertir IDs a nombres si es necesario
                if (!empty($direccion_detalle['departamento']) && is_numeric($direccion_detalle['departamento'])) {
                    $direccion_detalle['departamento'] = $this->convertirIdDepartamento($direccion_detalle['departamento']);
                }

                if (!empty($direccion_detalle['provincia']) && is_numeric($direccion_detalle['provincia'])) {
                    $direccion_detalle['provincia'] = $this->convertirIdProvincia($direccion_detalle['provincia']);
                }

                // DEBUG de los datos separados
                echo "<!-- DEBUG direccion_detalle: " . print_r($direccion_detalle, true) . " -->";
                error_log("DEBUG direccion_detalle: " . print_r($direccion_detalle, true));
            }
        } catch (Exception $e) {
            error_log("Error obteniendo dirección del pedido: " . $e->getMessage());
            $direccion_pedido = 'Dirección no disponible';
            $direccion_detalle = [];
        }

        // ... (el resto del método permanece igual)
        // Obtener nombres de productos para cada detalle
        $productoModel = new \Models\Producto();
        $detalles_con_nombres = [];

        foreach ($detalles as $detalle) {
            $producto = $productoModel->obtenerPorId($detalle['producto_id']);
            $detalle_con_nombre = $detalle;
            $detalle_con_nombre['producto_nombre'] = $producto ? $producto['nombre'] : 'Producto no encontrado';

            if ($producto) {
                $producto_preparado = $productoModel->prepararProductoParaVista($producto);
                // ✅ USAR url() para generar ruta absoluta como en confirmacion.php
                $detalle_con_nombre['producto_imagen'] = !empty($producto_preparado['imagenes'][0])
                    ? url($producto_preparado['imagenes'][0])
                    : url('image/default-product.jpg');
            } else {
                $detalle_con_nombre['producto_imagen'] = url('image/default-product.jpg');
            }

            if (!empty($detalle_con_nombre['variante_id'])) {
                require_once __DIR__ . '/../models/VarianteProducto.php';
                $variante = \Models\VarianteProducto::obtenerPorId($detalle_con_nombre['variante_id']);
                if ($variante) {
                    $detalle_con_nombre['variante_talla'] = $variante['talla'] ?? null;
                    $detalle_con_nombre['variante_color'] = $variante['color'] ?? null;
                }
            }

            $detalles_con_nombres[] = $detalle_con_nombre;
        }

        $detalles = $detalles_con_nombres;

        // Obtener información del cupón si existe
        $cupon_info = null;
        if (!empty($pedido['cupon_id'])) {
            $cuponModel = new \Models\Cupon();
            $cupon = $cuponModel->obtenerPorId($pedido['cupon_id']);
            if ($cupon) {
                $cupon_info = [
                    'codigo' => $cupon['codigo'],
                    'tipo' => $cupon['tipo'],
                    'valor' => $cupon['valor'],
                    'descuento_aplicado' => $pedido['descuento_cupon']
                ];
            }
        }

        // Obtener nombres y montos de promociones aplicadas
        $promociones_aplicadas = [];
        if (!empty($pedido['promociones_aplicadas'])) {
            $promociones_json = json_decode($pedido['promociones_aplicadas'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($promociones_json)) {
                $promociones_aplicadas = $promociones_json;
            } else {
                $nombres = explode(', ', $pedido['promociones_aplicadas']);
                foreach ($nombres as $nombre) {
                    $promociones_aplicadas[] = [
                        'nombre' => $nombre,
                        'monto' => 'N/A'
                    ];
                }
            }
        }

        if (empty($promociones_aplicadas) && !empty($pedido['descuento_promocion']) && $pedido['descuento_promocion'] > 0) {
            $promociones_aplicadas[] = [
                'nombre' => 'Promociones varias',
                'monto' => $pedido['descuento_promocion']
            ];
        }

        require __DIR__ . '/../views/pedido/ver.php';
    }
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

    // Lista todos los pedidos
    public function listar()
    {
        try {
            $pedidos = $this->pedidoModel->obtenerTodosConDirecciones();
        } catch (Exception $e) {
            $pedidos = $this->pedidoModel->obtenerTodos();
        }
        require __DIR__ . '/../views/pedido/listar.php';
    }

    // Aplicar cupón via AJAX
    public function aplicarCupon()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        $codigo = trim($_POST['codigo'] ?? '');
        $carrito = $_SESSION['carrito'] ?? [];

        if (empty($codigo)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Código de cupón requerido']);
            exit;
        }

        if (empty($carrito)) {
            echo json_encode(['exito' => false, 'mensaje' => 'El carrito está vacío']);
            exit;
        }

        $cliente_id = $_SESSION['cliente_id'] ?? 1;

        $resultado = CuponHelper::aplicarCupon($codigo, $cliente_id, $carrito);

        if ($resultado['exito']) {
            $_SESSION['cupon_aplicado'] = $resultado['cupon'];
        }

        echo json_encode($resultado);
        exit;
    }

    // Quitar cupón aplicado
    public function quitarCupon()
    {
        CuponHelper::limpiarCuponSesion();

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['exito' => true, 'mensaje' => 'Cupón removido']);
        } else {
            header('Location: ' . url('carrito/ver'));
        }
        exit;
    }

    // Cambia el estado del pedido
    public function cambiarEstado()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $estado = $_POST['estado'] ?? null;
            if ($id && $estado) {
                $this->pedidoModel->actualizarEstado($id, $estado);
            }
            header('Location: ' . url('pedido/listar'));
            exit;
        }
    }

    // Muestra mensaje de confirmación de compra
    public function confirmacion($id = null)
    {
        // Verificar que el usuario esté autenticado
        if (!isset($_SESSION['usuario'])) {
            header('Location: ' . url('home/index'));
            exit;
        }

        $usuario_id = $_SESSION['usuario']['id'];

        // ✅ OBTENER ID DEL PEDIDO DESDE PARÁMETRO O SESIÓN
        if (!$id) {
            $id = $_SESSION['ultimo_pedido_id'] ?? null;
        }

        if (!$id) {
            echo "ID de pedido no especificado.";
            return;
        }

        // Obtener pedido verificando que pertenezca al usuario
        $pedido = $this->pedidoModel->obtenerPorIdYUsuario($id, $usuario_id);

        if (!$pedido) {
            header('Location: ' . url('usuario/pedidos'));
            exit;
        }

        // Obtener detalles del pedido (productos)
        $detalles_pedido = $this->detalleModel->obtenerPorPedido($id);

        // Preparar productos para la vista
        $productos_pedido = [];
        $productoModel = new \Models\Producto();

        foreach ($detalles_pedido as $detalle) {
            $producto = $productoModel->obtenerPorId($detalle['producto_id']);
            if ($producto) {
                // Preparar producto para vista (con imágenes)
                $producto = $productoModel->prepararProductoParaVista($producto);

                $productos_pedido[] = [
                    'nombre_producto' => $detalle['producto_nombre'] ?? $producto['nombre'] ?? 'Producto',
                    'imagen_url' => !empty($producto['imagenes'][0]) ? $producto['imagenes'][0] : url('image/default-product.jpg'),
                    'talla' => $detalle['variante_talla'] ?? null,
                    'color' => $detalle['variante_color'] ?? null,
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario']
                ];
            } else {
                // Si no se encuentra el producto, usar datos del detalle
                $productos_pedido[] = [
                    'nombre_producto' => $detalle['producto_nombre'] ?? 'Producto no disponible',
                    'imagen_url' => url('image/default-product.jpg'),
                    'talla' => $detalle['variante_talla'] ?? null,
                    'color' => $detalle['variante_color'] ?? null,
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario']
                ];
            }
        }

        // Obtener dirección del pedido como array
        // Obtener dirección del pedido como array
        $direccion_pedido = [];
        try {
            $pedido_direccion = $this->pedidoDireccionModel->obtenerPorPedido($id);

            echo "<!-- DEBUG: Resultado obtenerPorPedido: " . print_r($pedido_direccion, true) . " -->";

            if ($pedido_direccion) {
                echo "<!-- DEBUG: Dirección encontrada, datos: " . print_r($pedido_direccion, true) . " -->";

                // Si hay dirección guardada en la tabla direcciones
                if (!empty($pedido_direccion['direccion'])) {
                    $direccion_pedido = [
                        'nombre_completo' => $pedido_direccion['nombre_direccion'] ?? $_SESSION['usuario']['nombre'],
                        'direccion' => $pedido_direccion['direccion'],
                        'distrito' => $pedido_direccion['distrito'] ?? '',
                        'provincia' => $pedido_direccion['provincia'] ?? '',
                        'departamento' => $pedido_direccion['departamento'] ?? '',
                        'referencia' => $pedido_direccion['referencia'] ?? '',
                        'telefono' => $pedido_direccion['telefono_contacto'] ?? ''
                    ];
                }
                // Si hay dirección temporal
                else if (!empty($pedido_direccion['direccion_temporal'])) {
                    $direccion_pedido = [
                        'nombre_completo' => $_SESSION['usuario']['nombre'],
                        'direccion' => $pedido_direccion['direccion_temporal'],
                        'telefono' => $pedido_direccion['telefono_contacto'] ?? ''
                    ];
                } else {
                    echo "<!-- DEBUG: Ni dirección ni dirección_temporal tienen datos -->";
                }
            } else {
                echo "<!-- DEBUG: No se encontró registro en pedido_direcciones para pedido_id: " . $id . " -->";
            }
        } catch (\Exception $e) {
            error_log("Error obteniendo dirección del pedido: " . $e->getMessage());
            echo "<!-- DEBUG: Exception: " . $e->getMessage() . " -->";
        }

        // DEBUG TEMPORAL - Ver en el código fuente
        echo "<!-- DEBUG: Pedido ID: " . $pedido['id'] . " -->";
        echo "<!-- DEBUG: Cantidad productos: " . count($productos_pedido) . " -->";
        echo "<!-- DEBUG: Productos: " . print_r($productos_pedido, true) . " -->";
        echo "<!-- DEBUG: Dirección pedido: " . print_r($pedido_direccion, true) . " -->";
        echo "<!-- DEBUG: Dirección final: " . print_r($direccion_pedido, true) . " -->";

        // Obtener detalles del usuario
        $usuario_detalles = [];
        try {
            $conexion = \Core\Database::getConexion();
            $stmt = $conexion->prepare("SELECT * FROM usuario_detalles WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $usuario_detalles = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            $usuario_detalles = [];
        }

        // Pasar datos a la vista
        require __DIR__ . '/../views/pedido/confirmacion.php';
    }

    // Guarda la observación del administrador
    public function guardarObservacion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $observacion = trim($_POST['observacion'] ?? '');
            if ($id) {
                $this->pedidoModel->actualizarObservacionesAdmin($id, $observacion);
            }
            header('Location: ' . url('pedido/listar'));
            exit;
        }
    }
    // En PedidoController.php o en un nuevo DireccionController.php
    public function eliminarDireccion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $direccionId = $_POST['id'] ?? null;

            if (!$direccionId) {
                echo json_encode(['success' => false, 'message' => 'ID de dirección no proporcionado']);
                exit;
            }

            try {
                $conexion = \Core\Database::getConexion();

                // Verificar que la dirección pertenece al usuario
                $usuario = $_SESSION['usuario'];
                $stmt = $conexion->prepare("SELECT usuario_id FROM direcciones WHERE id = ?");
                $stmt->execute([$direccionId]);
                $direccion = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$direccion || $direccion['usuario_id'] != $usuario['id']) {
                    echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar esta dirección']);
                    exit;
                }

                // Eliminar la dirección
                $stmt = $conexion->prepare("UPDATE direcciones SET activa = 0 WHERE id = ?");
                $stmt->execute([$direccionId]);

                echo json_encode(['success' => true, 'message' => 'Dirección eliminada correctamente']);
            } catch (Exception $e) {
                error_log("Error al eliminar dirección: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error al eliminar la dirección']);
            }
        }
        exit;
    }
}
