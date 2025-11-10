/**
 * ========================================
 * GESTIÓN DE VARIANTES DE PRODUCTO
 * ========================================
 */

console.log('🔵 Cargando producto-variantes.js...');

// Usar setTimeout para asegurar que el DOM esté completamente listo
setTimeout(function() {
    console.log('🚀 Iniciando sistema de variantes (con delay)...');
    console.log(' window.productVariants:', window.productVariants);
    
    // Verificar si hay variantes
    if (!window.productVariants || window.productVariants.length === 0) {
        console.log('⚪ No hay variantes - modo normal');
        return;
    }
    
    console.log('✅ Producto con', window.productVariants.length, 'variantes');
    
    // Elementos del DOM
    const variantOptions = document.querySelectorAll('.variant-option');
    const addToCartBtn = document.querySelector('.add-to-cart-btn');
    const variantStockInfo = document.querySelector('.variant-stock-info');
    const variantStockCount = document.getElementById('variant-stock-count');
    const variantIdInput = document.getElementById('form-variante-id');
    const qtyInput = document.getElementById('qty-input');
    const formCantidad = document.getElementById('form-cantidad');
    
    console.log('🔍 Elementos encontrados:');
    console.log('  - Opciones de variantes:', variantOptions.length);
    console.log('  - Botón agregar:', addToCartBtn ? 'SÍ' : 'NO');
    console.log('  - Input variante_id:', variantIdInput ? 'SÍ' : 'NO');
    
    if (variantOptions.length === 0) {
        console.error('❌ No se encontraron botones .variant-option en el DOM');
        return;
    }
    
    // Estado
    const selectedVariants = {
        talla: null,
        color: null
    };
    
    let currentVariant = null;
    
    // REGISTRAR EVENTOS - Método simple y directo
    console.log('📝 Registrando eventos click...');
    
    variantOptions.forEach((btn, index) => {
        console.log(`  ${index + 1}. Botón "${btn.textContent.trim()}" - type: ${btn.dataset.variantType}, value: ${btn.dataset.variantValue}`);
        
        // Asegurar que sea clickeable
        btn.style.cursor = 'pointer';
        btn.style.userSelect = 'none';
        
        // Registrar evento
        btn.addEventListener('click', function(event) {
            console.log(`\n🎯 ¡CLICK DETECTADO! en botón ${index + 1}`);
            console.log('   Tipo:', this.dataset.variantType);
            console.log('   Valor:', this.dataset.variantValue);
            
            const variantType = this.dataset.variantType;
            const variantValue = this.dataset.variantValue;
            
            // Remover selección anterior del mismo tipo
            document.querySelectorAll(`[data-variant-type="${variantType}"]`).forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Agregar selección actual
            this.classList.add('selected');
            selectedVariants[variantType] = variantValue;
            
            console.log('   ✅ Selección actualizada:', selectedVariants);
            
            // Buscar variante que coincida
            updateVariantInfo();
        }, false);
    });
    
    console.log('✅ Eventos registrados correctamente\n');
    
    // Función para actualizar info de variante
    function updateVariantInfo() {
        console.log('📊 Buscando variante que coincida con:', selectedVariants);
        
        const matchingVariant = window.productVariants.find(variant => {
            let matches = true;
            
            if (selectedVariants.talla !== null && variant.talla !== selectedVariants.talla) {
                matches = false;
            }
            
            if (selectedVariants.color !== null && variant.color !== selectedVariants.color) {
                matches = false;
            }
            
            return matches;
        });
        
        if (matchingVariant) {
            console.log('✅ Variante encontrada:', matchingVariant);
            currentVariant = matchingVariant;
            
            const stock = parseInt(matchingVariant.stock) || 0;
            
            // Mostrar stock
            if (variantStockCount) {
                variantStockCount.textContent = stock;
            }
            if (variantStockInfo) {
                variantStockInfo.style.display = 'block';
                variantStockInfo.classList.remove('low-stock', 'out-of-stock');
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
                console.log('🟢 Botón habilitado - Variante ID:', matchingVariant.id);
            } else {
                if (addToCartBtn) {
                    addToCartBtn.disabled = true;
                    addToCartBtn.textContent = 'Agotado';
                }
                console.log('🔴 Producto agotado');
            }
            
            // Actualizar límites de cantidad
            if (qtyInput) {
                qtyInput.max = stock;
                const currentQty = parseInt(qtyInput.value) || 1;
                if (currentQty > stock) {
                    qtyInput.value = Math.max(1, stock);
                }
            }
            
            window.productStock = stock;
            
        } else {
            console.log('⚠️ No se encontró variante que coincida');
            
            if (variantStockInfo) variantStockInfo.style.display = 'none';
            if (variantIdInput) variantIdInput.value = '';
            if (addToCartBtn) {
                addToCartBtn.disabled = true;
                addToCartBtn.textContent = 'Selecciona una variante';
            }
        }
    }
    
    // Auto-seleccionar si solo hay una opción
    const tallas = [...new Set(window.productVariants.map(v => v.talla).filter(Boolean))];
    const colores = [...new Set(window.productVariants.map(v => v.color).filter(Boolean))];
    
    console.log('🎯 Tallas disponibles:', tallas);
    console.log('🎯 Colores disponibles:', colores);
    
    if (tallas.length === 1) {
        const tallaBtn = document.querySelector(`[data-variant-type="talla"][data-variant-value="${tallas[0]}"]`);
        if (tallaBtn) {
            console.log('🤖 Auto-seleccionando única talla:', tallas[0]);
            tallaBtn.click();
        }
    }
    
    if (colores.length === 1) {
        const colorBtn = document.querySelector(`[data-variant-type="color"][data-variant-value="${colores[0]}"]`);
        if (colorBtn) {
            console.log('🤖 Auto-seleccionando único color:', colores[0]);
            colorBtn.click();
        }
    }
    
    console.log(' Sistema de variantes listo!\n');
    
}, 500); // Delay de 500ms para asegurar que todo el DOM esté listo
