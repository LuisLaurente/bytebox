<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header('Location: ' . url('pedido/precheckout'));
    exit;
}

use Core\Helpers\PromocionHelper;
use Models\Cupon;

$envPath = __DIR__ . '/../.env';

$errores = [];
if (isset($_SESSION['errores_checkout']) && is_array($_SESSION['errores_checkout'])) {
    $errores = $_SESSION['errores_checkout'];
}
unset($_SESSION['errores_checkout']);

$usuario = $_SESSION['usuario'];

$direcciones = [];
try {
    $conexion = \Core\Database::getConexion();
    $stmt = $conexion->prepare("SELECT * FROM direcciones WHERE usuario_id = ? AND activa = 1 ORDER BY es_principal DESC, created_at DESC");
    $stmt->execute([$usuario['id']]);
    $direcciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $direcciones = [];
}

$usuario_detalles = [];
try {
    $conexion = \Core\Database::getConexion();
    $stmt = $conexion->prepare("SELECT * FROM usuario_detalles WHERE usuario_id = ?");
    $stmt->execute([$usuario['id']]);
    $usuario_detalles = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    $usuario_detalles = [
        'telefono' => $usuario['telefono'] ?? ''
    ];
}

$productosDetallados = [];
$carrito = $_SESSION['carrito'] ?? [];

if (!empty($carrito)) {
    $productoModel = new \Models\Producto();
    foreach ($carrito as $clave => $item) {
        $producto = $productoModel->obtenerPorId($item['producto_id']);
        if ($producto) {
            $producto = $productoModel->prepararProductoParaVista($producto);
            $producto['nombre'] = $producto['nombre'];
            $producto['cantidad'] = $item['cantidad'];
            $producto['talla'] = $item['talla'];
            $producto['color'] = $item['color'];
            $producto['clave'] = $clave;
            $producto['precio'] = $item['precio'];
            $producto['subtotal'] = $producto['precio'] * $item['cantidad'];
            $productosDetallados[] = $producto;
        }
    }
}

$carritoParaPromociones = [];
foreach ($carrito as $item) {
    $producto = $productoModel->obtenerPorId($item['producto_id']);
    if ($producto) {
        $carritoParaPromociones[] = [
            'id' => $producto['id'],
            'nombre' => $producto['nombre'],
            'precio' => (float)$item['precio'],
            'cantidad' => (int)$item['cantidad'],
            'categoria_id' => $producto['categoria_id'] ?? null,
            'precio_final' => 0,
            'descuento_aplicado' => 0,
            'promociones' => []
        ];
    }
}

$resultado = PromocionHelper::aplicarPromociones($carritoParaPromociones, $usuario);
$totales = [
    'subtotal' => $resultado['subtotal'],
    'descuento' => $resultado['descuento'],
    'total' => $resultado['total'],
    'envio_gratis' => $resultado['envio_gratis']
];

$cupon_aplicado = $_SESSION['cupon_aplicado'] ?? null;
$descuento_cupon = 0;
if ($cupon_aplicado) {
    if ($cupon_aplicado['tipo'] === 'descuento_porcentaje') {
        $descuento_cupon = $totales['subtotal'] * ($cupon_aplicado['valor'] / 100);
    } elseif ($cupon_aplicado['tipo'] === 'descuento_fijo') {
        $descuento_cupon = min($cupon_aplicado['valor'], $totales['subtotal']);
    }
}

$costo_envio_inicial = $totales['envio_gratis'] ? 0 : 8;
$total_final = max(0, $totales['total'] - $descuento_cupon + $costo_envio_inicial);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Bytebox</title>

    <link rel="icon" href="<?= url('image/faviconT.ico') ?>" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('image/faviconT.png') ?>">

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= url('css/checkout.css') ?>">
    <style>
        .hidden {
            display: none !important;
        }
    </style>
</head>

<script>
    window.mpBrickInstance = null;
    window.mpInitializing = false;

    window.mpPreferenciaCache = null;
    window.mpFormularioHash = null;


    /**
     * Genera un hash único basado en los datos del formulario y carrito
     * para detectar si el usuario cambió algo importante
     */
    function generarHashFormulario() {
        const datos = obtenerDatosFormularioActuales();
        const carrito = <?= json_encode($_SESSION['carrito'] ?? []) ?>;
        const total = obtenerTotalActual();

        // Crear string único con todos los datos relevantes
        const datosCompletos = JSON.stringify({
            datos: datos,
            carrito: carrito,
            total: total.toFixed(2)
        });

        // Generar hash simple
        let hash = 0;
        for (let i = 0; i < datosCompletos.length; i++) {
            const char = datosCompletos.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return hash.toString();
    }


    document.getElementById("checkoutForm").addEventListener("submit", function(event) {
        let camposObligatorios = [
            "input-nombre",
            "input-telefono",
            "departamento",
            "provincia",
            "distrito",
            "input-direccion",
            "facturacion_tipo_documento",
            "facturacion_numero_documento",
            "facturacion_email",
            "facturacion_nombre",
            "facturacion_direccion",
            "terminos"
        ];

        let incompletos = [];

        camposObligatorios.forEach(id => {
            let campo = document.getElementById(id);
            if (campo && (campo.value.trim() === "" || (campo.type === "checkbox" && !campo.checked))) {
                incompletos.push(id);
                campo.style.border = "2px solid red";
            } else if (campo) {
                campo.style.border = "";
            }
        });

        if (incompletos.length > 0) {
            event.preventDefault();
            alert("Por favor, completa todos los campos obligatorios antes de continuar con la compra.");
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ========== 1. VALIDACIÓN DEL CHECKOUT ==========
        const form = document.getElementById('checkoutForm');
        const mercadoPagoContainer = document.getElementById('mercado-pago-container');
        const confirmBtn = document.getElementById('confirm-order-btn');
        const terminos = document.getElementById('terminos');
        const direccionIdInput = document.getElementById('direccion_id_seleccionada');

        if (!form || !mercadoPagoContainer || !confirmBtn) {
            console.warn('checkout-validation: elementos no encontrados');
            return;
        }

        // Helper: está visible un elemento
        function isVisible(el) {
            if (!el) return false;
            if (el.classList && el.classList.contains('hidden')) return false;
            const style = window.getComputedStyle(el);
            if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
            return el.offsetParent !== null;
        }

        // Validar grupo facturación
        function validarFacturacion() {
            const factSection = document.getElementById('facturacion-section');
            if (!isVisible(factSection)) return false;
            const campos = factSection.querySelectorAll('input[required], select[required], textarea[required]');
            for (const campo of campos) {
                if (!isVisible(campo)) continue;
                if (campo.type === 'checkbox') {
                    if (!campo.checked) return false;
                } else {
                    if (!campo.value || !campo.value.toString().trim()) return false;
                }
            }
            return true;
        }

        // Validar envío
        function validarEnvio() {
            if (direccionIdInput && direccionIdInput.value && direccionIdInput.value.trim() !== '') {
                const telefono = form.querySelector('#telefono_contacto, #input-telefono');
                if (telefono && telefono.value && telefono.value.toString().trim()) return true;
                return false;
            }

            const newAddressForm = document.getElementById('newAddressForm');
            if (!isVisible(newAddressForm)) return false;

            const campos = newAddressForm.querySelectorAll('input[required], select[required], textarea[required]');
            for (const campo of campos) {
                if (!isVisible(campo)) continue;
                if (campo.type === 'checkbox') {
                    if (!campo.checked) return false;
                } else {
                    if (!campo.value || !campo.value.toString().trim()) return false;
                }
            }
            return true;
        }

        // Validar método de pago
        function validarMetodoPago() {
            const pmContainer = document.getElementById('payment-methods-container');
            if (!pmContainer) return true;
            const seleccionado = pmContainer.querySelector('input[type="radio"]:checked');
            return !!seleccionado || pmContainer.querySelectorAll('input[type="radio"]').length === 0;
        }

        // Validación global
        function validarTodo() {
            const envioOK = validarEnvio();
            const facturaOK = validarFacturacion();
            const terminosOK = terminos ? terminos.checked : false;
            const metodoOK = validarMetodoPago();

            console.groupCollapsed('checkout-validation debug');
            console.log('envioOK:', envioOK);
            console.log('facturaOK:', facturaOK);
            console.log('terminosOK:', terminosOK);
            console.log('metodoOK:', metodoOK);
            console.groupEnd();

            return envioOK && facturaOK && terminosOK && metodoOK;
        }

        // actualizar UI según estado
        function actualizarUI() {
            const envioOK = validarEnvio();
            const facturaOK = validarFacturacion();
            const terminosOK = terminos ? terminos.checked : false;
            const metodoOK = validarMetodoPago();

            const todoValido = envioOK && facturaOK && terminosOK && metodoOK;

            // ✅ Obtener método de pago actual
            const metodoActual = document.getElementById('metodo_pago_seleccionado')?.value || 'contrareembolso';

            console.groupCollapsed('🔍 actualizarUI() - Estado');
            console.log('Envío:', envioOK ? '✅' : '❌');
            console.log('Facturación:', facturaOK ? '✅' : '❌');
            console.log('Términos:', terminosOK ? '✅' : '❌');
            console.log('Método Pago:', metodoOK ? '✅' : '❌');
            console.log('Todo válido:', todoValido ? '✅' : '❌');
            console.log('Método actual:', metodoActual);
            console.groupEnd();

            if (todoValido) {
                // ✅ Habilitar botón de confirmación
                confirmBtn.disabled = false;

                // ✅ SOLO mostrar MercadoPago si el método seleccionado es 'tarjeta'
                if (metodoActual === 'tarjeta') {
                    mercadoPagoContainer.classList.remove('hidden');
                    mercadoPagoContainer.style.display = 'block';
                    console.log('✅ MercadoPago container mostrado');
                } else {
                    // ✅ Para otros métodos (contra entrega), ocultar MercadoPago
                    mercadoPagoContainer.classList.add('hidden');
                    mercadoPagoContainer.style.display = 'none';
                    console.log('✅ MercadoPago container oculto (método: ' + metodoActual + ')');
                }
            } else {
                // ✅ Deshabilitar botón si no está todo completo
                confirmBtn.disabled = true;

                // ✅ Ocultar MercadoPago siempre que no esté todo válido
                mercadoPagoContainer.classList.add('hidden');
                mercadoPagoContainer.style.display = 'none';
                console.log('⚠️ Formulario incompleto - MercadoPago oculto');
            }
        }

        // ========== 2. INICIALIZACIÓN DEL FORMULARIO ==========

        // Event listener para submit del formulario
        checkoutForm.addEventListener('submit', function(e) {
            // Habilitar temporalmente campos deshabilitados antes de enviar
            const camposDeshabilitados = this.querySelectorAll('input:disabled, select:disabled, textarea:disabled');
            camposDeshabilitados.forEach(campo => {
                campo.disabled = false;
            });
        });

        // ========== 3. INICIALIZACIÓN DE LA PÁGINA ==========

        // ✅ CARGAR DEPARTAMENTOS AL INICIAR
        cargarDepartamentos();

        // Abrir primera sección por defecto
        toggleSection('envio-section');

        // Inicializar métodos de pago
        actualizarMetodosPago();

        // Preseleccionar primera dirección si existe
        const firstAddress = document.querySelector('.address-card');
        if (firstAddress) {
            selectAddress(firstAddress);
        }

        // ========== 4. CONFIGURACIÓN DE DIRECCIONES ==========

        const newAddressForm = document.getElementById('newAddressForm');
        const hasDirecciones = <?= !empty($direcciones) ? 'true' : 'false' ?>;

        // ✅ GUARDAR estado inicial de campos required
        const camposFormularioNuevo = newAddressForm.querySelectorAll('input[required], select[required], textarea[required]');
        camposFormularioNuevo.forEach(campo => {
            campo.setAttribute('data-was-required', 'true');
        });

        if (hasDirecciones && newAddressForm && newAddressForm.classList.contains('hidden')) {
            showSavedPhoneMode();
        } else if (!hasDirecciones) {
            showNewAddressMode();
        }

        // ========== 5. EVENT LISTENERS ESPECÍFICOS ==========

        // ✅ LISTENER PARA CAMBIOS DE MÉTODO DE PAGO (AGREGAR ESTO)
        document.addEventListener('change', function(e) {
            if (e.target.name === 'metodo_pago') {
                cambiarMetodoPago(e.target.value);
            }
        });

        // Event listener para provincia
        const provinciaSelect = document.getElementById('provincia');
        if (provinciaSelect) {
            provinciaSelect.addEventListener('change', function() {
                // ✅ LIMPIAR CACHE AL CAMBIAR PROVINCIA
                window.mpPreferenciaCache = null;
                window.mpFormularioHash = null;
                console.log('🗑️ Cache limpiado por cambio de provincia');

                actualizarMetodosPago();
                actualizarCostoEnvio();
            });
        }


        // Configuración de guardar dirección
        const guardarCheckbox = document.getElementById('guardar_direccion');
        const tipoDiv = document.getElementById('tipoDereccion');
        if (guardarCheckbox && tipoDiv) {
            tipoDiv.style.display = guardarCheckbox.checked ? 'grid' : 'none';
            guardarCheckbox.addEventListener('change', function() {
                tipoDiv.style.display = this.checked ? 'grid' : 'none';
            });
        }

        // ========== 6. MODALES Y TÉRMINOS ==========

        // Modal de términos
        const modal = document.getElementById('terms-modal');
        const openBtn = document.getElementById('open-terms-modal');
        const closeBtn = document.querySelector('#close-terms-modal');
        const acceptBtn = document.getElementById('accept-terms-btn');
        const checkbox = document.getElementById('terminos');
        const submitBtn = document.getElementById('confirm-order-btn');

        if (openBtn) {
            openBtn.onclick = function(e) {
                e.preventDefault();
                modal.style.display = 'flex';
            };
        }

        if (closeBtn) {
            closeBtn.onclick = function() {
                modal.style.display = 'none';
            };
        }

        if (acceptBtn && checkbox && submitBtn) {
            acceptBtn.onclick = function() {
                checkbox.checked = true;
                submitBtn.disabled = false;
                modal.style.display = 'none';
            };
        }

        if (checkbox && submitBtn) {
            checkbox.onchange = function() {
                submitBtn.disabled = !this.checked;
            };
        }

        if (modal) {
            modal.onclick = function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            };
        }

        // ========== 7. MODALES DE DIRECCIONES ==========

        document.getElementById('cancel-delete').addEventListener('click', cerrarModalEliminacion);
        document.getElementById('confirm-delete').addEventListener('click', confirmarEliminacion);

        document.getElementById('delete-address-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalEliminacion();
            }
        });

        document.getElementById('cancel-edit').addEventListener('click', cerrarModalEdicion);
        document.getElementById('edit-address-form').addEventListener('submit', guardarCambiosDireccion);

        document.getElementById('edit-address-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalEdicion();
            }
        });

        // Cerrar con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const deleteModal = document.getElementById('delete-address-modal');
                const editModal = document.getElementById('edit-address-modal');

                if (deleteModal.style.display === 'block') {
                    cerrarModalEliminacion();
                }
                if (editModal.style.display === 'block') {
                    cerrarModalEdicion();
                }
            }
        });

        // ========== 8. VALIDACIÓN DE DIRECCIÓN EDITADA ==========

        const direccionEditadaId = <?= $_SESSION['ultima_direccion_editada'] ?? 'null' ?>;
        if (direccionEditadaId) {
            const addressCard = document.querySelector(`.address-card[data-direccion*='"id":${direccionEditadaId}']`);
            if (addressCard) {
                selectAddress(addressCard);
            }
            <?php unset($_SESSION['ultima_direccion_editada']); ?>
        }

        // ========== 9. FUNCIONES GLOBALES ==========

        // Asignar función global para forzar texto de Mercado Pago
        window.forzarTextoBotonMP = forzarTextoBotonMercadoPago;

        // ========== 10. LISTENERS DE VALIDACIÓN ==========

        // listeners: cambios en todo el formulario (delegación)
        form.addEventListener('input', actualizarUI, true);
        form.addEventListener('change', actualizarUI, true);

        // Al cargar asegurar estado correcto
        actualizarUI();

        // Prevención final en submit
        form.addEventListener('submit', function(e) {
            if (!validarTodo()) {
                e.preventDefault();
                alert('Debes completar todos los campos obligatorios (envío, facturación, aceptar términos) antes de proceder al pago.');
                form.reportValidity();
            }
        });
    });
