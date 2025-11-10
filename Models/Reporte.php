<?php
namespace Models;

use PDO;

class Reporte
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::getConexion();
    }

    public function obtenerResumenVentas($fechaInicio, $fechaFin)
    {
        $sql = "SELECT 
                COUNT(*) AS total_pedidos,
                SUM(monto_total) AS total_vendido,
                AVG(monto_total) AS ticket_promedio
            FROM pedidos
            WHERE creado_en BETWEEN :inicio AND :fin";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':inicio' => $fechaInicio,
            ':fin' => $fechaFin
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function obtenerDetallePorProducto($fechaInicio, $fechaFin)
{
    $sql = "SELECT 
                    p.nombre,
                    dp.precio_unitario,
                    SUM(dp.cantidad) AS cantidad_total
                FROM detalle_pedido dp
                INNER JOIN pedidos ped ON dp.pedido_id = ped.id
                INNER JOIN productos p ON dp.producto_id = p.id
                WHERE ped.creado_en BETWEEN :inicio AND :fin
                GROUP BY dp.producto_id, dp.precio_unitario, p.nombre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':inicio' => $fechaInicio,
            ':fin' => $fechaFin
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
