<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema</title>
    <link rel="stylesheet" href="<?= url('/css/usuarioIndex.css') ?>">
    <?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
</head>
<body>
    <div class="usuario-layout">
        <!-- Sidebar - Solo incluir navbar.php aquí -->
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        <!-- Main Content -->
        <div class="usuario-main-content">
            <main class="usuario-content">
                <div class="usuario-container">
                    <div class="usuario-card">
                        <!-- Estadísticas -->
                        <?php if (isset($estadisticas)): ?>
                        <div class="stats-grid">
                            <div class="stat-card blue">
                                <div class="stat-content">
                                    <div class="stat-icon blue">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.239"></path>
                                        </svg>
                                    </div>
                                    <div class="stat-info">
                                        <p class="stat-label">Total Usuarios</p>
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
                                        <p class="stat-label">Usuarios Activos</p>
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
                                        <p class="stat-label">Usuarios Inactivos</p>
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
                            <h2 class="table-title">Gestión de Usuarios</h2>
                            <a href="<?= url('/usuario/crear') ?>" class="btn btn-primary">
                                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Nuevo Usuario
                            </a>
                        </div>
                        
                        <!-- Tabla de usuarios -->
                        <div class="table-container">
                            <table class="usuarios-table">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Fecha Registro</th>
                                        <th class="acciones-cell">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($usuarios)): ?>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-cell">
                                                        <div class="user-avatar">
                                                            <span class="user-initials">
                                                                <?= strtoupper(substr($usuario['nombre'], 0, 2)) ?>
                                                            </span>
                                                        </div>
                                                        <div class="user-info">
                                                            <div class="user-name">
                                                                <?= htmlspecialchars($usuario['nombre']) ?>
                                                            </div>
                                                            <div class="user-id">
                                                                ID: <?= $usuario['id'] ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($usuario['email']) ?></div>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $rolNombre = $usuario['rol_nombre'] ?? 'Sin rol';
                                                        $badgeClass = match(strtolower($rolNombre)) {
                                                            'admin' => 'badge-purple',
                                                            'moderador' => 'badge-blue',
                                                            'vendedor' => 'badge-green',
                                                            default => 'badge-gray'
                                                        };
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>">
                                                        <?= htmlspecialchars(ucfirst($rolNombre)) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $usuario['activo'] ? 'badge-success' : 'badge-danger' ?>">
                                                        <?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                </td>
                                                <td class="feccha_registro">
                                                    <?= date('d/m/Y', strtotime($usuario['fecha_creacion'])) ?>
                                                </td>
                                                <td class="acciones-cell">
                                                    <div class="acciones-container">
                                                        <!-- Botón Editar -->
                                                        <a href="<?= url('/usuario/editar/' . $usuario['id']) ?>" 
                                                           class="btn-action btn-edit"
                                                           title="Editar usuario">
                                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                        </a>
                                                        
                                                        <!-- Botón Cambiar Estado -->
                                                        <form method="POST" action="<?= url('/usuario/cambiarEstado/' . $usuario['id']) ?>" 
                                                              onsubmit="return confirm('¿Estás seguro de cambiar el estado de este usuario?')" class="form-inline">
                                                            <button type="submit" 
                                                                    class="btn-action btn-toggle <?= $usuario['activo'] ? '' : 'active' ?>"
                                                                    title="<?= $usuario['activo'] ? 'Desactivar' : 'Activar' ?> usuario">
                                                                <?php if ($usuario['activo']): ?>
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
                                                        
                                                        <!-- Botón Eliminar -->
                                                        <form method="POST" action="<?= url('/usuario/eliminar/' . $usuario['id']) ?>" 
                                                              onsubmit="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')" class="form-inline">
                                                            <button type="submit" 
                                                                    class="btn-action btn-delete"
                                                                    title="Eliminar usuario">
                                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">
                                                <div class="empty-state">
                                                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.239"></path>
                                                    </svg>
                                                    <p class="empty-title">No hay usuarios registrados</p>
                                                    <p class="empty-description">Crea tu primer usuario haciendo clic en el botón "Nuevo Usuario"</p>
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