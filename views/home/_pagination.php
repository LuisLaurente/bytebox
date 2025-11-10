<?php
// views/home/_pagination.php
// Espera: $paginaActual (int), $totalPaginas (int), $currentQuery (array)
if (!isset($paginaActual)) $paginaActual = 1;
if (!isset($totalPaginas)) $totalPaginas = 1;
if (!isset($currentQuery)) $currentQuery = [];
?>

<?php if ($totalPaginas > 1): ?>
<nav class="pagination" aria-label="Paginación de productos">
    <div class="pagination-inner">
        <?php
        // Botón "Anterior" (solo si no estamos en la primera página)
        if ($paginaActual > 1) {
            $qs = $currentQuery;
            $qs['pagina'] = $paginaActual - 1;
            $hrefPrev = url('home/busqueda') . '?' . http_build_query($qs);
            echo "<a href=\"{$hrefPrev}\" class=\"page-link prev\" data-page=\"" . ($paginaActual - 1) . "\">Anterior</a>";
        }

        // Lógica de paginación simplificada como en la maqueta
        // Mostrar solo algunas páginas alrededor de la actual
        $ventana = 2; // Páginas a cada lado de la actual
        $inicio = max(1, $paginaActual - $ventana);
        $fin = min($totalPaginas, $paginaActual + $ventana);
        
        // Siempre mostrar la primera página si no está en la ventana
        if ($inicio > 1) {
            $qs = $currentQuery;
            $qs['pagina'] = 1;
            $href = url('home/busqueda') . '?' . http_build_query($qs);
            echo "<a href=\"{$href}\" class=\"page-link\" data-page=\"1\">1</a>";
            
            if ($inicio > 2) {
                echo "<span class=\"page-link disabled\">...</span>";
            }
        }

        // Páginas en la ventana actual
        for ($i = $inicio; $i <= $fin; $i++) {
            $qs = $currentQuery;
            $qs['pagina'] = $i;
            $href = url('home/busqueda') . '?' . http_build_query($qs);
            $class = ($i == $paginaActual) ? 'page-link active' : 'page-link';
            echo "<a href=\"{$href}\" class=\"{$class}\" data-page=\"{$i}\">{$i}</a>";
        }
        
        // Siempre mostrar la última página si no está en la ventana
        if ($fin < $totalPaginas) {
            if ($fin < $totalPaginas - 1) {
                echo "<span class=\"page-link disabled\">...</span>";
            }
            
            $qs = $currentQuery;
            $qs['pagina'] = $totalPaginas;
            $href = url('home/busqueda') . '?' . http_build_query($qs);
            echo "<a href=\"{$href}\" class=\"page-link\" data-page=\"{$totalPaginas}\">{$totalPaginas}</a>";
        }

        // Botón "Siguiente" (solo si no estamos en la última página)
        if ($paginaActual < $totalPaginas) {
            $qs = $currentQuery;
            $qs['pagina'] = $paginaActual + 1;
            $hrefNext = url('home/busqueda') . '?' . http_build_query($qs);
            echo "<a href=\"{$hrefNext}\" class=\"page-link next\" data-page=\"" . ($paginaActual + 1) . "\">Siguiente</a>";
        }
        ?>
    </div>
</nav>
<?php endif; ?>

