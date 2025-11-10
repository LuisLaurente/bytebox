<!DOCTYPE html>
<html lang="es">
<?php include_once __DIR__ . '/../admin/includes/head.php'; ?>
<link rel="stylesheet" href="<?= url('css/profile.css') ?>">
<style>
    .direccion-card {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        position: relative;
    }

    .direccion-card:hover {
        border-color: var(--primary-color, #007bff);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .direccion-card.principal {
        border-color: var(--success-color, #28a745);
        border-width: 2px;
    }

    .badge-principal {
        position: absolute;
        top: 15px;
        right: 15px;
        background: var(--success-color, #28a745);
        color: white;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .direccion-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .direccion-nombre {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
    }

    .direccion-tipo {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 0.8rem;
        margin-left: 10px;
        font-weight: 500;
    }

    .tipo-casa {
        background: #e8f4f8;
        color: #0066cc;
    }

    .tipo-trabajo {
        background: #fff4e5;
        color: #cc6600;
    }

    .tipo-otro {
        background: #f5f5f5;
        color: #666;
    }

    .direccion-info {
        color: #555;
        line-height: 1.8;
        margin-bottom: 15px;
    }

    .direccion-info p {
        margin: 5px 0;
    }

    .direccion-info strong {
        color: #333;
    }

    .direccion-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }

    .btn-editar, .btn-eliminar {
        flex: 1;
        padding: 10px 20px;
        border: 1px solid;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s ease;
        text-decoration: none;
        text-align: center;
        display: inline-block;
    }

    .btn-editar {
        background: var(--primary-color, #007bff);
        border-color: var(--primary-color, #007bff);
        color: white;
    }

    .btn-editar:hover {
        background: var(--primary-dark, #0056b3);
        border-color: var(--primary-dark, #0056b3);
    }

    .btn-eliminar {
        background: white;
        border-color: #dc3545;
        color: #dc3545;
    }

    .btn-eliminar:hover {
        background: #dc3545;
        color: white;
    }

    .no-direcciones {
        text-align: center;
        padding: 40px 20px;
        background: #f8f9fa;
        border-radius: 12px;
        color: #666;
    }

    .no-direcciones i {
        font-size: 3rem;
        margin-bottom: 15px;
        color: #ccc;
    }

    .btn-volver {
        display: inline-block;
        margin-bottom: 20px;
        padding: 10px 20px;
        background: #6c757d;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .btn-volver:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }
</style>

<body>
    <?php include_once __DIR__ . '/../admin/includes/header.php'; ?>

    <div class="main-wrapper">
        <!-- Contenido principal -->
        <div class="main-content" style="margin-left: 0; width: 100%;">
            <div class="profile-container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
                <a href="<?= url('/auth/profile') ?>" class="btn-volver">
                    ← Volver al perfil
                </a>

                <h1>Mis Direcciones</h1>

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

                <?php if (empty($direcciones)): ?>
                    <div class="no-direcciones">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>No tienes direcciones guardadas</h3>
                        <p>Las direcciones que guardes durante el proceso de compra aparecerán aquí.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($direcciones as $direccion): ?>
                        <div class="direccion-card <?= $direccion['es_principal'] ? 'principal' : '' ?>">
                            <?php if ($direccion['es_principal']): ?>
                                <span class="badge-principal">Principal</span>
                            <?php endif; ?>

                            <div class="direccion-header">
                                <div>
                                    <span class="direccion-nombre"><?= htmlspecialchars($direccion['nombre_direccion'] ?? 'Mi dirección') ?></span>
                                    <span class="direccion-tipo tipo-<?= htmlspecialchars($direccion['tipo_direccion'] ?? 'casa') ?>">
                                        <?php
                                        $tipoDir = $direccion['tipo_direccion'] ?? 'casa';
                                        $tipos = ['casa' => 'Casa', 'trabajo' => 'Trabajo', 'otro' => 'Otro'];
                                        echo $tipos[$tipoDir] ?? 'Otro';
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <div class="direccion-info">
                                <p><strong>Dirección:</strong> <?= htmlspecialchars($direccion['direccion'] ?? '') ?></p>
                                <p><strong>Distrito:</strong> <?= htmlspecialchars($direccion['distrito'] ?? '') ?></p>
                                <?php if (!empty($direccion['provincia_nombre'])): ?>
                                    <p><strong>Provincia:</strong> <?= htmlspecialchars($direccion['provincia_nombre']) ?></p>
                                <?php endif; ?>
                                <p><strong>Departamento:</strong> <?= htmlspecialchars($direccion['departamento_nombre'] ?? '') ?></p>
                                <?php if (!empty($direccion['referencia'])): ?>
                                    <p><strong>Referencia:</strong> <?= htmlspecialchars($direccion['referencia']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="direccion-actions">
                                <a href="<?= url('/usuario/editar-direccion?id=' . $direccion['id']) ?>" class="btn-editar">
                                    Editar
                                </a>
                                <button onclick="eliminarDireccion(<?= $direccion['id'] ?>)" class="btn-eliminar">
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/../admin/includes/footer.php'; ?>

    <script>
        function eliminarDireccion(direccionId) {
            if (!confirm('¿Estás seguro de que deseas eliminar esta dirección?')) {
                return;
            }

            fetch('<?= url("usuario/eliminar-direccion") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + direccionId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '<?= url("/usuario/mis-direcciones?success=") ?>' + encodeURIComponent(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar la dirección');
            });
        }
    </script>
</body>
</html>
