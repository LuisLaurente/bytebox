<?php if (!empty($_SESSION['flash'])): ?>
    <div id="flashMessage" class="_vistapedido-flash-message">
        <?= $_SESSION['flash'] ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Modal de confirmación de pago pendiente -->
<?php if (isset($showPaymentModal) && $showPaymentModal && !empty($externalReference)): ?>
<div id="paymentModal" class="modal" style="display: block; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; text-align: center; max-width: 400px; width: 90%; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="color: #ffa500; font-size: 48px; margin-bottom: 15px;">⏳</div>
        <h3 style="color: #e67e22; margin-bottom: 15px; font-size: 1.5rem;">¡Pedido en Proceso!</h3>
        <p style="margin-bottom: 15px; line-height: 1.5;">
            Hemos recibido tu solicitud de pago. Estamos esperando la confirmación.
        </p>
        <p style="margin-bottom: 20px; font-size: 14px; color: #666; line-height: 1.4;">
            Te notificaremos cuando el pago sea confirmado y procesemos tu pedido.
        </p>
        <p style="font-size: 12px; color: #888; margin-bottom: 20px; padding: 8px; background: #f8f9fa; border-radius: 4px;">
            N° de pedido: <strong><?= htmlspecialchars($externalReference) ?></strong>
        </p>
        <button onclick="closeModal()" style="padding: 10px 25px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; transition: background 0.3s;">Entendido</button>
    </div>
</div>

<script>
function closeModal() {
    const modal = document.getElementById('paymentModal');
    if (modal) {
        modal.style.display = 'none';
    }
    // Limpiar parámetros de la URL sin recargar
    const url = new URL(window.location);
    url.searchParams.delete('payment_status');
    url.searchParams.delete('external_ref');
    window.history.replaceState({}, '', url);
}

// Cerrar modal al hacer click fuera del contenido
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target.id === 'paymentModal') {
        closeModal();
    }
});

// Cerrar modal con ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/usuarioPedidos.css') ?>">

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const flash = document.getElementById("flashMessage");
        if (flash) {
            setTimeout(() => {
                flash.style.transition = "opacity 0.5s ease";
                flash.style.opacity = "0";
                setTimeout(() => flash.remove(), 500);
            }, 2000);
        }
    });
</script>

