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

    // === NUEVAS CONSTANTES DEL MODAL ===
    const modal = document.getElementById('verificationModal');
    const modalEmailDisplay = document.getElementById('modalEmailDisplay');
    const codeInput = document.getElementById('verificationCode');
    const btnVerify = document.getElementById('btnVerify');
    const btnResend = document.getElementById('btnResend');
    const btnCancel = document.getElementById('btnCancel');
    const msgError = document.getElementById('modalError');
    const msgSuccess = document.getElementById('modalSuccess');

    if (passwordStrengthDiv && passwordHint && passwordStrengthDiv.parentNode === passwordHint.parentNode) {
        // Mueve la etiqueta <p> (passwordHint) para que sea el último hijo del <div> (passwordStrengthDiv)
        passwordStrengthDiv.appendChild(passwordHint);
        // Esto consolida ambos elementos en el mismo contenedor padre lógico.
        console.log("✅ Anidamiento DOM: El hint (P) ahora es hijo del strengthDiv (DIV).");
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
            btnResend.disabled = true;
            btnResend.textContent = "Reenviando...";
            msgError.style.display = 'none';

            // Simulación de reenvío: Llama de nuevo a iniciarRegistro
            const formData = new FormData(form);
            formData.append('redirect', getRedirectParam()); // Mantener los datos originales
            
            fetch('/bytebox/public/auth/iniciarRegistro', { 
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    msgError.textContent = "¡Nuevo código enviado! Revisa tu bandeja.";
                    msgError.style.color = 'green';
                } else {
                    msgError.textContent = data.message || "Error al reenviar. Intenta de nuevo más tarde.";
                    msgError.style.color = 'red';
                }
            })
            .catch(err => {
                msgError.textContent = "Error de conexión al reenviar.";
                msgError.style.color = 'red';
            })
            .finally(() => {
                // Delay de seguridad de 30 segundos
                setTimeout(() => {
                    btnResend.textContent = "Reenviar código";
                    btnResend.disabled = false;
                    msgError.style.color = 'red'; // Restablecer color de error
                }, 30000); 
                msgError.style.display = 'block';
            });
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
    function updatePasswordStrengthUI(strength, strengthDiv, hintElement) {
        // Limpiar clases anteriores
        strengthDiv.className = 'password-strength';
        
        if (strength.level === 'none') {
            strengthDiv.style.display = 'none';
            hintElement.textContent = 'Mínimo 6 caracteres';
            hintElement.className = 'hint';
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