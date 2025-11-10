<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/profile.css') ?>">
<style>
    .form-direccion {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #dee2e6;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #2c3e50;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 0.95rem;
        transition: border-color 0.2s ease;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-color, #007bff);
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 20px 0;
    }

    .checkbox-group input[type="checkbox"] {
        width: auto;
        cursor: pointer;
    }

    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn-guardar, .btn-cancelar {
        padding: 10px 30px;
        border: 1px solid;
        border-radius: 4px;
        font-size: 0.95rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-guardar {
        background: var(--primary-color, #007bff);
        border-color: var(--primary-color, #007bff);
        color: white;
    }

    .btn-guardar:hover {
        background: var(--primary-dark, #0056b3);
        border-color: var(--primary-dark, #0056b3);
    }

    .btn-cancelar {
        background: white;
        border-color: #6c757d;
        color: #6c757d;
    }

    .btn-cancelar:hover {
        background: #6c757d;
        color: white;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<body>
    <?php include_once __DIR__ . '/../admin/includes/header.php'; ?>

    <div class="main-wrapper">
        <!-- Contenido principal -->
        <div class="main-content" style="margin-left: 0; width: 100%;">
            <div class="profile-container" style="max-width: 900px; margin: 0 auto; padding: 40px 20px;">
                <h1>Editar Dirección</h1>

                <!-- Mensajes -->
                <?php if (!empty($_GET['error'])): ?>
                    <div class="message error-message">
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>

                <div class="form-direccion">
                    <form method="POST" action="<?= url('/usuario/actualizar-direccion') ?>" id="formDireccion">
                        <input type="hidden" name="direccion_id" value="<?= $direccion['id'] ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre_direccion">Nombre de la dirección *</label>
                                <input type="text" 
                                    id="nombre_direccion" 
                                    name="nombre_direccion" 
                                    value="<?= htmlspecialchars($direccion['nombre_direccion']) ?>"
                                    placeholder="Ej: Casa, Oficina, Casa de mamá"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="tipo_direccion">Tipo de dirección</label>
                                <select id="tipo_direccion" name="tipo_direccion">
                                    <option value="casa" <?= $direccion['tipo_direccion'] === 'casa' ? 'selected' : '' ?>>🏠 Casa</option>
                                    <option value="trabajo" <?= $direccion['tipo_direccion'] === 'trabajo' ? 'selected' : '' ?>>💼 Trabajo</option>
                                    <option value="otro" <?= $direccion['tipo_direccion'] === 'otro' ? 'selected' : '' ?>>📍 Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="departamento">Departamento *</label>
                                <input type="text" 
                                    id="departamento" 
                                    name="departamento" 
                                    value="<?= htmlspecialchars($direccion['departamento'] ?? '') ?>"
                                    placeholder="Ej: Lima, Arequipa, Cusco"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="provincia">Provincia</label>
                                <input type="text" 
                                    id="provincia" 
                                    name="provincia" 
                                    value="<?= htmlspecialchars($direccion['provincia'] ?? '') ?>"
                                    placeholder="Ej: Lima, Huaura">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="distrito">Distrito *</label>
            <input type="text" 
                                id="distrito" 
                                name="distrito" 
                                value="<?= htmlspecialchars($direccion['distrito'] ?? '') ?>"
                                placeholder="Ej: San Isidro, Miraflores"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="direccion">Dirección completa *</label>
                            <textarea id="direccion" 
                                name="direccion" 
                                placeholder="Av. Principal 123, Urbanización..."
                                required><?= htmlspecialchars($direccion['direccion']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="referencia">Referencia</label>
                            <input type="text" 
                                id="referencia" 
                                name="referencia" 
                                value="<?= htmlspecialchars($direccion['referencia'] ?? '') ?>"
                                placeholder="Ej: Frente al parque, cerca del mercado">
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" 
                                id="es_principal" 
                                name="es_principal" 
                                <?= $direccion['es_principal'] ? 'checked' : '' ?>>
                            <label for="es_principal">Establecer como dirección principal</label>
                        </div>

                        <div class="button-group">
                            <button type="submit" class="btn-guardar">
                                Guardar cambios
                            </button>
                            <a href="<?= url('/usuario/mis-direcciones') ?>" class="btn-cancelar">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../admin/includes/footer.php'; ?>
</body>
</html>
