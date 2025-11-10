<?php

namespace Controllers;

use Models\VarianteProducto;

class VarianteController
{
    // 🛠️ Acción para actualizar una variante existente
    public function actualizar($id)
    {
        require_once __DIR__ . '/../Core/Helpers/urlHelper.php'; // Aseguramos el helper disponible

        $producto_id = $_POST['producto_id'] ?? null;
        $talla = $_POST['talla'] ?? '';
        $color = $_POST['color'] ?? '';
        $stock = $_POST['stock'] ?? 0;

        if ($id) {
            VarianteProducto::actualizar($id, $talla, $color, $stock);
        }

        // Redirecciono nuevamente a la edición del producto con url()
        header('Location: ' . url("producto/editar/$producto_id"));
        exit;
    }

    // Acción para eliminar una variante
    public function eliminar($id)
    {
        require_once __DIR__ . '/../Core/Helpers/urlHelper.php'; // Aseguramos el helper disponible

        // Llamo al modelo para eliminar la variante por ID
        \Models\VarianteProducto::eliminar($id);

        // Después de eliminar, redirijo a la edición del producto si existe el producto_id
        $producto_id = $_GET['producto_id'] ?? null;

        if ($producto_id) {
            header('Location: ' . url("producto/editar/$producto_id"));
        } else {
            // Si no hay producto_id, redirige al listado general de productos
            header('Location: ' . url('producto'));
        }
        exit;
    }

    // 🖼️ Acción para actualizar la imagen de una variante vía AJAX
    public function actualizar_imagen()
    {
        header('Content-Type: application/json');
        
        try {
            // Obtener datos
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $imagen = isset($_POST['imagen']) ? trim($_POST['imagen']) : null;
            
            // Validar ID
            if (!$id) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'ID de variante no válido'
                ]);
                return;
            }
            
            // Permitir imagen vacía (null) para quitar la asociación
            if ($imagen === '') {
                $imagen = null;
            }
            
            // Actualizar en la base de datos
            $resultado = VarianteProducto::actualizarImagen($id, $imagen);
            
            if ($resultado) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Imagen actualizada correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'No se pudo actualizar la imagen'
                ]);
            }
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
