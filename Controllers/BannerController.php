<?php

namespace Controllers;

require_once __DIR__ . 
'/BaseController.php';

class BannerController extends BaseController
{
    private string $uploadDirFs;
    private string $uploadDirUrl;

    public function __construct()
    {
        $root = dirname(__DIR__);
        $this->uploadDirFs  = $root . '/public/uploads/banners/';
        $this->uploadDirUrl = 'uploads/banners/';
        if (!is_dir($this->uploadDirFs)) {
            @mkdir($this->uploadDirFs, 0777, true);
        }
    }

    private function conn() { return \Core\Database::getInstance()->getConnection(); }
    private function hasAccess(): bool { return class_exists('\Core\Helpers\SessionHelper') && (\Core\Helpers\SessionHelper::hasPermission('banners') || \Core\Helpers\SessionHelper::hasPermission('promociones')); }
    private function deny() { http_response_code(403); $view403 = dirname(__DIR__) . '/views/errors/403.php'; if (file_exists($view403)) require $view403; else echo '403 - Acceso denegado'; }

    public function index()
    {
        if (!$this->hasAccess()) return $this->deny();

        try {
            // Obtener ambos tipos de banners
            $banners_principales = \Models\Banner::obtenerTodosPorTipo('principal');
            $banners_secundarios_izquierda = \Models\Banner::obtenerTodosPorTipo('secundario_izquierda');
            $banners_secundarios_derecha = \Models\Banner::obtenerTodosPorTipo('secundario_derecha');
        } catch (\Throwable $e) {
            error_log('BannerController::index error: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'No se pudieron cargar los banners.';
            $banners_principales = [];
            $banners_secundarios_izquierda = [];
            $banners_secundarios_derecha = [];
        }

        $uploadDirUrl = $this->uploadDirUrl;
        require_once dirname(__DIR__) . '/views/banner/index.php';
    }

    public function guardar()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->hasAccess()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Acceso denegado']);
            return;
        }

