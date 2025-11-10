<?php
$metaTitle = "Bienvenido a BYTEBOX - Tecnología y Novedades";
$metaDescription = "Descubre lo último en tecnología, novedades y accesorios al mejor precio.";

$metaTitle = "Bytebox - Tu Tienda de Tecnología y Componentes";
$metaDescription = "Descubre lo último en tecnología, componentes de PC, periféricos y más. Calidad y confianza en cada compra.";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// --- Fallback para categorías (Mantenido de tu código) ---
if (!isset($categorias)) {
  try {
    if (class_exists('\Models\Categoria')) {
      $categorias = method_exists('\Models\Categoria', 'obtenerPadres')
        ? \Models\Categoria::obtenerPadres()
        : [];
    } else {
      $categorias = [];
    }
  } catch (\Throwable $e) {
    error_log('[home/index] Error cargando categorias: ' . $e->getMessage());
    $categorias = [];
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  
  <?php include_once __DIR__ . '/../admin/includes/head.php'; ?>

  <!-- Estilos -->
  <link rel="stylesheet" href="<?= url('css/home.css') ?>">
  <link rel="stylesheet" href="<?= url('css/cards.css') ?>"> <!-- No se toca -->

  <!-- Iconos y fuentes -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">

  <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
  <title><?= htmlspecialchars($metaTitle) ?></title>
</head>

<body>
  <?php include_once __DIR__ . '/../admin/componentes/popup.php'; ?>
  <?php include_once __DIR__ . '/../admin/includes/header.php'; ?>

  <main>
    <!-- ===================== HERO + CATEGORÍAS (UN CONTENEDOR 100vh) ===================== -->
    <section class="hero-and-categories">
      <div class="hero-banner-container">
        <?php if (!empty($banners)): ?>
          <?php foreach ($banners as $index => $ban): ?>
            <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>">
              <?php if (!empty($ban['enlace'])): ?>
                <a href="<?= htmlspecialchars($ban['enlace']) ?>" target="_blank" rel="noopener noreferrer">
                  <img src="<?= url('uploads/banners/' . htmlspecialchars($ban['nombre_imagen'])) ?>" alt="Banner <?= $index + 1 ?>">
                </a>
              <?php else: ?>
                <img src="<?= url('uploads/banners/' . htmlspecialchars($ban['nombre_imagen'])) ?>" alt="Banner <?= $index + 1 ?>">
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          
          <!-- Controles del carrusel (flechas y puntitos) -->
        <button class="hero-prev" aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
        <button class="hero-next" aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
        <div class="hero-dots" aria-hidden="false"></div>
          
        <?php else: ?>
          <div class="hero-content-static">
            <div class="hero-text">
              <h1 class="fade-text">GIRA, AJUSTA Y CREA 2 <span class="highlight">EL MONITOR PERFECTO</span></h1>
              <p class="fade-text">Encuentra la configuración ideal para tu espacio de trabajo o gaming con nuestra selección de monitores de alto rendimiento.</p>
            </div>
            <div class="hero-image">
              <img src="https://i.imgur.com/gYf2xS5.png" alt="Monitor Gamer de alto rendimiento">
            </div>
          </div>
        <?php endif; ?>
      </div>


    </section>
    <!-- Contenedor de categorías pegado al pie del hero (permanecerá dentro del mismo contenedor 100vh) -->
    <div class="categories-carousel-container">
      <div class="container">
        <div class="section-title" id="categorias-section">
          <h2 class="fade-text">Categorías</h2>
          <div class="line"></div>
        </div>

        <div class="categories-carousel-track" aria-label="Carrusel de categorías">
          <?php if (!empty($categorias)): ?>
            <?php foreach ($categorias as $cat): ?>
              <?php
              $catId = $cat['id'] ?? '';
              $catName = htmlspecialchars($cat['nombre'] ?? 'Categoría');
              $catLink = $catId !== '' ? url('home/busqueda?categoria=' . $catId) : '#';
              $imgFile = $cat['imagen'] ?? $cat['nombre_imagen'] ?? $cat['imagen_categoria'] ?? null;
              $imgSrc = $imgFile ? url('uploads/categorias/' . $imgFile) : url('uploads/default-category.png');
              ?>
              <a class="category-box" href="<?= $catLink ?>" aria-label="<?= $catName ?>">
                <div class="category-image"><img src="<?= $imgSrc ?>" alt="<?= $catName ?>"></div>
                <div class="category-name"><?= $catName ?></div>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align:center;">No hay categorías para mostrar.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <!-- ===================== FIN HERO + CATEGORÍAS ===================== -->
    <!-- ===================== PRODUCTOS DESTACADOS (CARRUSEL INFINITO) ===================== -->

    <section class="featured-products">
      <div class="container">
        <div class="section-title">
          <h2>Productos Destacados</h2>
          <div class="line"></div>
        </div>
      </div>
      <!-- Contenedor del carrusel de productos -->
      <div class="products-carousel-container" aria-label="Carrusel de productos destacados">
        <?php
        if (!empty($productos_destacados)) {  // Cambio 1: Usa el nombre del controlador ($productos_destacados)
          $productos = $productos_destacados;  // Cambio 2: Asigna a $productos para compatibilidad con el parcial _products_grid.php
          // Ahora el include recibirá $productos con los datos reales
          include __DIR__ . '/_products_grid.php';
        } else {
          echo '<p style="text-align:center;">No hay productos destacados disponibles.</p>';  // Fallback si no hay datos
        }
        ?>
      </div>
    </section>
    <!-- ... (código anterior) ... -->
    <!-- ================================================== -->
    <!--          INICIO DE LA SECCIÓN DE BANNERS SECUNDARIOS          -->
    <!-- ================================================== -->
    <section class="promo-banners-section">
      <div class="container">
        <div class="banners-grid">
          <?php
          $banner_sec_izq = !empty($banners_secundarios_izquierda) ? $banners_secundarios_izquierda[0] : null;
          $banner_sec_der = !empty($banners_secundarios_derecha) ? $banners_secundarios_derecha[0] : null;
          ?>

          <?php if ($banner_sec_izq): ?>
            <?php if (!empty($banner_sec_izq['enlace'])): ?>
              <a href="<?= htmlspecialchars($banner_sec_izq['enlace']) ?>" class="promo-banner-item" target="_blank" rel="noopener noreferrer">
                <img src="<?= url("uploads/banners/" . htmlspecialchars($banner_sec_izq["nombre_imagen"])) ?>" alt="Banner Secundario Izquierda">
              </a>
            <?php else: ?>
              <div class="promo-banner-item">
                <img src="<?= url("uploads/banners/" . htmlspecialchars($banner_sec_izq["nombre_imagen"])) ?>" alt="Banner Secundario Izquierda">
              </div>
            <?php endif; ?>
          <?php else: ?>
            <!-- Fallback si no hay banner secundario izquierdo en la BD -->
            <a href="#" class="promo-banner-item">
              <img src="<?= url("images/baner1.jpg") ?>" alt="Promoción o categoría destacada 1">
            </a>
          <?php endif; ?>

          <?php if ($banner_sec_der): ?>
            <?php if (!empty($banner_sec_der['enlace'])): ?>
              <a href="<?= htmlspecialchars($banner_sec_der['enlace']) ?>" class="promo-banner-item" target="_blank" rel="noopener noreferrer">
                <img src="<?= url("uploads/banners/" . htmlspecialchars($banner_sec_der["nombre_imagen"])) ?>" alt="Banner Secundario Derecha">
              </a>
            <?php else: ?>
              <div class="promo-banner-item">
                <img src="<?= url("uploads/banners/" . htmlspecialchars($banner_sec_der["nombre_imagen"])) ?>" alt="Banner Secundario Derecha">
              </div>
            <?php endif; ?>
          <?php else: ?>
            <!-- Fallback si no hay banner secundario derecho en la BD -->
            <a href="#" class="promo-banner-item">
              <img src="<?= url("images/baner2.jpg") ?>" alt="Promoción o categoría destacada 2">
            </a>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <!-- ================================================== -->
    <!--           FIN DE LA SECCIÓN DE BANNERS             -->
    <!-- ================================================== -->
    <!-- ... (código posterior) ... -->
    <!-- WHY CHOOSE US -->
    <section class="why-choose-us">
      <div class="container">
        <div class="section-title">
          <h2>¿Por qué elegir Bytebox?</h2>
          <div class="line"></div>
        </div>
        <div class="features-grid">
          <div class="feature-box">
            <i class="fa-solid fa-shield-halved"></i>
            <h3>Calidad Premium</h3>
            <p>Garantizamos productos seleccionados bajo estrictos estándares, diseñados para ofrecerte el máximo rendimiento y una experiencia de compra superior.</p>
          </div>
          <div class="feature-box">
            <i class="fa-solid fa-headset"></i>
            <h3>Soporte Postventa</h3>
            <p>Nuestro equipo especializado te brinda asistencia continua después de tu compra, resolviendo dudas y asegurando el mejor desempeño de tus equipos.</p>
          </div>
          <div class="feature-box">
            <i class="fa-solid fa-truck-fast"></i>
            <h3>Envíos a Nivel Nacional</h3>
            <p>Realizamos entregas rápidas y seguras, con un tiempo de envío de hasta 24 horas en Lima y de 1 a 2 días en provincias, asegurando puntualidad y confianza en cada pedido.</p>
          </div>
        </div>

      </div>
    </section>
  </main>

  <?php include_once __DIR__ . '/../admin/includes/footer.php'; ?>

  <!-- SCRIPTS -->
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const targetId = "categorias-section";
      const target = document.getElementById(targetId);

      // 👉 1. Interceptar clics en el link dentro de la misma página
      const link = document.querySelector(`a[href$="#${targetId}"]`);
      if (link && target) {
        link.addEventListener("click", function(e) {
          e.preventDefault();
          target.scrollIntoView({
            behavior: "smooth",
            block: "center"
          });
          // Actualizar hash en la URL sin que el navegador haga scroll automático
          history.pushState(null, "", `#${targetId}`);
        });
      }

      // 👉 2. Si vienes desde otra página con hash (#categorias-section)
      if (window.location.hash === `#${targetId}` && target) {
        // Esperar un momento a que cargue todo antes de centrar
        setTimeout(() => {
          target.scrollIntoView({
            behavior: "smooth",
            block: "center"
          });
        }, 300); // puedes ajustar el delay
      }
    });
    // --- Banner simple fade ---
    (function() {
      const slides = document.querySelectorAll('.hero-slide');
      if (slides.length <= 1) return;
      let currentSlide = 0;
    
      function showNextSlide() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
      }
      setInterval(showNextSlide, 12500);
    })();

    // --- Funcionalidad de Arrastre para Productos con Scroll Infinito Seamless ---
    function setupDragCarousel(containerSelector) {
      const container = document.querySelector(containerSelector);
      if (!container) return;

      const track = container.querySelector('.products-grid');
      if (!track) return;

      const originalItems = Array.from(track.children);
      if (originalItems.length === 0) return;

      // **IMPORTANTE**: Asegúrate de que tu CSS no tenga la animación de keyframes:
      // .products-grid.scrolling { animation: none !important; }

      // PASO 1: Clonar items para loop infinito (3 sets totales)
      for (let i = 0; i < 2; i++) {
        originalItems.forEach(item => {
          const clone = item.cloneNode(true);
          clone.setAttribute('aria-hidden', 'true');
          track.appendChild(clone);
        });
      }

      // PASO 2: Variables de Estado y Dimensiones
      let itemWidth = 0;
      let setWidth = 0; // Ancho de un set original
      const totalSets = 3;
      let isDragging = false;
      let startX = 0;
      let initialX = 0;

      // currentX es ahora el estado principal de la posición (negativo, va hacia la izquierda)
      let currentX = 0;
      let animationFrameId = null;
      const scrollSpeed = 0.5; // Velocidad de scroll automático en píxeles por frame
      const clickThreshold = 5;

      // --- Funciones de Utilidad ---

      function calculateDimensions() {
        if (originalItems.length === 0) return;
        const firstItem = originalItems[0];

        // Calcular el ancho del ítem (incluye margin o padding si está en box-sizing: border-box)
        const style = window.getComputedStyle(firstItem);
        const marginRight = parseFloat(style.marginRight) || 0;

        // Esta es la unidad de movimiento (ancho del ítem + su margen derecho)
        const effectiveItemWidth = firstItem.offsetWidth + marginRight;

        // setWidth es el ancho total de UN set original (N items * ancho efectivo)
        setWidth = effectiveItemWidth * originalItems.length;

        track.style.width = `${setWidth * totalSets}px`;

        // Establecer la posición inicial justo al inicio del SEGUNDO set (posición visual más estable)
        // Ya que currentX se normaliza en el rango [-setWidth, 0], empezar en -setWidth es más robusto.
        currentX = -setWidth;
      }

      // La función principal de renderizado y loop infinito
      function autoScroll() {
        if (isDragging) {
          // No hacer scroll si estamos arrastrando
          animationFrameId = requestAnimationFrame(autoScroll);
          return;
        }

        // 1. Mover: Desplaza currentX a la izquierda (negativo)
        currentX -= scrollSpeed;

        // 2. Normalizar/Loop: Si nos movemos más allá del set central (ancho del set - 1px)
        // El rango de visualización principal es de [-setWidth * 2, -setWidth]
        // Si currentX se mueve a -setWidth * 2 (el inicio del tercer set), 
        // lo teletransportamos al inicio del segundo set (-setWidth)
        if (currentX <= -(setWidth * 2)) {
          // Esto es el salto invisible. El movimiento se reanuda sin cambios bruscos
          currentX += setWidth; // O equivalentemente: currentX = -setWidth;
        }

        // 3. Renderizar
        track.style.transform = `translateX(${currentX}px)`;

        // 4. Continuar el loop
        animationFrameId = requestAnimationFrame(autoScroll);
      }

      // --- Inicialización y Eventos ---

      calculateDimensions();
      track.style.transform = `translateX(${currentX}px)`;

      // Iniciar el scroll automático
      animationFrameId = requestAnimationFrame(autoScroll);

      window.addEventListener('resize', () => {
        calculateDimensions();
        // Si no está arrastrando, reubicar al inicio del set central
        if (!isDragging) {
          currentX = -setWidth;
        }
      });


      // --- Handlers de Arrastre (Drag) ---

      const handleDragStart = (e) => {
        if (isDragging) return;
        isDragging = true;

        // Pausar el scroll automático
        if (animationFrameId) {
          cancelAnimationFrame(animationFrameId);
          animationFrameId = null;
        }

        container.classList.add('dragging');
        track.style.cursor = 'grabbing';

        initialX = currentX; // Guarda la posición actual de donde empezamos a arrastrar

        const pageX = e.touches ? e.touches[0].pageX : e.pageX;
        startX = pageX - container.offsetLeft;
      };

      const handleDragMove = (e) => {
        if (!isDragging) return;
        e.preventDefault();

        const pageX = e.touches ? e.touches[0].pageX : e.pageX;
        const x = pageX - container.offsetLeft;
        const walk = (x - startX) * 1.5; // Sensibilidad de arrastre (aumentada para mejor respuesta)

        let nextX = initialX + walk;

        // Loop infinito mientras arrastramos: Mantiene el track en el rango de los 3 sets
        // Si nos pasamos de -2*setWidth (fin del 3er set), saltamos a -setWidth
        if (nextX <= -(setWidth * 2)) {
          nextX += setWidth;
          initialX += setWidth; // Ajusta initialX para que el arrastre sea continuo
        }
        // Si nos pasamos de 0 (inicio del 1er set), saltamos a -setWidth
        if (nextX >= 0) {
          nextX -= setWidth;
          initialX -= setWidth; // Ajusta initialX para que el arrastre sea continuo
        }

        currentX = nextX;
        track.style.transform = `translateX(${currentX}px)`;
      };

      const handleDragEnd = () => {
        if (!isDragging) return;
        isDragging = false;
        container.classList.remove('dragging');
        track.style.cursor = 'grab';

        // Reanudar el scroll automático (rAF toma la posición actual 'currentX' y continúa desde ahí)
        if (!animationFrameId) {
          animationFrameId = requestAnimationFrame(autoScroll);
        }
      };


      // --- Event Listeners ---

      // Mouse Events
      container.addEventListener('mousedown', handleDragStart);
      document.addEventListener('mousemove', handleDragMove);
      document.addEventListener('mouseup', handleDragEnd);

      // Touch Events
      container.addEventListener('touchstart', handleDragStart, {
        passive: false
      });
      container.addEventListener('touchmove', handleDragMove, {
        passive: false
      });
      document.addEventListener('touchend', handleDragEnd);

      // Prevención de clicks si hubo arrastre significativo
      container.addEventListener('click', (e) => {
        if (Math.abs(currentX - initialX) > clickThreshold) {
          e.preventDefault();
          e.stopPropagation();
        }
      }, true);

      // Prevención de defaults del navegador
      container.addEventListener('dragstart', e => e.preventDefault());
      container.addEventListener('selectstart', e => e.preventDefault());
    }

    // --- Inicializar (sin cambios) ---
    document.addEventListener('DOMContentLoaded', function() {
      setupDragCarousel('.products-carousel-container');
    });


    // --- Delegación: plus / minus (cantidad) ---
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.quantity-btn');
    if (!btn) return;

    // Evitar que el click se propague al carrusel al arrastrar
    e.preventDefault();
    e.stopPropagation();

    const control = btn.closest('.quantity-controls');
    if (!control) return;
    const input = control.querySelector('.quantity-input');
    if (!input) return;

    let value = parseInt(input.value || '1', 10);
    if (btn.classList.contains('minus')) {
      value = Math.max(1, value - 1);
    } else if (btn.classList.contains('plus')) {
      value = Math.min(99, value + 1);
    }
    input.value = value;

    // Opcional: disparar un evento change si otros scripts lo escuchan
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  // --- Delegación: submit de formularios .add-to-cart-form ---
  document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form || !form.classList.contains('add-to-cart-form')) return;

    e.preventDefault();
    e.stopPropagation();

    const button = form.querySelector('.add-button');
    const originalHTML = button ? button.innerHTML : null;

    // Extraer campos
    const productoIdInput = form.querySelector('input[name="producto_id"]');
    const cantidadInput = form.querySelector('input[name="cantidad"]') || form.querySelector('.quantity-input');
    const csrfInput = form.querySelector('input[name="csrf_token"]');

    const producto_id = productoIdInput ? productoIdInput.value : '';
    const cantidad = cantidadInput ? (cantidadInput.value || '1') : '1';
    const csrf_token = csrfInput ? csrfInput.value : '';

    // Feedback: mostrar spinner
    if (button) {
      button.disabled = true;
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
    }

    try {
      const body = new URLSearchParams();
      body.append('producto_id', producto_id);
      body.append('cantidad', cantidad);
      if (csrf_token) body.append('csrf_token', csrf_token);

      const response = await fetch('<?= url("carrito/agregar") ?>', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: body
      });

      // Intentar parsear JSON aun si el servidor responde 302/HTML (evitamos crash)
      let data = null;
      try {
        data = await response.json();
      } catch (err) {
        // Si no es JSON, devolvemos error genérico
        console.error('Respuesta no JSON al agregar al carrito', err);
        data = { success: false, message: 'Respuesta inesperada del servidor.' };
      }

      if (data && data.success) {
      // 🔹 Emitir el evento global para actualizar el contador del header
      if (typeof data.itemCount !== 'undefined') {
        document.dispatchEvent(new CustomEvent("cartUpdated", {
          detail: { count: data.itemCount }
        }));
      }

      // 🔹 Feedback visual en el botón
      if (button) {
        button.innerHTML = '<i class="fas fa-check"></i> Añadido';
        button.classList.add('added');
      }

        // Restaurar después de un tiempo
        setTimeout(() => {
          if (button) {
            button.disabled = false;
            if (originalHTML) button.innerHTML = originalHTML;
            button.classList.remove('added');
          }
        }, 1400);
      } else {
        // Error
        const msg = (data && data.message) ? data.message : 'No se pudo agregar al carrito.';
        console.warn('Agregar carrito: ', msg);
        if (button) button.innerHTML = '<i class="fas fa-times"></i> Error';

        setTimeout(() => {
          if (button) {
            button.disabled = false;
            if (originalHTML) button.innerHTML = originalHTML;
          }
        }, 1400);
      }
    } catch (error) {
      console.error('Error en fetch carrito:', error);
      if (button) {
        button.innerHTML = '<i class="fas fa-times"></i> Error';
        setTimeout(() => {
          if (button) {
            button.disabled = false;
            if (originalHTML) button.innerHTML = originalHTML;
          }
        }, 1400);
      }
    }
  });

  // --- Protección extra: prevenir que el submit tradicional ocurra en clicks del carrusel
  // (En caso haya handlers que llamen form.submit())
  window.addEventListener('beforeunload', () => {}); // no-op — solo placeholder para evitar comportamiento raro

  </script>
  
