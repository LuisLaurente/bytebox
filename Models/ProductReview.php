<?php
namespace Models;

use Core\Database;
use PDO;

class ProductReview {
    private $db;

    public function __construct() {
        // ✅ Aquí usamos tu método correcto
        $this->db = Database::getConexion();
    }

    public function crear($producto_id, $user_id, $orden_id, $puntuacion, $titulo, $texto) {
        $sql = "INSERT INTO product_reviews (producto_id, user_id, orden_id, puntuacion, titulo, texto) 
                VALUES (:producto_id, :user_id, :orden_id, :puntuacion, :titulo, :texto)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':producto_id' => $producto_id,
            ':user_id'     => $user_id,
            ':orden_id'    => $orden_id,
            ':puntuacion'  => $puntuacion,
            ':titulo'      => $titulo,
            ':texto'       => $texto
        ]);
    }

    public function obtenerPorProducto($producto_id) {
        $sql = "SELECT r.*, u.nombre AS usuario 
                FROM product_reviews r
                JOIN usuarios u ON r.user_id = u.id
                WHERE producto_id = :producto_id
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':producto_id' => $producto_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
