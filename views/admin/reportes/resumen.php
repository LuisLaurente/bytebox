<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/resumenIndex.css') ?>">

<body>
    <div class="_resumenAdmin-admin-layout">
        <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="_resumenAdmin-main-content">
            <main class="_resumenAdmin-content">
                <div class="_resumenAdmin-container">
                    <!-- Título y descripción -->
                    <div class="_resumenAdmin-header-card">
                        <h1 class="_resumenAdmin-title"> Reporte de Ventas</h1>
                        <p class="_resumenAdmin-subtitle">Consulta general y detallada de las ventas realizadas por rango de fechas</p>
                    </div>

                    <!-- Filtro de fechas -->
                    <form method="get" class="_resumenAdmin-filter-card">
                        <div class="_resumenAdmin-filter-container">
                            <label class="_resumenAdmin-filter-group">
                                <span class="_resumenAdmin-filter-label">Desde</span>
                                <input type="date" name="inicio" value="<?= htmlspecialchars($fechaInicio ?? '') ?>" class="_resumenAdmin-filter-input">
                            </label>
                            <label class="_resumenAdmin-filter-group">
                                <span class="_resumenAdmin-filter-label">Hasta</span>
                                <input type="date" name="fin" value="<?= htmlspecialchars($fechaFin ?? '') ?>" class="_resumenAdmin-filter-input">
                            </label>
                            <div class="_resumenAdmin-filter-actions">
                                <button type="submit" class="_resumenAdmin-btn _resumenAdmin-btn-primary">
                                    Buscar
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Botón de exportación -->
                    <div class="_resumenAdmin-export-section">
                        <form method="get" action="<?= url('reporte/exportarExcel') ?>">
                            <input type="hidden" name="inicio" value="<?= htmlspecialchars($fechaInicio ?? '') ?>">
                            <input type="hidden" name="fin" value="<?= htmlspecialchars($fechaFin ?? '') ?>">
                            <button type="submit" class="_resumenAdmin-btn _resumenAdmin-btn-export">
                                ⬇️ Exportar a Excel
                            </button>
                        </form>
                    </div>

                    <!-- Resumen General -->
                    <div class="_resumenAdmin-summary-card">
                        <h2 class="_resumenAdmin-section-title">📈 Resumen General</h2>
                        <?php if (!empty($resumen)): ?>
                            <ul class="_resumenAdmin-summary-list">
                                <li class="_resumenAdmin-summary-item">
                                    <span class="_resumenAdmin-summary-icon">🧾</span>
                                    <span class="_resumenAdmin-summary-label">Total vendido:</span>
                                    <span class="_resumenAdmin-summary-value">S/ <?= number_format($resumen['total_vendido'] ?? 0, 2) ?></span>
                                </li>
                                <li class="_resumenAdmin-summary-item">
                                    <span class="_resumenAdmin-summary-icon">📦</span>
                                    <span class="_resumenAdmin-summary-label">Total de pedidos:</span>
                                    <span class="_resumenAdmin-summary-value"><?= intval($resumen['total_pedidos'] ?? 0) ?></span>
                                </li>
                                <li class="_resumenAdmin-summary-item">
                                    <span class="_resumenAdmin-summary-icon">🎟️</span>
                                    <span class="_resumenAdmin-summary-label">Ticket promedio:</span>
                                    <span class="_resumenAdmin-summary-value">S/ <?= number_format($resumen['ticket_promedio'] ?? 0, 2) ?></span>
                                </li>
                                <li class="_resumenAdmin-summary-item">
                                    <span class="_resumenAdmin-summary-icon">📅</span>
                                    <span class="_resumenAdmin-summary-label">Rango:</span>
                                    <span class="_resumenAdmin-summary-value"><?= htmlspecialchars($fechaInicio ?? '-') ?> — <?= htmlspecialchars($fechaFin ?? '-') ?></span>
                                </li>
                            </ul>
                        <?php else: ?>
                            <p class="_resumenAdmin-empty-state">No hay datos disponibles para este rango.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Detalle por Producto -->
                    <div class="_resumenAdmin-details-card">
                        <h2 class="_resumenAdmin-section-title">Detalle por Producto</h2>
                        <?php if (!empty($detalles)): ?>
                            <div class="_resumenAdmin-table-container">
                                <table class="_resumenAdmin-table">
                                    <thead class="_resumenAdmin-table-header">
                                        <tr>
                                            <th class="_resumenAdmin-table-head">Pedido ID</th>
                                            <th class="_resumenAdmin-table-head">Fecha</th>
                                            <th class="_resumenAdmin-table-head">Estado</th>
                                            <th class="_resumenAdmin-table-head">Método Pago</th>
                                            <th class="_resumenAdmin-table-head">Productos</th>
                                            <th class="_resumenAdmin-table-head">Items</th>
                                            <th class="_resumenAdmin-table-head">Subtotal</th>
                                            <th class="_resumenAdmin-table-head">Promoción</th>
                                            <th class="_resumenAdmin-table-head">Cupón</th>
                                            <th class="_resumenAdmin-table-head">Envío</th>
                                            <th class="_resumenAdmin-table-head">TOTAL</th>
                                            <th class="_resumenAdmin-table-head _resumenAdmin-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="_resumenAdmin-table-body">
                                        <?php
                                        $pedidosProcesados = [];
                                        foreach ($detalles as $d):
                                            $pedidoId = $d['pedido_id'];

                                            // Si ya procesamos este pedido, lo saltamos
                                            if (in_array($pedidoId, $pedidosProcesados)) continue;

                                            // Buscar todos los productos de este pedido
                                            $productosPedido = array_filter($detalles, function ($item) use ($pedidoId) {
                                                return $item['pedido_id'] == $pedidoId;
                                            });
                                        ?>
                                            <tr class="_resumenAdmin-table-row">
                                                <td class="_resumenAdmin-table-cell"><?= htmlspecialchars($pedidoId) ?></td>
                                                <td class="_resumenAdmin-table-cell"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($d['creado_en']))) ?></td>
                                                <td class="_resumenAdmin-table-cell">
                                                    <span class="_resumenAdmin-status-badge _resumenAdmin-status-<?= $d['estado'] ?>">
                                                        <?= htmlspecialchars(ucfirst($d['estado'])) ?>
                                                    </span>
                                                </td>
                                                <td class="_resumenAdmin-table-cell">
                                                    <?= htmlspecialchars($d['metodo_pago'] ?? 'N/A') ?>
                                                </td>
                                                <td class="_resumenAdmin-table-cell">
                                                    <ul class="_resumenAdmin-productos-lista">
                                                        <?php foreach ($productosPedido as $producto): ?>
                                                            <li class="_resumenAdmin-producto-item">
                                                                <span class="_resumenAdmin-producto-nombre">
                                                                    <?= htmlspecialchars($producto['producto']) ?>
                                                                </span>
                                                                <span class="_resumenAdmin-producto-detalle">
                                                                    S/ <?= number_format($producto['precio_unitario'], 2) ?>
                                                                    <span class="_resumenAdmin-producto-cantidad">
                                                                        x<?= $producto['cantidad'] ?>
                                                                    </span>
                                                                </span>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </td>
                                                <td class="_resumenAdmin-table-cell _resumenAdmin-center">
                                                    <?= array_sum(array_column($productosPedido, 'cantidad')) ?>
                                                </td>
                                                <td class="_resumenAdmin-table-cell">S/ <?= number_format($d['pedido_subtotal'], 2) ?></td>
                                                <td class="_resumenAdmin-table-cell">
                                                    <?php if ($d['descuento_promocion'] > 0): ?>
                                                        -S/ <?= number_format($d['descuento_promocion'], 2) ?>
                                                    <?php else: ?>
                                                        S/ 0.00
                                                    <?php endif; ?>
                                                </td>
                                                <td class="_resumenAdmin-table-cell">
                                                    <?php if ($d['descuento_cupon'] > 0): ?>
                                                        -S/ <?= number_format($d['descuento_cupon'], 2) ?>
                                                    <?php else: ?>
                                                        S/ 0.00
                                                    <?php endif; ?>
                                                </td>
                                                <td class="_resumenAdmin-table-cell">
                                                    <?php if ($d['costo_envio'] > 0): ?>
                                                        S/ <?= number_format($d['costo_envio'], 2) ?>
                                                    <?php else: ?>
                                                        <span style="color: #28a745;">GRATIS</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="_resumenAdmin-table-cell _resumenAdmin-total-cell">
                                                    S/ <?= number_format($d['pedido_total'], 2) ?>
                                                </td>
                                                <td class="_resumenAdmin-table-cell _resumenAdmin-center _resumenAdmin-actions-cell">
                                                    <a href="<?= url('pedido/ver/' . $pedidoId) ?>" class="_resumenAdmin-action-link">Ver</a>
                                                </td>
                                            </tr>
                                        <?php
                                            $pedidosProcesados[] = $pedidoId;
                                        endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="_resumenAdmin-empty-state">No hay ventas registradas en el rango seleccionado.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>