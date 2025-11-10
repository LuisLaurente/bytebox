<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bytebox - Finalizar Compra</title>
    <link rel="stylesheet" href="<?= url('css/precheckout.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="checkout-page">
    <div class="container">
        <!-- Header -->
        <div class="checkout-header">
            <a href="<?= url('carrito/ver') ?>" class="back-link">
                <span class="back-arrow">←</span>
                Volver al carrito
            </a>
            <h1 class="checkout-title">Finalizar Compra</h1>
            <p class="checkout-subtitle">Para continuar necesitas iniciar sesión o crear una cuenta</p>
        </div>

        <!-- Nueva fila de 3 columnas -->
        <div class="checkout-grid">
            
            <!-- Columna 1: Resumen -->
            <div class="summary-card">
                <h3 class="summary-title">Resumen de tu compra</h3>
                <div class="summary-details">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal:</span>
                        <span class="summary-value">S/ <?= number_format($totales['subtotal'] ?? 0, 2) ?></span>
                    </div>
                    <?php if (($totales['descuento'] ?? 0) > 0): ?>
                    <div class="summary-row discount">
                        <span class="summary-label">Descuento:</span>
                        <span class="summary-value">-S/ <?= number_format($totales['descuento'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <hr class="summary-divider">
                    <div class="summary-row total">
                        <span class="summary-label">Total:</span>
                        <span class="summary-value">S/ <?= number_format($totales['total'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Columna 2: Login -->
            <div class="auth-card login-card">
                <div class="card-header">
                    <div class="icon-circle blue">
                        <span class="auth-icon"></span>
                    </div>
                    <h2 class="auth-title">¿Ya tienes cuenta?</h2>
                    <p class="auth-description">Inicia sesión para acceder a tus direcciones guardadas</p>
                </div>
                
                <div class="benefits-list">
                    <div class="benefit-item"><span class="benefit-text">Direcciones guardadas</span></div>
                    <div class="benefit-item"><span class="benefit-text">Historial de pedidos</span></div>
                    <div class="benefit-item"><span class="benefit-text">Proceso más rápido</span></div>
                </div>

                <a href="<?= url('auth/login?redirect=' . urlencode('pedido/checkout')) ?>" class="auth-button blue">
                    Iniciar Sesión
                </a>
            </div>

            <!-- Columna 3: Registro -->
            <div class="auth-card register-card">
                <div class="card-header">
                    <div class="icon-circle green">
                        <span class="auth-icon"></span>
                    </div>
                    <h2 class="auth-title">¿Primera vez aquí?</h2>
                    <p class="auth-description">Crea tu cuenta y disfruta de todos los beneficios</p>
                </div>
                
                <div class="benefits-list">
                    <div class="benefit-item"><span class="benefit-text">Guarda múltiples direcciones</span></div>
                    <div class="benefit-item"><span class="benefit-text">Ofertas personalizadas</span></div>
                    <div class="benefit-item"><span class="benefit-text">Notificaciones de estado</span></div>
                    <div class="benefit-item"><span class="benefit-text">Programa de puntos</span></div>
                </div>

                <a href="<?= url('auth/registro?redirect=' . urlencode('pedido/checkout')) ?>" class="auth-button green">
                    Crear Cuenta Gratis
                </a>
            </div>

            <!-- Columna 4: Info -->
            <div class="info-card">
                <h3 class="info-title">¿Por qué necesito crear una cuenta?</h3>
                <div class="info-list">
                    <p><strong>Seguridad:</strong> Protegemos tus datos de pago y personales</p>
                    <p><strong>Conveniencia:</strong> Guardamos tus direcciones para futuras compras</p>
                    <p><strong>Seguimiento:</strong> Podrás ver el estado de tus pedidos en tiempo real</p>
                    <p><strong>Soporte:</strong> Te ayudamos mejor cuando conocemos tu historial</p>
                </div>
                <div class="info-tip">
                    <p><strong>Tip:</strong> Tus datos se guardarán automáticamente para hacer tus próximas compras más rápidas</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
