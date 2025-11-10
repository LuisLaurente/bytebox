<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/popupIndex.css') ?>">

<body>
    <div class="_popupAdmin-admin-layout">
        <?php include_once __DIR__ . '/../includes/navbar.php'; ?>
        <div class="_popupAdmin-main-content">
            <main class="_popupAdmin-content">
                <div class="_popupAdmin-container">
                    <div class="_popupAdmin-card">
                        <h1 class="_popupAdmin-title">Configurar Pop-up Promocional</h1>

                        <form action="<?= url('adminPopup/guardar') ?>" method="post" enctype="multipart/form-data" class="_popupAdmin-form">
                            <!-- Texto del Pop-up -->
                            <div class="_popupAdmin-form-group">
                                <label for="texto" class="_popupAdmin-form-label">Texto del Pop-up</label>
                                <textarea id="texto" name="texto" rows="4" class="_popupAdmin-textarea"
                                    placeholder="Escribe el texto que quieres mostrar en el pop-up..."><?= htmlspecialchars($popup['texto'] ?? '') ?></textarea>
                            </div>

                            <!-- Subir nuevas imágenes -->
                            <div class="_popupAdmin-form-group">
                                <label class="_popupAdmin-form-label">Subir nuevas imágenes</label>
                                <div class="_popupAdmin-upload-container">
                                    <label for="nuevas_imagenes" class="_popupAdmin-upload-button">
                                        Seleccionar
                                    </label>
                                    <input id="nuevas_imagenes" name="nuevas_imagenes[]" type="file" accept="image/*" multiple class="_popupAdmin-file-input">
                                    
                                    <div class="_popupAdmin-upload-info">
                                        <p id="nuevasInfo" class="_popupAdmin-upload-text">Ningún archivo seleccionado</p>
                                        <p class="_popupAdmin-upload-help">Puedes seleccionar varias imágenes. Se mostrarán como miniaturas abajo. Para finalizar presione GUARDAR CAMBIOS</p>
                                        
                                        <!-- Previsualización de nuevos archivos -->
                                        <div id="previewNuevas" class="_popupAdmin-preview-grid"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Activar popup -->
                            <div class="_popupAdmin-checkbox-group">
                                <input id="activo" type="checkbox" name="activo" <?= ($popup['activo'] ?? 0) ? 'checked' : '' ?> class="_popupAdmin-checkbox">
                                <label for="activo" class="_popupAdmin-checkbox-label">Activar pop-up</label>
                            </div>

                            <!-- Imágenes disponibles -->
                            <?php if (!empty($imagenes)): ?>
                                <div class="_popupAdmin-images-section">
                                    <h2 class="_popupAdmin-subtitle">Imágenes disponibles</h2>
                                    <div class="_popupAdmin-images-grid">
                                        <?php foreach ($imagenes as $img): 
                                            $imgName = htmlspecialchars($img['nombre_imagen']);
                                            $imgId = (int)$img['id'];
                                            $isChecked = (isset($popup['imagen']) && $popup['imagen'] === $img['nombre_imagen']);
                                        ?>
                                        <div class="_popupAdmin-image-card">
                                            <div class="_popupAdmin-image-container">
                                                <img src="<?= url('images/popup/' . $imgName) ?>"
                                                    alt="Imagen <?= $imgId ?>"
                                                    class="_popupAdmin-image">
                                            </div>

                                            <div class="_popupAdmin-radio-group">
                                                <input id="principal_<?= $imgId ?>" type="radio" name="imagen_principal" value="<?= $imgName ?>"
                                                        <?= $isChecked ? 'checked' : '' ?>
                                                        class="_popupAdmin-radio">
                                                <label for="principal_<?= $imgId ?>" class="_popupAdmin-radio-label">Principal</label>
                                            </div>

                                            <a href="<?= url('adminPopup/eliminarImagen/' . $imgId) ?>"
                                            onclick="return confirm('¿Eliminar esta imagen?')"
                                            class="_popupAdmin-delete-button">
                                            ❌ Eliminar
                                            </a>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="_popupAdmin-no-images">No hay imágenes subidas.</p>
                            <?php endif; ?>

                            <!-- Botón guardar -->
                            <div class="_popupAdmin-form-actions">
                                <button type="submit" class="_popupAdmin-submit-button">
                                    Guardar cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function(){
            const input = document.getElementById('nuevas_imagenes');
            const info = document.getElementById('nuevasInfo');
            const preview = document.getElementById('previewNuevas');

            if (!input || !info || !preview) return;

            input.addEventListener('change', () => {
                preview.innerHTML = '';
                const files = Array.from(input.files || []);
                if (files.length === 0) {
                    info.textContent = 'Ningún archivo seleccionado';
                    return;
                }

                info.textContent = files.length + (files.length === 1 ? ' archivo seleccionado' : ' archivos seleccionados');

                files.slice(0, 12).forEach(file => {
                    if (!file.type.startsWith('image/')) return;

                    const reader = new FileReader();
                    const container = document.createElement('div');
                    container.className = '_popupAdmin-preview-item';

                    reader.onload = (e) => {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = file.name;
                        img.className = '_popupAdmin-preview-image';
                        container.appendChild(img);
                    };
                    reader.readAsDataURL(file);

                    preview.appendChild(container);
                });
            });
        })();
    </script>
</body>
</html>