<body>
    <div class="_vistapedido-layout">
        <div class="_vistapedido-main-content">
            <div class="_vistapedido-header-sticky">
                <?php include_once __DIR__ . '/../admin/includes/header.php'; ?>
            </div>

            <main class="_vistapedido-content">
                <div class="_vistapedido-pedidos-container">
                    <!-- Header -->
                    <div class="_vistapedido-pedidos-header">
                        <div class="_vistapedido-header-content">
                            <div class="_vistapedido-header-text">
                                <h1>Mis Pedidos</h1>
                            </div>
                            <div class="_vistapedido-header-actions">
                                <a href="<?= url('/home/busqueda') ?>" class="_vistapedido-btn-primary">
                                    <span class="_vistapedido-btn-icon"></span>
                                    Seguir Comprando
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Pedidos -->
                    <div class="_vistapedido-pedidos-list">
                        <div class="_vistapedido-list-header">
                            <h2>Historial de Pedidos</h2>
                        </div>

                        <?php if (empty($pedidos)): ?>
                            <div class="_vistapedido-empty-state">
                                <div class="_vistapedido-empty-icon"></div>
                                <h3>Sin pedidos aún</h3>
                                <p>¡Comienza a explorar nuestros productos y realiza tu primera compra!</p>
                                <a href="<?= url('/producto/index') ?>" class="_vistapedido-btn-primary">
                                    <span class="_vistapedido-btn-icon">🛒</span>
                                    Explorar Productos
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="_vistapedido-pedidos-grid">
                                <?php foreach ($pedidos as $pedidozz): ?>
                                    <div class="_vistapedido-pedido-card">
                                        <div class="_vistapedido-pedido-header">
                                            <div class="_vistapedido-pedido-info">
                                                <h3>Pedido N° <?= $pedidozz['id'] ?></h3>
                                                <p class="_vistapedido-pedido-date"><?= date('d/m/Y H:i', strtotime($pedidozz['creado_en'])) ?></p>
                                            </div>
                                            <!-- NUEVA SECCIÓN: Productos con imágenes -->
                                            <div class="_vistapedido-pedido-productos">
                                                <?php if (isset($pedidozz['detalles']) && is_array($pedidozz['detalles'])): ?>
                                                    <?php foreach ($pedidozz['detalles'] as $detalle): ?>
                                                        <div class="_vistapedido-producto-item">
                                                            <?php if (!empty($detalle['imagen_producto'])): ?>
                                                                <img src="<?= url('uploads/' . $detalle['imagen_producto']) ?>"
                                                                    alt="<?= htmlspecialchars($detalle['producto_nombre'] ?? 'Producto') ?>"
                                                                    class="_vistapedido-producto-imagen">
                                                            <?php else: ?>
                                                                <div class="_vistapedido-producto-imagen _vistapedido-placeholder"></div>
                                                            <?php endif; ?>
                                                            <div class="_vistapedido-producto-info">
                                                                <span class="_vistapedido-producto-nombre"><?= htmlspecialchars($detalle['producto_nombre'] ?? 'Producto') ?></span>
                                                                <span class="_vistapedido-producto-cantidad">(x<?= $detalle['cantidad'] ?? 1 ?>)</span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <p class="_vistapedido-no-productos">No hay productos disponibles</p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="_vistapedido-pedido-actions">
                                                <button onclick="mostrarDetallePedido(<?= $pedidozz['id'] ?>)" class="_vistapedido-btn-details">
                                                    Ver detalles
                                                </button>
                                                <div class="_vistapedido-pedido-total">
                                                    <?php
                                                    $subtotal = $pedidozz['subtotal'] ?? 0;
                                                    $descuento_cupon = $pedidozz['descuento_cupon'] ?? 0;
                                                    $descuento_promocion = $pedidozz['descuento_promocion'] ?? 0;
                                                    $costo_envio = $pedidozz['costo_envio'] ?? 0;
                                                    $cupon_codigo = $pedidozz['cupon_codigo'] ?? null;

                                                    if ($subtotal > 0) {
                                                        $totalPedido = $subtotal - $descuento_cupon - $descuento_promocion + $costo_envio;
                                                    } else {
                                                        $totalPedido = $pedidozz['total'] ?? $pedidozz['monto_total'] ?? 0;
                                                        if ($totalPedido == 0 && isset($pedidozz['detalles']) && is_array($pedidozz['detalles'])) {
                                                            foreach ($pedidozz['detalles'] as $detalle) {
                                                                $precio = floatval($detalle['precio_unitario'] ?? 0);
                                                                $cantidad = intval($detalle['cantidad'] ?? 0);
                                                                $totalPedido += $precio * $cantidad;
                                                            }
                                                            $totalPedido += $costo_envio;
                                                        } else {
                                                            $totalPedido += $costo_envio;
                                                        }
                                                    }

                                                    if ($subtotal > 0 && ($descuento_cupon > 0 || $descuento_promocion > 0 || $costo_envio > 0)): ?>
                                                        <div class="_vistapedido-price-breakdown">
                                                            <div>Subtotal: S/ <?= number_format($subtotal, 2) ?></div>
                                                            <?php if ($costo_envio > 0): ?>
                                                                <div class="_vistapedido-shipping-cost">Envío: S/ <?= number_format($costo_envio, 2) ?></div>
                                                            <?php endif; ?>
                                                            <?php if ($descuento_promocion > 0): ?>
                                                                <div class="_vistapedido-promo-discount">Desc. Promoción: -S/ <?= number_format($descuento_promocion, 2) ?></div>
                                                            <?php endif; ?>
                                                            <?php if ($descuento_cupon > 0 && $cupon_codigo): ?>
                                                                <div class="_vistapedido-coupon-discount">Cupón <?= htmlspecialchars($cupon_codigo) ?>: -S/ <?= number_format($descuento_cupon, 2) ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <p class="_vistapedido-total-amount">S/ <?= number_format($totalPedido, 2) ?></p>
                                                    <span class="_vistapedido-status-badge _vistapedido-status-<?= $pedidozz['estado'] ?>">
                                                        <?= ucfirst($pedidozz['estado']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($pedidozz['estado'] === 'entregado'): ?>
                                            <div class="_vistapedido-rating-section">
                                                <button
                                                    onclick="abrirModalComentario(<?= $pedidozz['id'] ?>)"
                                                    class="_vistapedido-rating-button">
                                                    <div class="_vistapedido-stars">
                                                        <span>★</span>
                                                        <span>★</span>
                                                        <span>★</span>
                                                        <span>★</span>
                                                        <span>★</span>
                                                    </div>
                                                    <span class="_vistapedido-rating-text">Califica tu compra</span>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para mostrar detalles del pedido -->
    <div id="modalDetallePedido" class="_vistapedido-modal _vistapedido-hidden">
        <div class="_vistapedido-modal-content">
            <div class="_vistapedido-modal-header">
                <h2>Detalles del Pedido</h2>
                <button onclick="cerrarModal()" class="_vistapedido-modal-close">×</button>
            </div>

            <div id="modalContenido" class="_vistapedido-modal-body">
                <div class="_vistapedido-loading">
                    <div class="_vistapedido-spinner"></div>
                    <p>Cargando detalles...</p>
                </div>
            </div>

            <div class="_vistapedido-modal-footer">
                <button onclick="cerrarModal()" class="_vistapedido-btn-secondary">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Comentario -->
    <div id="modalComentario" class="_vistapedido-modal _vistapedido-hidden">
        <div class="_vistapedido-modal-content _vistapedido-small">
            <div class="_vistapedido-modal-header">
                <h2>Dejar un comentario</h2>
                <button onclick="cerrarModalComentario()" class="_vistapedido-modal-close">×</button>
            </div>

            <form id="formComentario" class="formDeComentario" action="<?= url('producto/guardarComentario') ?>" method="post">
                <input type="hidden" name="orden_id" id="inputOrdenId">

                <div id="productoSelectWrapper" class="_vistapedido-form-group _vistapedido-hidden">
                    <label>Producto:</label>
                    <select id="selectProducto"></select>
                </div>

                <input type="hidden" name="producto_id" id="inputProductoIdHidden">
                <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">

                <div class="_vistapedido-form-group">
                    <label for="inputPuntuacion">Puntuación:</label>
                    <div class="_vistapedido-star-rating" id="starRating">
                        <span data-value="1" class="_vistapedido-star">★</span>
                        <span data-value="2" class="_vistapedido-star">★</span>
                        <span data-value="3" class="_vistapedido-star">★</span>
                        <span data-value="4" class="_vistapedido-star">★</span>
                        <span data-value="5" class="_vistapedido-star">★</span>
                    </div>
                    <input type="hidden" name="puntuacion" id="inputPuntuacion">
                </div>

                <div class="_vistapedido-form-group">
                    <label>Descripción:</label>
                    <input type="text" name="titulo" required>
                </div>

                <div class="_vistapedido-form-group">
                    <label>Comentario:</label>
                    <textarea name="texto" rows="4" required></textarea>
                </div>

                <button type="submit" class="_vistapedido-btn-primary _vistapedido-full-width">
                    Enviar
                </button>
            </form>
        </div>
    </div>

    <script>
        const pedidosData = <?= json_encode($pedidos) ?>;

        function mostrarDetallePedido(pedidoId) {
            const pedido = pedidosData.find(p => p.id == pedidoId);
            if (!pedido) {
                cargarDetallePedidoAjax(pedidoId);
                return;
            }
            mostrarModalConDatos(pedido);
        }

        function mostrarModalConDatos(pedido) {
            document.getElementById('modalDetallePedido').classList.remove('_vistapedido-hidden');
            const contenido = generarContenidoDetalle(pedido);
            document.getElementById('modalContenido').innerHTML = contenido;
        }

        function cargarDetallePedidoAjax(pedidoId) {
            document.getElementById('modalDetallePedido').classList.remove('_vistapedido-hidden');
            document.getElementById('modalContenido').innerHTML = `
                <div class="_vistapedido-loading">
                    <div class="_vistapedido-spinner"></div>
                    <p>Cargando detalles...</p>
                </div>
            `;

            fetch('<?= url("/usuario/detallePedido/") ?>' + pedidoId, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const contenido = generarContenidoDetalle(data.pedido);
                        document.getElementById('modalContenido').innerHTML = contenido;
                    } else {
                        document.getElementById('modalContenido').innerHTML = `
                        <div class="_vistapedido-error-state">
                            <div class="_vistapedido-error-icon">⚠️</div>
                            <p class="_vistapedido-error-message">Error al cargar detalles</p>
                            <p class="_vistapedido-error-details">${data.error || 'Error desconocido'}</p>
                        </div>
                    `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('modalContenido').innerHTML = `
                    <div class="_vistapedido-error-state">
                        <div class="_vistapedido-error-icon">❌</div>
                        <p class="_vistapedido-error-message">Error de conexión</p>
                        <p class="_vistapedido-error-details">No se pudo cargar la información</p>
                    </div>
                `;
                });
        }

        function cerrarModal() {
            document.getElementById('modalDetallePedido').classList.add('_vistapedido-hidden');
        }

        function generarContenidoDetalle(pedido) {
            const estadoClass = `_vistapedido-status-${pedido.estado}`;
            const fechaCreacion = new Date(pedido.creado_en).toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const subtotal = parseFloat(pedido.subtotal || 0);
            const descuentoCupon = parseFloat(pedido.descuento_cupon || 0);
            const descuentoPromocion = parseFloat(pedido.descuento_promocion || 0);
            const costoEnvio = parseFloat(pedido.costo_envio || 0);

            let totalCalculado;
            if (subtotal > 0) {
                totalCalculado = subtotal - descuentoCupon - descuentoPromocion + costoEnvio;
            } else {
                if (descuentoCupon > 0 || descuentoPromocion > 0) {
                    const totalDetalle = pedido.detalles && pedido.detalles.length > 0 ?
                        pedido.detalles.reduce((sum, detalle) => {
                            const precio = parseFloat(detalle.precio_unitario || 0);
                            const cantidad = parseInt(detalle.cantidad || 0);
                            return sum + (precio * cantidad);
                        }, 0) :
                        parseFloat(pedido.total || pedido.monto_total || 0);
                    totalCalculado = totalDetalle - descuentoCupon - descuentoPromocion + costoEnvio;
                } else {
                    totalCalculado = parseFloat(pedido.total || pedido.monto_total || 0);
                    if (totalCalculado === 0 && pedido.detalles && pedido.detalles.length > 0) {
                        const totalDetalleCalculado = pedido.detalles.reduce((sum, detalle) => {
                            const precio = parseFloat(detalle.precio_unitario || 0);
                            const cantidad = parseInt(detalle.cantidad || 0);
                            return sum + (precio * cantidad);
                        }, 0);
                        totalCalculado = totalDetalleCalculado + costoEnvio;
                    }
                }
            }
            const cuponCodigo = pedido.cupon_codigo || null;

            let desgloseHtml = '';
            if (descuentoCupon > 0 || descuentoPromocion > 0 || costoEnvio > 0) {
                const subtotalParaMostrar = subtotal > 0 ? subtotal :
                    (pedido.detalles && pedido.detalles.length > 0 ?
                        pedido.detalles.reduce((sum, detalle) => {
                            const precio = parseFloat(detalle.precio_unitario || 0);
                            const cantidad = parseInt(detalle.cantidad || 0);
                            return sum + (precio * cantidad);
                        }, 0) :
                        parseFloat(pedido.total || pedido.monto_total || 0));

                desgloseHtml = `
                    <div class="_vistapedido-price-breakdown-modal">
                        <h4> Desglose de Precios</h4>
                        <div class="_vistapedido-price-items">
                            <div class="_vistapedido-price-item">
                                <span>Subtotal:</span>
                                <span>S/ ${subtotalParaMostrar.toFixed(2)}</span>
                            </div>
                            ${costoEnvio > 0 ? `
                                <div class="_vistapedido-price-item _vistapedido-shipping">
                                    <span>Costo de Envío:</span>
                                    <span>S/ ${costoEnvio.toFixed(2)}</span>
                                </div>
                            ` : ''}
                            ${descuentoPromocion > 0 ? `
                                <div class="_vistapedido-price-item _vistapedido-discount">
                                    <span>Descuento Promoción:</span>
                                    <span>-S/ ${descuentoPromocion.toFixed(2)}</span>
                                </div>
                            ` : ''}
                            ${descuentoCupon > 0 && cuponCodigo ? `
                                <div class="_vistapedido-price-item _vistapedido-coupon">
                                    <span>Cupón ${cuponCodigo}:</span>
                                    <span>-S/ ${descuentoCupon.toFixed(2)}</span>
                                </div>
                            ` : ''}
                            <div class="_vistapedido-price-divider"></div>
                            <div class="_vistapedido-price-item _vistapedido-total">
                                <span>Total Final:</span>
                                <span>S/ ${totalCalculado.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                `;
            }

            let productosHtml = '';
            if (pedido.detalles && pedido.detalles.length > 0) {
                const subtotalTotalProductos = pedido.detalles.reduce((sum, detalle) => {
                    const precio = parseFloat(detalle.precio_unitario || 0);
                    const cantidad = parseInt(detalle.cantidad || 0);
                    return sum + (precio * cantidad);
                }, 0);

                productosHtml = pedido.detalles.map(detalle => {
                    const precioUnitario = parseFloat(detalle.precio_unitario || 0);
                    const cantidad = parseInt(detalle.cantidad || 0);
                    const subtotalProducto = precioUnitario * cantidad;

                    let precioFinalProducto = subtotalProducto;
                    if ((descuentoCupon > 0 || descuentoPromocion > 0) && subtotalTotalProductos > 0) {
                        const porcentajeProducto = subtotalProducto / subtotalTotalProductos;
                        const descuentoTotalAplicable = descuentoCupon + descuentoPromocion;
                        const descuentoProducto = descuentoTotalAplicable * porcentajeProducto;
                        precioFinalProducto = subtotalProducto - descuentoProducto;
                    }

                    return `
                        <div class="_vistapedido-product-item">
                            <div class="_vistapedido-product-image-container">
                                ${detalle.imagen_producto ? 
                                    `<img src="<?= url('uploads/') ?>${detalle.imagen_producto}" 
                                        alt="${detalle.producto_nombre || 'Producto'}" 
                                        class="_vistapedido-modal-product-image">` :
                                    `<div class="_vistapedido-modal-product-image _vistapedido-placeholder"></div>`
                                }
                            </div>
                            <div class="_vistapedido-product-info">
                                <h4>${detalle.producto_nombre || 'Producto sin nombre'}</h4>
                                <p class="_vistapedido-product-details">
                                    Cantidad: ${cantidad} × S/ ${precioUnitario.toFixed(2)}
                                    ${((descuentoCupon > 0 || descuentoPromocion > 0) && subtotalTotalProductos > 0) ? 
                                        `<br><span class="_vistapedido-discount-applied">(con descuento aplicado)</span>` : ''}
                                </p>
                                ${(detalle.variante_talla || detalle.variante_color) ? 
                                    `<p class="_vistapedido-variant-info">
                                        ${detalle.variante_talla ? `Talla: ${detalle.variante_talla}` : ''}
                                        ${(detalle.variante_talla && detalle.variante_color) ? ' - ' : ''}
                                        ${detalle.variante_color ? `Color: ${detalle.variante_color}` : ''}
                                    </p>` : ''}
                            </div>
                            <div class="_vistapedido-product-price">
                                ${((descuentoCupon > 0 || descuentoPromocion > 0) && subtotalTotalProductos > 0) ? 
                                    `<p class="_vistapedido-original-price">S/ ${subtotalProducto.toFixed(2)}</p>` : ''}
                                <p class="_vistapedido-final-price">
                                    S/ ${precioFinalProducto.toFixed(2)}
                                </p>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                productosHtml = '<p class="_vistapedido-no-products">No hay detalles de productos disponibles</p>';
            }

            return `
            <div class="_vistapedido-modal-grid">
                <div class="_vistapedido-modal-column">
                    <div class="_vistapedido-info-card _vistapedido-general">
                        <h3>Información General</h3>
                        <div class="_vistapedido-info-items">
                            <div class="_vistapedido-info-item">
                                <span>Fecha:</span>
                                <span>${fechaCreacion}</span>
                            </div>
                            <div class="_vistapedido-info-item">
                                <span>Estado:</span>
                                <span class="_vistapedido-status-badge ${estadoClass}">
                                    ${pedido.estado.charAt(0).toUpperCase() + pedido.estado.slice(1)}
                                </span>
                            </div>
                            ${costoEnvio > 0 ? `
                                <div class="_vistapedido-info-item">
                                    <span>Costo de Envío:</span>
                                    <span>S/ ${costoEnvio.toFixed(2)}</span>
                                </div>
                            ` : ''}
                            <div class="_vistapedido-info-item">
                                <span>Total:</span>
                                <span>S/ ${totalCalculado.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>

                    <div class="_vistapedido-info-card _vistapedido-address">
                        <h3>Dirección de Envío</h3>
                        <p>${pedido.direccion_envio || 'Dirección no disponible'}</p>
                    </div>
                    
                    
                </div>

                <div class="_vistapedido-modal-column">
                    <h3>Productos (${pedido.detalles ? pedido.detalles.length : 0})</h3>
                    <div class="_vistapedido-products-list">
                        ${productosHtml}
                    </div>
                    ${pedido.detalles && pedido.detalles.length > 0 ? `
                    ${desgloseHtml}
                    <div class="_vistapedido-modal-footer-section">
                        <div class="_vistapedido-total-section">
                            <span>Total del Pedido:</span>
                            <span>S/ ${totalCalculado.toFixed(2)}</span>
                        </div>
                    </div>
                ` : ''}
                </div>
            </div>
        `;
        }

        document.getElementById('modalDetallePedido').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });

        function resetStars() {
            const stars = document.querySelectorAll('#starRating ._vistapedido-star');
            stars.forEach(s => {
                s.classList.remove('_vistapedido-active');
            });
            const input = document.getElementById('inputPuntuacion');
            if (input) input.value = '';
        }

        document.addEventListener('click', function(e) {
            const star = e.target.closest('#starRating ._vistapedido-star');
            if (!star) return;

            const value = Number(star.getAttribute('data-value')) || 0;
            const input = document.getElementById('inputPuntuacion');
            if (input) input.value = value;

            const stars = document.querySelectorAll('#starRating ._vistapedido-star');
            stars.forEach(s => {
                const v = Number(s.getAttribute('data-value')) || 0;
                if (v <= value) {
                    s.classList.add('_vistapedido-active');
                } else {
                    s.classList.remove('_vistapedido-active');
                }
            });
        });

        function abrirModalComentario(ordenId) {
            const pedido = pedidosData.find(p => p.id == ordenId);
            if (!pedido) {
                console.error('Pedido no encontrado:', ordenId);
                return;
            }

            const productos = pedido.detalles || [];
            document.getElementById("inputOrdenId").value = ordenId;
            resetStars();

            if (productos.length === 1) {
                document.getElementById("productoSelectWrapper").classList.add("_vistapedido-hidden");
                document.getElementById("inputProductoIdHidden").value = productos[0].producto_id;
            } else {
                document.getElementById("productoSelectWrapper").classList.remove("_vistapedido-hidden");
                let select = document.getElementById("selectProducto");
                select.innerHTML = "";

                productos.forEach(p => {
                    let opt = document.createElement("option");
                    opt.value = p.producto_id;
                    opt.textContent = p.producto_nombre || "Producto " + p.producto_id;
                    select.appendChild(opt);
                });

                document.getElementById("inputProductoIdHidden").value = productos[0]?.producto_id || '';
                select.addEventListener("change", function() {
                    document.getElementById("inputProductoIdHidden").value = this.value;
                });
            }

            document.getElementById("modalComentario").classList.remove("_vistapedido-hidden");
        }

        function cerrarModalComentario() {
            document.getElementById("modalComentario").classList.add("_vistapedido-hidden");
        }
    </script>
</body>

</html>