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

// Obtener información del usuario
$userName = \Core\Helpers\SessionHelper::getUserName();
$userEmail = \Core\Helpers\SessionHelper::getUserEmail();
$userRole = \Core\Helpers\SessionHelper::getRole();

// Verificar si tiene permisos administrativos
$tienePermisosAdmin = hasPermission('usuarios') || hasPermission('productos') || hasPermission('categorias') || hasPermission('pedidos');
?>

<!-- Navbar Horizontal Administrativo -->
<nav class="admin-navbar">
    <!-- Logo y título -->
    <div class="navbar-brand">
        <div class="brand-logo">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
        </div>
        <div class="brand-text">
            <h1 class="brand-title">ByteBox</h1>
            <p class="brand-subtitle">Panel de Control</p>
        </div>
    </div>

    <!-- Menú principal -->
    <div class="navbar-menu" id="navbarMenu">
        <!-- Mi Perfil (siempre visible para usuarios autenticados) -->
        <a href="<?= url('/auth/profile') ?>" class="nav-item">
            <div class="nav-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <span class="nav-text">Mi Cuenta</span>
        </a>

        <!-- Gestión de Usuarios (solo admin) -->
        <?php if (hasPermission('usuarios')): ?>
            <a href="<?= url('/usuario') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.239"></path>
                    </svg>
                </div>
                <span class="nav-text">Usuarios</span>
            </a>
        <?php endif; ?>

        <!-- Roles -->
        <?php if (hasPermission('usuarios')): ?>
            <a href="<?= url('/rol') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <span class="nav-text">Roles</span>
            </a>
        <?php endif; ?>

        <!-- Productos -->
        <?php if (hasPermission('productos')): ?>
            <a href="<?= url('/producto') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <span class="nav-text">Productos</span>
            </a>
        <?php endif; ?>

        <!-- Categorías -->
        <?php if (hasPermission('categorias')): ?>
            <a href="<?= url('/categoria') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <span class="nav-text">Categorías</span>
            </a>
        <?php endif; ?>

        <!-- Etiquetas -->
        <?php if (hasPermission('productos')): ?>
            <a href="<?= url('/etiqueta') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <span class="nav-text">Etiquetas</span>
            </a>
        <?php endif; ?>

        <!-- Pedidos -->
        <?php if (hasPermission('pedidos')): ?>
            <a href="<?= url('/pedido/listar') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <span class="nav-text">Pedidos</span>
            </a>
        <?php endif; ?>

        <!-- Cupones -->
        <?php if (hasPermission('cupones')): ?>
            <a href="<?= url('/cupon') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <span class="nav-text">Cupones</span>
            </a>
        <?php endif; ?>

        <!-- Promociones -->
        <?php if (hasPermission('promociones')): ?>
            <a href="<?= url('/promocion') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <span class="nav-text">Promociones</span>
            </a>
        <?php endif; ?>

        <!-- Popup Promocional -->
        <?php if (hasPermission('promociones')): ?>
            <a href="<?= url('/adminpopup') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 011 1v1a1 1 0 01-1 1v9a3 3 0 01-3 3H6a3 3 0 01-3-3V7a1 1 0 01-1-1V5a1 1 0 011-1h4z"></path>
                    </svg>
                </div>
                <span class="nav-text">Popup</span>
            </a>
        <?php endif; ?>

        <!-- Banners -->
        <?php if (hasPermission('promociones') || hasPermission('banners')): ?>
            <a href="<?= url('/banner') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M3 7l6 6 4-4 8 8"></path>
                    </svg>
                </div>
                <span class="nav-text">Banners</span>
            </a>
        <?php endif; ?>

        <!-- Carga Masiva -->
        <?php if (hasPermission('productos')): ?>
            <a href="<?= url('/cargamasiva') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <span class="nav-text">Carga Masiva</span>
            </a>
        <?php endif; ?>

        <!-- Reportes de Reclamaciones -->
        <?php if (hasPermission('reportes')): ?>
            <a href="<?= url('/adminreclamacion') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <span class="nav-text">Reportes Reclamaciones</span>
            </a>
        <?php endif; ?>

        <!-- Reseñas -->
        <?php if (hasPermission('reportes')): ?> 
            <a href="<?= url('/review') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.178c.969 0 1.371 1.24.588 1.81l-3.382 2.46a1 1 0 00-.364 1.118l1.287 3.966c.3.922-.755 1.688-1.54 1.118l-3.382-2.46a1 1 0 00-1.176 0l-3.382 2.46c-.785.57-1.84-.196-1.54-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.045 9.394c-.783-.57-.38-1.81.588-1.81h4.178a1 1 0 00.95-.69l1.288-3.967z"/>
                    </svg>
                </div>
                <span class="nav-text">Reseñas</span>
            </a>
        <?php endif; ?>

        <!-- Reportes de Ventas -->
        <?php if (hasPermission('reportes')): ?>
            <a href="<?= url('/reporte/resumen') ?>" class="nav-item">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 11V9a4 4 0 118 0v2m-4 4h.01M5 13h4m0 0V9m0 4v4m6-4h4"></path>
                    </svg>
                </div>
                <span class="nav-text">Reportes Ventas</span>
            </a>
        <?php endif; ?>

        <!-- Listado de Productos (para clientes) -->
        <a href="<?= url('/home/index') ?>" class="nav-item">
            <div class="nav-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </div>
            <span class="nav-text">Listado Productos</span>
        </a>
    </div>

    <!-- Información de usuario y menú móvil -->
    <div class="navbar-user">
        <div class="user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($userName ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-details">
                <p class="user-name"><?= htmlspecialchars($userName ?? 'Usuario') ?></p>
                <p class="user-role"><?= htmlspecialchars($userRole['nombre'] ?? 'Sin rol') ?></p>
            </div>
        </div>
        
        <!-- Botón hamburguesa para móviles -->
        <button id="mobileMenuButton" class="mobile-menu-button">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>
