/**
 * Modal de Imagen con Zoom y Arrastre - TecnoVedades
 * Funcionalidad para abrir imágenes en modal con zoom y movimiento
 */
(function() {
    'use strict';

    // Variables del modal
    const imageModal = document.getElementById('image-modal');
    const modalImage = document.getElementById('modal-image');
    const closeImageModal = document.getElementById('close-image-modal');
    const mainImage = document.getElementById('main-product-image');
    const thumbnails = document.querySelectorAll('.thumbnail-images img');

    // Variables para el zoom y arrastre
    let scale = 1;
    let isDragging = false;
    let startX, startY, translateX = 0, translateY = 0;
    let lastTranslateX = 0, lastTranslateY = 0;

    // Configuración
    const ZOOM_SENSITIVITY = 0.1;
    const MIN_ZOOM = 1;
    const MAX_ZOOM = 5;

    /**
     * Abre el modal con la imagen especificada
     */
    function openImageModal(imageSrc, imageAlt) {
        if (!imageModal || !modalImage) return;
        
        // Resetear transformaciones
        resetImageTransform();
        
        modalImage.src = imageSrc;
        modalImage.alt = imageAlt || '';
        imageModal.style.display = 'flex';
        
        // Prevenir scroll del body
        document.body.classList.add('modal-open');
        
        // Enfocar el modal para accesibilidad
        imageModal.focus();
        
        // Añadir event listeners específicos del modal
        addModalEventListeners();
    }

    /**
     * Cierra el modal de imagen
     */
    function closeModal() {
        if (!imageModal) return;
        
        imageModal.style.display = 'none';
        
        // Restaurar scroll del body
        document.body.classList.remove('modal-open');
        
        // Remover event listeners específicos del modal
        removeModalEventListeners();
        
        // Resetear transformaciones
        resetImageTransform();
    }

    /**
     * Resetea todas las transformaciones de la imagen
     */
    function resetImageTransform() {
        scale = 1;
        translateX = 0;
        translateY = 0;
        lastTranslateX = 0;
        lastTranslateY = 0;
        
        if (modalImage) {
            modalImage.style.transform = 'scale(1) translate(0px, 0px)';
            modalImage.classList.remove('zoomed');
        }
    }

    /**
     * Aplica las transformaciones actuales a la imagen
     */
    function applyTransform() {
        if (!modalImage) return;
        
        modalImage.style.transform = `scale(${scale}) translate(${translateX}px, ${translateY}px)`;
        
        if (scale > 1) {
            modalImage.classList.add('zoomed');
        } else {
            modalImage.classList.remove('zoomed');
        }
    }

    /**
     * Maneja el zoom con la rueda del mouse
     */
    function handleWheel(e) {
        e.preventDefault();
        
        const rect = modalImage.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        
        const delta = -Math.sign(e.deltaY);
        const newScale = scale + (delta * ZOOM_SENSITIVITY * scale);
        
        zoomImage(newScale, mouseX, mouseY);
    }

    /**
     * Aplica zoom a la imagen manteniendo el punto bajo el mouse
     */
    function zoomImage(newScale, mouseX, mouseY) {
        newScale = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, newScale));
        
        if (newScale === scale) return;
        
        // Calcular el desplazamiento para mantener el punto bajo el mouse
        const scaleRatio = newScale / scale;
        const imageCenterX = (mouseX - translateX) / scale;
        const imageCenterY = (mouseY - translateY) / scale;
        
        translateX = mouseX - (imageCenterX * newScale);
        translateY = mouseY - (imageCenterY * newScale);
        
        scale = newScale;
        applyTransform();
    }

    /**
     * Inicia el arrastre
     */
    function startDrag(e) {
        if (scale <= 1) return;
        
        isDragging = true;
        modalImage.style.cursor = 'grabbing';
        
        const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
        const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;
        
        startX = clientX - lastTranslateX;
        startY = clientY - lastTranslateY;
        
        // Prevenir comportamiento por defecto
        e.preventDefault();
    }

    /**
     * Mueve la imagen durante el arrastre
     */
    function duringDrag(e) {
        if (!isDragging) return;
        
        const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
        const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;
        
        translateX = clientX - startX;
        translateY = clientY - startY;
        
        applyTransform();
    }

    /**
     * Termina el arrastre
     */
    function endDrag() {
        if (!isDragging) return;
        
        isDragging = false;
        modalImage.style.cursor = scale > 1 ? 'grab' : 'default';
        
        lastTranslateX = translateX;
        lastTranslateY = translateY;
    }

    /**
     * Añade event listeners específicos del modal
     */
    function addModalEventListeners() {
        if (!modalImage) return;
        
        // Wheel para zoom
        modalImage.addEventListener('wheel', handleWheel, { passive: false });
        
        // Mouse events para arrastre
        modalImage.addEventListener('mousedown', startDrag);
        document.addEventListener('mousemove', duringDrag);
        document.addEventListener('mouseup', endDrag);
        
        // Touch events para móviles
        modalImage.addEventListener('touchstart', startDrag, { passive: false });
        document.addEventListener('touchmove', duringDrag, { passive: false });
        document.addEventListener('touchend', endDrag);
    }

    /**
     * Remueve event listeners específicos del modal
     */
    function removeModalEventListeners() {
        if (!modalImage) return;
        
        modalImage.removeEventListener('wheel', handleWheel);
        modalImage.removeEventListener('mousedown', startDrag);
        document.removeEventListener('mousemove', duringDrag);
        document.removeEventListener('mouseup', endDrag);
        modalImage.removeEventListener('touchstart', startDrag);
        document.removeEventListener('touchmove', duringDrag);
        document.removeEventListener('touchend', endDrag);
    }

    /**
     * Inicializa los event listeners principales
     */
    function initModal() {
        // Abrir modal al hacer click en la imagen principal
        if (mainImage) {
            mainImage.addEventListener('click', function() {
                openImageModal(this.src, this.alt);
            });
        }

        // Abrir modal al hacer click en miniaturas
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                openImageModal(this.src, this.alt);
                
                // Actualizar la imagen principal también
                if (mainImage) {
                    mainImage.src = this.src;
                }
            });
        });

        // Cerrar con botón X
        if (closeImageModal) {
            closeImageModal.addEventListener('click', closeModal);
        }

        // Cerrar al hacer click en el overlay (fondo)
        if (imageModal) {
            imageModal.addEventListener('click', function(e) {
                if (e.target === imageModal) {
                    closeModal();
                }
            });
        }

        // Cerrar con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && imageModal.style.display === 'flex') {
                closeModal();
            }
        });
    }

    /**
     * Inicializa toda la funcionalidad
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initModal);
        } else {
            initModal();
        }
    }

    // Inicializar
    init();

    // Exportar funciones para uso externo
    window.ImageModal = {
        open: openImageModal,
        close: closeModal,
        reset: resetImageTransform
    };

})();


