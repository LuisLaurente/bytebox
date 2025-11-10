<?php require_once __DIR__ . '/../../core/helpers/urlHelper.php'; ?>

<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('/css/gestion_imagenes.css') ?>">
<body>
    <div class="gestion-imagenes-layout">
        <!-- Incluir navegación lateral fija -->
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

        <div class="gestion-imagenes-main">
            <main class="gestion-imagenes-content">
                <div class="gestion-imagenes-container">
                    <h1 class="gestion-imagenes-title">Gestión Masiva de Imágenes por Excel</h1>
                    
                    <?php if (isset($_SESSION['flash_error'])): ?>
                        <div class="gestion-imagenes-alert gestion-imagenes-alert-danger">
                            <?= $_SESSION['flash_error'] ?>
                        </div>
                        <?php unset($_SESSION['flash_error']); ?>
                    <?php endif; ?>
                    
                    <!-- PASO 1: Generar Excel -->
                    <div class="gestion-imagenes-paso">
                        <h3 class="gestion-imagenes-paso-title">
                            <span class="gestion-imagenes-paso-numero">1</span>
                            Generar CSV con Productos
                        </h3>
                        <p class="gestion-imagenes-paso-descripcion">Descarga un archivo CSV con todos los productos existentes. Este archivo incluye columnas para especificar qué imágenes corresponden a cada producto.</p>
                        
                        <div class="gestion-imagenes-instrucciones">
                            <h4 class="gestion-imagenes-instrucciones-titulo"> ¿Qué contiene el CSV?</h4>
                            <ul class="gestion-imagenes-lista">
                                <li><strong>ID_PRODUCTO:</strong> Identificador único</li>
                                <li><strong>NOMBRE_PRODUCTO:</strong> Nombre del producto</li>
                                <li><strong>SKU:</strong> Código de producto</li>
                                <li><strong>IMAGENES_ACTUALES:</strong> Cantidad de imágenes que ya tiene</li>
                                <li><strong>IMAGEN_1 a IMAGEN_5:</strong> Columnas donde escribirás los nombres de archivos</li>
                            </ul>
                        </div>
                        
                        <a href="<?= url('cargaMasiva/generarExcelImagenes') ?>" class="gestion-imagenes-btn gestion-imagenes-btn-success">
                            Descargar CSV de Productos
                        </a>
                    </div>
                    
                    <!-- PASO 2: Completar Excel -->
                    <div class="gestion-imagenes-paso">
                        <h3 class="gestion-imagenes-paso-title">
                            <span class="gestion-imagenes-paso-numero">2</span>
                            Completar CSV con Referencias de Imágenes
                        </h3>
                        <p class="gestion-imagenes-paso-descripcion">Abre el CSV descargado con Excel o LibreOffice y completa las columnas IMAGEN_1 a IMAGEN_5 con los nombres exactos de tus archivos de imagen.</p>
                        
                        <div class="gestion-imagenes-instrucciones">
                            <h4 class="gestion-imagenes-instrucciones-titulo">Ejemplo de cómo completar:</h4>
                            <div class="gestion-imagenes-tabla-contenedor">
                                <table class="gestion-imagenes-tabla-ejemplo">
                                    <thead>
                                        <tr>
                                            <th>NOMBRE_PRODUCTO</th>
                                            <th>IMAGEN_1</th>
                                            <th>IMAGEN_2</th>
                                            <th>IMAGEN_3</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>iPhone 14</td>
                                            <td>iphone14_frontal.jpg</td>
                                            <td>iphone14_trasera.jpg</td>
                                            <td>iphone14_lateral.jpg</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <h4 class="gestion-imagenes-instrucciones-titulo">Reglas importantes:</h4>
                            <ul class="gestion-imagenes-lista">
                                <li>Los nombres deben ser EXACTOS (respeta mayúsculas, minúsculas y caracteres especiales)</li>
                                <li>Solo el nombre del archivo, sin rutas (ejemplo: "foto.jpg" NO "carpeta/foto.jpg")</li>
                                <li>Puedes dejar columnas vacías si no tienes tantas imágenes</li>
                                <li>Formatos soportados: .jpg, .jpeg, .png, .webp, .gif</li>
                                <li><strong>Al guardar:</strong> Mantén la codificación UTF-8 para conservar tildes y caracteres especiales</li>
                                <li><strong>En Excel:</strong> Usa "Guardar como" → CSV (separado por punto y coma) → UTF-8</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- PASO 3: Subir archivos -->
                    <div class="gestion-imagenes-paso">
                        <h3 class="gestion-imagenes-paso-title">
                            <span class="gestion-imagenes-paso-numero">3</span>
                            Subir CSV y Archivo de Imágenes
                        </h3>
                        <p class="gestion-imagenes-paso-descripcion">Sube el CSV completado junto con un archivo ZIP que contenga todas las imágenes referenciadas.</p>
                        
                        <form action="<?= url('cargaMasiva/procesarExcelImagenes') ?>" method="POST" enctype="multipart/form-data" class="gestion-imagenes-formulario">
                            <div class="gestion-imagenes-upload-area">
                                <h4 class="gestion-imagenes-upload-titulo">CSV Completado</h4>
                                <input type="file" name="excel_imagenes" accept=".csv" required class="gestion-imagenes-file-input">
                                <p class="gestion-imagenes-upload-descripcion">Sube el archivo CSV que modificaste con las referencias de imágenes</p>
                            </div>
                            
                            <div class="gestion-imagenes-upload-area">
                                <h4 class="gestion-imagenes-upload-titulo">🗜️ Archivo ZIP con Imágenes</h4>
                                <input type="file" name="archivo_imagenes" accept=".zip" required class="gestion-imagenes-file-input">
                                <p class="gestion-imagenes-upload-descripcion">Comprime todas las imágenes en un archivo ZIP</p>
                            </div>
                            
                            <div class="gestion-imagenes-alert gestion-imagenes-alert-info">
                                <strong>Antes de subir, verifica que:</strong>
                                <ul class="gestion-imagenes-lista">
                                    <li>Los nombres en el CSV coinciden exactamente con los archivos en el ZIP</li>
                                    <li>Todas las imágenes están en el ZIP (pueden estar en subcarpetas)</li>
                                    <li>Los archivos son imágenes válidas (JPG, PNG, WEBP, GIF)</li>
                                    <li>Cada imagen pesa menos de 5MB</li>
                                    <li>El CSV está guardado con separador de punto y coma (;)</li>
                                </ul>
                            </div>
                            
                            <div class="gestion-imagenes-form-actions">
                                <button type="submit" class="gestion-imagenes-btn gestion-imagenes-btn-success">
                                    Procesar y Enlazar Imágenes
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="gestion-imagenes-actions">
                        <a href="<?= url('producto/index') ?>" class="gestion-imagenes-btn gestion-imagenes-btn-secondary">
                            ← Volver a Productos
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>