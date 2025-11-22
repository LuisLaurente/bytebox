<!DOCTYPE html>

<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Bytebox</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400&family=Outfit:wght@300;400;500;600&display=swap"
        rel="stylesheet">
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
                        <input id="email" name="email" type="email" autocomplete="email" required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="tu@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Contraseña
                        </label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                            placeholder="••••••••">
                    </div>
                </div>

                <!-- Remember me -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Recordarme
                        </label>
                    </div>

                    <div class="text-right" style="margin-top: 10px;">
                        <a href="#" id="btn-forgot-password"
                            style="color: #00d2ff; text-decoration: none; font-size: 0.9em;">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                </div>

                <!-- Submit button -->
                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        Iniciar Sesión
                    </button>
                    <div class="text-center text-sm text-gray-700"> ¿No tienes una cuenta?
                        <a href="<?= url('auth/registro') . (!empty($redirect) ? '?redirect=' . urlencode($redirect) : '') ?>"
                            class="btn-secondary font-medium text-blue-600 hover:text-blue-500">
                            Crea una
                        </a>.
                    </div>
                </div>
            </form>

            <!-- Botón para regresar a la tienda -->
            <div class="mt-6 text-center">
                <a href="<?= url('home/index') ?>"
                    class="inline-block px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded font-medium transition">&larr;
                    Regresar a la tienda</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center">
            <p class="text-sm text-white opacity-75">
                © <?= date('Y') ?> Bytebox. Todos los derechos reservados.
            </p>
        </div>
    </div>

    <div id="recoveryModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div id="rec-step1">
                <h3 class="modal-title">Recuperar Contraseña</h3>
                <p class="modal-desc">Ingresa tu correo para recibir un código.</p>
                <form id="form-rec-step1">
                    <input type="email" id="rec-email" class="modal-input" placeholder="tu@email.com" required>
                    <div id="rec-msg-1" class="error-msg"></div>
                    <button type="submit" class="btn-primary">Enviar Código</button>
                    <button type="button" class="btn-link btn-close-recovery">Cancelar</button>
                </form>
            </div>

            <div id="rec-step2" style="display: none;">
                <h3 class="modal-title">Verificar Código</h3>
                <p class="modal-desc">Enviado a <strong id="rec-email-display" style="color:white"></strong></p>
                <form id="form-rec-step2">
                    <input type="text" id="rec-code" class="modal-input" placeholder="123456" maxlength="6" required
                        style="text-align:center; font-size:1.5em; letter-spacing:5px;">
                    <div id="rec-msg-2" class="error-msg"></div>
                    <button type="submit" class="btn-primary">Verificar</button>
                    <button type="button" class="btn-link" id="btn-back-step1">Corregir correo</button>
                </form>
            </div>

            <div id="rec-step3" style="display: none;">
                <h3 class="modal-title">Nueva Contraseña</h3>
                <p class="modal-desc">Crea una contraseña segura.</p>
                <form id="form-rec-step3">
                    <input type="password" id="rec-pass" class="modal-input" placeholder="Nueva contraseña" required>
                    <input type="password" id="rec-confirm" class="modal-input" placeholder="Confirmar contraseña"
                        required>
                    <div id="rec-msg-3" class="error-msg"></div>
                    <button type="submit" class="btn-primary">Actualizar y Entrar</button>
                </form>
            </div>
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
                    <input type="text" id="verificationCode" placeholder="123456" maxlength="6" autocomplete="off"
                        style="box-sizing: border-box; width: 100%; padding: 15px; font-size: 24px; text-align: center; letter-spacing: 8px; background: #000; border: 2px solid #333; color: #00d2ff; border-radius: 8px; margin-top: 15px;">
                </div>
                <div id="modalError" class="error-msg" style="display:none; color: red; margin-top: 10px;"></div>
                <div id="modalSuccess" class="success-msg" style="display:none; color: green; margin-top: 10px;">
                    ¡Verificado! Ingresando...</div>
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
        <form id="formVerificar" method="POST" action="<?= url('auth/verificarCodigoRegistro') ?>"
            style="display:none;">
            <input type="hidden" name="email" id="inputEmailVerificar">
            <input type="hidden" name="codigo" id="inputCodigoVerificar">
            <input type="hidden" name="redirect" value="auth/profile">
        </form>
    </div>

    <style>
        /* Estilos rápidos para el modal (Mover a registro.css idealmente) */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: #1a1a1a;
            border: 1px solid #333;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            color: white;
            box-shadow: 0 0 20px rgba(0, 210, 255, 0.2);
        }

        /* Elementos de Formulario Oscuro */
        .modal-input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            background: #000;
            border: 1px solid #333;
            color: white;
            border-radius: 8px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.3s;
        }

        .modal-input:focus {
            border-color: #00d2ff;
        }

        #verificationCode {
            width: 100%;
            padding: 15px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            background: #000;
            border: 2px solid #333;
            color: #00d2ff;
            border-radius: 8px;
            margin-top: 15px;
            box-sizing: border-box;
        }

        #verificationCode:focus {
            border-color: #00d2ff;
            outline: none;
        }

        .modal-footer {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(45deg, #00d2ff, #0078ff);
            border: none;
            padding: 12px;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            transition: opacity 0.3s;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-link {
            background: none;
            border: none;
            padding: 12px;
            color: #888;
            cursor: pointer;
            text-decoration: underline;
            width: 100%;
            margin-top: 10px;
            font-size: 0.9em;
        }

        .btn-link:hover {
            color: #ccc;
        }

        /* Títulos */
        .modal-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: white;
        }

        .modal-desc {
            font-size: 0.9rem;
            color: #aaa;
            margin-bottom: 1.5rem;
        }
    </style>



    <?php
    // Variables para Modal Invitación
    $openInvite = false;
    $emailInvite = '';
    if (
        isset($_SESSION['login_verificacion_pendiente']) ||
        isset($_SESSION['registro_reenvio_exito']) ||
        isset($_SESSION['abrir_modal_verificacion'])
    ) {
        $openInvite = true;
        $emailInvite = $_SESSION['login_email_temp'] ?? $_SESSION['registro_email_temp'] ?? '';
        // Limpiar sesión
        unset($_SESSION['login_verificacion_pendiente'], $_SESSION['registro_reenvio_exito'], $_SESSION['abrir_modal_verificacion']);
    }
    ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log("✅ Script de login cargado");

            // Focus inicial
            const emailLoginInput = document.getElementById('email');
            if (emailLoginInput) emailLoginInput.focus();

            // --- LÓGICA 1: MODAL DE RECUPERACIÓN ---
            const recoveryModal = document.getElementById('recoveryModal');
            const btnForgot = document.getElementById('btn-forgot-password');
            const btnCloseRec = document.querySelector('.btn-close-recovery');
            const btnBackStep1 = document.getElementById('btn-back-step1');

            // Pasos
            const step1 = document.getElementById('rec-step1');
            const step2 = document.getElementById('rec-step2');
            const step3 = document.getElementById('rec-step3');

            let recEmail = '';
            let recCode = '';

            // Abrir Modal Recuperación
            if (btnForgot) {
                btnForgot.addEventListener('click', (e) => {
                    e.preventDefault();
                    recoveryModal.style.display = 'flex';
                    // Resetear vista
                    step1.style.display = 'block';
                    step2.style.display = 'none';
                    step3.style.display = 'none';
                    setTimeout(() => document.getElementById('rec-email').focus(), 100);
                });
            }

            // Cerrar Modal Recuperación
            if (btnCloseRec) {
                btnCloseRec.addEventListener('click', () => {
                    recoveryModal.style.display = 'none';
                });
            }

            // Volver al paso 1
            if (btnBackStep1) {
                btnBackStep1.addEventListener('click', () => {
                    step2.style.display = 'none';
                    step1.style.display = 'block';
                });
            }

            // AJAX 1: Enviar Código
            const f1 = document.getElementById('form-rec-step1');
            if (f1) {
                f1.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const email = document.getElementById('rec-email').value;
                    const btn = f1.querySelector('button');
                    const msg = document.getElementById('rec-msg-1');

                    btn.disabled = true; btn.textContent = "Enviando..."; msg.style.display = 'none';

                    const fd = new FormData(); fd.append('email', email);

                    fetch('/bytebox/public/auth/iniciarRecuperacion', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            btn.disabled = false; btn.textContent = "Enviar Código";
                            if (data.success) {
                                recEmail = email;
                                document.getElementById('rec-email-display').textContent = email;
                                step1.style.display = 'none';
                                step2.style.display = 'block';
                                document.getElementById('rec-code').focus();
                            } else {
                                msg.textContent = data.message; msg.style.display = 'block';
                            }
                        }).catch(() => {
                            btn.disabled = false; btn.textContent = "Enviar Código";
                            msg.textContent = "Error de conexión"; msg.style.display = 'block';
                        });
                });
            }

            // AJAX 2: Verificar Código
            const f2 = document.getElementById('form-rec-step2');
            if (f2) {
                f2.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const code = document.getElementById('rec-code').value;
                    const btn = f2.querySelector('button[type="submit"]');
                    const msg = document.getElementById('rec-msg-2');

                    btn.disabled = true; btn.textContent = "Verificando..."; msg.style.display = 'none';

                    const fd = new FormData(); fd.append('email', recEmail); fd.append('codigo', code);

                    fetch('/bytebox/public/auth/verificarCodigoRecuperacion', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            btn.disabled = false; btn.textContent = "Verificar";
                            if (data.success) {
                                recCode = code;
                                step2.style.display = 'none';
                                step3.style.display = 'block';
                                document.getElementById('rec-pass').focus();
                            } else {
                                msg.textContent = data.message; msg.style.display = 'block';
                            }
                        }).catch(() => {
                            btn.disabled = false; btn.textContent = "Verificar";
                            msg.textContent = "Error de conexión"; msg.style.display = 'block';
                        });
                });
            }

            // AJAX 3: Cambiar Password
            const f3 = document.getElementById('form-rec-step3');
            if (f3) {
                f3.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const p1 = document.getElementById('rec-pass').value;
                    const p2 = document.getElementById('rec-confirm').value;
                    const btn = f3.querySelector('button');
                    const msg = document.getElementById('rec-msg-3');

                    if (p1.length < 6) { msg.textContent = "Mínimo 6 caracteres"; msg.style.display = 'block'; return; }
                    if (p1 !== p2) { msg.textContent = "Las contraseñas no coinciden"; msg.style.display = 'block'; return; }

                    btn.disabled = true; btn.textContent = "Actualizando..."; msg.style.display = 'none';

                    const fd = new FormData();
                    fd.append('email', recEmail); fd.append('codigo', recCode);
                    fd.append('password', p1); fd.append('confirm_password', p2);

                    fetch('/bytebox/public/auth/finalizarRecuperacion', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                alert("¡Contraseña actualizada! Ingresando...");
                                window.location.href = '/bytebox/public/auth/profile';
                            } else {
                                msg.textContent = data.message; msg.style.display = 'block';
                                btn.disabled = false; btn.textContent = "Actualizar y Entrar";
                            }
                        }).catch(() => {
                            btn.disabled = false; btn.textContent = "Actualizar y Entrar";
                            msg.textContent = "Error de conexión"; msg.style.display = 'block';
                        });
                });
            }


            // --- LÓGICA 2: MODAL DE INVITACIÓN (Automático) ---
            const inviteModal = document.getElementById('verificationModal');

            if (inviteModal) {
                const emailDisplay = document.getElementById('modalEmailDisplay');
                const btnVerify = document.getElementById('btnVerify');
                const btnResend = document.getElementById('btnResend');
                const btnCancel = document.getElementById('btnCancel');
                const codeInput = document.getElementById('verificationCode');

                const shouldOpen = <?= $openInvite ? 'true' : 'false' ?>;
                const userEmail = "<?= htmlspecialchars($emailInvite) ?>";

                if (shouldOpen) {
                    if (emailDisplay) emailDisplay.textContent = userEmail;
                    inviteModal.style.display = 'flex';
                    const inpRe = document.getElementById('inputEmailReenvio'); if (inpRe) inpRe.value = userEmail;
                    const inpVer = document.getElementById('inputEmailVerificar'); if (inpVer) inpVer.value = userEmail;
                }

                if (btnCancel) btnCancel.addEventListener('click', () => { inviteModal.style.display = 'none'; });

                if (btnVerify) {
                    btnVerify.addEventListener('click', () => {
                        const code = codeInput.value.trim();
                        if (code.length !== 6) { alert('Código inválido'); return; }
                        document.getElementById('inputCodigoVerificar').value = code;
                        document.getElementById('formVerificar').submit();
                    });
                }

                if (btnResend) {
                    btnResend.addEventListener('click', () => {
                        btnResend.textContent = "Enviando...";
                        document.getElementById('formReenvio').submit();
                    });
                }
            }
        });
    </script>
</body>

</html>