<script>
document.addEventListener('DOMContentLoaded', function() {
  const slides = Array.from(document.querySelectorAll('.hero-slide'));
  const prevBtn = document.querySelector('.hero-prev');
  const nextBtn = document.querySelector('.hero-next');
  const dotsContainer = document.querySelector('.hero-dots');

  if (!slides.length) return;

  if (slides.length === 1) {
    slides[0].classList.add('active');
    if (dotsContainer) dotsContainer.style.display = 'none';
    if (prevBtn) prevBtn.style.display = 'none';
    if (nextBtn) nextBtn.style.display = 'none';
    return;
  }

  slides.forEach((_, i) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'dot' + (i === 0 ? ' active' : '');
    btn.addEventListener('click', () => goTo(i));
    dotsContainer.appendChild(btn);
  });

  const dots = Array.from(dotsContainer.querySelectorAll('.dot'));
  let current = slides.findIndex(s => s.classList.contains('active'));
  if (current === -1) current = 0;

  slides.forEach((s, i) => s.classList.toggle('active', i === current));
  dots.forEach((d, i) => d.classList.toggle('active', i === current));

  let timer = null;
  const autoplayInterval = 7000;

  function goTo(index) {
    slides[current].classList.remove('active');
    dots[current].classList.remove('active');
    current = index;
    slides[current].classList.add('active');
    dots[current].classList.add('active');
    resetTimer();
  }

  function next() {
    goTo((current + 1) % slides.length);
  }

  function prev() {
    goTo((current - 1 + slides.length) % slides.length);
  }

  function resetTimer() {
    if (timer) clearInterval(timer);
    timer = setInterval(next, autoplayInterval);
  }

  if (nextBtn) nextBtn.addEventListener('click', next);
  if (prevBtn) prevBtn.addEventListener('click', prev);

  resetTimer();
});
</script>
  
</body>

</html>