<?php
// views/home/_products_grid.php
// Variables esperadas: $productos
?>

<div id="productsWrapper">
  <div class="products-grid">
    <?php if (!empty($productos)): ?>
      <?php foreach ($productos as $producto): ?>
        <?php
        $precioFinal   = isset($producto['precio']) ? (float)$producto['precio'] : 0.0;
        $precioTachado = (isset($producto['precio_tachado']) && $producto['precio_tachado'] !== '') ? (float)$producto['precio_tachado'] : null;
        $showTachado = ($precioTachado !== null && $precioTachado > $precioFinal) && !empty($producto['precio_tachado_visible']);

        // Calcular porcentaje de descuento si hay precio tachado
        $porcentajeDescuento = 0;
        if ($showTachado && $precioTachado > 0) {
          $porcentajeDescuento = round((($precioTachado - $precioFinal) / $precioTachado) * 100);
        }

        $imgSrc = url('uploads/default-product.png');
        if (!empty($producto['imagenes'])) {
          // Verificar si es un array con índice nombre_imagen (caso normal)
          if (isset($producto['imagenes'][0]['nombre_imagen'])) {
            $imgSrc = url('uploads/' . $producto['imagenes'][0]['nombre_imagen']);
          }
          // O si es directamente el string del nombre del archivo
          elseif (is_string($producto['imagenes'][0])) {
            // Limpiar la ruta si ya contiene /uploads/
            $nombreArchivo = $producto['imagenes'][0];
            if (strpos($nombreArchivo, '/uploads/') === 0) {
              $nombreArchivo = substr($nombreArchivo, 9); // Remover '/uploads/'
            }
            $imgSrc = url('uploads/' . $nombreArchivo);
          }
        } elseif (!empty($producto['imagen'])) {
          $imgSrc = url('uploads/' . $producto['imagen']);
        }

        // Asegurar que la descripción no esté vacía
        $descripcion = !empty($producto['descripcion']) ? $producto['descripcion'] : 'Producto de calidad disponible en nuestra tienda.';
        ?>

        <div class="product-card <?= !empty($producto['destacado']) ? 'is-featured' : '' ?>">
          <?php if (!empty($producto['destacado'])): ?>
            <div class="badge-featured">★</div>
          <?php endif; ?>

          <?php if ($showTachado && $porcentajeDescuento > 0): ?>
            <div class="badge-porcentaje">-<?= $porcentajeDescuento ?>%</div>
          <?php endif; ?>

          <a href="<?= url('producto/ver/' . $producto['id']) ?>" class="product-link">
            <div class="product-image-container">
              <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>" loading="lazy">
            </div>
            <div class="product-info">
              <h3 class="product-title"><?= htmlspecialchars($producto['nombre']) ?></h3>
              <p class="product-description"><?= htmlspecialchars($descripcion) ?></p>
              <div class="product-price">
                <span class="price-now">S/ <?= number_format($precioFinal, 2) ?></span>
                <?php if ($showTachado): ?>
                  <span class="price-old">S/ <?= number_format($precioTachado, 2) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </a>

          <!-- Formulario de agregar al carrito (funciona sin login) -->
          <form method="POST" action="<?= url('carrito/agregar') ?>" class="add-to-cart-form" >
            <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="quantity-section">
              <div class="quantity-controls">
                <button type="button" class="quantity-btn minus" title="Disminuir">-</button>
                <input type="number" name="cantidad" value="1" min="1" max="99" class="quantity-input" title="Cantidad" readonly>
                <button type="button" class="quantity-btn plus" title="Aumentar">+</button>
              </div>
              <button type="submit" class="add-button btn-agregar-carrito" data-id="<?= $producto['id'] ?>" title="Agregar al carrito">
                <i class="fas fa-cart-plus"></i>
                <span class="add-text">Agregar</span>
              </button>
            </div>
          </form>
          <style>
            .quantity-section {
              display: flex;
              gap: 8px;
              align-items: center;
              width: 100%;
            }

            .quantity-controls {
              display: flex;
              align-items: center;
              background: #f8f9fa;
              border-radius: 6px;
              border: 1px solid #e0e0e0;
              overflow: hidden;
              min-width: 90px;
            }

            .quantity-btn {
              background: #ffffff;
              border: none;
              width: 28px;
              height: 28px;
              display: flex;
              align-items: center;
              justify-content: center;
              cursor: pointer;
              font-size: 12px;
              font-weight: 600;
              color: #333;
              transition: all 0.2s ease;
              padding: 0;
            }

            .quantity-btn:hover {
              background: #2AC1DB;
              color: white;
            }

            .quantity-btn:active {
              transform: scale(0.95);
            }

            .quantity-input {
              width: 30px;
              height: 28px;
              border: none;
              text-align: center;
              background: #ffffff;
              font-size: 12px;
              font-weight: 600;
              color: #333;
              pointer-events: none;
              padding: 0;
            }

            .quantity-input:focus {
              outline: none;
            }

            .quantity-input::-webkit-outer-spin-button,
            .quantity-input::-webkit-inner-spin-button {
              -webkit-appearance: none;
              margin: 0;
            }

            .quantity-input[type=number] {
              -moz-appearance: textfield;
            }

            .add-button {
              background: #2AC1DB;
              color: white;
              border: none;
              padding: 6px 12px;
              border-radius: 6px;
              cursor: pointer;
              font-size: 12px;
              font-weight: 600;
              transition: all 0.2s ease;
              flex: 1;
              display: flex;
              align-items: center;
              justify-content: center;
              gap: 4px;
              min-height: 28px;
            }

            .add-button:hover {
              background: #24A8C1;
              transform: translateY(-1px);
            }

            .add-button:active {
              transform: translateY(0);
            }

            .add-text {
              font-size: 11px;
            }

            /* Responsive */
            @media (max-width: 768px) {
              .quantity-section {
                gap: 6px;
              }

              .quantity-controls {
                min-width: 80px;
              }

              .quantity-btn {
                width: 26px;
                height: 26px;
                font-size: 11px;
              }

              .quantity-input {
                width: 28px;
                height: 26px;
                font-size: 11px;
              }

              .add-button {
                padding: 5px 10px;
                font-size: 11px;
                min-height: 26px;
              }

              .add-text {
                font-size: 10px;
              }
            }

            @media (max-width: 480px) {
              .quantity-section {
                flex-direction: row;
                gap: 8px;
              }

              .quantity-controls {
                width: 100%;
                min-width: auto;
              }

              .add-button {
                width: 100%;
              }

              .add-text {
                display: inline;
                /* Siempre mostrar texto en móvil */
              }
            }

            /* Para pantallas muy pequeñas */
            @media (max-width: 360px) {
              .quantity-section {
                gap: 6px;
              }

              .quantity-controls {
                justify-content: space-between;
              }

              .quantity-btn {
                width: 32px;
              }

              .quantity-input {
                width: 40px;
              }
            }
          </style>