        if (empty($_FILES['imagen']['name']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Subida de imagen no válida.']);
            return;
        }

        $tmpPath  = $_FILES['imagen']['tmp_name'];
        $origName = $_FILES['imagen']['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $permitidas, true)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Formato no permitido.']);
            return;
        }

        $safeName = uniqid('banner_', true) . '.' . $ext;
        $destFs   = $this->uploadDirFs . $safeName;

        if (!@move_uploaded_file($tmpPath, $destFs)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'No se pudo guardar el archivo.']);
            return;
        }

        $tipo   = isset($_POST['tipo']) && in_array($_POST['tipo'], ['principal', 'secundario_izquierda', 'secundario_derecha']) ? $_POST['tipo'] : 'principal';
        $orden  = isset($_POST['orden']) ? (int)$_POST['orden'] : 0;
        $activo = isset($_POST['activo']) ? 1 : 0;
        $enlace = isset($_POST['enlace']) && trim($_POST['enlace']) !== '' ? trim($_POST['enlace']) : null;

        try {
            $id = \Models\Banner::crear($safeName, $tipo, $orden, $activo, $enlace);
            if ($id) {
                echo json_encode(['ok' => true, 'data' => ['id' => $id, 'nombre_imagen' => $safeName, 'activo' => $activo, 'tipo' => $tipo, 'enlace' => $enlace]]);
            } else {
                if (file_exists($destFs)) @unlink($destFs);
                http_response_code(500);
                echo json_encode(['ok' => false, 'message' => 'Error al guardar en la base de datos.']);
            }
        } catch (\Throwable $e) {
            if (file_exists($destFs)) @unlink($destFs);
            error_log('BannerController::guardar (AJAX) error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Error interno del servidor.']);
        }
    }
    
    public function toggle()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->hasAccess()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Acceso denegado']);
            return;
        }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 0;

        try {
            // Usar el modelo Banner para actualizar el estado
            $conn = $this->conn();
            if ($conn instanceof \PDO) {
                $stmt = $conn->prepare("UPDATE banners SET activo = :activo WHERE id = :id");
                $ok = $stmt->execute([':activo' => $activo, ':id' => $id]);
            } elseif ($conn instanceof \mysqli) {
                $ok = (bool)$conn->query("UPDATE banners SET activo = $activo WHERE id = $id");
            }

            if ($ok) {
                echo json_encode(['ok' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['ok' => false, 'message' => 'Error al actualizar estado en la base de datos.']);
            }
        } catch (\Throwable $e) {
            error_log('BannerController::toggle error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Error interno del servidor al cambiar estado.']);
        }
    }

    public function eliminar()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->hasAccess()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Acceso denegado']);
            return;
        }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        try {
            $oldName = \Models\Banner::eliminar($id);
            if ($oldName) {
                if (file_exists($this->uploadDirFs . $oldName)) {
                    @unlink($this->uploadDirFs . $oldName);
                }
                echo json_encode(['ok' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['ok' => false, 'message' => 'Error al eliminar el banner de la base de datos o no se encontró.']);
            }
        } catch (\Throwable $e) {
            error_log('BannerController::eliminar error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Error interno del servidor al eliminar.']);
        }
    }

    public function ordenar()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->hasAccess()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Acceso denegado']);
            return;
        }
        $ids = isset($_POST['orden']) && is_array($_POST['orden']) ? $_POST['orden'] : [];

        try {
            if (\Models\Banner::ordenar($ids)) {
                echo json_encode(['ok' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['ok' => false, 'message' => 'Error al ordenar los banners.']);
            }
        } catch (\Throwable $e) {
            error_log('BannerController::ordenar error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    public function actualizar_imagen()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->hasAccess()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Acceso denegado']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id === 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'ID de banner no válido.']);
            return;
        }

        if (empty($_FILES['imagen']['name']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Subida de imagen no válida.']);
            return;
        }

        $tmpPath  = $_FILES['imagen']['tmp_name'];
        $origName = $_FILES['imagen']['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $permitidas, true)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Formato no permitido.']);
            return;
        }

        $safeName = uniqid('banner_', true) . '.' . $ext;
        $destFs   = $this->uploadDirFs . $safeName;

        try {
            $banner = \Models\Banner::obtenerPorId($id);
            if (!$banner) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'message' => 'Banner no encontrado.']);
                return;
            }

            if (!@move_uploaded_file($tmpPath, $destFs)) {
                http_response_code(500);
                echo json_encode(['ok' => false, 'message' => 'No se pudo guardar el nuevo archivo.']);
                return;
            }

            if (\Models\Banner::actualizarImagen($id, $safeName)) {
                // Eliminar la imagen antigua si existe
                if (!empty($banner['nombre_imagen']) && file_exists($this->uploadDirFs . $banner['nombre_imagen'])) {
                    @unlink($this->uploadDirFs . $banner['nombre_imagen']);
                }
                echo json_encode(['ok' => true, 'data' => ['nombre_imagen' => $safeName]]);
            } else {
                if (file_exists($destFs)) @unlink($destFs);
                http_response_code(500);
                echo json_encode(['ok' => false, 'message' => 'Error al actualizar la imagen en la base de datos.']);
            }
        } catch (\Throwable $e) {
            if (file_exists($destFs)) @unlink($destFs);
            error_log('BannerController::actualizar_imagen error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Error interno del servidor.']);
        }
    }

    public function actualizar_enlace()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!$this->hasAccess()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Acceso denegado']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $enlace = isset($_POST['enlace']) && trim($_POST['enlace']) !== '' ? trim($_POST['enlace']) : null;

        if ($id === 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'ID de banner no válido.']);
            return;
        }

        try {
            if (\Models\Banner::actualizarEnlace($id, $enlace)) {
                echo json_encode(['ok' => true, 'data' => ['enlace' => $enlace]]);
            } else {
                http_response_code(500);
                echo json_encode(['ok' => false, 'message' => 'Error al actualizar el enlace en la base de datos.']);
            }
        } catch (\Throwable $e) {
            error_log('BannerController::actualizar_enlace error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Error interno del servidor.']);
        }
    }
}


