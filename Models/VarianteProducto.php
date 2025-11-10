<?php

namespace Models;

use Core\Database;
use PDO;

class VarianteProducto
{
    private $db;

    public function __construct()
    {
        // Me conecto a la base de datos
        $this->db = Database::getInstance()->getConnection();
    }

    // Este método me permite registrar una nueva variante
    public function crear($producto_id, $talla, $color, $stock, $imagen = null)
    {
        $sql = "INSERT INTO variantes_producto (producto_id, talla, color, stock, imagen) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$producto_id, $talla, $color, $stock, $imagen]);
    }

    // Este método me permite obtener todas las variantes de un producto específico
    public function obtenerPorProducto($producto_id)
    {
        $sql = "SELECT * FROM variantes_producto WHERE producto_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$producto_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Este método me permite eliminar todas las variantes de un producto (opcionalmente útil en edición)
    public function eliminarPorProducto($producto_id)
    {
        $sql = "DELETE FROM variantes_producto WHERE producto_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$producto_id]);
    }
    // Este método me permite obtener variantes por ID de producto
    public static function obtenerPorProductoId($producto_id)
    {
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM variantes_producto WHERE producto_id = ?");
        $stmt->execute([$producto_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    // Este método me permite obtener UNA variante específica por su ID
    public static function obtenerPorId($id)
    {
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM variantes_producto WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    // Este método me permite actualizar una variante existente
    public static function actualizar($id, $talla, $color, $stock, $imagen = null)
    {
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE variantes_producto SET talla = ?, color = ?, stock = ?, imagen = ? WHERE id = ?");
        $stmt->execute([$talla, $color, $stock, $imagen, $id]);
    }
    
    // Este método me permite actualizar solo la imagen de una variante
    public static function actualizarImagen($id, $imagen)
    {
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE variantes_producto SET imagen = ? WHERE id = ?");
        return $stmt->execute([$imagen, $id]);
    }
    // Este método me permite eliminar una variante por su ID
    public static function eliminar($id)
    {
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM variantes_producto WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function obtenerEtiquetasPorProducto($id_producto) {
    $stmt = $this->db->prepare("SELECT etiqueta_id FROM producto_etiqueta WHERE producto_id = ?");
    $stmt->execute([$id_producto]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN); // Devuelve array con IDs
}
}
