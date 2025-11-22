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
      $cantidadEnCarrito += (int) ($item['cantidad'] ?? 0);
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
    $pid = isset($c['id_padre']) && ($c['id_padre'] !== '') ? (int) $c['id_padre'] : 0;
    $itemsByParent[$pid][] = $c;
  }

  $buildTree = function ($parentId) use (&$itemsByParent, &$buildTree) {
    $branch = [];
    if (!isset($itemsByParent[$parentId]))
      return [];
    foreach ($itemsByParent[$parentId] as $item) {
      $children = $buildTree((int) $item['id']);
      if (!empty($children))
        $item['children'] = $children;
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
        <form class="search-form" action="<?= url('producto/busqueda') ?>" method="GET" role="search"
          autocomplete="off">
          <input type="search" name="q" class="search-input" placeholder="Buscar productos..."
            value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '' ?>"
            aria-label="Buscar productos" spellcheck="false" autocapitalize="off" autocomplete="off" />
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
        <button class="user-profile-button" id="userProfileButton" aria-haspopup="true" aria-expanded="false"
          aria-label="Abrir menú de usuario">
          <svg class="user-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="32" height="32"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4 0-7 2-7 4v2h14v-2c0-2-3-4-7-4z" />
          </svg>
          <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"
            aria-hidden="true">
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
              <button type="button" class="dropdown-item login-item" id="openLoginModalBtn" role="menuitem"
                aria-haspopup="dialog"
                style="width: fit-content; display: flex; align-items: center; gap: 10px; padding: 10px 15px;">
                <svg class="dropdown-item-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18"
                  height="18" aria-hidden="true" style="flex-shrink: 0;">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7" />
                </svg>
                <span class="dropdown-item-text" style="line-height: 1;">Iniciar Sesión</span>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Carrito (a la derecha del perfil) -->
      <div class="cart-section">
        <a href="<?= url('carrito/ver') ?>" class="cart-button" aria-label="Ver carrito">
          <svg class="cart-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="32" height="32"
            aria-hidden="true">
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
        <button class="all-categories-button ml-[15px]" id="allCategoriesButton" aria-haspopup="true"
          aria-expanded="false">
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
        $topN = 8;
        $top = !empty($categoriasTree) ? array_slice($categoriasTree, 0, $topN) : array_slice($allCategories, 0, $topN);
        if (empty($top)) {
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
</div>

<script>
  const BASE_URL = "<?= rtrim(url(''), '/') ?>";
  const OPEN_LOGIN_MODAL_ON_LOAD = <?= $openLoginModalOnLoad ? 'true' : 'false' ?>;
</script>

<!-- Modal de login (insertado en header para no navegar a nueva página) -->
<div id="loginModal" class="fixed inset-0 z-[9999] hidden" aria-labelledby="loginModalTitle" role="dialog"
  aria-modal="true">

  <div id="loginModalOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity cursor-pointer">
  </div>

  <div class="fixed inset-0 z-10 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">

      <div id="loginModalPanel"
        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-md border border-gray-100">

        <button type="button" id="loginModalClose"
          class="absolute top-4 right-4 rounded-full bg-gray-100 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-200 focus:outline-none transition-colors z-20">
          <span class="sr-only">Cerrar</span>
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <div class="px-6 py-8 sm:px-8">

          <div class="text-center mb-6">
            <h2 id="loginModalTitle" class="text-2xl font-bold text-gray-900 font-orbitron">BYTEBOX</h2>
            <p id="loginModalDesc" class="mt-2 text-sm text-gray-500">Bienvenido de nuevo</p>
          </div>

          <?php if (!empty($loginErrorMsg)): ?>
            <div class="mb-4 rounded-lg bg-red-50 p-4 border border-red-100 flex items-center gap-3">
              <svg class="h-5 w-5 text-red-500 flex-shrink-0" fill="viewBox" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                  clip-rule="evenodd" />
              </svg>
              <span class="text-sm text-red-700 font-medium"><?= htmlspecialchars($loginErrorMsg) ?></span>
            </div>
          <?php endif; ?>

          <?php
          $loginSocialIncluded = false;
          $pathsToTry = [__DIR__ . '/login_social.php', __DIR__ . '/../auth/login_social.php'];
          foreach ($pathsToTry as $p)
            if (file_exists($p)) {
              include $p;
              $loginSocialIncluded = true;
              break;
            }

          if (!$loginSocialIncluded): ?>
            <div class="space-y-3 mb-6">
              <a href="<?= url('auth/oauth/google') ?>"
                class="flex w-full items-center justify-center gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-all">
                <svg class="h-5 w-5" viewBox="0 0 48 48">
                  <path fill="#EA4335"
                    d="M24 9.5c3.54 0 6.7 1.23 9.2 3.24l6.86-6.86C36.43 3.01 30.55 1 24 1 14.97 1 6.96 6.6 3.06 14.86l7.83 6.09C12.9 16.05 18.85 9.5 24 9.5z" />
                  <path fill="#4285F4"
                    d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
                  <path fill="#FBBC05"
                    d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z" />
                  <path fill="#34A853"
                    d="M24 48c6.48 0 11.95-2.16 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
                </svg>
                Continuar con Google
              </a>
              <a href="<?= url('auth/facebook/login') ?>"
                class="flex w-full items-center justify-center gap-3 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-all">
                <svg class="h-5 w-5 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                </svg>
                Continuar con Facebook
              </a>
            </div>

            <div class="relative mb-6">
              <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-gray-200"></div>
              </div>
              <div class="relative flex justify-center"><span class="bg-white px-2 text-sm text-gray-400">o con tu
                  correo</span></div>
            </div>
          <?php endif; ?>

          <form id="loginModalForm" method="POST" action="<?= url('auth/authenticate') ?>" class="space-y-5">
            <?php if (class_exists('\Core\Helpers\CsrfHelper'))
              echo \Core\Helpers\CsrfHelper::tokenField('login_form'); ?>
            <input type="hidden" name="redirect" value="<?php
            $url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
            if (empty($url)) {
              $currentUri = $_SERVER['REQUEST_URI'] ?? '';
              if (preg_match('#/public/(.*)$#', $currentUri, $matches)) {
                $url = $matches[1] ?? 'home/index';
              } else {
                $url = 'home/index';
              }
            }
            echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            ?>">

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
              <div class="mt-1">
                <input id="email" name="email" type="email" autocomplete="email" required
                  class="block w-full rounded-lg border-gray-300 px-4 py-3 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-gray-50 focus:bg-white transition-colors"
                  placeholder="nombre@ejemplo.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
              </div>
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
              <div class="mt-1">
                <input id="password" name="password" type="password" autocomplete="current-password" required
                  class="block w-full rounded-lg border-gray-300 px-4 py-3 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-gray-50 focus:bg-white transition-colors"
                  placeholder="••••••••">
              </div>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <input id="remember" name="remember" type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="remember" class="ml-2 block text-sm text-gray-600">Recordarme</label>
              </div>
              <div class="text-sm">
                <a href="#" class="font-medium text-gray-900 hover:text-gray-900">¿Olvidaste tu contraseña?</a>
              </div>
            </div>

            <button type="submit"
              class="flex w-full justify-center rounded-lg bg-gray-900 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-all transform active:scale-[0.98]">
              Iniciar Sesión
            </button>
          </form>

          <p class="mt-6 text-center text-sm text-gray-600">
            ¿No tienes cuenta?
            <a href="<?= url('auth/registro?redirect=' . urlencode('carrito/ver')) ?>"
              class="font-semibold text-gray-900 hover:text-blue-500">Regístrate gratis</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Estilos del modal (puedes mover a header.css) -->
<style>
  #loginModal.open {
    display: block !important;
  }


  #loginModal.open #loginModalPanel {
    animation: modalPop 0.3s ease-out forwards;
  }

  @keyframes modalPop {
    from {
      opacity: 0;
      transform: scale(0.95) translateY(10px);
    }

    to {
      opacity: 1;
      transform: scale(1) translateY(0);
    }
  }

  .font-orbitron {
    font-family: 'Orbitron', sans-serif;
  }
</style>

<?php include_once __DIR__ . '/cookies_banner.php'; ?>


<!-- Autocomplete + dropdown profile behavior + modal JS -->
<script>
  document.addEventListener('DOMContentLoaded', function () {

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
      }[s]));
    }

    function hideResults() {
      if (!resultsContainer) return;
      resultsContainer.style.display = 'none';
      resultsContainer.innerHTML = '';
      resultsContainer.setAttribute('aria-expanded', 'false');
    }

    if (input) {
      input.addEventListener('input', function (e) {
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

    /* ---------- Categories dropdown open/close logic (Mobile & Desktop fix) ---------- */
    const allCategoriesButton = document.getElementById('allCategoriesButton');
    const categoriesDropdown = document.getElementById('categoriesDropdown');
    let categoriesCloseTimeout = null;
    // Bandera para detectar si la interacción es táctil
    let isTouchInteraction = false;

    if (allCategoriesButton && categoriesDropdown) {

      // 1. Detectar interacción táctil para desactivar el hover
      allCategoriesButton.addEventListener('touchstart', function () {
        isTouchInteraction = true;
      }, { passive: true });

      // 2. Lógica de Mouse (Escritorio)
      allCategoriesButton.addEventListener('mouseenter', () => {
        if (isTouchInteraction) return; // Ignorar si es táctil
        clearTimeout(categoriesCloseTimeout);
        openCategories();
      });

      allCategoriesButton.addEventListener('mouseleave', () => {
        if (isTouchInteraction) return; // Ignorar si es táctil
        scheduleCloseCategories();
      });

      categoriesDropdown.addEventListener('mouseenter', () => {
        if (isTouchInteraction) return; // Ignorar si es táctil
        clearTimeout(categoriesCloseTimeout);
        openCategories();
      });

      categoriesDropdown.addEventListener('mouseleave', () => {
        if (isTouchInteraction) return; // Ignorar si es táctil
        scheduleCloseCategories();
      });

      // 3. Lógica de Clic (Móvil y Escritorio)
      allCategoriesButton.addEventListener('click', (e) => {
        // En móvil, el clic es el evento principal para abrir/cerrar
        // En escritorio, permite hacer clic para alternar si el usuario lo prefiere
        e.stopPropagation();

        if (categoriesDropdown.classList.contains('open')) {
          closeCategories();
        } else {
          openCategories();
        }
      });

      // 4. Cerrar al hacer clic fuera (Esencial para móvil)
      document.addEventListener('click', (e) => {
        if (!allCategoriesButton.contains(e.target) && !categoriesDropdown.contains(e.target)) {
          closeCategories();
        }
      });

      // --- Funciones auxiliares para no repetir código ---
      function openCategories() {
        clearTimeout(categoriesCloseTimeout);
        categoriesDropdown.classList.add('open');
        allCategoriesButton.setAttribute('aria-expanded', 'true');
        categoriesDropdown.setAttribute('aria-hidden', 'false');
      }

      function closeCategories() {
        categoriesDropdown.classList.remove('open');
        allCategoriesButton.setAttribute('aria-expanded', 'false');
        categoriesDropdown.setAttribute('aria-hidden', 'true');
      }

      function scheduleCloseCategories() {
        categoriesCloseTimeout = setTimeout(closeCategories, 200);
      }
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
      } catch (e) { }
    }

    if (openLoginBtn) {
      openLoginBtn.addEventListener('click', function (e) {
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
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        if (loginModal && loginModal.classList.contains('open')) {
          closeLoginModal();
        }
      }
    });

    // simple validation en cliente para evitar submit vacío
    if (loginForm) {
      loginForm.addEventListener('submit', function (e) {
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