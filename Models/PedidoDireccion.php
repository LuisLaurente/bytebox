<?php

namespace Models;

use Core\Database;
use PDO;

class PedidoDireccion
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::getConexion();
    }

    public static function obtenerPorUsuario($usuarioId)
    {
        $db = \Core\Database::getConexion();
        $stmt = $db->prepare("SELECT * FROM pedido_direcciones WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear($pedido_id, $direccion_id = null, $telefono_contacto = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO pedido_direcciones 
            (pedido_id, direccion_id, telefono_contacto) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $pedido_id,
            $direccion_id,
            $telefono_contacto
        ]);
        return $this->db->lastInsertId();
    }

    public function obtenerPorPedido($pedido_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                pd.*, 
                d.direccion, 
                d.distrito, 
                d.provincia, 
                d.departamento, 
                d.referencia, 
                d.nombre_direccion,
                d.activa
            FROM pedido_direcciones pd
            LEFT JOIN direcciones d ON pd.direccion_id = d.id
            WHERE pd.pedido_id = ?
        ");
        $stmt->execute([$pedido_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerDireccionCompleta($pedido_id)
    {
        $direccion = $this->obtenerPorPedido($pedido_id);

        if (!$direccion) {
            return 'Dirección no disponible';
        }

        if ($direccion['direccion']) {
            $direccion_completa = $direccion['direccion'];

            $ubicacion = [];
            if ($direccion['distrito']) $ubicacion[] = $direccion['distrito'];
            if ($direccion['provincia']) $ubicacion[] = $direccion['provincia'];
            if ($direccion['departamento']) $ubicacion[] = $direccion['departamento'];

            if (!empty($ubicacion)) {
                $direccion_completa .= ', ' . implode(', ', $ubicacion);
            }

            if ($direccion['referencia']) {
                $direccion_completa .= ' - ' . $direccion['referencia'];
            }

            return $direccion_completa;
        }

        return 'Dirección no disponible';
    }
}