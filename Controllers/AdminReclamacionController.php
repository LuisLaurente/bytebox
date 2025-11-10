<?php
namespace Controllers;

use Models\Reclamacion;

class AdminReclamacionController
{
    public function index()
    {
        $reclamacionModel = new Reclamacion();
        $reclamaciones = $reclamacionModel->obtenerTodas();

        require_once __DIR__ . '/../views/admin/reclamaciones/index.php';
    }
    public function eliminar($id)
{
    $reclamacion = new \Models\Reclamacion();
    $reclamacion->eliminarPorId($id);

    header('Location: ' . url('adminReclamacion/index'));
    exit;
}
}
