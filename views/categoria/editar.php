<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('/css/categoriaEditar.css') ?>">

<body>
    <div class="categoria-editar-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

        <div class="categoria-editar-main">
            <main class="categoria-editar-content">
                <div class="categoria-editar-container">
                    <h1 class="categoria-editar-title">Editar Categoría</h1>

                    <!-- Mensajes de error -->
                    <?php if (!empty($error)): ?>
                        <div class="categoria-editar-error">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errores)): ?>
                        <div class="categoria-editar-errors">
                            <ul class="categoria-editar-errors-list">
                                <?php foreach ($errores as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= url('categoria/actualizar') ?>" enctype="multipart/form-data" class="categoria-editar-form">
                        <input type="hidden" name="id" value="<?= (int)$categoria['id'] ?>">
                        <input type="hidden" name="MAX_FILE_SIZE" value="2097152">

                        <div class="categoria-editar-form-group">
                            <label for="nombre" class="categoria-editar-label">Nombre de la categoría:</label>
                            <input
                                type="text"
                                name="nombre"
                                id="nombre"
                                value="<?= htmlspecialchars($categoria['nombre'] ?? '') ?>"
                                required
                                class="categoria-editar-input"
                            >
                        </div>

                        <div class="categoria-editar-form-group">
                            <label for="id_padre" class="categoria-editar-label">Categoría padre (opcional):</label>
                            <select name="id_padre" id="id_padre" class="categoria-editar-select">
                                <option value="">-- Ninguna (Categoría principal) --</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <?php if ($cat['id'] != $categoria['id']): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $categoria['id_padre']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="categoria-editar-form-group">
                            <label class="categoria-editar-label">Imagen actual</label>
                            <?php if (!empty($categoria['imagen'])): ?>
                                <div class="categoria-editar-current-image">
                                    <img
                                        src="<?= url('uploads/categorias/' . $categoria['imagen']) ?>"
                                        alt="<?= htmlspecialchars($categoria['nombre']) ?>"
                                        class="categoria-editar-current-image-preview"
                                    >
                                </div>
                            <?php else: ?>
                                <p class="categoria-editar-no-image">No hay imagen.</p>
                            <?php endif; ?>
                        </div>

                        <div class="categoria-editar-form-group">
                            <label for="imagen" class="categoria-editar-label">Reemplazar imagen (opcional) — jpg, png, webp, gif. Máx 2 MB</label>
                            <input
                                type="file"
                                name="imagen"
                                id="imagen-edit"
                                accept="image/*"
                                class="categoria-editar-file-input"
                            >
                            <div id="preview-wrapper-edit" class="categoria-editar-preview-wrapper">
                                <div class="categoria-editar-preview-label">Vista previa nueva imagen:</div>
                                <img id="preview-edit" src="#" alt="Vista previa de la imagen" class="categoria-editar-preview-image">
                            </div>
                        </div>

                        <div class="categoria-editar-actions">
                            <button type="submit" class="categoria-editar-submit-btn">
                                Actualizar categoría
                            </button>
                            <a href="<?= url('categoria') ?>" class="categoria-editar-back-link">← Volver al listado</a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function(){
            const input = document.getElementById('imagen-edit');
            const preview = document.getElementById('preview-edit');
            const wrapper = document.getElementById('preview-wrapper-edit');

            if (!input) return;

            input.addEventListener('change', function(){
                const file = this.files && this.files[0];
                if (!file) {
                    wrapper.style.display = 'none';
                    preview.src = '#';
                    return;
                }

                const maxBytes = 2097152;
                if (file.size > maxBytes) {
                    alert('El archivo supera el tamaño máximo de 2 MB.');
                    this.value = '';
                    wrapper.style.display = 'none';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(ev) {
                    preview.src = ev.target.result;
                    wrapper.style.display = 'block';
                };
                reader.readAsDataURL(file);
            });
        })();
    </script>
</body>
</html>