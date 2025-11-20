/**
 * JavaScript para el formulario de registro
 * Incluye validaciones en tiempo real y mejoras UX
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registroForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordStrengthDiv = document.getElementById('passwordStrength');
    const passwordHint = document.getElementById('passwordHint');
    const passwordMatch = document.getElementById('passwordMatch');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitSpinner = document.getElementById('submitSpinner');
    const nameInput = document.getElementById('nombre');
    const termsCheckbox = document.getElementById('terms');

    // === NUEVAS CONSTANTES DEL MODAL ===
    const modal = document.getElementById('verificationModal');
    const modalEmailDisplay = document.getElementById('modalEmailDisplay');
    const codeInput = document.getElementById('verificationCode');
    const btnVerify = document.getElementById('btnVerify');
    const btnResend = document.getElementById('btnResend');
    const btnCancel = document.getElementById('btnCancel');
    const msgError = document.getElementById('modalError');
    const msgSuccess = document.getElementById('modalSuccess');

    const RESEND_DELAY_SECONDS = 60; // 60 segundos de espera
    let resendCooldown = 0; 
    let resendTimer = null;

    // ANIDAMIENTO DE LA P DENTRO DEL DIV MEDIANTE DOM
    if (passwordStrengthDiv && passwordHint) {
        // Aseguramos que la etiqueta P sea hija del DIV al cargar el DOM
        if (passwordStrengthDiv.parentNode === passwordHint.parentNode) {
            passwordStrengthDiv.appendChild(passwordHint);
        }
    }

    // Forzamos a que la función se ejecute al inicio para ocultar los elementos vacíos
    if (passwordInput && passwordStrengthDiv && passwordHint) {
        // La función calculatePasswordStrength debe existir y devolver {level: 'none'}
        const initialStrength = calculatePasswordStrength(passwordInput.value); 
        updatePasswordStrengthUI(initialStrength, passwordStrengthDiv, passwordHint);
    }

    // Validación de fortaleza de contraseña (el event listener original)
    if (passwordInput && passwordStrengthDiv) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            updatePasswordStrengthUI(strength, passwordStrengthDiv, passwordHint);
        });
    }

    // Función auxiliar para gestionar estados del botón principal
    function toggleSubmitButton(isLoading, message = 'Crear Cuenta Gratis') {
        if (isLoading) {
            submitText.classList.add('hidden');
            submitSpinner.classList.remove('hidden');
            submitBtn.disabled = true;
        } else {
            submitText.textContent = message;
            submitText.classList.remove('hidden');
            submitSpinner.classList.add('hidden');
            submitBtn.disabled = false;
        }
    }

    // Función auxiliar para obtener el redirect de la URL
    function getRedirectParam() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('redirect') || '';
    }

    // ---------------------------------------------------------------------
    // ASIGNACIÓN DE LISTENERS Y ESTADO INICIAL
    // ---------------------------------------------------------------------
    
    // 1. Asignar la función de chequeo a todos los eventos relevantes
    [nameInput, emailInput, passwordInput, confirmPasswordInput, termsCheckbox].forEach(element => {
        if (element) {
            // El evento 'input' captura escritura. El evento 'change' (para checkbox) captura el clic.
            element.addEventListener(element.type === 'checkbox' ? 'change' : 'input', checkFormValidity);
        }
    });

    // 2. Asegurar el estado inicial
    if (submitBtn) {
        submitBtn.disabled = true; // Deshabilitar por defecto en caso de que el HTML no lo haga
        checkFormValidity(); // Ejecutar inmediatamente al cargar para actualizar el estado
    }

    // === VALIDACIONES EN TIEMPO REAL ===
    // Validación de fortaleza de contraseña
    if (passwordInput && passwordStrengthDiv) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            updatePasswordStrengthUI(strength, passwordStrengthDiv, passwordHint);
        });
    }

    // Validación de confirmación de contraseña
    if (confirmPasswordInput && passwordMatch) {
        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    passwordMatch.textContent = '✓ Las contraseñas coinciden';
                    passwordMatch.className = 'hint success';
                } else {
                    passwordMatch.textContent = '✗ Las contraseñas no coinciden';
                    passwordMatch.className = 'hint error';
                }
            } else {
                passwordMatch.textContent = '';
                passwordMatch.className = 'hint';
            }
        });
    }

    // ---------------------------------------------------------------------
    // 1. MANEJO DE ENVÍO DEL FORMULARIO (NUEVA LÓGICA AJAX)
    // ---------------------------------------------------------------------
    if (form) {
        form.addEventListener('submit', function(e) {
            
            e.preventDefault(); // 🛑 Detener el envío síncrono inmediatamente

            // 1.1. Validaciones finales (Cliente)
            const isValid = validateForm(); // Usamos la validación que ya creaste
            if (!isValid) { 
                // Si la validación falla, restauramos el botón y salimos
                toggleSubmitButton(false); 
                return false; 
            }
            
            toggleSubmitButton(true, 'Enviando código...'); // Mostrar spinner

            const formData = new FormData(form);
            formData.append('redirect', getRedirectParam());

            // 1.2. Petición AJAX al endpoint de INICIO (auth/procesarRegistro)
            // Usamos form.action para que apunte a procesarRegistro, que internamente llama a iniciarRegistro
            fetch(form.action, { 
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // 1.3. ÉXITO: Mostrar Modal
                    modalEmailDisplay.textContent = emailInput.value;
                    modal.style.display = 'flex';
                    codeInput.value = '';
                    msgError.style.display = 'none';
                    msgSuccess.style.display = 'none';
                    btnVerify.textContent = "Verificar y Crear Cuenta";
                    btnVerify.disabled = false;
                    codeInput.focus();
                    startResendTimer(RESEND_DELAY_SECONDS);
                } else {
                    // 1.4. FALLO: Mostrar error en el formulario principal
                    alert('Error en el registro: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error de red/servidor:', err);
                alert('Ocurrió un error de conexión. Intenta nuevamente.');
            })
            .finally(() => {
                // Solo restaurar el botón principal si el modal NO SE MOSTRÓ
                if (modal.style.display !== 'flex') {
                    toggleSubmitButton(false); 
                }
            });
        });
    }

    // ---------------------------------------------------------------------
    // 2. LÓGICA DEL MODAL (Verificación de Código)
    // ---------------------------------------------------------------------

    // 2.1. Botón de Verificar (Finalizar Registro)
    btnVerify.addEventListener('click', function() {
        const code = codeInput.value.trim();
        
        if (code.length !== 6) {
            msgError.textContent = "El código debe tener 6 dígitos.";
            msgError.style.display = 'block';
            return;
        }

        btnVerify.textContent = "Verificando...";
        btnVerify.disabled = true;
        msgError.style.display = 'none';

        const formData = new FormData();
        formData.append('email', emailInput.value);
        formData.append('codigo', code);
        formData.append('redirect', getRedirectParam());

        // Petición AJAX al endpoint de VERIFICACIÓN (Paso 2 del Backend)
        fetch('/bytebox/public/auth/verificarCodigoRegistro', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // ÉXITO: Redirección final
                msgSuccess.style.display = 'block';
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            } else {
                // FALLO: Mostrar error en el modal
                msgError.textContent = data.message;
                msgError.style.display = 'block';
                btnVerify.textContent = "Verificar y Crear Cuenta";
                btnVerify.disabled = false;
            }
        })
        .catch(err => {
            console.error('Error de verificación:', err);
            msgError.textContent = "Error de conexión o servidor.";
            msgError.style.display = 'block';
            btnVerify.textContent = "Verificar y Crear Cuenta";
            btnVerify.disabled = false;
        });
    });
    
    // 2.2. Botón de Cancelar/Cerrar Modal
    if (btnCancel) {
        btnCancel.addEventListener('click', function() {
            modal.style.display = 'none';
            toggleSubmitButton(false); // Restaurar botón principal
        });
    }

    // 2.3. Lógica del botón Reenviar Código (Opcional, seguridad mejorada)
    if (btnResend) {
        btnResend.addEventListener('click', function() {
            const msgError = document.getElementById('modalError');

            if (resendCooldown > 0) {
                msgError.textContent = `Por favor, espera ${resendCooldown} segundos para reenviar.`;
                msgError.style.color = 'orange';
                msgError.style.display = 'block';
                return; 
            }
            
            btnResend.disabled = true;
            btnResend.textContent = "Reenviando...";
            msgError.style.display = 'none';

            // Simulación de reenvío: Llama de nuevo a iniciarRegistro
            const formData = new FormData(form);
            formData.append('email', emailInput.value); // Solo necesitamos el email
            
            fetch('/bytebox/public/resend_code_handler.php', { 
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    // Si el servidor responde con 4xx o 5xx, disparamos el error de conexión
                    throw new Error(`Error HTTP: ${response.status} - El servidor devolvió un error.`);
                }
                return response.json(); // Intentar procesar JSON
            })
            .then(data => {
                if (data.success) {
                    // ÉXITO: Iniciar el temporizador
                    startResendTimer(RESEND_DELAY_SECONDS); 
                    msgError.textContent = "¡Nuevo código enviado! Revisa tu bandeja.";
                    msgError.style.color = 'green';
                    msgError.style.display = 'block';
                } else {
                    // FALLO LÓGICO: El backend devolvió un error de validación (ej. email ya existe)
                    msgError.textContent = data.message;
                    msgError.style.color = 'red';
                    msgError.style.display = 'block';
                    
                    // Restaurar botón (ya que el problema no es el envío sino la lógica)
                    btnResend.textContent = "Reenviar código"; 
                    btnResend.disabled = false;
                }
                msgError.style.display = 'block';
            })
            .catch(err => {
                // Este catch se activa en caso de fallo de red o si el backend devuelve HTML de error (PHP)
                console.error('Error al reenviar:', err);
                msgError.textContent = "Error de conexión o JSON inválido. Revisa el log del servidor.";
                msgError.style.color = 'red';
                msgError.style.display = 'block';
                
                // Restaurar botón (permitir reintento inmediato si fue fallo de red)
                btnResend.textContent = "Reenviar código"; 
                btnResend.disabled = false;
            })
        });
    }

    /**
     * Calcula la fortaleza de la contraseña
     * @param {string} password 
     * @returns {Object}
     */
    function calculatePasswordStrength(password) {
        let score = 0;
        let feedback = [];

        if (password.length === 0) {
            return { score: 0, level: 'none', feedback: [] };
        }

        // Longitud
        if (password.length >= 6) score += 1;
        if (password.length >= 8) score += 1;
        if (password.length >= 12) score += 1;

        // Complejidad
        if (/[a-z]/.test(password)) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;

        // Feedback
        if (password.length < 6) feedback.push('Mínimo 6 caracteres');
        if (!/[a-z]/.test(password)) feedback.push('Incluye minúsculas');
        if (!/[A-Z]/.test(password)) feedback.push('Incluye mayúsculas');
        if (!/[0-9]/.test(password)) feedback.push('Incluye números');

        // Nivel
        let level = 'weak';
        if (score >= 5) level = 'strong';
        else if (score >= 3) level = 'medium';

        return { score, level, feedback };
    }

    /**
     * Actualiza la UI de fortaleza de contraseña
     * @param {Object} strength 
     * @param {HTMLElement} strengthDiv 
     * @param {HTMLElement} hintElement 
     */
    /* function updatePasswordStrengthUI(strength, strengthDiv, hintElement) {
        // Limpiar clases anteriores
        strengthDiv.className = 'password-strength';
        
        let strengthTextSpan = strengthDiv.querySelector('.strength-label');

        if (strength.level === 'none' || passwordInput.value.length === 0) {
            strengthDiv.style.display = 'none';
            hintElement.textContent = 'Mínimo 6 caracteres';
            hintElement.className = 'hint';
            hintElement.style.display = 'block';
            if (strengthTextSpan) strengthTextSpan.remove();
            return;
        } else {
            strengthDiv.classList.add(strength.level);
            
            // Texto del indicador
            let strengthText = '';
            switch (strength.level) {
                case 'weak':
                    strengthText = 'Débil';
                    break;
                case 'medium':
                    strengthText = 'Media';
                    break;
                case 'strong':
                    strengthText = 'Fuerte';
                    break;
            }
            
            strengthDiv.textContent = `Fortaleza: ${strengthText}`;
            
            // Actualizar hint
            if (strength.feedback.length > 0) {
                hintElement.textContent = strength.feedback.join(' • '); // Separador más limpio
                hintElement.className = 'hint warning';
            } else {
                hintElement.textContent = '✓ Contraseña segura';
                hintElement.className = 'hint success';
            }
        }
    } */

    function updatePasswordStrengthUI(strength, strengthDiv, hintElement) {
        // 1. Limpiar y configurar el DIV padre
        strengthDiv.className = 'password-strength';
        
        // 2. Buscamos el SPAN para el texto de Fortaleza (o lo creamos si fue eliminado)
        let strengthTextSpan = strengthDiv.querySelector('.strength-label');

        if (strength.level === 'none' || passwordInput.value.length === 0) {
            // CRÍTICO: Eliminamos !important y forzamos 'none'
            strengthDiv.style.display = 'none'; 
            hintElement.textContent = 'Mínimo 6 caracteres';
            hintElement.className = 'hint';
            hintElement.style.display = 'block';
            if (strengthTextSpan) strengthTextSpan.remove();
            return;
        }
        
        // 3. Si hay contenido, configuramos el display y la clase de color
        strengthDiv.style.display = 'inline-flex';
        strengthDiv.style.flexDirection = 'column';
        strengthDiv.style.justifyContent = 'center';
        strengthDiv.style.alignItems = 'flex-start';
        strengthDiv.classList.add(strength.level);
        
        // 4. Crear el SPAN si no existe (la primera vez)
        if (!strengthTextSpan) {
            strengthTextSpan = document.createElement('span');
            strengthTextSpan.className = 'strength-label';
            // Insertamos el nuevo span antes del elemento P (hintElement), que ya está anidado
            strengthDiv.insertBefore(strengthTextSpan, hintElement); 
        }

        // 5. Asignar el texto al SPAN (no destructivo)
        let strengthText = '';
        switch (strength.level) {
            case 'weak':
                strengthText = 'Débil';
                break;
            case 'medium':
                strengthText = 'Media';
                break;
            case 'strong':
                strengthText = 'Fuerte';
                break;
        }
        
        strengthTextSpan.textContent = `Fortaleza: ${strengthText}`;
        
        // 6. Actualizar hint (P)
        if (strength.feedback.length > 0) {
            hintElement.textContent = strength.feedback.join(' • '); // Uso punto medio para un look más limpio
            hintElement.className = 'hint warning';
        } else {
            hintElement.textContent = '✓ Contraseña segura';
            hintElement.className = 'hint success';
        }
        
        hintElement.style.display = 'block';
    }
    
    function startResendTimer(seconds) {
        const btnResend = document.getElementById('btnResend');
        const msgError = document.getElementById('modalError');
        resendCooldown = seconds;
        btnResend.disabled = true;

        // Detiene cualquier temporizador anterior
        if (resendTimer) {
            clearInterval(resendTimer);
        }
        
        // Inicia el nuevo temporizador
        resendTimer = setInterval(() => {
            resendCooldown--;
            if (resendCooldown <= 0) {
                clearInterval(resendTimer);
                btnResend.disabled = false;
                btnResend.textContent = "Reenviar código";
                // Mensaje de feedback de que el tiempo de espera terminó
                const msgError = document.getElementById('modalError');
                msgError.style.color = 'orange';
                msgError.textContent = "El tiempo de espera para el reenvío ha terminado. Puedes volver a enviarlo.";
                msgError.style.display = 'block';
                setTimeout(() => {
                    msgError.style.display = 'none';
                }, 5000);
            } else {
                btnResend.textContent = `Reenviar en (${resendCooldown}s)`;
            }
        }, 1000);
    }

    /**
     * Valida el formulario completo
     * @returns {boolean}
     */
    function validateForm() {
        let isValid = true;
        const errors = [];

        // Validar nombre
        const nombre = document.getElementById('nombre').value.trim();
        if (nombre.length < 2) {
            errors.push('El nombre debe tener al menos 2 caracteres');
            isValid = false;
        }

        // Validar email
        const email = document.getElementById('email').value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            errors.push('El email no es válido');
            isValid = false;
        }

        // Validar contraseña
        const password = passwordInput.value;
        if (password.length < 6) {
            errors.push('La contraseña debe tener al menos 6 caracteres');
            isValid = false;
        }

        // Validar confirmación
        const confirmPassword = confirmPasswordInput.value;
        if (password !== confirmPassword) {
            errors.push('Las contraseñas no coinciden');
            isValid = false;
        }

        // Validar términos
        const terms = document.getElementById('terms').checked;
        if (!terms) {
            errors.push('Debes aceptar los términos y condiciones');
            isValid = false;
        }

        // Mostrar errores si los hay
        if (errors.length > 0) {
            alert('Por favor corrige los siguientes errores:\n\n' + errors.join('\n'));
        }

        return isValid;
    }

    // ---------------------------------------------------------------------
    // 🛑 NUEVA LÓGICA DE CONTROL UX: Habilitación del Botón 🛑
    // ---------------------------------------------------------------------

    function checkFormValidity() {
        // 1. Verificar contenido y longitud mínima (debe ser consistente con el backend)
        const isNameValid = nameInput && nameInput.value.trim().length >= 2;
        const isPasswordLengthValid = passwordInput && passwordInput.value.length >= 6;
        
        // 2. Verificar formato simple de email (para UX, la validación estricta queda en el backend)
        const isEmailFormatValid = emailInput && emailInput.value.includes('@') && emailInput.value.includes('.'); 
        
        // 3. Verificar match de contraseñas
        const doPasswordsMatch = passwordInput && confirmPasswordInput && 
                                 passwordInput.value.length > 0 && 
                                 passwordInput.value === confirmPasswordInput.value;
        
        // 4. Verificar aceptación de términos
        const isTermsAccepted = termsCheckbox && termsCheckbox.checked;

        const isFormReady = isNameValid && isEmailFormatValid && isPasswordLengthValid && doPasswordsMatch && isTermsAccepted;

        // Habilitar / Deshabilitar el botón
        if (submitBtn) {
            submitBtn.disabled = !isFormReady;
        }
    }

    // Auto-ocultar mensajes de alerta después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        }, 5000);
    });
});