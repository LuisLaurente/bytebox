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
    <link rel="stylesheet" href="<?= url('/css/productoIndex.css') ?>">

<body class="_productoIndex_body" data-base-url="<?= htmlspecialchars(url('producto')) ?>">

<div class="_productoIndex_layout">
  <!-- Sidebar -->
  <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

  <!-- Main -->
  <div class="_productoIndex_main">
    <!-- Content -->
    <main class="_productoIndex_content">
      <div class="_productoIndex_container">

        <!-- Page header -->
        <div class="_productoIndex_page-header">
          <h1 class="_productoIndex_title"> Gestión de Productos</h1>
          <p class="_productoIndex_subtitle">Administra el catálogo completo de productos</p>
        </div>

        <!-- Session alert -->
        <?php if (isset($_SESSION['mensaje_carrito'])): ?>
          <div id="mensaje-alerta"
               class="_productoIndex_alert">
            <span class="_productoIndex_alert-text"><?= htmlspecialchars($_SESSION['mensaje_carrito']) ?></span>
            <button id="cerrarAlerta" class="_productoIndex_alert-close">✖</button>
          </div>
          <?php unset($_SESSION['mensaje_carrito']); ?>
        <?php endif; ?>

        <!-- Acciones --> 
         <div class="_productoIndex_actions"> 
            <a href="<?= url('cargaMasiva/descargarPlantilla') ?>" class="_productoIndex_action-btn _productoIndex_action-blue">  Descargar Plantilla CSV </a> 
            <a href="<?= url('cargaMasiva/gestionImagenes') ?>" class="_productoIndex_action-btn _productoIndex_action-purple">  Gestión Imágenes </a>
            <a href="<?= url('producto/crear') ?>" class="_productoIndex_action-btn _productoIndex_action-green"> Nuevo Producto</a>
        </div>

        <!-- CSV upload card -->
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

        <!-- Filters card -->
        <div class="_productoIndex_card">
          <h3 class="_productoIndex_card-title">🔍 Filtros de búsqueda</h3>

          <?php if (!empty($estadisticasPrecios)): ?>
            <div class="_productoIndex_stats">
              <div>Rango: <span class="_productoIndex_stat-value">S/ <?= htmlspecialchars($estadisticasPrecios['precio_minimo']) ?> - S/ <?= htmlspecialchars($estadisticasPrecios['precio_maximo']) ?></span></div>
              <div>Promedio: <span class="_productoIndex_stat-value">S/ <?= htmlspecialchars($estadisticasPrecios['precio_promedio']) ?></span></div>
              <div>Total: <span class="_productoIndex_stat-value"><?= htmlspecialchars($estadisticasPrecios['total_productos']) ?></span></div>
            </div>
          <?php endif; ?>

          <form id="filtroForm" method="GET" action="<?= url('producto') ?>" class="_productoIndex_filter-form">
            <!-- Min price -->
            <div class="_productoIndex_form-group">
              <label for="min_price" class="_productoIndex_label">Precio mínimo (S/):</label>
              <input id="min_price" name="min_price" type="number" step="1" min="0" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>"
                     class="_productoIndex_input">
            </div>

            <!-- Max price -->
            <div class="_productoIndex_form-group">
              <label for="max_price" class="_productoIndex_label">Precio máximo (S/):</label>
              <input id="max_price" name="max_price" type="number" step="1" min="0" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>"
                     class="_productoIndex_input">
            </div>

            <!-- Category -->
            <div class="_productoIndex_form-group _productoIndex_form-group-full">
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

            <!-- Tags full-width -->
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

            <!-- Disponibilidad -->
            <div class="_productoIndex_availability">
              <label class="_productoIndex_checkbox-label">
                <input type="checkbox" id="disponibles" name="disponibles" value="1" <?= isset($_GET['disponibles']) ? 'checked' : '' ?> class="_productoIndex_checkbox">
                Solo productos disponibles (stock > 0)
              </label>
            </div>

            <!-- Orden -->
            <div class="_productoIndex_form-group">
              <label for="orden" class="_productoIndex_label">Ordenar por:</label>
              <select id="orden" name="orden" class="_productoIndex_select">
                <option value="">-- Seleccionar --</option>
                <option value="precio_asc" <?= (($_GET['orden'] ?? '') === 'precio_asc') ? 'selected' : '' ?>>Precio: Menor a mayor</option>
                <option value="precio_desc" <?= (($_GET['orden'] ?? '') === 'precio_desc') ? 'selected' : '' ?>>Precio: Mayor a menor</option>
                <option value="nombre_asc" <?= (($_GET['orden'] ?? '') === 'nombre_asc') ? 'selected' : '' ?>>Nombre: A-Z</option>
                <option value="nombre_desc" <?= (($_GET['orden'] ?? '') === 'nombre_desc') ? 'selected' : '' ?>>Nombre: Z-A</option>
                <option value="fecha_desc" <?= (($_GET['orden'] ?? '') === 'fecha_desc') ? 'selected' : '' ?>>Más recientes</option>
              </select>
            </div>

            <!-- Buttons -->
            <div class="_productoIndex_filter-actions">
              <button type="button" id="btnFiltrar" class="_productoIndex_filter-btn _productoIndex_filter-primary">🔍 Filtrar</button>
              <button type="button" id="btnLimpiar" class="_productoIndex_filter-btn _productoIndex_filter-secondary">❌ Limpiar</button>
              <span id="loading" class="_productoIndex_loading">⏳ Cargando...</span>
            </div>
          </form>
        </div>

        <!-- Error & active filters placeholders -->
        <div id="errorFiltros" class="_productoIndex_error">
          <strong>❌ Errores en filtros:</strong>
          <ul id="listaErrores" class="_productoIndex_error-list"></ul>
        </div>

        <div id="filtrosActivos" class="_productoIndex_active-filters">
          <strong>🔍 Filtros activos:</strong>
          <div id="infoFiltros" class="_productoIndex_filters-info"></div>
        </div>

        <!-- 🔍 Barra de búsqueda -->
        <div class="_productoIndex_search-container">
          <div class="_productoIndex_search-wrapper">
            <input 
              type="text" 
              id="busquedaProducto" 
              placeholder="Escriba para buscar..." 
              class="_productoIndex_search-input"
            >
            <!-- Icono lupa -->
            <span class="_productoIndex_search-icon">🔍</span>
          </div>
        </div>

        <!--  Tabla de productos -->
        <div id="productosContainer" class="_productoIndex_table-container">
          <table id="tablaProductos" class="_productoIndex_table">
            <thead class="_productoIndex_table-header">
              <tr>
                <th class="_productoIndex_table-head">ID</th>
                <th class="_productoIndex_table-head">SKU</th>
                <th class="_productoIndex_table-head">Nombre</th>
                <th class="_productoIndex_table-head">Descripción</th>
                <th class="_productoIndex_table-head">Precio</th>
                <th class="_productoIndex_table-head">Original</th>
                <th class="_productoIndex_table-head">% Desc.</th>
                <th class="_productoIndex_table-head">Visible</th>
                <th class="_productoIndex_table-head">Categorías</th>
                <th class="_productoIndex_table-head">Imágenes</th>
                <th class="_productoIndex_table-head">Acciones</th>
              </tr>
            </thead>
            <tbody class="_productoIndex_table-body">
              <?php foreach ($productos as $producto): ?>
              <tr class="_productoIndex_table-row">
                <td class="_productoIndex_table-cell"><?= htmlspecialchars($producto['id'] ?? '') ?></td>
                <td class="_productoIndex_table-cell"><?= htmlspecialchars($producto['sku'] ?? '') ?></td>
                <td class="_productoIndex_table-cell"><?= htmlspecialchars($producto['nombre'] ?? '') ?></td>
                <td class="_productoIndex_table-cell">
                  <?php
                    $desc = $producto['descripcion'] ?? '';
                    $shortDesc = strlen($desc) > 60 ? substr($desc, 0, 57) . '...' : $desc;
                  ?>
                  <span title="<?= htmlspecialchars($desc) ?>"><?= htmlspecialchars($shortDesc) ?></span>
                </td>
                <td class="_productoIndex_table-cell">S/ <?= number_format($producto['precio'] ?? 0, 2) ?></td>
                <td class="_productoIndex_table-cell">
                  <?php if (!empty($producto['precio_tachado']) && $producto['precio_tachado'] > ($producto['precio'] ?? 0)): ?>
                    S/ <?= number_format($producto['precio_tachado'], 2) ?>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td class="_productoIndex_table-cell">
                  <?php if (!empty($producto['porcentaje_descuento']) && $producto['porcentaje_descuento'] > 0): ?>
                    <?= number_format($producto['porcentaje_descuento'], 2) ?>%
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td class="_productoIndex_table-cell _productoIndex_visible-cell <?= !empty($producto['visible']) ? '_productoIndex_visible-yes' : '_productoIndex_visible-no' ?>">
                  <?= !empty($producto['visible']) ? 'Sí' : 'No' ?>
                </td>
                <td class="_productoIndex_table-cell">
                  <?php if (!empty($producto['categorias'])): ?>
                    <?= implode(', ', array_map('htmlspecialchars', $producto['categorias'])) ?>
                  <?php else: ?>
                    <span class="_productoIndex_no-category">Sin categoría</span>
                  <?php endif; ?>
                </td>
                <td class="_productoIndex_table-cell">
                  <?php if (!empty($producto['imagenes']) && is_array($producto['imagenes'])): ?>
                    <div class="_productoIndex_images">
                      <?php foreach ($producto['imagenes'] as $imagen): ?>
                        <img src="<?= htmlspecialchars(url('uploads/' . ($imagen['nombre_imagen'] ?? ''))) ?>" alt="Img <?= htmlspecialchars($producto['nombre'] ?? '') ?>" class="_productoIndex_image">
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <span class="_productoIndex_no-images">Sin imágenes</span>
                  <?php endif; ?>
                </td>
                <td class="_productoIndex_table-cell _productoIndex_actions-cell">
                  <a href="<?= url('producto/editar/' . ($producto['id'] ?? '')) ?>" class="_productoIndex_action-link _productoIndex_edit-link"> Editar</a>
                  <a href="<?= url('producto/eliminar/' . ($producto['id'] ?? '')) ?>" class="_productoIndex_action-link _productoIndex_delete-link"
                     onclick="return confirm('¿Estás seguro de eliminar este producto?')">Eliminar</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>
    </main>
  </div>
