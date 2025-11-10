<?php

namespace Controllers;

use Core\Database;
use Models\Producto;
use Models\VarianteProducto;
use Models\ImagenProducto;
use Models\Categoria;
use Models\Etiqueta;
use PDO;
use PDOException;
use Exception;

class ProductoController extends BaseController
{
    public function index()
    {
        $productoModel = new Producto();

        // Filtros comunes
        $validacionFiltros = \Core\Helpers\Validator::validarFiltrosGET($_GET);
        $minPrice = $validacionFiltros['filtros_validos']['min_price'] ?? null;
        $maxPrice = $validacionFiltros['filtros_validos']['max_price'] ?? null;
        $categoriaId = isset($_GET['categoria']) && is_numeric($_GET['categoria']) ? (int)$_GET['categoria'] : null;

        // Filtros adicionales por etiquetas, stock y orden
        $etiquetasSeleccionadas = $_GET['etiquetas'] ?? [];
        $soloDisponibles = isset($_GET['disponibles']) && $_GET['disponibles'] == '1';
        $orden = $_GET['orden'] ?? '';

        // 🔎 Nuevo filtro: búsqueda por nombre o descripción
        $busqueda = isset($_GET['q']) && !empty(trim($_GET['q'])) ? trim($_GET['q']) : null;

        // Obtener datos para filtros y visualización
        $estadisticasPrecios = $productoModel->obtenerEstadisticasPrecios();
        $categoriasDisponibles = Producto::obtenerCategoriasConProductos();
        $productos = $productoModel->obtenerFiltrados(
            $minPrice,
            $maxPrice,
            $categoriaId,
            $etiquetasSeleccionadas,
            $soloDisponibles,
            $orden,
            $busqueda // 👈 pasamos la búsqueda al modelo
        );
        $totalFiltrados = $productoModel->contarFiltrados(
            $minPrice,
            $maxPrice,
            $categoriaId,
            $etiquetasSeleccionadas,
            $busqueda
        );

        // Asociar categorías e imágenes a cada producto
        foreach ($productos as &$producto) {
            $producto['categorias'] = Producto::obtenerCategoriasPorProducto($producto['id']);
            $producto['imagenes'] = \Models\ImagenProducto::obtenerPorProducto($producto['id']);
        }
        unset($producto);

        // Obtener todas las etiquetas
        $etiquetaModel = new Etiqueta();
        $todasEtiquetas = $etiquetaModel->obtenerTodas();

        // Si es petición AJAX, devolver JSON
        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');

            echo json_encode([
                'success' => empty($validacionFiltros['errores']),
                'productos' => $productos,
                'total' => $totalFiltrados,
                'filtros' => [
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                    'categoria' => $categoriaId,
                    'etiquetas' => $etiquetasSeleccionadas,
                    'disponibles' => $soloDisponibles,
                    'orden' => $orden,
                    'q' => $busqueda // 👈 incluimos búsqueda en la respuesta AJAX
                ],
                'errores' => $validacionFiltros['errores'] ?? []
            ]);
            exit;
        }

        // 🔹 Variables SEO
        $metaTitle = 'Catálogo de productos | Tienda Tecnovedades';
        $metaDescription = 'Explora nuestro catálogo con la mejor tecnología y novedades a precios increíbles.';
        $metaImage = url('images/catalogo-share.png');
        $canonical = url('producto');

