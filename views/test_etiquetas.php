<?php
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Etiqueta.php';

$etiquetaModel = new \Models\Etiqueta();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $nombre)));
    $etiquetaModel->crear($nombre, $slug);
    echo "Etiqueta creada.<br>";
}

$etiquetas = $etiquetaModel->obtenerTodas();
?>

<form method="POST">
    <input type="text" name="nombre" placeholder="Nombre de etiqueta">
    <button type="submit">Crear</button>
</form>

<ul>
<?php foreach ($etiquetas as $et): ?>
    <li><?= $et['nombre'] ?> (<?= $et['slug'] ?>)</li>
<?php endforeach; ?>
</ul>