    <?php
    $metaTitle = $metaTitle ?? 'BYTEBOX';
    $metaDescription = $metaDescription ?? 'Compra tecnología y novedades al mejor precio.';
    $metaImage = $metaImage ?? url('images/default-share.png');
    $canonical = $canonical ?? url('');
    ?>
    <!-- Favicon -->
    <link rel="icon" href="<?= url('image/faviconT.ico') ?>" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('image/faviconT.png') ?>">

    <!-- Web Manifest -->
    <link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
    <meta name="theme-color" content="#2563eb">

    <title><?= htmlspecialchars($metaTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="canonical" href="<?= $canonical ?>">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="<?= $metaImage ?>">
    <meta property="og:url" content="<?= $canonical ?>">
    <meta property="og:type" content="website">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($metaTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="<?= $metaImage ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    
    <!-- FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS personalizado para usuarios -->
    <link rel="stylesheet" href="<?= url('css/min/head.min.css') ?>">
    <!-- CSS personalizado para animaciones de modales -->
    <style>
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: scale(0.95) translateY(-20px); 
            }
            to { 
                opacity: 1; 
                transform: scale(1) translateY(0); 
            }
        }
        
        @keyframes fadeInBackdrop {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-fadeIn {
            opacity: 0;
            transform: scale(0.95) translateY(-20px);
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Estilo para modales con fondo sutil */
        .modal-backdrop {
            animation: fadeInBackdrop 0.2s ease-out;
            background-color: rgba(0, 0, 0, 0.15) !important; /* Fondo aún más transparente */
        }
        
        /* Asegurar que los modales estén bien centrados y visibles */
        #userModal, #deleteModal, #detailModal, #createModal, #editModal {
            align-items: center !important;
            justify-content: center !important;
        }
        
        #userModal.hidden, #deleteModal.hidden, #detailModal.hidden, #createModal.hidden, #editModal.hidden {
            display: none !important;
        }
        
        /* Mostrar modales como flex cuando no están ocultos */
        #userModal:not(.hidden), #deleteModal:not(.hidden), #detailModal:not(.hidden), #createModal:not(.hidden), #editModal:not(.hidden) {
            display: flex !important;
        }
        
        /* Mejorar la visibilidad de los botones de acción */
        .action-button {
            transition: all 0.2s ease-in-out;
        }
        
        .action-button:hover {
            transform: scale(1.1);
        }

        /* Efecto suave para el contenido del modal */
        .modal-content {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
    
    <!-- Componentes JavaScript reutilizables -->

    <title>Panel del Administrador</title>
</head>