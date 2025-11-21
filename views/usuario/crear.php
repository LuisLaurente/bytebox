<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
    <link rel="stylesheet" href="<?= url('/css/usuarioCrear.css') ?>">
<body>
    <div class="usuario-crear-layout">
        <!-- Incluir navegación lateral fija -->
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

        <div class="usuario-crear-main">
            <main class="usuario-crear-content">
                <div class="usuario-crear-container">
                    <div class="usuario-crear-card">
                        <!-- Header -->
                        <div class="usuario-crear-header-section">
                            <div class="usuario-crear-title-wrapper">
                                <h2 class="usuario-crear-title">Crear Nuevo Usuario</h2>
                                <p class="usuario-crear-subtitle">Completa la información para crear un nuevo usuario</p>
                            </div>
                            <a href="<?= url('/usuario') ?>" class="usuario-crear-close-btn">
                                <svg class="usuario-crear-close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </a>
                        </div>
                        
                        <!-- Mensajes de error -->
                        <?php if (!empty($_GET['error'])): ?>
                            <div class="usuario-crear-error-message">
                                <?= htmlspecialchars($_GET['error']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Formulario -->
                        <form method="POST" action="<?= url('/usuario/store') ?>" class="usuario-crear-form">
                            <div class="usuario-crear-form-grid">
                                <!-- Nombre -->
                                <div class="usuario-crear-form-group">
                                    <label for="nombre" class="usuario-crear-label">
                                        Nombre Completo *
                                    </label>
                                    <input type="text" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                                           required 
                                           class="usuario-crear-input"
                                           placeholder="Ingresa el nombre completo">
                                </div>
                                
                                <!-- Email -->
                                <div class="usuario-crear-form-group">
                                    <label for="email" class="usuario-crear-label">
                                        Email *
                                    </label>
                                    <input type="email" 
                                           id="crear_email" 
                                           name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                           required 
                                           class="usuario-crear-input"
                                           placeholder="usuario@ejemplo.com">
                                </div>
                            </div>
                            
                            <div class="usuario-crear-form-grid">
                                <!-- Contraseña -->
                                <div class="usuario-crear-form-group">
                                    <label for="password" class="usuario-crear-label">
                                        Contraseña *
                                    </label>
                                    <div class="usuario-crear-password-wrapper">
                                        <input type="password" 
                                               id="crear_password" 
                                               name="password" 
                                               required 
                                               minlength="6"
                                               class="usuario-crear-input usuario-crear-password-input"
                                               placeholder="Mínimo 6 caracteres">
                                        <button type="button" 
                                                onclick="togglePassword('crear_password')"
                                                class="usuario-crear-password-toggle">
                                            <svg class="usuario-crear-password-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Confirmar Contraseña -->
                                <div class="usuario-crear-form-group">
                                    <label for="confirmar_password" class="usuario-crear-label">
                                        Confirmar Contraseña *
                                    </label>
                                    <div class="usuario-crear-password-wrapper">
                                        <input type="password" 
                                               id="crear_confirmar_password" 
                                               name="confirmar_password" 
                                               required 
                                               minlength="6"
                                               class="usuario-crear-input usuario-crear-password-input"
                                               placeholder="Confirma la contraseña">
                                        <button type="button" 
                                                onclick="togglePassword('crear_confirmar_password')"
                                                class="usuario-crear-password-toggle">
                                            <svg class="usuario-crear-password-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="usuario-crear-form-grid">
                                <!-- Rol -->
                                <div class="usuario-crear-form-group">
                                    <label for="rol" class="usuario-crear-label">
                                        Rol *
                                    </label>
                                    <select id="rol" 
                                            name="rol" 
                                            required 
                                            class="usuario-crear-select">
                                        <?php foreach ($roles as $rol): ?>
                                            <?php if ($rol['id'] == 2) continue; ?>
                                            <option value="<?= htmlspecialchars($rol['id']) ?>" 
                                                    <?= ($_POST['rol'] ?? '') == $rol['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($rol['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Estado -->
                                <div class="usuario-crear-form-group">
                                    <label class="usuario-crear-label">
                                        Estado
                                    </label>
                                    <div class="usuario-crear-checkbox-wrapper">
                                        <input type="checkbox" 
                                               id="activo" 
                                               name="activo" 
                                               value="1"
                                               <?= isset($_POST['activo']) || !isset($_POST['submit']) ? 'checked' : '' ?>
                                               class="usuario-crear-checkbox">
                                        <label for="activo" class="usuario-crear-checkbox-label">
                                            Usuario activo
                                        </label>
                                    </div>
                                    <p class="usuario-crear-help-text">
                                        Los usuarios inactivos no podrán acceder al sistema
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Información adicional -->
                            <div class="usuario-crear-info-box">
                                <div class="usuario-crear-info-content">
                                    <svg class="usuario-crear-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div class="usuario-crear-info-text">
                                        <p class="usuario-crear-info-title">Información importante:</p>
                                        <ul class="usuario-crear-info-list">
                                            <li>La contraseña debe tener al menos 6 caracteres</li>
                                            <li>El email debe ser único en el sistema</li>
                                            <li>Los administradores tienen acceso completo al panel</li>
                                            <li>Los usuarios regulares tienen acceso limitado</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botones -->
                            <div class="usuario-crear-actions">
                                <a href="<?= url('/usuario') ?>" 
                                   class="usuario-crear-cancel-btn">
                                    Cancelar
                                </a>
                                <button type="submit" 
                                        name="submit"
                                        class="usuario-crear-submit-btn">
                                    <svg class="usuario-crear-submit-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Crear Usuario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
    field.setAttribute('type', type);
}

// Validación de contraseñas en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('crear_password');
    const confirmPassword = document.getElementById('crear_confirmar_password');
    
    function validatePasswords() {
        if (password.value && confirmPassword.value) {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Las contraseñas no coinciden');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
    }
    
    password.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
});
</script>
</body>
</html>