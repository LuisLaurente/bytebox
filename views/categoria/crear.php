<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('/css/categoriaCrear.css') ?>">

<body>
    <div class="categoria-crear-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

        <div class="categoria-crear-main">
            <main class="categoria-crear-content">
                <div class="categoria-crear-container">
                    <h1 class="categoria-crear-title">Crear Nueva Categoría</h1>

                    <?php if (!empty($errores)): ?>
                        <div class="categoria-crear-errors">
                            <ul class="categoria-crear-errors-list">
                                <?php foreach ($errores as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= url('categoria/guardar') ?>" enctype="multipart/form-data" class="categoria-crear-form">
                        <input type="hidden" name="MAX_FILE_SIZE" value="2097152">

                        <div class="categoria-crear-form-group">
                            <label for="nombre" class="categoria-crear-label">Nombre de la categoría:</label>
                            <input
                                type="text"
                                name="nombre"
                                id="nombre"
                                value="<?= htmlspecialchars($nombre ?? '') ?>"
                                required
                                class="categoria-crear-input"
                            >
                        </div>

                        <div class="categoria-crear-form-group">
                            <label for="id_padre" class="categoria-crear-label">Categoría padre (opcional):</label>
                            <select name="id_padre" id="id_padre" class="categoria-crear-select">
                                <option value="">-- Ninguna (Categoría principal) --</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= isset($id_padre) && $id_padre == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="categoria-crear-form-group">
                            <label for="imagen" class="categoria-crear-label">Imagen (opcional) — Tipo: jpg, png, webp, gif. Máx 2 MB</label>
                            <input
                                type="file"
                                name="imagen"
                                id="imagen"
                                accept="image/*"
                                class="categoria-crear-file-input"
                            >
                            <div id="preview-wrapper" class="categoria-crear-preview-wrapper">
                                <div class="categoria-crear-preview-label">Vista previa:</div>
                                <img id="preview" src="#" alt="Vista previa de la imagen" class="categoria-crear-preview-image">
                            </div>
                        </div>

                        <div class="categoria-crear-actions">
                            <button type="submit" class="categoria-crear-submit-btn">
                                Guardar categoría
                            </button>
                            <a href="<?= url('categoria') ?>" class="categoria-crear-back-link">← Volver al listado</a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function(){
            const input = document.getElementById('imagen');
            const preview = document.getElementById('preview');
            const wrapper = document.getElementById('preview-wrapper');

            if (!input) return;

            input.addEventListener('change', function(e){
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