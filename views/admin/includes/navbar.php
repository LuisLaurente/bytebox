<?php
// views/admin/includes/navbar.php

// Función helper para verificar permisos
function hasPermission($permission)
{
    return \Core\Helpers\SessionHelper::hasPermission($permission);
}

// Función para verificar si el usuario es un cliente (rol usuario)
function isCliente()
{
    // Optimización: Cachea el resultado para evitar múltiples accesos a la sesión.
    static $isClient = null;

    if ($isClient !== null) {
        return $isClient;
    }

    $userRole = \Core\Helpers\SessionHelper::getRole();

    if (is_array($userRole) && isset($userRole['nombre'])) {
        $isClient = $userRole['nombre'] === 'usuario';
    } elseif (is_string($userRole)) {
        $isClient = $userRole === 'usuario';
    } else {
        $userPermissions = \Core\Helpers\SessionHelper::getPermissions();
        if (is_array($userPermissions)) {
            $isClient = in_array('perfil', $userPermissions) &&
                        !in_array('usuarios', $userPermissions) &&
                        !in_array('productos', $userPermissions);
        } else {
            $isClient = false;
        }
    }
    
    return $isClient;
}

// Obtener información del usuario y sanitizar (Prevención XSS)
$userName = \Core\Helpers\SessionHelper::getUserName();
// FIX DE SEGURIDAD: Uso de ENT_QUOTES para manejar comillas simples y dobles.
$safeUserName = htmlspecialchars($userName ?? 'Usuario', ENT_QUOTES, 'UTF-8');

$userEmail = \Core\Helpers\SessionHelper::getUserEmail();

$userRole = \Core\Helpers\SessionHelper::getRole();
$userRoleName = is_array($userRole) && isset($userRole['nombre']) ? $userRole['nombre'] : ($userRole ?? 'Sin rol');
$safeUserRoleName = htmlspecialchars($userRoleName, ENT_QUOTES, 'UTF-8');

// FIX del error CsrfHelper::getToken(): Usamos el método existente tokenField() 
// para obtener el campo de formulario completo con el token generado y seguro.
$csrfTokenField = class_exists('\Core\Helpers\CsrfHelper') 
    ? \Core\Helpers\CsrfHelper::tokenField('logout_form') // Usamos un nombre de formulario específico
    : '<input type="hidden" name="csrf_token" value="SAFE_FALLBACK_TOKEN">'; // Fallback
?>
<link rel="stylesheet" href="<?= url('/css/navbar.css') ?>">
<?php if (!isCliente()): ?>
    <aside id="sidebar_navbar" class="sidebar_navbar">
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

        <nav class="sidebar-nav_navbar">

            <?php
            $tienePermisosAdmin = hasPermission('usuarios') || hasPermission('productos') || hasPermission('categorias') || hasPermission('pedidos');
            if ($tienePermisosAdmin):
            ?>
                <div class="nav-section_navbar">
                    <h3 class="nav-section-title_navbar">Panel Administrativo</h3>
                </div>
            <?php endif; ?>

            <?php if (hasPermission('usuarios')): ?>
                <a href="<?= url('/usuario') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="nav-text_navbar">Usuarios</span>
                </a>
            <?php endif; ?>

            <?php if (hasPermission('usuarios')): ?>
                <a href="<?= url('/rol') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <span class="nav-text_navbar">Roles</span>
                </a>
            <?php endif; ?>

            <?php if (hasPermission('productos')): ?>
                <a href="<?= url('/producto') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-box"></i>
                    </div>
                    <span class="nav-text_navbar">Productos</span>
                </a>
            <?php endif; ?>

            <?php if (hasPermission('categorias')): ?>
                <a href="<?= url('/categoria') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-tags"></i>
                    </div>
                    <span class="nav-text_navbar">Categorías</span>
                </a>
            <?php endif; ?>


            <?php if (hasPermission('pedidos')): ?>
                <a href="<?= url('/pedido/listar') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <span class="nav-text_navbar">Pedidos</span>
                </a>
            <?php endif; ?>

            <?php if (hasPermission('cupones')): ?>
                <a href="<?= url('/cupon') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <span class="nav-text_navbar">Cupones</span>
                </a>
            <?php endif; ?>

            <?php if (hasPermission('promociones')): ?>
                <a href="<?= url('/promocion') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <span class="nav-text_navbar">Promociones</span>
                </a>
            <?php endif; ?>

            <?php if (hasPermission('promociones')): ?>
                <a href="<?= url('/adminpopup') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <span class="nav-text_navbar">Popup</span>
                </a>
            <?php endif; ?>

            <?php if (hasPermission('banners')): ?>
                <a href="<?= url('/banner') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-image"></i>
                    </div>
                    <span class="nav-text_navbar">Banners</span>
                </a>
            <?php endif; ?>

            <?php if (hasPermission('productos')): ?>
                <a href="<?= url('/cargamasiva') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <span class="nav-text_navbar">Carga Masiva</span>
                </a>
            <?php endif; ?>

            <?php if (hasPermission('reportes')): ?>
                <a href="<?= url('/adminreclamacion') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-flag"></i>
                    </div>
                    <span class="nav-text_navbar">Reportes de Reclamaciones</span>
                </a>

                <a href="<?= url('/review') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-star"></i>
                    </div>
                    <span class="nav-text_navbar">Reseñas</span>
                </a>

                <a href="<?= url('/reporte/resumen') ?>" class="nav-link_navbar">
                    <div class="nav-icon-wrapper_navbar">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <span class="nav-text_navbar">Reportes de Ventas</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer_navbar">
            <a href="<?= url('/auth/profile') ?>" class="user-info-navbar-link">
                <div id="userDropdownWrapper" class="user-dropdown-wrapper">
                    <div id="userDropdownButton" class="user-dropdown-button">
                        <div class="user-avatar_navbar">
                            <?= strtoupper(substr($safeUserName, 0, 1)) ?>
                        </div>
                        <div class="user-details_navbar">
                            <p class="user-name_navbar"><?= $safeUserName ?></p>
                            <p class="user-role_navbar"><?= $safeUserRoleName ?></p>
                        </div>
                        <i class="fas fa-chevron-up dropdown-arrow"></i>
                    </div>

                    <div id="userDropdownMenu" class="user-dropdown-menu">
                        <a href="<?= url('/auth/profile') ?>" class="dropdown-link">Mi Cuenta</a>
                        <div class="dropdown-divider"></div>
                        
                        <form id="logout-form" action="<?= url('/auth/logout') ?>" method="POST" style="display: none;">
                            <?= $csrfTokenField ?>
                        </form>
                        <a href="#" class="dropdown-link logout-link" 
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
            </a>

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

    <div id="sidebar-overlay_navbar" class="sidebar-overlay_navbar"></div>

    <button id="mobile-menu-button_navbar" class="mobile-menu-button_navbar">
        <i class="fas fa-bars"></i>
    </button>
<?php endif; ?>

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