<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('/css/etiquetaIndex.css') ?>">

<body>
    <div class="etiqueta-index-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

        <div class="etiqueta-index-main">
            <main class="etiqueta-index-content">
                <div class="etiqueta-index-container">
                    <div class="etiqueta-index-header">
                        <div class="etiqueta-index-header-content">
                            <h2 class="etiqueta-index-title">Listado de Etiquetas</h2>
                            <button id="openCrearModal" class="etiqueta-index-new-btn">
                                + Nueva Etiqueta
                            </button>
                        </div>
                    </div>

                    <div class="etiqueta-index-card">
                        <table class="etiqueta-index-table">
                            <thead class="etiqueta-index-table-header">
                                <tr>
                                    <th class="etiqueta-index-table-head">ID</th>
                                    <th class="etiqueta-index-table-head">Nombre</th>
                                    <th class="etiqueta-index-table-head etiqueta-index-actions-head">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="etiqueta-index-table-body">
                                <?php if (!empty($etiquetas)): ?>
                                    <?php foreach ($etiquetas as $et): ?>
                                        <tr class="etiqueta-index-table-row">
                                            <td class="etiqueta-index-table-cell"><?= $et['id'] ?></td>
                                            <td class="etiqueta-index-table-cell"><?= htmlspecialchars($et['nombre']) ?></td>
                                            <td class="etiqueta-index-table-cell etiqueta-index-actions-cell">
                                                <button 
                                                    class="etiqueta-index-edit-btn"
                                                    data-id="<?= $et['id'] ?>" 
                                                    data-nombre="<?= htmlspecialchars($et['nombre']) ?>">
                                                    Editar
                                                </button>
                                                <a href="<?= url('etiqueta/eliminar/' . $et['id']) ?>"
                                                onclick="return confirm('¿Eliminar esta etiqueta?')"
                                                class="etiqueta-index-delete-btn">
                                                    Eliminar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="etiqueta-index-table-row">
                                        <td colspan="3" class="etiqueta-index-empty-cell">
                                            No hay etiquetas registradas.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Crear -->
    <div id="crearModal" class="etiqueta-index-modal etiqueta-index-modal-hidden">
        <div class="etiqueta-index-modal-content">
            <button id="closeCrearModal" class="etiqueta-index-modal-close">&times;</button>
            <h2 class="etiqueta-index-modal-title">Crear Etiqueta</h2>
            <form method="POST" action="<?= url('etiqueta/crear') ?>" class="etiqueta-index-modal-form">
                <input type="text" name="nombre" 
                    value="<?= htmlspecialchars($nombre ?? '') ?>" 
                    placeholder="Nombre de la etiqueta"
                    class="etiqueta-index-modal-input" />
                <button type="submit" class="etiqueta-index-modal-submit-btn">Guardar</button>
            </form>
            <?php if (!empty($errores)): ?>
                <ul class="etiqueta-index-errors-list">
                    <?php foreach ($errores as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Editar -->
    <div id="editarModal" class="etiqueta-index-modal etiqueta-index-modal-hidden">
        <div class="etiqueta-index-modal-content">
            <button id="closeEditarModal" class="etiqueta-index-modal-close">&times;</button>
            <h2 class="etiqueta-index-modal-title">Editar Etiqueta</h2>
            <form method="POST" id="editarForm" action="" class="etiqueta-index-modal-form">
                <label for="nombreEditar" class="etiqueta-index-modal-label">Nombre:</label>
                <input id="nombreEditar" type="text" name="nombre" 
                    value="" 
                    class="etiqueta-index-modal-input" />
                <button type="submit" class="etiqueta-index-modal-submit-btn etiqueta-index-modal-update-btn">Actualizar</button>
            </form>
            <div id="erroresEditar" class="etiqueta-index-errors-container"></div>
        </div>
    </div>

    <script>
        // Modal Crear
        const openCrearBtn = document.getElementById('openCrearModal');
        const crearModal = document.getElementById('crearModal');
        const closeCrearBtn = document.getElementById('closeCrearModal');

        openCrearBtn.addEventListener('click', () => {
            crearModal.classList.remove('etiqueta-index-modal-hidden');
        });
        closeCrearBtn.addEventListener('click', () => {
            crearModal.classList.add('etiqueta-index-modal-hidden');
        });
        crearModal.addEventListener('click', (e) => {
            if (e.target === crearModal) crearModal.classList.add('etiqueta-index-modal-hidden');
        });

        // Modal Editar
        const editarModal = document.getElementById('editarModal');
        const closeEditarBtn = document.getElementById('closeEditarModal');
        const editarForm = document.getElementById('editarForm');
        const nombreInput = document.getElementById('nombreEditar');

        document.querySelectorAll('.etiqueta-index-edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');
                editarModal.classList.remove('etiqueta-index-modal-hidden');
                nombreInput.value = nombre;
                editarForm.action = '<?= url("etiqueta/editar") ?>/' + id;
                document.getElementById('erroresEditar').innerHTML = '';
            });
        });

        closeEditarBtn.addEventListener('click', () => {
            editarModal.classList.add('etiqueta-index-modal-hidden');
        });
        editarModal.addEventListener('click', (e) => {
            if (e.target === editarModal) editarModal.classList.add('etiqueta-index-modal-hidden');
        });
    </script>
</body>
</html>