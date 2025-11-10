document.addEventListener('DOMContentLoaded', function() {
    const minPriceInput = document.getElementById('min_price');
    const maxPriceInput = document.getElementById('max_price');
    const categoriaSelect = document.getElementById('categoria');
    const ordenSelect = document.getElementById('orden');
    const disponiblesCheckbox = document.getElementById('disponibles');
    const etiquetaCheckboxes = document.querySelectorAll('.etiqueta-checkbox');
    const btnFiltrar = document.getElementById('btnFiltrar');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const loading = document.getElementById('loading');
    const productosContainer = document.getElementById('productosContainer');
    const errorFiltros = document.getElementById('errorFiltros');
    const filtrosActivos = document.getElementById('filtrosActivos');
    const infoFiltros = document.getElementById('infoFiltros');
    const listaErrores = document.getElementById('listaErrores');

    let filtroTimeout = null;

    // Función para mostrar/ocultar loading
    function toggleLoading(show) {
        if (loading) loading.style.display = show ? 'inline' : 'none';
        if (btnFiltrar) btnFiltrar.disabled = show;
        if (btnLimpiar) btnLimpiar.disabled = show;
    }

    // Función para mostrar errores
    function mostrarErrores(errores) {
        if (!errorFiltros || !listaErrores) return;
        
        if (errores && errores.length > 0) {
            listaErrores.innerHTML = '';
            errores.forEach(error => {
                const li = document.createElement('li');
                li.textContent = error;
                listaErrores.appendChild(li);
            });
            errorFiltros.style.display = 'block';
        } else {
            errorFiltros.style.display = 'none';
        }
    }

    // Función para mostrar filtros activos
    function mostrarFiltrosActivos(filtros, total) {
        if (!filtrosActivos || !infoFiltros) return;
        
        let hayFiltros = false;
        let info = '';
        
        if (filtros.min_price && filtros.min_price > 0) {
            info += `<span class="filtro-tag">
                Mínimo: S/ ${parseFloat(filtros.min_price).toFixed(2)}
            </span>`;
            hayFiltros = true;
        }
        
        if (filtros.max_price && filtros.max_price > 0) {
            info += `<span class="filtro-tag">
                Máximo: S/ ${parseFloat(filtros.max_price).toFixed(2)}
            </span>`;
            hayFiltros = true;
        }
        
        if (filtros.categoria) {
            const categoriaOption = categoriaSelect.querySelector(`option[value="${filtros.categoria}"]`);
            const categoriaNombre = categoriaOption ? categoriaOption.textContent : 'Categoría seleccionada';
            info += `<span class="filtro-tag">
                Categoría: ${categoriaNombre}
            </span>`;
            hayFiltros = true;
        }

        if (filtros.etiquetas && filtros.etiquetas.length > 0) {
            info += `<span class="filtro-tag">
                Etiquetas: ${filtros.etiquetas.length}
            </span>`;
            hayFiltros = true;
        }

        if (filtros.disponibles) {
            info += `<span class="filtro-tag">
                Solo disponibles
            </span>`;
            hayFiltros = true;
        }

        if (filtros.orden) {
            const ordenOption = ordenSelect.querySelector(`option[value="${filtros.orden}"]`);
            const ordenNombre = ordenOption ? ordenOption.textContent : 'Ordenamiento';
            info += `<span class="filtro-tag">
                Orden: ${ordenNombre}
            </span>`;
            hayFiltros = true;
        }
        
        if (total !== undefined) {
            info += `<span class="filtro-tag total">
                ${total} producto(s) encontrado(s)
            </span>`;
        }
        
        if (hayFiltros) {
            infoFiltros.innerHTML = info;
            filtrosActivos.style.display = 'block';
        } else {
            filtrosActivos.style.display = 'none';
        }
    }

    // Función para renderizar productos
    function renderizarProductos(productos) {
        if (!productosContainer) return;
        
        if (!productos || productos.length === 0) {
            productosContainer.innerHTML = '<p>No hay productos disponibles.</p>';
            return;
        }

        let html = '';
        productos.forEach(producto => {
            html += `
                <div class="producto-card">
                    <strong>${producto.nombre || ''}</strong><br>
                    ${producto.descripcion || ''}<br>
                    Precio: S/ ${parseFloat(producto.precio || 0).toFixed(2)}<br>
                    Visible: ${producto.visible ? 'Sí' : 'No'}<br>
                    
                    ${producto.categorias && producto.categorias.length > 0 ? 
                        `<div class="categoria-lista">Categorías: ${producto.categorias.join(', ')}</div>` : 
                        '<div class="categoria-lista">Sin categoría</div>'
                    }

                    <div class="acciones">
                        <a href="/producto/editar/${producto.id}">Editar</a> |
                        <a href="/producto/eliminar/${producto.id}" onclick="return confirm('¿Estás seguro de eliminar este producto?')">Eliminar</a>
                    </div>
                </div>
            `;
        });
        productosContainer.innerHTML = html;
    }

    // Función para obtener etiquetas seleccionadas
    function getEtiquetasSeleccionadas() {
        const etiquetas = [];
        etiquetaCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                etiquetas.push(checkbox.value);
            }
        });
        return etiquetas;
    }

    // Función para realizar filtrado AJAX
    function filtrarProductos() {
        const minPrice = minPriceInput ? minPriceInput.value.trim() : '';
        const maxPrice = maxPriceInput ? maxPriceInput.value.trim() : '';
        const categoria = categoriaSelect ? categoriaSelect.value : '';
        const orden = ordenSelect ? ordenSelect.value : '';
        const disponibles = disponiblesCheckbox ? disponiblesCheckbox.checked : false;
        const etiquetas = getEtiquetasSeleccionadas();

        toggleLoading(true);
        mostrarErrores([]);

        // Construir parámetros
        const params = new URLSearchParams();
        if (minPrice) params.append('min_price', minPrice);
        if (maxPrice) params.append('max_price', maxPrice);
        if (categoria) params.append('categoria', categoria);
        if (orden) params.append('orden', orden);
        if (disponibles) params.append('disponibles', '1');
        etiquetas.forEach(etiqueta => params.append('etiquetas[]', etiqueta));
        params.append('ajax', '1');

        // Realizar petición AJAX
        const baseUrl = document.querySelector('body').getAttribute('data-base-url') + 'producto';

        const fullUrl = baseUrl + '?' + params.toString();
        
        fetch(fullUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Respuesta no JSON recibida:', text.substring(0, 200));
                    throw new Error('Respuesta no es JSON válido');
                });
            }
            
            return response.json();
        })
        .then(data => {
            toggleLoading(false);
            
            if (data.success) {
                renderizarProductos(data.productos);
                mostrarFiltrosActivos(data.filtros, data.total);
                mostrarErrores([]);
            } else {
                mostrarErrores(data.errores || ['Error al filtrar productos']);
            }
        })
        .catch(error => {
            toggleLoading(false);
            console.error('Error completo:', error);
            mostrarErrores([`Error de conexión: ${error.message}`]);
        });
    }

    // Función para limpiar filtros
    function limpiarFiltros() {
        if (minPriceInput) minPriceInput.value = '';
        if (maxPriceInput) maxPriceInput.value = '';
        if (categoriaSelect) categoriaSelect.value = '';
        if (ordenSelect) ordenSelect.value = '';
        if (disponiblesCheckbox) disponiblesCheckbox.checked = false;
        etiquetaCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        toggleLoading(true);
        mostrarErrores([]);
        mostrarFiltrosActivos({}, 0);

        const baseUrl = document.querySelector('body').getAttribute('data-base-url') + 'producto';

        const fullUrl = baseUrl + '?ajax=1';

        fetch(fullUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Respuesta no JSON recibida:', text.substring(0, 200));
                    throw new Error('Respuesta no es JSON válido');
                });
            }
            
            return response.json();
        })
        .then(data => {
            toggleLoading(false);
            if (data.success) {
                renderizarProductos(data.productos);
                mostrarFiltrosActivos({}, data.total);
            }
        })
        .catch(error => {
            toggleLoading(false);
            console.error('Error:', error);
            mostrarErrores(['Error de conexión. Intenta nuevamente.']);
        });
    }

    // Función para filtrado en tiempo real con debounce
    function filtrarEnTiempoReal() {
        clearTimeout(filtroTimeout);
        filtroTimeout = setTimeout(filtrarProductos, 800); // Espera 800ms después de que el usuario deje de escribir
    }

    // Event listeners
    if (btnFiltrar) btnFiltrar.addEventListener('click', filtrarProductos);
    if (btnLimpiar) btnLimpiar.addEventListener('click', limpiarFiltros);

    // Filtrado automático al cambiar selectores
    if (categoriaSelect) categoriaSelect.addEventListener('change', filtrarProductos);
    if (ordenSelect) ordenSelect.addEventListener('change', filtrarProductos);
    if (disponiblesCheckbox) disponiblesCheckbox.addEventListener('change', filtrarProductos);

    // Event listeners para etiquetas
    etiquetaCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', filtrarProductos);
    });

    // Filtrado en tiempo real para inputs de precio
    if (minPriceInput) {
        minPriceInput.addEventListener('input', filtrarEnTiempoReal);
        minPriceInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(filtroTimeout);
                filtrarProductos();
            }
        });
    }

    if (maxPriceInput) {
        maxPriceInput.addEventListener('input', filtrarEnTiempoReal);
        maxPriceInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(filtroTimeout);
                filtrarProductos();
            }
        });
    }

    // Cargar productos iniciales si la página se carga sin filtros
    window.addEventListener('load', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('min_price') && !urlParams.has('max_price') && !urlParams.has('categoria') && !urlParams.has('orden')) {
            // Solo mostrar filtros activos si no hay filtros en la URL
            mostrarFiltrosActivos({}, productosContainer.children.length);
        }
    });
});