<script>
// Función para inicializar los controles de cantidad
function initQuantityControls() {
    // Remover event listeners existentes primero (para evitar duplicados)
    document.querySelectorAll('.quantity-controls').forEach(control => {
        // Clonar y reemplazar para limpiar event listeners
        const newControl = control.cloneNode(true);
        control.parentNode.replaceChild(newControl, control);
    });
    
    // Agregar nuevos event listeners
    document.querySelectorAll('.quantity-controls').forEach(control => {
        const minusBtn = control.querySelector('.minus');
        const plusBtn = control.querySelector('.plus');
        const quantityInput = control.querySelector('.quantity-input');
        
        // Verificar que los elementos existen
        if (!minusBtn || !plusBtn || !quantityInput) return;
        
        // Función para actualizar cantidad
        const updateQuantity = (change) => {
            let currentValue = parseInt(quantityInput.value) || 1;
            let newValue = currentValue + change;
            
            // Validar límites
            if (newValue < 1) newValue = 1;
            if (newValue > 99) newValue = 99;
            
            quantityInput.value = newValue;
        };
        
        // Remover event listeners existentes (por si acaso)
        minusBtn.replaceWith(minusBtn.cloneNode(true));
        plusBtn.replaceWith(plusBtn.cloneNode(true));
        
        // Obtener las nuevas referencias
        const newMinusBtn = control.querySelector('.minus');
        const newPlusBtn = control.querySelector('.plus');
        
        // Agregar event listeners
        newMinusBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            updateQuantity(-1);
        });
        
        newPlusBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            updateQuantity(1);
        });
        
        // Soporte para teclado
        newMinusBtn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                updateQuantity(-1);
            }
        });
        
        newPlusBtn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                updateQuantity(1);
            }
        });
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initQuantityControls();
});

// Si usas AJAX o cargas contenido dinámicamente, llama a initQuantityControls() después de agregar nuevo contenido
</script>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon"></div>
        <h3 class="empty-title">No hay productos disponibles</h3>
        <p class="empty-description">
          <?php if (!empty($_GET['termino']) || !empty($_GET['categoria']) || !empty($_GET['min_price']) || !empty($_GET['max_price'])): ?>
            No se encontraron productos que coincidan con los filtros aplicados. Intenta ajustar los criterios de búsqueda.
          <?php else: ?>
            Aún no se han agregado productos visibles al catálogo. Vuelve pronto para ver nuestras novedades.
          <?php endif; ?>
        </p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('.add-to-cart-form');

  forms.forEach(form => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const button = form.querySelector('.add-button, .btn-agregar');
      const cantidadInput = form.querySelector('.quantity-input');
      const productoId = form.querySelector('input[name="producto_id"]').value;
      const csrfToken = form.querySelector('input[name="csrf_token"]').value;
      const cantidad = cantidadInput ? parseInt(cantidadInput.value) || 1 : 1;
      const originalText = button ? button.innerHTML : '';

      // Estado de carga visual
      if (button) {
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
        button.disabled = true;
      }

      try {
        const response = await fetch(form.action || '<?= url("carrito/agregar") ?>', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: new URLSearchParams({
            producto_id: productoId,
            cantidad,
            csrf_token: csrfToken
          })
        });

        const data = await response.json();

        if (data.success) {
          // ✅ Actualiza contador visual del carrito
          const contador = document.querySelector('#contador-carrito');
          if (contador && typeof data.itemCount !== 'undefined') {
            contador.textContent = data.itemCount;
          }

          // ✅ Dispara evento global (por si otro script actualiza el header)
          document.dispatchEvent(new CustomEvent('cartUpdated', {
            detail: { count: data.itemCount || data.total_items || 0 }
          }));

          // ✅ Feedback visual
          if (button) {
            button.innerHTML = '<i class="fas fa-check"></i> Añadido';
            button.classList.add('added');
            setTimeout(() => {
              button.innerHTML = originalText;
              button.disabled = false;
              button.classList.remove('added');
            }, 1500);
          }
        } else {
          throw new Error(data.message || 'Error al agregar al carrito');
        }
      } catch (error) {
        console.error('❌ Error al agregar al carrito:', error);
        if (button) {
          button.innerHTML = '<i class="fas fa-times"></i> Error';
          setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
          }, 2000);
        }
      }
    });
  });

  // 🖼️ Lazy loading para imágenes
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src || img.src;
          img.classList.remove('lazy');
          observer.unobserve(img);
        }
      });
    });

    document.querySelectorAll('img[loading="lazy"]').forEach(img => {
      observer.observe(img);
    });
  }
});
</script>
