<?php

namespace Controllers;

use Models\ImagenProducto;

class ImagenController
{
    public function eliminar($id)
    {
        // Obtenemos el nombre del archivo para borrarlo físicamente
        $db = \Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT nombre_imagen FROM imagenes_producto WHERE id = ?");
        $stmt->execute([$id]);
        $imagen = $stmt->fetch();

        if ($imagen) {
            // ✅ Ruta CORREGIDA - misma estructura que funciona
            $ruta = __DIR__ . '/../public/uploads/' . $imagen['nombre_imagen'];
            
            if (file_exists($ruta)) {
                unlink($ruta);
            }

            // Eliminamos el registro de la base de datos
            ImagenProducto::eliminar($id);
        }

        // ✅ Redireccionar de forma segura usando url() si no hay HTTP_REFERER
        if (!empty($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('Location: ' . url('producto')); // fallback seguro
        }
        exit;
    }

    public function subir()
    {
        // 1️⃣ Validación inicial
        if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = "No se recibió la imagen o hubo un error al subir.";
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('producto')));
            exit;
        }

        $producto_id = $_POST['producto_id'] ?? null;
        if (!$producto_id) {
            $_SESSION['error'] = "No se recibió ID de producto.";
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('producto')));
            exit;
        }

        $nombreOriginal = $_FILES['imagen']['name'];
        $nombreFinal = uniqid() . '_' . basename($nombreOriginal);
        $tmpPath = $_FILES['imagen']['tmp_name'];

        // 2️⃣ Ruta destino CORREGIDA
        $destino = __DIR__ . '/../public/uploads/' . $nombreFinal;

        // 3️⃣ Probar movimiento
        if (!move_uploaded_file($tmpPath, $destino)) {
            $_SESSION['error'] = "No se pudo mover la imagen al destino.";
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('producto')));
            exit;
        }

        // 4️⃣ Guardar en base de datos
        \Models\ImagenProducto::guardar($producto_id, $nombreFinal);

        $_SESSION['success'] = "Imagen subida correctamente: $nombreFinal";
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('producto')));
        exit;
    }
}