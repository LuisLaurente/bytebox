<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/promocionCrear.css') ?>">

<body>
    <div class="_promCrear-admin-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        <div class="_promCrear-main-content">
            <main class="_promCrear-content">
                <div class="_promCrear-page-container">
                    <div class="_promCrear-container">
                        <div class="_promCrear-dashboard-header">
                            <h1 class="_promCrear-dashboard-title">Crear Nueva Promoción</h1>
                            <p class="_promCrear-dashboard-subtitle">Configura una nueva promoción, descuento u oferta especial.</p>
                        </div>

                        <!-- Mensajes de error -->
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="_promCrear-alert _promCrear-alert-error">
                                <?= htmlspecialchars($_SESSION['error']) ?>
                                <?php unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="_promCrear-form-container">
                            <form method="POST" action="<?= url('promocion/guardar') ?>" id="promocionForm">
                                <!-- SECCIÓN 1: INFORMACIÓN GENERAL -->
                                <div class="_promCrear-form-section">
                                    <h3 class="_promCrear-section-title">1. Información General</h3>
                                    <div class="_promCrear-form-grid">
                                        <div class="_promCrear-form-group">
                                            <label for="nombre" class="_promCrear-form-label">Nombre de la Promoción *</label>
                                            <input type="text" id="nombre" name="nombre" class="_promCrear-form-input" required
                                                placeholder="Ej: 20% de descuento en compras mayores a S/100">
                                        </div>
                                        <div class="_promCrear-form-group">
                                            <label for="prioridad" class="_promCrear-form-label">Prioridad</label>
                                            <select id="prioridad" name="prioridad" class="_promCrear-form-select">
                                                <option value="1">1 - Muy Alta</option>
                                                <option value="2">2 - Alta</option>
                                                <option value="3" selected>3 - Media</option>
                                                <option value="4">4 - Baja</option>
                                                <option value="5">5 - Muy Baja</option>
                                            </select>
                                        </div>
                                        <div class="_promCrear-form-group">
                                            <label for="fecha_inicio" class="_promCrear-form-label">Fecha de Inicio *</label>
                                            <input type="date" id="fecha_inicio" name="fecha_inicio" class="_promCrear-form-input" required>
                                        </div>
                                        <div class="_promCrear-form-group">
                                            <label for="fecha_fin" class="_promCrear-form-label">Fecha de Fin *</label>
                                            <input type="date" id="fecha_fin" name="fecha_fin" class="_promCrear-form-input" required>
                                        </div>
                                    </div>
                                    <div class="_promCrear-form-grid-small">
                                        <div class="_promCrear-form-checkbox">
                                            <input type="checkbox" id="activo" name="activo" checked>
                                            <label for="activo">Promoción activa</label>
                                        </div>
                                        <div class="_promCrear-form-checkbox">
                                            <input type="checkbox" id="acumulable" name="acumulable" checked>
                                            <label for="acumulable">Acumulable con otras promociones</label>
                                        </div>
                                        <div class="_promCrear-form-checkbox">
                                            <input type="checkbox" id="exclusivo" name="exclusivo">
                                            <label for="exclusivo">Promoción exclusiva (no se combina)</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECCIÓN 2: TIPO DE REGLA Y CONFIGURACIÓN -->
                                <div class="_promCrear-form-section">
                                    <h3 class="_promCrear-section-title">2. Regla de Promoción</h3>
                                    <div class="_promCrear-form-group">
                                        <label for="tipo_regla" class="_promCrear-form-label">Tipo de Regla *</label>
                                        <select id="tipo_regla" name="tipo_regla" class="_promCrear-form-select" required>
                                            <option value="">-- Selecciona una regla --</option>
                                            <option value="descuento_subtotal">Descuento % por monto mínimo</option>
                                            <option value="descuento_fijo_subtotal">Descuento fijo por monto mínimo</option>
                                            <option value="envio_gratis_primera_compra">Envío gratis primera compra</option>
                                            <option value="nxm_producto">Lleva N paga M (mismo producto)</option>
                                            <option value="descuento_enesima_unidad">Descuento en N-ésima unidad</option>
                                            <option value="descuento_menor_valor_categoria">Descuento producto más barato por categorías</option>
                                            <option value="nxm_general">Lleva N paga M (productos mixtos)</option>
                                            <option value="descuento_enesimo_producto">Descuento en N-ésimo producto más barato</option>
                                            <option value="envio_gratis_general">Envío gratis general</option>
                                            <option value="envio_gratis_monto_minimo">Envío gratis por monto mínimo</option>
                                        </select>
                                    </div>
                                    <div id="campos_dinamicos" class="_promCrear-dynamic-fields"></div>
                                </div>

                                <div class="_promCrear-form-buttons">
                                    <button type="submit" class="_promCrear-btn-submit"> Crear Promoción</button>
                                    <a href="<?= url('promocion/index') ?>" class="_promCrear-btn-cancel">Cancelar</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                if (fechaFin.value < fechaInicio.value) fechaFin.value = fechaInicio.value;
            });

            const exclusivoCheckbox = document.getElementById('exclusivo');
            const acumulableCheckbox = document.getElementById('acumulable');

            exclusivoCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    acumulableCheckbox.checked = false;
                    acumulableCheckbox.disabled = true;
                } else {
                    acumulableCheckbox.disabled = false;
                }
            });

            // --- LÓGICA DE FORMULARIO DINÁMICO ---
            const tipoReglaSelect = document.getElementById('tipo_regla');
            const camposDinamicosContainer = document.getElementById('campos_dinamicos');
            const form = document.getElementById('promocionForm');

            tipoReglaSelect.addEventListener('change', manejarCambioDeRegla);

            // Debounce util
            function debounce(fn, wait = 250) {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), wait);
                };
            }

            // HTML-escape helper
            function escapeHtml(s) {
                if (!s) return '';
                return String(s).replace(/[&<>"']/g, function(m) {
                    return ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    })[m];
                });
            }

            function manejarCambioDeRegla() {
                const regla = tipoReglaSelect.value;
                camposDinamicosContainer.innerHTML = '';

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
                        <div class="_promCrear-form-grid">
                            <div class="_promCrear-form-group">
                                <label class="_promCrear-form-label">Monto mínimo del carrito (S/)</label>
                                <input type="number" name="cond_subtotal_minimo" class="_promCrear-form-input" min="0" step="0.01" required>
                            </div>
                            <div class="_promCrear-form-group">
                                <label class="_promCrear-form-label">Porcentaje de descuento (%)</label>
                                <input type="number" name="accion_valor_descuento" class="_promCrear-form-input" min="0" max="100" step="0.01" required>
                            </div>
                        </div>`;
                        break;

                    case 'envio_gratis_primera_compra':
                        tipoCondicionInput.value = 'primera_compra';
                        tipoAccionInput.value = 'envio_gratis';
                        camposDinamicosContainer.innerHTML = `<p class="_promCrear-info-text">Esta regla no necesita configuración adicional.</p>`;
                        break;
                    case 'nxm_producto':
                        tipoCondicionInput.value = 'cantidad_producto_identico';
                        tipoAccionInput.value = 'compra_n_paga_m';
                        camposDinamicosContainer.innerHTML = `
                            <p class="_promCrear-info-text">Aplica para un producto específico. Ej: Lleva 3, Paga 2.</p>
                            <div class="_promCrear-form-grid">
                                <div class="_promCrear-form-group">
                                    <label class="_promCrear-form-label">Buscar Producto *</label>
                                    <input type="text" id="buscarProductoNXM" class="_promCrear-form-input" placeholder="Escribe el nombre del producto..." autocomplete="off" required>
                                    <ul id="listaProductosNXM" class="_promCrear-autocomplete-list"></ul>
                                    <input type="hidden" name="cond_producto_id" id="productoIdSeleccionadoNXM" required>
                                    <div id="producto-seleccionado-nxm" class="_promCrear-producto-seleccionado"></div>
                                </div>

                                <div class="_promCrear-form-group">
                                    <label class="_promCrear-form-label">Cantidad que lleva (N) *</label>
                                    <input type="number" name="accion_cantidad_lleva" class="_promCrear-form-input" min="2" required>
                                </div>

                                <div class="_promCrear-form-group">
                                    <label class="_promCrear-form-label">Cantidad que paga (M) *</label>
                                    <input type="number" name="accion_cantidad_paga" class="_promCrear-form-input" min="1" required>
                                </div>
                            </div>
                        `;

                        // ✅ AGREGAR VALIDACIÓN EXPLÍCITA
                        setTimeout(() => {
                            const productoHidden = document.getElementById('productoIdSeleccionadoNXM');
                            const buscarInput = document.getElementById('buscarProductoNXM');

                            if (productoHidden && buscarInput) {
                                buscarInput.addEventListener('input', function() {
                                    if (this.value.trim() === '') {
                                        productoHidden.value = '';
                                    }
                                });
                            }
                        }, 100);

                        // --- LÓGICA DEL AUTOCOMPLETE (modelo del caso 'descuento_enesima_unidad') ---
                        (function initAutocompleteForNxM() {
                            const inputBuscar = camposDinamicosContainer.querySelector('#buscarProductoNXM');
                            const lista = camposDinamicosContainer.querySelector('#listaProductosNXM');
                            const inputHidden = camposDinamicosContainer.querySelector('#productoIdSeleccionadoNXM');

                            if (!inputBuscar || !lista || !inputHidden) return;

                            let items = [];
                            let focusedIndex = -1;

                            // Si el usuario escribe, borrar selección previa y buscar
                            inputBuscar.addEventListener('input', () => {
                                inputHidden.value = '';
                                focusedIndex = -1;
                                doSearch(inputBuscar.value);
                            });

                            // Debounced search (reusa la función debounce que ya definiste)
                            const doSearch = debounce(async (q) => {
                                lista.innerHTML = '';
                                lista.classList.add('_promCrear-hidden');
                                if (!q || q.trim().length < 2) return;

                                try {
                                    const res = await fetch('<?= htmlspecialchars(url("producto/autocomplete")) ?>?q=' + encodeURIComponent(q.trim()));
                                    if (!res.ok) throw new Error('Network response was not OK');
                                    const productos = await res.json();
                                    items = productos || [];
                                    renderList(items);
                                } catch (err) {
                                    console.error('Autocomplete NXM error:', err);
                                    lista.innerHTML = '';
                                    lista.classList.add('_promCrear-hidden');
                                }
                            }, 220);

                            function renderList(productos) {
                                lista.innerHTML = '';
                                focusedIndex = -1;
                                if (!productos || productos.length === 0) {
                                    lista.classList.add('_promCrear-hidden');
                                    return;
                                }
                                productos.forEach((p, idx) => {
                                    const li = document.createElement('li');
                                    li.tabIndex = 0;
                                    li.className = '_promCrear-autocomplete-item';
                                    li.dataset.id = p.id;
                                    li.dataset.idx = idx;
                                    // mostrar nombre y sku si existe (usa escapeHtml definida arriba)
                                    li.innerHTML = `<strong>${escapeHtml(p.nombre)}</strong>${p.sku ? ' <small>(' + escapeHtml(p.sku) + ')</small>' : ''}`;
                                    li.addEventListener('click', () => selectProduct(p));
                                    li.addEventListener('keydown', (ev) => {
                                        if (ev.key === 'Enter') selectProduct(p);
                                    });
                                    lista.appendChild(li);
                                });
                                lista.classList.remove('_promCrear-hidden');
                            }

                            function selectProduct(p) {
                                inputBuscar.value = p.nombre;
                                inputHidden.value = p.id;
                                lista.innerHTML = '';
                                lista.classList.add('_promCrear-hidden');
                            }

                            // Navegación por teclado en el input
                            inputBuscar.addEventListener('keydown', function(e) {
                                const lis = lista.querySelectorAll('li');
                                if (!lis.length) return;
                                if (e.key === 'ArrowDown') {
                                    focusedIndex = Math.min(focusedIndex + 1, lis.length - 1);
                                    lis[focusedIndex].focus();
                                    e.preventDefault();
                                } else if (e.key === 'ArrowUp') {
                                    focusedIndex = Math.max(focusedIndex - 1, 0);
                                    lis[focusedIndex].focus();
                                    e.preventDefault();
                                } else if (e.key === 'Enter') {
                                    if (focusedIndex >= 0 && lis[focusedIndex]) {
                                        lis[focusedIndex].click();
                                        e.preventDefault();
                                    }
                                }
                            });

                            // Hide lists when clicking outside (registered once)
                            if (!window._promocion_autocomplete_click) {
                                document.addEventListener('click', function(ev) {
                                    document.querySelectorAll('._promCrear-autocomplete-list').forEach(ul => {
                                        if (!ul.contains(ev.target) && !ul.previousElementSibling?.contains(ev.target)) {
                                            ul.classList.add('_promCrear-hidden');
                                        }
                                    });
                                });
                                window._promocion_autocomplete_click = true;
                            }
                        })();
                        break;


                    case 'descuento_enesima_unidad':
                        tipoCondicionInput.value = 'cantidad_producto_identico';
                        tipoAccionInput.value = 'descuento_enesima_unidad';

                        camposDinamicosContainer.innerHTML = `
                                <p class="_promCrear-info-text">Aplica un descuento a una unidad específica. Ej: 50% en la 3ra unidad.</p>
                                <div class="_promCrear-form-grid">
                                    <div class="_promCrear-form-group">
                                        <label class="_promCrear-form-label">Buscar Producto *</label>
                                        <input type="text" id="buscarProductoEnesima" class="_promCrear-form-input" placeholder="Escribe el nombre del producto..." autocomplete="off" required>
                                        <ul id="listaProductosEnesima" class="_promCrear-autocomplete-list"></ul>
                                        <input type="hidden" name="cond_producto_id" id="productoIdSeleccionadoEnesima" required>
                                        <div id="producto-seleccionado-enesima" class="_promCrear-producto-seleccionado"></div>
                                    </div>

                                    <div class="_promCrear-form-group">
                                        <label class="_promCrear-form-label">N-ésima unidad a descontar *</label>
                                        <input type="number" name="accion_numero_unidad" class="_promCrear-form-input" min="2" required>
                                        <small class="_promCrear-form-help">Ej: 2 para la segunda unidad, 3 para la tercera, etc.</small>
                                    </div>

                                    <div class="_promCrear-form-group">
                                        <label class="_promCrear-form-label">Porcentaje de descuento (%) *</label>
                                        <input type="number" name="accion_descuento_unidad" class="_promCrear-form-input" min="1" max="100" step="0.01" required>
                                    </div>
                                </div>
                                
                                <!-- ✅ CAMPO OCULTO PARA LA CANTIDAD MÍNIMA -->
                                <input type="hidden" name="accion_cantidad_lleva" id="accionCantidadLlevaHidden">
                            `;

                        // ✅ SINCRONIZAR CANTIDAD MÍNIMA CON N-ÉSIMA UNIDAD
                        setTimeout(() => {
                            const numeroUnidadInput = document.querySelector('input[name="accion_numero_unidad"]');
                            const cantidadLlevaHidden = document.getElementById('accionCantidadLlevaHidden');

                            if (numeroUnidadInput && cantidadLlevaHidden) {
                                numeroUnidadInput.addEventListener('input', function() {
                                    cantidadLlevaHidden.value = this.value;
                                });

                                // Establecer valor inicial
                                cantidadLlevaHidden.value = numeroUnidadInput.value;
                            }
                        }, 100);

                        // ✅ AUTOCOMPLETE CORREGIDO CON IDs CORRECTOS
                        (function initAutocompleteForDescuentoEnesima() {
                            const inputBuscar = camposDinamicosContainer.querySelector('#buscarProductoEnesima');
                            const lista = camposDinamicosContainer.querySelector('#listaProductosEnesima');
                            const inputHidden = camposDinamicosContainer.querySelector('#productoIdSeleccionadoEnesima');

                            if (!inputBuscar || !lista || !inputHidden) return;

                            let items = [];
                            let focusedIndex = -1;

                            inputBuscar.addEventListener('input', () => {
                                inputHidden.value = '';
                                focusedIndex = -1;
                                doSearch(inputBuscar.value);
                            });

                            const doSearch = debounce(async (q) => {
                                lista.innerHTML = '';
                                lista.classList.add('_promCrear-hidden');
                                if (!q || q.trim().length < 2) return;
                                try {
                                    const res = await fetch('<?= htmlspecialchars(url("producto/autocomplete")) ?>?q=' + encodeURIComponent(q.trim()));
                                    if (!res.ok) throw new Error('Network response was not OK');
                                    const productos = await res.json();
                                    items = productos || [];
                                    renderList(items);
                                } catch (err) {
                                    console.error('Autocomplete error:', err);
                                    lista.innerHTML = '';
                                    lista.classList.add('_promCrear-hidden');
                                }
                            }, 220);

                            function renderList(productos) {
                                lista.innerHTML = '';
                                focusedIndex = -1;
                                if (!productos || productos.length === 0) {
                                    lista.classList.add('_promCrear-hidden');
                                    return;
                                }
                                productos.forEach((p, idx) => {
                                    const li = document.createElement('li');
                                    li.tabIndex = 0;
                                    li.className = '_promCrear-autocomplete-item';
                                    li.dataset.id = p.id;
                                    li.dataset.idx = idx;
                                    li.innerHTML = `<strong>${escapeHtml(p.nombre)}</strong>${p.sku ? ' <small>(' + escapeHtml(p.sku) + ')</small>' : ''}`;
                                    li.addEventListener('click', () => selectProduct(p));
                                    li.addEventListener('keydown', (ev) => {
                                        if (ev.key === 'Enter') selectProduct(p);
                                    });
                                    lista.appendChild(li);
                                });
                                lista.classList.remove('_promCrear-hidden');
                            }

                            function selectProduct(p) {
                                inputBuscar.value = p.nombre;
                                inputHidden.value = p.id;
                                lista.innerHTML = '';
                                lista.classList.add('_promCrear-hidden');
                            }

                            inputBuscar.addEventListener('keydown', function(e) {
                                const lis = lista.querySelectorAll('li');
                                if (!lis.length) return;
                                if (e.key === 'ArrowDown') {
                                    focusedIndex = Math.min(focusedIndex + 1, lis.length - 1);
                                    lis[focusedIndex].focus();
                                    e.preventDefault();
                                } else if (e.key === 'ArrowUp') {
                                    focusedIndex = Math.max(focusedIndex - 1, 0);
                                    lis[focusedIndex].focus();
                                    e.preventDefault();
                                } else if (e.key === 'Enter') {
                                    if (focusedIndex >= 0 && lis[focusedIndex]) {
                                        lis[focusedIndex].click();
                                        e.preventDefault();
                                    }
                                }
                            });

                            if (!window._promocion_autocomplete_click) {
                                document.addEventListener('click', function(ev) {
                                    document.querySelectorAll('._promCrear-autocomplete-list').forEach(ul => {
                                        if (!ul.contains(ev.target) && !ul.previousElementSibling?.contains(ev.target)) {
                                            ul.classList.add('_promCrear-hidden');
                                        }
                                    });
                                });
                                window._promocion_autocomplete_click = true;
                            }
                        })();

                        break;

                    case 'descuento_menor_valor_categoria':
                        tipoCondicionInput.value = 'cantidad_producto_categoria';
                        tipoAccionInput.value = 'descuento_menor_valor';

                        camposDinamicosContainer.innerHTML = `
    <p class="_promCrear-info-text">Aplica un descuento al producto más barato dentro de una categoría seleccionada.</p>
    <div class="_promCrear-form-grid">
        <div class="_promCrear-form-group _promCrear-relative">
            <label class="_promCrear-form-label">Categoría *</label>
            <input type="text" id="categoria-buscador" class="_promCrear-form-input" placeholder="Buscar categoría..." required>
            <input type="hidden" name="cond_categoria_id" id="categoria-id-seleccionada" required>
            <ul id="categoria-sugerencias" class="_promCrear-autocomplete-list _promCrear-categoria-list"></ul>
            <div id="categoria-seleccionada" class="_promCrear-categoria-seleccionada"></div>
        </div>
        
        <div class="_promCrear-form-group">
            <label class="_promCrear-form-label">Cantidad mínima de productos *</label>
            <input type="number" name="cond_cantidad_min_categoria" class="_promCrear-form-input" min="2" required>
            <small class="_promCrear-form-help">Número mínimo de productos de esta categoría en el carrito</small>
        </div>
        
        <div class="_promCrear-form-group">
            <label class="_promCrear-form-label">Porcentaje de descuento (%) *</label>
            <!-- ✅ CORREGIDO: Cambiar name a "accion_valor" -->
            <input type="number" name="accion_valor" class="_promCrear-form-input" min="0" max="100" step="0.01" required>
        </div>
    </div>
    `;

                        // --- Buscador dinámico de categorías (seguro y aislado) ---
                        (() => {
                            const buscador = document.querySelector('#categoria-buscador');
                            const lista = document.querySelector('#categoria-sugerencias');
                            const hiddenInput = document.querySelector('#categoria-id-seleccionada');
                            let timeout = null;
                            let controller = null;

                            buscador?.addEventListener('input', () => {
                                const termino = buscador.value.trim();
                                lista.innerHTML = '';
                                lista.classList.add('_promCrear-hidden');
                                hiddenInput.value = '';

                                if (termino.length < 2) return;

                                clearTimeout(timeout);
                                timeout = setTimeout(async () => {
                                    if (controller) controller.abort();
                                    controller = new AbortController();

                                    try {
                                        const response = await fetch(`<?= url('categoria/buscarPorNombre') ?>?q=${encodeURIComponent(termino)}`, {
                                            signal: controller.signal
                                        });
                                        if (!response.ok) throw new Error('Error HTTP');
                                        const data = await response.json();

                                        if (Array.isArray(data) && data.length > 0) {
                                            lista.innerHTML = '';
                                            data.forEach(cat => {
                                                const li = document.createElement('li');
                                                li.textContent = cat.nombre;
                                                li.className = '_promCrear-autocomplete-item';
                                                li.addEventListener('click', () => {
                                                    buscador.value = cat.nombre;
                                                    hiddenInput.value = cat.id;
                                                    lista.innerHTML = '';
                                                    lista.classList.add('_promCrear-hidden');
                                                });
                                                lista.appendChild(li);
                                            });
                                            lista.classList.remove('_promCrear-hidden');
                                        }
                                    } catch (e) {
                                        console.error('Error en búsqueda de categorías', e);
                                    }
                                }, 300);
                            });

                            // Ocultar sugerencias al hacer clic fuera
                            document.addEventListener('click', e => {
                                if (!buscador.contains(e.target) && !lista.contains(e.target)) {
                                    lista.innerHTML = '';
                                    lista.classList.add('_promCrear-hidden');
                                }
                            });
                        })();
                        break;


                    case 'nxm_general':
                        tipoCondicionInput.value = 'cantidad_total_productos';
                        tipoAccionInput.value = 'compra_n_paga_m_general';
                        camposDinamicosContainer.innerHTML = `
                        <p class="_promCrear-info-text">Aplica para cualquier combinación de productos. Ej: Lleva 3, Paga 2 (el de menor valor gratis).</p>
                        <div class="_promCrear-form-grid">
                            <div class="_promCrear-form-group"><label class="_promCrear-form-label">Cantidad mínima de productos</label><input type="number" name="cond_cantidad_total" class="_promCrear-form-input" min="2" required></div>
                            <div class="_promCrear-form-group"><label class="_promCrear-form-label">Cantidad que lleva (N)</label><input type="number" name="accion_cantidad_lleva_general" class="_promCrear-form-input" min="2" required></div>
                            <div class="_promCrear-form-group"><label class="_promCrear-form-label">Cantidad que paga (M)</label><input type="number" name="accion_cantidad_paga_general" class="_promCrear-form-input" min="1" required></div>
                        </div>`;
                        break;

                    case 'descuento_enesimo_producto':
                        tipoCondicionInput.value = 'cantidad_total_productos';
                        tipoAccionInput.value = 'descuento_producto_mas_barato';

                        camposDinamicosContainer.innerHTML = `
                            <p class="_promCrear-info-text">Aplica un descuento al producto más barato al llevar una cantidad mínima de productos.</p>
                            <div class="_promCrear-form-grid">
                                <div class="_promCrear-form-group">
                                    <label class="_promCrear-form-label">Cantidad mínima de productos *</label>
                                    <input type="number" name="cond_cantidad_total" class="_promCrear-form-input" min="2" required>
                                    <small class="_promCrear-form-help">Número total mínimo de productos en el carrito</small>
                                </div>
                                
                                <div class="_promCrear-form-group">
                                    <label class="_promCrear-form-label">Porcentaje de descuento (%) *</label>
                                    <input type="number" name="accion_valor" class="_promCrear-form-input" min="0" max="100" step="0.01" required>                                </div>
                            </div>
                        `;
                        break;

                    case 'envio_gratis_general':
                        tipoCondicionInput.value = 'todos';
                        tipoAccionInput.value = 'envio_gratis';
                        camposDinamicosContainer.innerHTML = `<p class="_promCrear-info-text">Envío gratis aplica a todos los pedidos sin condiciones.</p>`;
                        break;

                    case 'envio_gratis_monto_minimo':
                        tipoCondicionInput.value = 'subtotal_minimo';
                        tipoAccionInput.value = 'envio_gratis';
                        camposDinamicosContainer.innerHTML = `
                        <div class="_promCrear-form-group">
                            <label class="_promCrear-form-label">Monto mínimo del carrito (S/)</label>
                            <input type="number" name="cond_subtotal_minimo" class="_promCrear-form-input" required min="0" step="0.01">
                        </div>`;
                        break;

                    case 'descuento_fijo_subtotal':
                        tipoCondicionInput.value = 'subtotal_minimo';
                        tipoAccionInput.value = 'descuento_fijo';
                        camposDinamicosContainer.innerHTML = `
                        <div class="_promCrear-form-grid">
                            <div class="_promCrear-form-group">
                                <label class="_promCrear-form-label">Monto mínimo del carrito (S/)</label>
                                <input type="number" name="cond_subtotal_minimo" class="_promCrear-form-input" min="0" step="0.01" required>
                            </div>
                            <div class="_promCrear-form-group">
                                <label class="_promCrear-form-label">Monto de descuento fijo (S/)</label>
                                <input type="number" name="accion_valor_descuento_fijo" class="_promCrear-form-input" min="0" step="0.01" required>
                            </div>
                        </div>`;
                        break;
                }

                if (regla) {
                    camposDinamicosContainer.prepend(tipoCondicionInput);
                    camposDinamicosContainer.prepend(tipoAccionInput);
                }
            }

            // Validación mejorada del formulario
            form.addEventListener('submit', function(e) {
                console.log("=== 🚨 DEBUG CONSOLE - FORMULARIO ENVIADO ===");

                const tipoRegla = tipoReglaSelect.value;

                // ✅ CAPTURAR TODOS LOS DATOS DEL FORMULARIO
                const formData = new FormData(this);
                console.log("📋 TODOS LOS DATOS DEL FORMULARIO:");
                for (let [key, value] of formData.entries()) {
                    console.log(` - ${key}: ${value} (tipo: ${typeof value})`);
                }

                if (!tipoRegla) {
                    e.preventDefault();
                    alert('Por favor, selecciona un tipo de regla para la promoción.');
                    return false;
                }

                // Validación específica por tipo de regla
                let isValid = true;
                let errorMessage = '';

                switch (tipoRegla) {
                    case 'nxm_producto':
                    case 'descuento_enesima_unidad':
                        const productoHidden = camposDinamicosContainer.querySelector('input[name="cond_producto_id"]');
                        console.log("🔍 Validando producto - Hidden value:", productoHidden?.value);
                        if (!productoHidden || !productoHidden.value) {
                            isValid = false;
                            errorMessage = 'Por favor selecciona un producto de la lista.';
                        }
                        break;

                    case 'descuento_menor_valor_categoria':
                        const categoriaHidden = camposDinamicosContainer.querySelector('input[name="cond_categoria_id"]');
                        const cantidadMin = camposDinamicosContainer.querySelector('input[name="cond_cantidad_min_categoria"]');
                        const accionValor = camposDinamicosContainer.querySelector('input[name="accion_valor"]');

                        console.log("🔍 PROMOCIÓN 6 - VALIDANDO CAMPOS:");
                        console.log(" - cond_categoria_id:", categoriaHidden?.value);
                        console.log(" - cond_cantidad_min_categoria:", cantidadMin?.value);
                        console.log(" - accion_valor:", accionValor?.value);

                        // Validar que el categoria_id sea numérico
                        if (categoriaHidden && categoriaHidden.value) {
                            const categoriaId = parseInt(categoriaHidden.value);
                            console.log("🔢 ID de categoría convertido:", categoriaId);
                            console.log("✅ ¿Es número válido?", !isNaN(categoriaId) && categoriaId > 0);

                            if (isNaN(categoriaId) || categoriaId <= 0) {
                                console.error("❌ ERROR: ID de categoría inválido");
                                isValid = false;
                                errorMessage = 'El ID de categoría seleccionado no es válido. Por favor selecciona otra categoría.';
                            }
                        }

                        if (!categoriaHidden || !categoriaHidden.value) {
                            isValid = false;
                            errorMessage = 'Por favor selecciona una categoría de la lista.';
                        } else if (!cantidadMin || !cantidadMin.value || cantidadMin.value < 2) {
                            isValid = false;
                            errorMessage = 'La cantidad mínima debe ser al menos 2 productos.';
                        } else if (!accionValor || !accionValor.value || accionValor.value <= 0) {
                            isValid = false;
                            errorMessage = 'El porcentaje de descuento debe ser mayor a 0.';
                        }
                        break;
                }

                if (!isValid) {
                    console.error("❌ VALIDACIÓN FALLIDA:", errorMessage);
                    e.preventDefault();
                    alert(errorMessage);
                    return false;
                }

                console.log("✅ FORMULARIO VALIDADO CORRECTAMENTE - ENVIANDO...");
                // El formulario se envía normalmente
            });
        });
    </script>


</body>

</html>