<?php

namespace Controllers;

use Models\Banner;
use Models\Producto;
use Models\Categoria;
use Models\Etiqueta;
use Models\ImagenProducto;

require_once __DIR__ . '/BaseController.php';

class HomeController extends BaseController
{

    public function index()
    {
        // Banners
        $banners = Banner::obtenerActivosPorTipo('principal');
        $banners_secundarios_izquierda = Banner::obtenerActivosPorTipo('secundario_izquierda');
        $banners_secundarios_derecha = Banner::obtenerActivosPorTipo('secundario_derecha');

        // Productos destacados
        $productos_destacados = [];
        if (class_exists('Models\Producto') && method_exists('Models\Producto', 'obtenerDestacados')) {
            $productoModel = new Producto();
            $productos_destacados = $productoModel->obtenerDestacados(12);

            // Enriquecer productos con imágenes
            foreach ($productos_destacados as &$producto) {
                $producto['imagenes'] = \Models\ImagenProducto::obtenerPorProducto($producto['id']);
                $producto = $productoModel->prepararProductoParaVista($producto);
            }
            unset($producto);
        }

        $this->render('home/index', [
            'banners' => $banners,
            'banners_secundarios_izquierda' => $banners_secundarios_izquierda,
            'banners_secundarios_derecha' => $banners_secundarios_derecha,
            'productos_destacados' => $productos_destacados,
        ]);
    }

    /**
     * ======================================================
     *  MÉTODO BUSQUEDA: TIENDA CON FILTROS Y PAGINACIÓN
     * ======================================================
     */
    public function busqueda()
    {
        $productoModel = new Producto();

        // Término de búsqueda
        $termino = isset($_GET['termino']) ? trim($_GET['termino']) : '';

        // --- Validación de filtros ---
        $validacionFiltros = \Core\Helpers\Validator::validarFiltrosGET($_GET);
        $minPrice = $validacionFiltros['filtros_validos']['min_price'] ?? null;
        $maxPrice = $validacionFiltros['filtros_validos']['max_price'] ?? null;
        $categoriaId = isset($_GET['categoria']) && is_numeric($_GET['categoria']) ? (int)$_GET['categoria'] : null;
        $etiquetasSeleccionadas = isset($_GET['etiquetas']) ? (array)$_GET['etiquetas'] : [];
        $soloDisponibles = isset($_GET['disponibles']) && $_GET['disponibles'] == '1';
        $orden = $_GET['orden'] ?? '';

        // --- Paginación ---
        $productosPorPagina = 24;
        $paginaActual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) && $_GET['pagina'] > 0
            ? (int)$_GET['pagina'] : 1;
        $offset = ($paginaActual - 1) * $productosPorPagina;

        // --- Obtener productos filtrados ---
        $productos = $productoModel->obtenerFiltrados(
            $minPrice,
            $maxPrice,
            $categoriaId,
            $etiquetasSeleccionadas,
            $soloDisponibles,
            $orden,
            true,
            $productosPorPagina,
            $offset,
            $termino // si tu método soporta término, si no, ignora este argumento
        );
        $totalFiltrados = $productoModel->contarFiltrados(
            $minPrice,
            $maxPrice,
            $categoriaId,
            $etiquetasSeleccionadas,
            true,
            $termino // idem
        );

        $totalPaginas = (int) max(1, ceil($totalFiltrados / $productosPorPagina));


        // --- Enriquecer productos ---
        foreach ($productos as &$producto) {
            // Agregar imágenes con namespace completo (igual que ProductoController)
            $producto['imagenes'] = \Models\ImagenProducto::obtenerPorProducto($producto['id']);
            $producto = $productoModel->prepararProductoParaVista($producto);
        }
        unset($producto);


        // --- Categorías y etiquetas ---
        $categoriasDisponibles = Categoria::obtenerPadres();
        $etiquetaModel = new Etiqueta();
        $todasEtiquetas = $etiquetaModel->obtenerTodas();

        // --- Determinar la categoría actual (para el título dinámico) ---
        $categoriaActual = null;
        if ($categoriaId) {
            if (class_exists('\Models\Categoria') && method_exists('\Models\Categoria', 'obtenerPorId')) {
                $categoriaActual = \Models\Categoria::obtenerPorId($categoriaId);
            } else {
                // Fallback simple si no existe método en el modelo
                $db = \Core\Database::getInstance()->getConnection();
                $stmt = $db->prepare('SELECT id, nombre FROM categorias WHERE id = :id LIMIT 1');
                $stmt->execute([':id' => $categoriaId]);
                $categoriaActual = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            }
        }

        // --- Query base para links ---
        $currentQuery = $_GET;
        unset($currentQuery['pagina'], $currentQuery['ajax']);

        // --- Lógica AJAX ---
        $isAjax = !empty($_GET['ajax']);
        if ($isAjax) {
            ob_start();
            require __DIR__ . '/../views/home/_products_grid.php';
            $productsHtml = ob_get_clean();

            ob_start();
            require __DIR__ . '/../views/home/_pagination.php';
            $paginationHtml = ob_get_clean();

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'products_html' => $productsHtml,
                'pagination_html' => $paginationHtml,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // --- Vista completa ---
        $this->render('home/busqueda', [
            'productos' => $productos,
            'categoriasDisponibles' => $categoriasDisponibles, // mantengo el nombre que usa tu vista
            'categorias' => $categoriasDisponibles, // adicional por seguridad
            'etiquetas' => $todasEtiquetas,
            'paginaActual' => $paginaActual,
            'totalPaginas' => $totalPaginas,
            'totalFiltrados' => $totalFiltrados,
            'query' => $currentQuery,
            'termino' => $termino,
            'categoriaActual' => $categoriaActual
        ]);
    }

}
