<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/cuponCrear.css') ?>">

<body>
    <div class="layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        <div class="main-content">
            <main class="content">
                <div class="cupon-admin-container">
                    <div class="cupon-admin">
                        <!-- Header -->
                        <div class="cupon-header">
                            <h1>Crear Nuevo Cupón</h1>
                            <a href="<?= url('cupon') ?>" class="btn-secondary">
                                ← Volver a la lista
                            </a>
                        </div>

                        <!-- Alertas de error general -->
                        <?php if (isset($error)): ?>
                            <div class="alert alert-error">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario -->
                        <form method="POST" action="<?= url('cupon/guardar') ?>" class="formulario-cupon">
                            <h2>Información del Cupón</h2>

                            <!-- Código y Tipo -->
                            <div class="form-row">
                                <div class="form-group <?= isset($errores['codigo']) ? 'has-error' : '' ?>">
                                    <label for="codigo">Código del Cupón *</label>
                                    <input type="text"
                                        id="codigo"
                                        name="codigo"
                                        value="<?= htmlspecialchars($codigo ?? '') ?>"
                                        placeholder="Ej: DESCUENTO20"
                                        maxlength="20"
                                        required>
                                    <?php if (isset($errores['codigo'])): ?>
                                        <span class="error"><?= htmlspecialchars($errores['codigo']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group <?= isset($errores['tipo']) ? 'has-error' : '' ?>">
                                    <label for="tipo">Tipo de Descuento *</label>
                                    <select id="tipo" name="tipo" required onchange="toggleValorInput()">
                                        <option value="">Seleccionar tipo</option>
                                        <option value="descuento_porcentaje" <?= isset($tipo) && $tipo === 'descuento_porcentaje' ? 'selected' : '' ?>>
                                            Porcentaje (%)
                                        </option>
                                        <option value="descuento_fijo" <?= isset($tipo) && $tipo === 'descuento_fijo' ? 'selected' : '' ?>>
                                            Monto Fijo (S/)
                                        </option>
                                        <option value="envio_gratis" <?= isset($tipo) && $tipo === 'envio_gratis' ? 'selected' : '' ?>>
                                            Envío Gratis
                                        </option>
                                    </select>
                                    <?php if (isset($errores['tipo'])): ?>
                                        <span class="error"><?= htmlspecialchars($errores['tipo']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Valor y Monto mínimo -->
                            <div class="form-row">
                                <div class="form-group <?= isset($errores['valor']) ? 'has-error' : '' ?>">
                                    <label for="valor">
                                        <span id="valor-label">Valor del Descuento *</span>
                                    </label>
                                    <input type="number"
                                        id="valor"
                                        name="valor"
                                        value="<?= htmlspecialchars($valor ?? '') ?>"
                                        step="0.01"
                                        min="0"
                                        required>
                                    <?php if (isset($errores['valor'])): ?>
                                        <span class="error"><?= htmlspecialchars($errores['valor']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group <?= isset($errores['monto_minimo']) ? 'has-error' : '' ?>">
                                    <label for="monto_minimo">Monto Mínimo de Compra (S/)</label>
                                    <input type="number"
                                        id="monto_minimo"
                                        name="monto_minimo"
                                        value="<?= htmlspecialchars($monto_minimo ?? '') ?>"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00">
                                    <?php if (isset($errores['monto_minimo'])): ?>
                                        <span class="error"><?= htmlspecialchars($errores['monto_minimo']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Fechas -->
                            <div class="form-row">
                                <div class="form-group <?= isset($errores['fecha_inicio']) ? 'has-error' : '' ?>">
                                    <label for="fecha_inicio">Fecha de Inicio *</label>
                                    <input type="date"
                                        id="fecha_inicio"
                                        name="fecha_inicio"
                                        value="<?= htmlspecialchars($fecha_inicio ?? date('Y-m-d')) ?>"
                                        required>
                                    <?php if (isset($errores['fecha_inicio'])): ?>
                                        <span class="error"><?= htmlspecialchars($errores['fecha_inicio']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group <?= isset($errores['fecha_fin']) ? 'has-error' : '' ?>">
                                    <label for="fecha_fin">Fecha de Fin *</label>
                                    <input type="date"
                                        id="fecha_fin"
                                        name="fecha_fin"
                                        value="<?= htmlspecialchars($fecha_fin ?? '') ?>"
                                        required>
                                    <?php if (isset($errores['fecha_fin'])): ?>
                                        <span class="error"><?= htmlspecialchars($errores['fecha_fin']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Límites -->
                            <h2>Configuración de Límites</h2>

                            <div class="form-row">
                                <div class="form-group <?= isset($errores['limite_uso']) ? 'has-error' : '' ?>">
                                    <label for="limite_uso">Límite Total de Usos</label>
                                    <input type="number"
                                        id="limite_uso"
                                        name="limite_uso"
                                        value="<?= htmlspecialchars($limite_uso ?? '') ?>"
                                        min="1"
                                        placeholder="Sin límite">
                                    <small class="help-text">
                                        Deja vacío para uso ilimitado
                                    </small>
                                    <?php if (isset($errores['limite_uso'])): ?>
                                        <span class="error"><?= htmlspecialchars($errores['limite_uso']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group <?= isset($errores['limite_por_usuario']) ? 'has-error' : '' ?>">
                                    <label for="limite_por_usuario">Límite por Usuario</label>
                                    <input type="number"
                                        id="limite_por_usuario"
                                        name="limite_por_usuario"
                                        value="<?= htmlspecialchars($limite_por_usuario ?? '') ?>"
                                        min="1"
                                        placeholder="Sin límite">
                                    <small class="help-text">
                                        Máximo de veces que un usuario puede usar este cupón
                                    </small>
                                    <?php if (isset($errores['limite_por_usuario'])): ?>
                                        <span class="error"><?= htmlspecialchars($errores['limite_por_usuario']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Usuarios autorizados -->
                            <div class="form-group">
                                <label for="usuarios_autorizados">Usuarios Autorizados (opcional)</label>
                                <textarea id="usuarios_autorizados"
                                    name="usuarios_autorizados"
                                    rows="3"
                                    placeholder="IDs de usuarios separados por comas (ej: 1,2,3). Deja vacío para todos los usuarios."><?= htmlspecialchars($usuarios_autorizados ?? '') ?></textarea>
                                <small class="help-text">
                                    Si especificas usuarios, solo ellos podrán usar este cupón
                                </small>
                            </div>

                            <!-- Configuración de Aplicabilidad -->
                            <h2>Configuración de Aplicabilidad</h2>

                            <!-- Categorías aplicables -->
                            <div class="form-group">
                                <label>Aplicable a categorías:</label>
                                <div class="checkbox-group">
                                    <input type="checkbox"
                                        id="todas_categorias"
                                        name="aplicar_todas_categorias"
                                        value="1"
                                        <?= !isset($categorias_aplicables) || empty($categorias_aplicables) ? 'checked' : '' ?>
                                        onchange="toggleCategorias()">
                                    <label for="todas_categorias">Todas las categorías</label>
                                </div>
                                <div id="categorias_container" class="categorias-container">
                                    <select name="categorias_aplicables[]" multiple size="6">
                                        <?php if (isset($categorias) && !empty($categorias)): ?>
                                            <?php
                                            $categoriasSeleccionadas = isset($categorias_aplicables) ? json_decode($categorias_aplicables, true) : [];
                                            ?>
                                            <?php foreach ($categorias as $categoria): ?>
                                                <option value="<?= $categoria['id'] ?>"
                                                    <?= is_array($categoriasSeleccionadas) && in_array($categoria['id'], $categoriasSeleccionadas) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <small class="help-text">
                                        Mantén Ctrl presionado para seleccionar múltiples categorías
                                    </small>
                                </div>
                            </div>

                            <!-- Público objetivo -->
                            <div class="form-group">
                                <label for="publico_objetivo">Público objetivo:</label>
                                <select name="publico_objetivo" id="publico_objetivo">
                                    <option value="todos" <?= (!isset($publico_objetivo) || $publico_objetivo === 'todos') ? 'selected' : '' ?>>
                                        Todos los usuarios
                                    </option>
                                    <option value="nuevos" <?= isset($publico_objetivo) && $publico_objetivo === 'nuevos' ? 'selected' : '' ?>>
                                        Solo usuarios nuevos (sin pedidos previos)
                                    </option>
                                    <option value="usuarios_especificos" <?= isset($publico_objetivo) && $publico_objetivo === 'usuarios_especificos' ? 'selected' : '' ?>>
                                        Usuarios específicos (usar campo de usuarios autorizados)
                                    </option>
                                </select>
                                <small class="help-text">
                                    Define a quién va dirigido este cupón
                                </small>
                            </div>

                            <!-- Acumulable con promociones -->
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox"
                                        id="acumulable_promociones"
                                        name="acumulable_promociones"
                                        value="1"
                                        <?= !isset($acumulable_promociones) || $acumulable_promociones ? 'checked' : '' ?>>
                                    <label for="acumulable_promociones">Acumulable con otras promociones</label>
                                </div>
                                <small class="help-text">
                                    Si está marcado, se puede usar junto con otras promociones activas
                                </small>
                            </div>

                            <!-- Estado -->
                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox"
                                        id="activo"
                                        name="activo"
                                        value="1"
                                        <?= isset($activo) && $activo ? 'checked' : 'checked' ?>>
                                    <label for="activo">Cupón activo</label>
                                </div>
                                <small class="help-text">
                                    Los cupones inactivos no pueden ser utilizados
                                </small>
                            </div>

                            <!-- Botones -->
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    Crear Cupón
                                </button>
                                <a href="<?= url('cupon') ?>" class="btn-secondary">
                                    Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Función para cambiar el label del valor según el tipo
        function toggleValorInput() {
            const tipo = document.getElementById('tipo').value;
            const valorLabel = document.getElementById('valor-label');
            const valorInput = document.getElementById('valor');

            if (tipo === 'descuento_porcentaje') {
                valorLabel.textContent = 'Porcentaje de Descuento (%) *';
                valorInput.setAttribute('max', '100');
                valorInput.setAttribute('placeholder', 'Ej: 20');
            } else if (tipo === 'descuento_fijo') {
                valorLabel.textContent = 'Monto de Descuento (S/) *';
                valorInput.removeAttribute('max');
                valorInput.setAttribute('placeholder', 'Ej: 50.00');
            } else {
                valorLabel.textContent = 'Valor del Descuento *';
                valorInput.removeAttribute('max');
                valorInput.setAttribute('placeholder', '');
            }
        }

        // Función para toggle de categorías
        function toggleCategorias() {
            const checkbox = document.getElementById('todas_categorias');
            const container = document.getElementById('categorias_container');
            const select = container.querySelector('select');

            if (checkbox.checked) {
                container.style.display = 'none';
                // Limpiar selecciones
                for (let option of select.options) {
                    option.selected = false;
                }
            } else {
                container.style.display = 'block';
            }
        }

        // Validación de fechas
        document.getElementById('fecha_inicio').addEventListener('change', function() {
            const fechaInicio = this.value;
            const fechaFin = document.getElementById('fecha_fin');

            if (fechaInicio) {
                fechaFin.setAttribute('min', fechaInicio);

                // Si la fecha fin es anterior a la de inicio, limpiarla
                if (fechaFin.value && fechaFin.value < fechaInicio) {
                    fechaFin.value = '';
                }
            }
        });

        // Auto-generar código basado en tipo y valor
        document.getElementById('tipo').addEventListener('change', generateCode);
        document.getElementById('valor').addEventListener('input', generateCode);

        function generateCode() {
            const codigo = document.getElementById('codigo');
            if (codigo.value) return; // No sobrescribir si ya hay código

            const tipo = document.getElementById('tipo').value;
            const valor = document.getElementById('valor').value;

            if (tipo && valor) {
                if (tipo === 'descuento_porcentaje') {
                    codigo.value = `DESC${valor}PCT`;
                } else if (tipo === 'descuento_fijo') {
                    codigo.value = `DESC${valor}SOL`;
                } else {
                    codigo.value = `ENVIOGRATIS`;
                }
            }
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            toggleValorInput();

            // Configurar fecha mínima
            const fechaInicio = document.getElementById('fecha_inicio');
            const hoy = new Date().toISOString().split('T')[0];
            fechaInicio.setAttribute('min', hoy);
        });
    </script>
</body>
</html>