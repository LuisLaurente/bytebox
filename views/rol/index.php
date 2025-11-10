<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Roles - Sistema</title>
    <link rel="stylesheet" href="<?= url('/css/rolIndex.css') ?>">
  
</head>
<body>
    <div class="rol-layout">
        <!-- NAVBAR DENTRO del contenedor principal -->
            <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        <!-- Main Content -->
        <div class="rol-main-content">
            <!-- Content -->
            <main class="rol-content">
                <div class="rol-container">
                    <div class="rol-card">
                        <!-- Estadísticas -->
                        <?php if (isset($estadisticas)): ?>
                        <div class="stats-grid">
                            <div class="stat-card blue">
                                <div class="stat-content">
                                    <div class="stat-icon blue">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                        </svg>
                                    </div>
                                    <div class="stat-info">
                                        <p class="stat-label">Total Roles</p>
                                        <p class="stat-value blue"><?= $estadisticas['total'] ?? 0 ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card green">
                                <div class="stat-content">
                                    <div class="stat-icon green">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="stat-info">
                                        <p class="stat-label">Roles Activos</p>
                                        <p class="stat-value green"><?= $estadisticas['activos'] ?? 0 ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stat-card yellow">
                                <div class="stat-content">
                                    <div class="stat-icon yellow">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <div class="stat-info">
                                        <p class="stat-label">Roles Inactivos</p>
                                        <p class="stat-value yellow"><?= $estadisticas['inactivos'] ?? 0 ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Mensajes de estado -->
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-error">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Header de la tabla -->
                        <div class="table-header">
                            <h2 class="table-title">Gestión de Roles</h2>
                            <a href="<?= url('/rol/crear') ?>" class="btn btn-primary">
                                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Nuevo Rol
                            </a>
                        </div>
                        
                        <!-- Tabla de roles -->
                        <div class="table-container">
                            <table class="roles-table">
                                <thead>
                                    <tr>
                                        <th>Rol</th>
                                        <th>Descripción</th>
                                        <th>Usuarios</th>
                                        <th>Estado</th>
                                        <th>Fecha Creación</th>
                                        <th class="acciones-cell">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($roles)): ?>
                                        <?php foreach ($roles as $rol): ?>
                                            <tr>
                                                <td>
                                                    <div class="role-cell">
                                                        <div class="role-avatar">
                                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                            </svg>
                                                        </div>
                                                        <div class="role-info">
                                                            <div class="role-name">
                                                                <?= htmlspecialchars($rol['nombre']) ?>
                                                            </div>
                                                            <div class="role-id">
                                                                ID: <?= $rol['id'] ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="role-description">
                                                        <?= htmlspecialchars($rol['descripcion'] ?: 'Sin descripción') ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge users-badge">
                                                        <?= $rol['total_usuarios'] ?? 0 ?> usuarios
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $rol['activo'] ? 'badge-success' : 'badge-danger' ?>">
                                                        <?= $rol['activo'] ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                </td>
                                                <td class="fecha_creacion">
                                                    <?= date('d/m/Y', strtotime($rol['fecha_creacion'])) ?>
                                                </td>
                                                <td class="acciones-cell">
                                                    <div class="acciones-container">
                                                        <!-- Botón Ver -->
                                                        <a href="<?= url('/rol/ver/' . $rol['id']) ?>" 
                                                           class="btn-action btn-edit"
                                                           title="Ver detalles">
                                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                            </svg>
                                                        </a>
                                                        
                                                        <!-- Botón Editar -->
                                                        <a href="<?= url('/rol/editar/' . $rol['id']) ?>" 
                                                           class="btn-action btn-edit"
                                                           title="Editar rol">
                                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                        </a>
                                                        
                                                        <!-- Botón Cambiar Estado -->
                                                        <?php if (!in_array($rol['nombre'], ['admin', 'usuario'])): ?>
                                                            <form method="POST" action="<?= url('/rol/cambiarEstado/' . $rol['id']) ?>" 
                                                                  onsubmit="return confirm('¿Estás seguro de cambiar el estado de este rol?')" class="form-inline">
                                                                <button type="submit" 
                                                                        class="btn-action btn-toggle"
                                                                        title="<?= $rol['activo'] ? 'Desactivar' : 'Activar' ?> rol">
                                                                    <?php if ($rol['activo']): ?>
                                                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                                        </svg>
                                                                    <?php else: ?>
                                                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                        </svg>
                                                                    <?php endif; ?>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Botón Eliminar -->
                                                        <?php if (!in_array($rol['nombre'], ['admin', 'usuario']) && $rol['total_usuarios'] == 0): ?>
                                                            <form method="POST" action="<?= url('/rol/eliminar/' . $rol['id']) ?>" 
                                                                  onsubmit="return confirm('¿Estás seguro de eliminar este rol? Esta acción no se puede deshacer.')" class="form-inline">
                                                                <button type="submit" 
                                                                        class="btn-action btn-delete"
                                                                        title="Eliminar rol">
                                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">
                                                <div class="empty-state">
                                                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                    </svg>
                                                    <p class="empty-title">No hay roles registrados</p>
                                                    <p class="empty-description">Crea tu primer rol haciendo clic en el botón "Nuevo Rol"</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>