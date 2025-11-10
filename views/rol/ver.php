<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>

<body>
    <div class="flex h-screen">
        <!-- Incluir navegación lateral fija -->
        <div class="fixed inset-y-0 left-0 z-50">
            <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        </div>
        <div class="flex-1 ml-64 flex flex-col min-h-screen">

            <main class="flex-1 p-2 bg-gray-50 overflow-y-auto">
                <!-- Incluir header superior fijo -->
                <div class="sticky top-0 z-40">
                    <?php include_once __DIR__ . '/../admin/includes/header.php'; ?>
                </div>

                <div class="flex-1 p-6 bg-gray-50 overflow-y-auto">
        <!-- Encabezado -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Detalles del Rol</h1>
                    <p class="text-gray-600 mt-2">Información completa del rol: <span class="font-semibold"><?= htmlspecialchars($rol['nombre']) ?></span></p>
                </div>
                <div class="flex gap-3">
                    <a href="<?= url('/rol/editar/' . $rol['id']) ?>" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Editar
                    </a>
                    <a href="<?= url('/rol') ?>" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition duration-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Volver
                    </a>
                </div>
            </div>
        </div>

        <!-- Información básica -->
        <div class="bg-white rounded-lg shadow-sm p-8 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Información Básica</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Rol</label>
                    <div class="bg-gray-50 px-3 py-2 rounded-lg border">
                        <?= htmlspecialchars($rol['nombre']) ?>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <div class="bg-gray-50 px-3 py-2 rounded-lg border">
                        <?php if ($rol['activo']): ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Activo
                            </span>
                        <?php else: ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                Inactivo
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($rol['descripcion'])): ?>
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <div class="bg-gray-50 px-3 py-2 rounded-lg border">
                    <?= htmlspecialchars($rol['descripcion']) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Fechas -->
            <?php if (isset($rol['created_at'])): ?>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Creación</label>
                    <div class="bg-gray-50 px-3 py-2 rounded-lg border text-sm">
                        <?= date('d/m/Y H:i:s', strtotime($rol['created_at'])) ?>
                    </div>
                </div>
                <?php if (isset($rol['updated_at'])): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Última Modificación</label>
                    <div class="bg-gray-50 px-3 py-2 rounded-lg border text-sm">
                        <?= date('d/m/Y H:i:s', strtotime($rol['updated_at'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Permisos -->
        <div class="bg-white rounded-lg shadow-sm p-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Permisos Asignados</h3>
            
            <?php if (!empty($rol['permisos'])): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($rol['permisos'] as $permiso): ?>
                    <?php if (isset($permisosDisponibles[$permiso])): ?>
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-full mr-3">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">
                                    <?= ucfirst(str_replace('_', ' ', $permiso)) ?>
                                </h4>
                                <p class="text-xs text-gray-600 mt-1">
                                    <?= htmlspecialchars($permisosDisponibles[$permiso]) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Resumen de permisos -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-blue-800">
                        Este rol tiene <?= count($rol['permisos']) ?> de <?= count($permisosDisponibles) ?> permisos disponibles
                    </span>
                </div>
            </div>
            
            <?php else: ?>
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <h4 class="text-lg font-medium text-gray-900 mb-2">Sin Permisos Asignados</h4>
                <p class="text-gray-600">Este rol no tiene permisos asignados actualmente.</p>
                <a href="<?= url('/rol/editar/' . $rol['id']) ?>" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition duration-200 mt-4">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Asignar Permisos
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Permisos faltantes (opcional) -->
        <?php 
        $permisosFaltantes = array_diff(array_keys($permisosDisponibles), $rol['permisos']);
        if (!empty($permisosFaltantes)): 
        ?>
        <div class="bg-white rounded-lg shadow-sm p-8 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Permisos Disponibles</h3>
            <p class="text-sm text-gray-600 mb-4">Permisos que se pueden asignar a este rol:</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($permisosFaltantes as $permiso): ?>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-full mr-3">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-700">
                                <?= ucfirst(str_replace('_', ' ', $permiso)) ?>
                            </h4>
                            <p class="text-xs text-gray-500 mt-1">
                                <?= htmlspecialchars($permisosDisponibles[$permiso]) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
