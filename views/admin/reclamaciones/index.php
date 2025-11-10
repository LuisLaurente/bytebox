<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/reclamIndex.css') ?>">

<body>
    <div class="_reclamAdmin-admin-layout">
        <?php include_once __DIR__ . '/../includes/navbar.php'; ?>

        <div class="_reclamAdmin-main-content">
            <main class="_reclamAdmin-content">
                <div class="_reclamAdmin-container">
                    <!-- Header -->
                    <div class="_reclamAdmin-header-card">
                        <div class="_reclamAdmin-header-content">
                            <h1 class="_reclamAdmin-title">Reclamaciones Recibidas</h1>
                            <p class="_reclamAdmin-subtitle">Visualiza y gestiona los reclamos enviados por los clientes</p>
                        </div>
                    </div>

                    <!-- Tabla de reclamaciones -->
                    <div class="_reclamAdmin-table-card">
                        <?php if (!empty($reclamaciones)): ?>
                            <div class="_reclamAdmin-table-container">
                                <table class="_reclamAdmin-table">
                                    <thead class="_reclamAdmin-table-header">
                                        <tr>
                                            <th class="_reclamAdmin-table-head">Código de pedido</th>
                                            <th class="_reclamAdmin-table-head">Nombre</th>
                                            <th class="_reclamAdmin-table-head">Correo</th>
                                            <th class="_reclamAdmin-table-head">Teléfono</th>
                                            <th class="_reclamAdmin-table-head">Mensaje</th>
                                            <th class="_reclamAdmin-table-head">Fecha</th>
                                            <th class="_reclamAdmin-table-head">🗑️</th>
                                        </tr>
                                    </thead>
                                    <tbody class="_reclamAdmin-table-body">
                                        <?php foreach ($reclamaciones as $r): ?>
                                            <tr class="_reclamAdmin-table-row">
                                                <td class="_reclamAdmin-table-cell"><?= htmlspecialchars($r['pedido_id'] ?? 'N/A') ?></td>
                                                <td class="_reclamAdmin-table-cell"><?= htmlspecialchars($r['nombre']) ?></td>
                                                <td class="_reclamAdmin-table-cell"><?= htmlspecialchars($r['correo']) ?></td>
                                                <td class="_reclamAdmin-table-cell"><?= htmlspecialchars($r['telefono']) ?></td>
                                                <td class="_reclamAdmin-table-cell _reclamAdmin-message-cell"><?= nl2br(htmlspecialchars($r['mensaje'])) ?></td>
                                                <td class="_reclamAdmin-table-cell"><?= htmlspecialchars($r['creado_en']) ?></td>
                                                <td class="_reclamAdmin-table-cell _reclamAdmin-actions-cell">
                                                    <a href="<?= url('adminReclamacion/eliminar/' . $r['id']) ?>"
                                                       onclick="return confirm('¿Estás seguro de eliminar esta reclamación?')"
                                                       class="_reclamAdmin-delete-button">
                                                        ❌
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="_reclamAdmin-empty-state">No hay reclamaciones registradas aún.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>