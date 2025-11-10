<?php
// Asegura que la sesión esté activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializa contador del carrito
$cantidadEnCarrito = 0;
if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidadEnCarrito += (int)($item['cantidad'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
    <link rel="stylesheet" href="<?= url('/css/productoCrear.css') ?>">

<body class="_productoCrear_body" data-base-url="<?= htmlspecialchars(url('producto')) ?>">

<div class="_productoCrear_layout">
    <!-- Sidebar -->
    <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
    <!-- Main -->
    <div class="_productoCrear_main">
        <!-- Content -->
        <main class="_productoCrear_content">
            <div class="_productoCrear_container">
                <div class="_productoCrear_form-container">
                    <h2 class="_productoCrear_title">Crear Nuevo Producto</h2>

                    <!-- FORMULARIO ÚNICO -->
                    <form action="<?= url('producto/guardar') ?>" method="POST" enctype="multipart/form-data" class="_productoCrear_form">
                        <!-- Nombre -->
                        <div class="_productoCrear_form-group">
                            <label for="nombre" class="_productoCrear_label">Nombre del Producto</label>
                            <input type="text" name="nombre" id="nombre" required placeholder="Ingrese el nombre del producto" class="_productoCrear_input">
                        </div>

                        <!-- Descripción -->
                        <div class="_productoCrear_form-group">
                            <label for="descripcion" class="_productoCrear_label">Descripción</label>
                            <textarea name="descripcion" id="descripcion" required placeholder="Describa las características del producto" class="_productoCrear_textarea"></textarea>
                        </div>

                        <!-- Especificaciones -->
                        <div class="_productoCrear_form-group">
                            <label for="especificaciones" class="_productoCrear_label">Especificaciones (una por línea)</label>
                            <textarea name="especificaciones" id="especificaciones" rows="5" class="_productoCrear_textarea"><?= htmlspecialchars($producto['especificaciones'] ?? '') ?></textarea>
                        </div>

                        <!-- Productos relacionados (checkboxes con buscador) -->
                        <div class="_productoCrear_form-group">
                            <label for="buscador-productos" class="_productoCrear_label">🔍 Buscar Productos Relacionados</label>
                            <input
                                type="text"
                                id="buscador-productos"
                                placeholder="Escribe para buscar productos..."
                                class="_productoCrear_search-input">

                            <!-- Área de productos seleccionados -->
                            <div class="_productoCrear_selected-container" id="productos-seleccionados-container">
                                <h4 class="_productoCrear_selected-title">✅ Productos Seleccionados:</h4>
                                <div id="lista-productos-seleccionados" class="_productoCrear_selected-list">
                                    <!-- Los productos seleccionados aparecerán aquí dinámicamente -->
                                </div>
                            </div>

                            <!-- Contenedor de checkboxes -->
                            <div class="_productoCrear_checkboxes-container" id="productos-relacionados-container">
                                <!-- Checkboxes iniciales (todos los productos visibles) -->
                                <?php foreach ($allProducts as $p): ?>
                                    <div class="_productoCrear_checkbox-item" data-product-id="<?= (int)$p['id'] ?>" data-product-name="<?= htmlspecialchars(strtolower($p['nombre'])) ?>">
                                        <label class="_productoCrear_checkbox-label">
                                            <input
                                                type="checkbox"
                                                name="productos_relacionados[]"
                                                value="<?= (int)$p['id'] ?>"
                                                onchange="actualizarProductosSeleccionados(this)"
                                                class="_productoCrear_checkbox">
                                            <?= htmlspecialchars($p['nombre']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="_productoCrear_help-text">Selecciona los productos relacionados con checkboxes.</small>
                        </div>

                        <!-- Precios Section -->
                        <div class="_productoCrear_prices-section">
                            <!-- Precio Original -->
                            <div class="_productoCrear_form-group">
                                <label for="precio_tachado" class="_productoCrear_label">Precio Original (tachado) (S/.)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="precio_tachado"
                                    id="precio_tachado"
                                    placeholder="Opcional — ej. 120.00"
                                    value="<?= isset($producto['precio_tachado']) ? htmlspecialchars($producto['precio_tachado']) : '' ?>"
                                    class="_productoCrear_input">
                            </div>

                            <!-- Checkbox visibilidad precio tachado -->
                            <div class="_productoCrear_checkbox-group">
                                <input type="checkbox"
                                    name="precio_tachado_visible" id="precio_tachado_visible"
                                    <?= isset($producto['precio_tachado_visible']) && $producto['precio_tachado_visible'] ? 'checked' : (empty($producto) ? 'checked' : '') ?>
                                    class="_productoCrear_checkbox">
                                <label for="precio_tachado_visible" class="_productoCrear_checkbox-label">
                                    Mostrar precio tachado en la tarjeta
                                </label>
                            </div>

                            <!-- Precio Final -->
                            <div class="_productoCrear_form-group">
                                <label for="precio" class="_productoCrear_label">Precio Final (S/.)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="precio"
                                    id="precio"
                                    required
                                    value="<?= isset($producto['precio']) ? htmlspecialchars($producto['precio']) : '' ?>"
                                    class="_productoCrear_input">
                            </div>

                            <!-- Porcentaje descuento -->
                            <div class="_productoCrear_form-group">
                                <label for="porcentaje_descuento_readonly" class="_productoCrear_label">Porcentaje de Descuento</label>
                                <input type="text" id="porcentaje_descuento_readonly" readonly
                                    value="<?= isset($producto['precio_tachado']) && $producto['precio_tachado'] > $producto['precio'] ?
                                                number_format((($producto['precio_tachado'] - $producto['precio']) / $producto['precio_tachado']) * 100, 2) . '%' : '0.00%' ?>"
                                    class="_productoCrear_input-readonly">
                                <input type="hidden" name="porcentaje_descuento" id="porcentaje_descuento"
                                    value="<?= isset($producto['precio_tachado']) && $producto['precio_tachado'] > $producto['precio'] ?
                                                number_format((($producto['precio_tachado'] - $producto['precio']) / $producto['precio_tachado']) * 100, 2) : '0' ?>">
                            </div>

                            <!-- Checkbox visibilidad porcentaje -->
                            <div class="_productoCrear_checkbox-group">
                                <input type="checkbox"
                                    name="porcentaje_visible" id="porcentaje_visible"
                                    <?= isset($producto['porcentaje_visible']) && $producto['porcentaje_visible'] ? 'checked' : (empty($producto) ? 'checked' : '') ?>
                                    class="_productoCrear_checkbox">
                                <label for="porcentaje_visible" class="_productoCrear_checkbox-label">
                                    Mostrar porcentaje de descuento en la tarjeta
                                </label>
                            </div>
                        </div>

                        <!-- Stock -->
                        <div class="_productoCrear_form-group">
                            <label for="stock" class="_productoCrear_label">Stock Inicial</label>
                            <input type="number" name="stock" id="stock" required placeholder="Cantidad disponible" class="_productoCrear_input">
                        </div>

                        <!-- Visible -->
                        <div class="_productoCrear_checkbox-group">
                            <input type="checkbox" name="visible" id="visible" value="1" checked class="_productoCrear_checkbox">
                            <label for="visible" class="_productoCrear_checkbox-label">Producto visible en la tienda</label>
                        </div>

                        <!-- Imágenes -->
                        <div class="_productoCrear_form-group">
                            <label class="_productoCrear_label">Imágenes del Producto</label>
                            
                            <!-- Área de dropzone -->
                            <div class="_productoCrear_dropzone-container" id="dropzone-container">
                                <div class="_productoCrear_dropzone-area" id="dropzone-area">
                                    <div class="_productoCrear_dropzone-content">
                                        <span class="_productoCrear_upload-icon">📁</span>
                                        <p>Arrastra y suelta imágenes aquí o haz clic para seleccionar</p>
                                        <small>Formatos: JPG, PNG, WEBP (Máx. 5MB por imagen)</small>
                                    </div>
                                    <input type="file" name="imagenes[]" id="imagenes" multiple accept="image/*" class="_productoCrear_file-input">
                                </div>
                            </div>
                            
                            <!-- Contenedor de previsualización -->
                            <div class="_productoCrear_preview-container" id="preview-container">
                                <h4 class="_productoCrear_preview-title"> Vista Previa:</h4>
                                <div class="_productoCrear_preview-grid" id="preview-grid">
                                    <!-- Las miniaturas aparecerán aquí -->
                                </div>
                            </div>
                        </div>

                        <!-- Etiquetas -->
                        <div class="_productoCrear_form-group">
                            <label for="etiquetas" class="_productoCrear_label">Etiquetas</label>
                            <select name="etiquetas[]" id="etiquetas" multiple class="_productoCrear_select">
                                <?php foreach ($etiquetas as $et): ?>
                                    <option value="<?= $et['id'] ?>" <?= in_array($et['id'], $etiquetasAsignadas ?? []) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($et['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Categorías -->
                        <h3 class="_productoCrear_section-title"> Categorías</h3>
                        <div class="_productoCrear_categories-container">
                            <?php
                            function renderCheckboxCategorias($categorias, $padre = null, $nivel = 0)
                            {
                                foreach ($categorias as $cat) {
                                    if ($cat['id_padre'] == $padre) {
                                        $margen = $nivel * 20;
                                        echo "<div class='_productoCrear_category-item' style='margin-left: {$margen}px'>";
                                        echo "<label class='_productoCrear_checkbox-label'>";
                                        echo "<input type='checkbox' name='categorias[]' value='{$cat['id']}' class='_productoCrear_checkbox'> ";
                                        echo htmlspecialchars($cat['nombre']);
                                        echo "</label>";
                                        echo "</div>";
                                        renderCheckboxCategorias($categorias, $cat['id'], $nivel + 1);
                                    }
                                }
                            }
                            renderCheckboxCategorias($categorias);
                            ?>
                        </div>

                        <!-- Variantes -->
                        <h3 class="_productoCrear_section-title">🎨 Variantes del Producto</h3>
                        <div class="_productoCrear_variants-section">
                            <p class="_productoCrear_help-text" style="margin-bottom: 15px;">
                                <i class="fas fa-info-circle"></i> Puedes crear variantes con solo talla, solo color, o ambos. Al menos uno de los dos campos debe tener valor.
                            </p>
                            <div id="variantes-container" class="_productoCrear_variants-container">
                                <div class="_productoCrear_variant">
                                    <div class="_productoCrear_variant-field">
                                        <label class="_productoCrear_label">Talla (opcional)</label>
                                        <input type="text" name="variantes[talla][]" placeholder="Ej: S, M, L, XL" class="_productoCrear_input">
                                    </div>
                                    <div class="_productoCrear_variant-field">
                                        <label class="_productoCrear_label">Color (opcional)</label>
                                        <input type="text" name="variantes[color][]" placeholder="Ej: Rojo, Azul" class="_productoCrear_input">
                                    </div>
                                    <div class="_productoCrear_variant-field">
                                        <label class="_productoCrear_label">Stock</label>
                                        <input type="number" name="variantes[stock][]" placeholder="Cantidad" class="_productoCrear_input">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="_productoCrear_add-btn" onclick="agregarVariante()">+ Agregar Variante</button>
                        </div>

                        <!-- Destacado -->
                        <div class="_productoCrear_checkbox-group">
                            <input type="checkbox" name="destacado" id="destacado" value="1"
                                <?= isset($producto['destacado']) && $producto['destacado'] ? 'checked' : '' ?>
                                class="_productoCrear_checkbox">
                            <label for="destacado" class="_productoCrear_checkbox-label">Marcar como producto destacado ⭐</label>
                        </div>

                        <!-- Botones de acción -->
                        <div class="_productoCrear_actions">
                            <button type="submit" class="_productoCrear_submit-btn"> Guardar Producto</button>
                            <a href="<?= url('producto') ?>" class="_productoCrear_cancel-btn">← Atrás</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Validación de variantes antes de enviar el formulario
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('._productoCrear_form');
        
        form.addEventListener('submit', function(e) {
            const variantes = document.querySelectorAll('._productoCrear_variant');
            let variantesValidas = true;
            let mensajeError = [];
            
            variantes.forEach((variante, index) => {
                const tallaInput = variante.querySelector('input[name="variantes[talla][]"]');
                const colorInput = variante.querySelector('input[name="variantes[color][]"]');
                const stockInput = variante.querySelector('input[name="variantes[stock][]"]');
                
                const talla = tallaInput ? tallaInput.value.trim() : '';
                const color = colorInput ? colorInput.value.trim() : '';
                const stock = stockInput ? stockInput.value.trim() : '';
                
                // Si hay stock pero no hay ni talla ni color
                if (stock !== '' && stock !== '0' && talla === '' && color === '') {
                    variantesValidas = false;
                    mensajeError.push(`Variante ${index + 1}: Debes especificar al menos una talla o un color.`);
                    
                    // Resaltar los campos
                    if (tallaInput) tallaInput.style.borderColor = 'red';
                    if (colorInput) colorInput.style.borderColor = 'red';
                }
            });
            
            if (!variantesValidas) {
                e.preventDefault();
                alert('Error en variantes:\n\n' + mensajeError.join('\n'));
                return false;
            }
        });
    });

    function agregarVariante() {
        const container = document.getElementById('variantes-container');
        const html = `
            <div class="_productoCrear_variant">
                <div class="_productoCrear_variant-field">
                    <label class="_productoCrear_label">Talla (opcional)</label>
                    <input type="text" name="variantes[talla][]" placeholder="Ej: S, M, L, XL" class="_productoCrear_input">
                </div>
                <div class="_productoCrear_variant-field">
                    <label class="_productoCrear_label">Color (opcional)</label>
                    <input type="text" name="variantes[color][]" placeholder="Ej: Rojo, Azul" class="_productoCrear_input">
                </div>
                <div class="_productoCrear_variant-field">
                    <label class="_productoCrear_label">Stock</label>
                    <input type="number" name="variantes[stock][]" placeholder="Cantidad" class="_productoCrear_input">
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }

    // Resaltar input de imágenes
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('imagenes');
        fileInput.addEventListener('change', function() {
            const fileCount = this.files.length;
            if (fileCount > 0) {
                this.style.borderColor = '#28a745';
                this.style.backgroundColor = 'rgba(40, 167, 69, 0.1)';
            }
        });
    });

    // Cálculo dinámico del porcentaje de descuento
    (function() {
        const precioTachadoEl = document.getElementById('precio_tachado');
        const precioFinalEl = document.getElementById('precio');
        const porcentajeReadonlyEl = document.getElementById('porcentaje_descuento_readonly');
        const porcentajeHiddenEl = document.getElementById('porcentaje_descuento');

        function calcularPorcentaje(precioTachado, precioFinal) {
            if (!precioTachado || precioTachado <= 0) return 0;
            if (!precioFinal || precioFinal >= precioTachado) return 0;
            const diff = precioTachado - precioFinal;
            return parseFloat(((diff / precioTachado) * 100).toFixed(2));
        }

        function actualizarPorcentaje() {
            const precioTachado = parseFloat(precioTachadoEl.value) || 0;
            const precioFinal = parseFloat(precioFinalEl.value) || 0;
            const pct = calcularPorcentaje(precioTachado, precioFinal);

            porcentajeReadonlyEl.value = (pct > 0) ? pct.toFixed(2) + '%' : '0.00%';
            porcentajeHiddenEl.value = (pct > 0) ? pct.toFixed(2) : '0';
        }

        precioTachadoEl.addEventListener('input', actualizarPorcentaje);
        precioFinalEl.addEventListener('input', actualizarPorcentaje);

        document.addEventListener('DOMContentLoaded', actualizarPorcentaje);
    })();
</script>

<!-- Los otros scripts se mantienen igual que en tu código original -->
<script>
    // Sistema de búsqueda y selección de productos relacionados
    document.addEventListener('DOMContentLoaded', function() {
        const buscador = document.getElementById('buscador-productos');
        const container = document.getElementById('productos-relacionados-container');
        let checkboxesOriginales = container.innerHTML;

        function filtrarProductosLocales(termino) {
            const items = container.querySelectorAll('._productoCrear_checkbox-item');
            let encontrados = 0;

            items.forEach(item => {
                const nombre = item.getAttribute('data-product-name');
                if (nombre.includes(termino.toLowerCase())) {
                    item.style.display = 'block';
                    encontrados++;
                } else {
                    item.style.display = 'none';
                }
            });

            return encontrados;
        }

        function buscarProductosServidor(termino) {
            container.innerHTML = '<p>Buscando productos...</p>';

            fetch(`<?= url('producto/autocomplete') ?>?q=${encodeURIComponent(termino)}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error en la respuesta');
                    return response.json();
                })
                .then(productos => {
                    container.innerHTML = '';

                    if (productos.length === 0) {
                        container.innerHTML = '<p>No se encontraron productos</p>';
                        return;
                    }

                    productos.forEach(producto => {
                        const div = document.createElement('div');
                        div.className = '_productoCrear_checkbox-item';
                        div.setAttribute('data-product-id', producto.id);
                        div.setAttribute('data-product-name', producto.nombre.toLowerCase());
                        div.innerHTML = `
                        <label class="_productoCrear_checkbox-label">
                            <input type="checkbox" name="productos_relacionados[]" 
                                   value="${producto.id}"
                                   onchange="actualizarProductosSeleccionados(this)"
                                   class="_productoCrear_checkbox">
                            ${producto.nombre}
                        </label>
                    `;
                        container.appendChild(div);
                    });
                })
                .catch(error => {
                    console.error('Error en búsqueda:', error);
                    container.innerHTML = '<p>Error al buscar productos</p>';
                });
        }

        buscador.addEventListener('input', function() {
            const filtro = this.value.trim();

            if (filtro.length === 0) {
                container.innerHTML = checkboxesOriginales;
                setTimeout(() => {
                    document.querySelectorAll('._productoCrear_checkbox-item input[type="checkbox"]').forEach(checkbox => {
                        checkbox.onchange = function() {
                            actualizarProductosSeleccionados(this);
                        };
                    });
                }, 0);
            } else {
                const encontradosLocales = filtrarProductosLocales(filtro);

                if (encontradosLocales === 0 && filtro.length >= 2) {
                    buscarProductosServidor(filtro);
                }
            }
        });

        buscador.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    });

    function actualizarProductosSeleccionados(checkbox) {
        const productId = checkbox.value;
        const productName = checkbox.parentElement.textContent.trim();
        const listaSeleccionados = document.getElementById('lista-productos-seleccionados');
        const containerSeleccionados = document.getElementById('productos-seleccionados-container');

        if (checkbox.checked) {
            if (!document.querySelector(`._productoCrear_selected-item[data-product-id="${productId}"]`)) {
                const div = document.createElement('div');
                div.className = '_productoCrear_selected-item';
                div.setAttribute('data-product-id', productId);
                div.innerHTML = `
                <span>${productName}</span>
                <button type="button" class="_productoCrear_remove-btn" onclick="deseleccionarProducto(${productId})">×</button>
            `;
                listaSeleccionados.appendChild(div);
            }
        } else {
            const elemento = document.querySelector(`._productoCrear_selected-item[data-product-id="${productId}"]`);
            if (elemento) {
                elemento.remove();
            }
        }

        if (listaSeleccionados.children.length > 0) {
            containerSeleccionados.style.display = 'block';
        } else {
            containerSeleccionados.style.display = 'none';
        }
    }

    function deseleccionarProducto(productId) {
        const checkbox = document.querySelector(`._productoCrear_checkbox-item input[value="${productId}"]`);
        if (checkbox) {
            checkbox.checked = false;
        }

        const elemento = document.querySelector(`._productoCrear_selected-item[data-product-id="${productId}"]`);
        if (elemento) {
            elemento.remove();
        }

        const listaSeleccionados = document.getElementById('lista-productos-seleccionados');
        const containerSeleccionados = document.getElementById('productos-seleccionados-container');
        if (listaSeleccionados.children.length === 0) {
            containerSeleccionados.style.display = 'none';
        }
    }
