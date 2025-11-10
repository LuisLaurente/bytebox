<?php
// --- Función Auxiliar para describir la promoción ---
if (!function_exists('describirPromocion')) {
    function describirPromocion($condicion, $accion, $tipo = null)
    {
        $condicion = json_decode($condicion, true);
        $accion = json_decode($accion, true);

        if (!$condicion || !$accion || empty($condicion['tipo']) || empty($accion['tipo'])) {
            error_log("Error en describirPromocion: Condicion=" . json_encode($condicion) . ", Accion=" . json_encode($accion));
            return '<span class="_IndexProm-error-text">Error en datos</span>';
        }

        $tipoCondicion = $condicion['tipo'];
        $tipoAccion = $accion['tipo'];

        if ($tipo && $tipo !== $tipoCondicion) {
            error_log("Inconsistencia en tipo: DB=$tipo, Condicion=$tipoCondicion");
        }

        // --- 🔍 Funciones internas para obtener nombres ---
        $getNombreProducto = function ($id) {
            if (!$id) return null;
            try {
                $pdo = \Core\Database::getInstance()->getConnection();
                $stmt = $pdo->prepare("SELECT nombre FROM productos WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetchColumn() ?: "Producto #$id";
            } catch (Exception $e) {
                error_log("Error obteniendo nombre de producto: " . $e->getMessage());
                return "Producto #$id";
            }
        };

        $getNombreCategoria = function ($id) {
            if (!$id) return null;
            try {
                $pdo = \Core\Database::getInstance()->getConnection();
                $stmt = $pdo->prepare("SELECT nombre FROM categorias WHERE id = ?");
                $stmt->execute([$id]);
                return $stmt->fetchColumn() ?: "Categoría #$id";
            } catch (Exception $e) {
                error_log("Error obteniendo nombre de categoría: " . $e->getMessage());
                return "Categoría #$id";
            }
        };

        // --- 🧠 Descripción de cada tipo de promoción ---
        switch ($tipoCondicion) {
            case 'subtotal_minimo':
                $valorCond = number_format($condicion['valor'] ?? 0, 2);
                if ($tipoAccion === 'descuento_porcentaje') {
                    $valorAcc = $accion['valor'] ?? 0;
                    return "Si el carrito supera S/ {$valorCond} → <strong>{$valorAcc}% de descuento</strong>";
                }
                if ($tipoAccion === 'descuento_fijo') {
                    $valorAcc = number_format($accion['valor'] ?? 0, 2);
                    return "Si el carrito supera S/ {$valorCond} → <strong>S/ {$valorAcc} de descuento</strong>";
                }
                if ($tipoAccion === 'envio_gratis') {
                    return "Si el carrito supera S/ {$valorCond} → <strong>Envío Gratis</strong>";
                }
                break;

            case 'primera_compra':
                if ($tipoAccion === 'envio_gratis') {
                    return "Si es la primera compra del usuario → <strong>Envío Gratis</strong>";
                }
                break;

            case 'cantidad_producto_identico':
                $prodId = $condicion['producto_id'] ?? null;
                $nombreProd = $getNombreProducto($prodId);
                if ($tipoAccion === 'compra_n_paga_m') {
                    $lleva = $accion['cantidad_lleva'] ?? 'N';
                    $paga = $accion['cantidad_paga'] ?? 'M';
                    return "Lleva {$lleva}, Paga {$paga} en <strong>{$nombreProd}</strong>";
                }
                if ($tipoAccion === 'descuento_enesima_unidad') {
                    $unidad = $accion['numero_unidad'] ?? 'N';
                    $desc = $accion['descuento_unidad'] ?? 0;
                    return "<strong>{$desc}% de descuento</strong> en la {$unidad}ª unidad de <strong>{$nombreProd}</strong>";
                }
                break;

            case 'cantidad_producto_categoria':
                if ($tipoAccion === 'descuento_menor_valor') {
                    $catId = $condicion['categoria_id'] ?? null;
                    $nombreCat = $getNombreCategoria($catId);
                    $cantidad = $condicion['cantidad_min'] ?? 'N';
                    $desc = $accion['valor'] ?? 0;
                    return "<strong>{$desc}% de descuento</strong> en el producto de menor valor al llevar {$cantidad} de la categoría <strong>{$nombreCat}</strong>";
                }
                break;

            case 'cantidad_total_productos':
                $cantidadMin = $condicion['cantidad_min'] ?? 0;
                if ($tipoAccion === 'compra_n_paga_m_general') {
                    $lleva = $accion['cantidad_lleva'] ?? 'N';
                    $paga = $accion['cantidad_paga'] ?? 'M';
                    return "Al llevar {$cantidadMin} productos mezclados → <strong>Lleva {$lleva}, Paga {$paga}</strong> (el de menor valor gratis)";
                }
                if ($tipoAccion === 'descuento_enesimo_producto') {
                    $descuento = $accion['valor'] ?? 0;
                    return "Al llevar {$cantidadMin} productos mezclados → <strong>{$descuento}% de descuento</strong> en el producto más barato";
                }
                break;

            case 'todos':
                if ($tipoAccion === 'envio_gratis') {
                    return "<strong>Envío Gratis</strong> para cualquier pedido";
                }
                break;

            default:
                error_log("Tipo de condición desconocido: $tipoCondicion, Accion: $tipoAccion");
                return 'Regla personalizada';
        }

        error_log("Combinación no soportada: Condicion=$tipoCondicion, Accion=$tipoAccion");
        return 'Regla personalizada';
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/promocionIndex.css') ?>">

<body>
    <div class="_IndexProm-admin-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        <div class="_IndexProm-main-content">
            <div class="_IndexProm-content-wrapper">
                <div class="_IndexProm-promociones-container">
                    <div class="_IndexProm-dashboard-header">
                        <h1 class="_IndexProm-dashboard-title">Gestión de Promociones</h1>
                        <p class="_IndexProm-dashboard-subtitle">Administra cupones, descuentos y ofertas especiales.</p>
                    </div>

                    <!-- Mensajes de sesión -->
                    <?php if (isset($_SESSION['mensaje'])): ?>
                        <div class="_IndexProm-alert _IndexProm-alert-success"><?= $_SESSION['mensaje'] ?></div>
                        <?php unset($_SESSION['mensaje']); ?>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="_IndexProm-alert _IndexProm-alert-error"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- Panel de estadísticas -->
                    <?php
                    $promocionModel = new \Models\Promocion();
                    $stats = $promocionModel->obtenerEstadisticas();
                    ?>
                    <div class="_IndexProm-stats-panel">
                        <div class="_IndexProm-stat-card _IndexProm-total">
                            <div class="_IndexProm-stat-number"><?= $stats['total'] ?></div>
                            <div class="_IndexProm-stat-label">Total</div>
                        </div>
                        <div class="_IndexProm-stat-card _IndexProm-activas">
                            <div class="_IndexProm-stat-number"><?= $stats['activas'] ?></div>
                            <div class="_IndexProm-stat-label">Activas</div>
                        </div>
                        <div class="_IndexProm-stat-card _IndexProm-vigentes">
                            <div class="_IndexProm-stat-number"><?= $stats['vigentes'] ?></div>
                            <div class="_IndexProm-stat-label">Vigentes</div>
                        </div>
                        <div class="_IndexProm-stat-card _IndexProm-vencidas">
                            <div class="_IndexProm-stat-number"><?= $stats['vencidas'] ?></div>
                            <div class="_IndexProm-stat-label">Vencidas</div>
                        </div>
                    </div>

                    <div class="_IndexProm-action-buttons">
                        <a href="<?= url('promocion/crear') ?>" class="_IndexProm-btn-primary">Nueva Promoción</a>
                    </div>

                    <?php if (!empty($promociones)): ?>
                        <div class="_IndexProm-promotions-panel">
                            <div class="_IndexProm-table-header">
                                <h3 class="_IndexProm-table-title">Lista de Promociones</h3>
                                <span class="_IndexProm-promotions-count"><?= count($promociones) ?> promociones</span>
                            </div>
                            <div class="_IndexProm-table-container">
                                <table class="_IndexProm-promotions-table">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Regla de Promoción</th>
                                            <th>Estado</th>
                                            <th>Vigencia</th>
                                            <th>Prioridad</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($promociones as $promocion): ?>
                                            <?php
                                            $ahora = date('Y-m-d');
                                            $esVigente = $ahora >= $promocion['fecha_inicio'] && $ahora <= $promocion['fecha_fin'];
                                            ?>
                                            <tr>
                                                <td class="_IndexProm-promotion-name">
                                                    <?= htmlspecialchars($promocion['nombre']) ?>
                                                    <?php if ($promocion['exclusivo']): ?>
                                                        <span class="_IndexProm-exclusive-tag">EXCLUSIVO</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="_IndexProm-promotion-rule">
                                                    <?= describirPromocion($promocion['condicion'], $promocion['accion']) ?>
                                                </td>
                                                <td class="_IndexProm-status-cell">
                                                    <span class="_IndexProm-status-badge _IndexProm-status-<?= $promocion['activo'] ? 'activo' : 'inactivo' ?>">
                                                        <?= $promocion['activo'] ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                    <?php if ($promocion['activo']): ?>
                                                        <span class="_IndexProm-status-badge _IndexProm-status-<?= $esVigente ? 'vigente' : 'vencido' ?>">
                                                            <?= $esVigente ? 'Vigente' : ($ahora > $promocion['fecha_fin'] ? 'Expirado' : 'Programado') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="_IndexProm-date-cell">
                                                    <div class="_IndexProm-date-range">
                                                        <div><strong>Inicio:</strong> <?= date('d/m/Y', strtotime($promocion['fecha_inicio'])) ?></div>
                                                        <div><strong>Fin:</strong> <?= date('d/m/Y', strtotime($promocion['fecha_fin'])) ?></div>
                                                    </div>
                                                </td>
                                                <td class="_IndexProm-priority-cell">
                                                    <span class="_IndexProm-priority-badge"><?= $promocion['prioridad'] ?></span>
                                                </td>
                                                <td class="_IndexProm-actions-cell">
                                                    <a href="<?= url('promocion/editar/' . $promocion['id']) ?>" class="_IndexProm-btn-action _IndexProm-btn-edit">Editar</a>
                                                    <a href="<?= url('promocion/toggleEstado/' . $promocion['id']) ?>" class="_IndexProm-btn-action _IndexProm-btn-toggle">
                                                        <?= $promocion['activo'] ? 'Desactivar' : 'Activar' ?>
                                                    </a>
                                                    <form action="<?= url('promocion/eliminar/' . $promocion['id']) ?>" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta promoción? No se puede deshacer.')">
                                                        <button type="submit" class="_IndexProm-btn-action _IndexProm-btn-delete">Eliminar</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="_IndexProm-promotions-panel">
                            <div class="_IndexProm-empty-state">
                                <h3>No hay promociones registradas</h3>
                                <p>Crea tu primera promoción para empezar a ofrecer descuentos y ofertas especiales.</p>
                                <div class="_IndexProm-empty-action">
                                    <a href="<?= url('promocion/crear') ?>" class="_IndexProm-btn-primary">Crear Primera Promoción</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>