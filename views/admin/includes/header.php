<?php
// header.php (reemplazar el archivo actual con este)
use Core\Helpers\CookieHelper;
// Asegurarnos session + $cantidadEnCarrito disponible
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($cantidadEnCarrito)) {
  $cantidadEnCarrito = 0;
  if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
      $cantidadEnCarrito += (int)($item['cantidad'] ?? 0);
    }
  }
}

// Función para verificar si el usuario es un cliente (rol usuario)
function isClienteHeader()
{
  $userRole = \Core\Helpers\SessionHelper::getRole();

  // Si el rol es un array, obtener el nombre
  if (is_array($userRole) && isset($userRole['nombre'])) {
    return $userRole['nombre'] === 'usuario';                              
  }

  // Si es una cadena, verificar directamente
  if (is_string($userRole)) {
    return $userRole === 'usuario';
  }

  // Verificar por permisos - los clientes solo tienen 'perfil'
  $userPermissions = \Core\Helpers\SessionHelper::getPermissions();
  if (is_array($userPermissions)) {
    // Cliente típico: solo tiene permiso de 'perfil' y no tiene permisos administrativos
    return in_array('perfil', $userPermissions) &&
      !in_array('usuarios', $userPermissions) &&
      !in_array('productos', $userPermissions);
  }

  return false;
}

/* -----------------------
   CARGA DINÁMICA DE CATEGORÍAS (con id_padre y tree)
   ----------------------- */
$allCategories = [];
$categoriasTree = [];
$parentCategories = [];

