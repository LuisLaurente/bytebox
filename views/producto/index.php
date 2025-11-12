<?php
// Asegura que la sesión esté activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializa contador del carrito (manteniendo lógica existente)
$cantidadEnCarrito = 0;
if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidadEnCarrito += (int)($item['cantidad'] ?? 0);
    }
}

// Recupera el término de búsqueda actual de $_GET
$busquedaActual = htmlspecialchars($_GET['q'] ?? '');

// Comprobación simple para determinar si los filtros deben estar abiertos
$filtrosAbiertos = !empty($_GET['min_price']) || !empty($_GET['max_price']) || !empty($_GET['categoria']) || !empty($_GET['etiquetas']) || !empty($_GET['orden']);
$filterCardClass = $filtrosAbiertos ? '_productoIndex_card-open' : '_productoIndex_card-closed';
?>
<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
    <link rel="stylesheet" href="<?= url('/css/productoIndex.css') ?>">
    <style>
        /* Estilos básicos para colapsable, el resto va en productoIndex.css */
        ._productoIndex_filter-content-wrapper {
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
            overflow: hidden;
        }
        ._productoIndex_filter-content-wrapper.collapsed {
            max-height: 0;
            opacity: 0;
            margin-top: 0;
        }
        ._productoIndex_filter-content-wrapper:not(.collapsed) {
            max-height: 1000px; /* Suficientemente grande para mostrar contenido */
            opacity: 1;
        }
    </style>

<body class="_productoIndex_body" data-base-url="<?= htmlspecialchars(url('producto')) ?>">

