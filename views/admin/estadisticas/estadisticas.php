<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../includes/head.php'; ?>

<body>
    <div class="flex h-screen">
        <!-- Incluir navegación lateral fija -->
        <div class="fixed inset-y-0 left-0 z-50">
            <?php include_once __DIR__ . '/../includes/navbar.php'; ?>
        </div>
        <div class="flex-1 ml-64 flex flex-col min-h-screen">
            <!-- Incluir header superior fijo -->
            <div class="sticky top-0 z-40">
                <?php include_once __DIR__ . '/../includes/header.php'; ?>
            </div>

            <div class="flex-1 p-2 bg-gray-50 overflow-y-auto">
                <div class="max-w-4xl mx-auto p-4 bg-white shadow-md rounded-lg">
                    <h1 class="text-2xl font-bold mb-4">📊 Estadísticas del Sitio</h1>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-blue-100 p-4 rounded-lg shadow-md">
                            <h2 class="font-semibold">Total de Visitas</h2>
                            <p class="text-2xl font-bold">1,234</p>
                        </div>
                        <div class="bg-green-100 p-4 rounded-lg shadow-md">
                            <h2 class="font-semibold">Usuarios Registrados</h2>
                            <p class="text-2xl font-bold">567</p>
                        </div>
                        <div class="bg-yellow-100 p-4 rounded-lg shadow-md">
                            <h2 class="font-semibold">Ventas Realizadas</h2>
                            <p class="text-2xl font-bold">89</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <?php include_once __DIR__ . '/../includes/footer.php'; ?>
            </div>
        </div>
    </div>
</body>