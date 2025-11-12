<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/listar.css') ?>">

<body>
    <?php
    $estados = ['pendiente', 'pendiente_pago', 'procesando', 'enviado', 'entregado', 'cancelado'];
    $estadoFiltro = $_GET['estado'] ?? '';

    $estadisticas = [
        'total' => count($pedidos),
        'pendiente' => 0,
        'procesando' => 0,
        'enviado' => 0,
        'entregado' => 0
    ];

    foreach ($pedidos as $pedido) {
        if (isset($estadisticas[$pedido['estado']])) {
            $estadisticas[$pedido['estado']]++;
        }
    }
    ?>
    <div class="pedidos-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

        <div class="pedidos-main">
            <main class="pedidos-content">
                <div class="pedidos-container">
                    <div class="pedidos-header">
                        <h1 class="pedidos-title">Gestión de Pedidos</h1>
                        <p class="pedidos-subtitle">Administra y supervisa todos los pedidos del sistema</p>
                    </div>

                    <!-- Estadísticas lll -->
                    <div class="pedidos-stats">
                        <div class="pedidos-stat-card pedidos-stat-total">
                            <div class="pedidos-stat-icon"></div>
                            <div class="pedidos-stat-number"><?= $estadisticas['total'] ?></div>
                            <div class="pedidos-stat-label">Total Pedidos</div>
                        </div>
                        <div class="pedidos-stat-card pedidos-stat-pendientes">
                            <div class="pedidos-stat-icon"></div>
                            <div class="pedidos-stat-number"><?= $estadisticas['pendiente'] ?></div>
                            <div class="pedidos-stat-label">Pendientes</div>
                        </div>
                        <div class="pedidos-stat-card pedidos-stat-procesando">
                            <div class="pedidos-stat-icon"></div>
                            <div class="pedidos-stat-number"><?= $estadisticas['procesando'] ?></div>
                            <div class="pedidos-stat-label">Procesando</div>
                        </div>
                        <div class="pedidos-stat-card pedidos-stat-enviados">
                            <div class="pedidos-stat-icon"></div>
                            <div class="pedidos-stat-number"><?= $estadisticas['enviado'] ?></div>
                            <div class="pedidos-stat-label">Enviados</div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="pedidos-filters">
                        <h3 class="pedidos-filters-title">Filtros de Búsqueda</h3>
                        <form method="get" class="pedidos-filters-form">
                            <div class="pedidos-filter-group">
                                <label class="pedidos-filter-label">Filtrar por estado:</label>
                                <select name="estado" class="pedidos-filter-select">
                                    <option value="">-- Todos los estados --</option>
                                    <?php foreach ($estados as $estado): ?>
                                        <option value="<?= $estado ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>>
                                            <?= ucfirst($estado) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="pedidos-filter-group">
                                <button type="submit" class="pedidos-filter-btn">Aplicar filtro</button>
                            </div>
                        </form>
                    </div>

                    <!-- Botones de acción -->
                    <div class="pedidos-actions">
                        <a href="<?= url('cupon') ?>" class="pedidos-action-btn pedidos-action-primary">
                            Gestionar Cupones
                        </a>
                        <a href="<?= url('promocion') ?>" class="pedidos-action-btn pedidos-action-secondary">
                            Gestionar Promociones
                        </a>
                    </div>

                    <!-- Tabla de pedidos -->
                    <?php
                    $pedidosFiltrados = array_filter($pedidos, function ($pedido) use ($estadoFiltro) {
                        return !$estadoFiltro || $pedido['estado'] === $estadoFiltro;
                    });
                    ?>

                    <?php if (!empty($pedidosFiltrados)): ?>
                        <div class="pedidos-table-container">
                            <div class="pedidos-table-header">
                                <h3 class="pedidos-table-title">Lista de Pedidos</h3>
                                <span class="pedidos-count"><?= count($pedidosFiltrados) ?> pedidos</span>
                            </div>
                            <div class="pedidos-table-wrapper">
                                <table class="pedidos-table">
                                    <thead>
                                        <tr>
                                            <th class="pedidos-table-head">ID</th>
                                            <th class="pedidos-table-head">Usuario</th>
                                            <th class="pedidos-table-head">Estado</th>
                                            <th class="pedidos-table-head">Monto Total</th>
                                            <th class="pedidos-table-head">Fecha</th>
                                            <th class="pedidos-table-head">Observaciones</th>
                                            <th class="pedidos-table-head">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="pedidos-table-body">
                                        <?php foreach ($pedidosFiltrados as $pedido): ?>
                                            <tr class="pedidos-table-row">
                                                <td class="pedidos-table-cell pedidos-table-id"><strong>#<?= $pedido['id']?></strong></td>
                                                <td class="pedidos-table-cell"><?= htmlspecialchars($pedido['cliente_id']) ?></td>
                                                <td class="pedidos-table-cell">
                                                    <span class="pedidos-status-badge pedidos-status-<?= $pedido['estado'] ?>">
                                                        <?= ucfirst($pedido['estado']) ?>
                                                    </span>
                                                </td>
                                                <td class="pedidos-table-cell pedidos-table-amount">S/ <?= number_format($pedido['monto_total'], 2) ?></td>
                                                <td class="pedidos-table-cell pedidos-table-date"><?= date('d/m/Y H:i', strtotime($pedido['creado_en'])) ?></td>
                                                <td class="pedidos-table-cell pedidos-table-observations">
                                                    <?php if (!empty($pedido['observaciones_admin'])): ?>
                                                        <span title="<?= htmlspecialchars($pedido['observaciones_admin']) ?>">
                                                            <?= substr(htmlspecialchars($pedido['observaciones_admin']), 0, 30) ?>
                                                            <?= strlen($pedido['observaciones_admin']) > 30 ? '...' : '' ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="pedidos-no-observations">Sin observaciones</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="pedidos-table-cell pedidos-table-actions">
                                                    <a href="<?= url('pedido/ver/' . $pedido['id']) ?>" class="pedidos-action-link pedidos-action-view">
                                                        Ver detalle
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="pedidos-empty">
                            <h3 class="pedidos-empty-title">No se encontraron pedidos</h3>
                            <p class="pedidos-empty-description">
                                <?php if ($estadoFiltro): ?>
                                    No hay pedidos con el estado "<?= ucfirst($estadoFiltro) ?>" en este momento.
                                <?php else: ?>
                                    Aún no se han registrado pedidos en el sistema.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="pedidos-back">
                        <a href="<?= url('producto/index') ?>" class="pedidos-back-link">
                            Volver al listado de productos
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>