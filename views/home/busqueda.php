<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$totalProductos = isset($productos) ? count($productos) : 0;
$productosPorPagina = 15;
$totalEncontrados = $totalProductos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
    <meta name="description" content="<?= !empty($termino) ? 'Resultados de búsqueda para: ' . htmlspecialchars($termino) : 'Explora nuestro catálogo completo de productos electrónicos' ?>">
    <meta name="keywords" content="electrónicos, tecnología, productos, tienda, búsqueda, catálogo">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css" />
    <link rel="stylesheet" href="<?= url('css/busqueda.css' ) ?>">
    <link rel="stylesheet" href="<?= url('css/cards.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preload" href="<?= url('css/busqueda.css' ) ?>" as="style">
</head>
<body>
    <?php include_once __DIR__ . '/../admin/includes/header.php'; ?>

    <div class="main-container">
        <div class="content-wrapper">
            <nav class="breadcrumb">
                <a href="<?= url('home') ?>">INICIO</a> /
                <?php if (!empty($categoriaActual)): ?>
                    <?= htmlspecialchars(strtoupper($categoriaActual['nombre'])) ?>
                <?php else: ?>
                    BÚSQUEDA
                <?php endif; ?>
            </nav>

            <!-- Layout principal con Sidebar y Contenido -->
            <div class="search-layout">
                <!-- ================================================================== -->
                <!-- COLUMNA DE FILTROS (SIDEBAR)                                       -->
                <!-- ================================================================== -->
                <aside class="filters-sidebar">
                    <form id="filterForm" method="GET" action="<?= url('home/busqueda') ?>">
                        <h2 class="filters-title">Filtros</h2>
                        
                        <!-- Mantener término de búsqueda si existe -->
                        <?php if (!empty($termino)): ?>
                            <input type="hidden" name="termino" value="<?= htmlspecialchars($termino) ?>">
                        <?php endif; ?>

                        <!-- Filtro por Categoría -->
                        <div class="filter-group">
                            <label class="filter-label" for="categoria-select">CATEGORÍAS</label>
                            <select name="categoria" id="categoria-select" class="filter-select">
                                <option value="">Todas</option>
                                <?php if (!empty($categoriasDisponibles)): ?>
                                    <?php foreach ($categoriasDisponibles as $categoria): ?>
                                        <option value="<?= (int)$categoria['id'] ?>" <?= (($_GET['categoria'] ?? '') == $categoria['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categoria['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Filtro por Precio -->
                        <div class="filter-group">
                            <label class="filter-label">PRECIO</label>
                            <div id="price-slider" class="price-slider-container"></div>
                            <br>
                            <div class="price-inputs">
                                <input type="number" class="price-input" name="min_price" value="<?= htmlspecialchars($_GET['min_price'] ?? '0') ?>" placeholder="Mín">
                                <span>-</span>
                                <input type="number" class="price-input" name="max_price" value="<?= htmlspecialchars($_GET['max_price'] ?? '5000') ?>" placeholder="Máx">
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="filter-actions">
                            <button type="submit" class="apply-filters-btn">Aplicar Filtros</button>
                            <a href="<?= url('home/busqueda') ?>" class="clear-filters-btn">Limpiar</a>
                        </div>
                    </form>
                </aside>

                <!-- ================================================================== -->
                <!-- CONTENIDO PRINCIPAL (PRODUCTOS)                                    -->
                <!-- ================================================================== -->
                <div class="main-content">
                    <div class="page-header">
                        <?php if (!empty($termino)): ?>
                            <h1 class="page-title">Resultados para: "<?= htmlspecialchars($termino, ENT_QUOTES, 'UTF-8') ?>"</h1>

                        <?php elseif (!empty($categoriaActual) && !empty($categoriaActual['nombre'])): ?>
                            <h1 class="page-title">Solo <?= htmlspecialchars($categoriaActual['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>

                        <?php else: ?>
                            <h1 class="page-title">Todos los Productos</h1>
                        <?php endif; ?>
                    </div>


                    <!-- Barra de herramientas superior (Resultados y Ordenamiento) -->
                    <div class="top-toolbar">
                        <p class="results-count"><?= $totalEncontrados ?> productos encontrados</p>
                        <button class="mobile-filter-toggle"><i class="fas fa-filter"></i> Mostrar Filtros</button>
                        <div class="sort-section">
                            <label class="sort-label" for="sortSelect">Ordenar por:</label>
                            <select class="sort-select" id="sortSelect" name="orden" form="filterForm">
                                <option value="relevancia" <?= (($_GET['orden'] ?? '') === 'relevancia') ? 'selected' : '' ?>>Relevancia</option>
                                <option value="ofertas" <?= (($_GET['orden'] ?? '') === 'ofertas') ? 'selected' : '' ?>>Ofertas</option>
                                <option value="novedades" <?= (($_GET['orden'] ?? '') === 'novedades') ? 'selected' : '' ?>>Novedades</option>
                                <option value="mas_vendidos" <?= (($_GET['orden'] ?? '') === 'mas_vendidos') ? 'selected' : '' ?>>Más vendidos</option>
                                <option value="destacados" <?= (($_GET['orden'] ?? '') === 'destacados') ? 'selected' : '' ?>>Destacados</option>
                                <option value="nombre_asc" <?= (($_GET['orden'] ?? '') === 'nombre_asc') ? 'selected' : '' ?>>Nombre: A - Z</option>
                                <option value="nombre_desc" <?= (($_GET['orden'] ?? '') === 'nombre_desc') ? 'selected' : '' ?>>Nombre: Z - A</option>
                                <option value="precio_asc" <?= (($_GET['orden'] ?? '') === 'precio_asc') ? 'selected' : '' ?>>Precio: Menor a Mayor</option>
                                <option value="precio_desc" <?= (($_GET['orden'] ?? '') === 'precio_desc') ? 'selected' : '' ?>>Precio: Mayor a Menor</option>
                            </select>
                        </div>
                    </div>

                    <?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
                        <?php include __DIR__ . '/_pagination.php'; ?>
                    <?php endif; ?>

                    <div id="products-container">
                        <?php include __DIR__ . '/_products_grid.php'; ?>
                    </div>

                    <?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
                        <?php include __DIR__ . '/_pagination.php'; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../admin/includes/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function( ) {
        const priceSlider = document.getElementById('price-slider');
        if (priceSlider) {
            const minPriceInput = document.querySelector('input[name="min_price"]');
            const maxPriceInput = document.querySelector('input[name="max_price"]');
            const maxRange = 5000;
            const initialMin = parseInt(minPriceInput.value, 10) || 0;
            const initialMax = parseInt(maxPriceInput.value, 10) || maxRange;

            noUiSlider.create(priceSlider, {
                start: [initialMin, initialMax],
                connect: true,
                step: 10,
                range: { 'min': 0, 'max': maxRange },
                format: {
                    to: value => Math.round(value),
                    from: value => Number(value)
                }
            });

            priceSlider.noUiSlider.on('update', (values, handle) => {
                const value = values[handle];
                if (handle === 0) {
                    minPriceInput.value = value;
                } else {
                    maxPriceInput.value = value;
                }
            });

            minPriceInput.addEventListener('change', function() { priceSlider.noUiSlider.set([this.value, null]); });
            maxPriceInput.addEventListener('change', function() { priceSlider.noUiSlider.set([null, this.value]); });
        }

        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            document.getElementById('sortSelect').addEventListener('change', () => {
                filterForm.submit();
            });
        }

        const mobileFilterToggle = document.querySelector('.mobile-filter-toggle');
        const sidebar = document.querySelector('.filters-sidebar');
        if (mobileFilterToggle && sidebar) {
            mobileFilterToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
            // Cerrar si se hace clic fuera del panel
            document.addEventListener('click', (e) => {
                if (sidebar.classList.contains('active')) {
                    const clickedInside = sidebar.contains(e.target) || mobileFilterToggle.contains(e.target);
                    if (!clickedInside) {
                        sidebar.classList.remove('active');
                    }
                }
            });
        }
    });
    </script>
</body>
</html>
