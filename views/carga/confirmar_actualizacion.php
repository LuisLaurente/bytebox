<h2>Productos Duplicados por SKU</h2>
<form action="<?= url('cargaMasiva/confirmarActualizacion') ?>" method="POST">
    <input type="hidden" name="confirmar" value="1">

    <table border="1">
        <tr>
            <th>SKU</th><th>Nombre</th><th>Descripción</th><th>Precio</th><th>¿Sobrescribir?</th>
        </tr>
        <?php foreach ($_SESSION['productos_pendientes'] as $prod): ?>
            <tr>
                <td><?= htmlspecialchars($prod['sku']) ?></td>
                <td><?= htmlspecialchars($prod['nombre']) ?></td>
                <td><?= htmlspecialchars($prod['descripcion']) ?></td>
                <td><?= htmlspecialchars($prod['precio']) ?></td>
                <td>
                    <input type="checkbox" name="sobrescribir[]" value="<?= $prod['sku'] ?>" checked>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <br>
    <button type="submit" onclick="return confirm('Desea sobre escribir?')">Actualizar</button>
</form>