try {
  if (class_exists('\Models\Categoria') && method_exists('\Models\Categoria', 'obtenerTodas')) {
    $raw = \Models\Categoria::obtenerTodas();
  } else {
    $db = \Core\Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, nombre, IFNULL(slug, id) AS slug, activo, COALESCE(id_padre, 0) AS id_padre, orden FROM categorias WHERE activo = 1 ORDER BY orden ASC, nombre ASC");
    $stmt->execute();
    $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  if (!is_array($raw)) {
    $raw = json_decode(json_encode($raw), true) ?: [];
  }
  $allCategories = $raw;

  $itemsByParent = [];
  foreach ($allCategories as $c) {
    $pid = isset($c['id_padre']) && ($c['id_padre'] !== '') ? (int)$c['id_padre'] : 0;
    $itemsByParent[$pid][] = $c;
  }

  $buildTree = function ($parentId) use (&$itemsByParent, &$buildTree) {
    $branch = [];
    if (!isset($itemsByParent[$parentId])) return [];
    foreach ($itemsByParent[$parentId] as $item) {
      $children = $buildTree((int)$item['id']);
      if (!empty($children)) $item['children'] = $children;
      $branch[] = $item;
    }
    return $branch;
  };

  $categoriasTree = $buildTree(0);
  $parentCategories = $itemsByParent[0] ?? [];
} catch (\Throwable $e) {
  error_log("Header: error cargando categorías - " . $e->getMessage());
  $allCategories = $categoriasTree = $parentCategories = [];
}

function categoria_url($cat)
{
  $identifier = '';
  if (isset($cat['id']) && $cat['id'] !== '') {
    $identifier = $cat['id'];
  } elseif (isset($cat['slug']) && $cat['slug'] !== '') {
    $identifier = $cat['slug'];
  }
  return url('home/busqueda') . '?categoria=' . rawurlencode($identifier);
}

/* Flag para abrir el modal si viene error del servidor o query param */
$openLoginModalOnLoad = false;
$loginErrorMsg = null;
if (!empty($_SESSION['login_error'])) {
  $openLoginModalOnLoad = true;
  $loginErrorMsg = $_SESSION['login_error'];
  // unsetear para no repetir en siguiente request
  unset($_SESSION['login_error']);
}
if (!empty($_GET['open_login']) && $_GET['open_login'] == '1') {
  $openLoginModalOnLoad = true;
}
?>

<link rel="stylesheet" href="<?= url('css/header.css') ?>">

<div class="sticky-header-wrapper">
  <header class="main-header">
    <div class="header-content">

      <!-- Left: logo -->
      <div class="header-left ml-[15px]">
        <a href="<?= url('home/index') ?>" class="logo-link" aria-label="Bytebox home">
          <img src="<?= url('images/Logo_Horizontal2_Versi_nPrincipal.png') ?>" alt="Bytebox" class="logo-image">
        </a>
      </div>

      <!-- Center: search -->
      <nav class="main-nav">
        <form class="search-form" action="<?= url('producto/busqueda') ?>" method="GET" role="search" autocomplete="off">
          <input
            type="search"
            name="q"
            class="search-input"
            placeholder="Buscar productos..."
            value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '' ?>"
            aria-label="Buscar productos"
            spellcheck="false"
            autocapitalize="off"
            autocomplete="off" />
          <div id="autocomplete-results" class="autocomplete-results" role="listbox" aria-expanded="false"></div>
          <button type="submit" class="search-button" aria-label="Buscar">
            <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
            </svg>
          </button>
        </form>
      </nav>

      <!-- Perfil: contenedor envuelve botón + dropdown (para evitar gaps) -->
      <div class="user-profile-container" id="userProfileContainer">
        <button class="user-profile-button" id="userProfileButton" aria-haspopup="true" aria-expanded="false" aria-label="Abrir menú de usuario">
          <svg class="user-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="32" height="32" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4 0-7 2-7 4v2h14v-2c0-2-3-4-7-4z" />
          </svg>
          <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>

        <div class="profile-dropdown" id="profileDropdown" role="menu" aria-hidden="true">
          <div class="dropdown-options">
            <?php if (isset($_SESSION['user_id'])): ?>
              <a href="<?= url('auth/profile') ?>" class="dropdown-item" role="menuitem">Mi Cuenta</a>
              <div class="dropdown-divider"></div>
              <a href="<?= url('usuario/pedidos') ?>" class="dropdown-item" role="menuitem">Mis Pedidos</a>
              <div class="dropdown-divider"></div>
              <a href="<?= url('auth/logout') ?>" class="dropdown-item logout-item" role="menuitem">Cerrar Sesión</a>
            <?php else: ?>
              <!-- Botón que abre el modal -->
              <button type="button" class="dropdown-item login-item" id="openLoginModalBtn" role="menuitem" aria-haspopup="dialog">
                <svg class="dropdown-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 12h14M12 5l7 7-7 7" />
                </svg>
                <span class="dropdown-item-text">Iniciar Sesión</span>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Carrito (a la derecha del perfil) -->
      <div class="cart-section">
        <a href="<?= url('carrito/ver') ?>" class="cart-button" aria-label="Ver carrito">
          <svg class="cart-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="32" height="32" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M2 6h2l2 12h12l2-8H6M16 18a2 2 0 11-4 0 2 2 0 014 0zm-6 0a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <?php if ($cantidadEnCarrito > 0): ?>
            <span class="cart-badge" id="cart-count" aria-live="polite" aria-atomic="true">
              <?= $cantidadEnCarrito ?>
            </span>
          <?php endif; ?>
        </a>
      </div>

    </div>

  </header>
  <div class="categories-bar">
    <div class="categories-content">
      <div class="all-categories-dropdown-container">
        <button class="all-categories-button ml-[15px]" id="allCategoriesButton" aria-haspopup="true" aria-expanded="false">
          Todas las Categorías
          <svg class="dropdown-arrow-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>

        <div class="categories-dropdown" id="categoriesDropdown" role="menu" aria-hidden="true">
          <?php
          $cols = 3;
          $topLevel = $categoriasTree; // elementos de primer nivel (cada uno puede traer 'children')
          $total = count($topLevel);

          if ($total === 0) {
            echo '<div class="category-column"><div class="category-empty">No hay categorías</div></div>';
          } else {
            $perCol = (int) ceil($total / $cols);
            $chunks = array_chunk($topLevel, $perCol);

            // closure recursivo para renderizar children
            $render_children = function ($children) use (&$render_children) {
              $html = '<ul class="subcategory-list">';
              foreach ($children as $ch) {
                $name = htmlspecialchars($ch['nombre'] ?? 'Sin nombre', ENT_QUOTES, 'UTF-8');
                $href = htmlspecialchars(categoria_url($ch), ENT_QUOTES, 'UTF-8');
                $html .= "<li class=\"subcategory-item\"><a href=\"{$href}\">{$name}</a>";
                if (!empty($ch['children'])) {
                  $html .= $render_children($ch['children']);
                }
                $html .= "</li>";
              }
              $html .= '</ul>';
              return $html;
            };

            foreach ($chunks as $i => $chunk) {
              echo '<div class="category-column">';

              // 🔹 Solo en la primera columna agregamos "Todas las categorías"
              if ($i === 0) {
                echo '<div class="category-item-with-children">';
                echo '<a href="' . url("home/busqueda") . '" class="category-item parent font-semibold text-blue-600">Todas las categorías</a>';
                echo '</div>';
              }

              foreach ($chunk as $c) {
                $nombre = htmlspecialchars($c['nombre'] ?? 'Sin nombre', ENT_QUOTES, 'UTF-8');
                $href = htmlspecialchars(categoria_url($c), ENT_QUOTES, 'UTF-8');
                echo "<div class=\"category-item-with-children\">";
                echo "<a href=\"{$href}\" class=\"category-item parent\">{$nombre}</a>";
                if (!empty($c['children'])) {
                  echo $render_children($c['children']);
                }
                echo "</div>";
              }
              echo '</div>';
            }
          }
          ?>
        </div>

      </div>

      <nav class="category-links">
        <?php
        // Mostrar las primeras N categorías en la barra superior (p. ej. 8)
        $topN = 8;
        $top = !empty($categoriasTree) ? array_slice($categoriasTree, 0, $topN) : array_slice($allCategories, 0, $topN);
        if (empty($top)) {
          // Fallback: enlace estático opcional
          echo '<a href="#" class="category-link">SIN CATEGORÍAS</a>';
        } else {
          foreach ($top as $t) {
            $label = mb_strtoupper(trim($t['nombre'] ?? ''), 'UTF-8');
            $href = htmlspecialchars(categoria_url($t), ENT_QUOTES, 'UTF-8');
            echo "<a href=\"{$href}\" class=\"category-link\">{$label}</a>";
          }
        }
        ?>
      </nav>
    </div>
  </div>
</div> <!-- <<-- CERRAR EL DIV ENVOLVENTE -->

<!-- Exponer BASE_URL para JS -->
<script>
  const BASE_URL = "<?= rtrim(url(''), '/') ?>";
  const OPEN_LOGIN_MODAL_ON_LOAD = <?= $openLoginModalOnLoad ? 'true' : 'false' ?>;
</script>

<!-- Modal de login (insertado en header para no navegar a nueva página) -->
<div id="loginModal" class="login-modal " aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle">
  <div class="max-w-md w-full space-y-8 p-8">
    <div class="login-modal-overlay" id="loginModalOverlay" tabindex="-1"></div>

    <div class="login-modal-panel" id="loginModalPanel" role="document" aria-describedby="loginModalDesc">
      <button type="button" class="login-modal-close" id="loginModalClose" aria-label="Cerrar">&times;</button>

      <!-- Header -->
      <div class="text-center" style="margin-bottom:1rem;">
        <h2 id="loginModalTitle" class="mt-2 text-3xl font-extrabold text-gray-900">Bytebox</h2>
        <p id="loginModalDesc" class="mt-1 text-sm text-gray-600">Inicia sesión en tu cuenta</p>
      </div>

      <!-- Mensajes de error -->
      <?php if (!empty($loginErrorMsg)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 login-error" role="alert">
          <?= htmlspecialchars($loginErrorMsg) ?>
        </div>
      <?php endif; ?>

      <!-- Botones de login social -->
      <?php
      // Intentar incluir partial login_social desde varias ubicaciones razonables
      $loginSocialIncluded = false;
      $pathsToTry = [
        __DIR__ . '/login_social.php',                // donde podría estar si pusiste el partial junto al header
        __DIR__ . '/../auth/login_social.php',       // views/includes -> views/auth
        __DIR__ . '/../../views/auth/login_social.php',
        __DIR__ . '/../../auth/login_social.php',
        __DIR__ . '/../../views/includes/login_social.php'
      ];

      foreach ($pathsToTry as $p) {
        if (file_exists($p)) {
          include $p;
          $loginSocialIncluded = true;
          break;
        }
      }

      // Si no se encontró el partial, mostramos un fallback (enlaces a endpoints OAuth).
      if (!$loginSocialIncluded):
      ?>
        <div class="login-social-row" style="display:flex;gap:0.5rem;flex-direction:column;margin-bottom:1rem;">
          <a href="<?= url('auth/oauth/google') ?>" class="social-btn google-btn"
            style="display:inline-flex;align-items:center;gap:8px;padding:10px;border-radius:6px;border:1px solid #d1d5db;text-decoration:none;">
            <!-- Google SVG simple -->
            <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
              <path fill="#EA4335" d="M24 9.5c3.54 0 6.7 1.23 9.2 3.24l6.86-6.86C36.43 3.01 30.55 1 24 1 14.97 1 6.96 6.6 3.06 14.86l7.83 6.09C12.9 16.05 18.85 9.5 24 9.5z" />
            </svg>
            <span style="font-size:14px;color:#111;">Continuar con Google</span>
          </a>

          <a href="<?= url('auth/facebook/login') ?>" class="social-btn fb-btn"
            style="display:inline-flex;align-items:center;gap:8px;padding:10px;border-radius:6px;border:1px solid #d1d5db;text-decoration:none;">
            <!-- Facebook simple -->
            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
              <path fill="#1877F2" d="M22 12.07C22 6.49 17.52 2 12 2S2 6.49 2 12.07C2 17.09 5.66 21.21 10.44 22v-7.07H8.08v-2.86h2.36V9.84c0-2.34 1.39-3.63 3.52-3.63 1.02 0 2.09.18 2.09.18v2.3h-1.18c-1.16 0-1.52.72-1.52 1.46v1.76h2.59l-.41 2.86h-2.18V22C18.34 21.21 22 17.09 22 12.07z" />
            </svg>
            <span style="font-size:14px;color:#111;">Continuar con Facebook</span>
          </a>
        </div>
      <?php
      endif;
      ?>


      <!-- Formulario de login -->
      <form id="loginModalForm" method="POST" action="<?= url('auth/authenticate') ?>">
        <?php
        // Generar token CSRF para el formulario de login
        if (class_exists('\Core\Helpers\CsrfHelper')) {
          echo \Core\Helpers\CsrfHelper::tokenField('login_form');
        }
        ?>

        <input type="hidden" name="redirect"
          value="<?php 
            // Extraer la ruta relativa sin la base URL
            $currentUri = $_SERVER['REQUEST_URI'] ?? '';
            $basePath = '/TECNOVEDADES/public/';
            if (strpos($currentUri, $basePath) === 0) {
              $currentUri = substr($currentUri, strlen($basePath));
            }
            echo htmlspecialchars($currentUri ?: 'home/index', ENT_QUOTES, 'UTF-8');
          ?>">

        <div class="modal-row">
          <label for="loginEmail">Correo Electrónico</label>
          <input id="email"
            name="email"
            type="email"
            autocomplete="email"
            required
            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
            placeholder="tu@email.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="modal-row">
          <label for="loginPassword">Contraseña</label>
          <input id="password"
            name="password"
            type="password"
            autocomplete="current-password"
            required
            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
            placeholder="••••••••">
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input id="remember"
              name="remember"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <label for="remember" class="ml-2 block text-sm text-gray-700">
              Recordarme
            </label>
          </div>

          <div class="text-sm">
            <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
              ¿Olvidaste tu contraseña?
            </a>
          </div>
        </div>

        <!-- Submit button -->
        <div>
          <button type="submit"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-blue-500 group-hover:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
              </svg>
            </span>
            Iniciar Sesión
          </button>
          <div class="text-center text-sm text-gray-700"> ¿No tienes una cuenta?
            <a href="<?= url('auth/registro?redirect=' . urlencode('carrito/ver')) ?>" class="btn-secondary font-medium text-blue-600 hover:text-blue-500">
              Crea una
            </a>.
          </div>
        </div>
      </form>
      <!-- Botón para regresar a la tienda -->
      <div class="mt-6 text-center">
        <a href="<?= url('home/index') ?>" class="inline-block px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded font-medium transition">&larr; Regresar a la tienda</a>
      </div>

    </div>
    <!-- Footer -->
    <div class="text-center">
      <p class="text-sm text-white opacity-75">
        © <?= date('Y') ?> Bytebox. Todos los derechos reservados.
      </p>
    </div>
  </div>
</div>


<!-- Estilos del modal (puedes mover a header.css) -->
<style>
  .login-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 2200;
    align-items: flex-start;
    justify-content: center;
    padding: 3rem 1rem;
  }

  .login-modal.open {
    display: flex;
  }

  .login-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(2px);
  }

  .login-modal-panel {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 520px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
    padding: 1.25rem;
  }

  .login-modal-close {
    position: absolute;
    right: 10px;
    top: 10px;
    border: 0;
    background: transparent;
    font-size: 20px;
    cursor: pointer;
  }

  .login-error {
    background: #ffe6e6;
    border: 1px solid #ffbdbd;
    color: #700;
    padding: 0.6rem;
    border-radius: 6px;
    margin-bottom: 0.8rem;
  }

  .modal-row {
    margin-bottom: 0.75rem;
  }

  .modal-row label {
    display: block;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
    color: #333;
  }

  .modal-row input[type="email"],
  .modal-row input[type="password"] {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    box-sizing: border-box;
  }

  .modal-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #2563eb;
    color: white;
    border: none;
    padding: 0.6rem 1rem;
    border-radius: 6px;
    cursor: pointer;
  }

  .modal-submit:hover {
    background: #1e40af;
  }

  .modal-small {
    font-size: 0.9rem;
    color: #444;
  }

  .login-social-row {
    margin-bottom: 0.6rem;
  }

  @media (max-width:640px) {
    .login-modal {
      align-items: flex-start;
      padding-top: 1.5rem;
    }

    .login-modal-panel {
      margin: 0 12px;
      width: calc(100% - 24px);
    }
  }
