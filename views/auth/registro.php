<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Bytebox</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/css/registro.css') ?>">
</head>

<body class="bg-gradient">
    <div class="container">
        <div class="card">
            <!-- Header -->
            <div class="text-center">
                <h2 class="brand">BYTEBOX</h2>
                <p class="subtitle">Crea tu cuenta gratuita</p>
                <?php if (isset($redirect) && !empty($redirect)): ?>
                    <p class="highlight">Después podrás finalizar tu compra</p>
                <?php endif; ?>
            </div>

            <!-- Mensajes -->
            <?php if (!empty($error)): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Beneficios -->
            <div class="benefits">
                <h4>Beneficios de tu cuenta:</h4>
                <ul>
                    <li>• Guarda múltiples direcciones de envío</li>
                    <li>• Rastrea tus pedidos en tiempo real</li>
                    <li>• Recibe ofertas personalizadas</li>
                    <li>• Compras más rápidas en el futuro</li>
                </ul>
            </div>

            <!-- Formulario -->
            <form method="POST" action="<?= url('/auth/procesarRegistro') ?>" id="registroForm">
                <?= \Core\Helpers\CsrfHelper::tokenField('registro_form') ?>
                <?php if (isset($redirect) && !empty($redirect)): ?>
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nombre">Nombre Completo *</label>
                    <input id="nombre" name="nombre" type="text" required placeholder="Tu nombre completo" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico *</label>
                    <input id="email" name="email" type="email" required placeholder="tu@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña *</label>
                    <input id="password" name="password" type="password" required placeholder="Mínimo 6 caracteres">
                    <div class="password-strength" id="passwordStrength"></div>
                    <p class="hint" id="passwordHint">Mínimo 6 caracteres</p>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña *</label>
                    <input id="confirm_password" name="confirm_password" type="password" required placeholder="Repite tu contraseña">
                    <p class="hint" id="passwordMatch"></p>
                </div>

                <div class="form-check">
                    <input id="terms" name="terms" type="checkbox" required>
                    <label for="terms">
                        Acepto los <a href="#">términos y condiciones</a> y la <a href="#">política de privacidad</a>
                    </label>
                </div>

                <button type="submit" id="submitBtn">
                    <span id="submitText">Crear Cuenta Gratis</span>
                    <span id="submitSpinner" class="hidden">⏳</span>
                </button>

                <p class="login-link">
                    ¿Ya tienes cuenta?
                    <a href="<?= url('auth/login' . (isset($redirect) && !empty($redirect) ? '?redirect=' . urlencode($redirect) : '')) ?>">Inicia sesión aquí</a>
                </p>
            </form>
        </div>

        <?php if (isset($redirect) && !empty($redirect) && $redirect === 'pedido/checkout'): ?>
            <div class="back-link">
                <a href="<?= url('carrito/ver') ?>">← Volver al carrito</a>
            </div>
        <?php endif; ?>
    </div>
    <script src="<?= url('public/js/registro.js') ?>"></script>
</body>

</html>