</script>

<script>
    // Sistema de previsualización de imágenes
document.addEventListener('DOMContentLoaded', function() {
    const dropzoneArea = document.getElementById('dropzone-area');
    const fileInput = document.getElementById('imagenes');
    const previewContainer = document.getElementById('preview-container');
    const previewGrid = document.getElementById('preview-grid');
    const fileCount = document.createElement('div');
    
    fileCount.className = '_productoCrear_file-count';
    dropzoneArea.appendChild(fileCount);

    dropzoneArea.addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    dropzoneArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('_productoCrear_dragover');
    });

    dropzoneArea.addEventListener('dragleave', function() {
        this.classList.remove('_productoCrear_dragover');
    });

    dropzoneArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('_productoCrear_dragover');
        
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFiles(e.dataTransfer.files);
        }
    });

    function handleFiles(files) {
        if (files.length === 0) return;

        fileCount.textContent = files.length + ' archivos seleccionados';
        fileCount.style.display = 'block';

        previewContainer.style.display = 'block';

        Array.from(files).forEach((file, index) => {
            if (!file.type.startsWith('image/')) return;

            const reader = new FileReader();
            
            reader.onload = function(e) {
                createPreviewItem(e.target.result, file.name, file.size, index);
            };
            
            reader.readAsDataURL(file);
        });
    }

    function createPreviewItem(src, name, size, index) {
        const previewItem = document.createElement('div');
        previewItem.className = '_productoCrear_preview-item';
        previewItem.setAttribute('data-index', index);

        const sizeMB = (size / (1024 * 1024)).toFixed(2);
        
        previewItem.innerHTML = `
            <img src="${src}" alt="${name}" class="_productoCrear_preview-image">
            <div class="_productoCrear_preview-info">
                ${name.length > 15 ? name.substring(0, 15) + '...' : name}
                <br>${sizeMB} MB
            </div>
            <button type="button" class="_productoCrear_preview-remove" onclick="removePreviewImage(${index})">×</button>
        `;

        previewGrid.appendChild(previewItem);
    }
});

