/**
 * JavaScript para el formulario de registro
 * Incluye validaciones en tiempo real y mejoras UX
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registroForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordStrengthDiv = document.getElementById('passwordStrength');
    const passwordHint = document.getElementById('passwordHint');
    const passwordMatch = document.getElementById('passwordMatch');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitSpinner = document.getElementById('submitSpinner');

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

    // Manejo del envío del formulario
    if (form) {
        form.addEventListener('submit', function(e) {
            // Mostrar spinner de carga
            if (submitText && submitSpinner) {
                submitText.classList.add('hidden');
                submitSpinner.classList.remove('hidden');
                submitBtn.disabled = true;
            }

            // Validaciones finales antes del envío
            const isValid = validateForm();
            if (!isValid) {
                e.preventDefault();
                // Restaurar botón
                if (submitText && submitSpinner) {
                    submitText.classList.remove('hidden');
                    submitSpinner.classList.add('hidden');
                    submitBtn.disabled = false;
                }
                return false;
            }
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
            strengthDiv.style.display = 'block';
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
                hintElement.textContent = strength.feedback.join(', ');
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