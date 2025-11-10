<?php

namespace Models;

use Core\Database;
use PDO;

class Etiqueta {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerTodas() {
        $sql = "SELECT * FROM etiquetas";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
    }

    public function obtenerPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM etiquetas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($nombre, $slug) {
        $stmt = $this->db->prepare("INSERT INTO etiquetas (nombre, slug) VALUES (?, ?)");
        return $stmt->execute([$nombre, $slug]);
    }

    public function actualizar($id, $nombre, $slug) {
        $stmt = $this->db->prepare("UPDATE etiquetas SET nombre = ?, slug = ? WHERE id = ?");
        return $stmt->execute([$nombre, $slug, $id]);
    }

    public function eliminar($id) {
        $stmt = $this->db->prepare("DELETE FROM etiquetas WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function obtenerEtiquetasPorProducto($id_producto) {
        $stmt = $this->db->prepare("SELECT etiqueta_id FROM producto_etiqueta WHERE producto_id = ?");
        $stmt->execute([$id_producto]);
    return $stmt->fetchAll(\PDO::FETCH_COLUMN); // array de IDs
}
}