</style>
<!-- Banner de Cookies -->
<?php include_once __DIR__ . '/cookies_banner.php'; ?>

<!-- DEBUG TEMPORAL -->
<script>
console.log('Cookie consent exists:', <?= json_encode(CookieHelper::exists('cookies_consent')) ?>);
console.log('Cookie consent value:', <?= json_encode(CookieHelper::get('cookies_consent')) ?>);
</script>

<!-- Autocomplete + dropdown profile behavior + modal JS -->
<script>
  document.addEventListener('DOMContentLoaded', function() {

    /* ---------- Autocomplete ---------- */
    const input = document.querySelector('.search-input');
    const resultsContainer = document.getElementById('autocomplete-results');
    let debounceTimeout;

    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/[&<>"']/g, s => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      } [s]));
    }

    function hideResults() {
      if (!resultsContainer) return;
      resultsContainer.style.display = 'none';
      resultsContainer.innerHTML = '';
      resultsContainer.setAttribute('aria-expanded', 'false');
    }

    if (input) {
      input.addEventListener('input', function(e) {
        const q = this.value.trim();
        if (q.length === 0) {
          hideResults();
          return;
        }

        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(() => {
          fetch(`${BASE_URL}/producto/autocomplete?q=${encodeURIComponent(q)}`)
            .then(resp => {
              if (!resp.ok) throw new Error('Network error');
              return resp.json();
            })
            .then(data => {
              if (!resultsContainer) return;
              resultsContainer.innerHTML = '';
              if (!Array.isArray(data) || data.length === 0) {
                hideResults();
                return;
              }

              data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'autocomplete-item';
                const imgSrc = item.imagen ? `${BASE_URL}/uploads/${item.imagen}` : `${BASE_URL}/uploads/default-product.png`;
                div.innerHTML = `
                  <img src="${escapeHtml(imgSrc)}" class="autocomplete-img" alt="${escapeHtml(item.nombre)}">
                  <div class="autocomplete-info">
                    <div class="autocomplete-name">${escapeHtml(item.nombre)}</div>
                    <div class="autocomplete-price">S/ ${Number(item.precio || 0).toFixed(2)}</div>
                  </div>
                `;
                div.addEventListener('click', () => {
                  window.location.href = `${BASE_URL}/producto/ver/${encodeURIComponent(item.id)}`;
                });
                resultsContainer.appendChild(div);
              });

              resultsContainer.style.display = 'block';
              resultsContainer.setAttribute('aria-expanded', 'true');
            })
            .catch(err => {
              console.error('Autocomplete error', err);
              hideResults();
            });
        }, 250);
      });

      document.addEventListener('click', (e) => {
        if (resultsContainer && !resultsContainer.contains(e.target) && e.target !== input) {
          hideResults();
        }
      });
    }

    /* ---------- Profile dropdown open/close with small delay ---------- */
    const profileContainer = document.getElementById('userProfileContainer');
    const profileDropdown = document.getElementById('profileDropdown');
    let profileCloseTimeout = null;

    if (profileContainer) {
      profileContainer.addEventListener('mouseenter', () => {
        clearTimeout(profileCloseTimeout);
        profileContainer.classList.add('open');
        profileDropdown.setAttribute('aria-hidden', 'false');
        const btn = document.getElementById('userProfileButton');
        if (btn) btn.setAttribute('aria-expanded', 'true');
      });

      profileContainer.addEventListener('mouseleave', () => {
        profileCloseTimeout = setTimeout(() => {
          profileContainer.classList.remove('open');
          profileDropdown.setAttribute('aria-hidden', 'true');
          const btn = document.getElementById('userProfileButton');
          if (btn) btn.setAttribute('aria-expanded', 'false');
        }, 200);
      });

      profileContainer.addEventListener('focusin', () => {
        clearTimeout(profileCloseTimeout);
        profileContainer.classList.add('open');
        profileDropdown.setAttribute('aria-hidden', 'false');
        const btn = document.getElementById('userProfileButton');
        if (btn) btn.setAttribute('aria-expanded', 'true');
      });
      profileContainer.addEventListener('focusout', () => {
        profileCloseTimeout = setTimeout(() => {
          profileContainer.classList.remove('open');
          profileDropdown.setAttribute('aria-hidden', 'true');
          const btn = document.getElementById('userProfileButton');
          if (btn) btn.setAttribute('aria-expanded', 'false');
        }, 200);
      });
    }

    /* ---------- Categories dropdown open/close with small delay ---------- */
    const allCategoriesButton = document.getElementById('allCategoriesButton');
    const categoriesDropdown = document.getElementById('categoriesDropdown');
    let categoriesCloseTimeout = null;

    if (allCategoriesButton && categoriesDropdown) {
      allCategoriesButton.addEventListener('mouseenter', () => {
        clearTimeout(categoriesCloseTimeout);
        categoriesDropdown.classList.add('open');
        allCategoriesButton.setAttribute('aria-expanded', 'true');
        categoriesDropdown.setAttribute('aria-hidden', 'false');
      });

      allCategoriesButton.addEventListener('mouseleave', () => {
        categoriesCloseTimeout = setTimeout(() => {
          categoriesDropdown.classList.remove('open');
          allCategoriesButton.setAttribute('aria-expanded', 'false');
          categoriesDropdown.setAttribute('aria-hidden', 'true');
        }, 200);
      });

      categoriesDropdown.addEventListener('mouseenter', () => {
        clearTimeout(categoriesCloseTimeout);
        categoriesDropdown.classList.add('open');
        allCategoriesButton.setAttribute('aria-expanded', 'true');
        categoriesDropdown.setAttribute('aria-hidden', 'false');
      });

      categoriesDropdown.addEventListener('mouseleave', () => {
        categoriesCloseTimeout = setTimeout(() => {
          categoriesDropdown.classList.remove('open');
          allCategoriesButton.setAttribute('aria-expanded', 'false');
          categoriesDropdown.setAttribute('aria-hidden', 'true');
        }, 200);
      });
    }

    /* ---------- Login modal logic ---------- */
    const openLoginBtn = document.getElementById('openLoginModalBtn');
    const loginModal = document.getElementById('loginModal');
    const loginOverlay = document.getElementById('loginModalOverlay');
    const loginPanel = document.getElementById('loginModalPanel');
    const loginClose = document.getElementById('loginModalClose');
    const loginEmail = document.getElementById('loginEmail');
    const loginForm = document.getElementById('loginModalForm');

    // helpers to open/close
    const previouslyFocused = {
      el: null
    };

    function openLoginModal() {
      if (!loginModal) return;
      previouslyFocused.el = document.activeElement;
      
      // 🔄 REGENERAR TOKEN CSRF FRESCO cada vez que se abre el modal
      fetch(`${BASE_URL}/auth/getCsrfToken`)
        .then(resp => resp.json())
        .then(data => {
          if (data.token && loginForm) {
            // Buscar el input del token CSRF
            let csrfInput = loginForm.querySelector('input[name="csrf_token"]');
            if (csrfInput) {
              csrfInput.value = data.token;
            } else {
              // Si no existe, crearlo
              csrfInput = document.createElement('input');
              csrfInput.type = 'hidden';
              csrfInput.name = 'csrf_token';
              csrfInput.value = data.token;
              loginForm.insertBefore(csrfInput, loginForm.firstChild);
            }
            console.log('✅ Token CSRF regenerado correctamente');
          }
        })
        .catch(err => {
          console.error('❌ Error regenerando CSRF token:', err);
          // Continuar de todos modos con el token inicial
        });
      
      loginModal.classList.add('open');
      loginModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      // focus first input
      if (loginEmail) {
        setTimeout(() => loginEmail.focus(), 50);
      } else if (loginPanel) {
        const first = loginPanel.querySelector('input,button,select,textarea');
        if (first) first.focus();
      }
    }

    function closeLoginModal() {
      if (!loginModal) return;
      loginModal.classList.remove('open');
      loginModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      // restore focus
      try {
        if (previouslyFocused.el) previouslyFocused.el.focus();
      } catch (e) {}
    }

    if (openLoginBtn) {
      openLoginBtn.addEventListener('click', function(e) {
        e.preventDefault();
        openLoginModal();
      });
    }

    if (loginClose) loginClose.addEventListener('click', closeLoginModal);
    if (loginOverlay) loginOverlay.addEventListener('click', (e) => {
      // si clic en el overlay => cerrar
      if (e.target === loginOverlay) closeLoginModal();
    });

    // cerrar con Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        if (loginModal && loginModal.classList.contains('open')) {
          closeLoginModal();
        }
      }
    });

    // simple validation en cliente para evitar submit vacío
    if (loginForm) {
      loginForm.addEventListener('submit', function(e) {
        const emailVal = (document.getElementById('email') || {}).value || '';
        const passVal = (document.getElementById('password') || {}).value || '';
        if (!emailVal || !passVal) {
          e.preventDefault();
          alert('Por favor completa email y contraseña');
          return false;
        }
        // allow normal submit (server will authenticate)
      });
    }

    // Abrir automáticamente si el servidor lo indicó
    if (OPEN_LOGIN_MODAL_ON_LOAD) {
      // small timeout so the DOM layout stabilizes
      setTimeout(openLoginModal, 80);
    }

  }); // DOMContentLoaded end

  document.addEventListener("cartUpdated", (e) => {
  const countEl = document.getElementById("cart-count");
  if (countEl && e.detail && typeof e.detail.count === "number") {
    countEl.textContent = e.detail.count;
  }
});
</script>