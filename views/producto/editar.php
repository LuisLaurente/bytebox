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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Sistema de Gestión</title>
    <link rel="stylesheet" href="<?= url('/css/editar-producto.css') ?>">
    <?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        </aside>

        <!-- Main Content -->
        <div class="main-content">

            <!-- Main Content Area -->
            <main class="content">
                <div class="container">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h1 class="page-title">Editar Producto</h1>
                        <p class="page-description">Modifica la información del producto existente</p>
                    </div>

                    <!-- Session Alert -->
                    <?php if (isset($_SESSION['mensaje_carrito'])): ?>
                        <div id="mensaje-alerta" class="alert alert-success">
                            <span class="alert-message"><?= htmlspecialchars($_SESSION['mensaje_carrito']) ?></span>
                            <button id="cerrarAlerta" class="alert-close">✖</button>
                        </div>
                        <?php unset($_SESSION['mensaje_carrito']); ?>
                    <?php endif; ?>

                    <!-- Form Container -->
                    <div class="form-card">
                        <form action="<?= url('producto/actualizar') ?>" method="POST" enctype="multipart/form-data" class="product-form">
                            <input type="hidden" name="id" value="<?= $producto['id'] ?>">

                            <!-- Basic Information Section -->
                            <section class="form-section">
                                <h2 class="section-title">Información Básica</h2>
                                
                                <div class="form-grid">
                                    <!-- Nombre -->
                                    <div class="form-group full-width">
                                        <label for="nombre" class="form-label">Nombre del Producto *</label>
                                        <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required class="form-input">
                                    </div>

                                    <!-- Descripción -->
                                    <div class="form-group full-width">
                                        <label for="descripcion" class="form-label">Descripción *</label>
                                        <textarea name="descripcion" id="descripcion" required class="form-textarea"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                                    </div>

                                    <!-- Especificaciones -->
                                    <div class="form-group full-width">
                                        <label for="especificaciones" class="form-label">Especificaciones (una por línea)</label>
                                        <textarea name="especificaciones" id="especificaciones" rows="5" class="form-textarea"><?= htmlspecialchars($producto['especificaciones'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </section>

                            <!-- Pricing Section -->
                            <section class="form-section">
                                <h2 class="section-title">Precios y Descuentos</h2>
                                
                                <div class="form-grid">
                                    <!-- Precio Original -->
                                    <div class="form-group">
                                        <label for="precio_tachado" class="form-label">Precio Original (S/.)</label>
                                        <input type="number" step="0.01" min="0" name="precio_tachado" id="precio_tachado" 
                                               placeholder="120.00" value="<?= isset($producto['precio_tachado']) ? htmlspecialchars($producto['precio_tachado']) : '' ?>" class="form-input">
                                        <div class="form-checkbox-group">
                                            <input type="checkbox" name="precio_tachado_visible" id="precio_tachado_visible" 
                                                   <?= isset($producto['precio_tachado_visible']) && $producto['precio_tachado_visible'] ? 'checked' : (empty($producto) ? 'checked' : '') ?>>
                                            <label for="precio_tachado_visible" class="checkbox-label">Mostrar precio tachado</label>
                                        </div>
                                    </div>

                                    <!-- Precio Final -->
                                    <div class="form-group">
                                        <label for="precio" class="form-label">Precio Final (S/.) *</label>
                                        <input type="number" step="0.01" min="0" name="precio" id="precio" required 
                                               value="<?= isset($producto['precio']) ? htmlspecialchars($producto['precio']) : '' ?>" class="form-input">
                                    </div>

                                    <!-- Porcentaje Descuento -->
                                    <div class="form-group">
                                        <label for="porcentaje_descuento_readonly" class="form-label">% Descuento</label>
                                        <input type="text" id="porcentaje_descuento_readonly" readonly 
                                               value="<?= isset($producto['precio_tachado']) && $producto['precio_tachado'] > $producto['precio'] ?
                                                           number_format((($producto['precio_tachado'] - $producto['precio']) / $producto['precio_tachado']) * 100, 2) . '%' : '0.00%' ?>" 
                                               class="form-input readonly">
                                        <input type="hidden" name="porcentaje_descuento" id="porcentaje_descuento"
                                               value="<?= isset($producto['precio_tachado']) && $producto['precio_tachado'] > $producto['precio'] ?
                                                           number_format((($producto['precio_tachado'] - $producto['precio']) / $producto['precio_tachado']) * 100, 2) : '0' ?>">
                                        <div class="form-checkbox-group">
                                            <input type="checkbox" name="porcentaje_visible" id="porcentaje_visible"
                                                   <?= isset($producto['porcentaje_visible']) && $producto['porcentaje_visible'] ? 'checked' : (empty($producto) ? 'checked' : '') ?>>
                                            <label for="porcentaje_visible" class="checkbox-label">Mostrar porcentaje</label>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Inventory Section -->
                            <section class="form-section">
                                <h2 class="section-title">Inventario y Visibilidad</h2>
                                
                                <div class="form-grid">
                                    <!-- Stock -->
                                    <div class="form-group">
                                        <label for="stock" class="form-label">Stock *</label>
                                        <input type="number" name="stock" id="stock" value="<?= htmlspecialchars($producto['stock']) ?>" required class="form-input">
                                    </div>

                                    <!-- Visible -->
                                    <div class="form-group">
                                        <div class="form-checkbox-group standalone">
                                            <input type="checkbox" name="visible" id="visible" value="1" <?= $producto['visible'] ? 'checked' : '' ?>>
                                            <label for="visible" class="checkbox-label">Producto visible en tienda</label>
                                        </div>
                                    </div>

                                    <!-- Destacado -->
                                    <div class="form-group">
                                        <div class="form-checkbox-group standalone">
                                            <input type="checkbox" name="destacado" value="1" <?= $producto['destacado'] ? 'checked' : '' ?>>
                                            <label class="checkbox-label">Producto destacado</label>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Related Products Section -->
                            <section class="form-section">
                                <h2 class="section-title">Productos Relacionados</h2>
                                
                                <div class="form-group">
                                    <label for="buscador-productos" class="form-label">🔍 Buscar Productos Relacionados</label>
                                    <input type="text" id="buscador-productos" placeholder="Escribe para buscar productos..." class="form-input search-input">

                                    <!-- Selected Products -->
                                    <div class="selected-products-container" id="productos-seleccionados-container" 
                                         style="<?= empty($producto['productos_relacionados']) ? 'display: none;' : '' ?>">
                                        <h4 class="selected-products-title"> Productos Seleccionados:</h4>
                                        <div id="lista-productos-seleccionados" class="selected-products-list">
                                            <?php if (!empty($producto['productos_relacionados'])): ?>
                                                <?php foreach ($allProducts as $p): ?>
                                                    <?php if (in_array((int)$p['id'], $producto['productos_relacionados'] ?? [])): ?>
                                                        <div class="selected-product-item" data-product-id="<?= (int)$p['id'] ?>">
                                                            <span class="nombreRelacionado"><?= htmlspecialchars($p['nombre']) ?></span>
                                                            <button type="button" class="remove-selection-btn" onclick="deseleccionarProducto(<?= (int)$p['id'] ?>)">×</button>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Checkboxes Container -->
                                    <div class="checkboxes-container" id="productos-relacionados-container">
                                        <?php foreach ($allProducts as $p): ?>
                                            <?php if (isset($producto['id']) && $p['id'] == $producto['id']) continue; ?>
                                            <div class="checkbox-item" data-product-id="<?= (int)$p['id'] ?>" data-product-name="<?= htmlspecialchars(strtolower($p['nombre'])) ?>">
                                                <label class="checkbox-label">
                                                    <input type="checkbox" name="productos_relacionados[]" value="<?= (int)$p['id'] ?>"
                                                           <?= in_array((int)$p['id'], $producto['productos_relacionados'] ?? []) ? 'checked' : '' ?>
                                                           onchange="actualizarProductosSeleccionados(this)">
                                                    <?= htmlspecialchars($p['nombre']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="form-help">Selecciona los productos relacionados con checkboxes.</small>
                                </div>
                            </section>

                            <!-- Images Section -->
                            <section class="form-section">
                                <h2 class="section-title"> Imágenes del Producto</h2>
                                
                                <!-- Current Images -->
                                <?php if (!empty($imagenes)): ?>
                                    <div class="current-images">
                                        <h4 class="images-subtitle"> Imágenes Actuales:</h4>
                                        <div class="images-grid">
                                            <?php foreach ($imagenes as $img): ?>
                                                <div class="image-item existing-image">
                                                    <img src="<?= url('uploads/' . $img['nombre_imagen']) ?>" alt="Imagen" class="image-preview">
                                                    <div class="image-info">
                                                        <?= htmlspecialchars($img['nombre_imagen']) ?>
                                                        <br>Imagen actual
                                                    </div>
                                                    <a href="<?= url('imagen/eliminar/' . $img['id']) ?>" class="image-remove-btn"
                                                       onclick="return confirm('¿Eliminar esta imagen permanentemente?')">×</a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- New Images Upload -->
                                <div class="upload-section">
                                    <div class="dropzone-container" id="dropzone-container">
                                        <div class="dropzone-area" id="dropzone-area">
                                            <div class="dropzone-content">
                                                <i class="upload-icon">📁</i>
                                                <p>Arrastra y suelta NUEVAS imágenes aquí o haz clic para seleccionar</p>
                                                <small>Formatos: JPG, PNG, WEBP (Máx. 5MB por imagen)</small>
                                            </div>
                                            <input type="file" name="imagenes[]" id="imagenes" multiple accept="image/*" class="file-input">
                                        </div>
                                    </div>

                                    <!-- New Images Preview -->
                                    <div class="preview-container" id="preview-container">
                                        <h4 class="images-subtitle"> Nuevas Imágenes:</h4>
                                        <div class="images-grid" id="preview-grid">
                                            <!-- Las miniaturas de nuevas imágenes aparecerán aquí -->
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Categories & Tags Section -->
                            <section class="form-section">
                                <h2 class="section-title">Categorías y Etiquetas</h2>
                                
                                <div class="form-grid">
                                    <!-- Etiquetas -->
                                    <div class="form-group">
                                        <label for="etiquetas" class="form-label"> Etiquetas</label>
                                        <select name="etiquetas[]" id="etiquetas" multiple class="form-select multiple">
                                            <?php foreach ($etiquetas as $et): ?>
                                                <option value="<?= $et['id'] ?>" <?= in_array($et['id'], $etiquetasAsignadas ?? []) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($et['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Categorías -->
                                    <div class="form-group full-width">
                                        <label class="form-label"> Categorías</label>
                                        <div class="categories-container">
                                            <?php
                                            function renderCheckboxCategoriasEdit($categorias, $seleccionadas, $padre = null, $nivel = 0)
                                            {
                                                foreach ($categorias as $cat) {
                                                    if ($cat['id_padre'] == $padre) {
                                                        $checked = in_array($cat['id'], $seleccionadas) ? 'checked' : '';
                                                        $margin = $nivel * 20;
                                                        echo "<div class='category-item' style='margin-left: {$margin}px'>";
                                                        echo "<label class='checkbox-label'><input type='checkbox' name='categorias[]' value='{$cat['id']}' $checked> " . htmlspecialchars($cat['nombre']) . "</label>";
                                                        echo "</div>";
                                                        renderCheckboxCategoriasEdit($categorias, $seleccionadas, $cat['id'], $nivel + 1);
                                                    }
                                                }
                                            }
                                            renderCheckboxCategoriasEdit($categorias, $categoriasAsignadas);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Variants Section -->
                            <section class="form-section">
                                <h2 class="section-title"> Variantes del Producto</h2>
                                <p class="form-help" style="margin-bottom: 15px;">
                                    <i class="fas fa-info-circle"></i> Puedes crear variantes con solo talla, solo color, o ambos. Al menos uno de los dos campos debe tener valor.
                                </p>
                                
                                <!-- Script para almacenar imágenes disponibles -->
                                <script>
                                    // Almacenar las imágenes disponibles del producto en JavaScript
                                    window.imagenesDisponibles = <?= json_encode(array_map(function($img) {
                                        return [
                                            'valor' => $img['nombre_imagen'],
                                            'nombreCorto' => strlen($img['nombre_imagen']) > 20 ? '...' . substr($img['nombre_imagen'], -17) : $img['nombre_imagen']
                                        ];
                                    }, $imagenes ?? [])) ?>;
                                    
                                </script>
                                
                                <div id="variantes-container" class="variants-container">
                                    <?php if (!empty($variantes)): ?>
                                        <?php foreach ($variantes as $var): ?>
                                            <div class="variant-item">
                                                <input type="hidden" name="variantes[id][]" value="<?= $var['id'] ?>">
                                                <div class="variant-field">
                                                    <label>Talla/Modelo (opcional)</label>
                                                    <input type="text" name="variantes[talla][]" value="<?= htmlspecialchars($var['talla'] ?? '') ?>" placeholder="Ej: S, M, L" class="form-input">
                                                </div>
                                                <div class="variant-field">
                                                    <label>Color (opcional)</label>
                                                    <input type="text" name="variantes[color][]" value="<?= htmlspecialchars($var['color'] ?? '') ?>" placeholder="Ej: Rojo, Azul" class="form-input">
                                                </div>
                                                <div class="variant-field">
                                                    <label>Stock</label>
                                                    <input type="number" name="variantes[stock][]" value="<?= htmlspecialchars($var['stock'] ?? '0') ?>" class="form-input">
                                                </div>
                                                <div class="variant-field">
                                                    <label>Imagen 🖼️</label>
                                                    <select name="variantes[imagen][]" 
                                                            class="form-input variante-imagen-select"
                                                            data-variante-id="<?= $var['id'] ?>"
                                                            title="Selecciona una imagen de la galería del producto">
                                                        <option value="">Sin imagen</option>
                                                        <?php
                                                        // Obtener todas las imágenes del producto desde imagenes_producto
                                                        $imagenesProducto = [];
                                                        if (!empty($imagenes)) {
                                                            foreach ($imagenes as $imgData) {
                                                                $imagenesProducto[] = $imgData['nombre_imagen'];
                                                            }
                                                        }
                                                        
                                                        foreach ($imagenesProducto as $img):
                                                            $selected = ($var['imagen'] ?? '') === $img ? 'selected' : '';
                                                            // Mostrar nombre corto
                                                            $nombreCorto = strlen($img) > 20 ? '...' . substr($img, -17) : $img;
                                                        ?>
                                                            <option value="<?= htmlspecialchars($img) ?>" <?= $selected ?>>
                                                                <?= htmlspecialchars($nombreCorto) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small style="color: #666; font-size: 0.85em;">De la galería</small>
                                                </div>
                                                <a href="<?= url('variante/eliminar/' . $var['id'] . '?producto_id=' . $producto['id']) ?>" 
                                                   class="delete-variant-btn" onclick="return confirm('¿Eliminar esta variante?')">❌ Eliminar</a>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="no-variants">No hay variantes registradas.</p>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-secondary" onclick="agregarVariante()">+ Agregar Variante</button>
                            </section>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary"> Guardar Cambios</button>
                                <a href="<?= url('producto') ?>" class="btn btn-secondary">← Atrás</a>
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
        const form = document.querySelector('.product-form');
        
        form.addEventListener('submit', function(e) {
            const variantes = document.querySelectorAll('.variant-item');
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
                    if (tallaInput) {
                        tallaInput.style.borderColor = 'red';
                        tallaInput.style.boxShadow = '0 0 5px rgba(220, 53, 69, 0.5)';
                    }
                    if (colorInput) {
                        colorInput.style.borderColor = 'red';
                        colorInput.style.boxShadow = '0 0 5px rgba(220, 53, 69, 0.5)';
                    }
                    
                    // Quitar resaltado después de 3 segundos
                    setTimeout(() => {
                        if (tallaInput) {
                            tallaInput.style.borderColor = '';
                            tallaInput.style.boxShadow = '';
                        }
                        if (colorInput) {
                            colorInput.style.borderColor = '';
                            colorInput.style.boxShadow = '';
                        }
                    }, 3000);
                }
            });
            
            if (!variantesValidas) {
                e.preventDefault();
                alert('❌ Error en variantes:\n\n' + mensajeError.join('\n'));
                return false;
            }
        });
    });

    function agregarVariante() {
        const container = document.getElementById('variantes-container');
        
        // ⭐ NUEVO: Construir opciones desde la variable global
        let opcionesImagenes = '<option value="">Sin imagen</option>';
        
        // Verificar si hay imágenes disponibles en la variable global
        if (window.imagenesDisponibles && window.imagenesDisponibles.length > 0) {
            console.log('✅ Usando imágenes de window.imagenesDisponibles');
            window.imagenesDisponibles.forEach(img => {
                opcionesImagenes += `<option value="${escapeHtml(img.valor)}">${escapeHtml(img.nombreCorto)}</option>`;
            });
        } else {
            // Fallback: intentar copiar del primer select si existe
            console.log('⚠️ Fallback: intentando copiar del primer select');
            const primeraVariante = container.querySelector('.variant-item');
            if (primeraVariante) {
                const primerSelect = primeraVariante.querySelector('.variante-imagen-select');
                if (primerSelect) {
                    Array.from(primerSelect.options).forEach(option => {
                        if (option.value !== '') {
                            opcionesImagenes += `<option value="${escapeHtml(option.value)}">${escapeHtml(option.textContent)}</option>`;
                        }
                    });
                }
            }
        }
        
        const html = `
            <div class="variant-item new-variant-item" style="animation: slideIn 0.3s ease-out;">
                <input type="hidden" name="variantes[id][]" value="0">
                <div class="variant-field">
                    <label>Talla (opcional)</label>
                    <input type="text" name="variantes[talla][]" placeholder="Ej: S, M, L" class="form-input">
                </div>
                <div class="variant-field">
                    <label>Color (opcional)</label>
                    <input type="text" name="variantes[color][]" placeholder="Ej: Rojo, Azul" class="form-input">
                </div>
                <div class="variant-field">
                    <label>Stock</label>
                    <input type="number" name="variantes[stock][]" value="0" class="form-input">
                </div>
                <div class="variant-field">
                    <label>Imagen 🖼️</label>
                    <select name="variantes[imagen][]" class="form-input" title="Selecciona una imagen de la galería">
                        ${opcionesImagenes}
                    </select>
                    <small style="color: #666; font-size: 0.85em;">De la galería</small>
                </div>
                <button type="button" class="delete-variant-btn" onclick="eliminarVarianteNueva(this)">❌ Eliminar</button>
            </div>`;
        
        // Remover mensaje "no hay variantes" si existe
        const noVariantsMsg = container.querySelector('.no-variants');
        if (noVariantsMsg) {
            noVariantsMsg.remove();
        }
        
        container.insertAdjacentHTML('beforeend', html);
        console.log('✅ Nueva variante agregada con', window.imagenesDisponibles?.length || 0, 'imágenes disponibles');
    }
    
    // ⭐ NUEVA: Función para escapar HTML y prevenir XSS
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
    
    // Función para eliminar variantes nuevas (no guardadas en BD)
    function eliminarVarianteNueva(button) {
        if (confirm('¿Eliminar esta variante?')) {
            const variantItem = button.closest('.variant-item');
            variantItem.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                variantItem.remove();
                
                // Si no quedan variantes, mostrar mensaje
                const container = document.getElementById('variantes-container');
                if (container.querySelectorAll('.variant-item').length === 0) {
                    container.innerHTML = '<p class="no-variants">No hay variantes registradas.</p>';
                }
            }, 300);
        }
    }
    
    // Agregar animaciones CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
        
        .new-variant-item {
            border-left: 3px solid #4CAF50 !important;
        }
    `;
    document.head.appendChild(style);
</script>
<script>
    (function() {
        const precioTachadoEl = document.getElementById('precio_tachado');
        const precioFinalEl = document.getElementById('precio');
        const porcentajeReadonlyEl = document.getElementById('porcentaje_descuento_readonly');
        const porcentajeHiddenEl = document.getElementById('porcentaje_descuento');

        // Calcula porcentaje a partir del precio tachado y precio final
        function calcularPorcentaje(precioTachado, precioFinal) {
            if (!precioTachado || precioTachado <= 0) return 0;
            // Si precioFinal >= precioTachado no hay descuento
            if (!precioFinal || precioFinal >= precioTachado) return 0;
            const diff = precioTachado - precioFinal;
            const pct = (diff / precioTachado) * 100;
            return parseFloat(pct.toFixed(2));
        }

        // Actualiza la UI y el hidden
        function actualizarPorcentajeUI(pct) {
            porcentajeReadonlyEl.value = (pct > 0) ? pct.toFixed(2) + '%' : '0.00%';
            porcentajeHiddenEl.value = (pct > 0) ? pct.toFixed(2) : '0';
        }

        // Handler cuando cambia precio tachado o precio final
        function onChangeCampos() {
            const precioTachado = parseFloat(precioTachadoEl.value) || 0;
            const precioFinal = parseFloat(precioFinalEl.value) || 0;

            const pct = calcularPorcentaje(precioTachado, precioFinal);
            actualizarPorcentajeUI(pct);
        }

        // Listeners
        precioTachadoEl.addEventListener('input', function() {
            // El admin puede editar precio tachado; no forzamos cambios en precio final,
            // solo recalculamos % informativo.
            onChangeCampos();
        });

        precioFinalEl.addEventListener('input', function() {
            // El admin edita el precio final (este es el campo importante).
            // Recalculamos % informativo basado en precio_tachado (si existe).
            onChangeCampos();
        });

        // Inicializa al cargar
        document.addEventListener('DOMContentLoaded', function() {
            onChangeCampos();
        });
    })();
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buscador = document.getElementById('buscador-productos');
        const container = document.getElementById('productos-relacionados-container');
        const containerSeleccionados = document.getElementById('productos-seleccionados-container');
        const listaSeleccionados = document.getElementById('lista-productos-seleccionados');
        let checkboxesOriginales = container.innerHTML;

        // Inicializar productos seleccionados al cargar
        actualizarVisibilidadSeleccionados();

        // Función para filtrar productos locales
        function filtrarProductosLocales(termino) {
            const items = container.querySelectorAll('.checkbox-item');
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

        // Función para buscar productos en el servidor
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
                        const estabaSeleccionado = <?= json_encode($producto['productos_relacionados'] ?? []) ?>.includes(producto.id);

                        const div = document.createElement('div');
                        div.className = 'checkbox-item';
                        div.setAttribute('data-product-id', producto.id);
                        div.setAttribute('data-product-name', producto.nombre.toLowerCase());
                        div.innerHTML = `
                        <label>
                            <input type="checkbox" name="productos_relacionados[]" 
                                   value="${producto.id}" ${estabaSeleccionado ? 'checked' : ''}
                                   onchange="actualizarProductosSeleccionados(this)">
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

        // Event listener para el buscador
        buscador.addEventListener('input', function() {
            const filtro = this.value.trim();

            if (filtro.length === 0) {
                container.innerHTML = checkboxesOriginales;
                // Re-asignar event listeners a los checkboxes restaurados
                setTimeout(() => {
                    document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(checkbox => {
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

        // Prevenir envío del formulario al presionar Enter en el buscador
        buscador.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    });

    // Función global para actualizar la lista de productos seleccionados
    function actualizarProductosSeleccionados(checkbox) {
        const productId = checkbox.value;
        const productName = checkbox.parentElement.textContent.trim();
        const listaSeleccionados = document.getElementById('lista-productos-seleccionados');

        if (checkbox.checked) {
            // Agregar a la lista de seleccionados
            if (!document.querySelector(`.producto-seleccionado[data-product-id="${productId}"]`)) {
                const div = document.createElement('div');
                div.className = 'producto-seleccionado';
                div.setAttribute('data-product-id', productId);
                div.innerHTML = `
                <span>${productName}</span>
                <button type="button" class="btn-eliminar-seleccion" onclick="deseleccionarProducto(${productId})">×</button>
            `;
                listaSeleccionados.appendChild(div);
            }
        } else {
            // Remover de la lista de seleccionados
            const elemento = document.querySelector(`.producto-seleccionado[data-product-id="${productId}"]`);
            if (elemento) {
                elemento.remove();
            }
        }

        actualizarVisibilidadSeleccionados();
    }

    // Función para deseleccionar un producto desde el botón ×
    function deseleccionarProducto(productId) {
        // Desmarcar el checkbox correspondiente
        const checkbox = document.querySelector(`.checkbox-item input[value="${productId}"]`);
        if (checkbox) {
            checkbox.checked = false;
        }

        // Remover de la lista de seleccionados
        const elemento = document.querySelector(`.producto-seleccionado[data-product-id="${productId}"]`);
        if (elemento) {
            elemento.remove();
        }

        actualizarVisibilidadSeleccionados();
    }

    // Función para mostrar/ocultar el contenedor de seleccionados
    function actualizarVisibilidadSeleccionados() {
        const container = document.getElementById('productos-seleccionados-container');
        const lista = document.getElementById('lista-productos-seleccionados');

        if (lista.children.length > 0) {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    }
</script>
<script>
    // Sistema de previsualización de imágenes PARA EDITAR
    document.addEventListener('DOMContentLoaded', function() {
        const dropzoneArea = document.getElementById('dropzone-area');
        const fileInput = document.getElementById('imagenes');
        const previewContainer = document.getElementById('preview-container');
        const previewGrid = document.getElementById('preview-grid');
        const fileCount = document.createElement('div');

        fileCount.className = 'file-count';
        fileCount.style.display = 'none';
        dropzoneArea.appendChild(fileCount);

        // Click en el dropzone
        dropzoneArea.addEventListener('click', function() {
            fileInput.click();
        });

        // Cambio en el input de archivo
        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
        });

        // Drag and drop
        dropzoneArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        dropzoneArea.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });

        dropzoneArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');

            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFiles(e.dataTransfer.files);
            }
        });

        // Función para manejar archivos
        function handleFiles(files) {
            if (files.length === 0) return;

            // Actualizar contador
            fileCount.textContent = files.length;
            fileCount.style.display = 'block';

            // Mostrar contenedor de previsualización
            previewContainer.style.display = 'block';

            // Procesar cada archivo
            Array.from(files).forEach((file, index) => {
                if (!file.type.startsWith('image/')) return;

                const reader = new FileReader();

                reader.onload = function(e) {
                    createPreviewItem(e.target.result, file.name, file.size, index);
                };

                reader.readAsDataURL(file);
            });
        }

        // Función para crear item de previsualización
        function createPreviewItem(src, name, size, index) {
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';
            previewItem.setAttribute('data-index', index);

            const sizeMB = (size / (1024 * 1024)).toFixed(2);

            previewItem.innerHTML = `
            <img src="${src}" alt="${name}" class="preview-image">
            <div class="preview-info">
                ${name.length > 15 ? name.substring(0, 15) + '...' : name}
                <br>${sizeMB} MB
            </div>
            <button type="button" class="preview-remove" onclick="removePreviewImage(${index})">×</button>
        `;

            previewGrid.appendChild(previewItem);
        }
    });

    // Función para eliminar imagen de la previsualización (NUEVAS imágenes)
    function removePreviewImage(index) {
        const fileInput = document.getElementById('imagenes');
        const files = Array.from(fileInput.files);
        files.splice(index, 1);

        // Crear nuevo FileList
        const newFileList = new DataTransfer();
        files.forEach(file => newFileList.items.add(file));
        fileInput.files = newFileList.files;

        // Actualizar UI
        updatePreviewUI();
    }

    // Función para actualizar la UI después de eliminar
    function updatePreviewUI() {
        const fileInput = document.getElementById('imagenes');
        const previewGrid = document.getElementById('preview-grid');
        const previewContainer = document.getElementById('preview-container');
        const fileCount = document.querySelector('.file-count');

        // Limpiar previsualización
        previewGrid.innerHTML = '';

        if (fileInput.files.length === 0) {
            previewContainer.style.display = 'none';
            fileCount.style.display = 'none';
            return;
        }

        // Actualizar contador
        fileCount.textContent = fileInput.files.length;

        // Recrear previsualizaciones
        Array.from(fileInput.files).forEach((file, index) => {
            if (!file.type.startsWith('image/')) return;

            const reader = new FileReader();

            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.setAttribute('data-index', index);

                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);

                previewItem.innerHTML = `
                <img src="${e.target.result}" alt="${file.name}" class="preview-image">
                <div class="preview-info">
                    ${file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name}
                    <br>${sizeMB} MB
                </div>
                <button type="button" class="preview-remove" onclick="removePreviewImage(${index})">×</button>
            `;

                previewGrid.appendChild(previewItem);
            };

            reader.readAsDataURL(file);
        });
    }
