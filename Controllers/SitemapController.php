<?php
namespace Controllers;
use Models\Producto;
use Models\Categoria;

class SitemapController {
    public function index() {
        header('Content-Type: application/xml; charset=utf-8');

        $baseUrl = url('');

        $productos = (new Producto())->obtenerVisibles();
        $categorias = Categoria::obtenerTodas();

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Home
        echo '<url><loc>' . $baseUrl . '</loc><changefreq>daily</changefreq><priority>1.0</priority></url>';

        // Categorías
        foreach ($categorias as $categoria) {
            echo '<url><loc>' . $baseUrl . '/categoria/ver/' . $categoria['id'] . '</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>';
        }

        // Productos
        foreach ($productos as $producto) {
            echo '<url><loc>' . $baseUrl . '/producto/ver/' . $producto['id'] . '</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>';
        }

        echo '</urlset>';
    }
}
 //ruta de acceso https://tudominio.com/sitemap.xml