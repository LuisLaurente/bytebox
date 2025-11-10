<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/resenaIndex.css') ?>">

<body>
    <div class="_resenaAdmin-admin-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        <div class="_resenaAdmin-main-content">
            <main class="_resenaAdmin-content">
                <div class="_resenaAdmin-container">
                    <!-- Header -->
                    <div class="_resenaAdmin-header-card">
                        <h1 class="_resenaAdmin-title">📊 Reporte de Reseñas</h1>
                        <p class="_resenaAdmin-subtitle">Administra las reseñas de los clientes (aprobar o eliminar).</p>
                    </div>

                    <!-- Mensaje de éxito/error -->
                    <?php if (isset($_SESSION['mensaje_review'])): ?>
                        <div class="_resenaAdmin-alert" role="alert">
                            <span><?= $_SESSION['mensaje_review']; ?></span>
                        </div>
                        <?php unset($_SESSION['mensaje_review']); ?>
                    <?php endif; ?>

                    <!-- Tabla de reseñas -->
                    <div class="_resenaAdmin-table-card">
                        <?php if (empty($reviews)): ?>
                            <p class="_resenaAdmin-empty-state">No hay reseñas aún.</p>
                        <?php else: ?>
                            <div class="_resenaAdmin-table-container">
                                <table class="_resenaAdmin-table">
                                    <thead class="_resenaAdmin-table-header">
                                        <tr>
                                            <th class="_resenaAdmin-table-head">Producto</th>
                                            <th class="_resenaAdmin-table-head">Usuario</th>
                                            <th class="_resenaAdmin-table-head">⭐ Puntuación</th>
                                            <th class="_resenaAdmin-table-head">💬 Comentario</th>
                                            <th class="_resenaAdmin-table-head">🕒 Fecha</th>
                                            <th class="_resenaAdmin-table-head _resenaAdmin-center">✅ Estado</th>
                                            <th class="_resenaAdmin-table-head _resenaAdmin-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="_resenaAdmin-table-body">
                                        <?php foreach ($reviews as $review): ?>
                                            <tr class="_resenaAdmin-table-row">
                                                <td class="_resenaAdmin-table-cell"><?= htmlspecialchars($review['producto_nombre']) ?></td>
                                                <td class="_resenaAdmin-table-cell"><?= htmlspecialchars($review['usuario_nombre']) ?></td>
                                                <td class="_resenaAdmin-table-cell">
                                                    <div class="_resenaAdmin-stars">
                                                        <?php for ($i=1; $i<=5; $i++): ?>
                                                            <span class="_resenaAdmin-star <?= $i <= $review['puntuacion'] ? '_resenaAdmin-star-active' : '_resenaAdmin-star-inactive' ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td class="_resenaAdmin-table-cell _resenaAdmin-comment-cell">
                                                    <?php if (!empty($review['titulo'])): ?>
                                                        <strong class="_resenaAdmin-comment-title"><?= htmlspecialchars($review['titulo']) ?></strong><br>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($review['texto']) ?>
                                                </td>
                                                <td class="_resenaAdmin-table-cell"><?= date('d/m/Y H:i', strtotime($review['created_at'])) ?></td>
                                                <td class="_resenaAdmin-table-cell _resenaAdmin-center">
                                                    <?php if ($review['estado'] === 'aprobado'): ?>
                                                        <span class="_resenaAdmin-status-badge _resenaAdmin-status-approved">Aprobado</span>
                                                    <?php else: ?>
                                                        <span class="_resenaAdmin-status-badge _resenaAdmin-status-pending">Pendiente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="_resenaAdmin-table-cell _resenaAdmin-center _resenaAdmin-actions-cell">
                                                    <div class="_resenaAdmin-actions">
                                                        <?php if ($review['estado'] === 'pendiente'): ?>
                                                            <a href="<?= url('review/aprobar/' . $review['id']) ?>" class="_resenaAdmin-action-btn _resenaAdmin-approve-btn">Aprobar</a>
                                                        <?php else: ?>
                                                            <a href="<?= url('review/rechazar/' . $review['id']) ?>" class="_resenaAdmin-action-btn _resenaAdmin-reject-btn">Rechazar</a>
                                                        <?php endif; ?>
                                                        <a href="<?= url('review/eliminar/' . $review['id']) ?>" class="_resenaAdmin-action-btn _resenaAdmin-delete-btn" onclick="return confirm('¿Seguro que deseas eliminar esta reseña?')">Eliminar</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>