</nav>

<!-- Overlay para móviles (cuando el menú esté abierto) -->
<div id="navbarOverlay" class="navbar-overlay"></div>

<!-- Estilos para el navbar horizontal -->
<style>
.admin-navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 0.75rem 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    position: relative;
    z-index: 40;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.brand-logo {
    width: 2.5rem;
    height: 2.5rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}

.brand-text {
    color: white;
}

.brand-title {
    font-size: 1.25rem;
    font-weight: bold;
    line-height: 1.2;
}

.brand-subtitle {
    font-size: 0.75rem;
    opacity: 0.8;
}

.navbar-menu {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.navbar-menu::-webkit-scrollbar {
    display: none;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    color: white;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
    backdrop-filter: blur(10px);
    border: 1px solid transparent;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

.nav-icon {
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-text {
    font-size: 0.875rem;
    font-weight: 500;
}

.navbar-user {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
}

.user-avatar {
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.875rem;
}

.user-details {
    display: none;
}

.user-name {
    font-weight: 500;
    font-size: 0.875rem;
}

.user-role {
    font-size: 0.75rem;
    opacity: 0.8;
}

.mobile-menu-button {
    display: none;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 0.375rem;
    padding: 0.5rem;
    color: white;
    cursor: pointer;
    backdrop-filter: blur(10px);
}

.navbar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 30;
}

/* Responsive para tablets */
@media (max-width: 1024px) {
    .navbar-menu {
        gap: 0.25rem;
    }
    
    .nav-item {
        padding: 0.4rem 0.6rem;
    }
    
    .nav-text {
        font-size: 0.8rem;
    }
}

/* Responsive para móviles */
@media (max-width: 768px) {
    .admin-navbar {
        padding: 0.5rem 1rem;
        flex-wrap: wrap;
    }
    
    .navbar-menu {
        position: fixed;
        top: 0;
        left: -100%;
        width: 80%;
        max-width: 300px;
        height: 100vh;
        background: white;
        flex-direction: column;
        align-items: flex-start;
        padding: 1rem;
        gap: 0.5rem;
        transition: left 0.3s ease;
        z-index: 50;
        overflow-y: auto;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    .navbar-menu.active {
        left: 0;
    }
    
    .nav-item {
        width: 100%;
        background: #f8fafc;
        color: #374151;
        justify-content: flex-start;
        border-radius: 0.375rem;
        padding: 0.75rem 1rem;
    }
    
    .nav-item:hover {
        background: #e5e7eb;
        border-color: #d1d5db;
    }
    
    .user-details {
        display: block;
    }
    
    .mobile-menu-button {
        display: block;
    }
    
    .navbar-overlay.active {
        display: block;
    }
    
    .brand-text {
        display: none;
    }
}

/* Responsive para móviles pequeños */
@media (max-width: 480px) {
    .navbar-user {
        gap: 0.5rem;
    }
    
    .user-info {
        display: none;
    }
}
</style>

<!-- Script para funcionalidad móvil -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const navbarMenu = document.getElementById('navbarMenu');
    const navbarOverlay = document.getElementById('navbarOverlay');
    
    if (mobileMenuButton && navbarMenu && navbarOverlay) {
        mobileMenuButton.addEventListener('click', function() {
            navbarMenu.classList.toggle('active');
            navbarOverlay.classList.toggle('active');
        });
        
        navbarOverlay.addEventListener('click', function() {
            navbarMenu.classList.remove('active');
            navbarOverlay.classList.remove('active');
        });
        
        // Cerrar menú al hacer clic en un enlace (solo móviles)
        const navItems = navbarMenu.querySelectorAll('.nav-item');
        navItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    navbarMenu.classList.remove('active');
                    navbarOverlay.classList.remove('active');
                }
            });
        });
    }
});
</script>