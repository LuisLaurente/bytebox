<?php if (!empty($errores)): ?>
    <ul style="color: red;">
        <?php foreach ($errores as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<h2>Editar Etiqueta</h2>
<form method="POST" action="">
    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($etiqueta['nombre']) ?>">
    <button type="submit">Actualizar</button>
</form>

<!-- actualmente no esta en uso, se esta modales -->