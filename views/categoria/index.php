<?php
$categoriaNombre = isset($categoria['nombre']) ? htmlspecialchars($categoria['nombre']) : "Categoría";
$metaTitle = "Productos en la categoría {$categoriaNombre} | Tienda Tecnovedades";
$metaDescription = "Encuentra los mejores productos en la categoría {$categoriaNombre} a precios increíbles.";
?>

<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('/css/categoriaIndex.css') ?>">

<body>
    <div class="categoria-index-layout">
        <?php include_once __DIR__ . '/../admin/includes/navbar.php'; ?>

        <div class="categoria-index-main">
            <main class="categoria-index-content">
                <div class="categoria-index-container">
                    <!-- Header de la página -->
                    <div class="categoria-index-header">
                        <div class="categoria-index-header-content">
                            <div class="categoria-index-header-text">
                                <h1 class="categoria-index-title">📂 Gestión de Categorías</h1>
                                <p class="categoria-index-subtitle">Organiza y administra las categorías de productos</p>
                            </div>
                            <a href="<?= url('categoria/crear') ?>" class="categoria-index-new-btn">
                                <span class="categoria-index-btn-icon">+</span>
                                Nueva Categoría
                            </a>
                        </div>
                    </div>

                    <!-- Contenido principal -->
                    <div class="categoria-index-card">
                        <?php if (!empty($categorias)): ?>

                            <?php
                            if (!function_exists('mostrarCategorias')) {
                                function mostrarCategorias(array $categorias, $padre = null, $nivel = 0)
                                {
                                    foreach ($categorias as $categoria) {
                                        $catPadre = $categoria['id_padre'] ?? null;
                                        if ((string)$catPadre == (string)$padre) {

                                            $margin = $nivel * 20;
                                            $safeName = htmlspecialchars($categoria['nombre']);
                                            $imgFile = !empty($categoria['imagen']) ? $categoria['imagen'] : null;
                                            $imgUrl = $imgFile ? url('uploads/categorias/' . $imgFile) : url('uploads/default-category.png');

                                            $tieneHijos = !empty($categoria['tiene_hijos']);
                                            $tieneProductos = !empty($categoria['tiene_productos']);

                                            $puedeEliminar = !$tieneHijos && !$tieneProductos;
                                            $puedeEditar = true;
                            ?>
                                            <div class="categoria-item" style="margin-left: <?= (int)$margin ?>px;">
                                                <div class="categoria-content">
                                                    <div class="categoria-info">
                                                        <div class="categoria-image">
                                                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= $safeName ?>">
                                                        </div>
                                                        <div class="categoria-details">
                                                            <h3 class="categoria-name"><?= $safeName ?></h3>
                                                            <?php if ($tieneHijos): ?>
                                                                <div class="categoria-badge categoria-badge-children">📁 Tiene subcategorías</div>
                                                            <?php elseif ($tieneProductos): ?>
                                                                <div class="categoria-badge categoria-badge-products"> Contiene productos</div>
                                                            <?php else: ?>
                                                                <div class="categoria-badge categoria-badge-empty">—</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <div class="categoria-actions">
                                                        <?php if ($puedeEditar): ?>
                                                            <?php $urlEditar = url("categoria/editar/{$categoria['id']}"); ?>
                                                            <a href="<?= $urlEditar ?>" class="categoria-btn categoria-edit-btn">
                                                                <span class="categoria-btn-icon"></span>
                                                                Editar
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if ($puedeEliminar): ?>
                                                            <?php $urlEliminar = url("categoria/eliminar/{$categoria['id']}"); ?>
                                                            <a href="<?= $urlEliminar ?>" class="categoria-btn categoria-delete-btn" onclick="return confirm('¿Estás seguro de eliminar la categoría <?= addslashes($safeName) ?>?')">
                                                                <span class="categoria-btn-icon">🗑️</span>
                                                                Eliminar
                                                            </a>
                                                        <?php else: ?>
                                                            <button type="button" class="categoria-btn categoria-disabled-btn" disabled title="No se puede eliminar mientras tenga subcategorías o productos">
                                                                <span class="categoria-btn-icon">🗑️</span>
                                                                Eliminar
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                            <?php
                                            mostrarCategorias($categorias, $categoria['id'], $nivel + 1);
                                        }
                                    }
                                }
                            }
                            ?>

                            <div class="categorias-list">
                                <?php mostrarCategorias($categorias); ?>
                            </div>

                        <?php else: ?>
                            <div class="categoria-empty-state">
                                <div class="categoria-empty-icon">📂</div>
                                <h3 class="categoria-empty-title">No hay categorías registradas</h3>
                                <p class="categoria-empty-description">Comienza creando tu primera categoría</p>
                                <a href="<?= url('categoria/crear') ?>" class="categoria-index-new-btn categoria-empty-btn">
                                    Crear Categoría
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>