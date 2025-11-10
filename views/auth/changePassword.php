<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>

<body>
    <div class="flex h-screen">
        <div class="fixed inset-y-0 left-0 z-50">
            <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>
        </div>
        <div class="flex-1 ml-64 flex flex-col min-h-screen">
            <main class="flex-1 p-2 bg-gray-50 overflow-y-auto">
                <div class="sticky top-0 z-40">
                    <?php include_once __DIR__ . '/../admin/includes/header.php'; ?>
                </div>
                <div class="flex-1 p-6 bg-gray-50 overflow-y-auto">

                <div class="flex-1 flex flex-col min-h-screen p-6 bg-gray-50">
                        <div class="max-w-lg mx-auto bg-white rounded-xl shadow-md p-8">
                            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">🔑 Cambiar Contraseña</h1>

                            <!-- Mensajes -->
                            <?php if (!empty($_GET['error'])): ?>
                                <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 border border-red-300">
                                    <?= htmlspecialchars($_GET['error']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($_GET['success'])): ?>
                                <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 border border-green-300">
                                    <?= htmlspecialchars($_GET['success']) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Formulario -->
                            <form method="POST" action="<?= url('/auth/updatePassword') ?>" class="space-y-5">
                                <div>
                                    <label for="actual" class="block font-semibold text-gray-700 mb-1">Contraseña actual</label>
                                    <input type="password" name="actual" id="actual" required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label for="nueva" class="block font-semibold text-gray-700 mb-1">Nueva contraseña</label>
                                    <input type="password" name="nueva" id="nueva" required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label for="confirmar" class="block font-semibold text-gray-700 mb-1">Confirmar nueva contraseña</label>
                                    <input type="password" name="confirmar" id="confirmar" required
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="text-right">
                                    <button type="submit"
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg shadow-md transition">
                                         Guardar cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>


                </div>
                <div class="mt-4">
                    <?php include_once __DIR__ . '/../admin/includes/footer.php'; ?>
                </div>
            </main>
        </div>
    </div>

</body>
</html>

