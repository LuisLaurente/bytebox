<?php

namespace Controllers;

use Models\Reclamacion;

class ReclamacionController
{
    public function formulario()
    {
        require_once __DIR__ . '/../views/reclamacion/formulario.php';
    }

    public function enviar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = $_POST['nombre'] ?? '';
            $correo = $_POST['correo'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $mensaje = $_POST['mensaje'] ?? '';
            $pedido_id = !empty($_POST['pedido_id']) ? (int)$_POST['pedido_id'] : null;

            $errores = [];

            if (empty($nombre)) $errores[] = "El nombre es obligatorio";
            if (empty($correo)) $errores[] = "El correo es obligatorio";
            if (empty($mensaje)) $errores[] = "El mensaje es obligatorio";

            // Validar si el código de pedido existe (si se ingresó)
            $advertencia = '';
            if ($pedido_id !== null) {
                $detallePedidoModel = new \Models\DetallePedido();
                $existePedido = $detallePedidoModel->existePedido($pedido_id);

                if (!$existePedido) {
                    $advertencia = "⚠️ El código de pedido ingresado no existe. De igual forma se ha creado una queja.";
                }
            }

            if (!empty($errores)) {
                require_once __DIR__ . '/../views/reclamacion/formulario.php';
                return;
            }

            // Insertar reclamación
            $reclamacionModel = new \Models\Reclamacion();
            $reclamacionModel->crear([
                'nombre' => $nombre,
                'correo' => $correo,
                'telefono' => $telefono,
                'mensaje' => $mensaje,
                'pedido_id' => $pedido_id
            ]);
            $toast_exito = true; // solo para el toast
            $mensaje_exito = "✅ Se notificará por correo dentro de tres dias hábiles.";
            require_once __DIR__ . '/../views/reclamacion/formulario.php';
            exit;
        }
    }


}
