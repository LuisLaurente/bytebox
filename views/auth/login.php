<!DOCTYPE html>

<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Iniciar Sesión - Bytebox</title>
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= url('/css/registro.css') ?>">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            .bg-gradient {
                background: #2ac1db;
            }
        </style>
    </head>

    <body class="bg-gradient min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8 p-8">
            <div class="bg-white rounded-lg shadow-2xl p-8">
                <!-- Header -->
                <div class="text-center">
                    <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                        Bytebox
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Inicia sesión en tu cuenta
                    </p>
                </div>

                <!-- Mensajes de error -->
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Botones de login social -->
                <?php require_once __DIR__ . '/login_social.php'; ?>

                <!-- Formulario de login -->
                <form class="mt-8 space-y-6" method="POST" action="<?= url('/auth/authenticate') ?>">
                    <!-- Token CSRF para seguridad -->
                    <?= \Core\Helpers\CsrfHelper::tokenField('login_form') ?>
                    
                    <!-- Campo oculto para redirección -->
                    <?php if (isset($redirect) && !empty($redirect)): ?>
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                    <?php endif; ?>
                    
                    <div class="space-y-4">
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Correo Electrónico
                            </label>
                            <input id="email" 
                                name="email" 
                                type="email" 
                                autocomplete="email" 
                                required 
                                class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                placeholder="tu@email.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Contraseña
                            </label>
                            <input id="password" 
                                name="password" 
                                type="password" 
                                autocomplete="current-password" 
                                required 
                                class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <!-- Remember me -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember" 
                                name="remember" 
                                type="checkbox" 
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">
                                Recordarme
                            </label>
                        </div>

                        <div id="view-login">
                            <div class="text-right" style="margin-top: 10px;">
                                <a href="#" id="btn-forgot-password" style="color: #00d2ff; text-decoration: none; font-size: 0.9em;">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            </div>
                        </div>

                        <div id="view-forgot-step1" style="display: none;">
                            <h3 style="text-align:center; color: white;">Recuperar Contraseña</h3>
                            <p style="color: #ccc; text-align: center; font-size: 0.9em; margin-bottom: 20px;">Ingresa tu correo para recibir un código de verificación.</p>
                            
                            <form id="form-forgot-step1">
                                <div class="form-group">
                                    <input type="email" id="forgot-email" placeholder="tu@email.com" required style="width: 100%; padding: 12px; box-sizing: border-box; margin-bottom: 15px;">
                                </div>
                                <div id="forgot-msg-1" style="color: red; display: none; margin-bottom: 10px; text-align: center;"></div>
                                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">Enviar Código</button>
                                <button type="button" class="btn-link btn-back-login" style="margin-top: 10px; width: 100%;">Volver al Login</button>
                            </form>
                        </div>

                        <div id="view-forgot-step2" style="display: none;">
                            <h3 style="text-align:center; color: white;">Verificar Código</h3>
                            <p style="color: #ccc; text-align: center; font-size: 0.9em; margin-bottom: 20px;">Ingresa el código enviado a <strong id="forgot-email-display"></strong></p>
                            
                            <form id="form-forgot-step2">
                                <div class="form-group">
                                    <input type="text" id="forgot-code" placeholder="123456" maxlength="6" required style="width: 100%; padding: 12px; text-align: center; letter-spacing: 5px; font-size: 1.2em; box-sizing: border-box; margin-bottom: 15px;">
                                </div>
                                <div id="forgot-msg-2" style="color: red; display: none; margin-bottom: 10px; text-align: center;"></div>
                                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">Verificar</button>
                            </form>
                        </div>

                        <div id="view-forgot-step3" style="display: none;">
                            <h3 style="text-align:center; color: white;">Nueva Contraseña</h3>
                            <form id="form-forgot-step3">
                                <div class="form-group">
                                    <input type="password" id="forgot-pass" placeholder="Nueva contraseña" required style="width: 100%; padding: 12px; box-sizing: border-box; margin-bottom: 10px;">
                                </div>
                                <div class="form-group">
                                    <input type="password" id="forgot-confirm" placeholder="Confirmar contraseña" required style="width: 100%; padding: 12px; box-sizing: border-box; margin-bottom: 15px;">
                                </div>
                                <div id="forgot-msg-3" style="color: red; display: none; margin-bottom: 10px; text-align: center;"></div>
                                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">Cambiar y Acceder</button>
                            </form>
                        </div>
                    </div>

                    <!-- Submit button -->
                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            Iniciar Sesión
                        </button>
                        <div class="text-center text-sm text-gray-700"> ¿No tienes una cuenta?
                            <a href="<?= url('auth/registro?redirect=' . urlencode('carrito/ver')) ?>" class="btn-secondary font-medium text-blue-600 hover:text-blue-500">
                                Crea una
                            </a>.
                        </div>
                    </div>
                </form>

                <!-- Botón para regresar a la tienda -->
                <div class="mt-6 text-center">
                    <a href="<?= url('home/index') ?>" class="inline-block px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded font-medium transition">&larr; Regresar a la tienda</a>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center">
                <p class="text-sm text-white opacity-75">
                    © <?= date('Y') ?> Bytebox. Todos los derechos reservados.
                </p>
            </div>
        </div>

        <div id="verificationModal" class="modal-overlay" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><span class="icon">✉️</span> Verifica tu cuenta</h3>
                    <p>Ingresa el código enviado a <strong id="modalEmailDisplay"></strong></p>
                </div>
                <div class="modal-body">
                    <div class="code-inputs">
                        <input type="text" id="verificationCode" placeholder="123456" maxlength="6" autocomplete="off" style="box-sizing: border-box; width: 100%; padding: 15px; font-size: 24px; text-align: center; letter-spacing: 8px; background: #000; border: 2px solid #333; color: #00d2ff; border-radius: 8px; margin-top: 15px;">
                    </div>
                    <div id="modalError" class="error-msg" style="display:none; color: red; margin-top: 10px;"></div>
                    <div id="modalSuccess" class="success-msg" style="display:none; color: green; margin-top: 10px;">¡Verificado! Ingresando...</div>
                </div>
                <div class="modal-footer">
                    <button id="btnVerify" type="button" class="btn-primary">Verificar</button>
                    <button id="btnResend" type="button" class="btn-link">Reenviar código</button>
                    <button id="btnCancel" type="button" class="btn-link close-modal">Cancelar</button>
                </div>
            </div>
            <form id="formReenvio" method="POST" action="<?= url('auth/reenviarCodigo') ?>" style="display:none;">
                <input type="hidden" name="email" id="inputEmailReenvio">
                <input type="hidden" name="redirect" value="auth/login">
                <?= \Core\Helpers\CsrfHelper::tokenField('registro_form') ?>
            </form>
            <form id="formVerificar" method="POST" action="<?= url('auth/verificarCodigoRegistro') ?>" style="display:none;">
                <input type="hidden" name="email" id="inputEmailVerificar">
                <input type="hidden" name="codigo" id="inputCodigoVerificar">
                <input type="hidden" name="redirect" value="auth/profile"> </form>
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
                box-sizing: border-box;
            }
            #verificationCode:focus { border-color: #00d2ff; outline: none; }
            .modal-footer { margin-top: 20px; display: flex; flex-direction: column; gap: 10px; }
            .btn-primary { background: linear-gradient(45deg, #00d2ff, #0078ff); border: none; padding: 12px; color: white; border-radius: 5px; cursor: pointer; font-weight: bold; }
            .btn-link { background: none; border: none; padding: 12px; color: #888; cursor: pointer; text-decoration: underline; }
        </style>

        <?php
            // Lógica para abrir el modal automáticamente
            $openModal = false;
            $emailPendiente = '';

            if (isset($_SESSION['login_verificacion_pendiente'])) {
                $openModal = true;
                $emailPendiente = $_SESSION['login_email_temp'];
                unset($_SESSION['login_verificacion_pendiente']);
            }
            // Soporte para el reenvío (tu lógica existente)
            if (isset($_SESSION['registro_reenvio_exito'])) {
                $openModal = true;
                $emailPendiente = $_SESSION['registro_email_temp'];
                unset($_SESSION['registro_reenvio_exito']);
            }
            // Soporte para error de verificación (tu lógica existente)
            if (isset($_SESSION['abrir_modal_verificacion'])) {
                $openModal = true;
                $emailPendiente = $_SESSION['registro_email_temp'];
                unset($_SESSION['abrir_modal_verificacion']);
            }
        ?>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('verificationModal');
                const emailDisplay = document.getElementById('modalEmailDisplay');
                const btnVerify = document.getElementById('btnVerify');
                const btnResend = document.getElementById('btnResend');
                const btnCancel = document.getElementById('btnCancel');
                const codeInput = document.getElementById('verificationCode');
                
                // Variables PHP inyectadas
                const shouldOpen = <?= $openModal ? 'true' : 'false' ?>;
                const userEmail = "<?= htmlspecialchars($emailPendiente) ?>";

                if (shouldOpen) {
                    emailDisplay.textContent = userEmail;
                    modal.style.display = 'flex';
                    
                    // Setup Forms
                    document.getElementById('inputEmailReenvio').value = userEmail;
                    document.getElementById('inputEmailVerificar').value = userEmail;
                }

                btnVerify.addEventListener('click', function() {
                    const code = codeInput.value.trim();
                    if(code.length !== 6) { alert('Código inválido'); return; }
                    
                    document.getElementById('inputCodigoVerificar').value = code;
                    document.getElementById('formVerificar').submit();
                });

                btnResend.addEventListener('click', function() {
                    btnResend.textContent = "Enviando...";
                    document.getElementById('formReenvio').submit();
                });

                btnCancel.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            });
            </script>

        <script>
            // Enfocar el primer campo al cargar la página
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('email').focus();
            });

            // Validación del lado del cliente
            document.querySelector('form').addEventListener('submit', function(e) {
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;

                if (!email || !password) {
                    e.preventDefault();
                    alert('Por favor, completa todos los campos');
                    return;
                }

                if (!email.includes('@')) {
                    e.preventDefault();
                    alert('Por favor, ingresa un email válido');
                    return;
                }
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Referencias DOM
                const viewLogin = document.getElementById('view-login');
                const viewStep1 = document.getElementById('view-forgot-step1');
                const viewStep2 = document.getElementById('view-forgot-step2');
                const viewStep3 = document.getElementById('view-forgot-step3');
                
                const btnForgot = document.getElementById('btn-forgot-password');
                const btnsBack = document.querySelectorAll('.btn-back-login');
                
                let currentEmail = '';
                let currentCode = '';

                // 1. Mostrar Recuperación
                if(btnForgot) {
                    btnForgot.addEventListener('click', (e) => {
                        e.preventDefault();
                        viewLogin.style.display = 'none';
                        viewStep1.style.display = 'block';
                    });
                }

                // Volver al login
                btnsBack.forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        viewStep1.style.display = 'none';
                        viewStep2.style.display = 'none';
                        viewStep3.style.display = 'none';
                        viewLogin.style.display = 'block';
                    });
                });

                // PASO 1: Enviar Correo
                document.getElementById('form-forgot-step1').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const email = document.getElementById('forgot-email').value;
                    const btn = this.querySelector('button[type="submit"]');
                    const msg = document.getElementById('forgot-msg-1');
                    
                    btn.disabled = true; btn.textContent = "Enviando...";
                    msg.style.display = 'none';

                    const formData = new FormData();
                    formData.append('email', email);

                    fetch('/bytebox/public/auth/iniciarRecuperacion', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if(data.success) {
                            currentEmail = email;
                            document.getElementById('forgot-email-display').textContent = email;
                            viewStep1.style.display = 'none';
                            viewStep2.style.display = 'block';
                        } else {
                            msg.textContent = data.message;
                            msg.style.display = 'block';
                            btn.disabled = false; btn.textContent = "Enviar Código";
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        msg.textContent = "Error de conexión";
                        msg.style.display = 'block';
                        btn.disabled = false; btn.textContent = "Enviar Código";
                    });
                });

                // PASO 2: Verificar Código
                document.getElementById('form-forgot-step2').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const code = document.getElementById('forgot-code').value;
                    const btn = this.querySelector('button[type="submit"]');
                    const msg = document.getElementById('forgot-msg-2');

                    btn.disabled = true; btn.textContent = "Verificando...";
                    
                    const formData = new FormData();
                    formData.append('email', currentEmail);
                    formData.append('codigo', code);

                    fetch('/bytebox/public/auth/verificarCodigoRecuperacion', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if(data.success) {
                            currentCode = code;
                            viewStep2.style.display = 'none';
                            viewStep3.style.display = 'block';
                        } else {
                            msg.textContent = data.message;
                            msg.style.display = 'block';
                            btn.disabled = false; btn.textContent = "Verificar";
                        }
                    })
                    .catch(() => {
                        btn.disabled = false; btn.textContent = "Verificar";
                    });
                });

                // PASO 3: Cambiar Contraseña
                document.getElementById('form-forgot-step3').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const p1 = document.getElementById('forgot-pass').value;
                    const p2 = document.getElementById('forgot-confirm').value;
                    const btn = this.querySelector('button[type="submit"]');
                    const msg = document.getElementById('forgot-msg-3');

                    if(p1 !== p2) {
                        msg.textContent = "Las contraseñas no coinciden";
                        msg.style.display = 'block';
                        return;
                    }

                    btn.disabled = true; btn.textContent = "Actualizando...";

                    const formData = new FormData();
                    formData.append('email', currentEmail);
                    formData.append('codigo', currentCode);
                    formData.append('password', p1);
                    formData.append('confirm_password', p2);

                    fetch('/bytebox/public/auth/finalizarRecuperacion', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if(data.success) {
                            alert("¡Contraseña actualizada! Ingresando...");
                            window.location.reload(); // O redirigir al perfil
                        } else {
                            msg.textContent = data.message;
                            msg.style.display = 'block';
                            btn.disabled = false; btn.textContent = "Cambiar y Acceder";
                        }
                    });
                });
            });
        </script>
    </body>
</html>