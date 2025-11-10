<?php
$popup = (new \Models\Popup())->obtener();
if ($popup && $popup['activo']) :
?>
<!-- Popup Corregido - Solo imagen -->
<div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-65 flex items-center justify-center z-[9999] hidden modal-backdrop p-4">
  <div id="popup-promocional" 
       class="relative bg-white shadow-2xl max-w-3xl w-full mx-auto max-h-[90vh] overflow-hidden transform scale-90 opacity-0 transition-all duration-500 ease-out z-[10000] aspect-square">
    
    <!-- Botón de cerrar - POSICIONADO CORRECTAMENTE -->
    <button class="cerrar-popup absolute top-2 right-2 text-white hover:text-gray-300 text-2xl font-bold z-[10001] w-10 h-10 flex items-center justify-center bg-black rounded-full shadow-lg hover:bg-gray-800 transition-all hover:scale-110 border-2 border-white"
            aria-label="Cerrar popup">×</button>

    <!-- Solo imagen que cubre todo -->
    <?php if (!empty($popup['imagen'])): ?>
        <div class="w-full h-full">
            <img src="<?= url('images/popup/' . $popup['imagen']) ?>" 
                 class="w-full h-full object-cover"
                 alt="Promoción especial"
                 loading="lazy">
        </div>
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('popup-overlay');
    const popup = document.getElementById('popup-promocional');
    const cerrarBtn = document.querySelector('.cerrar-popup');

    // Cerrar con ESC
    function handleEscapeKey(e) {
        if (e.key === 'Escape') {
            cerrarPopup();
        }
    }

    // Cerrar haciendo click fuera del popup
    function handleOverlayClick(e) {
        if (e.target === overlay) {
            cerrarPopup();
        }
    }

    function cerrarPopup() {
        // Restaurar el scroll del body PRIMERO
        document.body.style.overflow = '';
        document.body.classList.remove('popup-open');
        
        // Ocultar el popup
        overlay.classList.add("hidden");
        
        // Guardar en localStorage
        const siguienteAparicion = Date.now() + (6 * 60 * 60 * 1000); 
        localStorage.setItem('popupCerradoHasta', siguienteAparicion.toString());
        
        // Remover event listeners
        document.removeEventListener('keydown', handleEscapeKey);
        overlay.removeEventListener('click', handleOverlayClick);
    }

    function mostrarPopupSiCorresponde() {
        const ahora = Date.now();
        const limite = localStorage.getItem('popupCerradoHasta');

        if (!limite || parseInt(limite) < ahora) {
            // Prevenir scroll del body
            document.body.style.overflow = 'hidden';
            document.body.classList.add('popup-open');
            
            // Mostrar popup
            overlay.classList.remove("hidden");
            
            // Animación
            setTimeout(() => {
                popup.classList.remove("scale-90", "opacity-0");
                popup.classList.add("scale-100", "opacity-100");
            }, 50);

            // Agregar event listeners
            document.addEventListener('keydown', handleEscapeKey);
            overlay.addEventListener('click', handleOverlayClick);
        }
    }

    // Event listener para el botón cerrar
    if (cerrarBtn) {
        cerrarBtn.addEventListener('click', cerrarPopup);
    }

    // Inicializar
    mostrarPopupSiCorresponde();
});
</script>

<style>
/* Estilos adicionales para mejor compatibilidad */
#popup-overlay {
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
}

/* Asegurar que el popup sea cuadrado */
#popup-promocional {
    aspect-ratio: 1 / 1;
}

/* Mejorar accesibilidad en móvil */
@media (max-width: 768px) {
    #popup-overlay {
        padding: 1rem;
        align-items: center;
    }
    
    #popup-promocional {
        max-height: 80vw;
        max-width: 80vw;
    }
    
    .cerrar-popup {
        width: 36px;
        height: 36px;
        top: 8px;
        right: 8px;
        font-size: 1.5rem;
    }
}

/* Para pantallas muy pequeñas */
@media (max-width: 480px) {
    #popup-overlay {
        padding: 0.5rem;
    }
    
    #popup-promocional {
        max-height: 85vw;
        max-width: 85vw;
    }
    
    .cerrar-popup {
        width: 32px;
        height: 32px;
        top: 6px;
        right: 6px;
        font-size: 1.3rem;
    }
}

/* Para pantallas grandes */
@media (min-width: 1024px) {
    #popup-promocional {
        max-width: 600px;
        max-height: 600px;
    }
    
    .cerrar-popup {
        width: 44px;
        height: 44px;
        top: 12px;
        right: 12px;
        font-size: 2rem;
    }
}

/* Estados hover mejorados */
.cerrar-popup:hover {
    transform: scale(1.1);
    background-color: #333 !important;
}
</style>

<?php endif; ?>