<div class="_productoIndex_layout">
  <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

  <div class="_productoIndex_main">
    <main class="_productoIndex_content">
      <div class="_productoIndex_container">

        <div class="_productoIndex_page-header">
          <h1 class="_productoIndex_title"> Gestión de Productos</h1>
          <p class="_productoIndex_subtitle">Administra el catálogo completo de productos</p>
        </div>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div id="mensaje-alerta" class="_productoIndex_alert _productoIndex_alert-success">
                <span class="_productoIndex_alert-text"><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
                <button id="cerrarAlerta" class="_productoIndex_alert-close">✖</button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php elseif (isset($_SESSION['flash_error'])): ?>
            <div id="mensaje-alerta" class="_productoIndex_alert _productoIndex_alert-error">
                <span class="_productoIndex_alert-text"><?= htmlspecialchars(is_array($_SESSION['flash_error']) ? implode('<br>', $_SESSION['flash_error']) : $_SESSION['flash_error']) ?></span>
                <button id="cerrarAlerta" class="_productoIndex_alert-close">✖</button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>


        <div class="_productoIndex_actions"> 
            <a href="<?= url('cargaMasiva/descargarPlantilla') ?>" class="_productoIndex_action-btn _productoIndex_action-blue">  Descargar Plantilla CSV </a> 
            <a href="<?= url('cargaMasiva/gestionImagenes') ?>" class="_productoIndex_action-btn _productoIndex_action-purple">  Gestión Imágenes </a>
            <a href="<?= url('producto/crear') ?>" class="_productoIndex_action-btn _productoIndex_action-green"> Nuevo Producto</a>
        </div>

        <div class="_productoIndex_card">
          <h2 class="_productoIndex_card-title">📂 Carga masiva (CSV)</h2>
          <p class="_productoIndex_card-text">Sube un CSV para crear o actualizar productos. Formato: (sku,nombre,descripcion,precio,...)</p>

          <form action="<?= url('cargaMasiva/procesarCSV') ?>" method="POST" enctype="multipart/form-data" class="_productoIndex_upload-form">
            <label for="archivo_csv" class="_productoIndex_file-label">
              📂 Elegir archivo
            </label>
            <input id="archivo_csv" name="archivo_csv" type="file" accept=".csv" required class="_productoIndex_file-input">
            <span id="archivoNombre" class="_productoIndex_file-name">Ningún archivo seleccionado</span>

            <button type="submit" class="_productoIndex_upload-btn">
              📤 Subir CSV
            </button>
          </form>
        </div>
        
        <form id="filtroForm" method="GET" action="<?= url('producto') ?>" class="_productoIndex_filter-form-master">
            
            <div class="_productoIndex_search-container">
                <div class="_productoIndex_search-wrapper">
                    <input 
                    type="search" 
                    id="busquedaProducto" 
                    name="q" 
                    placeholder="Buscar por nombre o SKU..." 
                    value="<?= $busquedaActual ?>"
                    class="_productoIndex_search-input"
                    >
                    <button type="submit" class="_productoIndex_search-icon" title="Buscar">🔍</button>
                </div>
            </div>

            <div id="filterCard" class="_productoIndex_card <?= $filterCardClass ?>">
                <h3 id="toggleFiltros" class="_productoIndex_card-title _productoIndex_toggle-btn">
                    <span>🔍 Filtros avanzados</span>
                    <span id="filterToggleIcon" style="font-size: 1.2em; transition: transform 0.3s; transform: rotate(<?= $filtrosAbiertos ? '180deg' : '0deg' ?>);">▼</span>
                </h3>

                <div id="filterContent" class="_productoIndex_filter-content-wrapper <?= $filtrosAbiertos ? '' : 'collapsed' ?>">

                    <?php if (!empty($estadisticasPrecios)): ?>
                    <div class="_productoIndex_stats">
                        <div>Rango: <span class="_productoIndex_stat-value">S/ <?= number_format($estadisticasPrecios['precio_minimo'] ?? 0, 2) ?> - S/ <?= number_format($estadisticasPrecios['precio_maximo'] ?? 0, 2) ?></span></div>
                        <div>Promedio: <span class="_productoIndex_stat-value">S/ <?= number_format($estadisticasPrecios['precio_promedio'] ?? 0, 2) ?></span></div>
                        <div>Total en catálogo: <span class="_productoIndex_stat-value"><?= htmlspecialchars($estadisticasPrecios['total_productos']) ?></span></div>
                        <?php if (isset($totalFiltrados)): ?>
                            <div id="totalFiltradosDisplay">Mostrando: <span class="_productoIndex_stat-value"><?= htmlspecialchars($totalFiltrados) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="_productoIndex_filter-grid">
                        <div class="_productoIndex_form-group">
                        <label for="min_price" class="_productoIndex_label">Precio min (S/):</label>
                        <input id="min_price" name="min_price" type="number" step="1" min="0" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>"
                                class="_productoIndex_input">
                        </div>

                        <div class="_productoIndex_form-group">
                        <label for="max_price" class="_productoIndex_label">Precio max (S/):</label>
                        <input id="max_price" name="max_price" type="number" step="1" min="0" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>"
                                class="_productoIndex_input">
                        </div>

                        <div class="_productoIndex_form-group">
                        <label for="categoria" class="_productoIndex_label">Categoría:</label>
                        <select id="categoria" name="categoria" class="_productoIndex_select">
                            <option value="">-- Todas --</option>
                            <?php foreach ($categoriasDisponibles as $categoria): ?>
                            <option value="<?= htmlspecialchars($categoria['id']) ?>" <?= (($_GET['categoria'] ?? '') == $categoria['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['nombre']) ?> (<?= htmlspecialchars($categoria['total_productos']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        </div>
                        
                        <div class="_productoIndex_form-group">
                            <label for="orden" class="_productoIndex_label">Ordenar por:</label>
                            <select id="orden" name="orden" class="_productoIndex_select">
                                <option value="">-- Relevancia --</option>
                                <option value="precio_asc" <?= (($_GET['orden'] ?? '') === 'precio_asc') ? 'selected' : '' ?>>Precio: Menor a mayor</option>
                                <option value="precio_desc" <?= (($_GET['orden'] ?? '') === 'precio_desc') ? 'selected' : '' ?>>Precio: Mayor a menor</option>
                                <option value="nombre_asc" <?= (($_GET['orden'] ?? '') === 'nombre_asc') ? 'selected' : '' ?>>Nombre: A-Z</option>
                                <option value="nombre_desc" <?= (($_GET['orden'] ?? '') === 'nombre_desc') ? 'selected' : '' ?>>Nombre: Z-A</option>
                                <option value="fecha_desc" <?= (($_GET['orden'] ?? '') === 'fecha_desc') ? 'selected' : '' ?>>Más recientes</option>
                                <option value="mas_vendidos" <?= (($_GET['orden'] ?? '') === 'mas_vendidos') ? 'selected' : '' ?>>Más vendidos</option>
                                <option value="ofertas" <?= (($_GET['orden'] ?? '') === 'ofertas') ? 'selected' : '' ?>>Mejores ofertas</option>
                            </select>
                        </div>
                        
                        <div class="_productoIndex_availability _productoIndex_form-group-full">
                            <label class="_productoIndex_checkbox-label">
                                <input type="checkbox" id="disponibles" name="disponibles" value="1" <?= isset($_GET['disponibles']) && $_GET['disponibles'] == '1' ? 'checked' : '' ?> class="_productoIndex_checkbox">
                                Solo productos disponibles (Stock > 0)
                            </label>
                        </div>

                    </div>
                    
                    <fieldset class="_productoIndex_fieldset">
                        <legend class="_productoIndex_legend">Etiquetas:</legend>
                        <div class="_productoIndex_tags">
                            <?php foreach ($todasEtiquetas as $etiqueta): ?>
                            <label class="_productoIndex_tag-label">
                                <input type="checkbox" name="etiquetas[]" value="<?= htmlspecialchars($etiqueta['id']) ?>"
                                    <?= in_array($etiqueta['id'], $_GET['etiquetas'] ?? []) ? 'checked' : '' ?>
                                    class="_productoIndex_checkbox">
                                <?= htmlspecialchars($etiqueta['nombre']) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <div class="_productoIndex_filter-actions">
                        <button type="submit" id="btnFiltrar" class="_productoIndex_filter-btn _productoIndex_filter-primary">Aplicar Filtros</button>
                        <button type="button" id="btnLimpiar" class="_productoIndex_filter-btn _productoIndex_filter-secondary">❌ Limpiar</button>
                    </div>
                </div>
            </div>
        </form>


        <?php if (!empty($validacionFiltros['errores'] ?? [])): ?>
            <div id="errorFiltros" class="_productoIndex_error">
                <strong>❌ Errores en filtros:</strong>
                <ul class="_productoIndex_error-list">
                    <?php foreach ($validacionFiltros['errores'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div id="productosContainer" class="_productoIndex_table-container">
            <?php if (empty($productos)): ?>
                <div class="_productoIndex_empty-state">
                    <p>No se encontraron productos con los filtros aplicados. 😔</p>
                    <button type="button" id="btnLimpiarBottom" class="_productoIndex_filter-btn _productoIndex_filter-secondary">Limpiar Filtros</button>
                </div>
            <?php else: ?>
                <table id="tablaProductos" class="_productoIndex_table">
                    <thead class="_productoIndex_table-header">
                        <tr>
                            <th class="_productoIndex_table-head">ID</th>
                            <th class="_productoIndex_table-head">SKU</th>
                            <th class="_productoIndex_table-head">Nombre</th>
                            <th class="_productoIndex_table-head">Precio</th>
                            <th class="_productoIndex_table-head">Original</th>
                            <th class="_productoIndex_table-head">% Desc.</th>
                            <th class="_productoIndex_table-head">Stock</th>
                            <th class="_productoIndex_table-head">Visible</th>
                            <th class="_productoIndex_table-head">Categorías</th>
                            <th class="_productoIndex_table-head">Imágenes</th>
                            <th class="_productoIndex_table-head">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="_productoIndex_table-body">
                        <?php foreach ($productos as $producto): ?>
                        <?php 
                            $stock = (int)($producto['stock'] ?? 0);
                            $stockColor = $stock > 10 ? 'green' : ($stock > 0 ? 'orange' : 'red');
                            $stockBG = $stock > 10 ? 'rgba(0,128,0,0.1)' : ($stock > 0 ? 'rgba(255,165,0,0.1)' : 'rgba(255,0,0,0.1)');
                        ?>
                        <tr class="_productoIndex_table-row">
                            <td class="_productoIndex_table-cell"><?= htmlspecialchars($producto['id'] ?? '') ?></td>
                            <td class="_productoIndex_table-cell"><?= htmlspecialchars($producto['sku'] ?? '') ?></td>
                            <td class="_productoIndex_table-cell" title="<?= htmlspecialchars($producto['descripcion'] ?? '') ?>">
                                <?php
                                    $nombre = $producto['nombre'] ?? '';
                                    $shortName = strlen($nombre) > 40 ? substr($nombre, 0, 37) . '...' : $nombre;
                                ?>
                                <strong><?= htmlspecialchars($shortName) ?></strong>
                            </td>
                            <td class="_productoIndex_table-cell">S/ <?= number_format($producto['precio'] ?? 0, 2) ?></td>
                            <td class="_productoIndex_table-cell">
                                <?php if (!empty($producto['precio_tachado']) && $producto['precio_tachado'] > ($producto['precio'] ?? 0)): ?>
                                S/ <?= number_format($producto['precio_tachado'], 2) ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="_productoIndex_table-cell">
                                <?php if (!empty($producto['porcentaje_descuento']) && $producto['porcentaje_descuento'] > 0): ?>
                                <?= number_format($producto['porcentaje_descuento'], 0) ?>%
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="_productoIndex_table-cell" style="font-weight: bold; color: <?= $stockColor ?>; background-color: <?= $stockBG ?>;">
                                <?= htmlspecialchars($stock) ?>
                            </td>
                            <td class="_productoIndex_table-cell _productoIndex_visible-cell <?= !empty($producto['visible']) ? '_productoIndex_visible-yes' : '_productoIndex_visible-no' ?>">
                                <?= !empty($producto['visible']) ? 'Sí' : 'No' ?>
                            </td>
                            <td class="_productoIndex_table-cell">
                                <?php if (!empty($producto['categorias'])): ?>
                                <?= implode(', ', array_map('htmlspecialchars', $producto['categorias'])) ?>
                                <?php else: ?>
                                <span class="_productoIndex_no-category">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="_productoIndex_table-cell">
                                <?php if (!empty($producto['imagenes']) && is_array($producto['imagenes'])): ?>
                                    <div class="_productoIndex_images">
                                        <?php $imagen = $producto['imagenes'][0]; ?>
                                        <img src="<?= htmlspecialchars(url('uploads/' . ($imagen['nombre_imagen'] ?? ''))) ?>" 
                                            alt="Img <?= htmlspecialchars($producto['nombre'] ?? '') ?>" 
                                            class="_productoIndex_image"
                                            onerror="this.onerror=null;this.src='<?= url('images/default-product.png') ?>';"
                                        >
                                    </div>
                                <?php else: ?>
                                    <span class="_productoIndex_no-images">🖼️</span>
                                <?php endif; ?>
                            </td>
                            <td class="_productoIndex_table-cell _productoIndex_actions-cell">
                                <a href="<?= url('producto/editar/' . ($producto['id'] ?? '')) ?>" class="_productoIndex_action-link _productoIndex_edit-link" title="Editar"> 
                                    ✏️
                                </a>
                                <a href="<?= url('producto/eliminar/' . ($producto['id'] ?? '')) ?>" class="_productoIndex_action-link _productoIndex_delete-link"
                                    onclick="return confirm('¿Estás seguro de eliminar este producto? Esta acción es irreversible.')" title="Eliminar">
                                    🗑️
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

      </div>
    </main>
  </div>
</div>

<script>
  // Alert close & auto-hide
  (function(){
    const alerta = document.getElementById('mensaje-alerta');
    const btnCerrar = document.getElementById('cerrarAlerta');
    if (btnCerrar) btnCerrar.addEventListener('click', () => { if (alerta) alerta.style.display = 'none'; });
    if (alerta) setTimeout(()=> { 
        if (alerta) alerta.style.display = 'none'; 
    }, 5000);
  })();

  // File input custom label
  (function(){
    const input = document.getElementById('archivo_csv');
    const nombre = document.getElementById('archivoNombre');
    if (input) {
      input.addEventListener('change', () => {
        nombre.textContent = (input.files && input.files.length > 0) ? input.files[0].name : 'Ningún archivo seleccionado';
      });
    }
  })();

  // Filtrar / Limpiar con scroll a productos
  (function(){
    const form = document.getElementById('filtroForm');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const btnLimpiarBottom = document.getElementById('btnLimpiarBottom');
    const baseUrl = document.body.dataset.baseUrl || '<?= htmlspecialchars(url('producto')) ?>';
    
    // Función para limpiar filtros
    const limpiarFiltros = () => {
        // Redirige a la URL base + el fragmento para scroll
        window.location.href = baseUrl + '#productosContainer';
    };

    // Evento del botón Limpiar dentro del formulario
    if (btnLimpiar) {
      btnLimpiar.addEventListener('click', (e) => {
        e.preventDefault();
        limpiarFiltros();
      });
    }
    
    // Evento del botón Limpiar en estado vacío (si existe)
    if (btnLimpiarBottom) {
      btnLimpiarBottom.addEventListener('click', (e) => {
        e.preventDefault();
        limpiarFiltros();
      });
    }

    // Comportamiento de submit (ahora incluye la búsqueda)
    if (form) {
        form.addEventListener('submit', (e) => {
            // No prevenimos el submit, simplemente aseguramos el scroll después
            setTimeout(() => {
                 window.location.href = window.location.href.split('#')[0] + '#productosContainer';
            }, 100);
        });
    }
  })();
  
  // Lógica para hacer el panel de filtros colapsable (UX)
  (function(){
    const toggleButton = document.getElementById('toggleFiltros');
    const filterContent = document.getElementById('filterContent');
    const filterToggleIcon = document.getElementById('filterToggleIcon');
    const filterCard = document.getElementById('filterCard');

    if (toggleButton && filterContent) {
        toggleButton.addEventListener('click', () => {
            const isCollapsed = filterContent.classList.toggle('collapsed');
            
            // Toggle de la clase principal para ajustar bordes y padding si es necesario
            filterCard.classList.toggle('_productoIndex_card-open', !isCollapsed);
            filterCard.classList.toggle('_productoIndex_card-closed', isCollapsed);

            // Gira el icono
            filterToggleIcon.style.transform = isCollapsed ? 'rotate(0deg)' : 'rotate(180deg)';
        });
    }
  })();

</script>

</body>
</html>