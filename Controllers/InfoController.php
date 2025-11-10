<?php
namespace Controllers;

class InfoController
{
    public function nosotros()
    {
        require_once __DIR__ . '/../views/admin/info/nosotros.php';
    }

    public function contacto()
    {
        require_once __DIR__ . '/../views/admin/info/contacto.php';
    }

    public function terminos()
    {
        require_once __DIR__ . '/../views/admin/info/terminos.php';
    }

    public function privacidad()
    {
        require_once __DIR__ . '/../views/admin/info/privacidad.php';
    }

    // Si el formulario de contacto tiene envío:
    public function enviarContacto()
    {
        $nombre = $_POST['nombre'] ?? '';
        $email = $_POST['email'] ?? '';
        $mensaje = $_POST['mensaje'] ?? '';

        // Aquí podrías implementar:
        // - Guardar el mensaje en BD
        // - Enviar correo al administrador, etc.

        header("Location: " . url('contacto?enviado=1'));
        exit;
    }
}
