<?php
namespace Controllers;

use Models\Popup;

class AdminPopupController
{
    public function index()
    {
        $popupModel = new Popup();
        $popup = $popupModel->obtener();
        $imagenes = $popupModel->obtenerImagenes(); // nuevo

        require_once __DIR__ . '/../views/admin/popup/index.php';
    }

    public function guardar()
    {
        $texto = $_POST['texto'] ?? '';
        $activo = isset($_POST['activo']) ? 1 : 0;
        $imagenPrincipal = $_POST['imagen_principal'] ?? null;

        $popupModel = new Popup();
        $popupActual = $popupModel->obtener();

        // 1. Subir nuevas imágenes
        if (!empty($_FILES['nuevas_imagenes']['name'][0])) {
            $total = count($_FILES['nuevas_imagenes']['name']);
            for ($i = 0; $i < $total; $i++) {
                $tmpName = $_FILES['nuevas_imagenes']['tmp_name'][$i];
                $originalName = basename($_FILES['nuevas_imagenes']['name'][$i]);
                $nombreArchivo = uniqid('popup_') . '_' . $originalName;
                $destino = __DIR__ . '/../public/images/popup/' . $nombreArchivo;

                if (move_uploaded_file($tmpName, $destino)) {
                    $popupModel->agregarImagen($nombreArchivo);
                }
            }
        }

        // 2. Actualizar la imagen principal seleccionada
        if ($imagenPrincipal) {
            $popupModel->actualizarImagenPrincipal($imagenPrincipal);
        }

        // 3. Actualizar texto y estado
        $popupModel->actualizarTextoYEstado($texto, $activo);

        header("Location: " . url("adminPopup/index"));
        exit;
    }


    public function eliminarImagen($id)
    {
        $popupModel = new Popup();
        $popupModel->eliminarImagen($id);
        header("Location: " . url("adminPopup/index"));
        exit;
    }
}
