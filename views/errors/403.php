<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 403 - Acceso Denegado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .error-animation {
            animation: shake 0.82s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white shadow-xl rounded-lg overflow-hidden">
        <div class="bg-red-600 p-6 text-center">
            <div class="text-7xl mb-2 error-animation">🚫</div>
            <h1 class="text-3xl font-bold text-white mb-2">Acceso Denegado</h1>
            <p class="text-white/80">No tienes permisos para acceder a este recurso</p>
        </div>
        
        <div class="p-6">
            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-amber-800">
                            Este error ocurre porque tu cuenta no tiene los permisos necesarios para acceder a esta sección.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="space-y-3">
                <a href="<?= url('/auth/profile') ?>" 
                   class="block w-full bg-blue-600 text-white text-center py-3 px-4 rounded hover:bg-blue-700 transition duration-300">
                    🏠 Ir al Panel Principal
                </a>
                
                <button onclick="history.back()" 
                        class="block w-full bg-gray-200 text-gray-800 text-center py-3 px-4 rounded hover:bg-gray-300 transition duration-300">
                    ← Volver Atrás
                </button>
                
                <a href="<?= url('/') ?>" 
                   class="block w-full bg-gray-800 text-white text-center py-3 px-4 rounded hover:bg-gray-900 transition duration-300">
                    🏪 Ir a la Tienda
                </a>
                
                <a href="<?= url('/auth/logout') ?>" 
                   class="block w-full bg-red-600 text-white text-center py-3 px-4 rounded hover:bg-red-700 transition duration-300">
                    🚪 Cerrar Sesión
                </a>
            </div>
            
            <?php if (\Core\Helpers\SessionHelper::isAuthenticated()): ?>
            <div class="mt-6 pt-4 border-t border-gray-200 text-sm">
                <p class="text-gray-500">
                    Sesión activa como: <strong><?= htmlspecialchars(\Core\Helpers\SessionHelper::getUser()['nombre'] ?? 'Usuario') ?></strong>
                </p>
                <p class="text-gray-500">
                    Rol: <strong><?= htmlspecialchars(\Core\Helpers\SessionHelper::getRole()['nombre'] ?? 'Desconocido') ?></strong>
                </p>
                
                <?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
                <div class="mt-2 p-3 bg-gray-50 rounded text-left text-xs">
                    <h3 class="font-semibold mb-1">Debug Info:</h3>
                    <p><strong>Usuario ID:</strong> <?= \Core\Helpers\SessionHelper::getUserId() ?></p>
                    <p><strong>Rol ID:</strong> <?= \Core\Helpers\SessionHelper::getRole()['id'] ?? 'N/A' ?></p>
                    <p><strong>Permisos:</strong> <?= implode(', ', \Core\Helpers\SessionHelper::getPermissions()) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
    </div>
</body>
</html>
