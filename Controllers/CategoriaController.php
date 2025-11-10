<?php

namespace Controllers;

use Models\Categoria;

class CategoriaController
{
    // Límite máximo de subida (en bytes)
    private $maxFileSize = 2 * 1024 * 1024; // 2 MB

    // Tipos MIME permitidos
    private $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    public function index()
    {
        require_once __DIR__ . '/../Core/Helpers/urlHelper.php';
        $categorias = Categoria::obtenerTodas();

        $meta_title = "Categorías de productos | Tienda Tecnovedades";
        $meta_description = "Explora nuestras categorías y encuentra productos tecnológicos para cada necesidad.";
        $meta_image = url('images/default-share.png');
        $canonical = url('categoria/index');
        require_once __DIR__ . '/../views/categoria/index.php';
    }

    // Mostrar formulario de creación (NO procesa POST aquí)
    public function crear()
    {
        require_once __DIR__ . '/../Core/Helpers/urlHelper.php';
        $errores = [];
        $nombre = '';
        $categorias = Categoria::obtenerTodas(); // para select padre si aplica
        require_once __DIR__ . '/../views/categoria/crear.php';
    }

    // Procesa creación (POST)
    public function guardar()
    {
        require_once __DIR__ . '/../Core/Helpers/urlHelper.php';
        $errores = [];
        $nombre = trim($_POST['nombre'] ?? '');
        $id_padre = $_POST['id_padre'] ?? null;
        if ($id_padre === '') $id_padre = null;

        // Validaciones simples
        if (!\Core\Helpers\Validator::isRequired($nombre)) {
            $errores[] = "El nombre de la categoría es obligatorio.";
        }

        // Manejo de imagen (opcional)
        $imagenFilename = null;
        if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $imagenFilename = $this->handleImageUpload($_FILES['imagen']);
            } catch (\Exception $e) {
                $errores[] = $e->getMessage();
            }
        }

        if (empty($errores)) {
            // Nota: ajustar la firma de Categoria::crear para aceptar $imagenFilename (puede ser null)
            Categoria::crear($nombre, $id_padre, $imagenFilename);
            header('Location: ' . url('categoria'));
            exit;
        }

        // Si hay errores, volver al formulario mostrando errores
        $categorias = Categoria::obtenerTodas();
        require_once __DIR__ . '/../views/categoria/crear.php';
    }

    public function editar($id)
    {
        require_once __DIR__ . '/../Core/Helpers/urlHelper.php';
        $categoria = Categoria::obtenerPorId($id);
        $categorias = Categoria::obtenerTodas();

        if (!$categoria) {
            echo "Categoría no encontrada.";
            return;
        }

        require_once __DIR__ . '/../views/categoria/editar.php';
    }

    // Procesa actualización (POST)
    public function actualizar()
    {
        require_once __DIR__ . '/../Core/Helpers/urlHelper.php';
        $id = $_POST['id'] ?? null;
        $nombre = trim($_POST['nombre'] ?? '');
        $id_padre = $_POST['id_padre'] ?? null;
        if ($id_padre === '') $id_padre = null;

        $errores = [];
        if (!\Core\Helpers\Validator::isRequired($nombre)) {
            $errores[] = "El nombre de la categoría es obligatorio.";
        }

        // Obtener categoría actual (para imagen vieja)
        $categoria = Categoria::obtenerPorId($id);
        if (!$categoria) {
            $errores[] = "Categoría no encontrada.";
        }

        $nuevaImagen = null;
        if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $nuevaImagen = $this->handleImageUpload($_FILES['imagen']);
            } catch (\Exception $e) {
                $errores[] = $e->getMessage();
            }
        }

        if (empty($errores)) {
            // Nota: ajustar la firma de Categoria::actualizar para aceptar imagen opcional
            Categoria::actualizar($id, $nombre, $id_padre, $nuevaImagen);

            // Si se subió nueva imagen, eliminar la vieja del disco
            if ($nuevaImagen && !empty($categoria['imagen'])) {
                $this->deleteImageFile($categoria['imagen']);
            }

            header('Location: ' . url('categoria'));
            exit;
        }

        // Si hay errores, recargar vista editar con mensajes
        $categorias = Categoria::obtenerTodas();
        require_once __DIR__ . '/../views/categoria/editar.php';
    }

    public function eliminar($id)
    {
        require_once __DIR__ . '/../Core/Helpers/urlHelper.php';
        if (Categoria::tieneHijos($id) || Categoria::tieneProductos($id)) {
            echo "<p style='color:red;'>No se puede eliminar esta categoría porque tiene subcategorías o productos asignados.</p>";
            echo "<p><a href='" . url('categoria') . "'>← Volver al listado</a></p>";
            return;
        }

        // Obtener nombre archivo antes de eliminar DB
        $categoria = Categoria::obtenerPorId($id);
        $imagen = $categoria['imagen'] ?? null;

        Categoria::eliminar($id);

        // Intentar borrar archivo (si existía)
        if ($imagen) {
            $this->deleteImageFile($imagen);
        }

        header('Location: ' . url('categoria'));
        exit;
    }

    /**
     * Valida y guarda la imagen subida. Devuelve el nombre de archivo guardado (string) o lanza Exception.
     *
     * @param array $file elemento de $_FILES['imagen']
     * @return string
     * @throws \Exception
     */
    private function handleImageUpload(array $file): string
    {
        // Errores básicos de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Error al subir la imagen (code: {$file['error']}).");
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new \Exception("El archivo supera el tamaño máximo permitido (" . ($this->maxFileSize / 1024 / 1024) . " MB).");
        }

        $tmpName = $file['tmp_name'];
        if (!is_uploaded_file($tmpName)) {
            throw new \Exception("Archivo inválido.");
        }

        // Validar que realmente sea imagen
        $imgInfo = @getimagesize($tmpName);
        if ($imgInfo === false) {
            throw new \Exception("El archivo no parece ser una imagen válida.");
        }

        $mime = $imgInfo['mime'] ?? null;
        if (!$mime || !isset($this->allowedMimes[$mime])) {
            throw new \Exception("Tipo de imagen no permitido. (permitidos: jpg, png, webp, gif)");
        }

        $ext = $this->allowedMimes[$mime];
        // Generar nombre único
        $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

        // Ruta en disco (desde controllers/)
        $uploadDir = __DIR__ . '/../public/uploads/categorias/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                throw new \Exception("No se pudo crear directorio de subida.");
            }
        }

        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            throw new \Exception("No se pudo guardar la imagen en el servidor.");
        }

        // Opcional: ajustar permisos
        @chmod($destination, 0644);

        return $filename;
    }

    /**
     * Borra el archivo de imagen si existe en uploads/categorias.
     *
     * @param string $filename
     * @return void
     */
    private function deleteImageFile(string $filename): void
    {
        $filePath = __DIR__ . '/../public/uploads/categorias/' . $filename;
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }
    public function buscarPorNombre()
    {
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../Core/Helpers/urlHelper.php';

        $termino = trim($_GET['q'] ?? '');

        if ($termino === '') {
            echo json_encode([]);
            exit;
        }

        try {
            $categorias = \Models\Categoria::buscarPorNombre($termino);
            echo json_encode($categorias);
        } catch (\Throwable $e) {
            echo json_encode([]);
        }

        exit;
    }

}
