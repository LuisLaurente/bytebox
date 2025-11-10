/**
 * Descripción de Producto - TecnoVedades
 * JavaScript principal para la página de descripción del producto
 */
(function() {
    'use strict';

    /**
     * Inicializa la galería de imágenes
     */
    function initGallery() {
        const mainImage = document.getElementById('main-product-image');
        const thumbs = document.querySelectorAll('.thumbnail-images .thumb');
        
        if (mainImage && thumbs.length > 0) {
            thumbs.forEach(thumb => {
                thumb.addEventListener('click', function() {
                    mainImage.src = this.dataset.src || this.src;
                    thumbs.forEach(t => t.classList.remove('activo'));
                    this.classList.add('activo');
                });
            });
        }
    }

    /**
     * Inicializa los controles de cantidad
     */
    function initQuantityControls() {
        const qtyInput = document.getElementById('qty-input');
        const qtyIncrease = document.getElementById('qty-increase');
        const qtyDecrease = document.getElementById('qty-decrease');
        const formCantidad = document.getElementById('form-cantidad');
        const stockLimit = window.productStock || null;

        function setQty(val) {
            val = Math.max(1, Math.floor(parseInt(val) || 1));
            if (stockLimit !== null) val = Math.min(val, stockLimit);
            if (qtyInput) qtyInput.value = val;
            if (formCantidad) formCantidad.value = val;
        }

        if (qtyIncrease) {
            qtyIncrease.addEventListener('click', () => {
                const currentValue = parseInt(qtyInput.value) || 1;
                setQty(currentValue + 1);
            });
        }

        if (qtyDecrease) {
            qtyDecrease.addEventListener('click', () => {
                const currentValue = parseInt(qtyInput.value) || 1;
                setQty(currentValue - 1);
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('change', () => setQty(qtyInput.value));
            // Inicializar el valor del formulario
            if (formCantidad) formCantidad.value = qtyInput.value || '1';
        }
    }

    /**
     * Inicializa los elementos colapsables
     */
    function initCollapsibles() {
        document.querySelectorAll('.collapsible-header').forEach(header => {
            header.addEventListener('click', () => {
                const section = header.closest('.collapsible-section');
                
                // Si es la de especificaciones y está parcialmente visible, no hacer nada aquí
                if (section.classList.contains('partially-visible')) {
                    return;
                }

                header.classList.toggle('active');
                const content = header.nextElementSibling;
                const arrow = header.querySelector('.arrow');

                if (header.classList.contains('active')) {
                    content.style.display = 'block';
                    if (arrow) arrow.innerHTML = '&#9650;';
                } else {
                    content.style.display = 'none';
                    if (arrow) arrow.innerHTML = '&#9660;';
                }
            });
        });
    }

    /**
     * Inicializa la expansión de especificaciones
     */
    function initSpecsExpansion() {
        const specsSection = document.querySelector('.partially-visible');
        if (!specsSection) return;

        const viewMoreButton = specsSection.querySelector('.view-more-specs');
        const viewLessButton = specsSection.querySelector('.view-less-specs');
        const header = specsSection.querySelector('.collapsible-header');
        const content = specsSection.querySelector('.collapsible-content');

        const expandSpecs = function(e) {
            if (specsSection.classList.contains('partially-visible')) {
                specsSection.classList.remove('partially-visible');
                if (!header.classList.contains('active')) {
                    header.classList.add('active');
                }
                if (content) content.style.display = 'block';
                const arrow = header.querySelector('.arrow');
                if (arrow) arrow.innerHTML = '&#9650;';
                if (e) e.stopPropagation();
            }
        };

        const collapseSpecs = function(e) {
            if (!specsSection.classList.contains('partially-visible')) {
                specsSection.classList.add('partially-visible');
                if (header.classList.contains('active')) {
                    header.classList.remove('active');
                }
                if (content) content.style.display = '';
                const arrow = header.querySelector('.arrow');
                if (arrow) arrow.innerHTML = '&#9660;';
                if (e) e.stopPropagation();
            }
        };

        if (viewMoreButton) {
            viewMoreButton.addEventListener('click', expandSpecs);
        }
        if (viewLessButton) {
            viewLessButton.addEventListener('click', collapseSpecs);
        }
        if (header) {
            header.addEventListener('click', expandSpecs, true);
        }
    }

    /**
     * Inicializa el scroll suave para enlaces internos
     */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    /**
     * Inicializa toda la funcionalidad de la página
     */
    function init() {
        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initGallery();
                initQuantityControls();
                initCollapsibles();
                initSpecsExpansion();
                initSmoothScroll();
            });
        } else {
            initGallery();
            initQuantityControls();
            initCollapsibles();
            initSpecsExpansion();
            initSmoothScroll();
        }
    }

    // Inicializar
    init();
    
    // ========================================
    // GESTIÓN DE VARIANTES DE PRODUCTO
    // ========================================
    
    function initVariants() {
        // Verificar si hay variantes
        if (!window.productVariants || window.productVariants.length === 0) {
            return;
        }
        
        // Elementos del DOM
        const variantOptions = document.querySelectorAll('.variant-option');
        const addToCartBtn = document.querySelector('.add-to-cart-btn');
        const variantStockInfo = document.querySelector('.variant-stock-info');
        const variantStockCount = document.getElementById('variant-stock-count');
        const variantIdInput = document.getElementById('form-variante-id');
        const qtyInput = document.getElementById('qty-input');
        const formCantidad = document.getElementById('form-cantidad');
        
        if (variantOptions.length === 0) {
            return;
        }
        
        // Estado
        const selectedVariants = {
            talla: null,
            color: null
        };
        
        let currentVariant = null;
        
        // Registrar eventos
        variantOptions.forEach((btn) => {
            btn.addEventListener('click', function(event) {
                const variantType = this.dataset.variantType;
                const variantValue = this.dataset.variantValue;
                
                // Remover selección anterior del mismo tipo
                document.querySelectorAll(`[data-variant-type="${variantType}"]`).forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Agregar selección actual
                this.classList.add('selected');
                selectedVariants[variantType] = variantValue;
                
                // Buscar variante que coincida
                updateVariantInfo();
            }, false);
        });
        
        // Función para actualizar info de variante
        function updateVariantInfo() {
            // Solo buscar si hay al menos una variante seleccionada
            if (selectedVariants.talla === null && selectedVariants.color === null) {
                if (variantStockInfo) variantStockInfo.style.display = 'none';
                if (variantIdInput) variantIdInput.value = '';
                if (addToCartBtn) {
                    addToCartBtn.disabled = true;
                    addToCartBtn.textContent = 'Selecciona una variante';
                }
                return;
            }
            
            const matchingVariant = window.productVariants.find(variant => {
                // Verificar coincidencia exacta de talla
                const tallaMatch = selectedVariants.talla === null || 
                                   variant.talla === selectedVariants.talla || 
                                   (!variant.talla && selectedVariants.talla === null);
                
                // Verificar coincidencia exacta de color
                const colorMatch = selectedVariants.color === null || 
                                   variant.color === selectedVariants.color || 
                                   (!variant.color && selectedVariants.color === null);
                
                // Solo coincide si AMBOS criterios seleccionados coinciden
                // Si el usuario seleccionó talla Y color, la variante debe tener esa talla Y ese color
                if (selectedVariants.talla !== null && selectedVariants.color !== null) {
                    return variant.talla === selectedVariants.talla && variant.color === selectedVariants.color;
                }
                
                // Si solo seleccionó uno, debe coincidir ese y el otro puede ser cualquiera
                return tallaMatch && colorMatch;
            });
            
            if (matchingVariant) {
                currentVariant = matchingVariant;
                
                const stock = parseInt(matchingVariant.stock) || 0;
                
                // 🖼️ CAMBIAR IMAGEN SI LA VARIANTE TIENE UNA ASOCIADA
                if (matchingVariant.imagen) {
                    const mainImage = document.getElementById('main-product-image');
                    if (mainImage) {
                        // Usar la base URL pasada desde PHP (incluye /TECNOVEDADES/public/uploads/)
                        const variantImageUrl = window.baseImageUrl + matchingVariant.imagen;
                        
                        // Cambiar la imagen principal con efecto de transición
                        mainImage.style.transition = 'opacity 0.3s ease-in-out';
                        mainImage.style.opacity = '0.4';
                        
                        setTimeout(() => {
                            mainImage.src = variantImageUrl;
                            mainImage.onload = function() {
                                mainImage.style.opacity = '1';
                            };
                        }, 200);
                        
                        // Actualizar las miniaturas - activar la que coincida
                        const thumbs = document.querySelectorAll('.thumbnail-images .thumb');
                        thumbs.forEach(thumb => {
                            thumb.classList.remove('activo');
                            
                            // Verificar si esta miniatura corresponde a la imagen de la variante
                            const thumbSrc = thumb.dataset.src || thumb.src;
                            if (thumbSrc.includes(matchingVariant.imagen)) {
                                thumb.classList.add('activo');
                            }
                        });
                    }
                }
                
                // Mostrar stock
                if (variantStockCount) variantStockCount.textContent = stock;
                if (variantStockInfo) {
                    variantStockInfo.style.display = 'flex';
                    variantStockInfo.classList.remove('low-stock', 'out-of-stock');
                    
                    // Actualizar el texto según el stock
                    const stockText = variantStockInfo.querySelector('.variant-stock-text');
                    if (stockText) {
                        if (stock === 0) {
                            stockText.innerHTML = '<strong>Sin stock disponible</strong>';
                        } else {
                            stockText.innerHTML = `Stock disponible: <strong id="variant-stock-count" class="stock-number">${stock}</strong> unidades`;
                        }
                    }
                    
                    if (stock === 0) {
                        variantStockInfo.classList.add('out-of-stock');
                    } else if (stock <= 5) {
                        variantStockInfo.classList.add('low-stock');
                    }
                }
                
                // Habilitar botón
                if (stock > 0) {
                    if (variantIdInput) variantIdInput.value = matchingVariant.id;
                    if (addToCartBtn) {
                        addToCartBtn.disabled = false;
                        addToCartBtn.textContent = 'Agregar al Carro';
                    }
                } else {
                    if (addToCartBtn) {
                        addToCartBtn.disabled = true;
                        addToCartBtn.textContent = 'Agotado';
                    }
                }
                
                // Actualizar límites de cantidad
                if (qtyInput) {
                    qtyInput.max = stock;
                    const currentQty = parseInt(qtyInput.value) || 1;
                    if (currentQty > stock) {
                        qtyInput.value = Math.max(1, stock);
                    }
                    if (formCantidad) formCantidad.value = qtyInput.value;
                }
                
                window.productStock = stock;
                
            } else {
                // No se encontró una variante que coincida con la combinación seleccionada
                // Mostrar "Sin stock" si ambos están seleccionados
                if (selectedVariants.talla !== null && selectedVariants.color !== null) {
                    if (variantStockInfo) {
                        variantStockInfo.style.display = 'flex';
                        variantStockInfo.classList.remove('low-stock');
                        variantStockInfo.classList.add('out-of-stock');
                        
                        // Actualizar el texto
                        const stockText = variantStockInfo.querySelector('.variant-stock-text');
                        if (stockText) {
                            stockText.innerHTML = '<strong>Sin stock disponible</strong>';
                        }
                    }
                    if (addToCartBtn) {
                        addToCartBtn.disabled = true;
                        addToCartBtn.textContent = 'Sin stock';
                    }
                } else {
                    if (variantStockInfo) variantStockInfo.style.display = 'none';
                    if (addToCartBtn) {
                        addToCartBtn.disabled = true;
                        addToCartBtn.textContent = 'Selecciona una variante';
                    }
                }
                if (variantIdInput) variantIdInput.value = '';
            }
        }
        
        // Auto-seleccionar si solo hay una opción
        const tallas = [...new Set(window.productVariants.map(v => v.talla).filter(Boolean))];
        const colores = [...new Set(window.productVariants.map(v => v.color).filter(Boolean))];
        
        if (tallas.length === 1) {
            const tallaBtn = document.querySelector(`[data-variant-type="talla"][data-variant-value="${tallas[0]}"]`);
            if (tallaBtn) {
                setTimeout(() => tallaBtn.click(), 100);
            }
        }
        
        if (colores.length === 1) {
            const colorBtn = document.querySelector(`[data-variant-type="color"][data-variant-value="${colores[0]}"]`);
            if (colorBtn) {
                setTimeout(() => colorBtn.click(), 100);
            }
        }
    }
    
    // Inicializar variantes después de un pequeño delay
    setTimeout(initVariants, 300);

})();
