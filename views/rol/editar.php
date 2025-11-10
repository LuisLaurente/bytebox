<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
    <link rel="stylesheet" href="<?= url('/css/rolEditar.css') ?>">

<body>
    <div class="_editarRol_layout">
        <!-- Incluir navegación lateral fija -->
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        <div class="_editarRol_main">
            <main class="_editarRol_content">
                <div class="_editarRol_container">
                    <!-- Encabezado -->
                    <div class="_editarRol_header-section">
                        <div class="_editarRol_header-content">
                            <div class="_editarRol_title-wrapper">
                                <h1 class="_editarRol_page-title">Editar Rol</h1>
                                <p class="_editarRol_page-description">Modifica los permisos y configuración del rol: <span class="_editarRol_role-name"><?= htmlspecialchars($rol['nombre']) ?></span></p>
                            </div>
                            <a href="<?= url('/rol') ?>" 
                               class="_editarRol_back-button">
                                <svg class="_editarRol_back-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Volver
                            </a>
                        </div>
                    </div>

                    <!-- Mensajes de estado -->
                    <?php if (isset($_GET['error'])): ?>
                    <div class="_editarRol_error-message">
                        <svg class="_editarRol_error-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <?= htmlspecialchars(urldecode($_GET['error'])) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Formulario -->
                    <div class="_editarRol_card">
                        <form method="POST" action="<?= url('/rol/actualizar/' . $rol['id']) ?>" class="_editarRol_form">
                            <!-- Información básica -->
                            <div class="_editarRol_form-section">
                                <h3 class="_editarRol_section-title">Información Básica</h3>
                                
                                <div class="_editarRol_form-grid">
                                    <!-- Nombre -->
                                    <div class="_editarRol_form-group">
                                        <label for="nombre" class="_editarRol_label">
                                            Nombre del Rol *
                                        </label>
                                        <input type="text" 
                                               id="nombre" 
                                               name="nombre" 
                                               required 
                                               value="<?= htmlspecialchars($_POST['nombre'] ?? $rol['nombre']) ?>"
                                               placeholder="Ej: Moderador, Vendedor, etc."
                                               class="_editarRol_input">
                                    </div>

                                    <!-- Estado -->
                                    <div class="_editarRol_form-group">
                                        <label class="_editarRol_label">
                                            Estado
                                        </label>
                                        <div class="_editarRol_checkbox-wrapper">
                                            <input type="checkbox" 
                                                   id="activo" 
                                                   name="activo" 
                                                   <?= ($_POST['activo'] ?? $rol['activo']) ? 'checked' : '' ?>
                                                   class="_editarRol_checkbox">
                                            <label for="activo" class="_editarRol_checkbox-label">
                                                Rol activo
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Descripción -->
                                <div class="_editarRol_form-group-full">
                                    <label for="descripcion" class="_editarRol_label">
                                        Descripción
                                    </label>
                                    <textarea id="descripcion" 
                                              name="descripcion" 
                                              rows="3" 
                                              placeholder="Describe las responsabilidades y alcance de este rol..."
                                              class="_editarRol_textarea"><?= htmlspecialchars($_POST['descripcion'] ?? $rol['descripcion']) ?></textarea>
                                </div>
                            </div>

                            <!-- Permisos -->
                            <div class="_editarRol_permissions-section">
                                <h3 class="_editarRol_section-title">Permisos del Sistema</h3>
                                <p class="_editarRol_section-description">Selecciona los permisos que tendrá este rol en el sistema</p>
                                
                                <div class="_editarRol_permissions-grid">
                                    <?php 
                                    $permisosSeleccionados = $_POST['permisos'] ?? $rol['permisos'] ?? [];
                                    foreach ($permisosDisponibles as $permiso => $descripcion): 
                                    ?>
                                    <div class="_editarRol_permission-item">
                                        <div class="_editarRol_permission-content">
                                            <input type="checkbox" 
                                                   id="permiso_<?= $permiso ?>" 
                                                   name="permisos[]" 
                                                   value="<?= $permiso ?>"
                                                   <?= in_array($permiso, $permisosSeleccionados) ? 'checked' : '' ?>
                                                   class="_editarRol_permission-checkbox">
                                            <div class="_editarRol_permission-info">
                                                <label for="permiso_<?= $permiso ?>" class="_editarRol_permission-label">
                                                    <?= ucfirst(str_replace('_', ' ', $permiso)) ?>
                                                </label>
                                                <p class="_editarRol_permission-description">
                                                    <?= htmlspecialchars($descripcion) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Información adicional -->
                            <?php if (isset($rol['created_at'])): ?>
                            <div class="_editarRol_info-box">
                                <h4 class="_editarRol_info-title">Información del Registro</h4>
                                <div class="_editarRol_info-grid">
                                    <div>
                                        <span class="_editarRol_info-label">Fecha de creación:</span>
                                        <?= date('d/m/Y H:i', strtotime($rol['created_at'])) ?>
                                    </div>
                                    <?php if (isset($rol['updated_at'])): ?>
                                    <div>
                                        <span class="_editarRol_info-label">Última modificación:</span>
                                        <?= date('d/m/Y H:i', strtotime($rol['updated_at'])) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Botones de acción -->
                            <div class="_editarRol_actions">
                                <a href="<?= url('/rol') ?>" 
                                   class="_editarRol_cancel-button">
                                    Cancelar
                                </a>
                                <button type="submit" 
                                        class="_editarRol_submit-button">
                                    <svg class="_editarRol_submit-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
</body>
</html>