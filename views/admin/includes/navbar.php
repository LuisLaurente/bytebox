<?php
// Función helper para verificar permisos
function hasPermission($permission)
{
    return \Core\Helpers\SessionHelper::hasPermission($permission);
}

// Función para verificar si el usuario es un cliente (rol usuario)
function isCliente()
{
    $userRole = \Core\Helpers\SessionHelper::getRole();

    if (is_array($userRole) && isset($userRole['nombre'])) {
        return $userRole['nombre'] === 'usuario';
    }

    if (is_string($userRole)) {
        return $userRole === 'usuario';
    }

    $userPermissions = \Core\Helpers\SessionHelper::getPermissions();
    if (is_array($userPermissions)) {
        return in_array('perfil', $userPermissions) &&
            !in_array('usuarios', $userPermissions) &&
            !in_array('productos', $userPermissions);
    }

    return false;
}

// Obtener información del usuario
$userName = \Core\Helpers\SessionHelper::getUserName();
$userEmail = \Core\Helpers\SessionHelper::getUserEmail();
$userRole = \Core\Helpers\SessionHelper::getRole();
?>
<link rel="stylesheet" href="<?= url('/css/navbar.css') ?>">
<?php if (!isCliente()): ?>
    <!-- Sidebar Navigation -->
    <aside id="sidebar_navbar" class="sidebar_navbar">
        <!-- Header del sidebar con logo y nombre -->
        <div class="sidebar-header_navbar">
            <div class="sidebar-logo_navbar">
                <div class="logo-icon_navbar">
                    <svg class="icon_navbar" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="logo-text_navbar">
                    <h1 class="logo-title_navbar">ByteBox</h1>
                    <p class="logo-subtitle_navbar">Panel de Control</p>
                </div>
            </div>
        </div>

        <!-- Navegación principal - scrolleable -->
        <nav class="sidebar-nav_navbar">

            <!-- Sección Panel Administrativo -->
            <?php
            $tienePermisosAdmin = hasPermission('usuarios') || hasPermission('productos') || hasPermission('categorias') || hasPermission('pedidos');
            if ($tienePermisosAdmin):
            ?>
                <div class="nav-section_navbar">
                    <h3 class="nav-section-title_navbar">Panel Administrativo</h3>
                </div>
            <?php endif; ?>

            <!-- Gestión de Usuarios -->
            <?php if (hasPermission('usuarios')): ?>
                <a href="<?= url('/usuario') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="nav-text_navbar">Usuarios</span>
                </a>
            <?php endif; ?>

            <!-- Roles -->
            <?php if (hasPermission('usuarios')): ?>
                <a href="<?= url('/rol') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <span class="nav-text_navbar">Roles</span>
                </a>
            <?php endif; ?>

            <!-- Productos -->
            <?php if (hasPermission('productos')): ?>
                <a href="<?= url('/producto') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-box"></i>
                    </div>
                    <span class="nav-text_navbar">Productos</span>
                </a>
            <?php endif; ?>

            <!-- Categorías -->
            <?php if (hasPermission('categorias')): ?>
                <a href="<?= url('/categoria') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-tags"></i>
                    </div>
                    <span class="nav-text_navbar">Categorías</span>
                </a>
            <?php endif; ?>


            <!-- Pedidos -->
            <?php if (hasPermission('pedidos')): ?>
                <a href="<?= url('/pedido/listar') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <span class="nav-text_navbar">Pedidos</span>
                </a>
            <?php endif; ?>

            <!-- Cupones -->
            <?php if (hasPermission('cupones')): ?>
                <a href="<?= url('/cupon') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <span class="nav-text_navbar">Cupones</span>
                </a>
            <?php endif; ?>

            <!-- Promociones -->
            <?php if (hasPermission('promociones')): ?>
                <a href="<?= url('/promocion') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <span class="nav-text_navbar">Promociones</span>
                </a>
            <?php endif; ?>

            <!-- Popup Promocional -->
            <?php if (hasPermission('promociones')): ?>
                <a href="<?= url('/adminpopup') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <span class="nav-text_navbar">Popup</span>
                </a>
            <?php endif; ?>

            <!-- Banners -->
            <?php if (hasPermission('promociones') || hasPermission('banners')): ?>
                <a href="<?= url('/banner') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-image"></i>
                    </div>
                    <span class="nav-text_navbar">Banners</span>
                </a>
            <?php endif; ?>

            <!-- Carga Masiva -->
            <?php if (hasPermission('productos')): ?>
                <a href="<?= url('/cargamasiva') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <span class="nav-text_navbar">Carga Masiva</span>
                </a>
            <?php endif; ?>

            <!-- Reportes de Reclamaciones -->
            <?php if (hasPermission('reportes')): ?>
                <a href="<?= url('/adminreclamacion') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-flag"></i>
                    </div>
                    <span class="nav-text_navbar">Reportes de Reclamaciones</span>
                </a>

                <!-- Reseñas -->
                <a href="<?= url('/review') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-star"></i>
                    </div>
                    <span class="nav-text_navbar">Reseñas</span>
                </a>

                <!-- Reportes de Ventas -->
                <a href="<?= url('/reporte/resumen') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <span class="nav-text_navbar">Reportes de Ventas</span>
                </a>
            <?php endif; ?>
        </nav>

        <!-- Sección de información del usuario -->
        <div class="sidebar-footer_navbar">
            <a href="<?= url('/auth/profile') ?>" class="user-info-navbar-link">
                <!-- Usuario con dropdown hacia arriba -->
                <div id="userDropdownWrapper" class="user-dropdown-wrapper">
                    <div id="userDropdownButton" class="user-dropdown-button">
                        <div class="user-avatar_navbar">
                            <?= strtoupper(substr($userName ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="user-details_navbar">
                            <p class="user-name_navbar"><?= htmlspecialchars($userName ?? 'Usuario') ?></p>
                            <p class="user-role_navbar"><?= htmlspecialchars($userRole['nombre'] ?? 'Sin rol') ?></p>
                        </div>
                        <i class="fas fa-chevron-up dropdown-arrow"></i>
                    </div>

                    <!-- Dropdown hacia arriba -->
                    <div id="userDropdownMenu" class="user-dropdown-menu">
                        <a href="<?= url('/auth/profile') ?>" class="dropdown-link">Mi Cuenta</a>
                        <div class="dropdown-divider"></div>
                        <a href="<?= url('/auth/logout') ?>" 
                        class="dropdown-link logout-link" 
                        onclick="event.preventDefault(); 
                                    fetch('<?= url('/auth/logout') ?>')
                                    .then(() => {
                                        window.location.href='<?= url('/admin/login') ?>';
                                    });">
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
            </a>

            <!-- Debug de permisos (solo en desarrollo) -->
            <?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
                <div class="debug-info_navbar">
                    <p class="debug-title_navbar">🔍 Debug Permisos</p>
                    <p><strong>Permisos:</strong></p>
                    <ul class="debug-list_navbar">
                        <?php foreach (\Core\Helpers\SessionHelper::getPermissions() as $permission): ?>
                            <li><?= htmlspecialchars($permission) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Overlay para móviles -->
    <div id="sidebar-overlay_navbar" class="sidebar-overlay_navbar"></div>

    <!-- Botón hamburguesa para móviles -->
    <button id="mobile-menu-button_navbar" class="mobile-menu-button_navbar">
        <i class="fas fa-bars"></i>
    </button>
<?php endif; ?>

<!-- Script para funcionalidad móvil -->
<script>
    // Funcionalidad del menú móvil
    const mobileMenuButton_navbar = document.getElementById('mobile-menu-button_navbar');
    const sidebar_navbar = document.getElementById('sidebar_navbar');
    const overlay_navbar = document.getElementById('sidebar-overlay_navbar');

    mobileMenuButton_navbar?.addEventListener('click', function() {
        sidebar_navbar?.classList.toggle('sidebar-open_navbar');
        overlay_navbar?.classList.toggle('overlay-open_navbar');
    });

    overlay_navbar?.addEventListener('click', function() {
        sidebar_navbar?.classList.remove('sidebar-open_navbar');
        overlay_navbar?.classList.remove('overlay-open_navbar');
    });

    // Funcionalidad del dropdown del usuario
    const wrapper = document.getElementById('userDropdownWrapper');
    const dropdown = document.getElementById('userDropdownMenu');
    let hideTimeout;

    if (wrapper && dropdown) {
        wrapper.addEventListener('mouseenter', () => {
            clearTimeout(hideTimeout);
            dropdown.classList.remove('invisible', 'opacity-0');
            dropdown.classList.add('opacity-100');
        });

        wrapper.addEventListener('mouseleave', () => {
            hideTimeout = setTimeout(() => {
                dropdown.classList.add('opacity-0');
                dropdown.classList.remove('opacity-100');
                // Espera 300ms (duración de transición) antes de ocultar completamente
                setTimeout(() => dropdown.classList.add('invisible'), 300);
            }, 200);
        });
    }
</script>