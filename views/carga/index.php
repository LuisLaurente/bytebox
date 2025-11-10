<?php
// Asegura que la sesión esté activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializa contador del carrito
$cantidadEnCarrito = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidadEnCarrito += $item['cantidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/cargaIndex.css') ?>">

<body>
    <div class="_cargaMasiva-admin-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

        <!-- Contenido principal -->
        <div class="_cargaMasiva-main-content">
            <main class="_cargaMasiva-content">
                <div class="_cargaMasiva-container">
                    <div class="_cargaMasiva-card">
                        <h2 class="_cargaMasiva-title">Carga Masiva de Productos</h2>
                        <p class="_cargaMasiva-subtitle">Sube un archivo CSV para registrar o actualizar productos en el catálogo.</p>

                        <!-- Formulario -->
                        <form action="<?= url('cargaMasiva/procesarCSV') ?>" method="POST" enctype="multipart/form-data" class="_cargaMasiva-form">
                            <div class="_cargaMasiva-upload-group">
                                <label for="archivo_csv" class="_cargaMasiva-upload-button">
                                    Seleccionar
                                </label>
                                <input 
                                    id="archivo_csv" 
                                    name="archivo_csv" 
                                    type="file" 
                                    accept=".csv" 
                                    class="_cargaMasiva-file-input"
                                />
                                <span id="nombreArchivo" class="_cargaMasiva-file-name">Ningún archivo seleccionado</span>
                            </div>

                            <button type="submit" class="_cargaMasiva-submit-button">
                                Procesar archivo
                            </button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Script para mostrar nombre del archivo -->
    <script>
        const inputFile = document.getElementById("archivo_csv");
        const nombreArchivo = document.getElementById("nombreArchivo");

        inputFile.addEventListener("change", () => {
            if (inputFile.files.length > 0) {
                nombreArchivo.textContent = inputFile.files[0].name;
            } else {
                nombreArchivo.textContent = "Ningún archivo seleccionado";
            }
        });
    </script>

    <!-- Scripts -->
    <script src="<?= url('js/min/producto-filtros.min.js') ?>?v=<?= time() ?>"></script>
</body>
</html>