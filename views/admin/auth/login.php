<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$error = $error ?? $_GET['error'] ?? '';

// Generar token CSRF para el formulario
$csrfToken = \Core\Helpers\CsrfHelper::generateToken('login_form');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin - Bytebox</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
.bg-gradient { background: #2ac1db; }
</style>
</head>
<body class="bg-gradient min-h-screen flex items-center justify-center">
<div class="max-w-md w-full space-y-8 p-8">
    <div class="bg-white rounded-lg shadow-2xl p-8">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Bytebox Admin</h2>
            <p class="mt-2 text-sm text-gray-600">Inicia sesión con tu cuenta de administrador</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulario admin tradicional -->
        <form class="mt-8 space-y-6" method="POST" action="<?= url('/admin/authenticate') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input id="email" name="email" type="email" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="admin@bytebox.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                    <input id="password" name="password" type="password" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="••••••••">
                </div>
            </div>

            

            <div>
                <button type="submit"
                    class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition">
                    Iniciar Sesión
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <h1 class="text-gray-500 text-sm">Acceso solo al Personal autorizado</h1>
        </div>
    </div>

    <div class="text-center">
        <p class="text-sm text-white opacity-75">© <?= date('Y') ?> Bytebox. Todos los derechos reservados.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => document.getElementById('email').focus());
document.querySelector('form').addEventListener('submit', e => {
    if (!document.getElementById('email').value || !document.getElementById('password').value) {
        e.preventDefault();
        alert('Completa todos los campos');
    }
});
</script>
</body>
</html>

