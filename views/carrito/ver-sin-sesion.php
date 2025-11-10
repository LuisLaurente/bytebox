<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cantidadEnCarrito = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidadEnCarrito += $item['cantidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Bytebox</title>

    <!-- Favicon -->
    <link rel="icon" href="<?= url('image/faviconT.ico') ?>" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('image/faviconT.png') ?>">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="<?= url('css/carrito-sin-sesion.css') ?>">
</head>

<body class="carrito-sin-sesion">

    <?php include_once __DIR__ . '/../admin/includes/header.php'; ?>

    <div class="container-principal">
        <h2 class="page-title">Carrito de Compras</h2>

        <?php if (!empty($productosDetallados)): ?>
            <div class="main-grid">
                <!-- Columna Izquierda: Lista de Productos -->
                <div class="productos-container">
                    <div class="productos-list-header">
                        Tus Productos
                    </div>
                    <div id="productos-list">
                        <?php foreach ($productosDetallados as $item): ?>
                            <div class="producto-item" id="producto-<?= htmlspecialchars($item['clave']) ?>">
                                <div class="producto-info-wrapper">
                                    <div class="producto-imagen">
                                        <!-- Asegúrate de tener una imagen placeholder o de que $item['imagen'] siempre exista -->
                                        <img src="<?= htmlspecialchars($item['imagen'] ?? 'ruta/a/placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['nombre']) ?>">
                                    </div>
                                    <div class="producto-info">
                                        <div class="producto-nombre"><?= htmlspecialchars($item['nombre']) ?></div>
                                        <?php if (!empty($item['talla']) || !empty($item['color'])): ?>
                                            <div class="producto-variante" style="font-size: 0.85rem; color: #666; margin-top: 4px;">
                                                <?php if (!empty($item['talla'])): ?>
                                                    <span>Talla: <strong><?= htmlspecialchars($item['talla']) ?></strong></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['color'])): ?>
                                                    <?php if (!empty($item['talla'])): ?> | <?php endif; ?>
                                                    <span>Color: <strong><?= htmlspecialchars($item['color']) ?></strong></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="producto-precio">S/ <?= number_format($item['precio'], 2) ?></div>
                                    </div>
                                </div>
                                <div class="producto-actions-wrapper">
                                    <div class="cantidad-container">
                                        <a href="<?= url('carrito/disminuir/' . urlencode($item['clave'])) ?>" class="btn-cantidad btn-disminuir" data-clave="<?= htmlspecialchars($item['clave']) ?>" title="Disminuir">
                                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                                            </svg>
                                        </a>
                                        <span class="cantidad-numero" id="cantidad-<?= htmlspecialchars($item['clave']) ?>"><?= $item['cantidad'] ?></span>
                                        <a href="<?= url('carrito/aumentar/' . urlencode($item['clave'])) ?>" class="btn-cantidad btn-aumentar" data-clave="<?= htmlspecialchars($item['clave']) ?>" title="Aumentar">
                                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </a>
                                    </div>
                                    <div class="producto-subtotal" id="subtotal-<?= htmlspecialchars($item['clave']) ?>">
                                        S/ <?= number_format($item['subtotal'], 2) ?>
                                    </div>
                                    <a href="<?= url('carrito/eliminar/' . urlencode($item['clave'])) ?>" class="btn-eliminar" data-clave="<?= htmlspecialchars($item['clave']) ?>" title="Eliminar producto">
                                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Columna Derecha: Resumen de Compra -->
                <div class="resumen-container">
                    <div class="resumen-header">
                        <h3>Resumen de Compra</h3>
                    </div>
                    <div class="resumen-body">
                        <div class="resumen-item">
                            <span class="resumen-label">Subtotal</span>
                            <span class="resumen-valor" id="resumen-subtotal">S/ <?= number_format($totales['subtotal'] ?? 0, 2) ?></span>
                        </div>
                        <?php if (!empty($promocionesAplicadas) && $totales['descuento'] > 0): ?>
                            <div class="resumen-item descuentos-detalle">
                                <span class="resumen-label">Descuentos:</span>
                            </div>
                            <div class="descuentos-lista">
                                <?php foreach ($promocionesAplicadas as $promocion): ?>
                                    <?php if (is_numeric($promocion['monto']) && $promocion['monto'] > 0): ?>
                                        <div class="descuento-item">
                                            <div class="descuento-nombre">
                                                <?= htmlspecialchars($promocion['nombre']) ?>
                                            </div>
                                            <div class="descuento-monto">
                                                - S/ <?= number_format($promocion['monto'], 2) ?>
                                            </div>
                                        </div>
                                    <?php elseif ($promocion['monto'] === 'Gratis'): ?>
                                        <div class="descuento-item">
                                            <div class="descuento-nombre">
                                                <?= htmlspecialchars($promocion['nombre']) ?>
                                            </div>
                                            <div class="descuento-monto envio-gratis">
                                                Envío Gratis
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($totales['descuento'] > 0): ?>
                            <!-- ✅ Solo mostrar línea simple si hay descuento pero no promociones detalladas -->
                            <div class="resumen-item">
                                <span class="resumen-label">Total Descuento</span>
                                <span class="resumen-valor-descuento" id="resumen-descuento">
                                    - S/ <?= number_format($totales['descuento'] ?? 0, 2) ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['cupon_aplicado']) && !empty($totales['descuento_cupon'])): ?>
                            <div class="resumen-item cupon-aplicado-detalle">
                                <span class="resumen-label">Descuento Cupón</span>
                                <span class="resumen-valor" id="resumen-descuento-cupon">
                                    - S/ <?= number_format($totales['descuento_cupon'], 2) ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Sección de Envío Gratis -->
                        <?php if ($totales['envio_gratis']): ?>
                            <div class="resumen-item envio-gratis-detalle">
                                <span class="resumen-label">Envío:</span>
                            </div>
                            <div class="envio-gratis-info">
                                <?php
                                // Buscar la promoción de envío gratis en las promociones aplicadas
                                $promocion_envio_gratis = null;
                                foreach ($promocionesAplicadas as $promo) {
                                    if (isset($promo['envio_gratis']) && $promo['envio_gratis'] === true) {
                                        $promocion_envio_gratis = $promo;
                                        break;
                                    }
                                }
                                ?>
                                <div class="envio-gratis-item">
                                    <div class="envio-gratis-nombre">
                                        <?= $promocion_envio_gratis ? htmlspecialchars($promocion_envio_gratis['nombre']) : 'Envío Gratis' ?>
                                    </div>
                                    <div class="envio-gratis-monto">
                                        ¡GRATIS!
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="resumen-item total-final">
                            <span class="resumen-label">Total</span>
                            <span class="resumen-valor" id="resumen-total">S/ <?= number_format($totales['total'] ?? 0, 2) ?></span>
                        </div>

                        <!-- Sección de Cupones -->
                        <div class="resumen-item cupon-seccion">
                            <div class="cupon-titulo">
                                ¿Tienes un cupón?
                            </div>
                            <!-- Formulario para manejar el cupón -->
                            <form id="form-cupon" method="POST" action="<?= url('carrito/aplicarCupon') ?>" class="cupon-input-container">
                                <input type="text" name="codigo" id="codigo-cupon" placeholder="Ingresa tu código" value="<?= htmlspecialchars($_SESSION['cupon_aplicado']['codigo'] ?? '') ?>" <?= isset($_SESSION['cupon_aplicado']) ? 'readonly' : '' ?>>
                                <?php if (isset($_SESSION['cupon_aplicado'])): ?>
                                    <a href="<?= url('carrito/quitarCupon') ?>" class="btn-cupon btn-remover">Remover</a>
                                <?php else: ?>
                                    <button type="submit" class="btn-cupon btn-aplicar">Aplicar</button>
                                <?php endif; ?>
                            </form>
                            <div id="mensaje-cupon" class="cupon-mensaje">
                                <?php if (isset($_SESSION['mensaje_cupon_exito'])): ?>
                                    <span class="cupon-exito">✓ <?= htmlspecialchars($_SESSION['mensaje_cupon_exito']) ?></span>
                                    <?php unset($_SESSION['mensaje_cupon_exito']); ?>
                                <?php elseif (isset($_SESSION['mensaje_cupon_error'])): ?>
                                    <span class="cupon-error">✗ <?= htmlspecialchars($_SESSION['mensaje_cupon_error']) ?></span>
                                    <?php unset($_SESSION['mensaje_cupon_error']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="acciones-carrito">
                <a href="<?= url('/') ?>" class="boton-volver">Seguir Comprando</a>
                <?php if ($cantidadEnCarrito > 0): ?>
                    <button class="boton-checkout" id="btn-finalizar-compra">Finalizar Compra</button>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="carrito-vacio">
                <div class="carrito-vacio-icon">
                    <img src="<?= url('image/carrito.svg') ?>" alt="Carrito vacío" style="width: 60px; height: 60px; display: block; margin: 0 auto;">
                </div>

                <h3>Tu carrito está vacío</h3>
                <p>¡Agrega algunos productos para comenzar!</p>
                <a href="<?= url('/') ?>" class="boton-volver">Ir a la tienda</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scripts para AJAX y Cupones -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productosList = document.getElementById('productos-list');
            if (productosList) {
                productosList.addEventListener('click', function(e) {
                    const target = e.target.closest('a[data-clave]');
                    if (!target) return;

                    e.preventDefault();
                    const clave = target.dataset.clave;
                    let url;

                    if (target.classList.contains('btn-aumentar')) url = `<?= url('carrito/aumentar/') ?>${encodeURIComponent(clave)}`;
                    if (target.classList.contains('btn-disminuir')) url = `<?= url('carrito/disminuir/') ?>${encodeURIComponent(clave)}`;
                    if (target.classList.contains('btn-eliminar')) url = `<?= url('carrito/eliminar/') ?>${encodeURIComponent(clave)}`;

                    if (url) realizarPeticionAjax(url, clave);
                });
            }

            async function realizarPeticionAjax(url, clave) {
                const productoItem = document.getElementById(`producto-${clave}`);

                // ✅ MEJORADO: Aplicar opacidad solo si el elemento existe
                if (productoItem) {
                    productoItem.style.opacity = '0.7';
                    productoItem.style.transition = 'opacity 0.3s ease';
                }

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'ajax=true'
                    });

                    console.log('🔍 RESPONSE STATUS:', response.status);
                    console.log('🔍 RESPONSE OK:', response.ok);

                    // ✅ CORREGIDO: Primero obtener como texto para debug
                    const responseText = await response.text();
                    console.log('🔍 RESPONSE TEXT:', responseText);

                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }

                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('La respuesta no es JSON válido. Tipo: ' + contentType);
                    }

                    // ✅ CORREGIDO: Parsear manualmente desde el texto
                    let result;
                    try {
                        result = JSON.parse(responseText);
                        console.log('🔍 JSON PARSEADO:', result);
                    } catch (jsonError) {
                        console.error('❌ Error parseando JSON:', jsonError);
                        throw new Error('Respuesta JSON inválida: ' + responseText.substring(0, 100));
                    }

                    if (result.success) {
                        actualizarVista(result.data);
                    } else {
                        alert(result.message || 'Ocurrió un error');
                        // ✅ MEJORADO: Resetear opacidad en caso de error
                        resetearOpacidadProductos();
                    }
                } catch (error) {
                    console.error('Error AJAX:', error);
                    alert('Error de conexión: ' + error.message);
                    // ✅ MEJORADO: Resetear opacidad en caso de error
                    resetearOpacidadProductos();
                }
            }

            // ✅ NUEVA FUNCIÓN: Resetear opacidad de todos los productos
            function resetearOpacidadProductos() {
                const todosLosProductos = document.querySelectorAll('.producto-item');
                todosLosProductos.forEach(producto => {
                    producto.style.opacity = '1';
                    producto.style.transition = 'opacity 0.3s ease';
                });
            }

            function actualizarVista(data) {
                // ✅ VERIFICAR que data existe y tiene la estructura esperada
                if (!data || typeof data !== 'object') {
                    console.error('❌ DATA inválida en actualizarVista:', data);
                    alert('Error: Datos de respuesta inválidos');
                    resetearOpacidadProductos();
                    return;
                }

                // ✅ VERIFICAR que totals existe
                if (!data.totals) {
                    console.error('❌ DATA.totals no existe:', data);
                    alert('Error: No se recibieron los totales');
                    resetearOpacidadProductos();
                    return;
                }

                const formatter = new Intl.NumberFormat('es-PE', {
                    style: 'currency',
                    currency: 'PEN'
                });

                // ✅ ACTUALIZAR TOTALES CON VERIFICACIONES
                const subtotalEl = document.getElementById('resumen-subtotal');
                const descuentoEl = document.getElementById('resumen-descuento');
                const totalEl = document.getElementById('resumen-total');

                if (subtotalEl) {
                    subtotalEl.textContent = formatter.format(data.totals.subtotal || 0);
                }

                if (descuentoEl) {
                    descuentoEl.textContent = formatter.format(data.totals.descuento || 0);
                }

                if (totalEl) {
                    totalEl.textContent = formatter.format(data.totals.total || 0);
                }

                console.log('🔍 PROMOCIONES RECIBIDAS:', data.promotions);
                console.log('🔍 TOTALES RECIBIDOS:', data.totals);

                // ✅ ACTUALIZAR PROMOCIONES CON VERIFICACIÓN
                if (data.promotions) {
                    actualizarPromociones(data.promotions, data.totals);
                } else {
                    // ✅ Si no hay promociones, forzar la actualización con array vacío
                    actualizarPromociones([], data.totals);
                }

                // ✅ MANEJAR DESCUENTO DE CUPÓN CON VERIFICACIONES
                if (data.totals.descuento_cupon > 0) {
                    let cuponItem = document.querySelector('.cupon-aplicado-detalle');
                    const totalFinal = document.querySelector('.total-final');

                    if (!cuponItem && totalFinal) {
                        cuponItem = document.createElement('div');
                        cuponItem.className = 'resumen-item cupon-aplicado-detalle';
                        cuponItem.innerHTML = `
                <span class="resumen-label">Descuento Cupón</span>
                <span class="resumen-valor" id="resumen-descuento-cupon">
                    - ${formatter.format(data.totals.descuento_cupon)}
                </span>
            `;
                        totalFinal.parentNode.insertBefore(cuponItem, totalFinal);
                    } else if (cuponItem) {
                        const descuentoCuponEl = document.getElementById('resumen-descuento-cupon');
                        if (descuentoCuponEl) {
                            descuentoCuponEl.textContent = `- ${formatter.format(data.totals.descuento_cupon)}`;
                        }
                    }
                } else {
                    const cuponItem = document.querySelector('.cupon-aplicado-detalle');
                    if (cuponItem) {
                        cuponItem.remove();
                    }
                }

                // ✅ RESETEAR OPACIDAD DE PRODUCTOS
                resetearOpacidadProductos();

                // ✅ ACTUALIZAR PRODUCTOS EXISTENTES CON VERIFICACIONES
                let itemsEnRespuesta = new Set();
                if (data.itemDetails) {
                    for (const clave in data.itemDetails) {
                        const item = data.itemDetails[clave];
                        itemsEnRespuesta.add(clave);

                        const cantidadEl = document.getElementById(`cantidad-${clave}`);
                        const subtotalEl = document.getElementById(`subtotal-${clave}`);
                        const productoItem = document.getElementById(`producto-${clave}`);

                        if (cantidadEl) cantidadEl.textContent = item.cantidad;
                        if (subtotalEl) subtotalEl.textContent = formatter.format(item.subtotal);
                        if (productoItem) {
                            productoItem.style.opacity = '1';
                        }
                    }
                }

                // ✅ ELIMINAR PRODUCTOS QUE YA NO ESTÁN CON VERIFICACIONES
                const todosLosItemsEnDOM = document.querySelectorAll('.producto-item');
                let itemsAEliminar = [];

                todosLosItemsEnDOM.forEach(itemEl => {
                    const claveItem = itemEl.id.replace('producto-', '');
                    if (!itemsEnRespuesta.has(claveItem)) {
                        itemsAEliminar.push(itemEl);
                    }
                });

                // ✅ ANIMACIÓN DE ELIMINACIÓN CON VERIFICACIONES
                if (itemsAEliminar.length > 0) {
                    itemsAEliminar.forEach(itemEl => {
                        if (itemEl && itemEl.parentNode) {
                            itemEl.style.transition = 'all 0.4s ease';
                            itemEl.style.opacity = '0';
                            itemEl.style.maxHeight = itemEl.scrollHeight + 'px';

                            setTimeout(() => {
                                itemEl.style.maxHeight = '0px';
                                itemEl.style.paddingTop = '0';
                                itemEl.style.paddingBottom = '0';
                                itemEl.style.marginTop = '0';
                                itemEl.style.marginBottom = '0';
                                itemEl.style.overflow = 'hidden';
                            }, 50);

                            setTimeout(() => {
                                if (itemEl.parentNode) {
                                    itemEl.parentNode.removeChild(itemEl);
                                }
                            }, 450);
                        }
                    });
                }

                // ✅ VERIFICACIÓN FINAL: recargar si el carrito queda vacío
                setTimeout(() => {
                    const productosRestantes = document.querySelectorAll('.producto-item').length;
                    const itemDetailsCount = (data.itemDetails && Object.keys(data.itemDetails).length) || 0;
                    const serverIndicaVacio = (data.itemCount === 0) || (data.items && data.items.length === 0);
                    const totalsZero = data.totals && data.totals.total === 0;

                    if (productosRestantes === 0 || itemDetailsCount === 0 || serverIndicaVacio || totalsZero) {
                        window.location.reload();
                    }
                }, 500);
            }
        });
        // ✅ AGREGAR ESTE NUEVO CÓDIGO PARA USAR EL MODAL DEL HEADER

        document.addEventListener('DOMContentLoaded', function() {
            const btnFinalizar = document.getElementById('btn-finalizar-compra');

            if (btnFinalizar) {
                btnFinalizar.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Abrir el modal del header directamente
                    const loginModal = document.getElementById('loginModal');
                    const openLoginBtn = document.getElementById('openLoginModalBtn');

                    if (openLoginBtn) {
                        openLoginBtn.click(); // Simular click en el botón de login del header
                    } else if (loginModal) {
                        // Fallback: abrir modal directamente si existe
                        loginModal.classList.add('open');
                        loginModal.setAttribute('aria-hidden', 'false');
                        document.body.style.overflow = 'hidden';
                    }
                });
            }
        });

        function actualizarPromociones(promotions, totals) {
            console.log('🎯 ACTUALIZAR PROMOCIONES - Promotions:', promotions, 'Totals:', totals);

            // ✅ VERIFICAR que los parámetros existen
            if (!promotions || !totals) {
                console.error('❌ Parámetros inválidos en actualizarPromociones');
                return;
            }

            const formatter = new Intl.NumberFormat('es-PE', {
                style: 'currency',
                currency: 'PEN'
            });

            // Buscar el contenedor de descuentos detallados
            let descuentosDetalle = document.querySelector('.descuentos-detalle');
            // ✅ SOLUCIÓN: Buscar específicamente la línea de descuento simple
            const descuentoSimple = document.querySelector('.resumen-item .resumen-label');
            let elementoDescuentoSimple = null;

            if (descuentoSimple && descuentoSimple.textContent.trim() === 'Descuento') {
                elementoDescuentoSimple = descuentoSimple.parentElement;
            }
            // ✅ ENCONTRAR el elemento correcto para insertar después (el subtotal)
            const subtotalItem = document.querySelector('.resumen-item .resumen-label');
            let elementoSubtotal = null;

            if (subtotalItem && subtotalItem.textContent === 'Subtotal') {
                elementoSubtotal = subtotalItem.parentElement;
            }

            // Verificar si hay promociones para mostrar
            const tienePromociones = promotions && promotions.length > 0;
            const tieneDescuento = totals.descuento > 0;

            if (tienePromociones && tieneDescuento) {
                // Crear o actualizar la sección de descuentos detallados
                if (!descuentosDetalle && elementoSubtotal) {
                    // Crear nueva sección DESPUÉS del subtotal
                    descuentosDetalle = document.createElement('div');
                    descuentosDetalle.className = 'resumen-item descuentos-detalle';
                    descuentosDetalle.innerHTML = `
                        <span class="resumen-label">Descuentos:</span>
                    `;

                    // ✅ VERIFICAR SI YA EXISTE UN CONTENEDOR .descuentos-lista
                    let descuentosListaContainer = document.querySelector('.descuentos-lista');

                    if (!descuentosListaContainer) {
                        // Solo crear uno nuevo si no existe
                        descuentosListaContainer = document.createElement('div');
                        descuentosListaContainer.className = 'descuentos-lista';
                    }

                    // ✅ VERIFICAR QUE EL PARENTNODE EXISTE
                    if (elementoSubtotal.parentNode) {
                        elementoSubtotal.parentNode.insertBefore(descuentosDetalle, elementoSubtotal.nextSibling);

                        // Solo insertar el contenedor si no estaba ya en el DOM
                        if (!descuentosListaContainer.parentNode) {
                            descuentosDetalle.parentNode.insertBefore(descuentosListaContainer, descuentosDetalle.nextSibling);
                        }
                    }
                }

                // ✅ Asegurarse de que el subtotal esté visible
                if (elementoSubtotal) {
                    elementoSubtotal.style.display = 'flex';
                }

                // ✅ OCULTAR completamente la línea de "Descuento" simple
                if (descuentoSimple && descuentoSimple.textContent === 'Descuento') {
                    descuentoSimple.parentElement.style.display = 'none';
                }

                // ✅ ACTUALIZAR LISTA SOLO SI descuentosDetalle EXISTE
                if (descuentosDetalle) {
                    // ✅ BUSCAR EL CONTENEDOR DE LISTA CORRECTO (FUERA del descuentosDetalle)
                    const descuentosLista = document.querySelector('.descuentos-lista');

                    // ✅ VERIFICAR QUE descuentosLista EXISTE ANTES de usar innerHTML
                    if (descuentosLista) {
                        descuentosLista.innerHTML = '';

                        promotions.forEach(promocion => {
                            const descuentoItem = document.createElement('div');
                            descuentoItem.className = 'descuento-item';

                            if (isNumeric(promocion.monto) && promocion.monto > 0) {
                                descuentoItem.innerHTML = `
                        <div class="descuento-nombre">${escapeHtml(promocion.nombre)}</div>
                        <div class="descuento-monto">- ${formatter.format(promocion.monto)}</div>
                    `;
                            } else if (promocion.monto === 'Gratis') {
                                descuentoItem.innerHTML = `
                        <div class="descuento-nombre">${escapeHtml(promocion.nombre)}</div>
                        <div class="descuento-monto envio-gratis">Envío Gratis</div>
                    `;
                            }

                            descuentosLista.appendChild(descuentoItem);
                        });
                    }

                    // ✅ CAMBIAR: Usar 'block' en lugar de 'flex' para coincidir con el CSS
                    descuentosDetalle.style.display = 'block';
                }
            } else {
                // ✅ ELIMINAR completamente la sección detallada si existe
                const descuentosDetalleExistente = document.querySelector('.descuentos-detalle');
                if (descuentosDetalleExistente) {
                    descuentosDetalleExistente.remove();
                }
                // ✅ ELIMINAR también el contenedor de lista si existe
                const descuentosListaExistente = document.querySelector('.descuentos-lista');
                if (descuentosListaExistente) {
                    descuentosListaExistente.remove();
                }

                // ✅ Asegurarse de que el subtotal esté visible
                if (elementoSubtotal) {
                    elementoSubtotal.style.display = 'flex';
                }

                // ✅ BUSCAR Y MANEJAR LA LÍNEA SIMPLE DE DESCUENTO
                const descuentoSimple = document.querySelector('.resumen-item .resumen-label');
                let elementoDescuentoSimple = null;

                if (descuentoSimple && descuentoSimple.textContent.trim() === 'Descuento') {
                    elementoDescuentoSimple = descuentoSimple.parentElement;
                }

                if (elementoDescuentoSimple) {
                    if (totals.descuento > 0) {
                        elementoDescuentoSimple.style.display = 'flex';
                        // Actualizar el valor del descuento simple
                        const valorDescuento = elementoDescuentoSimple.querySelector('.resumen-valor-descuento');
                        if (valorDescuento) {
                            valorDescuento.textContent = formatter.format(totals.descuento);
                        }
                    } else {
                        // ✅ SI NO HAY DESCUENTO, OCULTAR COMPLETAMENTE
                        elementoDescuentoSimple.style.display = 'none';
                    }
                }
            }
        }

        // Función auxiliar para verificar si un valor es numérico
        function isNumeric(value) {
            return !isNaN(parseFloat(value)) && isFinite(value);
        }

        // Función auxiliar para escapar HTML
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
    <?php include_once __DIR__ . '/../admin/includes/footer.php'; ?>
</body>

</html>