<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/verPedido.css') ?>">

<body>
    <?php
    $estados = ['pendiente', 'procesando', 'enviado', 'entregado', 'cancelado'];
    ?>

    <div class="_verPedido-admin-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

        <div class="_verPedido-main-content">
            <div class="_verPedido-content">
                <a href="<?= url('pedido/listar') ?>" class="_verPedido-back-button">
                    <i class="fas fa-arrow-left"></i> Volver al listado
                </a>

                <div class="_verPedido-container">
                    <div class="_verPedido-header">
                        <h1 class="_verPedido-title">Detalle del Pedido #<?= $pedido['id'] ?></h1>
                        <p class="_verPedido-subtitle">Información completa y gestión del pedido</p>
                    </div>

                    <!-- Sección principal: Productos y Costos -->
                    <div class="_verPedido-main-section">
                        <!-- Productos -->
                        <div class="_verPedido-products-panel">
                            <div class="_verPedido-info-block">
                                <h3 class="_verPedido-block-title">
                                    <i class="fas fa-shopping-bag"></i> Productos del Pedido
                                </h3>
                                <div class="_verPedido-block-content">
                                    <div class="_verPedido-table-container">
                                        <table class="_verPedido-products-table">
                                            <thead class="_verPedido-table-header">
                                                <tr>
                                                    <th class="_verPedido-table-head">Producto</th>
                                                    <th class="_verPedido-table-head">Variante</th>
                                                    <th class="_verPedido-table-head">Cantidad</th>
                                                    <th class="_verPedido-table-head">Precio Unitario</th>
                                                    <th class="_verPedido-table-head">Descuento</th>
                                                    <th class="_verPedido-table-head">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody class="_verPedido-table-body">
                                                <?php
                                                $subtotal_total = 0;
                                                foreach ($detalles as $item):
                                                    $subtotal_item = $item['precio_unitario'] * $item['cantidad'];
                                                    $descuento_item = $item['descuento_aplicado'] ?? 0;
                                                    $subtotal_final = $subtotal_item - $descuento_item;
                                                    $subtotal_total += $subtotal_final;
                                                ?>
                                                    <tr class="_verPedido-table-row">
                                                        <td class="_verPedido-product-cell">
                                                            <div class="_verPedido-product-info">

                                                                <div class="_verPedido-product-details">
                                                                    <!-- ✅ NUEVO: Imagen como en confirmacion.php -->
                                                                    <div class="_verPedido-product-image">
                                                                        <img src="<?= $item['producto_imagen'] ?>"
                                                                            alt="<?= htmlspecialchars($item['producto_nombre'] ?? 'Producto') ?>"
                                                                            onerror="this.src='<?= url('image/default-product.jpg') ?>'">
                                                                    </div>
                                                                    <div class="_verPedido-product-name"><?= htmlspecialchars($item['producto_nombre'] ?? 'Producto ' . $item['producto_id']) ?></div>
                                                                    <div class="_verPedido-product-id">ID: <?= htmlspecialchars($item['producto_id']) ?></div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="_verPedido-variant-cell">
                                                            <?php if (!empty($item['variante_talla']) || !empty($item['variante_color'])): ?>
                                                                <div class="_verPedido-variant-info">
                                                                    <?php if (!empty($item['variante_talla'])): ?>
                                                                        <span class="_verPedido-variant-badge">Talla: <?= htmlspecialchars($item['variante_talla']) ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($item['variante_color'])): ?>
                                                                        <span class="_verPedido-variant-badge">Color: <?= htmlspecialchars($item['variante_color']) ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="_verPedido-no-variant">Sin variante</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="_verPedido-quantity-cell">
                                                            <span class="_verPedido-quantity-badge">
                                                                <?= $item['cantidad'] ?>
                                                            </span>
                                                        </td>
                                                        <td class="_verPedido-price-cell">S/ <?= number_format($item['precio_unitario'], 2) ?></td>
                                                        <td class="_verPedido-discount-cell">
                                                            <?php if ($descuento_item > 0): ?>
                                                                -S/ <?= number_format($descuento_item, 2) ?>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="_verPedido-total-cell">S/ <?= number_format($subtotal_final, 2) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen del Pedido -->
                        <div class="_verPedido-summary-panel">
                            <div class="_verPedido-info-block">
                                <h3 class="_verPedido-block-title">
                                    <i class="fas fa-receipt"></i> Resumen del Pedido
                                </h3>
                                <div class="_verPedido-block-content">
                                    <!-- Resumen financiero -->
                                    <div class="_verPedido-financial-summary">
                                        <div class="_verPedido-summary-grid">
                                            <div class="_verPedido-summary-item">
                                                <span class="_verPedido-summary-label">Subtotal Productos</span>
                                                <span class="_verPedido-summary-value">S/ <?= number_format($pedido['subtotal'], 2) ?></span>
                                            </div>

                                            <!-- Descuentos por Promociones -->
                                            <?php if (!empty($pedido['descuento_promocion']) && $pedido['descuento_promocion'] > 0): ?>
                                                <div class="_verPedido-summary-item _verPedido-discount-item">
                                                    <span class="_verPedido-summary-label">
                                                        Descuento por Promociones
                                                        <?php if (!empty($promociones_aplicadas)): ?>
                                                            <div class="_verPedido-promotion-names">
                                                                <?php foreach ($promociones_aplicadas as $promocion): ?>
                                                                    <small class="_verPedido-promotion-name">• <?= htmlspecialchars($promocion['nombre']) ?></small>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="_verPedido-summary-value">-S/ <?= number_format($pedido['descuento_promocion'], 2) ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Descuentos por Cupón -->
                                            <?php if (!empty($pedido['descuento_cupon']) && $pedido['descuento_cupon'] > 0): ?>
                                                <div class="_verPedido-summary-item _verPedido-discount-item">
                                                    <span class="_verPedido-summary-label">
                                                        Descuento por Cupón
                                                        <?php if (!empty($cupon_info)): ?>
                                                            <small class="_verPedido-coupon-name">(<?= htmlspecialchars($cupon_info['codigo']) ?>)</small>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="_verPedido-summary-value">-S/ <?= number_format($pedido['descuento_cupon'], 2) ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Costo de Envío -->
                                            <div class="_verPedido-summary-item">
                                                <span class="_verPedido-summary-label">Costo de Envío</span>
                                                <span class="_verPedido-summary-value <?= $pedido['costo_envio'] == 0 ? '_verPedido-free' : '' ?>">
                                                    <?= $pedido['costo_envio'] == 0 ? 'Gratis' : 'S/ ' . number_format($pedido['costo_envio'], 2) ?>
                                                </span>
                                            </div>

                                            <!-- Método de Pago -->
                                            <div class="_verPedido-summary-item">
                                                <span class="_verPedido-summary-label">Método de Pago</span>
                                                <span class="_verPedido-summary-value _verPedido-payment-method">
                                                    <?php
                                                    $metodo_pago = $pedido['metodo_pago'] ?? 'contraentrega';
                                                    $metodo_pago_texto = [
                                                        'contraentrega' => 'Pago contra entrega',
                                                        'tarjeta' => 'Tarjeta de crédito/débito'
                                                    ];
                                                    echo $metodo_pago_texto[$metodo_pago] ?? ucfirst($metodo_pago);
                                                    ?>
                                                </span>
                                            </div>

                                            <!-- Total -->
                                            <div class="_verPedido-summary-item _verPedido-total-item">
                                                <span class="_verPedido-summary-label">Total a Pagar</span>
                                                <span class="_verPedido-summary-value">S/ <?= number_format($pedido['monto_total'], 2) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Información del Cliente -->
                    <div class="_verPedido-customer-section">
                        <!-- Información General -->
                        <div class="_verPedido-info-block">
                            <h3 class="_verPedido-block-title">
                                <i class="fas fa-user"></i> Información del Cliente
                            </h3>
                            <div class="_verPedido-block-content">
                                <div class="_verPedido-info-grid">
                                    <div class="_verPedido-info-item">
                                        <span class="_verPedido-info-label">Cliente:</span>
                                        <span class="_verPedido-info-value">
                                            <?= htmlspecialchars($pedido['envio_nombre'] ?? $pedido['nombre_cliente'] ?? 'Cliente no disponible') ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($pedido['email_cliente'])): ?>
                                        <div class="_verPedido-info-item">
                                            <span class="_verPedido-info-label">Email:</span>
                                            <span class="_verPedido-info-value"><?= htmlspecialchars($pedido['email_cliente']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="_verPedido-info-item">
                                        <span class="_verPedido-info-label">Fecha del pedido:</span>
                                        <span class="_verPedido-info-value"><?= date('d/m/Y H:i', strtotime($pedido['creado_en'])) ?></span>
                                    </div>
                                    <div class="_verPedido-info-item">
                                        <span class="_verPedido-info-label">Estado:</span>
                                        <span class="_verPedido-status-badge _verPedido-status-<?= $pedido['estado'] ?>">
                                            <?= ucfirst($pedido['estado']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Envío -->
                        <?php if (isset($direccion_pedido) && $direccion_pedido): ?>
                            <div class="_verPedido-info-block">
                                <h3 class="_verPedido-block-title">
                                    <i class="fas fa-truck"></i> Información de Envío
                                </h3>
                                <div class="_verPedido-block-content">
                                    <div class="_verPedido-info-grid">
                                        <?php if (!empty($telefono_contacto)): ?>
                                            <div class="_verPedido-info-item">
                                                <span class="_verPedido-info-label">Teléfono de Contacto:</span>
                                                <span class="_verPedido-info-value"><?= htmlspecialchars($telefono_contacto) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Dirección principal -->
                                        <?php if (!empty($direccion_detalle['direccion'])): ?>
                                            <div class="_verPedido-info-item _verPedido-full-width">
                                                <span class="_verPedido-info-label">Dirección:</span>
                                                <span class="_verPedido-info-value"><?= htmlspecialchars($direccion_detalle['direccion']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Ubicación separada -->
                                        <?php if (!empty($direccion_detalle['departamento'])): ?>
                                            <div class="_verPedido-info-item">
                                                <span class="_verPedido-info-label">Departamento:</span>
                                                <span class="_verPedido-info-value"><?= htmlspecialchars($direccion_detalle['departamento']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($direccion_detalle['provincia'])): ?>
                                            <div class="_verPedido-info-item">
                                                <span class="_verPedido-info-label">Provincia:</span>
                                                <span class="_verPedido-info-value"><?= htmlspecialchars($direccion_detalle['provincia']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($direccion_detalle['distrito'])): ?>
                                            <div class="_verPedido-info-item">
                                                <span class="_verPedido-info-label">Distrito:</span>
                                                <span class="_verPedido-info-value"><?= htmlspecialchars($direccion_detalle['distrito']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Referencia -->
                                        <?php if (!empty($direccion_detalle['referencia'])): ?>
                                            <div class="_verPedido-info-item _verPedido-full-width">
                                                <span class="_verPedido-info-label">Referencia:</span>
                                                <span class="_verPedido-info-value"><?= htmlspecialchars($direccion_detalle['referencia']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Información de Facturación -->
                        <?php if (!empty($pedido['facturacion_nombre']) || !empty($pedido['facturacion_email'])): ?>
                            <div class="_verPedido-info-block">
                                <h3 class="_verPedido-block-title">
                                    <i class="fas fa-file-invoice-dollar"></i> Información de Facturación
                                </h3>
                                <div class="_verPedido-block-content">
                                    <div class="_verPedido-info-grid">
                                        <?php if (!empty($pedido['facturacion_tipo_documento']) && !empty($pedido['facturacion_numero_documento'])): ?>
                                            <div class="_verPedido-info-item">
                                                <span class="_verPedido-info-label">Documento:</span>
                                                <span class="_verPedido-info-value">
                                                    <?= htmlspecialchars(strtoupper($pedido['facturacion_tipo_documento'])) ?>:
                                                    <?= htmlspecialchars($pedido['facturacion_numero_documento']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($pedido['facturacion_nombre'])): ?>
                                            <div class="_verPedido-info-item">
                                                <span class="_verPedido-info-label">Nombre / Razón Social:</span>
                                                <span class="_verPedido-info-value"><?= htmlspecialchars($pedido['facturacion_nombre']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($pedido['facturacion_direccion'])): ?>
                                            <div class="_verPedido-info-item _verPedido-full-width">
                                                <span class="_verPedido-info-label">Dirección Fiscal:</span>
                                                <span class="_verPedido-info-value"><?= htmlspecialchars($pedido['facturacion_direccion']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($pedido['facturacion_email'])): ?>
                                            <div class="_verPedido-info-item">
                                                <span class="_verPedido-info-label">Email:</span>
                                                <span class="_verPedido-info-value"><?= htmlspecialchars($pedido['facturacion_email']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Panel de gestión -->
                    <div class="_verPedido-management-panel">
                        <div class="_verPedido-info-block">
                            <h3 class="_verPedido-block-title">
                                <i class="fas fa-cogs"></i> Gestión del Pedido
                            </h3>
                            <div class="_verPedido-block-content">
                                <!-- Cambiar Estado -->
                                <div class="_verPedido-management-form">
                                    <h4 class="_verPedido-form-title">Cambiar Estado</h4>
                                    <form method="post" action="<?= url('pedido/cambiarEstado') ?>">
                                        <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                        <div class="_verPedido-form-group">
                                            <label for="estado" class="_verPedido-form-label">Nuevo estado:</label>
                                            <select name="estado" id="estado" class="_verPedido-form-select">
                                                <?php foreach ($estados as $estado): ?>
                                                    <option value="<?= $estado ?>" <?= $pedido['estado'] === $estado ? 'selected' : '' ?>>
                                                        <?= ucfirst($estado) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="_verPedido-btn _verPedido-btn-primary">
                                                <i class="fas fa-sync-alt"></i> Actualizar estado
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Observaciones -->
                                <div class="_verPedido-management-form">
                                    <h4 class="_verPedido-form-title">Observaciones del Administrador</h4>
                                    <form method="post" action="<?= url('pedido/guardarObservacion') ?>">
                                        <input type="hidden" name="id" value="<?= $pedido['id'] ?>">
                                        <div class="_verPedido-form-group">
                                            <label for="observacion" class="_verPedido-form-label">Observaciones:</label>
                                            <textarea
                                                name="observacion"
                                                id="observacion"
                                                class="_verPedido-form-textarea"
                                                placeholder="Escribe aquí cualquier observación sobre el pedido..."><?= htmlspecialchars($pedido['observacion'] ?? '') ?></textarea>
                                            <button type="submit" class="_verPedido-btn _verPedido-btn-primary">
                                                <i class="fas fa-save"></i> Guardar observación
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>