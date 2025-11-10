<?php
// views/producto/detalle.php
// Requiere: $producto (array) -- pasarlo desde el controlador
// Opcionales: $breadcrumb (array), $relatedProducts (array), $reviews (array)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cantidad total en el carrito (para mostrar si lo necesitas en header)
$cantidadEnCarrito = 0;
if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidadEnCarrito += (int)($item['cantidad'] ?? 0);
    }
}

// Fallbacks mínimos para $producto si no están definidos (para evitar errores en desarrollo)
$producto = $producto ?? [
    'id' => 0,
    'nombre' => 'Smartwatch Samsung Galaxy Watch6',
    'descripcion' => 'Funda personalizada: Personaliza la pantalla. Actualización instantánea: Cambia el color. Protección Segura: Además de innovador. Diseño Delgado y Ligero: Su diseño es excepcional y muy cómodo para el día a día, te olvidarás que lo llevas puesto.',
    'descripcion_larga' => 'El Samsung Galaxy Watch6 es un smartwatch elegante y potente diseñado para quienes buscan estilo y funcionalidad en su muñeca. Incorpora una pantalla Super AMOLED de 1.5" con excelente resolución y Always-On Display. Con Wear OS Powered by Samsung, ofrece acceso a apps, notificaciones y servicios inteligentes. Cuenta con sensores avanzados para salud, fitness, y bienestar, incluyendo monitoreo de ritmo cardíaco, SpO2 y sueño. Su batería de larga duración, resistencia al agua 5 ATM y diseño premium lo convierten en un compañero ideal para el día a día.',
    'precio' => 1599.00,
    'precio_tachado' => 1899.00,
    'porcentaje_descuento' => 16,
    'precio_tachado_visible' => 1,
    'porcentaje_visible' => 1,
    'imagenes' => [['nombre_imagen' => 'default-product.png']],
    'categorias' => [],
    'stock' => 25,
    'especificaciones_array' => [
        'Pantalla: Super AMOLED de 1.5" (37.3 mm)',
        'Resolución: 480 x 480 píxeles',
        'Color Depth: 16 millones de colores',
        'Procesador: Dual-Core a 1.4 GHz',
        'Sistema Operativo: Wear OS Powered by Samsung',
        'Memoria RAM: 2 GB',
        'Almacenamiento: 16 GB',
        'Batería: 425 mAh',
        'Conectividad: Bluetooth 5.3, Wi-Fi, NFC, GPS',
        'Sensores: Acelerómetro, Barómetro, Giroscopio, Sensor de Luz, Sensor de Ritmo Cardíaco'
    ]
];

// Preparar precios y flags
$precioFinal = isset($producto['precio']) ? (float)$producto['precio'] : 0.0;
$precioTachado = isset($producto['precio_tachado']) && $producto['precio_tachado'] !== ''
    ? (float)$producto['precio_tachado'] : null;

$precioTachadoVisible = !empty($producto['precio_tachado_visible']);
$porcentajeVisible    = !empty($producto['porcentaje_visible']);

$descuentoPct = isset($producto['porcentaje_descuento']) && $producto['porcentaje_descuento'] !== ''
    ? (float)$producto['porcentaje_descuento'] : 0.0;

if (($descuentoPct <= 0 || $descuentoPct > 100) && $precioTachado !== null && $precioTachado > 0 && $precioFinal < $precioTachado) {
    $descuentoPct = round((($precioTachado - $precioFinal) / $precioTachado) * 100, 2);
}

$showTachado = ($precioTachado !== null) && ($precioTachado > $precioFinal) && $precioTachadoVisible;
$showPct     = $porcentajeVisible && $showTachado && ($descuentoPct > 0);

// Breadcrumb fallback
$breadcrumb = $breadcrumb ?? ($producto['breadcrumb'] ?? ['Inicio', 'Tecnología', 'Smartwatch Samsung Galaxy Watch6']);

// Related products & reviews fallback
$relatedProducts = $relatedProducts ?? [];
$reviews = $reviews ?? [];

