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

    <div id="verificationModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><span class="icon">✉️</span> Verifica tu correo</h3>
            <p>Hemos enviado un código a <strong id="modalEmailDisplay"></strong></p>
        </div>
        
        <div class="modal-body">
            <div class="code-inputs">
                <input type="text" id="verificationCode" placeholder="123456" maxlength="6" autocomplete="off">
            </div>
            <div id="modalError" class="error-msg" style="display:none; color: red; margin-top: 10px;"></div>
            <div id="modalSuccess" class="success-msg" style="display:none; color: green; margin-top: 10px;">¡Verificado! Redirigiendo...</div>
        </div>

        <div class="modal-footer">
            <button id="btnVerify" type="button" class="btn-primary">Verificar y Crear Cuenta</button>
            <button id="btnResend" type="button" class="btn-link">Reenviar código</button>
            <button id="btnCancel" type="button" class="btn-link close-modal">Cancelar</button>
        </div>
    </div>
</div>

<style>
/* Estilos rápidos para el modal (Mover a registro.css idealmente) */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.85); z-index: 9999;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(5px);
}
.modal-content {
    background: #1a1a1a; border: 1px solid #333; padding: 30px;
    border-radius: 15px; width: 90%; max-width: 400px;
    text-align: center; color: white;
    box-shadow: 0 0 20px rgba(0, 210, 255, 0.2);
}
#verificationCode {
    width: 100%; padding: 15px; font-size: 24px; text-align: center;
    letter-spacing: 8px; background: #000; border: 2px solid #333;
    color: #00d2ff; border-radius: 8px; margin-top: 15px;
}
#verificationCode:focus { border-color: #00d2ff; outline: none; }
.modal-footer { margin-top: 20px; display: flex; flex-direction: column; gap: 10px; }
.btn-primary { background: linear-gradient(45deg, #00d2ff, #0078ff); border: none; padding: 12px; color: white; border-radius: 5px; cursor: pointer; font-weight: bold; }
.btn-link { background: none; border: none; color: #888; cursor: pointer; text-decoration: underline; }
</style>

    <script src="<?= url('/js/registro.js') ?>"></script>
</body>

</html>