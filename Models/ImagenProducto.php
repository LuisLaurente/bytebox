<?php

namespace Models;

use Core\Database;
use PDO;

class ImagenProducto
{
    public static function guardar($producto_id, $nombre_imagen)
    {
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO imagenes_producto (producto_id, nombre_imagen) VALUES (?, ?)");
        $stmt->execute([$producto_id, $nombre_imagen]);
    }

    public static function obtenerPorProducto($producto_id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM imagenes_producto WHERE producto_id = ?");
        $stmt->execute([$producto_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function eliminar($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT nombre_imagen FROM imagenes_producto WHERE id = ?");
        $stmt->execute([$id]);
        $imagen = $stmt->fetch();

        if ($imagen) {
            // ✅ Usar la MISMA ruta que en ImagenController
            $rutaArchivo = __DIR__ . '/../public/uploads/' . $imagen['nombre_imagen'];

            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }

            $stmt = $db->prepare("DELETE FROM imagenes_producto WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
    public static function obtenerPrimeraPorProducto($producto_id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT nombre_imagen FROM imagenes_producto WHERE producto_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$producto_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
