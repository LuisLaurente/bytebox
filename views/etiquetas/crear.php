<h2>Crear Etiqueta</h2>
<form method="POST">
  <input type="text" name="nombre" value="<?= htmlspecialchars($nombre ?? '') ?>">
  <button type="submit">Guardar</button>
</form>
<?php if (!empty($errores)): ?>
    <ul style="color: red;">
        <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<!-- actualmente no esta en uso, se esta modales -->