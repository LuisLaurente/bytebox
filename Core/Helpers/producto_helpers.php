<?php
/**
 * Helper functions para productos
 */

if (!function_exists('producto_imagen_url')) {
    /**
     * Obtiene la URL de una imagen de producto
     * 
     * @param array $producto El producto con sus imágenes
     * @param int $idx Índice de la imagen (0 por defecto)
     * @return string URL de la imagen
     */
    function producto_imagen_url($producto, $idx = 0)
    {
        if (!empty($producto['imagenes']) && isset($producto['imagenes'][$idx])) {
            $imagen = $producto['imagenes'][$idx];
            
            // Caso 1: Array con índice 'nombre_imagen'
            if (is_array($imagen) && isset($imagen['nombre_imagen'])) {
                return url('uploads/' . $imagen['nombre_imagen']);
            }
            
            // Caso 2: String directo (nueva estructura)
            if (is_string($imagen)) {
                // Limpiar la ruta si ya contiene /uploads/
                if (strpos($imagen, '/uploads/') === 0) {
                    $imagen = substr($imagen, 9); // Remover '/uploads/'
                }
                return url('uploads/' . $imagen);
            }
        }
        
        return url('uploads/default-product.png');
    }
}