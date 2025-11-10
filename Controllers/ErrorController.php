<?php
namespace Controllers;

class ErrorController extends BaseController {
    
    public function notFound() {
        http_response_code(404);
        $this->renderErrorPage(
            'Error 404 - Página no encontrada',
            'La página que buscas no existe o ha sido movida.',
            '🔍'
        );
    }

    public function forbidden() {
        http_response_code(403);
        $this->renderErrorPage(
            'Error 403 - Acceso denegado',
            'No tienes permisos para acceder a esta página.',
            '🚫'
        );
    }

    public function serverError() {
        http_response_code(500);
        $this->renderErrorPage(
            'Error 500 - Error del servidor',
            'Ha ocurrido un error interno del servidor.',
            '⚠️'
        );
    }

    private function renderErrorPage($title, $message, $icon) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?></title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-100 min-h-screen flex items-center justify-center">
            <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-6 text-center">
                <div class="text-6xl mb-4"><?= $icon ?></div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($title) ?></h1>
                <p class="text-gray-600 mb-6"><?= htmlspecialchars($message) ?></p>
                
                <div class="space-y-3">
                    <?php if (\Core\Helpers\SessionHelper::isAuthenticated()): ?>
                        <a href="<?= url('/auth/profile') ?>" 
                           class="block w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition duration-300">
                            🏠 Ir al Panel Principal
                        </a>
                        <a href="<?= url('/auth/logout') ?>" 
                           class="block w-full bg-gray-600 text-white py-2 px-4 rounded hover:bg-gray-700 transition duration-300">
                            🚪 Cerrar Sesión
                        </a>
                    <?php else: ?>
                        <a href="<?= url('/auth/login') ?>" 
                           class="block w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition duration-300">
                            🔑 Iniciar Sesión
                        </a>
                    <?php endif; ?>
                    
                    <button onclick="history.back()" 
                            class="block w-full bg-gray-300 text-gray-700 py-2 px-4 rounded hover:bg-gray-400 transition duration-300">
                        ← Volver Atrás
                    </button>
                </div>
                
                <?php if (\Core\Helpers\SessionHelper::isAuthenticated()): ?>
                    <div class="mt-6 pt-4 border-t border-gray-200 text-sm text-gray-500">
                        Sesión activa como: <strong><?= htmlspecialchars(\Core\Helpers\SessionHelper::getUser()['nombre'] ?? 'Usuario') ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
    }
}
