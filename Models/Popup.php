<?php
namespace Models;

use Core\Database;
use PDO;

class Popup
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConexion();
    }

    public function obtener()
    {
        $stmt = $this->db->query("SELECT * FROM popup_promocional LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function agregarImagen($nombreImagen)
    {
        $sql = "INSERT INTO popup_imagenes (nombre_imagen) VALUES (:nombre)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nombre' => $nombreImagen]);
    }
    public function actualizarImagenPrincipal($nombreImagen)
    {
        $sql = "UPDATE popup_promocional SET imagen = :imagen";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':imagen' => $nombreImagen]);
    }
    public function actualizarTextoYEstado($texto, $activo)
    {
        $sql = "UPDATE popup_promocional SET texto = :texto, activo = :activo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':texto' => $texto,
            ':activo' => $activo
        ]);
    }
    public function obtenerImagenes()
    {
        $sql = "SELECT * FROM popup_imagenes ORDER BY id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

     public function eliminarImagen($id)
    {
        // Obtener primero el nombre del archivo para eliminarlo físicamente
        $stmt = $this->db->prepare("SELECT nombre_imagen FROM popup_imagenes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $imagen = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($imagen && file_exists(__DIR__ . '/../public/images/popup/' . $imagen['nombre_imagen'])) {
            unlink(__DIR__ . '/../public/images/popup/' . $imagen['nombre_imagen']);
        }

        // Eliminar de la base de datos
        $stmt = $this->db->prepare("DELETE FROM popup_imagenes WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

}