function removePreviewImage(index) {
    const fileInput = document.getElementById('imagenes');
    const files = Array.from(fileInput.files);
    files.splice(index, 1);
    
    const newFileList = new DataTransfer();
    files.forEach(file => newFileList.items.add(file));
    fileInput.files = newFileList.files;
    
    updatePreviewUI();
}

function updatePreviewUI() {
    const fileInput = document.getElementById('imagenes');
    const previewGrid = document.getElementById('preview-grid');
    const previewContainer = document.getElementById('preview-container');
    const fileCount = document.querySelector('._productoCrear_file-count');
    
    previewGrid.innerHTML = '';
    
    if (fileInput.files.length === 0) {
        previewContainer.style.display = 'none';
        fileCount.style.display = 'none';
        return;
    }
    
    fileCount.textContent = fileInput.files.length + ' archivos seleccionados';
    
    Array.from(fileInput.files).forEach((file, index) => {
        if (!file.type.startsWith('image/')) return;

        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewItem = document.createElement('div');
            previewItem.className = '_productoCrear_preview-item';
            previewItem.setAttribute('data-index', index);

            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            
            previewItem.innerHTML = `
                <img src="${e.target.result}" alt="${file.name}" class="_productoCrear_preview-image">
                <div class="_productoCrear_preview-info">
                    ${file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name}
                    <br>${sizeMB} MB
                </div>
                <button type="button" class="_productoCrear_preview-remove" onclick="removePreviewImage(${index})">×</button>
            `;

            previewGrid.appendChild(previewItem);
        };
        
        reader.readAsDataURL(file);
    });
}
</script>

</body>
</html>