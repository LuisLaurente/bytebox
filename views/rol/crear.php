<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Rol - Sistema</title>
    <link rel="stylesheet" href="<?= url('/css/rolCrear.css') ?>">
    <?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
</head>
<body>
    <div class="rol-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        <!-- Main Content -->
        <div class="rol-main-content">
            <!-- Content -->
            <main class="rol-content">
                <div class="rol-container">
                    <!-- Encabezado -->
                    <div class="page-header">
                        <div class="header-content">
                            <div class="header-text">
                                <h1>Crear Nuevo Rol</h1>
                                <p>Define un nuevo rol con permisos específicos para el sistema</p>
                            </div>
                            <a href="<?= url('/rol') ?>" class="btn btn-secondary">
                                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Volver
                            </a>
                        </div>
                    </div>

                    <!-- Mensajes de estado -->
                    <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <svg class="alert-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <?= htmlspecialchars(urldecode($_GET['error'])) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Formulario -->
                    <div class="form-card">
                        <form method="POST" action="<?= url('/rol/store') ?>">
                            <!-- Información básica -->
                            <div class="form-section">
                                <h3 class="section-title">Información Básica</h3>
                                
                                <div class="form-grid">
                                    <!-- Nombre -->
                                    <div class="form-group">
                                        <label for="nombre" class="form-label">
                                            Nombre del Rol *
                                        </label>
                                        <input type="text" 
                                               id="nombre" 
                                               name="nombre" 
                                               required 
                                               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                                               placeholder="Ej: Moderador, Vendedor, etc."
                                               class="form-input">
                                    </div>

                                    <!-- Estado -->
                                    <div class="form-group">
                                        <label class="form-label">
                                            Estado
                                        </label>
                                        <div class="checkbox-group" style="background: transparent; border: none; padding: 0;">
                                            <input type="checkbox" 
                                                   id="activo" 
                                                   name="activo" 
                                                   <?= ($_POST['activo'] ?? true) ? 'checked' : '' ?>
                                                   class="checkbox-input">
                                            <label for="activo" class="checkbox-label" style="margin-bottom: 0;">
                                                Rol activo
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Descripción -->
                                <div class="form-group mt-4">
                                    <label for="descripcion" class="form-label">
                                        Descripción
                                    </label>
                                    <textarea id="descripcion" 
                                              name="descripcion" 
                                              rows="3" 
                                              placeholder="Describe las responsabilidades y alcance de este rol..."
                                              class="form-textarea"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <!-- Permisos -->
                            <div class="form-section">
                                <h3 class="section-title">Permisos del Sistema</h3>
                                <p class="section-description">Selecciona los permisos que tendrá este rol en el sistema</p>
                                
                                <div class="permisos-grid">
                                    <?php foreach ($permisosDisponibles as $permiso => $descripcion): ?>
                                    <div class="checkbox-group">
                                        <input type="checkbox" 
                                               id="permiso_<?= $permiso ?>" 
                                               name="permisos[]" 
                                               value="<?= $permiso ?>"
                                               <?= in_array($permiso, $_POST['permisos'] ?? []) ? 'checked' : '' ?>
                                               class="checkbox-input">
                                        <div class="checkbox-content">
                                            <label for="permiso_<?= $permiso ?>" class="checkbox-label">
                                                <?= ucfirst(str_replace('_', ' ', $permiso)) ?>
                                            </label>
                                            <p class="checkbox-description">
                                                <?= htmlspecialchars($descripcion) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="form-actions">
                                <a href="<?= url('/rol') ?>" class="btn btn-outline">
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Crear Rol
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