        require_once __DIR__ . '/../views/producto/index.php';
    }


    public function crear()
    {
        $categorias = Categoria::obtenerTodas();
        $etiquetas = (new Etiqueta())->obtenerTodas();
        $productoModel = new Producto();
        $allProducts = $productoModel->obtenerVisibles(); // Para productos relacionados en la vista
        require_once __DIR__ . '/../views/producto/crear.php';
    }

    public function guardar()
    {
        $db = \Core\Database::getInstance()->getConnection();
        $errores = [];

        $nombre = $_POST['nombre'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $precio = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;
        $precio_tachado = $_POST['precio_tachado'] ?? null;
        $stock = $_POST['stock'] ?? '';
        $visible = isset($_POST['visible']) ? 1 : 0;
        $destacado = isset($_POST['destacado']) ? 1 : 0;

        // flags de visibilidad
        $precio_tachado_visible = isset($_POST['precio_tachado_visible']) ? 1 : 0;
        $porcentaje_visible = isset($_POST['porcentaje_visible']) ? 1 : 0;

        // Validaciones básicas
        if (!\Core\Helpers\Validator::isRequired($nombre)) $errores[] = "El nombre del producto es obligatorio.";
        if (!\Core\Helpers\Validator::isNumeric($precio)) $errores[] = "El precio debe ser un valor numérico.";
        if (!\Core\Helpers\Validator::isNumeric($stock)) $errores[] = "El stock debe ser un valor numérico.";

        // Validar precio_tachado y recalcular porcentaje
        if ($precio_tachado !== null && $precio_tachado !== '') {
            if (!is_numeric($precio_tachado) || (float)$precio_tachado <= $precio) {
                $precio_tachado = null;
                $precio_tachado_visible = 0;
                $porcentaje_descuento = 0;
                $porcentaje_visible = 0;
            } else {
                $precio_tachado = (float)$precio_tachado;
                $porcentaje_descuento = round((($precio_tachado - $precio) / $precio_tachado) * 100);
            }
        } else {
            $precio_tachado = null;
            $porcentaje_descuento = 0;
            $precio_tachado_visible = 0;
            $porcentaje_visible = 0;
        }

        if (!empty($errores)) {
            $_SESSION['flash_error'] = $errores;
            $categorias = Categoria::obtenerTodas();
            $etiquetas = (new Etiqueta())->obtenerTodas();
            $productoModel = new Producto();
            $allProducts = $productoModel->obtenerVisibles();
            require __DIR__ . '/../views/producto/crear.php';
            return;
        }

        $db->beginTransaction();
        try {
            // Campos adicionales
            $especificaciones = trim($_POST['especificaciones'] ?? '');
            $productos_relacionados_input = $_POST['productos_relacionados'] ?? [];
            $productos_relacionados_input = array_map('intval', (array)$productos_relacionados_input);
            $productos_relacionados_json = !empty($productos_relacionados_input) ? json_encode($productos_relacionados_input) : null;

            // INSERT del producto
            $stmt = $db->prepare("INSERT INTO productos 
            (nombre, descripcion, precio, precio_tachado, porcentaje_descuento, 
             precio_tachado_visible, porcentaje_visible, stock, visible, destacado,
             especificaciones, productos_relacionados) 
            VALUES (:nombre, :descripcion, :precio, :precio_tachado, :porcentaje_descuento, 
            :precio_tachado_visible, :porcentaje_visible, :stock, :visible, :destacado,
            :especificaciones, :productos_relacionados)");

            $stmt->execute([
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':precio' => $precio,
                ':precio_tachado' => $precio_tachado,
                ':porcentaje_descuento' => $porcentaje_descuento,
                ':precio_tachado_visible' => $precio_tachado_visible,
                ':porcentaje_visible' => $porcentaje_visible,
                ':stock' => $stock,
                ':visible' => $visible,
                ':destacado' => $destacado,
                ':especificaciones' => $especificaciones ?: null,
                ':productos_relacionados' => $productos_relacionados_json
            ]);

            $producto_id = $db->lastInsertId();

            // === Subir y guardar imágenes (VERSIÓN SIMPLE QUE FUNCIONA) ===
            $rutaDestino = __DIR__ . '/../public/uploads/';

            // Crear carpeta si no existe
            if (!is_dir($rutaDestino)) {
                mkdir($rutaDestino, 0777, true);
            }

            $imagenesSubidas = 0;
            if (!empty($_FILES['imagenes']['name'][0])) {
                foreach ($_FILES['imagenes']['tmp_name'] as $index => $tmpName) {
                    $nombreOriginal = $_FILES['imagenes']['name'][$index];
                    $nombreFinal = uniqid() . '_' . basename($nombreOriginal);
                    $rutaFinal = $rutaDestino . $nombreFinal;

                    if (move_uploaded_file($tmpName, $rutaFinal)) {
                        if (ImagenProducto::guardar($producto_id, $nombreFinal)) {
                            $imagenesSubidas++;
                        }
                    }
                }
            }

            // Categorías
            if (isset($_POST['categorias']) && !empty($_POST['categorias'])) {
                foreach ($_POST['categorias'] as $cat_id) {
                    if (is_numeric($cat_id)) {
                        $db->prepare("INSERT INTO producto_categoria (id_producto, id_categoria) VALUES (?, ?)")
                            ->execute([$producto_id, (int)$cat_id]);
                    }
                }
            }

            // Etiquetas
            if (isset($_POST['etiquetas']) && !empty($_POST['etiquetas'])) {
                foreach ($_POST['etiquetas'] as $etiqueta_id) {
                    if (is_numeric($etiqueta_id)) {
                        $db->prepare("INSERT INTO producto_etiqueta (producto_id, etiqueta_id) VALUES (?, ?)")
                            ->execute([$producto_id, (int)$etiqueta_id]);
                    }
                }
            }

            // Variantes - permitir variantes con solo talla, solo color, o ambos
            if (isset($_POST['variantes']) && 
                (isset($_POST['variantes']['talla']) || isset($_POST['variantes']['color']))) {
                $tallas = $_POST['variantes']['talla'] ?? [];
                $colores = $_POST['variantes']['color'] ?? [];
                $stocks = $_POST['variantes']['stock'] ?? [];
                $imagenes = $_POST['variantes']['imagen'] ?? [];
                
                $maxIndex = max(count($tallas), count($colores), count($stocks));
                
                for ($i = 0; $i < $maxIndex; $i++) {
                    $talla = trim($tallas[$i] ?? '');
                    $color = trim($colores[$i] ?? '');
                    $stockVar = (int)($stocks[$i] ?? 0);
                    $imagen = trim($imagenes[$i] ?? '');
                    
                    // Solo insertar si al menos talla o color tiene valor
                    if (!empty($talla) || !empty($color)) {
                        $stmtVar = $db->prepare("INSERT INTO variantes_producto (producto_id, talla, color, stock, imagen) VALUES (?, ?, ?, ?, ?)");
                        $stmtVar->execute([
                            $producto_id, 
                            $talla ?: null, 
                            $color ?: null, 
                            $stockVar,
                            !empty($imagen) ? $imagen : null
                        ]);
                    }
                }
            }

            $db->commit();

            $_SESSION['flash_success'] = "Producto creado correctamente. Imágenes subidas: $imagenesSubidas.";
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Error al guardar producto: " . $e->getMessage());
            $_SESSION['flash_error'] = "Error al crear producto: " . $e->getMessage();
            $categorias = Categoria::obtenerTodas();
            $etiquetas = (new Etiqueta())->obtenerTodas();
            $productoModel = new Producto();
            $allProducts = $productoModel->obtenerVisibles();
            require __DIR__ . '/../views/producto/crear.php';
            return;
        }

        header("Location: " . url("producto/index"));
        exit;
    }

    public function editar($id)
    {
        // Validar que el ID sea numérico
        if (!is_numeric($id)) {
            $_SESSION['flash_error'] = 'ID de producto no válido.';
            header('Location: ' . url('producto/index'));
            exit;
        }

        // Obtener el producto
        $producto = Producto::obtenerPorId($id);
        if (!$producto) {
            $_SESSION['flash_error'] = 'Producto no encontrado.';
            header('Location: ' . url('producto/index'));
            exit;
        }

        try {
            // Asegurar estructura mínima
            if (!isset($producto['destacado'])) {
                $producto['destacado'] = 0;
            }

            // Procesar productos_relacionados: solo decodificar si es string (JSON)
            if (!empty($producto['productos_relacionados'])) {
                if (is_string($producto['productos_relacionados'])) {
                    $producto['productos_relacionados'] = json_decode($producto['productos_relacionados'], true) ?: [];
                } elseif (is_array($producto['productos_relacionados'])) {
                    // ya es array, dejarlo
                } else {
                    $producto['productos_relacionados'] = [];
                }
            } else {
                $producto['productos_relacionados'] = [];
            }

            // Obtener datos relacionados
            $db = \Core\Database::getInstance()->getConnection();

            // Variantes del producto
            $variantes = VarianteProducto::obtenerPorProductoId($id);

            // Todas las categorías disponibles
            $categorias = Categoria::obtenerTodas();

            // Todas las etiquetas disponibles
            $etiquetaModel = new Etiqueta();
            $etiquetas = $etiquetaModel->obtenerTodas();

            // Etiquetas asignadas al producto
            $etiquetasAsignadas = $etiquetaModel->obtenerEtiquetasPorProducto($id);

            // Categorías asignadas al producto
            $stmt = $db->prepare("SELECT id_categoria FROM producto_categoria WHERE id_producto = ?");
            $stmt->execute([$id]);
            $categoriasAsignadas = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'id_categoria');

            // Imágenes del producto (array de filas: id, producto_id, nombre_imagen)
            $imagenes = ImagenProducto::obtenerPorProducto($id);

            // Todos los productos para "productos relacionados" (excluyendo el actual)
            $productoModel = new Producto();
            $allProductsQuery = $productoModel->obtenerVisibles();
            $allProducts = array_filter($allProductsQuery, function ($p) use ($id) {
                return $p['id'] != $id;
            });

            // Variables para la vista
            $metaTitle = 'Editar Producto: ' . htmlspecialchars($producto['nombre']) . ' | Panel Admin';
            $metaDescription = 'Editando el producto ' . htmlspecialchars($producto['nombre']);

            // Cargar la vista
            require __DIR__ . '/../views/producto/editar.php';
        } catch (\Exception $e) {
            error_log("Error al cargar producto para editar (ID: $id): " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al cargar el producto. Por favor, inténtelo de nuevo.';
            header('Location: ' . url('producto/index'));
            exit;
        }
    }

    public function actualizar()
    {
        $db = \Core\Database::getInstance()->getConnection();
        $errores = [];

        $id = $_POST['id'];
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = isset($_POST['precio']) ? (float) $_POST['precio'] : 0;
        $precio_tachado = $_POST['precio_tachado'] ?? null;
        $stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;
        $visible = isset($_POST['visible']) ? 1 : 0;

        // ✅ Campo destacado
        $destacado = isset($_POST['destacado']) ? 1 : 0;

        // flags de visibilidad (checkboxes)
        $precio_tachado_visible = isset($_POST['precio_tachado_visible']) ? 1 : 0;
        $porcentaje_visible = isset($_POST['porcentaje_visible']) ? 1 : 0;

        // Validaciones básicas
        if (empty($nombre)) $errores[] = "El nombre del producto es obligatorio.";
        if (!is_numeric($precio) || $precio <= 0) $errores[] = "El precio debe ser un valor numérico válido.";
        if (!is_numeric($stock) || $stock < 0) $errores[] = "El stock debe ser un valor numérico válido.";

        // Validar precio_tachado
        if ($precio_tachado !== null && $precio_tachado !== '') {
            if (!is_numeric($precio_tachado) || (float)$precio_tachado <= $precio) {
                $precio_tachado = null;
                $precio_tachado_visible = 0;
                $porcentaje_descuento = 0;
                $porcentaje_visible = 0;
            } else {
                $precio_tachado = (float)$precio_tachado;
                $porcentaje_descuento = round((($precio_tachado - $precio) / $precio_tachado) * 100);
            }
        } else {
            $precio_tachado = null;
            $porcentaje_descuento = 0;
            $precio_tachado_visible = 0;
            $porcentaje_visible = 0;
        }

        // Validar nuevas imágenes si se suben
        // ==================== PROCESAR NUEVAS IMÁGENES ====================
        $imagenesSubidas = 0;
        if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
            $uploadDir = __DIR__ . '/../public/uploads/';

            // Crear directorio si no existe (simple)
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach ($_FILES['imagenes']['tmp_name'] as $index => $tmpName) {
                if ($_FILES['imagenes']['error'][$index] === UPLOAD_ERR_OK) {
                    $nombreOriginal = $_FILES['imagenes']['name'][$index];
                    $nombreFinal = uniqid() . '_' . basename($nombreOriginal);
                    $uploadPath = $uploadDir . $nombreFinal;

                    if (move_uploaded_file($tmpName, $uploadPath)) {
                        if (ImagenProducto::guardar($id, $nombreFinal)) {
                            $imagenesSubidas++;
                        }
                    }
                }
            }
        }

        if (!empty($errores)) {
            $_SESSION['flash_error'] = implode('<br>', $errores);
            header("Location: " . url("producto/editar/$id"));
            exit;
        }

        $db->beginTransaction();
        try {
            // Recoger inputs adicionales
            $especificaciones = trim($_POST['especificaciones'] ?? '');
            $productos_relacionados_input = $_POST['productos_relacionados'] ?? [];
            $productos_relacionados_input = array_map('intval', (array)$productos_relacionados_input);
            $productos_relacionados_json = !empty($productos_relacionados_input) ? json_encode($productos_relacionados_input) : null;

            // ✅ Actualizar producto con todos los campos
            $actualizado = Producto::actualizar(
                $id,
                $nombre,
                $descripcion,
                $precio,
                $precio_tachado,
                $porcentaje_descuento,
                $precio_tachado_visible,
                $porcentaje_visible,
                $stock,
                $visible,
                $destacado,
                $especificaciones,
                $productos_relacionados_json
            );

            if (!$actualizado) {
                throw new Exception("Error al actualizar el producto en la base de datos");
            }

            // ==================== ACTUALIZAR CATEGORÍAS ====================
            $db->prepare("DELETE FROM producto_categoria WHERE id_producto = ?")->execute([$id]);
            if (isset($_POST['categorias']) && !empty($_POST['categorias'])) {
                foreach ($_POST['categorias'] as $cat_id) {
                    if (is_numeric($cat_id)) {
                        $db->prepare("INSERT INTO producto_categoria (id_producto, id_categoria) VALUES (?, ?)")
                            ->execute([$id, (int)$cat_id]);
                    }
                }
            }

            // ==================== ACTUALIZAR ETIQUETAS ====================
            $db->prepare("DELETE FROM producto_etiqueta WHERE producto_id = ?")->execute([$id]);
            if (isset($_POST['etiquetas']) && !empty($_POST['etiquetas'])) {
                foreach ($_POST['etiquetas'] as $etiqueta_id) {
                    if (is_numeric($etiqueta_id)) {
                        $db->prepare("INSERT INTO producto_etiqueta (producto_id, etiqueta_id) VALUES (?, ?)")
                            ->execute([$id, (int)$etiqueta_id]);
                    }
                }
            }

            // ==================== PROCESAR NUEVAS IMÁGENES ====================
            $imagenesSubidas = 0;
            if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
                $uploadDir = __DIR__ . '/../public/uploads/';
                // Crear directorio si no existe
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        throw new Exception("No se pudo crear el directorio de uploads");
                    }
                }

                // Validar y procesar cada imagen
                for ($i = 0; $i < count($_FILES['imagenes']['name']); $i++) {
                    if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                        $originalName = $_FILES['imagenes']['name'][$i];
                        $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
                        $uniqueName = uniqid() . '_' . time() . '.' . $fileExtension;
                        $uploadPath = $uploadDir . $uniqueName;

                        // Validar tipo de archivo
                        $tipo = mime_content_type($_FILES['imagenes']['tmp_name'][$i]);
                        if (!in_array($tipo, ['image/jpeg', 'image/png', 'image/webp'])) {
                            $errores[] = "Archivo no válido: " . $originalName;
                            continue;
                        }

                        if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$i], $uploadPath)) {
                            // Guardar en base de datos usando el modelo (correcto con producto_id)
                            if (ImagenProducto::guardar($id, $uniqueName)) {
                                $imagenesSubidas++;
                            } else {
                                unlink($uploadPath); // Rollback si falla BD
                                $errores[] = "Error al guardar imagen en BD: " . $originalName;
                            }
                        } else {
                            $errores[] = "Error al subir la imagen: " . $originalName;
                        }
                    }
                }
            }

            // ==================== ELIMINAR IMÁGENES MARCADAS ====================
            if (isset($_POST['eliminar_imagenes']) && !empty($_POST['eliminar_imagenes'])) {
                foreach ($_POST['eliminar_imagenes'] as $img_id) {
                    if (is_numeric($img_id)) {
                        ImagenProducto::eliminar((int)$img_id); // Borra de BD y disco
                    }
                }
            }

            // ==================== ACTUALIZAR VARIANTES ====================
            // Obtener IDs de variantes existentes
            $variantesIds = $_POST['variantes']['id'] ?? [];
            $tallas = $_POST['variantes']['talla'] ?? [];
            $colores = $_POST['variantes']['color'] ?? [];
            $stocks = $_POST['variantes']['stock'] ?? [];
            $imagenes = $_POST['variantes']['imagen'] ?? [];

            // Actualizar o crear variantes
            if (isset($_POST['variantes']) && 
                (isset($_POST['variantes']['talla']) || isset($_POST['variantes']['color']))) {
                
                $maxIndex = max(count($tallas), count($colores), count($stocks));

                for ($i = 0; $i < $maxIndex; $i++) {
                    $talla = trim($tallas[$i] ?? '');
                    $color = trim($colores[$i] ?? '');
                    $stockVar = (int)($stocks[$i] ?? 0);
                    $imagen = trim($imagenes[$i] ?? '');
                    $varianteId = isset($variantesIds[$i]) ? (int)$variantesIds[$i] : 0;

                    // Solo procesar si hay al menos talla o color
                    if (!empty($talla) || !empty($color)) {
                        if ($varianteId > 0) {
                            // ACTUALIZAR variante existente (mantiene la imagen si no se especifica nueva)
                            $stmtVar = $db->prepare("UPDATE variantes_producto SET talla = ?, color = ?, stock = ?, imagen = ? WHERE id = ?");
                            $stmtVar->execute([
                                !empty($talla) ? $talla : null,
                                !empty($color) ? $color : null,
                                $stockVar,
                                !empty($imagen) ? $imagen : null,
                                $varianteId
                            ]);
                        } else {
                            // CREAR nueva variante
                            $stmtVar = $db->prepare("INSERT INTO variantes_producto (producto_id, talla, color, stock, imagen) VALUES (?, ?, ?, ?, ?)");
                            $stmtVar->execute([
                                $id,
                                !empty($talla) ? $talla : null,
                                !empty($color) ? $color : null,
                                $stockVar,
                                !empty($imagen) ? $imagen : null
                            ]);
                        }
                    }
                }
            }

            // Si hay errores con imágenes, mostrar advertencia pero no fallar la actualización
            if (!empty($errores)) {
                $_SESSION['flash_warning'] = 'Producto actualizado, pero hubo problemas con algunas imágenes: ' . implode('<br>', $errores);
            } else {
                $_SESSION['flash_success'] = 'Producto actualizado correctamente. Imágenes subidas: ' . $imagenesSubidas . '.';
            }

            // Confirmar transacción
            $db->commit();

            header("Location: " . url("producto/editar/$id"));
            exit;
        } catch (\Exception $e) {
            // Rollback en caso de error
            $db->rollBack();

            // Log del error
            error_log("Error al actualizar producto: " . $e->getMessage());

            $_SESSION['flash_error'] = 'Error al actualizar el producto. Por favor, inténtelo de nuevo.';
            header("Location: " . url("producto/editar/$id"));
            exit;
        }
    }

    public function eliminar($id)
    {
        if (!is_numeric($id)) {
            $_SESSION['flash_error'] = 'ID de producto no válido.';
            header('Location: ' . url('producto/index'));
            exit;
        }

        $db = \Core\Database::getInstance()->getConnection();
        $db->beginTransaction();
        try {
            // Obtener y eliminar imágenes (borra de BD y disco usando el modelo)
            $imagenes = ImagenProducto::obtenerPorProducto($id);
            foreach ($imagenes as $imagen) {
                ImagenProducto::eliminar($imagen['id']);
            }

            // Eliminar relaciones
            $db->prepare("DELETE FROM producto_categoria WHERE id_producto = ?")->execute([$id]);
            $db->prepare("DELETE FROM producto_etiqueta WHERE producto_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM variantes_producto WHERE producto_id = ?")->execute([$id]);

            // Eliminar el producto principal
            Producto::eliminar($id);

            $db->commit();
            $_SESSION['flash_success'] = 'Producto eliminado correctamente.';
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Error al eliminar producto (ID: $id): " . $e->getMessage());
            $_SESSION['flash_error'] = 'Error al eliminar el producto: ' . $e->getMessage();
        }

        header('Location: ' . url('producto/index')); // Corregido: redirige a lista de productos, no carrito
        exit;
    }
    public function listarPublico()
    {
        $productoModel = new Producto();

        // ✅ Solo obtener productos visibles
        $productos = $productoModel->obtenerVisibles();

        // Asociar categorías a cada producto (opcional)
        foreach ($productos as &$producto) {
            $producto['categorias'] = Producto::obtenerCategoriasPorProducto($producto['id']);
        }
        unset($producto);

        // Mostrar la vista pública (home)
        require_once __DIR__ . '/../public/home.php';
    }
    public function autocomplete()
    {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';

        header('Content-Type: application/json');

        if ($q === '') {
            echo json_encode([]);
            exit;
        }

        $productoModel = new \Models\Producto();
        $resultados = $productoModel->buscarPorNombre($q);

        // Puedes mapear/limpiar campos si quieres reducir tamaño
        echo json_encode($resultados);
        exit;
    }
    public function ver($id)
    {
        $productoModel = new Producto();

        if (!is_numeric($id)) {
            header('Location: ' . url('producto'));
            exit;
        }

        $producto = $productoModel->obtenerPorId((int)$id);

        if (!$producto) {
            header('Location: ' . url('producto'));
            exit;
        }

        $producto['categorias'] = Producto::obtenerCategoriasPorProducto($producto['id']);
        $producto['imagenes'] = \Models\ImagenProducto::obtenerPorProducto($producto['id']);
        
        // Obtener variantes del producto
        $producto['variantes'] = VarianteProducto::obtenerPorProductoId($producto['id']);
        
        // DEBUG: Verificar variantes
        error_log("=== DEBUG VARIANTES PRODUCTO ID: {$producto['id']} ===");
        error_log("Variantes obtenidas: " . json_encode($producto['variantes']));
        error_log("Cantidad de variantes: " . count($producto['variantes']));

        // --- CALCULAR ESTADÍSTICAS DE RESEÑAS ---
        try {
            $db = \Core\Database::getInstance()->getConnection();

            // Obtener reseñas aprobadas
            $stmt = $db->prepare("
            SELECT r.*, u.nombre AS usuario_nombre
            FROM product_reviews r
            JOIN usuarios u ON r.user_id = u.id
            WHERE r.producto_id = :id AND r.estado = 'aprobado'
            ORDER BY r.created_at DESC
        ");
            $stmt->bindParam(':id', $producto['id'], \PDO::PARAM_INT);
            $stmt->execute();
            $reviews = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calcular estadísticas
            $totalReviews = count($reviews);
            $averageRating = 0;

            if ($totalReviews > 0) {
                $totalScore = 0;
                foreach ($reviews as $review) {
                    $totalScore += (int)$review['puntuacion'];
                }
                $averageRating = round($totalScore / $totalReviews, 1);
            }

            // Agregar estadísticas al producto para la vista
            $producto['rating_average'] = $averageRating;
            $producto['rating_count'] = $totalReviews;
        } catch (\Throwable $e) {
            error_log('ProductoController::ver - error cargando reseñas: ' . $e->getMessage());
            $reviews = [];
            $producto['rating_average'] = 0;
            $producto['rating_count'] = 0;
        }

        // --- Breadcrumb MEJORADO ---
        try {
            $db = \Core\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id_categoria FROM producto_categoria WHERE id_producto = ?");
            $stmt->execute([(int)$producto['id']]);
            $catIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $bestChain = [];
            foreach ($catIds as $cid) {
                $chain = \Models\Categoria::obtenerAncestros((int)$cid);
                if (count($chain) > count($bestChain)) {
                    $bestChain = $chain;
                }
            }

            // AHORA el breadcrumb incluye IDs
            $breadcrumb = [['id' => '', 'nombre' => 'Inicio']]; // Inicio sin categoría

            if (!empty($bestChain)) {
                foreach ($bestChain as $categoria) {
                    $breadcrumb[] = [
                        'id' => $categoria['id'],
                        'nombre' => $categoria['nombre']
                    ];
                }
            } else {
                $breadcrumb[] = ['id' => '', 'nombre' => 'Productos'];
            }

            // El producto actual (sin link)
            $breadcrumb[] = ['id' => '', 'nombre' => $producto['nombre']];
        } catch (\Throwable $e) {
            error_log('ProductoController::ver - error construyendo breadcrumb: ' . $e->getMessage());
            $breadcrumb = [
                ['id' => '', 'nombre' => 'Inicio'],
                ['id' => '', 'nombre' => 'Productos'],
                ['id' => '', 'nombre' => $producto['nombre']]
            ];
        }

        // ... código de productos relacionados
        $relatedProducts = [];
        $ids = $producto['productos_relacionados'] ?? [];

        if (!empty($ids)) {
            $ids = array_values(array_diff(array_map('intval', $ids), [(int)$producto['id']]));
            if (!empty($ids)) {
                $relatedProducts = $productoModel->obtenerPorIds($ids);
                foreach ($relatedProducts as &$rp) {
                    $rp['imagenes'] = \Models\ImagenProducto::obtenerPorProducto((int)$rp['id']);
                    $rp['imagen'] = !empty($rp['imagenes'][0]['nombre_imagen'])
                        ? $rp['imagenes'][0]['nombre_imagen']
                        : null;
                }
                unset($rp);
            }
        }

        // Variables SEO
        $meta_title = $producto['nombre'] . ' | Tienda Tecnovedades';
        $meta_description = substr(strip_tags($producto['descripcion']), 0, 160);
        $meta_image = !empty($producto['imagenes'][0]['nombre_imagen'])
            ? url('uploads/' . $producto['imagenes'][0]['nombre_imagen'])
            : url('images/default-share.png');
        $canonical = url('producto/ver/' . $producto['id']);

        require_once __DIR__ . '/../views/producto/descripcion.php';
    }
    public function busqueda()
    {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';

        if (empty($q)) {
            // Si no hay búsqueda, redirigimos al home
            header('Location: ' . url('home/index'));
            exit;
        }

        $productoModel = new \Models\Producto();
        $resultados = $productoModel->buscarPorNombre($q);

        // 🔹 Agregar imágenes a cada producto
        foreach ($resultados as &$producto) {
            $producto['imagenes'] = \Models\ImagenProducto::obtenerPorProducto($producto['id']);
        }
        unset($producto);

        // 🔹 Variables que la vista necesita
        $termino = $q;
        $productos = $resultados; // La vista espera $productos, no $resultados
        $totalProductos = count($productos);
        $totalEncontrados = $totalProductos;

        // Variables de paginación (valores por defecto)
        $paginaActual = 1;
        $productosPorPagina = 15;
        $totalPaginas = 1;

        // Variables de categorías - obtener todas las categorías disponibles
        $categoriaActual = null;
        try {
            $categoriaModel = new \Models\Categoria();
            $categoriasDisponibles = $categoriaModel->obtenerTodas();
        } catch (\Exception $e) {
            $categoriasDisponibles = [];
        }

        // 🔹 Variables SEO para resultados de búsqueda
        $metaTitle = 'Resultados para "' . htmlspecialchars($termino) . '" | Tienda Tecnovedades';
        $metaDescription = 'Encuentra los mejores productos relacionados con "' . htmlspecialchars($termino) . '".';
        $metaImage = url('images/busqueda-share.png');
        $canonical = url('producto/busqueda?q=' . urlencode($termino));

        // Renderizamos la vista de búsqueda
        include __DIR__ . '/../views/home/busqueda.php';
    }
    public function guardarComentario()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = \Core\Database::getConexion();

            $producto_id = $_POST['producto_id'] ?? null;
            $orden_id    = $_POST['orden_id'] ?? null;
            $user_id     = $_POST['user_id'] ?? null; // lo recibes del formulario o de la sesión
            $puntuacion  = $_POST['puntuacion'] ?? null;
            $titulo      = $_POST['titulo'] ?? null;
            $texto       = $_POST['texto'] ?? null;

            if ($producto_id && $orden_id && $user_id && $puntuacion) {
                $stmt = $db->prepare("
                INSERT INTO product_reviews 
                (producto_id, user_id, orden_id, puntuacion, titulo, texto, created_at)
                VALUES (:producto_id, :user_id, :orden_id, :puntuacion, :titulo, :texto, NOW())
            ");

                $stmt->bindParam(':producto_id', $producto_id, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':orden_id', $orden_id, PDO::PARAM_INT);
                $stmt->bindParam(':puntuacion', $puntuacion, PDO::PARAM_INT);
                $stmt->bindParam(':titulo', $titulo, PDO::PARAM_STR);
                $stmt->bindParam(':texto', $texto, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    header("Location: " . url('usuario/pedidos')); // regresa a pedidos
                    $_SESSION['flash'] = "✅ Comentario guardado con éxito";
                    exit;
                } else {
                    $_SESSION['flash'] = "❌ Error al guardar el comentario";
                }
            } else {
                $_SESSION['flash'] = "⚠️ Faltan datos para guardar el comentario";
            }
            // Redirigir siempre a pedidos
            header("Location: " . url('usuario/pedidos'));
            exit;
        }
    }
}