</div>

<!-- Scripts -->
<script>
  // Alert close & auto-hide
  (function(){
    const alerta = document.getElementById('mensaje-alerta');
    const btnCerrar = document.getElementById('cerrarAlerta');
    if (btnCerrar) btnCerrar.addEventListener('click', () => { if (alerta) alerta.style.display = 'none'; });
    if (alerta) setTimeout(()=> { alerta.style.display = 'none'; }, 3000);
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
    const btnFiltrar = document.getElementById('btnFiltrar');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const form = document.getElementById('filtroForm');
    const loading = document.getElementById('loading');
    const baseUrl = document.body.dataset.baseUrl || '<?= htmlspecialchars(url('producto')) ?>';

    if (btnFiltrar && form) {
      btnFiltrar.addEventListener('click', (e) => {
        e.preventDefault();
        if (loading) loading.classList.remove('_productoIndex_hidden');
        const url = form.getAttribute('action') + '?' + new URLSearchParams(new FormData(form)).toString();
        window.location.href = url + '#productosContainer';
      });
    }

    if (btnLimpiar) {
      btnLimpiar.addEventListener('click', () => {
        window.location.href = baseUrl + '#productosContainer';
      });
    }
  })();

  // Búsqueda en tiempo real
  document.getElementById('busquedaProducto').addEventListener('keyup', function() {
    let valor = this.value.toLowerCase();
    let filas = document.querySelectorAll("._productoIndex_table-row");

    filas.forEach(fila => {
      let celdas = fila.querySelectorAll("._productoIndex_table-cell");
      let coincide = false;

      celdas.forEach(celda => {
        if (celda.textContent.toLowerCase().includes(valor)) {
          coincide = true;
        }
      });

      fila.style.display = coincide ? "" : "none";
    });
  });
</script>

</body>
</html>