<?php require_once __DIR__ . '/../../core/helpers/urlHelper.php'; ?>

<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>

<style>
    .reporte-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }
    .stats {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        flex: 1;
        background: white;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .stat-number {
        font-size: 2em;
        font-weight: bold;
        margin-bottom: 10px;
    }
    .success { color: #28a745; }
    .warning { color: #ffc107; }
    .danger { color: #dc3545; }
    
    .reporte-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .reporte-table th,
    .reporte-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }
    .reporte-table th {
        background: #f8f9fa;
        font-weight: bold;
    }
    .estado-success { background: #d4edda; color: #155724; }
    .estado-not_found { background: #fff3cd; color: #856404; }
    .estado-error { background: #f8d7da; color: #721c24; }
    
    .btn {
        background: #007bff;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        display: inline-block;
        margin: 10px 5px;
    }
    .btn:hover {
        background: #0056b3;
    }
</style>

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

                <!-- Todo el contenido de la pagina-->
                <div class="reporte-container">
                    <h1 class="text-3xl font-bold text-gray-800 mb-6">📊 Reporte de Carga de Imágenes</h1>
                    
                    <?php 
                    $estadisticas = $_SESSION['estadisticas_imagenes'] ?? [];
                    $reporte = $_SESSION['reporte_imagenes'] ?? [];
                    ?>
                    
                    <div class="stats">
                        <div class="stat-card">
                            <div class="stat-number"><?= $estadisticas['total_procesadas'] ?? 0 ?></div>
                            <div class="text-gray-600">Total Imágenes Procesadas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number success"><?= $estadisticas['enlazadas'] ?? 0 ?></div>
                            <div class="text-gray-600">Enlazadas Correctamente</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number warning"><?= $estadisticas['no_encontradas'] ?? 0 ?></div>
                            <div class="text-gray-600">No Encontradas</div>
                        </div>
                    </div>

                    <?php if (!empty($reporte)): ?>
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="reporte-table">
                                    <thead>
                                        <tr>
                                            <th>Producto ID</th>
                                            <th>Imagen Original</th>
                                            <th>Estado</th>
                                            <th>Mensaje</th>
                                            <th>Imagen Final</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reporte as $item): ?>
                                            <tr class="estado-<?= $item['estado'] ?>">
                                                <td>
                                                    <a href="<?= url('producto/editar/' . $item['producto_id']) ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                                                        #<?= $item['producto_id'] ?>
                                                    </a>
                                                </td>
                                                <td class="font-mono text-sm"><?= htmlspecialchars($item['imagen_original']) ?></td>
                                                <td>
                                                    <?php if ($item['estado'] === 'success'): ?>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            ✅ Enlazada
                                                        </span>
                                                    <?php elseif ($item['estado'] === 'not_found'): ?>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            ⚠️ No encontrada
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                            ❌ Error
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-sm text-gray-600"><?= htmlspecialchars($item['mensaje'] ?? 'Procesada correctamente') ?></td>
                                                <td>
                                                    <?php if (isset($item['imagen_final'])): ?>
                                                        <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($item['imagen_final']) ?></code>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-6">
                        <a href="<?= url('cargaMasiva/gestionImagenes') ?>" class="btn">
                            📁 Cargar Más Imágenes
                        </a>
                        <a href="<?= url('producto/index') ?>" class="btn">
                             Ver Productos
                        </a>
                    </div>
                </div>

                <div class="mt-4">
                    <?php include_once __DIR__ . '/../admin/includes/footer.php'; ?>
                </div>
            </main>
        </div>
    </div>

    <?php
    // Limpiar datos de sesión
    unset($_SESSION['reporte_imagenes'], $_SESSION['estadisticas_imagenes']);
    ?>
</body>
</html>
