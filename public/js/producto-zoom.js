/**
 * Sistema de Zoom Estilo Falabella
 * Zoom hover en desktop, doble tap en móvil
 */
(function () {
  "use strict";

  // Elementos principales
  const zoomContainer = document.querySelector(".image-zoom-container");
  const imageWrapper = document.querySelector(".image-wrapper");
  const zoomableImage = document.getElementById("main-product-image");
  const zoomLens = document.querySelector(".zoom-lens");
  const thumbnails = document.querySelectorAll(".thumbnail-images img");

  // Variables de estado
  let isZoomed = false;
  let isDragging = false;
  let startX,
    startY,
    currentX = 0,
    currentY = 0;
  let imageRect, imageNaturalSize, scaleRatio;

  // Configuración
  const ZOOM_SCALE = 2;
  const MOBILE_ZOOM_SCALE = 2;

  /**
   * Inicializa el sistema de zoom
   */
  function initZoomSystem() {
    if (!zoomContainer || !zoomableImage) return;

    // Precargar la imagen para obtener dimensiones reales
    zoomableImage.onload = function () {
      imageNaturalSize = {
        width: this.naturalWidth,
        height: this.naturalHeight,
      };
      calculateScaleRatio();
    };

    // Si la imagen ya está cargada
    if (zoomableImage.complete) {
      imageNaturalSize = {
        width: zoomableImage.naturalWidth,
        height: zoomableImage.naturalHeight,
      };
      calculateScaleRatio();
    }

    // Event listeners para desktop
    if (window.innerWidth >= 769) {
      initDesktopZoom();
    } else {
      initMobileZoom();
    }

    // Event listeners para miniaturas
    initThumbnails();

    // Re-inicializar en resize
    window.addEventListener("resize", handleResize);
  }

  /**
   * Calcula la relación de escala de la imagen
   */
  function calculateScaleRatio() {
    if (!imageNaturalSize || !zoomableImage) return;

    const displayedWidth = zoomableImage.offsetWidth;
    const displayedHeight = zoomableImage.offsetHeight;

    scaleRatio = {
      x: imageNaturalSize.width / displayedWidth,
      y: imageNaturalSize.height / displayedHeight,
    };
  }

  /**
   * Inicializa zoom para desktop (hover)
   */
  function initDesktopZoom() {
    // Mostrar lente de zoom al mover mouse
    zoomContainer.addEventListener("mousemove", function (e) {
      if (!isZoomed) return;

      moveZoomLens(e);
      moveImage(e);
    });

    // Activar zoom al entrar al contenedor
    zoomContainer.addEventListener("mouseenter", function () {
      if (window.innerWidth < 769) return;
      activateZoom();
    });

    // Desactivar zoom al salir del contenedor
    zoomContainer.addEventListener("mouseleave", function () {
      if (window.innerWidth < 769) return;
      deactivateZoom();
    });
  }

  /**
   * Inicializa zoom para móvil (doble tap)
   */
  function initMobileZoom() {
    let tapCount = 0;
    let tapTimer;

    zoomContainer.addEventListener("click", function (e) {
      tapCount++;

      if (tapCount === 1) {
        tapTimer = setTimeout(function () {
          tapCount = 0;
        }, 300);
      } else if (tapCount === 2) {
        clearTimeout(tapTimer);
        tapCount = 0;

        // Toggle zoom
        if (isZoomed) {
          deactivateZoom();
        } else {
          activateZoom();
          // Centrar la imagen en el punto del tap
          centerImageOnTap(e);
        }
      }
    });

    // Arrastre cuando está zoomed
    zoomContainer.addEventListener(
      "touchmove",
      function (e) {
        if (!isZoomed) return;

        e.preventDefault();
        moveImageOnTouch(e);
      },
      { passive: false }
    );

    // Reset al soltar
    zoomContainer.addEventListener("touchend", function () {
      isDragging = false;
    });
  }

  /**
   * Mueve el lente de zoom en desktop
   */
  function moveZoomLens(e) {
    if (!zoomLens) return;

    const containerRect = zoomContainer.getBoundingClientRect();
    const x = e.clientX - containerRect.left;
    const y = e.clientY - containerRect.top;

    // Posicionar lente centrado en el cursor
    const lensX = x - zoomLens.offsetWidth / 2;
    const lensY = y - zoomLens.offsetHeight / 2;

    // Mantener lente dentro del contenedor
    const maxX = containerRect.width - zoomLens.offsetWidth;
    const maxY = containerRect.height - zoomLens.offsetHeight;

    zoomLens.style.left = Math.max(0, Math.min(lensX, maxX)) + "px";
    zoomLens.style.top = Math.max(0, Math.min(lensY, maxY)) + "px";
  }

  /**
   * Mueve la imagen zoomed en desktop con límites correctos
   */
  function moveImage(e) {
    if (!isZoomed) return;

    const containerRect = zoomContainer.getBoundingClientRect();
    const imageRect = zoomableImage.getBoundingClientRect();

    const x = e.clientX - containerRect.left;
    const y = e.clientY - containerRect.top;

    // Calcular posición relativa (0 a 1) dentro del contenedor
    const relX = x / containerRect.width;
    const relY = y / containerRect.height;

    // Calcular las dimensiones REALES de la imagen (sin bordes/padding)
    const imageVisibleWidth = imageRect.width;
    const imageVisibleHeight = imageRect.height;

    // Calcular las dimensiones zoomed
    const zoomedWidth = imageVisibleWidth * ZOOM_SCALE;
    const zoomedHeight = imageVisibleHeight * ZOOM_SCALE;

    // Calcular cuánto podemos mover la imagen (área visible extra)
    // Solo mover si la imagen zoomed es más grande que el área visible
    const extraWidth = Math.max(0, zoomedWidth - imageVisibleWidth);
    const extraHeight = Math.max(0, zoomedHeight - imageVisibleHeight);

    // Si no hay área extra, centrar y no mover
    if (extraWidth <= 0 && extraHeight <= 0) {
      zoomableImage.style.transform = `scale(${ZOOM_SCALE}) translate(0px, 0px)`;
      return;
    }

    // Calcular desplazamiento máximo (mitad del área extra)
    const maxMoveX = extraWidth / 2;
    const maxMoveY = extraHeight / 2;

    // Mapear la posición del cursor al desplazamiento
    // Cuando cursor en centro (0.5) → desplazamiento 0
    // Cuando cursor en bordes (0 o 1) → desplazamiento máximo
    const moveX = -((relX - 0.5) * extraWidth);
    const moveY = -((relY - 0.5) * extraHeight);

    // Aplicar límites estrictos - nunca mostrar áreas fuera de la imagen
    const boundedMoveX = Math.max(-maxMoveX, Math.min(maxMoveX, moveX));
    const boundedMoveY = Math.max(-maxMoveY, Math.min(maxMoveY, moveY));

    zoomableImage.style.transform = `scale(${ZOOM_SCALE}) translate(${boundedMoveX}px, ${boundedMoveY}px)`;
  }

  /**
   * Mueve la imagen en touch (móvil) con límites correctos
   */
  function moveImageOnTouch(e) {
    if (!isZoomed) return;

    const touch = e.touches[0];
    const containerRect = zoomContainer.getBoundingClientRect();
    const imageRect = zoomableImage.getBoundingClientRect();

    if (!isDragging) {
      isDragging = true;
      startX = touch.clientX - currentX;
      startY = touch.clientY - currentY;
      return;
    }

    e.preventDefault();

    currentX = touch.clientX - startX;
    currentY = touch.clientY - startY;

    // Calcular dimensiones REALES y límites
    const imageVisibleWidth = imageRect.width;
    const imageVisibleHeight = imageRect.height;
    const zoomedWidth = imageVisibleWidth * MOBILE_ZOOM_SCALE;
    const zoomedHeight = imageVisibleHeight * MOBILE_ZOOM_SCALE;

    const extraWidth = Math.max(0, zoomedWidth - imageVisibleWidth);
    const extraHeight = Math.max(0, zoomedHeight - imageVisibleHeight);

    // Si no hay área extra, no permitir movimiento
    if (extraWidth <= 0 && extraHeight <= 0) {
      currentX = 0;
      currentY = 0;
    } else {
      // Aplicar límites estrictos al movimiento
      const maxMoveX = extraWidth / 2;
      const maxMoveY = extraHeight / 2;

      currentX = Math.max(-maxMoveX, Math.min(maxMoveX, currentX));
      currentY = Math.max(-maxMoveY, Math.min(maxMoveY, currentY));
    }

    zoomableImage.style.transform = `scale(${MOBILE_ZOOM_SCALE}) translate(${currentX}px, ${currentY}px)`;
  }

  /**
   * Centra la imagen en el punto del tap (móvil) con límites correctos
   */
  function centerImageOnTap(e) {
    const containerRect = zoomContainer.getBoundingClientRect();
    const imageRect = zoomableImage.getBoundingClientRect();
    const touch = e.changedTouches ? e.changedTouches[0] : e;

    const x = touch.clientX - containerRect.left;
    const y = touch.clientY - containerRect.top;

    // Calcular posición relativa
    const relX = x / containerRect.width;
    const relY = y / containerRect.height;

    // Calcular dimensiones REALES
    const imageVisibleWidth = imageRect.width;
    const imageVisibleHeight = imageRect.height;
    const zoomedWidth = imageVisibleWidth * MOBILE_ZOOM_SCALE;
    const zoomedHeight = imageVisibleHeight * MOBILE_ZOOM_SCALE;

    // Calcular área extra visible
    const extraWidth = Math.max(0, zoomedWidth - imageVisibleWidth);
    const extraHeight = Math.max(0, zoomedHeight - imageVisibleHeight);

    // Si no hay área extra, no mover
    if (extraWidth <= 0 && extraHeight <= 0) {
      currentX = 0;
      currentY = 0;
      return;
    }

    // Calcular desplazamiento desde el centro
    const maxMoveX = extraWidth / 2;
    const maxMoveY = extraHeight / 2;

    // Mapear posición del tap al desplazamiento
    currentX = -((relX - 0.5) * extraWidth);
    currentY = -((relY - 0.5) * extraHeight);

    // Aplicar límites estrictos
    currentX = Math.max(-maxMoveX, Math.min(maxMoveX, currentX));
    currentY = Math.max(-maxMoveY, Math.min(maxMoveY, currentY));

    zoomableImage.style.transform = `scale(${MOBILE_ZOOM_SCALE}) translate(${currentX}px, ${currentY}px)`;
  }

  /**
   * Activa el zoom
   */
  function activateZoom() {
    isZoomed = true;
    zoomContainer.classList.add("zooming");
    zoomableImage.classList.add("zoomed");

    if (window.innerWidth >= 769) {
      zoomLens.style.opacity = "1";
    }
  }

  /**
   * Desactiva el zoom
   */
  function deactivateZoom() {
    isZoomed = false;
    isDragging = false;
    zoomContainer.classList.remove("zooming", "zoomed");
    zoomableImage.classList.remove("zoomed");
    zoomLens.style.opacity = "0";

    // Reset transform
    zoomableImage.style.transform = "scale(1) translate(0px, 0px)";
    currentX = 0;
    currentY = 0;
  }

  /**
   * Inicializa las miniaturas
   */
  function initThumbnails() {
    thumbnails.forEach((thumb) => {
      thumb.addEventListener("click", function () {
        // Remover clase activa de todas las miniaturas
        thumbnails.forEach((t) => t.classList.remove("activo"));

        // Activar miniatura clickeada
        this.classList.add("activo");

        // Cambiar imagen principal
        const newSrc = this.getAttribute("data-src");
        zoomableImage.src = newSrc;

        // Resetear zoom
        deactivateZoom();

        // Recalcular dimensiones cuando cargue la nueva imagen
        zoomableImage.onload = function () {
          imageNaturalSize = {
            width: this.naturalWidth,
            height: this.naturalHeight,
          };
          calculateScaleRatio();
        };
      });
    });
  }

  /**
   * Maneja el redimensionado de ventana
   */
  function handleResize() {
    deactivateZoom();
    calculateScaleRatio();

    if (window.innerWidth >= 769) {
      initDesktopZoom();
    } else {
      initMobileZoom();
    }
  }

  /**
   * Inicializa todo el sistema
   */
  function init() {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initZoomSystem);
    } else {
      initZoomSystem();
    }
  }


  // Inicializar
  init();
})();

