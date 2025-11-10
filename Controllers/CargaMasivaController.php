<?php

namespace Controllers;

use PDOException;

class CargaMasivaController
{
        public function index()
    {
        require __DIR__ . '/../views/carga/index.php';
    }
    public function descargarPlantilla()
    {
        $ruta = __DIR__ . '/../public/csv/plantilla_productos.csv';

        if (!file_exists($ruta)) {
            http_response_code(404);
            echo "Archivo no encontrado.";
            return;
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="plantilla_productos.csv"');
        readfile($ruta);
    }
    public function procesarCSV()
    {
    
        
        if (!isset($_FILES['archivo_csv']) || $_FILES['archivo_csv']['error'] !== UPLOAD_ERR_OK) {
            echo "❌ Error al subir el archivo.";
            return;
        }

        $rutaTemporal = $_FILES['archivo_csv']['tmp_name'];
        $handle = fopen($rutaTemporal, 'r');
        if (!$handle) {
            echo "❌ No se pudo leer el archivo.";
            return;
        }

        $db = \Core\Database::getInstance()->getConnection();
        $fila = 0;
        $reporteErrores  = [];
        $productosPendientes = [];
        while (($line = fgets($handle)) !== false) {
            $fila++;

            $datos = str_getcsv($line, ";");
            if ($fila === 1) continue; // saltar encabezado

            if (count($datos) < 11) continue;

            list($sku,$nombre, $descripcion, $precio, $stock, $visible, $categorias, $etiquetas, $talla, $color, $stockVariante, $imagenesNombres) = $datos;


            //validacion por fila del reporte de errores

            $errores  = [];

            if (!\Core\Helpers\Validator::isRequired($nombre)) {
                $errores[] = "Nombre vacío";
            }

            if (!\Core\Helpers\Validator::isRequired($descripcion)) {
                $errores[] = "Descripción vacía";
            }

            if (!\Core\Helpers\Validator::isNumeric($precio)) {
                $errores[] = "Precio inválido";
            }

            if (!ctype_digit($stock)) {
                $errores[] = "Stock debe ser entero";
            }

            if (!in_array($visible, ['0', '1'])) {
                $errores[] = "Visible debe ser 0 o 1";
            }

            // Validar stockVariante
            if (!ctype_digit($stockVariante)) {
                $errores[] = "Stock de variante inválido";
            }

            // Guardar los errores si existen
            if (!empty($errores)) {
                foreach ($errores as $error) {
                    $reporteErrores[] = [
                        'fila' => $fila,
                        'error' => $error
                    ];
                }
                continue; // no se inserta la fila
            }

            // 🔎 Buscar si existe producto por SKU
            $stmt = $db->prepare("SELECT id FROM productos WHERE sku = ?");
            $stmt->execute([$sku]);
            $productoExistente = $stmt->fetch();

            if ($productoExistente) {
                // Guardar para mostrar luego la confirmación
                $productosPendientes[] = [
                    'fila' => $fila,
                    'sku' => $sku,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio' => $precio,
                    'stock' => $stock,
                    'visible' => $visible,
                    'categorias' => $categorias,
                    'etiquetas' => $etiquetas,
                    'talla' => $talla,
                    'color' => $color,
                    'stockVariante' => $stockVariante,
                    'imagenesNombres' => $imagenesNombres
                ];
                continue; // 🔁 No actualices aún, espera confirmación
            }

            try {
            // Insertar nuevo producto con SKU
        $stmt = $db->prepare("INSERT INTO productos (sku, nombre, descripcion, precio, stock, visible)
                            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sku, $nombre, $descripcion, $precio, $stock, $visible]);
        $producto_id = $db->lastInsertId();

    // ✅ Insertar variante
    if (!empty($talla) && !empty($color) && ctype_digit($stockVariante)) {
        $stmtVar = $db->prepare("INSERT INTO variantes_producto (producto_id, talla, color, stock)
                                 VALUES (?, ?, ?, ?)");
        $stmtVar->execute([$producto_id, $talla, $color, $stockVariante]);
    }

    // ✅ Categorías
    $categoriasArr = explode(',', $categorias);
    foreach ($categoriasArr as $catNombre) {
        $catNombre = trim($catNombre);
        if (!$catNombre) continue;

        $stmtCat = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
        $stmtCat->execute([$catNombre]);
        $cat = $stmtCat->fetch();

        $cat_id = $cat ? $cat['id'] : null;
        if (!$cat_id) {
            $stmtInsertCat = $db->prepare("INSERT INTO categorias (nombre) VALUES (?)");
            $stmtInsertCat->execute([$catNombre]);
            $cat_id = $db->lastInsertId();
        }

        $stmtRelCat = $db->prepare("INSERT INTO producto_categoria (id_producto, id_categoria) VALUES (?, ?)");
        $stmtRelCat->execute([$producto_id, $cat_id]);
    }

    // ✅ Etiquetas
    $etiquetasArr = explode(',', $etiquetas);
    foreach ($etiquetasArr as $etNombre) {
        $etNombre = trim($etNombre);
        if (!$etNombre) continue;

        $stmtEt = $db->prepare("SELECT id FROM etiquetas WHERE nombre = ?");
        $stmtEt->execute([$etNombre]);
        $et = $stmtEt->fetch();

        $et_id = $et ? $et['id'] : null;
        if (!$et_id) {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $etNombre), '-'));
            $stmtInsertEt = $db->prepare("INSERT INTO etiquetas (nombre, slug) VALUES (?, ?)");
            $stmtInsertEt->execute([$etNombre, $slug]);
            $et_id = $db->lastInsertId();
        }

        $stmtRelEt = $db->prepare("INSERT INTO producto_etiqueta (producto_id, etiqueta_id) VALUES (?, ?)");
        $stmtRelEt->execute([$producto_id, $et_id]);
    }

    // ✅ Imágenes
    if (!empty($imagenesNombres)) {
        $imagenesArr = array_map('trim', explode(',', $imagenesNombres));
        foreach ($imagenesArr as $nombreImg) {
            $rutaImg = __DIR__ . '/../public/uploads/' . $nombreImg;
            if (file_exists($rutaImg)) {
                \Models\ImagenProducto::guardar($producto_id, $nombreImg);
            } else {
                $reporteErrores[] = [
                    'fila' => $fila,
                    'error' => "Imagen '$nombreImg' no encontrada en /public/uploads/"
                ];
            }
        }
    }

} catch (\PDOException $e) {
    $reporteErrores[] = [
        'fila' => $fila,
        'error' => "❌ Error exacto en fila $fila: " . $e->getMessage()
    ];
    continue;
}

                
        }

        fclose($handle);

        //estructura base del reporte de errores
        if (!empty($reporteErrores)) {
            require __DIR__ . '/../views/carga/reporte.php';
            return;
        }
        //estructura base del reporte de errores; mostrar confirmacion al final del procesamiento
        if (!empty($productosPendientes)) {
            $_SESSION['productos_pendientes'] = $productosPendientes;
            require __DIR__ . '/../views/carga/confirmar_actualizacion.php';
            return;
        }


        // Redirigir al listado de productos
        header("Location: /producto/index");
        exit;
    }
    public function confirmarActualizacion()
{
    session_start(); // Asegúrate que esté por si se accede directo

    if (!isset($_POST['sobrescribir'])) {
        echo "No se seleccionó ningún producto para sobrescribir.";
        return;
    }

    $db = \Core\Database::getInstance()->getConnection();
    $productosPendientes = $_SESSION['productos_pendientes'] ?? [];
    $skusAActualizar = $_POST['sobrescribir'];

    foreach ($productosPendientes as $prod) {
        if (!in_array($prod['sku'], $skusAActualizar)) continue;

        $sku = $prod['sku'];

        // Buscar ID del producto existente
        $stmt = $db->prepare("SELECT id FROM productos WHERE sku = ?");
        $stmt->execute([$sku]);
        $productoExistente = $stmt->fetch();

        if (!$productoExistente) continue; // si no existe, ignorar

        $producto_id = $productoExistente['id'];

        // Actualizar producto
        $stmt = $db->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, visible = ? WHERE id = ?");
        $stmt->execute([
            $prod['nombre'],
            $prod['descripcion'],
            $prod['precio'],
            $prod['stock'],
            $prod['visible'],
            $producto_id
        ]);

        // Eliminar relaciones antiguas
        $db->prepare("DELETE FROM variantes_producto WHERE producto_id = ?")->execute([$producto_id]);
        $db->prepare("DELETE FROM producto_categoria WHERE id_producto = ?")->execute([$producto_id]);
        $db->prepare("DELETE FROM producto_etiqueta WHERE producto_id = ?")->execute([$producto_id]);
        $db->prepare("DELETE FROM imagenes_producto WHERE producto_id = ?")->execute([$producto_id]);

        // Insertar nueva variante
        if (!empty($prod['talla']) && !empty($prod['color']) && ctype_digit($prod['stockVariante'])) {
            $stmtVar = $db->prepare("INSERT INTO variantes_producto (producto_id, talla, color, stock) VALUES (?, ?, ?, ?)");
            $stmtVar->execute([$producto_id, $prod['talla'], $prod['color'], $prod['stockVariante']]);
        }

        // Insertar nuevas categorías
        $categorias = explode(',', $prod['categorias']);
        foreach ($categorias as $catNombre) {
            $catNombre = trim($catNombre);
            if (!$catNombre) continue;

            $stmt = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
            $stmt->execute([$catNombre]);
            $cat = $stmt->fetch();
            $cat_id = $cat ? $cat['id'] : null;

            if (!$cat_id) {
                $stmt = $db->prepare("INSERT INTO categorias (nombre) VALUES (?)");
                $stmt->execute([$catNombre]);
                $cat_id = $db->lastInsertId();
            }

            $stmt = $db->prepare("INSERT INTO producto_categoria (id_producto, id_categoria) VALUES (?, ?)");
            $stmt->execute([$producto_id, $cat_id]);
        }

        // Insertar nuevas etiquetas
        $etiquetas = explode(',', $prod['etiquetas']);
        foreach ($etiquetas as $etiquetaNombre) {
            $etiquetaNombre = trim($etiquetaNombre);
            if (!$etiquetaNombre) continue;

            $stmt = $db->prepare("SELECT id FROM etiquetas WHERE nombre = ?");
            $stmt->execute([$etiquetaNombre]);
            $et = $stmt->fetch();

            $et_id = $et ? $et['id'] : null;

            if (!$et_id) {
                $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $etiquetaNombre), '-'));
                $stmt = $db->prepare("INSERT INTO etiquetas (nombre, slug) VALUES (?, ?)");
                $stmt->execute([$etiquetaNombre, $slug]);
                $et_id = $db->lastInsertId();
            }

            $stmt = $db->prepare("INSERT INTO producto_etiqueta (producto_id, etiqueta_id) VALUES (?, ?)");
            $stmt->execute([$producto_id, $et_id]);
        }

        // Insertar imágenes
        $imagenesArr = explode(',', $prod['imagenesNombres']);
        foreach ($imagenesArr as $imgNombre) {
            $imgNombre = trim($imgNombre);
            $ruta = __DIR__ . '/../public/uploads/' . $imgNombre;
            if (file_exists($ruta)) {
                \Models\ImagenProducto::guardar($producto_id, $imgNombre);
            }
        }
    }

    // Limpiar sesión
    unset($_SESSION['productos_pendientes']);

    // Redirigir al listado
    header("Location: /producto/index");
    exit;
}

    // ===== GESTIÓN MASIVA DE IMÁGENES POR EXCEL =====
    
    public function gestionImagenes()
    {
        require __DIR__ . '/../views/carga/gestion_imagenes.php';
    }

    public function generarExcelImagenes()
    {
        $db = \Core\Database::getInstance()->getConnection();
        
        // Obtener productos 
        $query = "
            SELECT 
                p.id,
                p.nombre,
                p.sku,
                p.descripcion,
                COUNT(ip.id) as total_imagenes
            FROM productos p
            LEFT JOIN imagenes_producto ip ON p.id = ip.producto_id
            GROUP BY p.id, p.nombre, p.sku, p.descripcion
            ORDER BY p.nombre ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $productos = $stmt->fetchAll();

        // Crear nombre de archivo único
        $fechaHora = date('Y-m-d_H-i-s');
        $nombreArchivo = "productos_para_imagenes_{$fechaHora}.csv";
        $rutaArchivo = __DIR__ . '/../temp/' . $nombreArchivo;

        // Crear directorio temp si no existe
        if (!is_dir(__DIR__ . '/../temp/')) {
            mkdir(__DIR__ . '/../temp/', 0755, true);
        }

        // Crear archivo CSV con codificación UTF-8
        $handle = fopen($rutaArchivo, 'w');
        
        // Agregar BOM UTF-8 para compatibilidad con Excel
        fwrite($handle, "\xEF\xBB\xBF");
        
        // Escribir encabezados
        $headers = [
            'ID_PRODUCTO',
            'NOMBRE_PRODUCTO', 
            'SKU',
            'IMAGENES_ACTUALES',
            'IMAGEN_1',
            'IMAGEN_2', 
            'IMAGEN_3',
            'IMAGEN_4',
            'IMAGEN_5'
        ];
        fputcsv($handle, $headers, ';');
        
        // Escribir datos
        foreach ($productos as $producto) {
            $fila = [
                $producto['id'],
                $producto['nombre'],
                $producto['sku'] ?: 'SIN_SKU',
                $producto['total_imagenes'] . ' imagen(es)',
                '', // IMAGEN_1
                '', // IMAGEN_2
                '', // IMAGEN_3
                '', // IMAGEN_4
                ''  // IMAGEN_5
            ];
            fputcsv($handle, $fila, ';');
        }
        
        fclose($handle);

        // Descargar archivo con headers correctos para UTF-8
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');
        
        readfile($rutaArchivo);
        unlink($rutaArchivo); // Eliminar archivo temporal
        exit;
    }

    public function procesarExcelImagenes()
    {
        if (!isset($_FILES['excel_imagenes']) || !isset($_FILES['archivo_imagenes'])) {
            $_SESSION['flash_error'] = '❌ Debe subir tanto el Excel como el archivo de imágenes.';
            header('Location: ' . url('cargaMasiva/gestionImagenes'));
            return;
        }

        try {
            // Procesar CSV con codificación UTF-8
            $contenido = file_get_contents($_FILES['excel_imagenes']['tmp_name']);
            
            // Remover BOM si existe
            $contenido = str_replace("\xEF\xBB\xBF", '', $contenido);
            
            // Crear un archivo temporal con el contenido limpio
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_temp');
            file_put_contents($tempFile, $contenido);
            
            $handle = fopen($tempFile, 'r');
            if (!$handle) {
                throw new \Exception('No se pudo leer el archivo CSV');
            }
            
            $datosImagenes = [];
            $fila = 0;
            
            while (($datos = fgetcsv($handle, 0, ';')) !== false) {
                $fila++;
                if ($fila === 1) continue; // Saltar encabezados
                
                if (count($datos) < 9) continue;
                
                $productoId = $datos[0];
                $imagenes = [];
                
                // Leer columnas IMAGEN_1 a IMAGEN_5 (índices 4-8)
                for ($i = 4; $i <= 8; $i++) {
                    $nombreImagen = trim($datos[$i] ?? '');
                    if (!empty($nombreImagen)) {
                        $imagenes[] = $nombreImagen;
                    }
                }
                
                if (!empty($imagenes)) {
                    $datosImagenes[$productoId] = $imagenes;
                }
            }
            
            fclose($handle);
            unlink($tempFile); // Eliminar archivo temporal

            // Procesar archivo de imágenes (ZIP)
            $this->extraerYProcesarImagenes($_FILES['archivo_imagenes'], $datosImagenes);
            
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = '❌ Error al procesar archivos: ' . $e->getMessage();
            header('Location: ' . url('cargaMasiva/gestionImagenes'));
        }
    }

    private function extraerYProcesarImagenes($archivoComprimido, $datosImagenes)
    {
        $tempDir = __DIR__ . '/../temp/imagenes_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        $extension = strtolower(pathinfo($archivoComprimido['name'], PATHINFO_EXTENSION));
        
        try {
            // Extraer archivo
            if ($extension === 'zip') {
                $zip = new \ZipArchive;
                if ($zip->open($archivoComprimido['tmp_name']) === TRUE) {
                    $zip->extractTo($tempDir);
                    $zip->close();
                } else {
                    throw new \Exception('No se pudo abrir el archivo ZIP');
                }
            } else {
                throw new \Exception('Solo se soportan archivos ZIP por ahora.');
            }

            // Procesar imágenes extraídas
            $reporte = [];
            $imagenesEnlazadas = 0;
            $imagenesNoEncontradas = 0;
            
            $uploadDir = __DIR__ . '/../public/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            foreach ($datosImagenes as $productoId => $imagenesRequeridas) {
                foreach ($imagenesRequeridas as $nombreImagen) {
                    $archivoEncontrado = $this->buscarArchivoRecursivo($tempDir, $nombreImagen);
                    
                    if ($archivoEncontrado) {
                        // Validar que es una imagen
                        $tipoArchivo = mime_content_type($archivoEncontrado);
                        if (in_array($tipoArchivo, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
                            
                            // Generar nombre único
                            $extension = pathinfo($nombreImagen, PATHINFO_EXTENSION);
                            $nombreFinal = uniqid('img_') . '_' . $productoId . '_' . time() . '.' . $extension;
                            
                            if (copy($archivoEncontrado, $uploadDir . $nombreFinal)) {
                                // Guardar en BD
                                \Models\ImagenProducto::guardar($productoId, $nombreFinal);
                                
                                $reporte[] = [
                                    'producto_id' => $productoId,
                                    'imagen_original' => $nombreImagen,
                                    'imagen_final' => $nombreFinal,
                                    'estado' => 'success'
                                ];
                                $imagenesEnlazadas++;
                            }
                        } else {
                            $reporte[] = [
                                'producto_id' => $productoId,
                                'imagen_original' => $nombreImagen,
                                'estado' => 'error',
                                'mensaje' => 'Formato no válido'
                            ];
                        }
                    } else {
                        $reporte[] = [
                            'producto_id' => $productoId,
                            'imagen_original' => $nombreImagen,
                            'estado' => 'not_found',
                            'mensaje' => 'Archivo no encontrado en ZIP'
                        ];
                        $imagenesNoEncontradas++;
                    }
                }
            }

            // Limpiar directorio temporal
            $this->eliminarDirectorioRecursivo($tempDir);

            // Guardar reporte en sesión
            $_SESSION['reporte_imagenes'] = $reporte;
            $_SESSION['estadisticas_imagenes'] = [
                'total_procesadas' => count($reporte),
                'enlazadas' => $imagenesEnlazadas,
                'no_encontradas' => $imagenesNoEncontradas
            ];

            require __DIR__ . '/../views/carga/reporte_imagenes_excel.php';

        } catch (\Exception $e) {
            $this->eliminarDirectorioRecursivo($tempDir);
            throw $e;
        }
    }

    private function buscarArchivoRecursivo($directorio, $nombreArchivo)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directorio));
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $nombreArchivo) {
                return $file->getPathname();
            }
        }
        
        return false;
    }

    private function eliminarDirectorioRecursivo($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->eliminarDirectorioRecursivo($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }


}