<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Bytebox</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-gradient {
            background: #2ac1db;
        }
    </style>
</head>
<body class="bg-gradient min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <!-- Header -->
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    Bytebox
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Inicia sesión en tu cuenta
                </p>
            </div>

            <!-- Mensajes de error -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Botones de login social -->
            <?php require_once __DIR__ . '/login_social.php'; ?>

            <!-- Formulario de login -->
            <form class="mt-8 space-y-6" method="POST" action="<?= url('/auth/authenticate') ?>">
                <!-- Token CSRF para seguridad -->
                <?= \Core\Helpers\CsrfHelper::tokenField('login_form') ?>
                
                <!-- Campo oculto para redirección -->
                <?php if (isset($redirect) && !empty($redirect)): ?>
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                <?php endif; ?>
                
                <div class="space-y-4">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Correo Electrónico
                        </label>
                        <input id="email" 
                               name="email" 
                               type="email" 
                               autocomplete="email" 
                               required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="tu@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Contraseña
                        </label>
                        <input id="password" 
                               name="password" 
                               type="password" 
                               autocomplete="current-password" 
                               required 
                               class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="••••••••">
                    </div>
                </div>

                <!-- Remember me -->
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

    <script>
        // Enfocar el primer campo al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });

        // Validación del lado del cliente
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Por favor, completa todos los campos');
                return;
            }

            if (!email.includes('@')) {
                e.preventDefault();
                alert('Por favor, ingresa un email válido');
                return;
            }
        });
    </script>
</body>
</html>
