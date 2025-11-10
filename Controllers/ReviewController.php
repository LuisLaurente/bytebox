<?php
namespace Controllers;
use PDO;
use Core\Database; 

class ReviewController
{
    public function index()
    {
        $db = Database::getConexion();
        $stmt = $db->query("
            SELECT r.*, p.nombre AS producto_nombre, u.nombre AS usuario_nombre
            FROM product_reviews r
            JOIN productos p ON r.producto_id = p.id
            JOIN usuarios u ON r.user_id = u.id
            ORDER BY r.created_at DESC
        ");
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pasamos la variable $reviews a la vista
        include_once __DIR__ . '/../views/review/index.php';
    }

    public function aprobar($id)
    {
        $db = Database::getConexion();
        $stmt = $db->prepare("UPDATE product_reviews SET estado = 'aprobado' WHERE id = :id");
        $stmt->execute([':id' => $id]);

        // Usar un nombre de sesión consistente
        $_SESSION['mensaje_review'] = "✅ Reseña aprobada con éxito.";
        header("Location: " . url('review/index'));
        exit;
    }

    // --- NUEVO MÉTODO ---
    // Para devolver una reseña a 'pendiente' si fue aprobada por error.
    public function rechazar($id)
    {
        $db = Database::getConexion();
        $stmt = $db->prepare("UPDATE product_reviews SET estado = 'pendiente' WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['mensaje_review'] = "⚠️ La reseña ha sido marcada como pendiente.";
        header("Location: " . url('review/index'));
        exit;
    }

    public function eliminar($id)
    {
        $db = Database::getConexion();
        $stmt = $db->prepare("DELETE FROM product_reviews WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['mensaje_review'] = "Reseña eliminada con éxito.";
        header("Location: " . url('review/index'));
        exit;
    }
}
