<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
    <link rel="stylesheet" href="<?= url('/css/usuarioEditar.css') ?>">
<body>
    <div class="usuario-editar-layout">
        <!-- Incluir navegación lateral fija -->
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        <div class="usuario-editar-main">
            <main class="usuario-editar-content">
                <div class="usuario-editar-container">
                    <div class="usuario-editar-card">
                        <!-- Header -->
                        <div class="usuario-editar-header-section">
                            <div class="usuario-editar-title-wrapper">
                                <h2 class="usuario-editar-title">Editar Usuario</h2>
                                <p class="usuario-editar-subtitle">Modifica la información del usuario: <?= htmlspecialchars($usuario['nombre']) ?></p>
                            </div>
                            <a href="<?= url('/usuario') ?>" class="usuario-editar-close-btn">
                                <svg class="usuario-editar-close-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </a>
                        </div>
                        
                        <!-- Mensajes de estado -->
                        <?php if (!empty($_GET['error'])): ?>
                            <div class="usuario-editar-error-message">
                                <?= htmlspecialchars($_GET['error']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($_GET['success'])): ?>
                            <div class="usuario-editar-success-message">
                                <?= htmlspecialchars($_GET['success']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Información del usuario -->
                        <div class="usuario-editar-info-box">
                            <div class="usuario-editar-info-content">
                                <div class="usuario-editar-avatar">
                                    <span class="usuario-editar-avatar-initials">
                                        <?= strtoupper(substr($usuario['nombre'], 0, 2)) ?>
                                    </span>
                                </div>
                                <div class="usuario-editar-info-details">
                                    <p class="usuario-editar-info-text">Usuario ID: <?= $usuario['id'] ?></p>
                                    <p class="usuario-editar-info-text">Registrado: <?= date('d/m/Y H:i', strtotime($usuario['fecha_creacion'])) ?></p>
                                    <?php if (!empty($usuario['fecha_actualizacion'])): ?>
                                        <p class="usuario-editar-info-text">Última actualización: <?= date('d/m/Y H:i', strtotime($usuario['fecha_actualizacion'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Formulario -->
                        <form method="POST" action="<?= url('/usuario/actualizar/' . $usuario['id']) ?>" class="usuario-editar-form">
                            <div class="usuario-editar-form-grid">
                                <!-- Nombre -->
                                <div class="usuario-editar-form-group">
                                    <label for="nombre" class="usuario-editar-label">
                                        Nombre Completo *
                                    </label>
                                    <input type="text" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="<?= htmlspecialchars($usuario['nombre']) ?>"
                                           required 
                                           class="usuario-editar-input"
                                           placeholder="Ingresa el nombre completo">
                                </div>
                                
                                <!-- Email -->
                                <div class="usuario-editar-form-group">
                                    <label for="email" class="usuario-editar-label">
                                        Email *
                                    </label>
                                    <input type="email" 
                                           id="crear_email" 
                                           name="email" 
                                           value="<?= htmlspecialchars($usuario['email']) ?>"
                                           required 
                                           class="usuario-editar-input"
                                           placeholder="usuario@ejemplo.com">
                                </div>
                            </div>
                            
                            <div class="usuario-editar-form-grid">
                                <!-- Nueva Contraseña -->
                                <div class="usuario-editar-form-group">
                                    <label for="password" class="usuario-editar-label">
                                        Nueva Contraseña
                                    </label>
                                    <div class="usuario-editar-password-wrapper">
                                        <input type="password" 
                                               id="crear_password" 
                                               name="password" 
                                               minlength="6"
                                               class="usuario-editar-input usuario-editar-password-input"
                                               placeholder="Dejar vacío para mantener actual">
                                        <button type="button" 
                                                onclick="togglePassword('crear_password')"
                                                class="usuario-editar-password-toggle">
                                            <svg class="usuario-editar-password-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="usuario-editar-help-text">
                                        Dejar vacío para mantener la contraseña actual
                                    </p>
                                </div>
                                
                                <!-- Confirmar Nueva Contraseña -->
                                <div class="usuario-editar-form-group">
                                    <label for="confirmar_password" class="usuario-editar-label">
                                        Confirmar Nueva Contraseña
                                    </label>
                                    <div class="usuario-editar-password-wrapper">
                                        <input type="password" 
                                               id="crear_confirmar_password" 
                                               name="confirmar_password" 
                                               minlength="6"
                                               class="usuario-editar-input usuario-editar-password-input"
                                               placeholder="Confirma la nueva contraseña">
                                        <button type="button" 
                                                onclick="togglePassword('crear_confirmar_password')"
                                                class="usuario-editar-password-toggle">
                                            <svg class="usuario-editar-password-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="usuario-editar-form-grid">
                                <!-- Rol -->
                                <div class="usuario-editar-form-group">
                                    <label for="rol" class="usuario-editar-label">
                                        Rol *
                                    </label>
                                    <select id="rol" 
                                            name="rol" 
                                            required 
                                            class="usuario-editar-select">
                                        <?php foreach ($roles as $rol): ?>
                                            <option value="<?= htmlspecialchars($rol['id']) ?>" 
                                                    <?= $usuario['rol_id'] == $rol['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($rol['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Estado -->
                                <div class="usuario-editar-form-group">
                                    <label class="usuario-editar-label">
                                        Estado
                                    </label>
                                    <div class="usuario-editar-checkbox-wrapper">
                                        <input type="checkbox" 
                                               id="activo" 
                                               name="activo" 
                                               value="1"
                                               <?= $usuario['activo'] ? 'checked' : '' ?>
                                               class="usuario-editar-checkbox">
                                        <label for="activo" class="usuario-editar-checkbox-label">
                                            Usuario activo
                                        </label>
                                    </div>
                                    <p class="usuario-editar-help-text">
                                        Los usuarios inactivos no podrán acceder al sistema
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Información adicional -->
                            <div class="usuario-editar-warning-box">
                                <div class="usuario-editar-warning-content">
                                    <svg class="usuario-editar-warning-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <div class="usuario-editar-warning-text">
                                        <p class="usuario-editar-warning-title">Consideraciones al editar:</p>
                                        <ul class="usuario-editar-warning-list">
                                            <li>Solo completa la contraseña si deseas cambiarla</li>
                                            <li>El email debe ser único en el sistema</li>
                                            <li>Los cambios de rol pueden afectar los permisos del usuario</li>
                                            <li>Desactivar un usuario impedirá su acceso inmediatamente</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botones -->
                            <div class="usuario-editar-actions">
                                <a href="<?= url('/usuario') ?>" 
                                   class="usuario-editar-cancel-btn">
                                    Cancelar
                                </a>
                                <button type="submit" 
                                        name="submit"
                                        class="usuario-editar-submit-btn">
                                    <svg class="usuario-editar-submit-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Guardar Cambios
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
        if (password.value || confirmPassword.value) {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Las contraseñas no coinciden');
            } else {
                confirmPassword.setCustomValidity('');
            }
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    password.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
});
</script>
</body>
</html>