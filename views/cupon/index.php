<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/cuponIndex.css') ?>">

<body>
    <div class="layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        
        <div class="main-content">
            <main class="content">
                <div class="cupon-admin-container">
                    <div class="cupon-admin">
                        <!-- Header -->
                        <div class="cupon-header">
                            <h1>Administración de Cupones</h1>
                            <div class="header-actions">
                                <a href="<?= url('cupon/crear') ?>" class="btn-nuevo-cupon">
                                    <span>+</span> Nuevo Cupón
                                </a>
                            </div>
                        </div>

                        <!-- Alertas -->
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">
                                <?php
                                switch ($_GET['success']) {
                                    case 'created':
                                        echo 'Cupón creado exitosamente';
                                        break;
                                    case 'updated':
                                        echo 'Cupón actualizado exitosamente';
                                        break;
                                    case 'status_changed':
                                        echo 'Estado del cupón cambiado exitosamente';
                                        break;
                                }
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-error">
                                <?php
                                switch ($_GET['error']) {
                                    case 'not_found':
                                        echo 'Cupón no encontrado';
                                        break;
                                    case 'status_change_failed':
                                        echo 'Error al cambiar el estado del cupón';
                                        break;
                                    default:
                                        echo 'Ha ocurrido un error';
                                }
                                ?>
                            </div>
                        <?php endif; ?>

                        <!-- Estadísticas -->
                        <div class="estadisticas-cupones">
                            <div class="stat-card total">
                                <h3>Total de Cupones</h3>
                                <p class="numero"><?= $estadisticas['total'] ?? 0 ?></p>
                            </div>
                            <div class="stat-card activos">
                                <h3>Cupones Activos</h3>
                                <p class="numero"><?= $estadisticas['activos'] ?? 0 ?></p>
                            </div>
                            <div class="stat-card vigentes">
                                <h3>Cupones Vigentes</h3>
                                <p class="numero"><?= $estadisticas['vigentes'] ?? 0 ?></p>
                            </div>
                            <div class="stat-card usados">
                                <h3>Cupones Usados</h3>
                                <p class="numero"><?= $estadisticas['usados'] ?? 0 ?></p>
                            </div>
                        </div>

                        <!-- Tabla de cupones -->
                        <div class="cupones-tabla-container">
                            <?php if (empty($cupones)): ?>
                                <div class="empty-state">
                                    <h3>No hay cupones registrados</h3>
                                    <p>Comienza creando tu primer cupón</p>
                                    <a href="<?= url('cupon/crear') ?>" class="btn-primary">Crear Cupón</a>
                                </div>
                            <?php else: ?>
                                <table class="cupones-tabla">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Tipo</th>
                                            <th>Valor</th>
                                            <th>Vigencia</th>
                                            <th>Estado</th>
                                            <th>Usos</th>
                                            <th>Límites</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cupones as $cupon001): ?>
                                            <tr>
                                                <td class="codigoNombre">
                                                    <strong><?= htmlspecialchars($cupon001['codigo']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="tipo-cupon <?= $cupon001['tipo'] === 'descuento_porcentaje' ? 'tipo-porcentaje' : ($cupon001['tipo'] === 'descuento_fijo' ? 'tipo-monto' : 'tipo-envio') ?>">
                                                        <?php
                                                        switch ($cupon001['tipo']) {
                                                            case 'descuento_porcentaje':
                                                                echo 'Porcentaje';
                                                                break;
                                                            case 'descuento_fijo':
                                                                echo 'Monto fijo';
                                                                break;
                                                            case 'envio_gratis':
                                                                echo 'Envío gratis';
                                                                break;
                                                            default:
                                                                echo 'Desconocido';
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td class="valorNumero">
                                                    <?php if ($cupon001['tipo'] === 'descuento_porcentaje'): ?>
                                                        <?= $cupon001['valor'] ?>%
                                                    <?php elseif ($cupon001['tipo'] === 'descuento_fijo'): ?>
                                                        S/ <?= number_format($cupon001['valor'], 2) ?>
                                                    <?php else: ?>
                                                        Gratis
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="vigencia-dates">
                                                        <div class="fecha-inicio"><?= date('d/m/Y', strtotime($cupon001['fecha_inicio'])) ?></div>
                                                        <div class="fecha-fin">al <?= date('d/m/Y', strtotime($cupon001['fecha_fin'])) ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="estado-badge estado-<?= $cupon001['estado_vigencia'] ?>">
                                                        <?php
                                                        switch ($cupon001['estado_vigencia']) {
                                                            case 'vigente':
                                                                echo 'Vigente';
                                                                break;
                                                            case 'expirado':
                                                                echo 'Expirado';
                                                                break;
                                                            case 'pendiente':
                                                                echo 'Pendiente';
                                                                break;
                                                            case 'inactivo':
                                                                echo 'Inactivo';
                                                                break;
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="usos-container">
                                                        <strong class="usos-actuales"><?= $cupon001['usos_totales'] ?></strong>
                                                        <?php if ($cupon001['limite_uso']): ?>
                                                            <div class="usos-limite">
                                                                / <?= $cupon001['limite_uso'] ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="limites-container">
                                                        <?php if ($cupon001['monto_minimo'] > 0): ?>
                                                            <div class="monto-minimo">Min: S/ <?= number_format($cupon001['monto_minimo'], 2) ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($cupon001['limite_por_usuario']): ?>
                                                            <div class="limite-usuario">Por usuario: <?= $cupon001['limite_por_usuario'] ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="acciones">
                                                        <a href="<?= url('cupon/historial?id=' . $cupon001['id']) ?>" class="btn-accion btn-ver" title="Ver historial de uso">
                                                            Historial
                                                        </a>
                                                        <a href="<?= url('cupon/editar/' . $cupon001['id']) ?>" class="btn-accion btn-editar">
                                                            Editar
                                                        </a>
                                                        <form method="POST" action="<?= url('cupon/toggleEstado/' . $cupon001['id']) ?>" class="form-toggle">
                                                            <button type="submit" class="btn-accion btn-toggle">
                                                                <?= $cupon001['activo'] ? 'Desactivar' : 'Activar' ?>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Confirmación para eliminar
        function confirmarEliminacion(codigo) {
            return confirm(`¿Estás seguro de eliminar el cupón "${codigo}"?`);
        }

        // Auto-ocultar alertas
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>