<?php

namespace Controllers;

use Core\Database;

class ReporteController
{
    public function resumen()
    {
        $db = Database::getInstance()->getConnection();

        $fechaInicio = $_GET['inicio'] ?? date('Y-m-01');
        $fechaFin    = $_GET['fin'] ?? date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) $fechaInicio = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin))    $fechaFin    = date('Y-m-d');

        // Resumen general CORREGIDO - usa monto_total real
        $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT p.id) AS total_pedidos,
            COALESCE(SUM(p.monto_total), 0) AS total_vendido,
            COALESCE(AVG(p.monto_total), 0) AS ticket_promedio,
            COALESCE(SUM(p.subtotal), 0) AS subtotal_total,
            COALESCE(SUM(p.descuento_promocion), 0) AS descuento_promocion_total,
            COALESCE(SUM(p.descuento_cupon), 0) AS descuento_cupon_total,
            COALESCE(SUM(p.costo_envio), 0) AS costo_envio_total
        FROM pedidos p
        WHERE DATE(p.creado_en) BETWEEN :inicio AND :fin
    ");
        $stmt->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        $resumen = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Detalle por producto COMPLETO con todos los datos financieros
        $stmt = $db->prepare("
        SELECT 
            p.id AS pedido_id,
            p.creado_en,
            p.estado,
            p.metodo_pago,
            p.subtotal AS pedido_subtotal,
            p.descuento_promocion,
            p.descuento_cupon,
            p.costo_envio,
            p.monto_total AS pedido_total,
            pr.nombre AS producto,
            pd.precio_unitario,
            pd.cantidad,
            (pd.precio_unitario * pd.cantidad) AS subtotal_producto
        FROM detalle_pedido pd
        JOIN pedidos p ON pd.pedido_id = p.id
        JOIN productos pr ON pd.producto_id = pr.id
        WHERE DATE(p.creado_en) BETWEEN :inicio AND :fin
        ORDER BY p.creado_en DESC
    ");
        $stmt->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        $detalles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/admin/reportes/resumen.php';
    }

    public function exportarExcel()
    {
        $db = Database::getInstance()->getConnection();

        $fechaInicio = $_GET['inicio'] ?? date('Y-m-01');
        $fechaFin    = $_GET['fin'] ?? date('Y-m-d');

        // Obtener datos COMPLETOS
        $stmt = $db->prepare("
    SELECT 
        p.id AS pedido_id,
        p.creado_en,
        p.estado,
        p.metodo_pago,
        p.subtotal AS pedido_subtotal,
        p.descuento_promocion,
        p.descuento_cupon,
        p.costo_envio,
        p.monto_total AS pedido_total,
        pr.nombre AS producto,
        pd.precio_unitario,
        pd.cantidad,
        (pd.precio_unitario * pd.cantidad) AS subtotal_producto
    FROM detalle_pedido pd
    JOIN pedidos p ON pd.pedido_id = p.id
    JOIN productos pr ON pd.producto_id = pr.id
    WHERE DATE(p.creado_en) BETWEEN :inicio AND :fin
    ORDER BY p.creado_en DESC
    ");
        $stmt->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin]);
        $detalles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Headers para Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="reporte_ventas_' . date('Y-m-d') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Generar HTML que Excel entenderá como tabla
        echo '<html>';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th { background-color: #2C3E50; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; }';
        echo 'td { padding: 8px; border: 1px solid #ddd; }';
        echo '.number { text-align: right; }';
        echo '.text-center { text-align: center; }';
        echo '.product-list { margin: 0; padding-left: 15px; }';
        echo '.product-item { margin: 2px 0; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';

        // Título
        echo '<h2>Reporte de Ventas - Detallado</h2>';
        echo '<p>Fecha: ' . date('d/m/Y') . ' | Periodo: ' . $fechaInicio . ' al ' . $fechaFin . '</p>';

        // Tabla
        echo '<table>';

        // Encabezados AGRUPADOS (igual que en la vista web)
        echo '<tr>';
        echo '<th>Pedido ID</th>';
        echo '<th>Fecha</th>';
        echo '<th>Estado</th>';
        echo '<th>Método Pago</th>';
        echo '<th>Productos</th>';
        echo '<th>Items</th>';
        echo '<th>Subtotal (S/)</th>';
        echo '<th>Desc. Promo (S/)</th>';
        echo '<th>Desc. Cupón (S/)</th>';
        echo '<th>Envío (S/)</th>';
        echo '<th>TOTAL (S/)</th>';
        echo '</tr>';

        // Agrupar datos por pedido
        $pedidosAgrupados = [];
        foreach ($detalles as $detalle) {
            $pedidoId = $detalle['pedido_id'];

            if (!isset($pedidosAgrupados[$pedidoId])) {
                $pedidosAgrupados[$pedidoId] = [
                    'pedido_id' => $detalle['pedido_id'],
                    'fecha' => $detalle['creado_en'],
                    'estado' => $detalle['estado'],
                    'metodo_pago' => $detalle['metodo_pago'],
                    'productos' => [],
                    'total_items' => 0,
                    'pedido_subtotal' => $detalle['pedido_subtotal'],
                    'descuento_promocion' => $detalle['descuento_promocion'],
                    'descuento_cupon' => $detalle['descuento_cupon'],
                    'costo_envio' => $detalle['costo_envio'],
                    'pedido_total' => $detalle['pedido_total']
                ];
            }

            // Agregar producto a la lista
            $pedidosAgrupados[$pedidoId]['productos'][] = [
                'nombre' => $detalle['producto'],
                'precio_unitario' => $detalle['precio_unitario'],
                'cantidad' => $detalle['cantidad'],
                'subtotal_producto' => $detalle['subtotal_producto']
            ];

            $pedidosAgrupados[$pedidoId]['total_items'] += $detalle['cantidad'];
        }

        // Datos AGRUPADOS
        $totalGeneral = 0;
        foreach ($pedidosAgrupados as $pedido) {
            echo '<tr>';
            echo '<td class="text-center">' . htmlspecialchars($pedido['pedido_id']) . '</td>';
            echo '<td>' . htmlspecialchars(date('d/m/Y H:i', strtotime($pedido['fecha']))) . '</td>';
            echo '<td class="text-center">' . htmlspecialchars(ucfirst($pedido['estado'])) . '</td>';
            echo '<td class="text-center">' . htmlspecialchars($pedido['metodo_pago'] ?? 'N/A') . '</td>';

            // Lista de productos
            echo '<td>';
            echo '<ul class="product-list">';
            foreach ($pedido['productos'] as $producto) {
                echo '<li class="product-item">';
                echo htmlspecialchars($producto['nombre']) . ' - ';
                echo 'S/ ' . number_format($producto['precio_unitario'], 2) . ' x ' . $producto['cantidad'];
                echo '</li>';
            }
            echo '</ul>';
            echo '</td>';

            echo '<td class="number">' . $pedido['total_items'] . '</td>';
            echo '<td class="number">S/ ' . number_format($pedido['pedido_subtotal'], 2) . '</td>';
            echo '<td class="number">' . ($pedido['descuento_promocion'] > 0 ? '-S/ ' . number_format($pedido['descuento_promocion'], 2) : 'S/ 0.00') . '</td>';
            echo '<td class="number">' . ($pedido['descuento_cupon'] > 0 ? '-S/ ' . number_format($pedido['descuento_cupon'], 2) : 'S/ 0.00') . '</td>';
            echo '<td class="number">' . ($pedido['costo_envio'] > 0 ? 'S/ ' . number_format($pedido['costo_envio'], 2) : 'GRATIS') . '</td>';
            echo '<td class="number" style="font-weight: bold;">S/ ' . number_format($pedido['pedido_total'], 2) . '</td>';
            echo '</tr>';

            $totalGeneral += $pedido['pedido_total'];
        }

        // Total general
        echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
        echo '<td colspan="10" style="text-align: right;">TOTAL GENERAL (Suma de todos los pedidos):</td>';
        echo '<td class="number">S/ ' . number_format($totalGeneral, 2) . '</td>';
        echo '</tr>';

        echo '</table>';
        echo '</body>';
        echo '</html>';

        exit;
    }
}
