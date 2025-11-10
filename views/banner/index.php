<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/bannerAdmi.css') ?>">

<body>
    <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

    <?php $baseUploadUrl = rtrim($uploadDirUrl ?? 'uploads/banners/', '/') . '/'; ?>

    <div class="container">
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']);
                                            unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>

        <!-- ======================================= -->
        <!-- GESTIÓN DE BANNERS PRINCIPALES (CARRUSEL) -->
        <!-- ======================================= -->
        <div id="banner-app-principal" class="banner-manager" data-tipo="principal"
            data-ruta-ordenar="<?= url('banner/ordenar') ?>"
            data-ruta-toggle="<?= url('banner/toggle') ?>"
            data-ruta-eliminar="<?= url('banner/eliminar') ?>"
            data-ruta-guardar="<?= url('banner/guardar') ?>"
            data-ruta-actualizar="<?= url('banner/actualizar-imagen') ?>"
            data-ruta-actualizar-enlace="<?= url('banner/actualizar_enlace') ?>"
            data-base-upload-url="<?= htmlspecialchars($baseUploadUrl, ENT_QUOTES) ?>">

            <div class="management-grid">
                <!-- IZQUIERDA: LISTA -->
                <div class="banner-list-container">
                    <h1>Banners Principales (Carrusel)</h1>
                    <p class="banner-description">Arrastra para cambiar el orden. Las imágenes deben ser de 1024 x 425 píxeles.</p>

                    <div class="table-responsive">
                        <table class="admin-productos-table">
                            <thead>
                                <tr>
                                    <th class="drag-column"></th>
                                    <th>#</th>
                                    <th>Imagen</th>
                                    <th class="link-column">Enlace</th>
                                    <th class="status-column">Activo</th>
                                    <th class="actions-column">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="sortable-list">
                                <?php if (empty($banners_principales)): ?>
                                    <tr>
                                        <td colspan="6" class="no-banners">No hay banners principales.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($banners_principales as $i => $b): ?>
                                        <tr data-id="<?= (int)$b['id'] ?>">
                                            <td class="order-controls">
                                                <div class="drag-handle" title="Arrastrar">☰</div>
                                                <div class="order-buttons">
                                                    <button class="btn-move-up" title="Mover arriba">↑</button>
                                                    <button class="btn-move-down" title="Mover abajo">↓</button>
                                                </div>
                                            </td>
                                            <td class="row-index"><?= $i + 1 ?></td>
                                            <td><img src="<?= url($baseUploadUrl . $b['nombre_imagen']) ?>" alt="banner" class="banner-preview"></td>
                                            <td>
                                                <input type="text" class="input-enlace" value="<?= htmlspecialchars($b['enlace'] ?? '') ?>" placeholder="https://..." title="El enlace se guarda automáticamente">
                                            </td>
                                            <td>
                                                <label class="switch"><input type="checkbox" class="switch-activo" <?= !empty($b['activo']) ? 'checked' : '' ?>><span class="slider"></span></label>
                                            </td>
                                            <td>
                                                <button class="btn-xs btn-primary-xs btn-reemplazar" title="Cambiar imagen del banner"><i class="fas fa-sync-alt"></i> Reemplazar</button>
                                                <input type="file" class="file-reemplazo" accept=".jpg,.jpeg,.png,.webp,.gif">
                                                <button class="btn-xs btn-danger-xs btn-eliminar" title="Eliminar este banner"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- DERECHA: NUEVO -->
                <div class="card new-banner-card">
                    <h2>Nuevo Banner Principal</h2>
                    <p class="banner-description">Formatos: JPG, PNG, WEBP, GIF.</p>
                    <div class="dropzone">
                        <p>Arrastra o haz clic para subir</p>
                        <input type="file" class="input-nuevo-file" accept=".jpg,.jpeg,.png,.webp,.gif">
                    </div>
                    <div class="preview-nuevo" aria-live="polite"></div>
                    <div class="form-group">
                        <label for="nuevo-enlace-principal">Enlace (URL):</label>
                        <input type="text" id="nuevo-enlace-principal" class="nuevo-enlace" placeholder="https://ejemplo.com" style="width: 100%; padding: 8px; margin-top: 5px;">
                        <small style="color: #666; font-size: 0.85em; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Opcional. Si agregas un enlace, el banner será clickeable.
                        </small>
                    </div>
                    <div class="active-toggle">
                        <label>
                            <span>Activo</span>
                            <label class="switch">
                                <input type="checkbox" class="nuevo-activo" checked>
                                <span class="slider"></span>
                            </label>
                        </label>
                    </div>
                    <button class="btn btn-guardar-nuevo">GUARDAR</button>
                </div>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- ======================================= -->
        <!-- GESTIÓN DE BANNERS SECUNDARIOS - IZQUIERDA -->
        <!-- ======================================= -->
        <div id="banner-app-secundario-izquierda" class="banner-manager" data-tipo="secundario_izquierda"
            data-ruta-ordenar="<?= url('banner/ordenar') ?>"
            data-ruta-toggle="<?= url('banner/toggle') ?>"
            data-ruta-eliminar="<?= url('banner/eliminar') ?>"
            data-ruta-guardar="<?= url('banner/guardar') ?>"
            data-ruta-actualizar="<?= url('banner/actualizar-imagen') ?>"
            data-ruta-actualizar-enlace="<?= url('banner/actualizar_enlace') ?>"
            data-base-upload-url="<?= htmlspecialchars($baseUploadUrl, ENT_QUOTES) ?>">

            <div class="management-grid">
                <!-- IZQUIERDA: LISTA -->
                <div class="banner-list-container">
                    <h1>Banner Secundario (Izquierda)</h1>
                    <p class="banner-description">Solo se mostrará el primer banner activo. Dimensiones recomendadas: 1024 x 425 píxeles</p>

                    <div class="table-responsive">
                        <table class="admin-productos-table">
                            <thead>
                                <tr>
                                    <th class="drag-column"></th>
                                    <th>#</th>
                                    <th>Imagen</th>
                                    <th class="link-column">Enlace</th>
                                    <th class="status-column">Activo</th>
                                    <th class="actions-column">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="sortable-list">
                                <?php if (empty($banners_secundarios_izquierda)): ?>
                                    <tr>
                                        <td colspan="6" class="no-banners">No hay banners secundarios izquierdos.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($banners_secundarios_izquierda as $i => $b): ?>
                                        <tr data-id="<?= (int)$b['id'] ?>">
                                            <td class="drag-handle" title="Arrastrar">☰</td>
                                            <td><?= $i + 1 ?></td>
                                            <td><img src="<?= url($baseUploadUrl . $b['nombre_imagen']) ?>" alt="banner" class="banner-preview"></td>
                                            <td>
                                                <input type="text" class="input-enlace" value="<?= htmlspecialchars($b['enlace'] ?? '') ?>" placeholder="https://..." title="El enlace se guarda automáticamente">
                                            </td>
                                            <td>
                                                <label class="switch"><input type="checkbox" class="switch-activo" <?= !empty($b['activo']) ? 'checked' : '' ?>><span class="slider"></span></label>
                                            </td>
                                            <td>
                                                <button class="btn-xs btn-primary-xs btn-reemplazar" title="Cambiar imagen del banner"><i class="fas fa-sync-alt"></i> Reemplazar</button>
                                                <input type="file" class="file-reemplazo" accept=".jpg,.jpeg,.png,.webp,.gif">
                                                <button class="btn-xs btn-danger-xs btn-eliminar" title="Eliminar este banner"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- DERECHA: NUEVO -->
                <div class="card new-banner-card">
                    <h2>Nuevo Banner Secundario Izquierdo</h2>
                    <p class="banner-description">Formatos: JPG, PNG, WEBP, GIF.</p>
                    <div class="dropzone">
                        <p>Arrastra o haz clic para subir</p>
                        <input type="file" class="input-nuevo-file" accept=".jpg,.jpeg,.png,.webp,.gif">
                    </div>
                    <div class="preview-nuevo" aria-live="polite"></div>
                    <div class="form-group">
                        <label for="nuevo-enlace-sec-izq">Enlace (URL):</label>
                        <input type="text" id="nuevo-enlace-sec-izq" class="nuevo-enlace" placeholder="https://ejemplo.com" style="width: 100%; padding: 8px; margin-top: 5px;">
                        <small style="color: #666; font-size: 0.85em; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Opcional. Si agregas un enlace, el banner será clickeable.
                        </small>
                    </div>
                    <div class="active-toggle">
                        <label>
                            <span>Activo</span>
                            <label class="switch">
                                <input type="checkbox" class="nuevo-activo" checked>
                                <span class="slider"></span>
                            </label>
                        </label>
                    </div>
                    <button class="btn btn-guardar-nuevo">GUARDAR</button>
                </div>
            </div>
        </div>

        <div class="section-divider"></div>

        <!-- ======================================= -->
        <!-- GESTIÓN DE BANNERS SECUNDARIOS - DERECHA -->
        <!-- ======================================= -->
        <div id="banner-app-secundario-derecha" class="banner-manager" data-tipo="secundario_derecha"
            data-ruta-ordenar="<?= url('banner/ordenar') ?>"
            data-ruta-toggle="<?= url('banner/toggle') ?>"
            data-ruta-eliminar="<?= url('banner/eliminar') ?>"
            data-ruta-guardar="<?= url('banner/guardar') ?>"
            data-ruta-actualizar="<?= url('banner/actualizar-imagen') ?>"
            data-ruta-actualizar-enlace="<?= url('banner/actualizar_enlace') ?>"
            data-base-upload-url="<?= htmlspecialchars($baseUploadUrl, ENT_QUOTES) ?>">

            <div class="management-grid">
                <!-- IZQUIERDA: LISTA -->
                <div class="banner-list-container">
                    <h1>Banner Secundario (Derecha)</h1>
                    <p class="banner-description">Solo se mostrará el primer banner activo. Dimensiones recomendadas: 1024 x 425 píxeles</p>

                    <div class="table-responsive">
                        <table class="admin-productos-table">
                            <thead>
                                <tr>
                                    <th class="drag-column"></th>
                                    <th>#</th>
                                    <th>Imagen</th>
                                    <th class="link-column">Enlace</th>
                                    <th class="status-column">Activo</th>
                                    <th class="actions-column">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="sortable-list">
                                <?php if (empty($banners_secundarios_derecha)): ?>
                                    <tr>
                                        <td colspan="6" class="no-banners">No hay banners secundarios derechos.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($banners_secundarios_derecha as $i => $b): ?>
                                        <tr data-id="<?= (int)$b['id'] ?>">
                                            <td class="drag-handle" title="Arrastrar">☰</td>
                                            <td><?= $i + 1 ?></td>
                                            <td><img src="<?= url($baseUploadUrl . $b['nombre_imagen']) ?>" alt="banner" class="banner-preview"></td>
                                            <td>
                                                <input type="text" class="input-enlace" value="<?= htmlspecialchars($b['enlace'] ?? '') ?>" placeholder="https://..." title="El enlace se guarda automáticamente">
                                            </td>
                                            <td>
                                                <label class="switch"><input type="checkbox" class="switch-activo" <?= !empty($b['activo']) ? 'checked' : '' ?>><span class="slider"></span></label>
                                            </td>
                                            <td>
                                                <button class="btn-xs btn-primary-xs btn-reemplazar" title="Cambiar imagen del banner"><i class="fas fa-sync-alt"></i> Reemplazar</button>
                                                <input type="file" class="file-reemplazo" accept=".jpg,.jpeg,.png,.webp,.gif">
                                                <button class="btn-xs btn-danger-xs btn-eliminar" title="Eliminar este banner"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- DERECHA: NUEVO -->
                <div class="card new-banner-card">
                    <h2>Nuevo Banner Secundario Derecho</h2>
                    <p class="banner-description">Formatos: JPG, PNG, WEBP, GIF.</p>
                    <div class="dropzone">
                        <p>Arrastra o haz clic para subir</p>
                        <input type="file" class="input-nuevo-file" accept=".jpg,.jpeg,.png,.webp,.gif">
                    </div>
                    <div class="preview-nuevo" aria-live="polite"></div>
                    <div class="form-group">
                        <label for="nuevo-enlace-sec-der">Enlace (URL):</label>
                        <input type="text" id="nuevo-enlace-sec-der" class="nuevo-enlace" placeholder="https://ejemplo.com" style="width: 100%; padding: 8px; margin-top: 5px;">
                        <small style="color: #666; font-size: 0.85em; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Opcional. Si agregas un enlace, el banner será clickeable.
                        </small>
                    </div>
                    <div class="active-toggle">
                        <label>
                            <span>Activo</span>
                            <label class="switch">
                                <input type="checkbox" class="nuevo-activo" checked>
                                <span class="slider"></span>
                            </label>
                        </label>
                    </div>
                    <button class="btn btn-guardar-nuevo">GUARDAR</button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast"></div>

    <script>
        (function() {
            function initBannerManager(containerSelector) {
                const app = document.querySelector(containerSelector);
                if (!app) return;

                const $ = (sel, ctx = app) => ctx.querySelector(sel);
                const $$ = (sel, ctx = app) => Array.from(ctx.querySelectorAll(sel));

                const tipoBanner = app.dataset.tipo;
                const rutas = {
                    ordenar: app.dataset.rutaOrdenar,
                    toggle: app.dataset.rutaToggle,
                    eliminar: app.dataset.rutaEliminar,
                    guardar: app.dataset.rutaGuardar,
                    actualizar: app.dataset.rutaActualizar,
                    actualizarEnlace: app.dataset.rutaActualizarEnlace,
                };
                const baseUploadUrl = app.dataset.baseUploadUrl || '';
                const csrf = (document.querySelector('meta[name="csrf"]')?.content || '').trim();

                function showToast(msg, type = 'success') {
                    const container = document.querySelector('#toast');
                    const box = document.createElement('div');
                    box.className = 'toast ' + (type === 'error' ? 'error' : 'success');
                    box.textContent = msg;
                    container.appendChild(box);
                    setTimeout(() => box.classList.add('show'), 10);
                    setTimeout(() => {
                        box.classList.remove('show');
                        setTimeout(() => box.remove(), 500);
                    }, 3500);
                }

                function safeSetImageSrc(img, url) {
                    if (!url) return;
                    if (url.startsWith('blob:') || url.startsWith('data:')) {
                        img.src = url;
                        return;
                    }
                    const sep = url.includes('?') ? '&' : '?';
                    img.src = url + sep + 'v=' + Date.now();
                }

                async function postForm(url, formData = null) {
                    const headers = {
                        'X-Requested-With': 'XMLHttpRequest'
                    };
                    if (csrf) headers['X-CSRF-Token'] = csrf;
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers,
                            body: formData
                        });
                        const txt = await res.text();
                        let data;
                        try {
                            data = JSON.parse(txt);
                        } catch (e) {
                            data = {
                                ok: res.ok,
                                message: txt || (res.ok ? 'ok' : 'Error')
                            };
                        }
                        if (!res.ok) data.ok = false;
                        return data;
                    } catch (err) {
                        return {
                            ok: false,
                            message: 'Error de red.'
                        };
                    }
                }

                function extractFilename(resp) {
                    return resp?.data?.nombre_imagen || resp?.nombre_imagen || null;
                }

                function renumerarFilas() {
                    $$('.sortable-list tr').forEach((tr, idx) => {
                        const idxEl = tr.querySelector('.row-index');
                        if (idxEl) idxEl.textContent = idx + 1;
                    });
                }

                async function guardarOrden() {
                    const rows = $$('.sortable-list tr');
                    if (!rows.length) return;
                    const fd = new FormData();
                    rows.forEach(tr => fd.append('orden[]', tr.dataset.id));
                    const resp = await postForm(rutas.ordenar, fd);
                    if (!resp?.ok) {
                        showToast(resp?.message || 'No se pudo guardar el orden', 'error');
                    }
                }


                let draggedRow = null;

                function makeRowsDraggable() {
                    // Drag & drop para desktop
                    $$('.sortable-list tr').forEach(tr => {
                        tr.setAttribute('draggable', 'true');
                        tr.addEventListener('dragstart', e => {
                            draggedRow = tr;
                            tr.classList.add('row-ghost');
                            e.dataTransfer.effectAllowed = 'move';
                        });
                        tr.addEventListener('dragend', () => {
                            if (draggedRow) draggedRow.classList.remove('row-ghost');
                            draggedRow = null;
                        });
                        tr.addEventListener('dragover', e => e.preventDefault());
                        tr.addEventListener('drop', async e => {
                            e.preventDefault();
                            const target = e.currentTarget;
                            if (!draggedRow || draggedRow === target) return;
                            const tbody = target.parentNode;
                            const rect = target.getBoundingClientRect();
                            const isAfter = e.clientY > rect.top + rect.height / 2;
                            if (isAfter) tbody.insertBefore(draggedRow, target.nextSibling);
                            else tbody.insertBefore(draggedRow, target);
                            renumerarFilas();
                            await guardarOrden();
                        });
                    });

                    // Botones de flechas para móvil
                    $$('.sortable-list tr').forEach((tr, index, array) => {
                        const btnUp = tr.querySelector('.btn-move-up');
                        const btnDown = tr.querySelector('.btn-move-down');

                        // Deshabilitar botones según posición
                        btnUp.disabled = index === 0;
                        btnDown.disabled = index === array.length - 1;

                        btnUp?.addEventListener('click', async () => {
                            if (index > 0) {
                                const tbody = tr.parentNode;
                                tbody.insertBefore(tr, array[index - 1]);
                                renumerarFilas();
                                await guardarOrden();
                                // Actualizar estados de botones
                                makeRowsDraggable();
                            }
                        });

                        btnDown?.addEventListener('click', async () => {
                            if (index < array.length - 1) {
                                const tbody = tr.parentNode;
                                const nextSibling = array[index + 1].nextSibling;
                                if (nextSibling) {
                                    tbody.insertBefore(tr, nextSibling);
                                } else {
                                    tbody.appendChild(tr);
                                }
                                renumerarFilas();
                                await guardarOrden();
                                // Actualizar estados de botones
                                makeRowsDraggable();
                            }
                        });
                    });
                }

                function readFileAsDataURL(file) {
                    return new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = () => resolve(reader.result);
                        reader.onerror = reject;
                        reader.readAsDataURL(file);
                    });
                }

                // Eventos para la subida de nuevo banner
                const dropzone = $('.dropzone');
                const inputNuevoFile = $('.input-nuevo-file');
                const previewNuevo = $('.preview-nuevo');
                const nuevoActivo = $('.nuevo-activo');
                const nuevoEnlace = $('.nuevo-enlace');
                const btnGuardarNuevo = $('.btn-guardar-nuevo');
                let nuevoArchivo = null;

                dropzone?.addEventListener('click', () => inputNuevoFile.click());
                dropzone?.addEventListener('dragover', e => {
                    e.preventDefault();
                    dropzone.classList.add('dragover');
                });
                dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
                dropzone?.addEventListener('drop', e => {
                    e.preventDefault();
                    dropzone.classList.remove('dragover');
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        inputNuevoFile.files = files;
                        inputNuevoFile.dispatchEvent(new Event('change'));
                    }
                });

                inputNuevoFile?.addEventListener('change', async e => {
                    const file = e.target.files[0];
                    if (file) {
                        nuevoArchivo = file;
                        const imgUrl = await readFileAsDataURL(file);
                        previewNuevo.innerHTML = `<img src="${imgUrl}" alt="Previsualización">`;
                    } else {
                        nuevoArchivo = null;
                        previewNuevo.innerHTML = '';
                    }
                });

                btnGuardarNuevo?.addEventListener('click', async () => {
                    if (!nuevoArchivo) {
                        showToast('Por favor, selecciona una imagen.', 'error');
                        return;
                    }

                    const fd = new FormData();
                    fd.append('imagen', nuevoArchivo);
                    fd.append('tipo', tipoBanner);
                    const nextOrden = $$('.sortable-list tr').length;
                    fd.append('orden', String(nextOrden));
                    if (nuevoActivo.checked) fd.append('activo', '1');
                    if (nuevoEnlace?.value) fd.append('enlace', nuevoEnlace.value);

                    const resp = await postForm(rutas.guardar, fd);

                    if (resp?.ok) {
                        showToast('Banner guardado exitosamente');
                        const newRow = document.createElement('tr');
                        newRow.dataset.id = resp.data.id;
                        newRow.innerHTML = `
                        <td class="order-controls">
                            <div class="drag-handle" title="Arrastrar">☰</div>
                            <div class="order-buttons">
                                <button class="btn-move-up" title="Mover arriba">↑</button>
                                <button class="btn-move-down" title="Mover abajo">↓</button>
                            </div>
                        </td>                        
                        <td class="row-index">${$$('.sortable-list tr').length + 1}</td>
                        <td><img src="${baseUploadUrl}${resp.data.nombre_imagen}" alt="banner" class="banner-preview"></td>
                        <td>
                            <input type="text" class="input-enlace" value="${resp.data.enlace || ''}" placeholder="https://..." title="El enlace se guarda automáticamente">
                        </td>
                        <td>
                            <label class="switch"><input type="checkbox" class="switch-activo" ${resp.data.activo ? 'checked' : ''}><span class="slider"></span></label>
                        </td>
                        <td>
                            <button class="btn-xs btn-primary-xs btn-reemplazar" title="Cambiar imagen del banner"><i class="fas fa-sync-alt"></i> Reemplazar</button>
                            <input type="file" class="file-reemplazo" accept=".jpg,.jpeg,.png,.webp,.gif">
                            <button class="btn-xs btn-danger-xs btn-eliminar" title="Eliminar este banner"><i class="fas fa-trash-alt"></i> Eliminar</button>
                        </td>
                    `;
                        $('.sortable-list').appendChild(newRow);
                        makeRowsDraggable();
                        attachRowEvents(newRow);
                        inputNuevoFile.value = '';
                        previewNuevo.innerHTML = '';
                        nuevoArchivo = null;
                        nuevoActivo.checked = true;
                        if (nuevoEnlace) nuevoEnlace.value = '';
                    } else {
                        showToast(resp?.message || 'Error al guardar el banner', 'error');
                    }
                });

                // Eventos para filas existentes (toggle, eliminar, reemplazar)
                function attachRowEvents(row) {
                    const id = row.dataset.id;
                    const switchActivo = row.querySelector('.switch-activo');
                    const btnEliminar = row.querySelector('.btn-eliminar');
                    const btnReemplazar = row.querySelector('.btn-reemplazar');
                    const fileReemplazo = row.querySelector('.file-reemplazo');
                    const imgElement = row.querySelector('img');
                    const inputEnlace = row.querySelector('.input-enlace');

                    switchActivo?.addEventListener('change', async e => {
                        const activo = e.target.checked ? 1 : 0;
                        const fd = new FormData();
                        fd.append('id', id);
                        fd.append('activo', activo);
                        const resp = await postForm(rutas.toggle, fd);
                        if (!resp?.ok) {
                            showToast(resp?.message || 'Error al cambiar estado', 'error');
                            e.target.checked = !e.target.checked;
                        }
                    });

                    btnEliminar?.addEventListener('click', async () => {
                        if (!confirm('¿Estás seguro de eliminar este banner?')) return;
                        const fd = new FormData();
                        fd.append('id', id);
                        const resp = await postForm(rutas.eliminar, fd);
                        if (resp?.ok) {
                            showToast('Banner eliminado');
                            row.remove();
                            renumerarFilas();
                        } else {
                            showToast(resp?.message || 'Error al eliminar banner', 'error');
                        }
                    });

                    btnReemplazar?.addEventListener('click', () => fileReemplazo.click());
                    fileReemplazo?.addEventListener('change', async e => {
                        const file = e.target.files[0];
                        if (!file) return;

                        const fd = new FormData();
                        fd.append('id', id);
                        fd.append('imagen', file);

                        const resp = await postForm(rutas.actualizar, fd);
                        if (resp?.ok) {
                            showToast('Imagen actualizada');
                            safeSetImageSrc(imgElement, baseUploadUrl + extractFilename(resp));
                        } else {
                            showToast(resp?.message || 'Error al actualizar imagen', 'error');
                        }
                    });

                    // Handler para actualizar enlace con debounce
                    let timeoutEnlace = null;
                    inputEnlace?.addEventListener('input', e => {
                        clearTimeout(timeoutEnlace);
                        
                        // Añadir clase "saving" para feedback visual
                        inputEnlace.classList.remove('saved');
                        inputEnlace.classList.add('saving');
                        
                        timeoutEnlace = setTimeout(async () => {
                            const enlace = e.target.value.trim();
                            const fd = new FormData();
                            fd.append('id', id);
                            fd.append('enlace', enlace);

                            const resp = await postForm(rutas.actualizarEnlace, fd);
                            
                            // Remover clase saving
                            inputEnlace.classList.remove('saving');
                            
                            if (resp?.ok) {
                                // Mostrar feedback visual de éxito
                                inputEnlace.classList.add('saved');
                                setTimeout(() => {
                                    inputEnlace.classList.remove('saved');
                                }, 2000);
                            } else {
                                showToast(resp?.message || 'Error al actualizar enlace', 'error');
                            }
                        }, 800); // Esperar 800ms después de que el usuario deje de escribir
                    });
                }

                // Inicializar eventos para todas las filas existentes al cargar
                $$('.sortable-list tr').forEach(attachRowEvents);
                makeRowsDraggable();
            }

            // Inicializar ambos gestores de banners
            document.addEventListener('DOMContentLoaded', function() {
                initBannerManager('#banner-app-principal');
                initBannerManager('#banner-app-secundario-izquierda');
                initBannerManager('#banner-app-secundario-derecha');
            });
        })();
    </script>
</body>

</html>