// La función producto_imagen_url() ahora está disponible globalmente desde core/helpers/producto_helpers.php
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($producto['nombre']) ?> - Bytebox</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= url('css/producto-descripcion.css') ?>">
    <link rel="stylesheet" href="<?= url('css/cards.css') ?>">
</head>

<body>
    <?php include_once __DIR__ . '/../admin/includes/header.php'; ?>
    <main class="product-detail-container">
        <div class="bread-links">
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <?php if (is_array($breadcrumb) && !empty($breadcrumb)): ?>
                        <?php foreach ($breadcrumb as $i => $crumb): ?>
                            <?php
                            $isLast = ($i === count($breadcrumb) - 1);
                            $categoriaId = $crumb['id'] ?? '';
                            $categoriaNombre = $crumb['nombre'] ?? '';
                            ?>
                            <li class="breadcrumb-item <?= $isLast ? 'crumb-current' : '' ?>" <?= $isLast ? 'aria-current="page"' : '' ?>>
                                <?php if ($isLast): ?>
                                    <?= htmlspecialchars($categoriaNombre) ?>
                                <?php else: ?>
                                    <?php if (!empty($categoriaId)): ?>
                                        <!-- Link a búsqueda por categoría -->
                                        <a href="<?= url('home/busqueda?categoria=' . $categoriaId) ?>">
                                            <?= htmlspecialchars($categoriaNombre) ?>
                                        </a>
                                    <?php else: ?>
                                        <!-- Link genérico (Inicio, Productos) -->
                                        <a href="<?= url($categoriaNombre === 'Inicio' ? '/' : 'home/busqueda') ?>">
                                            <?= htmlspecialchars($categoriaNombre) ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="breadcrumb-item"><a href="<?= url('/') ?>">Inicio</a></li>
                        <li class="breadcrumb-item crumb-current" aria-current="page">Productos</li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>

        <section class="product-info-grid">
            <div class="product-image-gallery">
                <!-- Contenedor principal del zoom -->
                <div class="image-zoom-container">
                    <div class="image-wrapper">
                        <img id="main-product-image"
                            src="<?= producto_imagen_url($producto, 0) ?>"
                            alt="<?= htmlspecialchars($producto['nombre']) ?>"
                            class="zoomable-image">
                    </div>
                    <!-- Lente de zoom (solo desktop) -->
                    <div class="zoom-lens"></div>
                </div>

                <?php if (!empty($producto['imagenes']) && count($producto['imagenes']) > 1): ?>
                    <div class="thumbnail-images" role="list">
                        <?php foreach ($producto['imagenes'] as $idx => $img): ?>
                            <?php $imgUrl = producto_imagen_url($producto, $idx); ?>
                            <img class="thumb <?= $idx === 0 ? 'activo' : '' ?>"
                                src="<?= $imgUrl ?>"
                                data-src="<?= $imgUrl ?>"
                                alt="<?= htmlspecialchars($producto['nombre']) ?> miniatura <?= $idx + 1 ?>"
                                role="listitem">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-short-description">
                <h2>Especificaciones Clave:</h2>
                <!-- Mostramos las especificaciones como una lista -->
                <ul class="specs-list-short">
                    <?php if (!empty($producto['especificaciones_array']) && is_array($producto['especificaciones_array'])): ?>
                        <?php
                        // Tomamos solo las primeras 5 especificaciones para no saturar el espacio
                        $especificacionesMostradas = array_slice($producto['especificaciones_array'], 0, 5);
                        ?>
                        <?php foreach ($especificacionesMostradas as $spec): ?>
                            <li><?= htmlspecialchars($spec) ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No hay especificaciones disponibles.</li>
                    <?php endif; ?>
                </ul>
                <a href="#descripcion-section" class="read-more-link">Ver descripción completa</a>


                <div class="info-boxes">
                    <div class="info-box">
                        <i class="fa-solid fa-truck"></i>
                        <span>Envíos rápidos a todo el Perú</span>
                    </div>
                    <div class="info-box">
                        <i class="fa-solid fa-certificate"></i>
                        <span>Garantía Bytebox en todos tus pedidos</span>
                    </div>
                    <div class="info-box">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Pagos seguros y protegidos siempre</span>
                    </div>
                </div>


            </div>

            <div class="product-details">
                <h1><?= htmlspecialchars($producto['nombre']) ?></h1>

                <div class="rating">
                    <a href="#reviews-section" class="rating-link">
                        <?php
                        $averageRating = $producto['rating_average'] ?? 0;
                        $ratingCount = $producto['rating_count'] ?? 0;
                        ?>
                        <span class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $averageRating): ?>
                                    ★<!-- Estrella llena -->
                                <?php else: ?>
                                    ☆<!-- Estrella vacía -->
                                <?php endif; ?>
                            <?php endfor; ?>
                        </span>
                        <span class="rating-count">(<?= $ratingCount ?>)</span>
                        <?php if ($averageRating > 0): ?>
                            <span class="rating-average"><?= number_format($averageRating, 1) ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="price">
                    <span class="current-price">S/ <?= number_format($precioFinal, 2) ?></span>
                    <?php if ($showTachado): ?>
                        <span class="old-price">S/ <?= number_format($precioTachado, 2) ?></span>
                    <?php endif; ?>
                    <?php if ($showPct): ?>
                        <span class="discount">-<?= number_format($descuentoPct, 0) ?>%</span>
                    <?php endif; ?>
                </div>

                <!-- Nueva sección para seleccionar variantes -->
                <?php if (!empty($producto['variantes']) && is_array($producto['variantes'])): ?>
                    <div class="product-variants">
                        <?php
                        // Agrupar variantes por talla y color
                        $tallas = [];
                        $colores = [];
                        foreach ($producto['variantes'] as $variante) {
                            if (!empty($variante['talla']) && !in_array($variante['talla'], $tallas)) {
                                $tallas[] = $variante['talla'];
                            }
                            if (!empty($variante['color']) && !in_array($variante['color'], $colores)) {
                                $colores[] = $variante['color'];
                            }
                        }
                        ?>

                        <?php if (!empty($tallas)): ?>
                            <div class="variant-group">
                                <label class="variant-label">Seleccione:</label>
                                <div class="variant-options">
                                    <?php foreach ($tallas as $talla): ?>
                                        <button type="button" class="variant-option" data-variant-type="talla" data-variant-value="<?= htmlspecialchars($talla) ?>">
                                            <?= htmlspecialchars($talla) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($colores)): ?>
                            <div class="variant-group">
                                <label class="variant-label">Color:</label>
                                <div class="variant-options">
                                    <?php foreach ($colores as $color): ?>
                                        <button type="button" class="variant-option color-option" data-variant-type="color" data-variant-value="<?= htmlspecialchars($color) ?>">
                                            <span class="color-name"><?= htmlspecialchars($color) ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Información de stock de la variante seleccionada -->
                        <div class="variant-stock-info" style="display: none;">
                            <svg class="stock-icon" width="22" height="22" viewBox="0 0 24 24" fill="#2ac1db">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-8 8z" />
                            </svg>
                            <span class="variant-stock-text">Stock disponible: <strong id="variant-stock-count" class="stock-number">0</strong> unidades</span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="quantity-selector" aria-label="Seleccionar cantidad">
                    <button type="button" id="qty-decrease" aria-label="Disminuir cantidad">−</button>
                    <input type="number" id="qty-input" name="cantidad" value="1" min="1" step="1" class="qty-input" />
                    <button type="button" id="qty-increase" aria-label="Aumentar cantidad">+</button>

                    <?php if (empty($producto['variantes'])): ?>
                        <!-- Solo mostrar stock general si no hay variantes -->
                        <?php if (isset($producto['stock']) && $producto['stock'] !== null): ?>
                            <span class="stock-info">Stock: <?= (int)$producto['stock'] ?> unidades</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" action="<?= url('carrito/agregar') ?>" class="add-to-cart-form">
                    <input type="hidden" name="producto_id" value="<?= (int)$producto['id'] ?>">
                    <input type="hidden" name="cantidad" id="form-cantidad" value="1">
                    <input type="hidden" name="variante_id" id="form-variante-id" value="">
                    <?php
                    $tieneVariantes = !empty($producto['variantes']) && is_array($producto['variantes']) && count($producto['variantes']) > 0;
                    ?>
                    <button type="submit" class="add-to-cart-btn" <?= $tieneVariantes ? 'disabled' : '' ?>>
                        <?= $tieneVariantes ? 'Selecciona una variante' : 'Agregar al Carro' ?>
                    </button>
                </form>
            </div>
        </section>

        <section id="descripcion-section" class="collapsible-section">
            <h2 class="collapsible-header active">Descripción <span class="arrow">&#9650;</span></h2>
            <div class="collapsible-content" style="display: block;">
                <?php if (!empty($producto['descripcion_larga'])): ?>
                    <?= nl2br(htmlspecialchars($producto['descripcion_larga'])) ?>
                <?php else: ?>
                    <p><?= nl2br(htmlspecialchars($producto['descripcion'] ?? '')) ?></p>
                <?php endif; ?>
            </div>
        </section>

        <section class="collapsible-section partially-visible">
            <h2 class="collapsible-header">Especificaciones <span class="arrow">&#9660;</span></h2>
            <div class="collapsible-content">
                <?php if (!empty($producto['especificaciones_array'])): ?>
                    <ul>
                        <?php foreach ($producto['especificaciones_array'] as $spec): ?>
                            <li><?= htmlspecialchars($spec) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No hay especificaciones detalladas.</p>
                <?php endif; ?>
                <button class="view-specs-toggle view-more-specs">VER MÁS ▼</button>
                <button class="view-specs-toggle view-less-specs">VER MENOS ▲</button>
            </div>
        </section>

        <section class="related-products">
            <h2>Productos Relacionados</h2>
            <?php if (!empty($relatedProducts) && is_array($relatedProducts)): ?>
                <div class="products-carousel-container" aria-label="Carrusel de productos relacionados">
                    <?php
                    // CRÍTICO: Guardar tanto $productos como $producto antes del include
                    $__productos_backup = $productos ?? null;
                    $__producto_backup = $producto ?? null;
                    $productos = $relatedProducts;
                    include __DIR__ . '/../home/_products_grid.php';
                    // Restaurar ambas variables
                    if ($__productos_backup === null) unset($productos);
                    else $productos = $__productos_backup;
                    if ($__producto_backup === null) unset($producto);
                    else $producto = $__producto_backup;
                    ?>
                </div>
            <?php else: ?>
                <p>No hay productos relacionados para mostrar.</p>
            <?php endif; ?>
            <!-- ================================================== -->
            <!-- INICIO DE LA NUEVA SECCIÓN DE RESEÑAS CON ESTADÍSTICAS -->
            <!-- ================================================== -->
            <section id="reviews-section" class="reviews-container">
                <h2 class="reviews-main-title">Comentarios de este producto</h2>

                <?php
                // --- Bloque de cálculo de estadísticas ---
                $totalReviews = count($reviews);
                $averageRating = 0;
                $ratingCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

                if ($totalReviews > 0) {
                    $totalScore = 0;
                    foreach ($reviews as $review) {
                        $puntuacion = (int)$review['puntuacion'];
                        if (isset($ratingCounts[$puntuacion])) {
                            $ratingCounts[$puntuacion]++;
                        }
                        $totalScore += $puntuacion;
                    }
                    $averageRating = round($totalScore / $totalReviews, 1);
                }
                // --- Fin del bloque de cálculo ---
                ?>

                <?php if ($totalReviews > 0): ?>
                    <div class="reviews-summary">
                        <!-- Columna Izquierda: Puntuación General -->
                        <div class="overall-rating">
                            <div class="score">
                                <span class="score-number"><?= htmlspecialchars($averageRating) ?></span>/5
                            </div>
                            <div class="stars-display">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="<?= $i <= floor($averageRating) ? 'filled' : '' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <div class="total-reviews-count"><?= $totalReviews ?> comentario<?= $totalReviews > 1 ? 's' : '' ?></div>
                        </div>

                        <!-- Columna Derecha: Desglose de Puntuaciones -->
                        <div class="rating-breakdown">
                            <?php foreach ($ratingCounts as $star => $count): ?>
                                <?php
                                $percentage = ($totalReviews > 0) ? ($count / $totalReviews) * 100 : 0;
                                ?>
                                <div class="breakdown-row">
                                    <span class="star-label"><?= $star ?> ★</span>
                                    <div class="bar-container">
                                        <div class="bar" style="width: <?= $percentage ?>%;"></div>
                                    </div>
                                    <span class="count-label"><?= $count ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Listado de Reseñas Individuales -->
                <div class="individual-reviews">
                    <?php if ($totalReviews > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <h3 class="review-title"><?= htmlspecialchars($review['titulo']) ?></h3>
                                    <div class="review-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="<?= $i <= $review['puntuacion'] ? 'filled' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-author-date">
                                    <span class="review-author">por <?= htmlspecialchars($review['usuario_nombre']) ?></span>
                                    <span class="review-date"><?= date('d/m/Y', strtotime($review['created_at'])) ?></span>
                                </div>
                                <p class="review-text"><?= nl2br(htmlspecialchars($review['texto'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-reviews-message">Todavía no hay comentarios para este producto.</p>
                    <?php endif; ?>
                </div>
            </section>
            <!-- ================================================ -->
            <!-- FIN DE LA NUEVA SECCIÓN DE RESEÑAS -->
            <!-- ================================================ -->

    </main>

    <!-- Modal para imagen en tamaño completo -->
    <div id="image-modal" class="image-modal-overlay" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-image" tabindex="-1">
        <div class="image-modal-container">
            <button type="button" id="close-image-modal" class="image-modal-close" aria-label="Cerrar imagen" title="Cerrar imagen">
                &times;
            </button>
            <img id="modal-image" src="" alt="" class="modal-image">
        </div>
    </div>

    <?php include_once __DIR__ . '/../admin/includes/footer.php'; ?>

    <!-- Scripts -->
    <script>
        // Pasar el stock al JavaScript
        window.productStock = <?= isset($producto['stock']) && $producto['stock'] !== null ? (int)$producto['stock'] : 'null' ?>;

        // Pasar las variantes al JavaScript
        window.productVariants = <?= json_encode($producto['variantes'] ?? []) ?>;
        
        // Pasar la base URL para construcción de rutas de imágenes
        window.baseImageUrl = '<?= url('uploads/') ?>';
    </script>
    <script src="<?= url('js/producto-zoom.js') ?>"></script>
    <script src="<?= url('js/producto-descripcion.js') ?>"></script>
</body>
<script>
document.addEventListener("DOMContentLoaded", () => {
  // Interceptar todos los formularios de agregar al carrito
  document.querySelectorAll(".add-to-cart-form").forEach(form => {
    form.addEventListener("submit", e => {
      e.preventDefault();

      const btn = form.querySelector(".add-to-cart-btn");
      const formData = new FormData(form);

      fetch(form.action, {
        method: "POST",
        body: formData,
        headers: { "X-Requested-With": "XMLHttpRequest" },
        credentials: "include"
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          // 🔹 Actualizar contador del carrito global
          document.dispatchEvent(new CustomEvent("cartUpdated", {
            detail: { count: data.itemCount }
          }));

          // 🔹 Feedback visual en el botón
          const originalText = btn.innerHTML;
          btn.innerHTML = "✔️ Añadido";
          btn.disabled = true;
          setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
          }, 1500);
        } else {
          alert(data.message || "Error al agregar al carrito");
        }
      })
      .catch(err => console.error("❌ Error AJAX:", err));
    });
  });
});
</script>

</html>