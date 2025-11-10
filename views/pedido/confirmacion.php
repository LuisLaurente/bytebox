<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: ' . url('home/index'));
    exit;
}

$usuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra Confirmada - Bytebox</title>
    <link rel="stylesheet" href="<?= url('css/confirmacion.css') ?>">

    <!-- Favicon -->
    <link rel="icon" href="<?= url('image/faviconT.ico') ?>" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('image/faviconT.png') ?>">
</head>

<body>
    <div class="confirmation-container">
        <div class="celebration"></div>

        <div class="success-icon"></div>

        <h1 class="confirmation-title">¡Compra Exitosa!</h1>

        <p class="confirmation-message">
            Tu pedido ha sido registrado correctamente.
            <br>
            Será procesado en las próximas horas y te mantendremos informado a través del número de contacto proporcionado.
        </p>

        <!-- Información del Pedido -->
        <!-- Información del Pedido -->
        <div class="order-info">
            <h3>Información del Pedido</h3>

            <div class="order-details">
                <div class="order-detail">
                    <span class="detail-label">N° de Pedido:</span>
                    <span class="detail-value">#<?= $pedido['id'] ?? 'N/A' ?></span>
                </div>
                
                    <div class="products-list">
                        <?php if (!empty($productos_pedido)): ?>
                            <?php foreach ($productos_pedido as $producto): ?>
                                <div class="product-item">
                                    <div class="product-image">
                                        <img src="<?= url($producto['imagen_url']) ?>"
                                            alt="<?= htmlspecialchars($producto['nombre_producto']) ?>"
                                            onerror="this.src='<?= url('image/default-product.jpg') ?>'">
                                    </div>
                                    <div class="product-info">
                                        <h4 class="product-name"><?= htmlspecialchars($producto['nombre_producto']) ?></h4>
                                        <div class="product-details">
                                            <?php if (!empty($producto['talla'])): ?>
                                                <span class="detail">Talla: <?= htmlspecialchars($producto['talla']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($producto['color'])): ?>
                                                <span class="detail">Color: <?= htmlspecialchars($producto['color']) ?></span>
                                            <?php endif; ?>
                                            <span class="detail">Cantidad: <?= $producto['cantidad'] ?></span>
                                        </div>
                                    </div>
                                    <div class="product-price">
                                        S/ <?= number_format($producto['precio_unitario'] * $producto['cantidad'], 2) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-products">No hay productos en este pedido</p>
                        <?php endif; ?>
                    </div>
                
                <div class="order-detail">
                    <span class="detail-label">Subtotal:</span>
                    <span class="detail-value">S/ <?= number_format($pedido['subtotal'] ?? 0, 2) ?></span>
                </div>

                <!-- ✅ NUEVO: Descuentos por promociones -->
                <?php if (($pedido['descuento_promocion'] ?? 0) > 0): ?>
                    <div class="order-detail">
                        <span class="detail-label">Descuento promociones:</span>
                        <span class="detail-value" style="color: var(--success-color);">
                            - S/ <?= number_format($pedido['descuento_promocion'] ?? 0, 2) ?>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- ✅ NUEVO: Descuento por cupón -->
                <?php if (($pedido['descuento_cupon'] ?? 0) > 0): ?>
                    <div class="order-detail">
                        <span class="detail-label">Descuento cupón:</span>
                        <span class="detail-value" style="color: var(--success-color);">
                            - S/ <?= number_format($pedido['descuento_cupon'] ?? 0, 2) ?>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- ✅ NUEVO: Costo de envío -->
                <div class="order-detail">
                    <span class="detail-label">Costo de envío:</span>
                    <span class="detail-value">
                        <?php if (($pedido['costo_envio'] ?? 0) > 0): ?>
                            S/ <?= number_format($pedido['costo_envio'] ?? 0, 2) ?>
                        <?php else: ?>
                            <span style="color: var(--success-color); font-weight: 600;">¡GRATIS!</span>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="order-detail">
                    <span class="detail-label">Total:</span>
                    <span class="detail-value" style="font-weight: 700; font-size: 1.1rem;">
                        S/ <?= number_format($pedido['monto_total'] ?? 0, 2) ?>
                    </span>
                </div>

                <div class="order-detail">
                    <span class="detail-label">Fecha:</span>
                    <span class="detail-value"><?= date('d/m/Y H:i', strtotime($pedido['creado_en'] ?? 'now')) ?></span>
                </div>
                <div class="order-detail">
                    <span class="detail-label">Estado:</span>
                    <span class="detail-value status-confirmed">Confirmado</span>
                </div>
            </div>
        </div>

        <!-- Dirección de Envío -->
        <div class="address-section">
            <h3>Dirección de Envío</h3>
            <div class="address-card">
                <div class="address-content">
                    <?php if (!empty($direccion_pedido) && is_array($direccion_pedido)): ?>
                        <p class="address-line"><strong><?= htmlspecialchars($direccion_pedido['nombre_completo'] ?? $usuario['nombre']) ?></strong></p>
                        <p class="address-line"><?= htmlspecialchars($direccion_pedido['direccion'] ?? '') ?></p>
                        <?php if (isset($direccion_pedido['distrito']) || isset($direccion_pedido['provincia']) || isset($direccion_pedido['departamento'])): ?>
                            <p class="address-line">
                                <?= htmlspecialchars($direccion_pedido['distrito'] ?? '') ?>
                                <?= !empty($direccion_pedido['provincia']) ? ', ' . htmlspecialchars($direccion_pedido['provincia']) : '' ?>
                                <?= !empty($direccion_pedido['departamento']) ? ', ' . htmlspecialchars($direccion_pedido['departamento']) : '' ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($direccion_pedido['referencia'])): ?>
                            <p class="address-line reference">Referencia: <?= htmlspecialchars($direccion_pedido['referencia']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($direccion_pedido['telefono'])): ?>
                            <p class="address-line phone">Teléfono: <?= htmlspecialchars($direccion_pedido['telefono']) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="no-address">No se especificó dirección de envío</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="<?= url('home/index') ?>" class="btn btn-primary">
                Seguir comprando
            </a>
            <a href="<?= url('/usuario/pedidos') ?>" class="btn btn-secondary">
                Ver mis pedidos
            </a>
        </div>
    </div>

    <script>
        // Agregar confeti al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Crear elementos de confeti
            for (let i = 0; i < 30; i++) {
                createConfetti();
            }
        });

        function createConfetti() {
            const confetti = document.createElement('div');
            confetti.className = 'confetti-particle';
            confetti.style.left = `${Math.random() * 100}vw`;
            confetti.style.animationDuration = `${3 + Math.random() * 3}s`;
            confetti.style.backgroundColor = getRandomColor();

            document.body.appendChild(confetti);

            setTimeout(() => {
                confetti.remove();
            }, 6000);
        }

        function getRandomColor() {
            const colors = ['#2ac1db', '#3498db', '#e74c3c', '#f39c12', '#2ecc71', '#9b59b6'];
            return colors[Math.floor(Math.random() * colors.length)];
        }
    </script>
</body>

</html>