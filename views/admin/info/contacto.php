<?php include_once __DIR__ . '/../includes/head.php'; ?>

<div class="info-container">
    <h1>Contáctanos</h1>
    <form method="POST" action="<?= url('contacto/enviar') ?>" class="contact-form">
        <label for="nombre">Nombre</label>
        <input type="text" name="nombre" id="nombre" required>

        <label for="email">Correo Electrónico</label>
        <input type="email" name="email" id="email" required>

        <label for="mensaje">Mensaje</label>
        <textarea name="mensaje" id="mensaje" required></textarea>

        <button type="submit" class="btn btn-primary">Enviar</button>
    </form>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