</script>

<body>

    <?php include_once __DIR__ . '/../admin/includes/header.php'; ?>

    <div class="container-principal">
        <h2 class="page-title">Finalizar Compra</h2>

        <?php if (!empty($errores)): ?>
            <div class="checkout-card">
                <div class="error-messages">
                    <h4>Por favor, corrige los siguientes errores:</h4>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div class="main-grid">
            <div class="productos-container">
                <form method="POST" action="<?= url('/pedido/registrar') ?>" id="checkoutForm">
                    <div class="checkout-section">
                        <div class="section-header" onclick="toggleSection('envio-section')">
                            <h3>Datos de Envío</h3>
                            <span class="toggle-icon">▼</span>
                        </div>
                        <div id="envio-section" class="section-content">
                            <?php if (!empty($direcciones)): ?>
                                <div class="addresses-section">
                                    <h4 class="section-subtitle">Selecciona una dirección guardada</h4>
                                    <div class="addresses-grid">
                                        <?php foreach ($direcciones as $index => $direccion): ?>
                                            <div class="address-card" data-direccion='<?= json_encode($direccion) ?>'
                                                onclick="selectAddress(this)">
                                                <div class="radio-button"></div>
                                                <div class="address-actions">
                                                    <button type="button" class="btn-editar-direccion" title="Editar dirección"
                                                        onclick="editarDireccion(event, <?= $direccion['id'] ?>)">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                        </svg>
                                                    </button>
                                                    <button type="button" class="btn-eliminar-direccion" title="Eliminar dirección"
                                                        onclick="eliminarDireccion(event, <?= $direccion['id'] ?>)">
                                                        ×
                                                    </button>
                                                </div>

                                                <div class="address-content">
                                                    <h4><?= htmlspecialchars($direccion['nombre_direccion'] ?: ucfirst($direccion['tipo'])) ?>
                                                        <?php if ($direccion['es_principal']): ?>
                                                            <span class="principal-badge">Principal</span>
                                                        <?php endif; ?>
                                                    </h4>
                                                    <p><?= htmlspecialchars($direccion['direccion']) ?></p>
                                                    <?php if ($direccion['distrito']): ?>
                                                        <p><?= htmlspecialchars($direccion['distrito']) ?><?= $direccion['provincia'] ? ', ' . $direccion['provincia'] : '' ?><?= $direccion['departamento'] ? ', ' . $direccion['departamento'] : '' ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="btn-nueva-direccion" onclick="mostrarFormularioNuevaDireccion()">
                                        + Agregar nueva dirección
                                    </button>
                                </div>
                                <div class="form-group" id="telefono-contacto-container" style="margin-top: 20px;">
                                    <label>Teléfono de contacto *</label>
                                    <input type="tel" name="telefono" id="telefono_contacto" required
                                        value="<?= htmlspecialchars($usuario_detalles['telefono'] ?? '') ?>"
                                        placeholder="999 999 999"
                                        pattern="[0-9\s\+\-\(\)]+"
                                        title="Solo números, espacios y símbolos (+, -, ( )) son permitidos">
                                    <small style="color: #666; font-size: 0.85rem;">Este número se usará para coordinar la entrega</small>
                                </div>
                            <?php endif; ?>
                            <div id="newAddressForm" class="<?= !empty($direcciones) ? 'hidden' : '' ?>">
                                <div class="new-address-form">
                                    <h4 class="section-subtitle">Nueva dirección de envío</h4>

                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Nombre completo *</label>
                                            <input type="text" name="nombre" id="input-nombre" required
                                                value="<?= htmlspecialchars($usuario['nombre']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Celular *</label>
                                            <input type="tel" name="telefono_nuevo" id="input-telefono" required
                                                value="<?= htmlspecialchars($usuario_detalles['telefono'] ?? '') ?>"
                                                placeholder="999 999 999"
                                                pattern="[0-9\s\+\-\(\)]+"
                                                title="Solo números, espacios y símbolos (+, -, ( )) son permitidos"
                                                <?= !empty($direcciones) ? 'disabled' : '' ?>>
                                        </div>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Departamento *</label>
                                            <select name="departamento" id="departamento" onchange="cargarProvincias()">
                                                <option value="">Seleccionar departamento</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Provincia *</label>
                                            <select name="provincia" id="provincia" onchange="actualizarMetodosPago()" disabled>
                                                <option value="" selected disabled>Seleccionar provincia</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Distrito *</label>
                                            <input type="text" name="distrito" id="distrito" placeholder="Ingresa tu distrito">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Dirección completa *</label>
                                        <textarea name="direccion" id="input-direccion" rows="3"
                                            placeholder="Av. Principal 123, Urbanización..."></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Referencia (opcional)</label>
                                        <input type="text" name="referencia" id="input-referencia"
                                            placeholder="Ej: Casa amarilla frente al parque">
                                    </div>
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="guardar_direccion" name="guardar_direccion" value="1" checked>
                                        <label for="guardar_direccion">
                                            <strong>Guardar esta dirección</strong> para futuras compras
                                        </label>
                                    </div>
                                    <div id="tipoDereccion" class="form-grid">
                                        <div class="form-group">
                                            <label>Tipo de dirección</label>
                                            <select name="tipo_direccion">
                                                <option value="casa">Casa</option>
                                                <option value="trabajo">Trabajo</option>
                                                <option value="envio">Solo envío</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Nombre (opcional)</label>
                                            <input type="text" name="nombre_direccion"
                                                placeholder="Ej: Casa de mamá">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="direccion_id_seleccionada" name="direccion_id" value="">
                        </div>
                    </div>
                    <div class="checkout-section">
                        <div class="section-header" onclick="toggleSection('facturacion-section')">
                            <h3>Datos de Facturación</h3>
                            <span class="toggle-icon">▼</span>
                        </div>
                        <div id="facturacion-section" class="section-content">

                            <div class="form-group">
                                <label>Tipo de documento *</label>
                                <select name="facturacion_tipo_documento" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="dni">DNI</option>
                                    <option value="ruc">RUC</option>
                                </select>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Número de documento *</label>
                                    <input type="text" name="facturacion_numero_documento" required
                                        placeholder="Número de DNI o RUC">
                                </div>
                                <div class="form-group">
                                    <label>Correo electrónico *</label>
                                    <input type="email" name="facturacion_email" required
                                        value="<?= htmlspecialchars($usuario['email']) ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Nombre o Razón Social *</label>
                                <input type="text" name="facturacion_nombre" required
                                    value="<?= htmlspecialchars($usuario['nombre']) ?>">
                            </div>

                            <div class="form-group">
                                <label>Dirección Fiscal *</label>
                                <textarea name="facturacion_direccion" required rows="3"
                                    placeholder="Dirección completa para la factura..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="checkout-section">
                        <div class="section-header" onclick="toggleSection('pago-section')">
                            <h3>Método de Pago</h3>
                            <span class="toggle-icon">▼</span>
                        </div>
                        <div id="pago-section" class="section-content">
                            <div class="payment-methods" id="payment-methods-container">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="metodo_pago_seleccionado" id="metodo_pago_seleccionado" value="contrareembolso">
                    <div class="terms-section">
                        <div class="terms-checkbox">
                            <input type="checkbox" id="terminos" name="terminos" required>
                            <div class="terms-text">
                                Acepto los
                                <span class="terms-link" id="open-terms-modal">términos y condiciones</span>
                                y autorizo el procesamiento de mis datos personales para el procesamiento de este pedido. *
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="preferencia_mp_id" id="preferencia_mp_id" value="">

                    <div id="mercado-pago-container" class="hidden" style="margin-top: 20px; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px; background: #fafafa;">
                        <h4 style="margin-bottom: 15px; color: #333;">Pago Seguro con Mercado Pago</h4>
                        <p style="color: #666; margin-bottom: 15px; font-size: 0.9rem;">
                            Paga de forma segura con tarjeta de crédito, débito o efectivo.
                        </p>
                        <div id="wallet_container" style="min-height: 100px;"></div>
                        <div id="mp-status-message" style="margin-top: 15px; text-align: center;"></div>
                    </div>
                    <button type="submit" id="confirm-order-btn" class="btn-finalizar" disabled>
                        Confirmar Pedido - S/ <span id="total-final"><?= number_format($total_final, 2) ?></span>
                    </button>
                </form>
            </div>
            <div class="resumen-container">
                <div class="resumen-header">
                    <h3>Resumen del Pedido</h3>
                </div>
                <div class="resumen-body">
                    <?php if (!empty($productosDetallados)): ?>
                        <div class="productos-resumen">
                            <?php foreach ($productosDetallados as $item): ?>
                                <div class="producto-resumen-item">
                                    <div class="producto-resumen-imagen">
                                        <?php
                                        $imagenUrl = $item['imagenes'][0] ?? 'default-product.jpg';

                                        if (filter_var($imagenUrl, FILTER_VALIDATE_URL)) {
                                            $imagenSrc = $imagenUrl;
                                        } else {
                                            $imagenSrc = url($imagenUrl);
                                        }
                                        ?>
                                        <img src="<?= $imagenSrc ?>"
                                            alt="<?= htmlspecialchars($item['nombre']) ?>"
                                            onerror="this.src='<?= url('image/default-product.jpg') ?>'">
                                    </div>
                                    <div class="producto-resumen-info">
                                        <div class="producto-resumen-nombre"><?= htmlspecialchars($item['nombre']) ?></div>
                                        <div class="producto-resumen-detalles">
                                            <?php if ($item['talla'] || $item['color']): ?>
                                                <?= ($item['talla'] ? 'Talla: ' . htmlspecialchars($item['talla']) : '') ?>
                                                <?= ($item['talla'] && $item['color'] ? ' • ' : '') ?>
                                                <?= ($item['color'] ? 'Color: ' . htmlspecialchars($item['color']) : '') ?>
                                                <br>
                                            <?php endif; ?>
                                            Cantidad: <?= $item['cantidad'] ?>
                                        </div>
                                    </div>
                                    <div class="producto-resumen-precio">
                                        S/ <?= number_format($item['subtotal'], 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="resumen-item">
                            <span class="resumen-label">Subtotal:</span>
                            <span class="resumen-valor">S/ <?= number_format($totales['subtotal'] ?? 0, 2) ?></span>
                        </div>
                        <?php if (!empty($resultado['promociones_aplicadas'])): ?>
                            <div class="resumen-item descuento-promociones">
                                <span class="resumen-label">Descuento:</span>
                                <div class="descuento-detalle">
                                    <?php foreach ($resultado['promociones_aplicadas'] as $promocion): ?>
                                        <?php if (is_numeric($promocion['monto']) && $promocion['monto'] > 0): ?>
                                            <div class="promocion-detalle">
                                                <span class="promocion-nombre"><?= htmlspecialchars($promocion['nombre']) ?></span>
                                                <span class="promocion-descuento">-S/ <?= number_format($promocion['monto'], 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($cupon_aplicado && $descuento_cupon > 0): ?>
                            <div class="resumen-item">
                                <span class="resumen-label">Cupón "<?= htmlspecialchars($cupon_aplicado['codigo']) ?>":</span>
                                <span class="resumen-valor" style="color: var(--success-color);">-S/ <?= number_format($descuento_cupon, 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="resumen-item">
                            <span class="resumen-label">Envío:</span>
                            <span class="resumen-valor" id="costo-envio-display">
                                <?php if ($totales['envio_gratis']): ?>
                                    <?php
                                    $promocion_envio_gratis = null;
                                    foreach ($resultado['promociones_aplicadas'] as $promo) {
                                        if (isset($promo['envio_gratis']) && $promo['envio_gratis'] === true) {
                                            $promocion_envio_gratis = $promo;
                                            break;
                                        }
                                    }
                                    ?>
                                    <span class="gratis-text">
                                        <?= $promocion_envio_gratis ? htmlspecialchars($promocion_envio_gratis['nombre']) : '¡GRATIS!' ?>
                                    </span>
                                    <small class="gratis-desc"></small>
                                <?php else: ?>
                                    S/ <span id="costo-envio-valor"><?= number_format($costo_envio_inicial, 2) ?></span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <!-- Total Final -->
                        <div class="resumen-item total-final">
                            <span class="resumen-label">Total:</span>
                            <span class="resumen-valor">S/ <span id="total-final-display"><?= number_format($total_final, 2) ?></span></span>
                        </div>

                    <?php else: ?>
                        <p style="color: #999; text-align: center; padding: 32px 0;">No hay productos en el carrito</p>
                    <?php endif; ?>

                    <!-- Información adicional -->
                    <div style="margin-top: 24px; padding: 16px; background: var(--gray-light); border-radius: 8px;">
                        <h4 style="margin: 0 0 12px 0; color: var(--dark-color); font-weight: 600;">Compra Segura</h4>
                        <div style="font-size: 0.85rem; color: var(--gray-dark); line-height: 1.6;">
                            <p style="margin: 4px 0;">• Tus datos están protegidos</p>
                            <p style="margin: 4px 0;">• Garantía de satisfacción</p>
                            <p style="margin: 4px 0;">• Seguimiento en tiempo real</p>
                            <p style="margin: 4px 0;">• Envíos a nivel nacional</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="acciones-carrito">
            <a href="<?= url('carrito/ver') ?>" class="boton-volver">Volver al carrito</a>
        </div>
    </div>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script>
        if (typeof MercadoPago === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://sdk.mercadopago.com/js/v2';
            s.onload = () => console.log('✅ SDK MercadoPago cargado correctamente');
            document.head.appendChild(s);
        }
    </script>
    <script>
        let selectedAddressCard = null;
        let isLoadingFromSavedAddress = false;
        const subtotalBase = <?= $totales['subtotal'] ?? 0 ?>;
        const descuentoPromociones = <?= $totales['descuento'] ?? 0 ?>;
        const descuentoCupon = <?= $descuento_cupon ?>;


        const departamentosData = [{
                id: '01',
                nombre: 'Amazonas'
            },
            {
                id: '02',
                nombre: 'Áncash'
            },
            {
                id: '03',
                nombre: 'Apurímac'
            },
            {
                id: '04',
                nombre: 'Arequipa'
            },
            {
                id: '05',
                nombre: 'Ayacucho'
            },
            {
                id: '06',
                nombre: 'Cajamarca'
            },
            {
                id: '07',
                nombre: 'Callao'
            },
            {
                id: '08',
                nombre: 'Cusco'
            },
            {
                id: '09',
                nombre: 'Huancavelica'
            },
            {
                id: '10',
                nombre: 'Huánuco'
            },
            {
                id: '11',
                nombre: 'Ica'
            },
            {
                id: '12',
                nombre: 'Junín'
            },
            {
                id: '13',
                nombre: 'La Libertad'
            },
            {
                id: '14',
                nombre: 'Lambayeque'
            },
            {
                id: '15',
                nombre: 'Lima'
            },
            {
                id: '16',
                nombre: 'Loreto'
            },
            {
                id: '17',
                nombre: 'Madre de Dios'
            },
            {
                id: '18',
                nombre: 'Moquegua'
            },
            {
                id: '19',
                nombre: 'Pasco'
            },
            {
                id: '20',
                nombre: 'Piura'
            },
            {
                id: '21',
                nombre: 'Puno'
            },
            {
                id: '22',
                nombre: 'San Martín'
            },
            {
                id: '23',
                nombre: 'Tacna'
            },
            {
                id: '24',
                nombre: 'Tumbes'
            },
            {
                id: '25',
                nombre: 'Ucayali'
            }
        ];

        const provinciasData = {
            '01': [ // Amazonas
                {
                    id: '0101',
                    nombre: 'Chachapoyas'
                },
                {
                    id: '0102',
                    nombre: 'Bagua'
                },
                {
                    id: '0103',
                    nombre: 'Bongará'
                },
                {
                    id: '0104',
                    nombre: 'Condorcanqui'
                },
                {
                    id: '0105',
                    nombre: 'Luya'
                },
                {
                    id: '0106',
                    nombre: 'Rodríguez de Mendoza'
                },
                {
                    id: '0107',
                    nombre: 'Utcubamba'
                }
            ],
            '02': [ // Áncash
                {
                    id: '0201',
                    nombre: 'Huaraz'
                },
                {
                    id: '0202',
                    nombre: 'Aija'
                },
                {
                    id: '0203',
                    nombre: 'Antonio Raymondi'
                },
                {
                    id: '0204',
                    nombre: 'Asunción'
                },
                {
                    id: '0205',
                    nombre: 'Bolognesi'
                },
                {
                    id: '0206',
                    nombre: 'Carhuaz'
                },
                {
                    id: '0207',
                    nombre: 'Carlos Fermín Fitzcarrald'
                },
                {
                    id: '0208',
                    nombre: 'Casma'
                },
                {
                    id: '0209',
                    nombre: 'Corongo'
                },
                {
                    id: '0210',
                    nombre: 'Huari'
                },
                {
                    id: '0211',
                    nombre: 'Huarmey'
                },
                {
                    id: '0212',
                    nombre: 'Huaylas'
                },
                {
                    id: '0213',
                    nombre: 'Mariscal Luzuriaga'
                },
                {
                    id: '0214',
                    nombre: 'Ocros'
                },
                {
                    id: '0215',
                    nombre: 'Pallasca'
                },
                {
                    id: '0216',
                    nombre: 'Pomabamba'
                },
                {
                    id: '0217',
                    nombre: 'Recuay'
                },
                {
                    id: '0218',
                    nombre: 'Santa'
                },
                {
                    id: '0219',
                    nombre: 'Sihuas'
                },
                {
                    id: '0220',
                    nombre: 'Yungay'
                }
            ],
            '03': [ // Apurímac
                {
                    id: '0301',
                    nombre: 'Abancay'
                },
                {
                    id: '0302',
                    nombre: 'Andahuaylas'
                },
                {
                    id: '0303',
                    nombre: 'Antabamba'
                },
                {
                    id: '0304',
                    nombre: 'Aymaraes'
                },
                {
                    id: '0305',
                    nombre: 'Cotabambas'
                },
                {
                    id: '0306',
                    nombre: 'Chincheros'
                },
                {
                    id: '0307',
                    nombre: 'Grau'
                }
            ],
            '04': [ // Arequipa
                {
                    id: '0401',
                    nombre: 'Arequipa'
                },
                {
                    id: '0402',
                    nombre: 'Camaná'
                },
                {
                    id: '0403',
                    nombre: 'Caravelí'
                },
                {
                    id: '0404',
                    nombre: 'Castilla'
                },
                {
                    id: '0405',
                    nombre: 'Caylloma'
                },
                {
                    id: '0406',
                    nombre: 'Condesuyos'
                },
                {
                    id: '0407',
                    nombre: 'Islay'
                },
                {
                    id: '0408',
                    nombre: 'La Uniòn'
                }
            ],
            '05': [ // Ayacucho
                {
                    id: '0501',
                    nombre: 'Huamanga'
                },
                {
                    id: '0502',
                    nombre: 'Cangallo'
                },
                {
                    id: '0503',
                    nombre: 'Huanca Sancos'
                },
                {
                    id: '0504',
                    nombre: 'Huanta'
                },
                {
                    id: '0505',
                    nombre: 'La Mar'
                },
                {
                    id: '0506',
                    nombre: 'Lucanas'
                },
                {
                    id: '0507',
                    nombre: 'Parinacochas'
                },
                {
                    id: '0508',
                    nombre: 'Pàucar del Sara Sara'
                },
                {
                    id: '0509',
                    nombre: 'Sucre'
                },
                {
                    id: '0510',
                    nombre: 'Víctor Fajardo'
                },
                {
                    id: '0511',
                    nombre: 'Vilcas Huamán'
                }
            ],
            '06': [ // Cajamarca
                {
                    id: '0601',
                    nombre: 'Cajamarca'
                },
                {
                    id: '0602',
                    nombre: 'Cajabamba'
                },
                {
                    id: '0603',
                    nombre: 'Celendín'
                },
                {
                    id: '0604',
                    nombre: 'Chota'
                },
                {
                    id: '0605',
                    nombre: 'Contumazá'
                },
                {
                    id: '0606',
                    nombre: 'Cutervo'
                },
                {
                    id: '0607',
                    nombre: 'Hualgayoc'
                },
                {
                    id: '0608',
                    nombre: 'Jaén'
                },
                {
                    id: '0609',
                    nombre: 'San Ignacio'
                },
                {
                    id: '0610',
                    nombre: 'San Marcos'
                },
                {
                    id: '0611',
                    nombre: 'San Miguel'
                },
                {
                    id: '0612',
                    nombre: 'San Pablo'
                },
                {
                    id: '0613',
                    nombre: 'Santa Cruz'
                }
            ],
            '07': [ // Callao
                {
                    id: '0701',
                    nombre: 'Callao'
                }
            ],
            '08': [ // Cusco
                {
                    id: '0801',
                    nombre: 'Cusco'
                },
                {
                    id: '0802',
                    nombre: 'Acomayo'
                },
                {
                    id: '0803',
                    nombre: 'Anta'
                },
                {
                    id: '0804',
                    nombre: 'Calca'
                },
                {
                    id: '0805',
                    nombre: 'Canas'
                },
                {
                    id: '0806',
                    nombre: 'Canchis'
                },
                {
                    id: '0807',
                    nombre: 'Chumbivilcas'
                },
                {
                    id: '0808',
                    nombre: 'Espinar'
                },
                {
                    id: '0809',
                    nombre: 'La Convención'
                },
                {
                    id: '0810',
                    nombre: 'Paruro'
                },
                {
                    id: '0811',
                    nombre: 'Paucartambo'
                },
                {
                    id: '0812',
                    nombre: 'Quispicanchi'
                },
                {
                    id: '0813',
                    nombre: 'Urubamba'
                }
            ],
            '09': [ // Huancavelica
                {
                    id: '0901',
                    nombre: 'Huancavelica'
                },
                {
                    id: '0902',
                    nombre: 'Acobamba'
                },
                {
                    id: '0903',
                    nombre: 'Angaraes'
                },
                {
                    id: '0904',
                    nombre: 'Castrovirreyna'
                },
                {
                    id: '0905',
                    nombre: 'Churcampa'
                },
                {
                    id: '0906',
                    nombre: 'Huaytará'
                },
                {
                    id: '0907',
                    nombre: 'Tayacaja'
                }
            ],
            '10': [ // Huánuco
                {
                    id: '1001',
                    nombre: 'Huánuco'
                },
                {
                    id: '1002',
                    nombre: 'Ambo'
                },
                {
                    id: '1003',
                    nombre: 'Dos de Mayo'
                },
                {
                    id: '1004',
                    nombre: 'Huacaybamba'
                },
                {
                    id: '1005',
                    nombre: 'Huamalíes'
                },
                {
                    id: '1006',
                    nombre: 'Leoncio Prado'
                },
                {
                    id: '1007',
                    nombre: 'Marañón'
                },
                {
                    id: '1008',
                    nombre: 'Pachitea'
                },
                {
                    id: '1009',
                    nombre: 'Puerto Inca'
                },
                {
                    id: '1010',
                    nombre: 'Lauricocha'
                },
                {
                    id: '1011',
                    nombre: 'Yarowilca'
                }
            ],
            '11': [ // Ica
                {
                    id: '1101',
                    nombre: 'Ica'
                },
                {
                    id: '1102',
                    nombre: 'Chincha'
                },
                {
                    id: '1103',
                    nombre: 'Nazca'
                },
                {
                    id: '1104',
                    nombre: 'Palpa'
                },
                {
                    id: '1105',
                    nombre: 'Pisco'
                }
            ],
            '12': [ // Junín
                {
                    id: '1201',
                    nombre: 'Huancayo'
                },
                {
                    id: '1202',
                    nombre: 'Concepción'
                },
                {
                    id: '1203',
                    nombre: 'Chanchamayo'
                },
                {
                    id: '1204',
                    nombre: 'Jauja'
                },
                {
                    id: '1205',
                    nombre: 'Junín'
                },
                {
                    id: '1206',
                    nombre: 'Satipo'
                },
                {
                    id: '1207',
                    nombre: 'Tarma'
                },
                {
                    id: '1208',
                    nombre: 'Yauli'
                },
                {
                    id: '1209',
                    nombre: 'Chupaca'
                }
            ],
            '13': [ // La Libertad
                {
                    id: '1301',
                    nombre: 'Trujillo'
                },
                {
                    id: '1302',
                    nombre: 'Ascope'
                },
                {
                    id: '1303',
                    nombre: 'Bolívar'
                },
                {
                    id: '1304',
                    nombre: 'Chepén'
                },
                {
                    id: '1305',
                    nombre: 'Julcán'
                },
                {
                    id: '1306',
                    nombre: 'Otuzco'
                },
                {
                    id: '1307',
                    nombre: 'Pacasmayo'
                },
                {
                    id: '1308',
                    nombre: 'Pataz'
                },
                {
                    id: '1309',
                    nombre: 'Sánchez Carrión'
                },
                {
                    id: '1310',
                    nombre: 'Santiago de Chuco'
                },
                {
                    id: '1311',
                    nombre: 'Gran Chimú'
                },
                {
                    id: '1312',
                    nombre: 'Virú'
                }
            ],
            '14': [ // Lambayeque
                {
                    id: '1401',
                    nombre: 'Chiclayo'
                },
                {
                    id: '1402',
                    nombre: 'Ferreñafe'
                },
                {
                    id: '1403',
                    nombre: 'Lambayeque'
                }
            ],
            '15': [ // Lima
                {
                    id: '1501',
                    nombre: 'Lima'
                },
                {
                    id: '1502',
                    nombre: 'Barranca'
                },
                {
                    id: '1503',
                    nombre: 'Cajatambo'
                },
                {
                    id: '1504',
                    nombre: 'Canta'
                },
                {
                    id: '1505',
                    nombre: 'Cañete'
                },
                {
                    id: '1506',
                    nombre: 'Huaral'
                },
                {
                    id: '1507',
                    nombre: 'Huaura'
                },
                {
                    id: '1508',
                    nombre: 'Huarochirí'
                },
                {
                    id: '1509',
                    nombre: 'Oyón'
                },
                {
                    id: '1510',
                    nombre: 'Yauyos'
                }
            ],
            '16': [ // Loreto
                {
                    id: '1601',
                    nombre: 'Maynas'
                },
                {
                    id: '1602',
                    nombre: 'Alto Amazonas'
                },
                {
                    id: '1603',
                    nombre: 'Loreto'
                },
                {
                    id: '1604',
                    nombre: 'Mariscal Ramón Castilla'
                },
                {
                    id: '1605',
                    nombre: 'Requena'
                },
                {
                    id: '1606',
                    nombre: 'Ucayali'
                },
                {
                    id: '1607',
                    nombre: 'Datem del Marañón'
                },
                {
                    id: '1608',
                    nombre: 'Putumayo'
                }
            ],
            '17': [ // Madre de Dios
                {
                    id: '1701',
                    nombre: 'Tambopata'
                },
                {
                    id: '1702',
                    nombre: 'Manu'
                },
                {
                    id: '1703',
                    nombre: 'Tahuamanu'
                }
            ],
            '18': [ // Moquegua
                {
                    id: '1801',
                    nombre: 'Mariscal Nieto'
                },
                {
                    id: '1802',
                    nombre: 'General Sánchez Cerro'
                },
                {
                    id: '1803',
                    nombre: 'Ilo'
                }
            ],
            '19': [ // Pasco
                {
                    id: '1901',
                    nombre: 'Pasco'
                },
                {
                    id: '1902',
                    nombre: 'Daniel Alcides Carrión'
                },
                {
                    id: '1903',
                    nombre: 'Oxapampa'
                }
            ],
            '20': [ // Piura
                {
                    id: '2001',
                    nombre: 'Piura'
                },
                {
                    id: '2002',
                    nombre: 'Ayabaca'
                },
                {
                    id: '2003',
                    nombre: 'Huancabamba'
                },
                {
                    id: '2004',
                    nombre: 'Morropón'
                },
                {
                    id: '2005',
                    nombre: 'Paita'
                },
                {
                    id: '2006',
                    nombre: 'Sullana'
                },
                {
                    id: '2007',
                    nombre: 'Talara'
                },
                {
                    id: '2008',
                    nombre: 'Sechura'
                }
            ],
            '21': [ // Puno
                {
                    id: '2101',
                    nombre: 'Puno'
                },
                {
                    id: '2102',
                    nombre: 'Azángaro'
                },
                {
                    id: '2103',
                    nombre: 'Carabaya'
                },
                {
                    id: '2104',
                    nombre: 'Chucuito'
                },
                {
                    id: '2105',
                    nombre: 'El Collao'
                },
                {
                    id: '2106',
                    nombre: 'Huancané'
                },
                {
                    id: '2107',
                    nombre: 'Lampa'
                },
                {
                    id: '2108',
                    nombre: 'Melgar'
                },
                {
                    id: '2109',
                    nombre: 'Moho'
                },
                {
                    id: '2110',
                    nombre: 'San Antonio de Putina'
                },
                {
                    id: '2111',
                    nombre: 'San Román'
                },
                {
                    id: '2112',
                    nombre: 'Sandia'
                },
                {
                    id: '2113',
                    nombre: 'Yunguyo'
                }
            ],
            '22': [ // San Martín
                {
                    id: '2201',
                    nombre: 'Moyobamba'
                },
                {
                    id: '2202',
                    nombre: 'Bellavista'
                },
                {
                    id: '2203',
                    nombre: 'El Dorado'
                },
                {
                    id: '2204',
                    nombre: 'Huallaga'
                },
                {
                    id: '2205',
                    nombre: 'Lamas'
                },
                {
                    id: '2206',
                    nombre: 'Mariscal Cáceres'
                },
                {
                    id: '2207',
                    nombre: 'Picota'
                },
                {
                    id: '2208',
                    nombre: 'Rioja'
                },
                {
                    id: '2209',
                    nombre: 'San Martín'
                },
                {
                    id: '2210',
                    nombre: 'Tocache'
                }
            ],
            '23': [ // Tacna
                {
                    id: '2301',
                    nombre: 'Tacna'
                },
                {
                    id: '2302',
                    nombre: 'Candarave'
                },
                {
                    id: '2303',
                    nombre: 'Jorge Basadre'
                },
                {
                    id: '2304',
                    nombre: 'Tarata'
                }
            ],
            '24': [ // Tumbes
                {
                    id: '2401',
                    nombre: 'Tumbes'
                },
                {
                    id: '2402',
                    nombre: 'Contralmirante Villar'
                },
                {
                    id: '2403',
                    nombre: 'Zarumilla'
                }
            ],
            '25': [ // Ucayali
                {
                    id: '2501',
                    nombre: 'Coronel Portillo'
                },
                {
                    id: '2502',
                    nombre: 'Atalaya'
                },
                {
                    id: '2503',
                    nombre: 'Padre Abad'
                },
                {
                    id: '2504',
                    nombre: 'Purús'
                }
            ]
        };

        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            const icon = section.previousElementSibling.querySelector('.toggle-icon');

            section.classList.toggle('active');
            icon.textContent = section.classList.contains('active') ? '▲' : '▼';
        }

        function cargarDepartamentos() {
            const departamentoSelect = document.getElementById('departamento');
            departamentosData.forEach(depto => {
                const option = document.createElement('option');
                option.value = depto.id;
                option.textContent = depto.nombre;
                departamentoSelect.appendChild(option);
            });
            setTimeout(() => {
                actualizarMetodosPago();
            }, 100);
        }

        function validarFormularioCompleto() {
            const direccionId = document.getElementById('direccion_id_seleccionada').value;
            const newAddressForm = document.getElementById('newAddressForm');
            const isNewAddressVisible = !newAddressForm.classList.contains('hidden');

            console.log('🔍 validarFormularioCompleto():', {
                direccionId,
                isNewAddressVisible,
                hasDirecciones: <?= !empty($direcciones) ? 'true' : 'false' ?>
            });

            // ✅ CASO 1: Hay dirección seleccionada (guardada) - VÁLIDO
            if (direccionId) {
                console.log('✅ Dirección seleccionada:', direccionId);
                return true;
            }

            // ✅ CASO 2: Formulario de nueva dirección visible
            if (isNewAddressVisible) {
                console.log('📝 Validando formulario de nueva dirección...');

                // ✅ Campos CRÍTICOS para calcular métodos de pago (más permisivos)
                const camposCriticos = [{
                        id: 'departamento',
                        nombre: 'Departamento'
                    },
                    {
                        id: 'provincia',
                        nombre: 'Provincia'
                    }
                ];

                // ✅ Campos COMPLETOS para procesar pago (más estrictos)
                const camposCompletos = [{
                        id: 'departamento',
                        nombre: 'Departamento'
                    },
                    {
                        id: 'provincia',
                        nombre: 'Provincia'
                    },
                    {
                        id: 'distrito',
                        nombre: 'Distrito'
                    },
                    {
                        selector: 'textarea[name="direccion"]',
                        nombre: 'Dirección'
                    },
                    {
                        selector: 'input[name="telefono"]',
                        nombre: 'Teléfono'
                    }
                ];

                // 🔍 Validar campos CRÍTICOS (mínimos para métodos de pago)
                for (let campo of camposCriticos) {
                    let elemento = campo.id ?
                        document.getElementById(campo.id) :
                        document.querySelector(campo.selector);

                    if (elemento && !elemento.value.trim()) {
                        console.log('❌ Campo crítico faltante:', campo.nombre);
                        // NO mostrar alert aquí - permitir cambiar provincia/métodos
                        return false;
                    }
                }

                console.log('✅ Campos críticos completos - métodos de pago disponibles');

                // ✅ Si pasó campos críticos, el formulario es "válido para métodos de pago"
                // La validación COMPLETA se hará al enviar el formulario
                return true;
            }

            // ✅ CASO 3: Hay direcciones guardadas pero ninguna seleccionada
            const hasDirecciones = <?= !empty($direcciones) ? 'true' : 'false' ?>;
            if (hasDirecciones) {
                console.log('ℹ️ Hay direcciones guardadas pero ninguna seleccionada');
                // Mostrar mensaje más específico
                return false;
            }

            // ❌ CASO 4: No hay direcciones y formulario no visible - ERROR REAL
            console.log('❌ No hay direcciones y formulario no visible');
            return false;
        }

        function cargarProvincias() {
            const departamentoSelect = document.getElementById('departamento');
            const provinciaSelect = document.getElementById('provincia');
            const distritoInput = document.getElementById('distrito');

            const departamentoId = departamentoSelect.value;

            // ✅ RESET COMPLETO - siempre empezar deshabilitado y sin required
            provinciaSelect.innerHTML = '<option value="" selected disabled>Seleccionar provincia</option>';
            provinciaSelect.disabled = true;
            provinciaSelect.removeAttribute('required');

            // Reset distrito
            distritoInput.value = '';

            if (departamentoId && provinciasData[departamentoId]) {
                if (provinciasData[departamentoId].length > 0) {
                    // ✅ SOLO habilitar y hacer required si hay provincias
                    provinciaSelect.disabled = false;
                    provinciaSelect.setAttribute('required', 'required');

                    provinciasData[departamentoId].forEach(prov => {
                        const option = document.createElement('option');
                        option.value = prov.id;
                        option.textContent = prov.nombre;
                        provinciaSelect.appendChild(option);
                    });
                }
                // ✅ Si no hay provincias, se mantiene deshabilitado (sin required)
            }

            // Actualizar métodos de pago
            actualizarMetodosPago();

            // ✅ SOLO actualizar costo si NO estamos cargando desde dirección guardada
            if (!isLoadingFromSavedAddress) {
                actualizarCostoEnvio();
            }
        }

        function cargarProvinciasEdicion() {
            const editDepartamentoSelect = document.getElementById('edit-departamento');
            const editProvinciaSelect = document.getElementById('edit-provincia');

            const departamentoId = editDepartamentoSelect.value;

            // Reset provincias
            editProvinciaSelect.innerHTML = '<option value="" selected disabled>Seleccionar provincia</option>';
            editProvinciaSelect.disabled = true;
            editProvinciaSelect.removeAttribute('required');

            if (departamentoId && provinciasData[departamentoId]) {
                if (provinciasData[departamentoId].length > 0) {
                    // Si hay provincias, habilitar el select y hacerlo required
                    editProvinciaSelect.disabled = false;
                    editProvinciaSelect.setAttribute('required', 'required');
                    provinciasData[departamentoId].forEach(prov => {
                        const option = document.createElement('option');
                        option.value = prov.id;
                        option.textContent = prov.nombre;
                        editProvinciaSelect.appendChild(option);
                    });
                } else {
                    // Si no hay provincias, dejar deshabilitado
                    editProvinciaSelect.disabled = true;
                    editProvinciaSelect.removeAttribute('required');
                }
            }
        }

        function validarFormularioCheckout() {
            const direccionId = document.getElementById('direccion_id_seleccionada').value;
            const newAddressForm = document.getElementById('newAddressForm');
            const isNewAddressVisible = !newAddressForm.classList.contains('hidden');

            if (direccionId) {
                return true;
            }

            if (!direccionId && isNewAddressVisible) {
                const departamento = document.getElementById('departamento').value;
                const provincia = document.getElementById('provincia').value;
                const distrito = document.getElementById('distrito').value;
                const direccion = document.querySelector('textarea[name="direccion"]').value;
                const telefono = document.querySelector('input[name="telefono"]').value;

                if (!departamento || !provincia || !distrito || !direccion || !telefono) {
                    if (!departamento) document.getElementById('departamento').focus();
                    else if (!provincia) document.getElementById('provincia').focus();
                    else if (!distrito) document.getElementById('distrito').focus();
                    else if (!direccion) document.querySelector('textarea[name="direccion"]').focus();
                    else if (!telefono) document.querySelector('input[name="telefono"]').focus();

                    return false;
                }

                return true;
            }

            if (!direccionId && !isNewAddressVisible) {
                alert('Por favor, selecciona una dirección o completa el formulario de nueva dirección.');
                return false;
            }

            return true;
        }

        function actualizarMetodosPago() {
            const departamentoSelect = document.getElementById('departamento');
            const provinciaSelect = document.getElementById('provincia');
            const paymentMethodsContainer = document.getElementById('payment-methods-container');

            const departamentoId = departamentoSelect?.value || '';
            const provinciaId = provinciaSelect?.value || '';

            console.log('📍 Departamento:', departamentoId, 'Provincia:', provinciaId);

            // ✅ VERIFICACIÓN CORREGIDA: Lima es departamento 15 Y provincia 1501
            const esLima = departamentoId === '15' && provinciaId === '1501';

            console.log('📍 Es Lima?:', esLima);

            let html = '';

            if (esLima) {
                // ✅ LIMA: Mostrar ambos métodos, contraentrega por defecto
                html = `
                    <div class="payment-option">
                        <input type="radio" id="contrareembolso" name="metodo_pago" value="contrareembolso" checked>
                        <label for="contrareembolso" onclick="cambiarMetodoPago('contrareembolso')">Pago contra entrega</label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="tarjeta" name="metodo_pago" value="tarjeta">
                        <label for="tarjeta" onclick="cambiarMetodoPago('tarjeta')">Pago con tarjeta (Mercado Pago)</label>
                    </div>
                `;
            } else if (departamentoId && provinciaId) {
                // ✅ PROVINCIA: Solo MercadoPago
                html = `
                        <div class="payment-option">
                            <input type="radio" id="tarjeta" name="metodo_pago" value="tarjeta" checked>
                            <label for="tarjeta" onclick="cambiarMetodoPago('tarjeta')">Pago con tarjeta (Mercado Pago)</label>
                        </div>
                    `;
            } else {
                // ✅ SIN UBICACIÓN: Contra entrega por defecto
                html = `
                    <div class="payment-option">
                        <input type="radio" id="contrareembolso" name="metodo_pago" value="contrareembolso" checked>
                        <label for="contrareembolso" onclick="cambiarMetodoPago('contrareembolso')">Pago contra entrega</label>
                    </div>
                    <div class="payment-option">
                        <input type="radio" id="tarjeta" name="metodo_pago" value="tarjeta">
                        <label for="tarjeta" onclick="cambiarMetodoPago('tarjeta')">Pago con tarjeta (Mercado Pago)</label>
                    </div>
                `;
            }

            paymentMethodsContainer.innerHTML = html;

            // ✅ SOLO EJECUTAR cambiarMetodoPago UNA VEZ - ELIMINAR EL TIMEOUT DUPLICADO
            const metodoSeleccionado = document.querySelector('input[name="metodo_pago"]:checked');
            if (metodoSeleccionado) {
                cambiarMetodoPago(metodoSeleccionado.value);
            }
        }

        function cargarProvincias() {
            const departamentoSelect = document.getElementById('departamento');
            const provinciaSelect = document.getElementById('provincia');
            const distritoInput = document.getElementById('distrito');

            const departamentoId = departamentoSelect.value;

            // Reset provincias
            provinciaSelect.innerHTML = '<option value="" selected disabled>Seleccionar provincia</option>';
            provinciaSelect.disabled = true;
            provinciaSelect.removeAttribute('required');

            // Reset distrito
            distritoInput.value = '';

            if (departamentoId && provinciasData[departamentoId]) {
                if (provinciasData[departamentoId].length > 0) {
                    provinciaSelect.disabled = false;
                    provinciaSelect.setAttribute('required', 'required');

                    provinciasData[departamentoId].forEach(prov => {
                        const option = document.createElement('option');
                        option.value = prov.id;
                        option.textContent = prov.nombre;
                        provinciaSelect.appendChild(option);
                    });

                    // ✅ AGREGAR: Actualizar métodos de pago cuando se cargan las provincias
                    setTimeout(() => {
                        actualizarMetodosPago();
                    }, 100);
                }
            }

            // Actualizar métodos de pago
            actualizarMetodosPago();

            if (!isLoadingFromSavedAddress) {
                actualizarCostoEnvio();
            }
        }

        let cambiarMetodoPagoTimeout = null;

        function cambiarMetodoPago(metodo) {
            // ✅ CANCELAR LLAMADAS PREVIAS (DEBOUNCE)
            if (cambiarMetodoPagoTimeout) {
                clearTimeout(cambiarMetodoPagoTimeout);
            }

            // ✅ EJECUCIÓN INMEDIATA para selecciones automáticas del sistema
            const esSeleccionAutomatica = !document.activeElement || document.activeElement.name !== 'metodo_pago';
            if (esSeleccionAutomatica) {
                console.log('🔘 Selección automática de método de pago:', metodo);
                // Ejecutar inmediatamente sin debounce
                ejecutarCambioMetodoPago(metodo);
                return;
            }

            // ✅ EJECUTAR DESPUÉS DE 200ms
            cambiarMetodoPagoTimeout = setTimeout(() => {
                // ✅ PREVENIR MÚLTIPLES EJECUCIONES SIMULTÁNEAS
                if (window.mpInitializing) {
                    console.log('⏳ MercadoPago ya se está inicializando, ignorando llamada duplicada');
                    return;
                }

                const checkoutForm = document.getElementById('checkoutForm');
                const mercadoPagoContainer = document.getElementById('mercado-pago-container');
                const confirmOrderBtn = document.getElementById('confirm-order-btn');
                const metodoPagoSeleccionado = document.getElementById('metodo_pago_seleccionado');
                const walletContainer = document.getElementById('wallet_container');
                const mpStatusMessage = document.getElementById('mp-status-message');

                console.log(`🔘 Cambiando método de pago a: ${metodo}`);

                // ✅ LIMPIAR INSTANCIA DE MERCADO PAGO SI EXISTE
                if (window.mpBrickInstance) {
                    console.log('🧹 Limpiando instancia previa de MercadoPago');
                    window.mpBrickInstance = null;
                }

                // ✅ LIMPIAR COMPLETAMENTE el contenedor de Mercado Pago SIEMPRE
                if (walletContainer) {
                    walletContainer.innerHTML = '';
                }
                if (mpStatusMessage) {
                    mpStatusMessage.innerHTML = '';
                }

                // ✅ Actualizar valor del input hidden
                metodoPagoSeleccionado.value = metodo;

                if (metodo === 'tarjeta') {
                    // ========== MÉTODO TARJETA (MERCADO PAGO) ==========

                    // ✅ MOSTRAR SIEMPRE el contenedor de MercadoPago (aunque esté incompleto)
                    mercadoPagoContainer.classList.remove('hidden');
                    mercadoPagoContainer.style.display = 'block';
                    confirmOrderBtn.style.display = 'none';

                    // ✅ Validar formulario para decidir QUÉ mostrar dentro de MercadoPago
                    if (!validarFormularioCompleto()) {
                        console.warn('⚠️ Formulario incompleto - Mostrando mensaje informativo');

                        // ✅ Mostrar mensaje INFORMATIVO en lugar de error
                        if (walletContainer) {
                            walletContainer.innerHTML = `
                        <div style="color: #856404; text-align: center; padding: 20px; 
                                   background: #fff3cd; border-radius: 8px; border: 1px solid #ffeaa7;
                                   margin: 10px 0;">
                            <strong>ℹ️ Información importante</strong><br>
                            Complete los datos de envío para habilitar el pago con tarjeta
                        </div>
                    `;
                        }

                        // ✅ NO hacer return - Permitir que el flujo continúe
                        // El contenedor ya está visible con el mensaje informativo

                    } else {
                        // ✅ Formulario completo - Inicializar MercadoPago normal
                        console.log('✅ Formulario completo - Inicializando MercadoPago');

                        // ✅ PREVENIR envío del formulario normal (MercadoPago maneja el pago)
                        checkoutForm.onsubmit = function(e) {
                            e.preventDefault();
                            console.log('🛑 Formulario bloqueado - Pago manejado por Mercado Pago');
                            return false;
                        };

                        // ✅ MARCAR COMO INICIALIZANDO ANTES
                        window.mpInitializing = true;

                        // ✅ Inicializar Mercado Pago con delay para estabilidad
                        setTimeout(() => {
                            inicializarMercadoPago();

                            // ✅ DESMARCAR DESPUÉS DE 2 SEGUNDOS
                            setTimeout(() => {
                                window.mpInitializing = false;
                            }, 2000);
                        }, 500);
                    }

                    console.log('✅ Modo MercadoPago activado');

                } else {
                    // ========== MÉTODO CONTRA ENTREGA ==========
                    // (Esta parte se mantiene igual)
                    // ✅ FORZAR ocultación COMPLETA del contenedor de MercadoPago
                    mercadoPagoContainer.classList.add('hidden');
                    mercadoPagoContainer.style.display = 'none';

                    // ✅ Mostrar botón de pago normal
                    confirmOrderBtn.style.display = 'block';

                    // ✅ RESTAURAR envío normal del formulario
                    checkoutForm.onsubmit = null;

                    // ✅ Actualizar texto del botón con el total actual
                    const totalActual = obtenerTotalActual();
                    confirmOrderBtn.innerHTML = `Confirmar Pedido - S/ <span id="total-final">${totalActual.toFixed(2)}</span>`;

                    // ✅ FORZAR ACTUALIZACIÓN DEL ESTADO DEL BOTÓN
                    setTimeout(() => {
                        actualizarUI();
                        // ✅ SI EL FORMULARIO ESTÁ COMPLETO, HABILITAR EL BOTÓN MANUALMENTE
                        if (validarTodo()) {
                            confirmOrderBtn.disabled = false;
                            console.log('✅ Botón habilitado para contraentrega');
                        } else {
                            console.log('❌ Formulario incompleto, botón deshabilitado');
                        }
                    }, 100);

                    console.log('✅ Modo Contra Entrega activado');
                }

                ejecutarCambioMetodoPago(metodo);
                console.log(`✅ Método de pago cambiado exitosamente a: ${metodo}`);
            }, 200); // ✅ DEBOUNCE DE 200ms
        }

        function ejecutarCambioMetodoPago(metodo) {
            // ✅ PREVENIR MÚLTIPLES EJECUCIONES SIMULTÁNEAS
            if (window.mpInitializing) {
                console.log('⏳ MercadoPago ya se está inicializando, ignorando llamada duplicada');
                return;
            }

            const checkoutForm = document.getElementById('checkoutForm');
            const mercadoPagoContainer = document.getElementById('mercado-pago-container');
            const confirmOrderBtn = document.getElementById('confirm-order-btn');
            const metodoPagoSeleccionado = document.getElementById('metodo_pago_seleccionado');
            const walletContainer = document.getElementById('wallet_container');
            const mpStatusMessage = document.getElementById('mp-status-message');

            console.log(`🔘 Ejecutando cambio de método de pago a: ${metodo}`);

            // ✅ LIMPIAR INSTANCIA DE MERCADO PAGO SI EXISTE
            if (window.mpBrickInstance) {
                console.log('🧹 Limpiando instancia previa de MercadoPago');
                window.mpBrickInstance = null;
            }

            // ✅ LIMPIAR COMPLETAMENTE el contenedor de Mercado Pago SIEMPRE
            if (walletContainer) {
                walletContainer.innerHTML = '';
            }
            if (mpStatusMessage) {
                mpStatusMessage.innerHTML = '';
            }

            // ✅ Actualizar valor del input hidden
            metodoPagoSeleccionado.value = metodo;

            if (metodo === 'tarjeta') {
                // ========== MÉTODO TARJETA (MERCADO PAGO) ==========

                // ✅ MOSTRAR SIEMPRE el contenedor de MercadoPago (aunque esté incompleto)
                mercadoPagoContainer.classList.remove('hidden');
                mercadoPagoContainer.style.display = 'block';
                confirmOrderBtn.style.display = 'none';

                // ✅ Validar formulario para decidir QUÉ mostrar dentro de MercadoPago
                if (!validarFormularioCompleto()) {
                    console.warn('⚠️ Formulario incompleto - Mostrando mensaje informativo');

                    // ✅ Mostrar mensaje INFORMATIVO en lugar de error
                    if (walletContainer) {
                        walletContainer.innerHTML = `
                            <div style="color: #856404; text-align: center; padding: 20px; 
                                       background: #fff3cd; border-radius: 8px; border: 1px solid #ffeaa7;
                                       margin: 10px 0;">
                                <strong>ℹ️ Información importante</strong><br>
                                Complete los datos de envío para habilitar el pago con tarjeta
                            </div>
                        `;
                    }

                } else {
                    // ✅ Formulario completo - Inicializar MercadoPago normal
                    console.log('✅ Formulario completo - Inicializando MercadoPago');

                    // ✅ PREVENIR envío del formulario normal (MercadoPago maneja el pago)
                    checkoutForm.onsubmit = function(e) {
                        e.preventDefault();
                        console.log('🛑 Formulario bloqueado - Pago manejado por Mercado Pago');
                        return false;
                    };

                    // ✅ MARCAR COMO INICIALIZANDO ANTES
                    window.mpInitializing = true;

                    // ✅ Inicializar Mercado Pago con delay para estabilidad
                    setTimeout(() => {
                        inicializarMercadoPago();

                        // ✅ DESMARCAR DESPUÉS DE 2 SEGUNDOS
                        setTimeout(() => {
                            window.mpInitializing = false;
                        }, 2000);
                    }, 500);
                }

                console.log('✅ Modo MercadoPago activado');

            } else {
                // ========== MÉTODO CONTRA ENTREGA ==========
                // (Esta parte se mantiene igual que antes)
                // ✅ FORZAR ocultación COMPLETA del contenedor de MercadoPago
                mercadoPagoContainer.classList.add('hidden');
                mercadoPagoContainer.style.display = 'none';

                // ✅ Mostrar botón de pago normal
                confirmOrderBtn.style.display = 'block';

                // ✅ RESTAURAR envío normal del formulario
                checkoutForm.onsubmit = null;

                // ✅ Actualizar texto del botón con el total actual
                const totalActual = obtenerTotalActual();
                confirmOrderBtn.innerHTML = `Confirmar Pedido - S/ <span id="total-final">${totalActual.toFixed(2)}</span>`;

                // ✅ FORZAR ACTUALIZACIÓN DEL ESTADO DEL BOTÓN
                setTimeout(() => {
                    actualizarUI();
                    // ✅ SI EL FORMULARIO ESTÁ COMPLETO, HABILITAR EL BOTÓN MANUALMENTE
                    if (validarTodo()) {
                        confirmOrderBtn.disabled = false;
                        console.log('✅ Botón habilitado para contraentrega');
                    } else {
                        console.log('❌ Formulario incompleto, botón deshabilitado');
                    }
                }, 100);

                console.log('✅ Modo Contra Entrega activado');
            }

            console.log(`✅ Método de pago cambiado exitosamente a: ${metodo}`);
        }

        function actualizarTextoBotonMercadoPago() {
            const total = obtenerTotalActual();
        }

        function obtenerTotalActual() {
            const totalSpan = document.getElementById('total-final-display');
            return totalSpan ? parseFloat(totalSpan.textContent) : <?= $total_final ?? 0 ?>;
        }

        let mpInstance = null;
        let mpScriptCargado = false;


        let mpBrickInstance = null; // ✅ VARIABLE GLOBAL para controlar una sola instancia

        function inicializarMercadoPago() {
            const metodoActual = document.getElementById('metodo_pago_seleccionado').value;

            // ✅ VERIFICAR SI ES MÉTODO TARJETA
            if (metodoActual !== 'tarjeta') {
                console.log('⚠️ Mercado Pago no inicializado - método actual:', metodoActual);

                // ✅ LIMPIAR CACHE AL SALIR DE TARJETA
                window.mpPreferenciaCache = null;
                window.mpFormularioHash = null;

                if (mpBrickInstance) {
                    console.log('🧹 Limpiando instancia previa de MercadoPago');
                    mpBrickInstance = null;
                }
                return;
            }

            // ✅ VALIDAR FORMULARIO COMPLETO
            if (!validarFormularioCompleto()) {
                alert('Por favor, completa todos los datos de envío y facturación antes de proceder al pago.');
                return;
            }

            const walletContainer = document.getElementById('wallet_container');
            const statusMessage = document.getElementById('mp-status-message');

            // ✅ GENERAR HASH DEL FORMULARIO ACTUAL
            const hashActual = generarHashFormulario();
            console.log('🔑 Hash formulario actual:', hashActual);
            console.log('🔑 Hash formulario cache:', window.mpFormularioHash);

            // ✅ VERIFICAR SI PODEMOS REUTILIZAR LA PREFERENCIA CACHEADA
            if (window.mpPreferenciaCache && window.mpFormularioHash === hashActual) {
                console.log('♻️ Reutilizando preferencia cacheada:', window.mpPreferenciaCache.preference_id);

                // ✅ CARGAR SDK Y CREAR BOTÓN CON PREFERENCIA CACHEADA (SIN LLAMAR AL BACKEND)
                cargarSdkYCrearBoton(window.mpPreferenciaCache);
                return; // ✅ SALIR SIN HACER PETICIÓN AL BACKEND
            }

            // ✅ SI NO HAY CACHE O CAMBIÓ EL FORMULARIO, CREAR NUEVA PREFERENCIA
            console.log('🆕 Creando nueva preferencia - formulario cambió o no hay cache');

            // ✅ LIMPIAR CONTENEDORES COMPLETAMENTE
            if (walletContainer) {
                walletContainer.innerHTML = '<div style="text-align:center;padding:20px;color:#666;">🔄 Preparando pago seguro...</div>';
            }
            if (statusMessage) {
                statusMessage.innerHTML = '';
            }

            // ✅ OBTENER TOTAL
            const totalActual = obtenerTotalActual();
            if (totalActual <= 0) {
                if (walletContainer) {
                    walletContainer.innerHTML = '<div style="color:#dc3545;text-align:center;padding:20px;">❌ Error: El total debe ser mayor a 0</div>';
                }
                return;
            }

            // ✅ OBTENER DATOS DEL FORMULARIO
            const datosFormulario = obtenerDatosFormularioActuales();
            console.log("📋 Datos del formulario a enviar:", datosFormulario);

            const payloadData = {
                datos_checkout: {
                    carrito: <?= json_encode($_SESSION['carrito'] ?? []) ?>,
                    usuario_id: <?= json_encode($_SESSION['usuario']['id'] ?? null) ?>,
                    usuario: <?= json_encode($_SESSION['usuario'] ?? []) ?>,
                    total_actual: totalActual,
                    datos_formulario: datosFormulario
                }
            };

            console.log("📤 Enviando datos a backend...");

            // ✅ PETICIÓN AL BACKEND
            fetch('<?= url("/pago/crear-pago-mercado-pago") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payloadData)
                })
                .then(res => {
                    console.log('📨 Respuesta recibida - Status:', res.status);
                    if (!res.ok) {
                        throw new Error(`Error HTTP ${res.status}: ${res.statusText}`);
                    }
                    return res.json();
                })
                .then(data => {
                    console.log('📦 Datos recibidos:', data);

                    if (!data.success) {
                        throw new Error(data.error || 'Error del servidor');
                    }

                    if (!data.preference_id) {
                        throw new Error('No se recibió preference_id');
                    }

                    console.log('✅ Preference ID recibido:', data.preference_id);

                    // ✅ GUARDAR EN CACHE
                    window.mpPreferenciaCache = data;
                    window.mpFormularioHash = hashActual;
                    console.log('💾 Preferencia guardada en cache');

                    // ✅ CARGAR SDK Y CREAR BOTÓN
                    cargarSdkYCrearBoton(data);

                })
                .catch(err => {
                    console.error('❌ Error en comunicación:', err);

                    window.mpInitializing = false;

                    if (walletContainer) {
                        walletContainer.innerHTML = `
                    <div style="color:#dc3545;text-align:center;padding:20px;">
                        ❌ ${err.message || 'Error al procesar el pago'}
                        <br>
                        <button onclick="inicializarMercadoPago()" style="
                            background: #007bff; 
                            color: white; 
                            border: none; 
                            padding: 8px 16px; 
                            border-radius: 5px; 
                            cursor: pointer;
                            margin-top: 10px;
                        ">Reintentar</button>
                    </div>
                `;
                    }
                });
        }


        // ✅ FUNCIÓN MEJORADA PARA CARGAR SDK Y CREAR BOTÓN
        function cargarSdkYCrearBoton(data) {
            const walletContainer = document.getElementById('wallet_container');
            const statusMessage = document.getElementById('mp-status-message');

            if (!walletContainer) {
                console.error('❌ No se encontró el contenedor de MercadoPago');
                return;
            }

            // ✅ LIMPIAR CONTENEDOR COMPLETAMENTE
            walletContainer.innerHTML = '<div style="text-align:center;padding:20px;color:#666;">🔄 Configurando pago...</div>';

            // ✅ CARGAR SDK SI NO ESTÁ CARGADO
            if (typeof MercadoPago === 'undefined') {
                console.log('📥 Cargando SDK MercadoPago...');
                const script = document.createElement('script');
                script.src = 'https://sdk.mercadopago.com/js/v2';
                script.onload = () => {
                    console.log('✅ SDK MercadoPago cargado');
                    crearBotonMercadoPago(data);
                };
                script.onerror = () => {
                    console.error('❌ Error cargando SDK MercadoPago');
                    walletContainer.innerHTML = '<div style="color:#dc3545;text-align:center;padding:20px;">❌ No se pudo cargar el SDK</div>';
                };
                document.head.appendChild(script);
            } else {
                console.log('✅ SDK MercadoPago ya está cargado');
                crearBotonMercadoPago(data);
            }
        }



        function manejarModalMercadoPago() {
            const mpContainer = document.getElementById('mercado-pago-container');

            document.addEventListener('mercadopago-brick-on-render', (event) => {
                console.log('✅ Mercado Pago brick renderizado');
            });

            document.addEventListener('mercadopago-brick-on-error', (event) => {
                console.error('❌ Error en brick MP:', event.detail);
            });
        }

        // ✅ FUNCIÓN MEJORADA PARA CREAR BOTÓN
        function crearBotonMercadoPago(data) {
            const walletContainer = document.getElementById('wallet_container');
            const statusMessage = document.getElementById('mp-status-message');

            if (!walletContainer) {
                console.error('❌ No se encontró wallet_container');
                return;
            }

            // ✅ LIMPIAR CONTENEDOR ANTES DE CREAR NUEVO BOTÓN
            walletContainer.innerHTML = '';

            try {
                console.log('🎯 Inicializando MercadoPago...');

                const publicKey = '<?= $_ENV["MERCADOPAGO_PUBLIC_KEY"] ?? getenv("MERCADOPAGO_PUBLIC_KEY") ?>';
                console.log('🔑 Public Key:', publicKey);

                // ✅ CREAR NUEVA INSTANCIA DE MP
                const mp = new MercadoPago(publicKey, {
                    locale: 'es-PE'
                });

                console.log('🎨 Creando botón con preference:', data.preference_id);

                // ✅ LIMPIAR INSTANCIA PREVIA SI EXISTE
                if (mpBrickInstance) {
                    console.log('🧹 Limpiando instancia previa de brick');
                    // No hay método directo para limpiar, pero recreamos el contenedor
                }

                // ✅ CREAR BOTÓN DE PAGO
                mp.bricks().create("wallet", "wallet_container", {
                    initialization: {
                        preferenceId: data.preference_id
                    },
                    customization: {
                        texts: {
                            action: "pay",
                            valueProp: "security"
                        }
                    }
                }).then((brickController) => {
                    console.log('✅ Botón MercadoPago renderizado exitosamente');
                    mpBrickInstance = brickController; // ✅ GUARDAR INSTANCIA
                }).catch(error => {
                    console.error('❌ Error creando botón MP:', error);
                    walletContainer.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <div style="color: #dc3545; margin-bottom: 10px;">❌ Error al cargar el botón de pago</div>
                    <button onclick="inicializarMercadoPago()" style="
                        background: #007bff; 
                        color: white; 
                        border: none; 
                        padding: 10px 20px; 
                        border-radius: 5px; 
                        cursor: pointer;
                    ">Reintentar</button>
                </div>
            `;
                });

            } catch (error) {
                console.error('❌ Error general:', error);
                walletContainer.innerHTML = `
            <div style="color:#dc3545;text-align:center;padding:20px;">
                ❌ Error: ${error.message}
                <br>
                <button onclick="inicializarMercadoPago()" style="
                    background: #007bff; 
                    color: white; 
                    border: none; 
                    padding: 8px 16px; 
                    border-radius: 5px; 
                    cursor: pointer;
                    margin-top: 10px;
                ">Reintentar</button>
            </div>
        `;
            }
        }

        function forzarTextoBotonMercadoPago() {
            const interval = setInterval(() => {
                const mpButtons = document.querySelectorAll('[id*="mercadopago"], [class*="mercadopago"], button');
                mpButtons.forEach(button => {
                    const buttonText = button.textContent || button.innerText;
                    if (buttonText.includes('Mercado Pago') && !buttonText.includes('Pagar con')) {
                        button.textContent = 'Pagar con Mercado Pago';
                        button.style.fontWeight = '600';
                        console.log('✅ Texto del botón actualizado');
                    }
                });
            }, 500);

            setTimeout(() => {
                clearInterval(interval);
            }, 5000);
        }

        function actualizarDisplayEnvio(costoEnvio) {
            const costoEnvioDisplay = document.getElementById('costo-envio-display');

            if (!costoEnvioDisplay) {
                return;
            }

            if (costoEnvio === 0) {
                costoEnvioDisplay.innerHTML = `
                    <span style="color: var(--success-color); font-weight: 600;">¡GRATIS!</span>
                    <small style="display: block; color: #666; font-size: 0.8rem;">
                        (Promoción aplicada)
                    </small>
                `;
            } else {
                costoEnvioDisplay.innerHTML = `
                    S/ <span id="costo-envio-valor">${costoEnvio.toFixed(2)}</span>
                `;
            }
            costoEnvioDisplay.style.transform = 'scale(1.05)';
            costoEnvioDisplay.style.transition = 'transform 0.2s ease';
            setTimeout(() => {
                costoEnvioDisplay.style.transform = 'scale(1)';
            }, 200);
        }

        function actualizarCostoEnvio() {
            if (isLoadingFromSavedAddress) {
                return;
            }

            const departamentoSelect = document.getElementById('departamento');

            if (!departamentoSelect) return;

            const departamentoId = departamentoSelect.value;
            let costoEnvio = 0;
            const envioGratis = <?= $totales['envio_gratis'] ? 'true' : 'false' ?>;

            if (envioGratis) {
                costoEnvio = 0;
            } else {
                if (departamentoId === '15') {
                    costoEnvio = 8;
                } else if (departamentoId && departamentoId !== '') {
                    costoEnvio = 12;
                } else {
                    costoEnvio = 8;
                }
            }

            const costoEnvioDisplay = document.getElementById('costo-envio-display');

            if (costoEnvioDisplay) {
                if (costoEnvio === 0) {
                    costoEnvioDisplay.innerHTML = `
                        <span style="color: var(--success-color); font-weight: 600;">¡GRATIS!</span>
                        <small style="display: block; color: #666; font-size: 0.8rem;">
                            (Promoción aplicada)
                        </small>
                    `;
                } else {
                    costoEnvioDisplay.innerHTML = `
                        S/ <span id="costo-envio-valor">${costoEnvio.toFixed(2)}</span>
                    `;
                }
            }
            actualizarDisplayEnvio(costoEnvio);
            recalcularTotalFinal(costoEnvio);
        }

        function recalcularTotalFinal(costoEnvio) {
            const subtotal = <?= $totales['subtotal'] ?? 0 ?>;
            const descuento = <?= $totales['descuento'] ?? 0 ?>;
            const descuentoCupon = <?= $descuento_cupon ?? 0 ?>;

            let total = subtotal - descuento - descuentoCupon + costoEnvio;
            total = Math.max(total, 0);
            const totalSpan = document.getElementById('total-final-display');

            if (totalSpan) {
                totalSpan.textContent = total.toFixed(2);
                totalSpan.style.transform = 'scale(1.1)';
                totalSpan.style.transition = 'transform 0.2s ease';
                setTimeout(() => {
                    totalSpan.style.transform = 'scale(1)';
                }, 200);
            } else {
                console.error('❌ Elemento #total-final-display no encontrado');
            }
            const btnFinalizar = document.querySelector('.btn-finalizar');
            if (btnFinalizar) {
                const totalBtnElement = btnFinalizar.querySelector('#total-final, [id*="total"]');
                if (totalBtnElement) {
                    totalBtnElement.textContent = total.toFixed(2);
                }
            }
        }

        function showSavedPhoneMode() {
            const newAddressForm = document.getElementById('newAddressForm');
            newAddressForm.classList.add('hidden');

            // ✅ NO DESHABILITAR los campos - solo ocultarlos
            // Esto permite que se capturen sus valores si es necesario
            const camposFormularioNuevo = newAddressForm.querySelectorAll('input, select, textarea');
            camposFormularioNuevo.forEach(campo => {
                // ❌ ELIMINAR ESTO: campo.disabled = true;

                // ✅ Solo quitar 'required' cuando está oculto
                if (campo.hasAttribute('required')) {
                    campo.setAttribute('data-was-required', 'true');
                    campo.removeAttribute('required');
                }
            });

            // Mostrar teléfono de contacto
            const telefonoContactoDiv = document.getElementById('telefono-contacto-container');
            const telefonoContacto = document.getElementById('telefono_contacto');
            const inputTelefono = document.getElementById('input-telefono');

            if (telefonoContactoDiv) telefonoContactoDiv.classList.remove('hidden');
            if (telefonoContacto) {
                telefonoContacto.name = 'telefono';
                telefonoContacto.required = true;
            }
            if (inputTelefono) {
                inputTelefono.name = 'telefono_nuevo';
                // ❌ ELIMINAR: inputTelefono.disabled = true;
                inputTelefono.removeAttribute('required');
            }
        }

        function showNewAddressMode() {
            const newAddressForm = document.getElementById('newAddressForm');
            newAddressForm.classList.remove('hidden');

            // ✅ Habilitar campos cuando se muestra el formulario
            const camposFormularioNuevo = newAddressForm.querySelectorAll('input, select, textarea');
            camposFormularioNuevo.forEach(campo => {
                campo.disabled = false; // ✅ Ahora sí habilitamos
                if (campo.getAttribute('data-was-required') === 'true') {
                    campo.setAttribute('required', 'required');
                    campo.removeAttribute('data-was-required');
                }
            });

            // Ocultar teléfono de contacto
            const telefonoContactoDiv = document.getElementById('telefono-contacto-container');
            const telefonoContacto = document.getElementById('telefono_contacto');
            const inputTelefono = document.getElementById('input-telefono');

            if (telefonoContactoDiv) telefonoContactoDiv.classList.add('hidden');
            if (telefonoContacto) {
                telefonoContacto.name = 'telefono_disabled';
                telefonoContacto.required = false;
            }
            if (inputTelefono) {
                inputTelefono.name = 'telefono';
                inputTelefono.disabled = false;
                inputTelefono.required = true;
            }
        }

        function showNewAddressMode() {
            const newAddressForm = document.getElementById('newAddressForm');
            newAddressForm.classList.remove('hidden');
            const camposFormularioNuevo = newAddressForm.querySelectorAll('input, select, textarea');
            camposFormularioNuevo.forEach(campo => {
                campo.disabled = false;
                if (campo.getAttribute('data-was-required') === 'true') {
                    campo.setAttribute('required', 'required');
                    campo.removeAttribute('data-was-required');
                }
            });
            const telefonoContactoDiv = document.getElementById('telefono-contacto-container');
            const telefonoContacto = document.getElementById('telefono_contacto');
            const inputTelefono = document.getElementById('input-telefono');

            console.log('showNewAddressMode: showing new address form');
            if (telefonoContactoDiv) telefonoContactoDiv.classList.add('hidden');
            if (telefonoContacto) {
                telefonoContacto.name = 'telefono_disabled';
                telefonoContacto.required = false;
            }
            if (inputTelefono) {
                inputTelefono.name = 'telefono';
                inputTelefono.disabled = false;
                inputTelefono.required = true;
            }
        }

        function selectAddress(card) {
            if (selectedAddressCard) selectedAddressCard.classList.remove('selected');
            selectedAddressCard = card;
            card.classList.add('selected');

            const direccionData = JSON.parse(card.dataset.direccion);
            document.getElementById('direccion_id_seleccionada').value = direccionData.id;
            isLoadingFromSavedAddress = true;

            window.mpPreferenciaCache = null;
            window.mpFormularioHash = null;


            showSavedPhoneMode();
            const departamentoOriginal = direccionData.departamento || '';
            const departamentoLower = departamentoOriginal.toLowerCase().trim();
            const esLima = departamentoLower.includes('lima') ||
                departamentoLower.includes('callao') ||
                departamentoOriginal === '15' ||
                departamentoOriginal === '07' ||
                departamentoLower === 'lima' ||
                departamentoLower === 'callao';
            const envioGratis = <?= $totales['envio_gratis'] ? 'true' : 'false' ?>;

            let costoEnvio = 0;
            if (!envioGratis) {
                costoEnvio = esLima ? 8 : 12;
            }
            actualizarDisplayEnvio(costoEnvio);
            recalcularTotalFinal(costoEnvio);
            setTimeout(() => {
                if (typeof llenarCamposDesdeDireccion === 'function') {
                    try {
                        llenarCamposDesdeDireccion(direccionData);
                    } catch (err) {
                        console.warn(err);
                    }
                }
                setTimeout(() => {
                    isLoadingFromSavedAddress = false;
                }, 200);
            }, 50);
        }

        function mostrarFormularioNuevaDireccion() {
            isLoadingFromSavedAddress = false;
            if (selectedAddressCard) {
                selectedAddressCard.classList.remove('selected');
                selectedAddressCard = null;
            }
            document.getElementById('direccion_id_seleccionada').value = '';
            showNewAddressMode();

            const envioGratis = <?= $totales['envio_gratis'] ? 'true' : 'false' ?>;
            const costoEnvioInicial = envioGratis ? 0 : 8;
            const costoEnvioDisplay = document.getElementById('costo-envio-display');

            if (costoEnvioDisplay) {
                if (costoEnvioInicial === 0) {
                    costoEnvioDisplay.innerHTML = `
                        <span style="color: var(--success-color); font-weight: 600;">¡GRATIS!</span>
                        <small style="display: block; color: #666; font-size: 0.8rem;">
                            (Promoción aplicada)
                        </small>
                    `;
                } else {
                    // Mostrar costo de envío
                    costoEnvioDisplay.innerHTML = `
                        S/ <span id="costo-envio-valor">${costoEnvioInicial.toFixed(2)}</span>
                    `;
                }
            }

            // Recalcular el total final

            // ✅ Actualizar display usando función centralizada
            actualizarDisplayEnvio(costoEnvioInicial);

            // ✅ Recalcular el total final
            recalcularTotalFinal(costoEnvioInicial);
        }

        let currentDireccionId = null;

        function editarDireccion(event, direccionId) {
            event.stopPropagation();
            event.preventDefault();
            const addressCards = document.querySelectorAll('.address-card');
            let direccionData = null;

            addressCards.forEach(card => {
                const data = JSON.parse(card.dataset.direccion);
                if (data.id === direccionId) {
                    direccionData = data;
                }
            });

            if (!direccionData) {
                alert('No se encontró la dirección');
                return;
            }

            // Llenar el formulario de edición
            document.getElementById('edit-direccion-id').value = direccionData.id;
            document.getElementById('edit-nombre-direccion').value = direccionData.nombre_direccion || '';
            document.getElementById('edit-tipo').value = direccionData.tipo || 'casa';
            document.getElementById('edit-direccion').value = direccionData.direccion || '';
            document.getElementById('edit-distrito').value = direccionData.distrito || '';
            document.getElementById('edit-referencia').value = direccionData.referencia || '';
            document.getElementById('edit-es-principal').checked = direccionData.es_principal == 1;

            // Cargar departamentos en el select de edición
            const editDepartamentoSelect = document.getElementById('edit-departamento');
            editDepartamentoSelect.innerHTML = '<option value="">Seleccionar departamento</option>';

            let departamentoSeleccionado = null;
            departamentosData.forEach(depto => {
                const option = document.createElement('option');
                option.value = depto.id;
                option.textContent = depto.nombre;
                if (direccionData.departamento === depto.nombre || direccionData.departamento === depto.id) {
                    option.selected = true;
                    departamentoSeleccionado = depto.id;
                }
                editDepartamentoSelect.appendChild(option);
            });

            // Cargar provincias si hay un departamento seleccionado
            if (departamentoSeleccionado) {
                // Primero cargar las provincias
                cargarProvinciasEdicion();

                // Luego seleccionar la provincia guardada con timeout más largo
                setTimeout(() => {
                    const editProvinciaSelect = document.getElementById('edit-provincia');
                    if (direccionData.provincia && editProvinciaSelect.options.length > 1) {
                        // ✅ MEJORADO: Buscar por ID primero, luego por nombre
                        let provinciaEncontrada = false;

                        // Intentar encontrar por ID
                        for (let i = 0; i < editProvinciaSelect.options.length; i++) {
                            const option = editProvinciaSelect.options[i];
                            if (option.value === direccionData.provincia) {
                                option.selected = true;
                                provinciaEncontrada = true;
                                break;
                            }
                        }

                        // Si no se encontró por ID, buscar por nombre (fallback)
                        if (!provinciaEncontrada) {
                            for (let i = 0; i < editProvinciaSelect.options.length; i++) {
                                const option = editProvinciaSelect.options[i];
                                if (option.textContent === direccionData.provincia) {
                                    option.selected = true;
                                    break;
                                }
                            }
                        }
                    }
                }, 200); // ✅ Aumentado de 100ms a 200ms para dar más tiempo
            }

            // Mostrar modal
            const modal = document.getElementById('edit-address-modal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function cerrarModalEdicion() {
            const modal = document.getElementById('edit-address-modal');
            const modalContent = modal.querySelector('.modal-content');

            modalContent.classList.add('modal-closing');

            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                modalContent.classList.remove('modal-closing');
            }, 300);
        }

        function guardarCambiosDireccion(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const direccionId = document.getElementById('edit-direccion-id').value;

            // Deshabilitar botón
            const confirmBtn = document.getElementById('confirm-edit');
            const originalText = confirmBtn.textContent;
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Guardando...';
            confirmBtn.style.opacity = '0.7';

            fetch('<?= url("usuario/actualizar-direccion") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarNotificacion('Dirección actualizada correctamente', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        throw new Error(data.message || 'Error al actualizar la dirección');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion(error.message || 'Error al actualizar la dirección', 'error');

                    // Rehabilitar botón
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = originalText;
                    confirmBtn.style.opacity = '1';
                });
        }

        function eliminarDireccion(event, direccionId) {
            event.stopPropagation();
            event.preventDefault();

            console.log('Mostrando modal para eliminar dirección ID:', direccionId);
            currentDireccionId = direccionId;
            const addressCard = event.target.closest('.address-card');
            let addressDetails = 'Esta dirección';

            if (addressCard) {
                try {
                    const direccionData = JSON.parse(addressCard.dataset.direccion);
                    const nombre = direccionData.nombre_direccion || 'Mi dirección';
                    const direccion = direccionData.direccion || '';
                    const distrito = direccionData.distrito || '';
                    const departamento = direccionData.departamento || '';

                    addressDetails = `${nombre}: ${direccion}${distrito ? ', ' + distrito : ''}${departamento ? ', ' + departamento : ''}`;
                } catch (e) {
                    console.warn('Error al parsear datos de dirección:', e);
                }
            }

            // Actualizar contenido del modal
            document.getElementById('address-details').textContent = addressDetails;

            // Mostrar modal
            const modal = document.getElementById('delete-address-modal');
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevenir scroll del body
        }

        function confirmarEliminacion() {
            if (!currentDireccionId) return;

            console.log('📡 Enviando petición de eliminación...');
            const confirmBtn = document.getElementById('confirm-delete');
            const originalText = confirmBtn.textContent;
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Eliminando...';
            confirmBtn.style.opacity = '0.7';

            fetch('<?= url("usuario/eliminar-direccion") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + currentDireccionId
                })
                .then(response => {
                    console.log('📨 Respuesta recibida:', response);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📄 Datos procesados:', data);
                    if (data.success) {
                        // Cerrar modal con animación
                        cerrarModalEliminacion();

                        // Mostrar mensaje de éxito
                        setTimeout(() => {
                            mostrarNotificacion('✅ Dirección eliminada correctamente', 'success');
                            setTimeout(() => location.reload(), 1500);
                        }, 300);
                    } else {
                        throw new Error(data.message || 'Error desconocido');
                    }
                })
                .catch(error => {
                    console.error('❌ Error en la petición:', error);
                    mostrarNotificacion('❌ Error al eliminar la dirección: ' + error.message, 'error');

                    // Restaurar botón
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = originalText;
                    confirmBtn.style.opacity = '1';
                });
        }

        function cerrarModalEliminacion() {
            const modal = document.getElementById('delete-address-modal');
            const modalContent = modal.querySelector('.modal-content');
            modalContent.classList.add('modal-closing');

            setTimeout(() => {
                modal.style.display = 'none';
                modalContent.classList.remove('modal-closing');
                document.body.style.overflow = '';
                currentDireccionId = null;
            }, 300);
        }

        function mostrarNotificacion(mensaje, tipo = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                padding: 16px 20px;
                border-radius: 8px;
                color: white;
                font-family: 'Outfit', sans-serif;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transform: translateX(100%);
                transition: transform 0.3s ease;
                max-width: 300px;
                ${tipo === 'success' ? 'background: linear-gradient(135deg, #28a745, #20c997);' : 'background: linear-gradient(135deg, #dc3545, #c82333);'}
            `;
            notification.textContent = mensaje;

            document.body.appendChild(notification);

            // Mostrar con animación
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            // Ocultar después de 3 segundos
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
    </script>
    <script>
        function llenarCamposDesdeDireccion(direccion) {
            const departamentoSelect = document.getElementById('departamento');
            if (departamentoSelect && direccion.departamento) {
                for (let i = 0; i < departamentoSelect.options.length; i++) {
                    if (departamentoSelect.options[i].text === direccion.departamento) {
                        departamentoSelect.value = departamentoSelect.options[i].value;
                        break;
                    }
                }
                setTimeout(() => {
                    cargarProvincias();
                    setTimeout(() => {
                        const provinciaSelect = document.getElementById('provincia');
                        if (provinciaSelect && direccion.provincia) {
                            for (let i = 0; i < provinciaSelect.options.length; i++) {
                                if (provinciaSelect.options[i].text === direccion.provincia) {
                                    provinciaSelect.value = provinciaSelect.options[i].value;
                                    break;
                                }
                            }
                        }

                        // Llenar distrito
                        const distritoInput = document.getElementById('distrito');
                        if (distritoInput && direccion.distrito) {
                            distritoInput.value = direccion.distrito;
                        }

                        // ✅ SOLO actualizar métodos de pago, NO el costo de envío
                        // (el costo ya se calculó correctamente en selectAddress)

                        // ✅ LIMPIAR CACHE AL CAMBIAR PROVINCIA
                        window.mpPreferenciaCache = null;
                        window.mpFormularioHash = null;
                        console.log('🗑️ Cache limpiado por cambio de provincia');

                        actualizarMetodosPago();
                        // ❌ NO llamar actualizarCostoEnvio() aquí para evitar conflictos
                    }, 100);
                }, 100);
            }
        }

        document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
            const direccionId = document.getElementById('direccion_id_seleccionada').value;
            const newAddressForm = document.getElementById('newAddressForm');
            const isNewAddressVisible = !newAddressForm.classList.contains('hidden');
            if (!direccionId && isNewAddressVisible) {
                const departamento = document.getElementById('departamento').value;
                const provincia = document.getElementById('provincia').value;
                const distrito = document.getElementById('distrito').value;
                const direccion = document.querySelector('textarea[name="direccion"]').value;

                if (!departamento || !provincia || !distrito || !direccion) {
                    e.preventDefault();
                    alert('Por favor, completa todos los campos obligatorios de la dirección de envío.');
                    return false;
                }
            } else if (!direccionId && !isNewAddressVisible) {
                e.preventDefault();
                alert('Por favor, selecciona una dirección o completa el formulario de nueva dirección.');
                return false;
            }

            return true;
        });

        function obtenerDatosFormularioActuales() {
            const direccionId = document.getElementById('direccion_id_seleccionada')?.value || '';
            const datos = {
                direccion_id: direccionId
            };

            // ✅ SI HAY DIRECCIÓN SELECCIONADA (GUARDADA)
            if (direccionId) {
                console.log('📍 Usando dirección guardada:', direccionId);

                // Capturar solo el teléfono de contacto
                const telefonoContacto = document.getElementById('telefono_contacto');
                if (telefonoContacto && telefonoContacto.value) {
                    datos.telefono = telefonoContacto.value;
                }

                // ✅ OBTENER DATOS DE LA DIRECCIÓN SELECCIONADA
                const selectedCard = document.querySelector('.address-card.selected');
                if (selectedCard) {
                    try {
                        const direccionData = JSON.parse(selectedCard.dataset.direccion);
                        datos.departamento = direccionData.departamento || '';
                        datos.provincia = direccionData.provincia || '';
                        datos.distrito = direccionData.distrito || '';
                        datos.direccion = direccionData.direccion || '';
                        datos.referencia = direccionData.referencia || '';
                        datos.nombre = direccionData.nombre_direccion || '';
                    } catch (e) {
                        console.error('Error parseando dirección guardada:', e);
                    }
                }
            }
            // ✅ SI ES NUEVA DIRECCIÓN (FORMULARIO VISIBLE)
            else {
                console.log('📝 Usando formulario de nueva dirección');

                const newAddressForm = document.getElementById('newAddressForm');
                if (!newAddressForm || newAddressForm.classList.contains('hidden')) {
                    console.warn('⚠️ Formulario de nueva dirección no está visible');
                    return datos;
                }

                // ✅ CAPTURAR TODOS LOS CAMPOS DEL FORMULARIO (incluso si están disabled)
                const camposFormulario = {
                    nombre: document.getElementById('input-nombre'),
                    telefono: document.getElementById('input-telefono'),
                    departamento: document.getElementById('departamento'),
                    provincia: document.getElementById('provincia'),
                    distrito: document.getElementById('distrito'),
                    direccion: document.querySelector('textarea[name="direccion"]'),
                    referencia: document.getElementById('input-referencia'),
                    guardar_direccion: document.getElementById('guardar_direccion'),
                    tipo_direccion: document.querySelector('select[name="tipo_direccion"]'),
                    nombre_direccion: document.querySelector('input[name="nombre_direccion"]')
                };

                // ✅ EXTRAER VALORES (incluso de campos disabled)
                Object.keys(camposFormulario).forEach(key => {
                    const campo = camposFormulario[key];
                    if (campo) {
                        if (campo.type === 'checkbox') {
                            datos[key] = campo.checked ? '1' : '0';
                        } else {
                            datos[key] = campo.value || '';
                        }
                    }
                });

                // ✅ LOG PARA DEBUG
                console.log('📋 Datos capturados del formulario:', datos);

                // ✅ VALIDAR CAMPOS CRÍTICOS
                const camposCriticos = ['departamento', 'provincia', 'distrito', 'direccion', 'telefono'];
                const faltantes = camposCriticos.filter(campo => !datos[campo]);

                if (faltantes.length > 0) {
                    console.warn('⚠️ Campos faltantes:', faltantes);
                }
            }

            // ✅ CAPTURAR DATOS DE FACTURACIÓN SIEMPRE
            const facturacionCampos = [
                'facturacion_tipo_documento',
                'facturacion_numero_documento',
                'facturacion_email',
                'facturacion_nombre',
                'facturacion_direccion'
            ];

            facturacionCampos.forEach(campo => {
                const elemento = document.querySelector(`[name="${campo}"]`);
                if (elemento) {
                    datos[campo] = elemento.value || '';
                }
            });

            // ✅ CAPTURAR MÉTODO DE PAGO
            const metodoPago = document.querySelector('input[name="metodo_pago"]:checked');
            if (metodoPago) {
                datos.metodo_pago = metodoPago.value;
            }

            console.log('✅ Datos finales capturados:', datos);
            return datos;
        }
    </script>

    <div id="delete-address-modal" class="modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background: #fff; margin: 10% auto; padding: 0; width: 90%; max-width: 450px; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.15); animation: modalFadeIn 0.3s ease;">
            <div class="modal-header" style="padding: 24px 24px 16px; border-bottom: 1px solid #eee; background: linear-gradient(135deg, #ff4757, #ff3742); border-radius: 12px 12px 0 0;">
                <div style="display: flex; align-items: center;">
                    <div style="background: rgba(255,255,255,0.2); border-radius: 50%; padding: 8px; margin-right: 12px;">
                        <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 style="margin: 0; color: white; font-size: 20px; font-weight: 600; font-family: 'Outfit', sans-serif;">Eliminar Dirección</h3>
                        <p style="margin: 4px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">Esta acción no se puede deshacer</p>
                    </div>
                </div>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="background: linear-gradient(135deg, #fee, #fdd); border-radius: 50%; width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; border: 3px solid #ff4757;">
                        <svg width="32" height="32" fill="#ff4757" viewBox="0 0 24 24">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>

                <div style="text-align: center; margin-bottom: 24px;">
                    <p style="font-size: 16px; color: #333; margin: 0 0 8px; font-weight: 500; font-family: 'Outfit', sans-serif;">¿Estás seguro de eliminar esta dirección?</p>
                    <p id="address-details" style="font-size: 14px; color: #666; margin: 0; background: #f8f9fa; padding: 12px; border-radius: 8px; border-left: 4px solid #ff4757;"></p>
                </div>

                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button id="cancel-delete" type="button" style="
                        background: #6c757d; 
                        color: white; 
                        border: none; 
                        padding: 12px 24px; 
                        border-radius: 8px; 
                        font-size: 14px; 
                        font-weight: 600; 
                        cursor: pointer; 
                        transition: all 0.2s ease;
                        font-family: 'Outfit', sans-serif;
                        min-width: 100px;
                    " onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
                        Cancelar
                    </button>
                    <button id="confirm-delete" type="button" style="
                        background: linear-gradient(135deg, #ff4757, #ff3742); 
                        color: white; 
                        border: none; 
                        padding: 12px 24px; 
                        border-radius: 8px; 
                        font-size: 14px; 
                        font-weight: 600; 
                        cursor: pointer; 
                        transition: all 0.2s ease;
                        font-family: 'Outfit', sans-serif;
                        min-width: 100px;
                        box-shadow: 0 4px 12px rgba(255, 71, 87, 0.3);
                    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(255, 71, 87, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(255, 71, 87, 0.3)'">
                        Sí, Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <style>
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes modalFadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 0;
                transform: translateY(-50px);
            }
        }

        .modal-closing {
            animation: modalFadeOut 0.3s ease;
        }
    </style>
    <div id="edit-address-modal" class="modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background: #fff; margin: 5% auto; padding: 0; width: 90%; max-width: 600px; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.15); animation: modalFadeIn 0.3s ease; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header" style="padding: 24px 24px 16px; border-bottom: 1px solid #eee; background: linear-gradient(135deg, #2AC1DB, #1a9fb5); border-radius: 12px 12px 0 0;">
                <div style="display: flex; align-items: center;">
                    <div style="background: rgba(255,255,255,0.2); border-radius: 50%; padding: 8px; margin-right: 12px;">
                        <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 style="margin: 0; color: white; font-size: 20px; font-weight: 600; font-family: 'Outfit', sans-serif;">Editar Dirección</h3>
                        <p style="margin: 4px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">Actualiza los datos de tu dirección</p>
                    </div>
                </div>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <form id="edit-address-form">
                    <input type="hidden" id="edit-direccion-id" name="direccion_id">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px;">Nombre de la dirección</label>
                            <input type="text" id="edit-nombre-direccion" name="nombre_direccion" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: 'Outfit', sans-serif;" placeholder="Casa, Trabajo, etc.">
                        </div>

                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px;">Tipo</label>
                            <select id="edit-tipo" name="tipo" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: 'Outfit', sans-serif;">
                                <option value="casa">Casa</option>
                                <option value="trabajo">Trabajo</option>
                                <option value="envio">Solo envío</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px;">Departamento *</label>
                            <select id="edit-departamento" name="departamento" required onchange="cargarProvinciasEdicion()" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: 'Outfit', sans-serif;">
                                <option value="">Seleccionar departamento</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px;">Provincia *</label>
                            <select id="edit-provincia" name="provincia" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: 'Outfit', sans-serif;" disabled>
                                <option value="">Seleccionar provincia</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px;">Distrito *</label>
                            <input type="text" id="edit-distrito" name="distrito" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: 'Outfit', sans-serif;" placeholder="Distrito">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px;">Dirección completa *</label>
                        <textarea id="edit-direccion" name="direccion" required rows="3" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: 'Outfit', sans-serif; resize: vertical;" placeholder="Av/Jr/Calle, número, urbanización..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 8px; color: #333; font-weight: 500; font-size: 14px;">Referencia</label>
                        <input type="text" id="edit-referencia" name="referencia" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: 'Outfit', sans-serif;" placeholder="Ej: Frente al parque, casa amarilla">
                    </div>

                    <div style="display: flex; align-items: center; margin-bottom: 20px; padding: 12px; background: #f8f9fa; border-radius: 8px;">
                        <input type="checkbox" id="edit-es-principal" name="es_principal" style="margin-right: 8px; width: 18px; height: 18px; cursor: pointer;">
                        <label for="edit-es-principal" style="margin: 0; color: #333; font-size: 14px; cursor: pointer; font-family: 'Outfit', sans-serif;">
                            <strong>Establecer como dirección principal</strong>
                        </label>
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid #eee;">
                        <button type="button" id="cancel-edit" style="
                            background: #6c757d; 
                            color: white; 
                            border: none; 
                            padding: 12px 24px; 
                            border-radius: 8px; 
                            font-size: 14px; 
                            font-weight: 600; 
                            cursor: pointer; 
                            transition: all 0.2s ease;
                            font-family: 'Outfit', sans-serif;
                        " onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
                            Cancelar
                        </button>
                        <button type="submit" id="confirm-edit" style="
                            background: linear-gradient(135deg, #2AC1DB, #1a9fb5); 
                            color: white; 
                            border: none; 
                            padding: 12px 24px; 
                            border-radius: 8px; 
                            font-size: 14px; 
                            font-weight: 600; 
                            cursor: pointer; 
                            transition: all 0.2s ease;
                            font-family: 'Outfit', sans-serif;
                            box-shadow: 0 4px 12px rgba(42, 193, 219, 0.3);
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(42, 193, 219, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(42, 193, 219, 0.3)'">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="terms-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 700px; margin: 20px auto; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); background: #fff; display: flex; flex-direction: column; max-height: 90vh; overflow: hidden;">
            <div class="modal-header" style="background: #1b1b1b; padding: 1.5rem 2rem; border-radius: 12px 12px 0 0;">
                <div class="modal-title-container" style="flex: 1;">
                    <h2 class="modal-title" style="font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0 0 0.25rem 0;">Términos y Condiciones</h2>
                    <p class="modal-subtitle" style="font-size: 0.9rem; color: #e0e0e0; margin: 0;">Políticas y condiciones de uso de ByteBox</p>
                </div>
                <button type="button" id="close-terms-modal" class="modal-close" style="background: rgba(255,255,255,0.1); border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 1.25rem; cursor: pointer; color: #fff; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">×</button>
            </div>

            <div class="modal-body" style="flex: 1; overflow-y: auto; padding: 2rem; max-height: calc(90vh - 160px);">
                <p style="font-size: 1rem; line-height: 1.6; color: #555; margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #2ac1db;">
                    Bienvenido a ByteBox, tu plataforma confiable para adquirir productos tecnológicos de calidad. Al utilizar nuestro sitio web, realizar compras o interactuar con nuestros servicios, aceptas cumplir con los términos y condiciones descritos a continuación.
                </p>

                <div class="terms-section-modal" style="margin-bottom: 1.5rem; padding: 1rem; background: #ffffff; border-radius: 8px; border: 1px solid #e9ecef;">
                    <h3 class="section-title-modal" style="font-size: 1.1rem; font-weight: 600; color: #1b1b1b; margin: 0 0 0.75rem 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="section-number" style="background: #2ac1db; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 600;">1</span>
                        Información General
                    </h3>
                    <div class="section-content" style="font-size: 0.9rem; color: #555; line-height: 1.5; display: block !important;">
                        <p style="margin: 0;">Al acceder a <strong style="color: #1b1b1b;">ByteBox</strong>, te comprometes a utilizar nuestros servicios de manera responsable y conforme a las leyes aplicables. Estos términos rigen el uso de nuestro sitio web, las compras realizadas y cualquier interacción con nuestra plataforma.</p>
                    </div>
                </div>

                <div class="terms-section-modal" style="margin-bottom: 1.5rem; padding: 1rem; background: #ffffff; border-radius: 8px; border: 1px solid #e9ecef;">
                    <h3 class="section-title-modal" style="font-size: 1.1rem; font-weight: 600; color: #1b1b1b; margin: 0 0 0.75rem 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="section-number" style="background: #2ac1db; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 600;">2</span>
                        Productos y Precios
                    </h3>
                    <div class="section-content" style="font-size: 0.9rem; color: #555; line-height: 1.5; display: block !important;">
                        <div class="check-item" style="display: flex; align-items: flex-start; margin: 0.5rem 0; gap: 0.5rem;">
                            <span class="check-icon" style="color: #28a745; font-weight: bold; flex-shrink: 0;">✓</span>
                            <span>Todos los precios están en soles peruanos (S/) e incluyen IGV.</span>
                        </div>
                        <div class="check-item" style="display: flex; align-items: flex-start; margin: 0.5rem 0; gap: 0.5rem;">
                            <span class="check-icon" style="color: #28a745; font-weight: bold; flex-shrink: 0;">✓</span>
                            <span>Los precios pueden variar sin previo aviso debido a factores de mercado.</span>
                        </div>
                        <div class="check-item" style="display: flex; align-items: flex-start; margin: 0.5rem 0; gap: 0.5rem;">
                            <span class="check-icon" style="color: #28a745; font-weight: bold; flex-shrink: 0;">✓</span>
                            <span>La disponibilidad de productos está sujeta a nuestro inventario.</span>
                        </div>
                    </div>
                </div>

                <div class="terms-section-modal" style="margin-bottom: 1.5rem; padding: 1rem; background: #ffffff; border-radius: 8px; border: 1px solid #e9ecef;">
                    <h3 class="section-title-modal" style="font-size: 1.1rem; font-weight: 600; color: #1b1b1b; margin: 0 0 0.75rem 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="section-number" style="background: #2ac1db; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 600;">3</span>
                        Política de Envío
                    </h3>
                    <div class="section-content" style="font-size: 0.9rem; color: #555; line-height: 1.5; display: block !important;">
                        <div class="check-item" style="display: flex; align-items: flex-start; margin: 0.5rem 0; gap: 0.5rem;">
                            <span class="check-icon" style="color: #28a745; font-weight: bold; flex-shrink: 0;">✓</span>
                            <span><strong style="color: #1b1b1b;">Envío gratuito</strong> a nivel nacional para compras superiores a S/ 100.</span>
                        </div>
                        <div class="check-item" style="display: flex; align-items: flex-start; margin: 0.5rem 0; gap: 0.5rem;">
                            <span class="check-icon" style="color: #28a745; font-weight: bold; flex-shrink: 0;">✓</span>
                            <span>Tiempos de entrega: 2-5 días hábiles en Lima, 3-7 días en provincias.</span>
                        </div>
                        <div class="check-item" style="display: flex; align-items: flex-start; margin: 0.5rem 0; gap: 0.5rem;">
                            <span class="check-icon" style="color: #28a745; font-weight: bold; flex-shrink: 0;">✓</span>
                            <span>Pago contra entrega disponible únicamente en Lima Metropolitana.</span>
                        </div>
                    </div>
                </div>

                <div class="terms-section-modal" style="margin-bottom: 1.5rem; padding: 1rem; background: #ffffff; border-radius: 8px; border: 1px solid #e9ecef;">
                    <h3 class="section-title-modal" style="font-size: 1.1rem; font-weight: 600; color: #1b1b1b; margin: 0 0 0.75rem 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="section-number" style="background: #2ac1db; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 600;">4</span>
                        Métodos de Pago
                    </h3>
                    <div class="section-content" style="font-size: 0.9rem; color: #555; line-height: 1.5; display: block !important;">
                        <div class="check-item" style="display: flex; align-items: flex-start; margin: 0.5rem 0; gap: 0.5rem;">
                            <span class="check-icon" style="color: #28a745; font-weight: bold; flex-shrink: 0;">✓</span>
                            <span><strong style="color: #1b1b1b;">Pago contra entrega:</strong> Exclusivo para Lima Metropolitana.</span>
                        </div>
                        <div class="check-item" style="display: flex; align-items: flex-start; margin: 0.5rem 0; gap: 0.5rem;">
                            <span class="check-icon" style="color: #28a745; font-weight: bold; flex-shrink: 0;">✓</span>
                            <span><strong style="color: #1b1b1b;">Pago con tarjeta:</strong> Aceptamos tarjetas a nivel nacional.</span>
                        </div>
                        <div class="check-item" style="display: flex; align-items: flex-start; margin: 0.5rem 0; gap: 0.5rem;">
                            <span class="check-icon" style="color: #28a745; font-weight: bold; flex-shrink: 0;">✓</span>
                            <span>Todas las transacciones están protegidas con encriptación avanzada.</span>
                        </div>
                    </div>
                </div>
                <div class="terms-section-modal" style="margin-bottom: 1.5rem; padding: 1rem; background: #ffffff; border-radius: 8px; border: 1px solid #e9ecef;">
                    <h3 class="section-title-modal" style="font-size: 1.1rem; font-weight: 600; color: #1b1b1b; margin: 0 0 0.75rem 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="section-number" style="background: #2ac1db; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 600;">5</span>
                        Contacto y Soporte
                    </h3>
                    <div class="section-content" style="font-size: 0.9rem; color: #555; line-height: 1.5; display: block !important;">
                        <p style="margin: 0 0 0.75rem 0;">Estamos aquí para ayudarte. Si tienes preguntas, necesitas soporte técnico o deseas presentar un reclamo, contáctanos a través de:</p>
                        <div class="contact-info" style="background: #f8f9fa; padding: 0.75rem; border-radius: 6px;">
                            <div class="contact-item" style="display: flex; align-items: center; margin: 0.25rem 0; gap: 0.5rem;">
                                <strong style="color: #1b1b1b; min-width: 60px;">Email:</strong>
                                <a href="mailto:info@bytebox.com" style="color: #2ac1db; text-decoration: none;">info@bytebox.com</a>
                            </div>
                            <div class="contact-item" style="display: flex; align-items: center; margin: 0.25rem 0; gap: 0.5rem;">
                                <strong style="color: #1b1b1b; min-width: 60px;">Teléfono:</strong>
                                <a href="tel:+51999123456" style="color: #2ac1db; text-decoration: none;">+51 999 123 456</a>
                            </div>
                            <div class="contact-item" style="display: flex; align-items: center; margin: 0.25rem 0; gap: 0.5rem;">
                                <strong style="color: #1b1b1b; min-width: 60px;">Horario:</strong>
                                <span>Lunes a Sábado, 9:00 AM - 8:00 PM</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="background: #f8f9fa; padding: 1.5rem 2rem; border-top: 1px solid #e9ecef; border-radius: 0 0 12px 12px; display: flex; justify-content: space-between; align-items: center;">
                <p style="margin: 0; font-size: 0.8rem; color: #666; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-info-circle" style="color: #2ac1db;"></i>
                    Última actualización: Septiembre 2025
                </p>
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" id="close-terms-btn" class="btn-secondary" style="padding: 0.75rem 1.5rem; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">Cerrar</button>
                    <button type="button" id="accept-terms-btn" class="btn-accept-terms" style="padding: 0.75rem 1.5rem; background: #2ac1db; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;">Acepto los Términos</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>