</script><script>
    //  AJAX para guardar imagen de variante automáticamente
    document.addEventListener('DOMContentLoaded', function() {
        // Event listener para cambios en el selector de imagen de variante
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('variante-imagen-select')) {
                const varianteId = e.target.dataset.varianteId;
                const imagen = e.target.value;
                const selectElement = e.target;
                
                // Deshabilitar temporalmente el select
                selectElement.disabled = true;
                selectElement.style.opacity = '0.6';
                
                // Guardar automáticamente vía AJAX
                fetch('<?= url('variante/actualizar_imagen') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${varianteId}&imagen=${encodeURIComponent(imagen)}`
                })
                .then(response => response.json())
                .then(data => {
                    // Re-habilitar el select
                    selectElement.disabled = false;
                    selectElement.style.opacity = '1';
                    
                    if (data.success) {
                        // Mostrar feedback visual exitoso
                        selectElement.style.borderColor = '#28a745';
                        selectElement.style.boxShadow = '0 0 5px rgba(40, 167, 69, 0.5)';
                        
                        // Crear mensaje de éxito temporal
                        const successMsg = document.createElement('small');
                        successMsg.textContent = ' Guardado';
                        successMsg.style.color = '#28a745';
                        successMsg.style.fontWeight = 'bold';
                        successMsg.style.marginLeft = '8px';
                        selectElement.parentElement.appendChild(successMsg);
                        
                        setTimeout(() => {
                            selectElement.style.borderColor = '';
                            selectElement.style.boxShadow = '';
                            if (successMsg.parentElement) {
                                successMsg.remove();
                            }
                        }, 2000);
                    } else {
                        // Mostrar error
                        selectElement.style.borderColor = '#dc3545';
                        alert('Error al guardar la imagen: ' + (data.message || 'Error desconocido'));
                        setTimeout(() => {
                            selectElement.style.borderColor = '';
                        }, 2000);
                    }
                })
                .catch(error => {
                    // Re-habilitar el select
                    selectElement.disabled = false;
                    selectElement.style.opacity = '1';
                    selectElement.style.borderColor = '#dc3545';
                    
                    console.error('Error:', error);
                    alert('Error al guardar la imagen. Por favor intenta de nuevo.');
                    
                    setTimeout(() => {
                        selectElement.style.borderColor = '';
                    }, 2000);
                });
            }
        });
    });
</script>
