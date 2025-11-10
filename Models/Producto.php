<?php

namespace Models;

use Core\Database;
use PDO;

class Producto
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerTodos($etiquetas = [], $soloDisponibles = false, $orden = '')
    {
        $sql = "SELECT DISTINCT p.* FROM productos p";
        $params = [];

        if (!empty($etiquetas)) {
            $etiquetaPlaceholders = [];
            foreach ($etiquetas as $i => $etiquetaId) {
                $key = ":etiqueta_$i";
                $etiquetaPlaceholders[] = $key;
                $params[$key] = $etiquetaId;
            }
            $sql .= " JOIN producto_etiqueta pe ON p.id = pe.producto_id
                      WHERE pe.etiqueta_id IN (" . implode(',', $etiquetaPlaceholders) . ")";
        } else {
            $sql .= " WHERE 1=1";
        }

        $sql .= " AND p.visible = 1";

        if ($soloDisponibles) {
            $sql .= " AND p.stock > 0";
        }

        $ordenesValidos = [
            'precio_asc'   => 'p.precio ASC',
            'precio_desc'  => 'p.precio DESC',
            'nombre_asc'   => 'p.nombre ASC',
            'nombre_desc'  => 'p.nombre DESC',
            'fecha_desc'   => 'p.created_at DESC'
        ];

        if (array_key_exists($orden, $ordenesValidos)) {
            $sql .= " ORDER BY " . $ordenesValidos[$orden];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productos as &$producto) {
            $producto = $this->prepararProductoParaVista($producto);
        }

        return $productos;
    }

    public function obtenerFiltrados(
        $minPrice = null,
        $maxPrice = null,
        $categoriaId = null,
        array $etiquetas = [],
        $soloDisponibles = false,
        $orden = '',
        $visibleOnly = true,
        $limit = null,
        $offset = null,
        $termino = ''
    ) {
        $db = \Core\Database::getInstance()->getConnection();

        list($whereSql, $params) = $this->buildWhereClause($minPrice, $maxPrice, $categoriaId, $etiquetas, $visibleOnly);

        // disponibilidad por stock: detectamos columna de stock si existe
        if ($soloDisponibles) {
            $cols = $this->getTableColumns('productos');
            $stockCols = ['stock', 'cantidad', 'cantidad_stock', 'qty', 'cantidad_disponible', 'disponible'];
            $foundStockCol = null;
            foreach ($stockCols as $c) {
                if (in_array($c, $cols, true)) {
                    $foundStockCol = $c;
                    break;
                }
            }
            if ($foundStockCol) {
                // si ya hay WHERE, añadimos con AND
                $whereSql .= ($whereSql ? " AND " : " WHERE ") . "p.`{$foundStockCol}` > 0";
            }
        }

        // Orden
        $orderSql = " ORDER BY p.id DESC"; // por defecto
        switch ($orden) {
            case 'precio_asc':
                $orderSql = " ORDER BY p.precio ASC";
                break;
            case 'precio_desc':
                $orderSql = " ORDER BY p.precio DESC";
                break;
            case 'nombre_asc':
                $orderSql = " ORDER BY p.nombre ASC";
                break;
            case 'nombre_desc':
                $orderSql = " ORDER BY p.nombre DESC";
                break;
            case 'ofertas':
                // Productos con precio tachado y descuento visible, ordenados por porcentaje de descuento
                $orderSql = " ORDER BY (CASE WHEN p.precio_tachado IS NOT NULL AND p.precio_tachado > p.precio AND p.precio_tachado_visible = 1 THEN 0 ELSE 1 END), p.porcentaje_descuento DESC";
                break;
            case 'novedades':
            case 'fecha_desc':
                // Productos más recientes por fecha de creación
                $orderSql = " ORDER BY p.created_at DESC";
                break;
            case 'mas_vendidos':
            case 'ventas_desc':
                // Productos más vendidos calculando desde detalle_pedido
                $orderSql = " ORDER BY total_vendido DESC, p.id DESC";
                break;
            case 'destacados':
                // Productos destacados primero
                $orderSql = " ORDER BY p.destacado DESC, p.id DESC";
                break;
            case 'relevancia':
            default:
                // Productos destacados primero, luego por más recientes
                $orderSql = " ORDER BY p.destacado DESC, p.id DESC";
                break;
        }

        // Para ordenamiento por más vendidos, necesitamos hacer JOIN con detalle_pedido
        if ($orden === 'mas_vendidos' || $orden === 'ventas_desc') {
            $sql = "SELECT p.*, COALESCE(SUM(dp.cantidad), 0) as total_vendido 
                    FROM productos p 
                    LEFT JOIN detalle_pedido dp ON p.id = dp.producto_id"
                . $whereSql .
                " GROUP BY p.id" . $orderSql;
        } else {
            $sql = "SELECT p.* FROM productos p" . $whereSql . $orderSql;
        }

        if (is_numeric($limit) && is_numeric($offset)) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($sql);
            // binds params dinámicos
            foreach ($params as $k => $v) {
                // inferir tipo
                if (is_int($v)) $stmt->bindValue($k, $v, \PDO::PARAM_INT);
                else $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $db->prepare($sql);
            foreach ($params as $k => $v) {
                if (is_int($v)) $stmt->bindValue($k, $v, \PDO::PARAM_INT);
                else $stmt->bindValue($k, $v);
            }
            $stmt->execute();
        }

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve array con los nombres de columnas de la tabla indicada (o [] si no existe / error)
     */
    private function getTableColumns(string $table): array
    {
        try {
            $db = \Core\Database::getInstance()->getConnection();
            $stmt = $db->query("SHOW COLUMNS FROM `{$table}`");
            $cols = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return array_column($cols, 'Field');
        } catch (\Throwable $e) {
            // tabla no existe o error -> devolvemos array vacío
            return [];
        }
    }


    public function contarFiltrados(
        $minPrice = null,
        $maxPrice = null,
        $categoriaId = null,
        array $etiquetas = [],
        $visibleOnly = true
    ) {
        $db = \Core\Database::getInstance()->getConnection();

        list($whereSql, $params) = $this->buildWhereClause($minPrice, $maxPrice, $categoriaId, $etiquetas, $visibleOnly);

        $sql = "SELECT COUNT(*) AS total FROM productos p" . $whereSql;
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            if (is_int($v)) $stmt->bindValue($k, $v, \PDO::PARAM_INT);
            else $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function obtenerEstadisticasPrecios()
    {
        $sql = "SELECT 
                    MIN(precio) as precio_minimo,
                    MAX(precio) as precio_maximo,
                    AVG(precio) as precio_promedio,
                    COUNT(*) as total_productos
                FROM productos 
                WHERE visible = 1 AND precio > 0";
        $stmt = $this->db->query($sql);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'precio_minimo' => round((float)$res['precio_minimo'], 2),
            'precio_maximo' => round((float)$res['precio_maximo'], 2),
            'precio_promedio' => round((float)$res['precio_promedio'], 2),
            'total_productos' => (int)$res['total_productos']
        ];
    }

    public static function obtenerPorId($id)
    {
        $db = Database::getInstance()->getConnection();
        // ✅ CORREGIDO: Obtener también la categoría del producto
        $stmt = $db->prepare("
        SELECT p.*, 
               (SELECT pc.id_categoria 
                FROM producto_categoria pc 
                WHERE pc.id_producto = p.id 
                LIMIT 1) as categoria_id
        FROM productos p 
        WHERE p.id = ?
    ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        // --- Especificaciones: mantenemos texto y también array por líneas ---
        $rawEspecificaciones = $row['especificaciones'] ?? '';
        $row['especificaciones'] = $rawEspecificaciones; // texto para el admin
        $row['especificaciones_array'] = [];
        if (trim($rawEspecificaciones) !== '') {
            $row['especificaciones_array'] = preg_split("/\r\n|\n|\r/", trim($rawEspecificaciones));
        }

        // --- Productos relacionados: soporta JSON ["1",2] o CSV "1,2,3" o array ---
        $rowIds = $row['productos_relacionados'] ?? '';
        $ids = [];

        if (is_array($rowIds)) {
            $ids = $rowIds;
        } elseif (is_string($rowIds)) {
            $raw = trim($rowIds);
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $ids = $decoded;
                } else {
                    $ids = array_map('trim', explode(',', $raw));
                }
            }
        }

        // normaliza
        $row['productos_relacionados'] = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));

        return $row;
    }

    /**
     * Obtener varios productos por un array de IDs.
     * Devuelve productos preparados (prepararProductoParaVista) pero **sin** cargar todas las imágenes (la vista puede esperar 'imagenes').
     *
     * @param array $ids
     * @return array
     */
    public function obtenerPorIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), function ($v) {
            return $v > 0;
        }));
        if (empty($ids)) return [];

        // placeholders
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT p.* FROM productos p WHERE p.id IN ($placeholders) AND p.visible = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);

        $productos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($productos as &$p) {
            // formato de precios, flags, etc.
            $p = $this->prepararProductoParaVista($p);
            // dejamos la carga de 'imagenes' al controlador/imagen model para flexibilidad
        }
        unset($p);

        // mantener el orden según $ids
        $byId = [];
        foreach ($productos as $p) $byId[(int)$p['id']] = $p;
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) $ordered[] = $byId[$id];
        }

        return $ordered;
    }


    public static function actualizar(
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
        $especificaciones = null,
        $productos_relacionados_json = null
    ) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "
        UPDATE productos
        SET
            nombre = :nombre,
            descripcion = :descripcion,
            precio = :precio,
            precio_tachado = :precio_tachado,
            porcentaje_descuento = :porcentaje_descuento,
            precio_tachado_visible = :precio_tachado_visible,
            porcentaje_visible = :porcentaje_visible,
            stock = :stock,
            visible = :visible,
            destacado = :destacado,
            especificaciones = :especificaciones,
            productos_relacionados = :productos_relacionados
        WHERE id = :id
        ";

            $stmt = $db->prepare($sql);

            $stmt->bindValue(':nombre', $nombre, \PDO::PARAM_STR);
            $stmt->bindValue(':descripcion', $descripcion, \PDO::PARAM_STR);
            $stmt->bindValue(':precio', $precio);
            if ($precio_tachado === null || $precio_tachado === '') {
                $stmt->bindValue(':precio_tachado', null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':precio_tachado', $precio_tachado);
            }
            $stmt->bindValue(':porcentaje_descuento', (int)$porcentaje_descuento, \PDO::PARAM_INT);
            $stmt->bindValue(':precio_tachado_visible', (int)$precio_tachado_visible, \PDO::PARAM_INT);
            $stmt->bindValue(':porcentaje_visible', (int)$porcentaje_visible, \PDO::PARAM_INT);
            $stmt->bindValue(':stock', (int)$stock, \PDO::PARAM_INT);
            $stmt->bindValue(':visible', (int)$visible, \PDO::PARAM_INT);
            $stmt->bindValue(':destacado', (int)$destacado, \PDO::PARAM_INT);

            // especificaciones puede ser NULL o string
            if ($especificaciones === null || $especificaciones === '') {
                $stmt->bindValue(':especificaciones', null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':especificaciones', $especificaciones, \PDO::PARAM_STR);
            }

            // productos_relacionados: ya lo dejamos como JSON string o NULL
            if ($productos_relacionados_json === null || $productos_relacionados_json === '') {
                $stmt->bindValue(':productos_relacionados', null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':productos_relacionados', $productos_relacionados_json, \PDO::PARAM_STR);
            }

            $stmt->bindValue(':id', (int)$id, \PDO::PARAM_INT);

            return (bool)$stmt->execute();
        } catch (\PDOException $e) {
            error_log("[Producto::actualizar] Error PDO: " . $e->getMessage());
            return false;
        }
    }

    public static function eliminar($id)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE productos SET visible = 0 WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function obtenerCategoriasPorProducto($productoId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT c.nombre
            FROM categorias c
            INNER JOIN producto_categoria pc ON pc.id_categoria = c.id
            WHERE pc.id_producto = ?
        ");
        $stmt->execute([$productoId]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nombre');
    }

    public function obtenerEtiquetasPorProducto($productoId)
    {
        $stmt = $this->db->prepare("SELECT etiqueta_id FROM producto_etiqueta WHERE producto_id = ?");
        $stmt->execute([$productoId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function obtenerCategoriasConProductos()
    {
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT DISTINCT c.id, c.nombre, c.id_padre, COUNT(pc.id_producto) as productos_directos
                FROM categorias c
                INNER JOIN producto_categoria pc ON c.id = pc.id_categoria
                INNER JOIN productos p ON pc.id_producto = p.id
                WHERE p.visible = 1
                GROUP BY c.id, c.nombre, c.id_padre
                ORDER BY c.nombre ASC";

        $stmt = $db->query($sql);
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categorias as &$categoria) {
            $stmtSub = $db->prepare("
                SELECT COUNT(pc.id_producto) as productos_subcategoria
                FROM categorias sub
                INNER JOIN producto_categoria pc ON sub.id = pc.id_categoria
                INNER JOIN productos p ON pc.id_producto = p.id
                WHERE sub.id_padre = ? AND p.visible = 1
            ");
            $stmtSub->execute([$categoria['id']]);
            $resultSub = $stmtSub->fetch(PDO::FETCH_ASSOC);
            $categoria['total_productos'] = $categoria['productos_directos'] + ($resultSub['productos_subcategoria'] ?? 0);
        }

        return $categorias;
    }
    public function obtenerVisibles()
    {
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM productos WHERE visible = 1");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function buscarPorNombre(string $q): array
    {
        $db = Database::getInstance()->getConnection();

        $sql = "
        SELECT p.*,
               (SELECT ip.nombre_imagen
                FROM imagenes_producto ip
                WHERE ip.producto_id = p.id
                ORDER BY ip.id ASC
                LIMIT 1) AS imagen
        FROM productos p
        WHERE (p.nombre LIKE :q OR p.descripcion LIKE :q)
          AND p.visible = 1
        LIMIT 100
    ";

        $stmt = $db->prepare($sql);
        $like = '%' . $q . '%';
        $stmt->execute([':q' => $like]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    /**
     * Calcula el precio final basado en precio_tachado y porcentaje_descuento.
     * Si el precio final (precio) no coincide, puede usarse para validaciones o ajustes.
     * 
     * @param float|null $precioTachado
     * @param float|null $porcentajeDescuento
     * @return float|null Precio final calculado o null si no hay datos suficientes.
     */
    public function calcularPrecioFinal(?float $precioTachado, ?float $porcentajeDescuento): ?float
    {
        if ($precioTachado !== null && $porcentajeDescuento !== null) {
            $descuento = $precioTachado * ($porcentajeDescuento / 100);
            return round($precioTachado - $descuento, 2);
        }
        return null;
    }

    /**
     * Formatea un número como precio con 2 decimales y punto decimal.
     * 
     * @param mixed $precio
     * @return string
     */
    public function formatearPrecio($precio): string
    {
        return number_format((float)$precio, 2, '.', '');
    }

    /**
     * Obtiene el texto para el badge del porcentaje, por ejemplo "-40%".
     * Retorna cadena vacía si no hay descuento válido.
     * 
     * @param float|null $porcentajeDescuento
     * @return string
     */
    public function obtenerTextoBadge(?float $porcentajeDescuento): string
    {
        if ($porcentajeDescuento !== null && $porcentajeDescuento > 0) {
            return '-' . $this->formatearPrecio($porcentajeDescuento) . '%';
        }
        return '';
    }

    /**
     * Completa los datos de un producto con cálculo y formateo de precios y porcentaje.
     * Modifica el array $producto directamente.
     * 
     * @param array $producto Producto con claves 'precio', 'precio_tachado', 'porcentaje_descuento'
     * @return array Producto modificado con datos formateados y precio calculado.
     */
    public function prepararProductoParaVista(array $producto): array
    {
        $producto['precio'] = $this->formatearPrecio($producto['precio'] ?? 0);
        $producto['precio_tachado'] = isset($producto['precio_tachado']) ? $this->formatearPrecio($producto['precio_tachado']) : null;
        $producto['porcentaje_descuento'] = isset($producto['porcentaje_descuento']) ? (float)$producto['porcentaje_descuento'] : 0;

        $producto['precio_tachado_visible'] = isset($producto['precio_tachado_visible']) ? (bool)$producto['precio_tachado_visible'] : true;
        $producto['porcentaje_visible'] = isset($producto['porcentaje_visible']) ? (bool)$producto['porcentaje_visible'] : true;

        if (empty($producto['precio_tachado']) || (float)$producto['precio_tachado'] <= (float)$producto['precio']) {
            $producto['precio_tachado'] = null;
            $producto['porcentaje_descuento'] = 0;
            $producto['precio_tachado_visible'] = false;
            $producto['porcentaje_visible'] = false;
        }

        $producto['texto_badge'] = ($producto['porcentaje_visible'] && $producto['porcentaje_descuento'] > 0)
            ? '-' . $this->formatearPrecio($producto['porcentaje_descuento']) . '%'
            : '';

        // 🚀 Aquí obtenemos las imágenes de la tabla
        $imagenes = ImagenProducto::obtenerPorProducto($producto['id']);
        $producto['imagenes'] = array_map(function ($img) {
            return "/uploads/" . $img['nombre_imagen'];
        }, $imagenes);

        // Si no hay imágenes, ponemos una por defecto
        if (empty($producto['imagenes'])) {
            $producto['imagenes'][] = url('uploads/default-product.png');
        }

        return $producto;
    }



    private function buildWhereClause($minPrice, $maxPrice, $categoriaId, array $etiquetas, $visibleOnly)
    {
        $where = [];
        $params = [];

        if ($visibleOnly) {
            $where[] = "p.visible = 1";
        }

        if ($minPrice !== null && $minPrice !== '') {
            $where[] = "p.precio >= :minPrice";
            $params[':minPrice'] = $minPrice;
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $where[] = "p.precio <= :maxPrice";
            $params[':maxPrice'] = $maxPrice;
        }

        if (!empty($categoriaId)) {
            $subcategorias = $this->obtenerSubcategorias($categoriaId);
            $categoriasParaFiltrar = array_merge([$categoriaId], $subcategorias);

            $placeholders = [];
            foreach ($categoriasParaFiltrar as $idx => $catId) {
                $ph = ':cat' . $idx;
                $placeholders[] = $ph;
                $params[$ph] = (int)$catId;
            }

            $where[] = "EXISTS (SELECT 1 FROM producto_categoria pc WHERE pc.id_producto = p.id AND pc.id_categoria IN (" . implode(',', $placeholders) . "))";
        }

        $whereSql = count($where) ? " WHERE " . implode(" AND ", $where) : "";
        return [$whereSql, $params];
    }

    // Agregar este método al modelo Producto
    private function obtenerSubcategorias($categoriaPadreId)
    {
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM categorias WHERE id_padre = :id_padre");
        $stmt->execute([':id_padre' => $categoriaPadreId]);
        $resultados = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return $resultados ?: [];
    }
    /**
     * ==================================================================
     *  NUEVO MÉTODO PARA OBTENER PRODUCTOS DESTACADOS (VERSIÓN CORREGIDA)
     * ==================================================================
     * Este método ahora une la tabla de imágenes directamente en la consulta
     * para obtener la primera imagen de forma más eficiente y segura.
     *
     * @param int $limit El número máximo de productos destacados a devolver.
     * @return array Lista de productos destacados.
     */
    public function obtenerDestacados(int $limit = 12): array
    {
        // Consulta SQL mejorada:
        // - Usa un LEFT JOIN para unir la tabla de imágenes.
        // - Usa una subconsulta para obtener SOLO la primera imagen (la de menor ID) para cada producto.
        // - Agrupa por ID de producto para evitar duplicados si un producto tiene varias imágenes.
        $sql = "
        SELECT 
            p.*, 
            (SELECT ip.nombre_imagen 
             FROM imagenes_producto ip 
             WHERE ip.producto_id = p.id 
             ORDER BY ip.id ASC 
             LIMIT 1) AS imagen_principal
        FROM productos p
        WHERE p.destacado = 1 AND p.visible = 1
        GROUP BY p.id
        ORDER BY p.id DESC
        LIMIT :limit
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ahora, en lugar de llamar a otro modelo, la imagen ya viene en la consulta.
        // Solo necesitamos preparar los datos para la vista.
        foreach ($productos as &$producto) {
            // Asignamos la imagen principal a la clave 'imagen' que espera tu parcial _products_grid.php
            $producto['imagen'] = $producto['imagen_principal'] ?? null;

            // Reutilizamos tu método existente para formatear precios, etc.
            $producto = $this->prepararProductoParaVista($producto);
        }

        return $productos;
    }
}
