<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/cuponHistorial.css') ?>">

<body>
    <div class="layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        <div class="main-content">
            <main class="content">
                <div class="historial-container">
                    <a href="<?= url('cupon') ?>" class="back-button">← Volver a Cupones</a>

                    <h1>Historial de Uso - Cupón <?= htmlspecialchars($cupon['codigo']) ?></h1>

                    <div class="cupon-info">
                        <div class="info-grid">
                            <div class="info-item">
                                <strong>Código:</strong> <?= htmlspecialchars($cupon['codigo']) ?>
                            </div>
                            <div class="info-item">
                                <strong>Tipo:</strong>
                                <?php
                                switch ($cupon['tipo']) {
                                    case 'descuento_porcentaje':
                                        echo $cupon['valor'] . '%';
                                        break;
                                    case 'descuento_fijo':
                                        echo 'S/. ' . number_format($cupon['valor'], 2);
                                        break;
                                    case 'envio_gratis':
                                        echo 'Envío gratis';
                                        break;
                                    default:
                                        echo 'Desconocido';
                                }
                                ?>
                            </div>
                            <div class="info-item">
                                <strong>Estado:</strong>
                                <span class="badge <?= $cupon['estado_vigencia'] ?>"><?= ucfirst($cupon['estado_vigencia']) ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Período:</strong>
                                <?= date('d/m/Y', strtotime($cupon['fecha_inicio'])) ?> -
                                <?= date('d/m/Y', strtotime($cupon['fecha_fin'])) ?>
                            </div>
                            <div class="info-item">
                                <strong>Monto Mínimo:</strong>
                                S/. <?= number_format($cupon['monto_minimo'], 2) ?>
                            </div>
                            <div class="info-item">
                                <strong>Límite Global:</strong>
                                <?= $cupon['limite_uso'] ?: 'Sin límite' ?>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($historial)): ?>
                        <div class="empty-state">
                            <h3>Sin Usos Registrados</h3>
                            <p>Este cupón aún no ha sido utilizado por ningún cliente.</p>
                        </div>
                    <?php else: ?>
                        <div class="historial-table-container">
                            <table class="historial-table">
                                <thead>
                                    <tr>
                                        <th>Fecha de Uso</th>
                                        <th>Cliente</th>
                                        <th>Correo</th>
                                        <th>Pedido #</th>
                                        <th>Monto Pedido</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $uso): ?>
                                        <tr>
                                            <td>
                                                <?= date('d/m/Y H:i', strtotime($uso['fecha_uso'])) ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($uso['nombre_completo'] ?: 'Cliente eliminado') ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($uso['correo'] ?: '-') ?>
                                            </td>
                                            <td>
                                                <?php if ($uso['pedido_id']): ?>
                                                    <a href="<?= url('pedido/ver?id=' . $uso['pedido_id']) ?>" class="pedido-link">
                                                        #<?= $uso['pedido_id'] ?>
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($uso['monto_total']): ?>
                                                    S/. <?= number_format($uso['monto_total'], 2) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="total-usos">
                            <strong>Total de usos: <?= count($historial) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>