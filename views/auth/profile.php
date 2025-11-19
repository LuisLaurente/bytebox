<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/profile.css') ?>">

<body>
<?php
// Mostrar header y footer SOLO para usuarios regulares
$isRegularUser = isset($usuario['rol_nombre']) && $usuario['rol_nombre'] === 'usuario';
?>

<?php if ($isRegularUser): ?>
    <!-- Solo mostrar header si ES usuario regular -->
    <?php include_once __DIR__ . '/../admin/includes/header.php'; ?>
<?php endif; ?>

<div class="main-wrapper">
    <!-- Barra lateral -->
    <div class="sidebar-fixed">
        <?php include __DIR__ . '/../admin/includes/navbar.php'; ?>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <div class="profile-container">
            <h1><?= $isRegularUser ? 'Mi Cuenta' : 'Administración' ?></h1>

            <!-- Mensajes -->
            <?php if (!empty($_GET['success'])): ?>
                <div class="message success-message">
                    <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['error'])): ?>
                <div class="message error-message">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <!-- Información Personal -->
            <div class="profile-card">
                <h3>Información Personal</h3>
                <form method="POST" action="<?= url('/auth/updateProfile') ?>">
                    <div class="form-group">
                        <label for="nombre">Nombre completo</label>
                        <input type="text"
                               name="nombre"
                               id="nombre"
                               value="<?= htmlspecialchars($usuario['nombre']) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email"
                               name="email"
                               id="email"
                               value="<?= htmlspecialchars($usuario['email']) ?>"
                               required readonly>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="button primary-button">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>

            <!-- Seguridad -->
            <?php
            $isSocial = false;
            if (isset($usuario['password']) && strlen($usuario['password']) >= 50) {
                $isSocial = true;
            }
            ?>

            <div class="profile-card">
                <div class="password-card">
                    <h3>Seguridad</h3>

                    <?php if (!$isSocial): ?>
                        <a href="<?= url('/auth/changePassword') ?>" class="button orders-button">
                            Cambiar contraseña
                        </a>
                    <?php else: ?>
                        <div class="button-disabled text-center text-gray-500 font-semibold cursor-not-allowed">
                            Cambiar contraseña (No disponible cuando se usa inicio de sesión social)
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isRegularUser): ?>
                <!-- Mis Pedidos - Solo para usuarios regulares -->
                <div class="orders-card">
                    <h3>Mis Pedidos</h3>
                    <a href="<?= url('/usuario/pedidos') ?>" class="button orders-button">
                        Ver mis pedidos
                    </a>
                </div>

                <!-- Mis Direcciones - Solo para usuarios regulares -->
                <div class="orders-card">
                    <h3>Mis Direcciones</h3>
                    <a href="<?= url('/usuario/mis-direcciones') ?>" class="button orders-button">
                        Gestionar direcciones
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isRegularUser): ?>
    <!-- Solo mostrar footer si ES usuario regular -->
    <?php include_once __DIR__ . '/../admin/includes/footer.php'; ?>
<?php endif; ?>
</body>
</html>