<?php
$metaTitle = "Contáctanos | Tienda Tecnovedades";
$metaDescription = "Envíanos tus dudas o consultas. Nuestro equipo de atención al cliente está disponible para ayudarte.";
?>
<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/reclamForm.css') ?>">
<script src="<?= url('js/reclamForm.js') ?>"></script>

<body>
    <div class="_reclamForm-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

        <div class="_reclamForm-main-content">
            <main class="_reclamForm-content">
                <div class="_reclamForm-container">
                    <div class="_reclamForm-card">
                        <h1 class="_reclamForm-title">Libro de Reclamaciones</h1>

                        <!-- Toast de éxito -->
                        <?php if (!empty($toast_exito)): ?>
                            <div id="toast" class="_reclamForm-toast _reclamForm-toast-success" style="display: none;">
                                ✅ Reclamo enviado con éxito.
                            </div>
                        <?php endif; ?>

                        <!-- Advertencia -->
                        <?php if (!empty($advertencia)): ?>
                            <p class="_reclamForm-warning"><?= htmlspecialchars($advertencia) ?></p>
                        <?php endif; ?>

                        <!-- Mensaje de éxito -->
                        <?php if (!empty($mensaje_exito)): ?>
                            <div class="_reclamForm-alert _reclamForm-alert-success">
                                <?= htmlspecialchars($mensaje_exito) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Errores -->
                        <?php if (!empty($errores)): ?>
                            <ul class="_reclamForm-alert _reclamForm-alert-error">
                                <?php foreach ($errores as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <!-- Formulario -->
                        <form method="post" action="<?= url('reclamacion/enviar') ?>" class="_reclamForm-form">
                            <div class="_reclamForm-form-group">
                                <label class="_reclamForm-label">Nombre completo: *</label>
                                <input type="text" name="nombre" required class="_reclamForm-input">
                            </div>

                            <div class="_reclamForm-form-group">
                                <label class="_reclamForm-label">Correo electrónico: *</label>
                                <input type="email" name="correo" required class="_reclamForm-input">
                            </div>

                            <div class="_reclamForm-form-group">
                                <label class="_reclamForm-label">Teléfono:</label>
                                <input type="tel" name="telefono" class="_reclamForm-input">
                            </div>

                            <div class="_reclamForm-form-group">
                                <label class="_reclamForm-label">Código del Pedido:</label>
                                <input type="number" name="pedido_id" required class="_reclamForm-input">
                            </div>

                            <div class="_reclamForm-form-group">
                                <label class="_reclamForm-label">Mensaje: *</label>
                                <textarea name="mensaje" rows="5" required class="_reclamForm-textarea"></textarea>
                            </div>

                            <div class="_reclamForm-form-actions">
                                <button type="submit" class="_reclamForm-btn _reclamForm-btn-primary">
                                    Enviar Reclamación
                                </button>

                                <a href="javascript:history.back()" class="_reclamForm-btn _reclamForm-btn-secondary">
                                    ← Volver
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= url('js/reclamForm.js') ?>"></script>
</body>

</html>