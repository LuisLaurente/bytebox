<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/bannerAdmi.css') ?>">
<body>
<?php include_once __DIR__ . '/../admin/includes/header.php'; ?>

<div class="container">
    <h1>Nuevo Banner</h1>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('banner/guardar') ?>" enctype="multipart/form-data">
        <div class="form-row">
            <label>Imagen (JPG/PNG/WEBP/GIF):</label>
            <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp,.gif" required>
        </div>

        <div class="form-row">
            <label>Orden:</label>
            <input type="number" name="orden" value="0">
        </div>

        <div class="form-row">
            <label>Activo:</label>
            <input type="checkbox" name="activo" value="1" checked>
        </div>

        <button class="btn" type="submit">Guardar</button>
        <a class="btn" href="<?= url('banners') ?>">Cancelar</a>
    </form>
</div>

<?php include_once __DIR__ . '/../admin/includes/footer.php'; ?>
</body>
