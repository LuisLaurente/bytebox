<?php
namespace Models;

use Core\Database;
use PDO;

class Reclamacion
{
    private $db;

    public function __construct()
    {
        $this->db = \Core\Database::getConexion();
    }

    public function guardar($nombre, $correo, $telefono, $mensaje)
    {
        $sql = "INSERT INTO reclamaciones (nombre, correo, telefono, mensaje)
                VALUES (:nombre, :correo, :telefono, :mensaje)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':telefono' => $telefono,
            ':mensaje' => $mensaje
        ]);
    }

    public function obtenerTodas()
    {
        $sql = "SELECT * FROM reclamaciones ORDER BY creado_en DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function eliminarPorId($id)
    {
        $sql = "DELETE FROM reclamaciones WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }

    public function crear($datos)
    {
        $sql = "INSERT INTO reclamaciones (nombre, correo, telefono, mensaje, pedido_id)
                VALUES (:nombre, :correo, :telefono, :mensaje, :pedido_id)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nombre', $datos['nombre']);
        $stmt->bindValue(':correo', $datos['correo']);
        $stmt->bindValue(':telefono', $datos['telefono']);
        $stmt->bindValue(':mensaje', $datos['mensaje']);
        $stmt->bindValue(':pedido_id', $datos['pedido_id'], PDO::PARAM_INT);

        return $stmt->execute();
    }
}
