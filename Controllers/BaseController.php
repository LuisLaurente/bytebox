<?php
namespace Controllers;

class BaseController
{
    protected function render($view, $data = [])
    {
        // Extraer variables para usarlas en la vista
        extract($data);

        // Ruta de la vista
        $file = __DIR__ . '/../views/' . $view . '.php';

        if (file_exists($file)) {
            include_once $file;
        } else {
            echo "❌ Error: No se encontró la vista $file";
        }
    }
}