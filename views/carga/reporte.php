<h2>❌ Errores encontrados en el archivo CSV</h2>
<a href="<?= url('producto/index') ?>">← Volver al listado de productos</a>

<table border="1" cellpadding="6" style="border-collapse: collapse; margin-top: 20px;">
    <thead>
        <tr>
            <th>Fila</th>
            <th>Detalle del Error</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reporteErrores as $error): ?>
            <tr>
                <td><?= $error['fila'] ?></td>
                <td><?= htmlspecialchars($error['error']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>