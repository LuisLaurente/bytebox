<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/promocionEditar.css') ?>">

<body>
    <div class="_promEditar-admin-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        <div class="_promEditar-main-content">
            <div class="_promEditar-content-wrapper">
                <div class="_promEditar-promociones-container">
                    <div class="_promEditar-dashboard-header">
                        <h1 class="_promEditar-dashboard-title">Editar Promoción: <?= htmlspecialchars($promocion['nombre']) ?></h1>
                        <p class="_promEditar-dashboard-subtitle">Modifica la configuración de la promoción.</p>
                    </div>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="_promEditar-alert _promEditar-alert-error">
                            <?= htmlspecialchars($_SESSION['error']) ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="_promEditar-form-container">
                        <form method="POST" action="<?= url('promocion/actualizar/' . $promocion['id']) ?>" id="promocionForm">
                            <!-- SECCIÓN 1: INFORMACIÓN GENERAL -->
                            <div class="_promEditar-form-section">
                                <h3 class="_promEditar-section-title">1. Información General</h3>
                                <div class="_promEditar-form-grid">
                                    <div class="_promEditar-form-group">
                                        <label for="nombre" class="_promEditar-form-label">Nombre de la Promoción *</label>
                                        <input type="text" id="nombre" name="nombre" class="_promEditar-form-input" value="<?= htmlspecialchars($promocion['nombre']) ?>" required>
                                    </div>
                                    <div class="_promEditar-form-group">
                                        <label for="prioridad" class="_promEditar-form-label">Prioridad</label>
                                        <select id="prioridad" name="prioridad" class="_promEditar-form-select">
                                            <option value="1" <?= $promocion['prioridad'] == 1 ? 'selected' : '' ?>>1 - Muy Alta</option>
                                            <option value="2" <?= $promocion['prioridad'] == 2 ? 'selected' : '' ?>>2 - Alta</option>
                                            <option value="3" <?= $promocion['prioridad'] == 3 ? 'selected' : '' ?>>3 - Media</option>
                                            <option value="4" <?= $promocion['prioridad'] == 4 ? 'selected' : '' ?>>4 - Baja</option>
                                            <option value="5" <?= $promocion['prioridad'] == 5 ? 'selected' : '' ?>>5 - Muy Baja</option>
                                        </select>
                                    </div>
                                    <div class="_promEditar-form-group">
                                        <label for="fecha_inicio" class="_promEditar-form-label">Fecha de Inicio *</label>
                                        <input type="date" id="fecha_inicio" name="fecha_inicio" class="_promEditar-form-input" value="<?= $promocion['fecha_inicio'] ?>" required>
                                    </div>
                                    <div class="_promEditar-form-group">
                                        <label for="fecha_fin" class="_promEditar-form-label">Fecha de Fin *</label>
                                        <input type="date" id="fecha_fin" name="fecha_fin" class="_promEditar-form-input" value="<?= $promocion['fecha_fin'] ?>" required>
                                    </div>
                                </div>
                                <div class="_promEditar-form-checkbox-group">
                                    <div class="_promEditar-form-checkbox">
                                        <input type="checkbox" id="activo" name="activo" <?= $promocion['activo'] ? 'checked' : '' ?>>
                                        <label for="activo">Promoción activa</label>
                                    </div>
                                    <div class="_promEditar-form-checkbox">
                                        <input type="checkbox" id="acumulable" name="acumulable" <?= $promocion['acumulable'] ? 'checked' : '' ?>>
                                        <label for="acumulable">Acumulable con otras promociones</label>
                                    </div>
                                    <div class="_promEditar-form-checkbox">
                                        <input type="checkbox" id="exclusivo" name="exclusivo" <?= $promocion['exclusivo'] ? 'checked' : '' ?>>
                                        <label for="exclusivo">Promoción exclusiva (no se combina)</label>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: TIPO DE REGLA Y CONFIGURACIÓN -->
                            <div class="_promEditar-form-section">
                                <h3 class="_promEditar-section-title">2. Regla de Promoción</h3>
                                <div class="_promEditar-form-group">
                                    <label for="tipo_regla" class="_promEditar-form-label">Tipo de Regla *</label>
                                    <select id="tipo_regla" name="tipo_regla" class="_promEditar-form-select" required>
                                        <option value="">-- Selecciona una regla --</option>
                                        <option value="descuento_subtotal">Descuento % por monto mínimo</option>
                                        <option value="descuento_fijo_subtotal">Descuento fijo por monto mínimo</option>
                                        <option value="envio_gratis_primera_compra">Envío gratis primera compra</option>
                                        <option value="nxm_producto">Lleva N paga M (mismo producto)</option>
                                        <option value="descuento_enesima_unidad">Descuento en N-ésima unidad</option>
                                        <option value="descuento_menor_valor_categoria">Descuento producto más barato por categoría</option>
                                        <option value="nxm_general">Lleva N paga M (productos mixtos)</option>
                                        <option value="descuento_enesimo_producto">Descuento en N-ésimo producto más barato</option>
                                        <option value="envio_gratis_general">Envío gratis general</option>
                                        <option value="envio_gratis_monto_minimo">Envío gratis por monto mínimo</option>
                                    </select>
                                </div>
                                <div id="campos_dinamicos" class="_promEditar-dynamic-fields"></div>
                            </div>

                            <div class="_promEditar-form-buttons">
                                <button type="submit" class="_promEditar-btn-submit"> Actualizar Promoción</button>
                                <a href="<?= url('promocion/index') ?>" class="_promEditar-btn-cancel">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- DATOS DE LA PROMOCIÓN ---
            const promocion = <?= json_encode($promocion) ?>;

            // --- CONFIGURACIÓN INICIAL ---
            const fechaInicio = document.getElementById('fecha_inicio');
            const fechaFin = document.getElementById('fecha_fin');
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const todayStr = `${yyyy}-${mm}-${dd}`;

            fechaInicio.setAttribute('min', todayStr);
            fechaFin.setAttribute('min', todayStr);

            fechaInicio.addEventListener('change', () => {
                fechaFin.min = fechaInicio.value;
                if (fechaFin.value < fechaInicio.value) {
                    fechaFin.value = fechaInicio.value;
                }
            });

            const exclusivoCheckbox = document.getElementById('exclusivo');
            const acumulableCheckbox = document.getElementById('acumulable');
            if (exclusivoCheckbox.checked) {
                acumulableCheckbox.disabled = true;
            }
            exclusivoCheckbox.addEventListener('change', function() {
                acumulableCheckbox.disabled = this.checked;
                if (this.checked) {
                    acumulableCheckbox.checked = false;
                }
            });

            // --- LÓGICA DE FORMULARIO DINÁMICO ---
            const tipoReglaSelect = document.getElementById('tipo_regla');
            const camposDinamicosContainer = document.getElementById('campos_dinamicos');

            tipoReglaSelect.addEventListener('change', () => manejarCambioDeRegla());

            function determinarReglaActual() {
                const cond = promocion.condicion || {};
                const acc = promocion.accion || {};

                const tipoCondicion = cond.tipo || '';
                const tipoAccion = acc.tipo || '';

                // Mapeo completo de todas las reglas
                if (tipoCondicion === 'subtotal_minimo' && tipoAccion === 'descuento_porcentaje') return 'descuento_subtotal';
                if (tipoCondicion === 'primera_compra' && tipoAccion === 'envio_gratis') return 'envio_gratis_primera_compra';
                if (tipoCondicion === 'cantidad_producto_identico' && tipoAccion === 'compra_n_paga_m') return 'nxm_producto';
                if (tipoCondicion === 'cantidad_producto_identico' && tipoAccion === 'descuento_enesima_unidad') return 'descuento_enesima_unidad';
                if (tipoCondicion === 'cantidad_producto_categoria' && tipoAccion === 'descuento_menor_valor') return 'descuento_menor_valor_categoria';
                if (tipoCondicion === 'cantidad_total_productos' && tipoAccion === 'compra_n_paga_m_general') return 'nxm_general';
                if (tipoCondicion === 'cantidad_total_productos' && tipoAccion === 'descuento_producto_mas_barato') return 'descuento_enesimo_producto';
                if (tipoCondicion === 'todos' && tipoAccion === 'envio_gratis') return 'envio_gratis_general';
                if (tipoCondicion === 'subtotal_minimo' && tipoAccion === 'envio_gratis') return 'envio_gratis_monto_minimo';
                if (tipoCondicion === 'subtotal_minimo' && tipoAccion === 'descuento_fijo') return 'descuento_fijo_subtotal';

                return '';
            }

            function manejarCambioDeRegla() {
                const regla = tipoReglaSelect.value;
                camposDinamicosContainer.innerHTML = '';

                const cond = promocion.condicion || {};
                const acc = promocion.accion || {};

                const tipoCondicionInput = document.createElement('input');
                tipoCondicionInput.type = 'hidden';
                tipoCondicionInput.name = 'tipo_condicion';

                const tipoAccionInput = document.createElement('input');
                tipoAccionInput.type = 'hidden';
                tipoAccionInput.name = 'tipo_accion';

                switch (regla) {
                    case 'descuento_subtotal':
                        tipoCondicionInput.value = 'subtotal_minimo';
                        tipoAccionInput.value = 'descuento_porcentaje';
                        camposDinamicosContainer.innerHTML = `
                        <div class="_promEditar-form-grid">
                            <div class="_promEditar-form-group">
                                <label class="_promEditar-form-label">Monto mínimo del carrito (S/)</label>
                                <input type="number" name="cond_subtotal_minimo" class="_promEditar-form-input" value="${cond.valor || ''}" required min="0" step="0.01">
                            </div>
                            <div class="_promEditar-form-group">
                                <label class="_promEditar-form-label">Porcentaje de descuento (%)</label>
                                <input type="number" name="accion_valor_descuento" class="_promEditar-form-input" value="${acc.valor || ''}" required min="0" max="100" step="0.01">
                            </div>
                        </div>`;
                        break;

                    case 'envio_gratis_primera_compra':
                        tipoCondicionInput.value = 'primera_compra';
                        tipoAccionInput.value = 'envio_gratis';
                        camposDinamicosContainer.innerHTML = `<p class="_promEditar-info-text">Esta regla no necesita configuración adicional.</p>`;
                        break;

                    case 'nxm_producto':
                        tipoCondicionInput.value = 'cantidad_producto_identico';
                        tipoAccionInput.value = 'compra_n_paga_m';
                        camposDinamicosContainer.innerHTML = `
                    <p class="_promEditar-info-text">Aplica para un producto específico. Ej: Lleva 3, Paga 2.</p>
                    <div class="_promEditar-form-grid">
                        
                        <div class="_promEditar-form-group _promEditar-relative">
                            <label class="_promEditar-form-label">Buscar nuevo producto (para reemplazar)</label>
                            <input type="text" id="producto_nombre" class="_promEditar-form-input" placeholder="Buscar producto..." autocomplete="off"
                                value="${cond.producto_nombre || ''}">
                            <input type="hidden" name="cond_producto_id" id="producto_id" value="${cond.producto_id || ''}">
                            <ul id="lista_productos" class="_promEditar-autocomplete-list"></ul>
                        </div>
                        <div class="_promEditar-form-group">
                            <label class="_promEditar-form-label">Cantidad que lleva (N)</label>
                            <input type="number" name="accion_cantidad_lleva" class="_promEditar-form-input" value="${acc.cantidad_lleva || ''}" required min="2">
                        </div>
                        <div class="_promEditar-form-group">
                            <label class="_promEditar-form-label">Cantidad que paga (M)</label>
                            <input type="number" name="accion_cantidad_paga" class="_promEditar-form-input" value="${acc.cantidad_paga || ''}" required min="1">
                        </div>
                    </div>`;
                        inicializarBuscadorProducto();
                        break;


                    case 'descuento_enesima_unidad':
                        tipoCondicionInput.value = 'cantidad_producto_identico';
                        tipoAccionInput.value = 'descuento_enesima_unidad';
                        camposDinamicosContainer.innerHTML = `
                    <p class="_promEditar-info-text">Aplica un descuento a una unidad específica. Ej: 50% en la 3ra unidad.</p>
                    <div class="_promEditar-form-grid">
                        <div class="_promEditar-form-group _promEditar-relative">
                            <label class="_promEditar-form-label">Buscar nuevo producto (para reemplazar)</label>
                            <input type="text" id="producto_nombre" class="_promEditar-form-input" placeholder="Buscar producto..." autocomplete="off"
                                value="${cond.producto_nombre || ''}">
                            <input type="hidden" name="cond_producto_id" id="producto_id" value="${cond.producto_id || ''}">
                            <ul id="lista_productos" class="_promEditar-autocomplete-list"></ul>
                        </div>
                        <div class="_promEditar-form-group">
                            <label class="_promEditar-form-label">N-ésima unidad a descontar</label>
                            <input type="number" name="accion_numero_unidad" class="_promEditar-form-input"
                                value="${acc.numero_unidad || ''}" required min="2">
                        </div>
                        <div class="_promEditar-form-group">
                            <label class="_promEditar-form-label">Porcentaje de descuento (%)</label>
                            <input type="number" name="accion_descuento_unidad" class="_promEditar-form-input"
                                value="${acc.descuento_unidad || ''}" required min="0" max="100" step="0.01">
                        </div>
                    </div>`;
                        inicializarBuscadorProducto();
                        break;

                    case 'descuento_menor_valor_categoria':
                        tipoCondicionInput.value = 'cantidad_producto_categoria';
                        tipoAccionInput.value = 'descuento_menor_valor';
                        camposDinamicosContainer.innerHTML = `
                    <p class="_promEditar-info-text">Aplica un descuento al producto más barato dentro de una categoría seleccionada.</p>
                    <div class="_promEditar-form-grid">
                        <div class="_promEditar-form-group _promEditar-relative">
                            <label class="_promEditar-form-label">Categoría</label>
                            <input type="text" id="categoria_nombre" class="_promEditar-form-input" placeholder="Buscar categoría..." autocomplete="off"
                                value="${cond.categoria_nombre || ''}">
                            <input type="hidden" name="cond_categoria_id" id="categoria_id" value="${cond.categoria_id || ''}">
                            <ul id="lista_categorias" class="_promEditar-autocomplete-list"></ul>
                        </div>
                        <div class="_promEditar-form-group">
                            <label class="_promEditar-form-label">Cantidad mínima de productos</label>
                            <input type="number" name="cond_cantidad_min_categoria" class="_promEditar-form-input"
                                value="${cond.cantidad_min || ''}" required min="2">
                        </div>
                        <div class="_promEditar-form-group">
                            <label class="_promEditar-form-label">Porcentaje de descuento (%)</label>
                            <input type="number" name="accion_valor" class="_promEditar-form-input"
                                value="${acc.valor || ''}" required min="0" max="100" step="0.01">
                        </div>
                    </div>`;
                        inicializarBuscadorCategoria();
                        break;


                    case 'nxm_general':
                        tipoCondicionInput.value = 'cantidad_total_productos';
                        tipoAccionInput.value = 'compra_n_paga_m_general';
                        camposDinamicosContainer.innerHTML = `
                        <p class="_promEditar-info-text">Aplica para cualquier combinación de productos. Ej: Lleva 3, Paga 2 (el de menor valor gratis).</p>
                        <div class="_promEditar-form-grid">
                            <div class="_promEditar-form-group">
                                <label class="_promEditar-form-label">Cantidad mínima de productos</label>
                                <input type="number" name="cond_cantidad_total" class="_promEditar-form-input" value="${cond.cantidad_min || ''}" required min="2">
                            </div>
                            <div class="_promEditar-form-group">
                                <label class="_promEditar-form-label">Cantidad que lleva (N)</label>
                                <input type="number" name="accion_cantidad_lleva_general" class="_promEditar-form-input" value="${acc.cantidad_lleva || ''}" required min="2">
                            </div>
                            <div class="_promEditar-form-group">
                                <label class="_promEditar-form-label">Cantidad que paga (M)</label>
                                <input type="number" name="accion_cantidad_paga_general" class="_promEditar-form-input" value="${acc.cantidad_paga || ''}" required min="1">
                            </div>
                        </div>`;
                        break;

                    case 'descuento_enesimo_producto':
                        tipoCondicionInput.value = 'cantidad_total_productos';
                        tipoAccionInput.value = 'descuento_producto_mas_barato';
                        camposDinamicosContainer.innerHTML = `
                        <p class="_promEditar-info-text">Aplica un descuento al producto más barato al llevar una cantidad mínima de productos.</p>
                        <div class="_promEditar-form-grid">
                            <div class="_promEditar-form-group">
                                <label class="_promEditar-form-label">Cantidad mínima de productos</label>
                                <input type="number" name="cond_cantidad_total" class="_promEditar-form-input" value="${cond.cantidad_min || ''}" required min="2">
                            </div>
                            <div class="_promEditar-form-group">
                                <label class="_promEditar-form-label">Porcentaje de descuento (%)</label>
                                <input type="number" name="accion_descuento_porcentaje" class="_promEditar-form-input" value="${acc.valor || ''}" required min="0" max="100" step="0.01">
                            </div>
                        </div>`;
                        break;

                    case 'envio_gratis_general':
                        tipoCondicionInput.value = 'todos';
                        tipoAccionInput.value = 'envio_gratis';
                        camposDinamicosContainer.innerHTML = `<p class="_promEditar-info-text">Envío gratis aplica a todos los pedidos sin condiciones.</p>`;
                        break;

                    case 'envio_gratis_monto_minimo':
                        tipoCondicionInput.value = 'subtotal_minimo';
                        tipoAccionInput.value = 'envio_gratis';
                        camposDinamicosContainer.innerHTML = `
                        <div class="_promEditar-form-group">
                            <label class="_promEditar-form-label">Monto mínimo del carrito (S/)</label>
                            <input type="number" name="cond_subtotal_minimo" class="_promEditar-form-input" value="${cond.valor || ''}" required min="0" step="0.01">
                        </div>`;
                        break;

                    case 'descuento_fijo_subtotal':
                        tipoCondicionInput.value = 'subtotal_minimo';
                        tipoAccionInput.value = 'descuento_fijo';
                        camposDinamicosContainer.innerHTML = `
                        <div class="_promEditar-form-grid">
                            <div class="_promEditar-form-group">
                                <label class="_promEditar-form-label">Monto mínimo del carrito (S/)</label>
                                <input type="number" name="cond_subtotal_minimo" class="_promEditar-form-input" value="${cond.valor || ''}" min="0" step="0.01" required>
                            </div>
                            <div class="_promEditar-form-group">
                                <label class="_promEditar-form-label">Monto de descuento fijo (S/)</label>
                                <input type="number" name="accion_valor_descuento_fijo" class="_promEditar-form-input" value="${acc.valor || ''}" min="0" step="0.01" required>
                            </div>
                        </div>`;
                        break;

                    default:
                        tipoCondicionInput.value = promocion.tipo || 'general';
                        tipoAccionInput.value = acc.tipo || '';
                        camposDinamicosContainer.innerHTML = `<p class="_promEditar-info-text">Regla personalizada. No se puede editar desde este formulario.</p>`;
                        break;
                }

                camposDinamicosContainer.prepend(tipoAccionInput);
                camposDinamicosContainer.prepend(tipoCondicionInput);
            }

            // Validación del formulario
            document.getElementById('promocionForm').addEventListener('submit', function(e) {
                const tipoRegla = document.getElementById('tipo_regla').value;
                if (!tipoRegla) {
                    e.preventDefault();
                    alert('Por favor, selecciona un tipo de regla para la promoción.');
                    return false;
                }
            });

            // --- INICIALIZACIÓN DEL FORMULARIO AL CARGAR ---
            const reglaActual = determinarReglaActual();
            if (reglaActual) {
                tipoReglaSelect.value = reglaActual;
                manejarCambioDeRegla();
            } else {
                console.warn('No se pudo determinar la regla actual para la promoción:', promocion);
            }
        });
        // --- FUNCIONES DE AUTOCOMPLETADO ---
        function inicializarBuscadorProducto() {
            const input = document.getElementById('producto_nombre');
            const hidden = document.getElementById('producto_id');
            const lista = document.getElementById('lista_productos');
            if (!input) return;

            input.addEventListener('input', async () => {
                const termino = input.value.trim();
                if (termino.length < 2) {
                    lista.innerHTML = '';
                    return;
                }

                try {
                    const res = await fetch('<?= url("producto/autocomplete") ?>?q=' + encodeURIComponent(termino));
                    const data = await res.json();
                    lista.innerHTML = data.map(p => `<li data-id="${p.id}" class="_promEditar-autocomplete-item">${p.nombre}</li>`).join('');
                } catch (err) {
                    console.error(err);
                }
            });

            lista.addEventListener('click', e => {
                if (e.target.classList.contains('_promEditar-autocomplete-item')) {
                    input.value = e.target.textContent;
                    hidden.value = e.target.dataset.id;
                    lista.innerHTML = '';
                }
            });
        }

        function inicializarBuscadorCategoria() {
            const input = document.getElementById('categoria_nombre');
            const hidden = document.getElementById('categoria_id');
            const lista = document.getElementById('lista_categorias');
            if (!input) return;

            input.addEventListener('input', async () => {
                const termino = input.value.trim();
                if (termino.length < 2) {
                    lista.innerHTML = '';
                    return;
                }

                try {
                    const res = await fetch('<?= url("categoria/buscarPorNombre") ?>?q=' + encodeURIComponent(termino));
                    const data = await res.json();
                    lista.innerHTML = data.map(c => `<li data-id="${c.id}" class="_promEditar-autocomplete-item">${c.nombre}</li>`).join('');
                } catch (err) {
                    console.error(err);
                }
            });

            lista.addEventListener('click', e => {
                if (e.target.classList.contains('_promEditar-autocomplete-item')) {
                    input.value = e.target.textContent;
                    hidden.value = e.target.dataset.id;
                    lista.innerHTML = '';
                }
            });
        }
    </script>
</body>

</html>