<link rel="stylesheet" href="<?= url('css/footer.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"> <!-- Font Awesome para iconos -->

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-content">
            <!-- Sección Superior: Título e Información de Contacto -->
            <div class="footer-top-section">
                <div class="footer-header">
                    <h5 class="footer-title">BYTEBOX</h5>
                    <p class="footer-subtitle">Tu tienda online de confianza para productos tecnológicos y novedades</p>
                </div>
                <div class="footer-info-grid">
                    <div class="footer-info-item">
                        <p class="info-label">Servicio al Cliente</p>
                        <p class="info-value">+51 942 507 022</p>
                    </div>
                    <div class="footer-info-item">
                        <p class="info-label">Email</p>
                        <p class="info-value">info@bytebox.com</p>
                    </div>
                    <div class="footer-info-item">
                        <p class="info-label">Horario</p>
                        <p class="info-value">Lun - Sáb: 9:00 AM - 8:00 PM</p>
                    </div>
                </div>
            </div>

            <!-- Sección Media: Columnas de Enlaces -->
            <div class="footer-links-grid">
                <div class="footer-links-column">
                    <h6 class="links-title">Productos</h6>
                    <ul class="links-list">
                        <li><a href="<?= url('home/busqueda?orden=ofertas') ?>">Ofertas</a></li>
                        <li><a href="<?= url('home/busqueda?orden=novedades') ?>">Novedades</a></li>
                        <li><a href="<?= url('home/busqueda?orden=mas_vendidos') ?>">Más vendidos</a></li>
                        <li><a href="<?= url('home/busqueda?orden=destacados') ?>">Destacados</a></li>
                    </ul>
                </div>
                <div class="footer-links-column">
                    <h6 class="links-title">Nosotros</h6>
                    <ul class="links-list">
                        <li><a href="<?= url('info/contacto') ?>">Contáctanos</a></li>
                        <li><a href="#" id="open-terms-footer">Términos y condiciones</a></li>
                    </ul>
                </div>
                <div class="footer-links-column">
                    <h6 class="links-title">Tu cuenta</h6>
                    <ul class="links-list">
                        <li><a href="<?= url('usuario/pedidos') ?>">Historial Pedidos</a></li>
                        <li><a href="<?= url('auth/profile') ?>">Mi cuenta</a></li>
                    </ul>
                </div>
            </div>

            <!-- Sección Inferior: Copyright y Redes Sociales -->
            <div class="footer-bottom">
                <p class="copyright">© 2025 Bytebox - Todos los derechos reservados</p>
                <div class="footer-meta">
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    </div>
                    <span class="version">Versión 2.0</span>
                    <span id="footer-date" class="date"></span>
                </div>
            </div>
        </div>

        <!-- Libro de Reclamaciones (fuera del padding principal para ocupar todo el ancho ) -->
        <div class="footer-reclamo-wrapper">
            <a href="<?= url('reclamacion/formulario') ?>" class="footer-reclamo-link">
                <i class="fas fa-book"></i> Libro de Reclamaciones
            </a>
        </div>
    </div>
</footer>

<!-- Modal de términos y condiciones -->
<div id="terms-modal-footer" class="modal-overlay" style="display: none;">
    <div class="modal-content-footer">
        <!-- Header del modal -->
        <div class="modal-header-footer">
            <div class="modal-title-section">
                <h2 class="modal-title"> Términos y Condiciones</h2>
                <p class="modal-subtitle">Términos de uso y políticas de nuestra tienda</p>
            </div>
            <button type="button" id="close-terms-modal-footer" class="modal-close-btn">
                &times;
            </button>
        </div>
        
        <!-- Contenido del modal -->
        <div class="modal-body-footer">
            <div class="terms-content">
                <div class="terms-section">
                    <h3 class="terms-title">
                        <span class="terms-number">1</span>
                        Aceptación de los Términos
                    </h3>
                    <p class="terms-text">
                        Al acceder y utilizar el sitio web de <strong>ByteBox</strong>, usted acepta cumplir y estar 
                        sujeto a los presentes términos y condiciones de uso. Estos términos se aplican a todos los 
                        usuarios del sitio, incluidos, entre otros, los usuarios que son navegadores, proveedores, 
                        clientes, comerciantes y/o contribuyentes de contenido. Si no está de acuerdo con alguna 
                        parte de estos términos, no debe utilizar nuestros servicios.
                    </p>
                    <p class="terms-text">
                        Le recomendamos leer estos términos detenidamente antes de realizar cualquier compra o 
                        utilizar nuestros servicios. Al realizar un pedido a través de nuestro sitio, usted declara 
                        ser mayor de edad y tener capacidad legal para celebrar contratos vinculantes.
                    </p>
                </div>
                
                <div class="terms-section">
                    <h3 class="terms-title">
                        <span class="terms-number">2</span>
                        Productos y Servicios
                    </h3>
                    <div class="terms-text">
                        <p>ByteBox se reserva el derecho de modificar o discontinuar cualquier producto o servicio sin previo aviso:</p>
                        <ul class="terms-list">
                            <li>Todos los precios mostrados están expresados en soles peruanos (S/) e incluyen el Impuesto General a las Ventas (IGV) del 18%</li>
                            <li>Los precios de los productos están sujetos a cambios sin previo aviso, aunque respetaremos el precio vigente al momento de su compra</li>
                            <li>La disponibilidad de productos está sujeta a existencias en nuestro almacén. En caso de agotamiento, le notificaremos a la brevedad</li>
                            <li>Nos reservamos el derecho de limitar las cantidades de compra por producto o por cliente para prevenir reventas no autorizadas</li>
                            <li>Las imágenes y descripciones de productos son referenciales. Nos esforzamos por mostrar información precisa, pero pueden existir pequeñas variaciones</li>
                            <li>Los productos tecnológicos cuentan con garantía del fabricante según las especificaciones de cada marca</li>
                            <li>Algunos productos pueden requerir instalación o configuración adicional no incluida en el precio base</li>
                        </ul>
                    </div>
                </div>
                
                <div class="terms-section">
                    <h3 class="terms-title">
                        <span class="terms-number">3</span>
                        Proceso de Compra y Pago
                    </h3>
                    <div class="terms-text">
                        <p>Al realizar una compra en ByteBox, usted acepta proporcionar información de pago válida y completa:</p>
                        <ul class="terms-list">
                            <li>Aceptamos tarjetas de crédito y débito (Visa, Mastercard, American Express), transferencias bancarias y pagos en efectivo contra entrega</li>
                            <li>Para pagos con tarjeta, utilizamos plataformas de pago seguras certificadas PCI DSS que encriptan su información</li>
                            <li>En pagos contra entrega, se acepta cambio hasta S/ 100. El monto debe ser pagado al momento de recibir el producto</li>
                            <li>Las transferencias bancarias deben realizarse dentro de las 24 horas posteriores a la confirmación del pedido</li>
                            <li>Una vez confirmado el pago, recibirá un correo electrónico con los detalles de su pedido y número de seguimiento</li>
                            <li>En caso de que su pago sea rechazado, le notificaremos inmediatamente para que pueda utilizar otro método de pago</li>
                            <li>ByteBox no almacena datos completos de tarjetas de crédito en nuestros servidores</li>
                        </ul>
                    </div>
                </div>
                
                <div class="terms-section">
                    <h3 class="terms-title">
                        <span class="terms-number">4</span>
                        Envío y Entrega
                    </h3>
                    <div class="terms-text">
                        <p>Nuestro compromiso es entregar su pedido en las mejores condiciones y en el menor tiempo posible:</p>
                        <ul class="terms-list">
                            <li><strong>Envío gratuito</strong> a todo el Perú en compras mayores a S/ 100. Para montos menores, se aplicará una tarifa según destino</li>
                            <li>Tiempo de entrega estimado: 2-5 días hábiles en Lima Metropolitana y Callao, 3-7 días hábiles en provincias</li>
                            <li>Los envíos se realizan de Lunes a Viernes en horario de 9:00 AM a 6:00 PM. Sábados hasta las 2:00 PM (solo Lima)</li>
                            <li>Es necesario que una persona mayor de edad esté presente para recibir el pedido y firmar el comprobante de entrega</li>
                            <li>Realizamos hasta 2 intentos de entrega gratuitos. Si no se encuentra a nadie, coordinaremos un nuevo horario</li>
                            <li>Para productos de alto valor, puede requerirse presentación de DNI del titular de la compra</li>
                            <li>ByteBox no se hace responsable por direcciones incorrectas proporcionadas por el cliente</li>
                            <li>Podrá rastrear su pedido en tiempo real a través del número de seguimiento proporcionado</li>
                        </ul>
                    </div>
                </div>
                
                <div class="terms-section">
                    <h3 class="terms-title">
                        <span class="terms-number">5</span>
                        Política de Devoluciones y Garantías
                    </h3>
                    <div class="terms-text">
                        <p>Su satisfacción es nuestra prioridad. Ofrecemos las siguientes políticas de devolución:</p>
                        <ul class="terms-list">
                            <li>Plazo para solicitar devolución: <strong>30 días calendario</strong> desde la fecha de recepción del producto</li>
                            <li>Los productos deben estar en perfecto estado, sin uso, con etiquetas originales y en su embalaje original</li>
                            <li>No se aceptan devoluciones de productos personalizados, software sin sellar, o artículos de higiene personal</li>
                            <li>Los gastos de envío para devoluciones por cambio de opinión corren por cuenta del cliente</li>
                            <li>Si el producto llega defectuoso o dañado, ByteBox cubre el costo del envío de devolución</li>
                            <li>El reembolso se realizará por el mismo medio de pago utilizado, en un plazo de 5 a 10 días hábiles</li>
                            <li>Puede optar por cambio de producto, crédito en tienda o reembolso completo</li>
                            <li>Todos los productos cuentan con garantía del fabricante. Los plazos varían según marca y producto</li>
                            <li>Para hacer efectiva la garantía, debe presentar el comprobante de compra y reportar el problema dentro del periodo de garantía</li>
                        </ul>
                    </div>
                </div>
                
                <div class="terms-section">
                    <h3 class="terms-title">
                        <span class="terms-number">6</span>
                        Protección de Datos Personales
                    </h3>
                    <div class="terms-text">
                        <p>ByteBox se compromete a proteger su privacidad conforme a la Ley N° 29733 - Ley de Protección de Datos Personales del Perú:</p>
                        <ul class="terms-list">
                            <li>Recopilamos datos personales como nombre, dirección, teléfono, email y datos de pago únicamente para procesar pedidos</li>
                            <li>Sus datos serán utilizados para: procesar compras, enviar confirmaciones, actualizar sobre el estado de pedidos y mejorar nuestros servicios</li>
                            <li>No compartimos, vendemos ni alquilamos su información personal a terceros sin su consentimiento explícito</li>
                            <li>Utilizamos medidas de seguridad técnicas, administrativas y físicas para proteger su información contra accesos no autorizados</li>
                            <li>Puede solicitar acceso, rectificación, cancelación u oposición al tratamiento de sus datos contactándonos</li>
                            <li>Utilizamos cookies para mejorar su experiencia de navegación. Puede desactivarlas en su navegador</li>
                            <li>Sus datos se almacenan en servidores seguros con encriptación SSL/TLS</li>
                            <li>ByteBox no se hace responsable por vulneraciones de seguridad causadas por terceros malintencionados</li>
                        </ul>
                    </div>
                </div>
                
                <div class="terms-section">
                    <h3 class="terms-title">
                        <span class="terms-number">7</span>
                        Propiedad Intelectual
                    </h3>
                    <div class="terms-text">
                        <p>Todo el contenido del sitio web ByteBox está protegido por derechos de propiedad intelectual:</p>
                        <ul class="terms-list">
                            <li>El diseño, logotipos, textos, gráficos, imágenes y código fuente son propiedad exclusiva de ByteBox</li>
                            <li>Las marcas comerciales de productos pertenecen a sus respectivos fabricantes</li>
                            <li>Queda prohibida la reproducción, distribución o modificación del contenido sin autorización escrita</li>
                            <li>No puede utilizar nuestros contenidos con fines comerciales sin permiso expreso</li>
                            <li>Los comentarios y reseñas publicados por usuarios son de su exclusiva responsabilidad</li>
                        </ul>
                    </div>
                </div>
                
                <div class="terms-section">
                    <h3 class="terms-title">
                        <span class="terms-number">8</span>
                        Responsabilidades y Limitaciones
                    </h3>
                    <div class="terms-text">
                        <ul class="terms-list">
                            <li>ByteBox no se hace responsable por daños causados por mal uso, negligencia o accidentes con los productos</li>
                            <li>Nuestra responsabilidad se limita al valor del producto adquirido, excluyendo daños indirectos o consecuenciales</li>
                            <li>No garantizamos que el sitio web esté libre de interrupciones, errores o virus informáticos</li>
                            <li>Los enlaces a sitios web de terceros son proporcionados para su conveniencia. No nos hacemos responsables de su contenido</li>
                            <li>El usuario es responsable de mantener la confidencialidad de su cuenta y contraseña</li>
                            <li>Debe notificarnos inmediatamente si detecta uso no autorizado de su cuenta</li>
                        </ul>
                    </div>
                </div>
                
                <div class="terms-section">
                    <h3 class="terms-title">
                        <span class="terms-number">9</span>
                        Contacto y Atención al Cliente
                    </h3>
                    <div class="terms-text">
                        <p>Estamos aquí para ayudarle. Puede contactarnos a través de los siguientes medios:</p>
                        <ul class="terms-list">
                            <li><strong>Correo electrónico:</strong> info@bytebox.com</li>
                            <li><strong>Teléfono y WhatsApp:</strong> +51 999 123 456</li>
                            <li><strong>Horario de atención:</strong> Lunes a Sábado de 9:00 AM a 8:00 PM</li>
                            <li><strong>Chat en línea:</strong> Disponible en nuestro sitio web durante horario de atención</li>
                            <li><strong>Libro de reclamaciones:</strong> Disponible en nuestra tienda online según normativa peruana</li>
                            <li>Tiempo de respuesta promedio: 24-48 horas hábiles</li>
                        </ul>
                    </div>
                </div>
                
                <div class="terms-section">
                    <h3 class="terms-title">
                        <span class="terms-number">10</span>
                        Modificaciones y Legislación Aplicable
                    </h3>
                    <div class="terms-text">
                        <p>ByteBox se reserva el derecho de modificar estos términos y condiciones en cualquier momento:</p>
                        <ul class="terms-list">
                            <li>Los cambios entrarán en vigor inmediatamente después de su publicación en el sitio web</li>
                            <li>Le recomendamos revisar periódicamente esta página para estar informado de cualquier actualización</li>
                            <li>El uso continuado del sitio después de las modificaciones constituye su aceptación de los nuevos términos</li>
                            <li>Estos términos se rigen por las leyes de la República del Perú</li>
                            <li>Cualquier controversia será resuelta en los tribunales de Lima, Perú</li>
                            <li>Si alguna disposición resulta inválida, el resto de términos permanecerá en vigor</li>
                        </ul>
                        <p style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-left: 4px solid #2ac1db; border-radius: 8px;">
                            <strong>Nota importante:</strong> Al realizar una compra en ByteBox, usted confirma que ha leído, 
                            entendido y aceptado estos términos y condiciones en su totalidad. Si tiene alguna pregunta o 
                            inquietud, no dude en contactarnos antes de completar su compra.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer del modal -->
        <div class="modal-footer-terms">
            <p class="modal-footer-text">
                <i class="fas fa-info-circle"></i>
                Última actualización: Octubre 2025
            </p>
            <button type="button" id="close-terms-btn-footer" class="btn-close-terms">
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- Script para el footer (sin cambios) -->
<script>
    function updateFooterDate() {
        const now = new Date();
        const dateString = now.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
        const footerDateElement = document.getElementById('footer-date');
        if (footerDateElement) {
            footerDateElement.textContent = dateString;
        }
    }
    updateFooterDate();

    // Modal de términos y condiciones
    document.addEventListener('DOMContentLoaded', function() {
        const termsModal = document.getElementById('terms-modal-footer');
        const openTermsLink = document.getElementById('open-terms-footer');
        const closeTermsModal = document.getElementById('close-terms-modal-footer');
        const closeTermsBtn = document.getElementById('close-terms-btn-footer');

        // Abrir modal
        if (openTermsLink) {
            openTermsLink.addEventListener('click', function(e) {
                e.preventDefault();
                termsModal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Prevenir scroll del body
            });
        }

        // Función para cerrar modal
        function closeModal() {
            termsModal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restaurar scroll del body
        }

        // Cerrar modal con X
        if (closeTermsModal) {
            closeTermsModal.addEventListener('click', closeModal);
        }
        
        // Cerrar modal con botón
        if (closeTermsBtn) {
            closeTermsBtn.addEventListener('click', closeModal);
        }

        // Cerrar modal al hacer clic fuera
        if (termsModal) {
            termsModal.addEventListener('click', function(e) {
                if (e.target === termsModal) {
                    closeModal();
                }
            });
        }

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && termsModal.style.display === 'flex') {
                closeModal();
            }
        });
    });

    (function() {
  // Se ejecuta inmediatamente al ser leído
  document.addEventListener("cartUpdated", function(e) {
    const count = Number(e.detail?.count ?? e.detail?.itemCount ?? 0);
    let countEl = document.getElementById('cart-count');
    const cartBtn = document.querySelector('.cart-button') || document.querySelector('.cart-section a');

    if (!countEl && cartBtn) {
      countEl = document.createElement('span');
      countEl.id = 'cart-count';
      countEl.className = 'cart-badge';
      countEl.setAttribute('aria-live', 'polite');
      countEl.setAttribute('aria-atomic', 'true');
      cartBtn.appendChild(countEl);
    }

    if (countEl) {
      countEl.textContent = count > 0 ? count : '';
      countEl.style.display = count > 0 ? 'inline-block' : 'none';
      countEl.style.transform = 'scale(1.2)';
      setTimeout(() => (countEl.style.transform = ''), 180);
    }

    // Confirmar recepción (debug)
    document.dispatchEvent(new CustomEvent('cartUpdatedAck', { detail: { ack: true } }));
  });

  document.addEventListener('DOMContentLoaded', () => {
    console.log('[carrito] Listener activo. #cart-count existe:', !!document.getElementById('cart-count'));
  });
})();

</script>

<!-- Estilos para el modal de términos -->
<style>
    /* Importar fuentes si no están disponibles */
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
    .cart-badge {
    display: inline-flex;
    background-color: #ff4b4b;
    color: #fff;
    text-align: center;
    vertical-align: middle;
    transition: transform 0.15s ease, background-color 0.15s ease;
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(27, 27, 27, 0.8);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: fadeInModal 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(5px);
    }

    .modal-content-footer {
        background: #ffffff;
        border-radius: 20px;
        width: 100%;
        max-width: 900px;
        max-height: 90vh;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        animation: slideInModal 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .modal-content-footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #2ac1db, #363993);
        animation: shimmerModal 3s ease-in-out infinite;
    }

    .modal-header-footer {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        color: #1b1b1b;
        padding: 30px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        position: relative;
        overflow: hidden;
        border-bottom: 2px solid #e9ecef;
    }

    .modal-header-footer::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(ellipse at top right, rgba(42, 193, 219, 0.08) 0%, transparent 70%);
        pointer-events: none;
    }

    .modal-title-section {
        flex: 1;
        position: relative;
        z-index: 1;
    }

    .modal-title {
        font-family: 'Orbitron', sans-serif;
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 10px 0;
        display: flex;
        align-items: center;
        gap: 12px;
        background: linear-gradient(135deg, #2ac1db, #363993);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .modal-subtitle {
        font-family: 'Outfit', sans-serif;
        font-size: 1rem;
        margin: 0;
        font-weight: 400;
        color: #6c757d;
    }

    .modal-close-btn {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        color: #495057;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 1;
    }

    .modal-close-btn:hover {
        background: #ffffff;
        border-color: #2ac1db;
        color: #2ac1db;
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(42, 193, 219, 0.2);
    }

    .modal-body-footer {
        flex: 1;
        overflow-y: auto;
        padding: 0;
        max-height: calc(90vh - 220px);
        background: #ffffff;
    }

    .terms-content {
        padding: 40px;
        font-family: 'Outfit', sans-serif;
    }

    .terms-section {
        margin-bottom: 35px;
        padding-bottom: 25px;
        border-bottom: 2px solid #f0f0f0;
        position: relative;
    }

    .terms-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .terms-title {
        font-family: 'Orbitron', sans-serif;
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: #1b1b1b;
        display: flex;
        align-items: center;
        gap: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .terms-number {
        background: linear-gradient(135deg, #2ac1db, #363993);
        color: #ffffff;
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: bold;
        flex-shrink: 0;
        box-shadow: 0 4px 15px rgba(42, 193, 219, 0.3);
        position: relative;
    }

    .terms-number::after {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 14px;
        background: linear-gradient(135deg, #2ac1db, #363993);
        z-index: -1;
        opacity: 0.3;
    }

    .terms-text {
        color: #4a4d50;
        line-height: 1.8;
        font-size: 1rem;
        text-align: justify;
        margin: 0;
        font-weight: 400;
    }

    .terms-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .terms-list li {
        padding: 12px 0;
        padding-left: 30px;
        position: relative;
        color: #4a4d50;
        line-height: 1.7;
        font-size: 1rem;
        transition: all 0.2s ease;
    }

    .terms-list li:hover {
        color: #1b1b1b;
        padding-left: 35px;
    }

    .terms-list li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #2ac1db;
        font-weight: bold;
        font-size: 1.1rem;
        top: 12px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: rgba(42, 193, 219, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }

    .modal-footer-terms {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 25px 40px;
        border-top: 2px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        position: relative;
    }

    .modal-footer-terms::before {
        content: '';
        position: absolute;
        top: 0;
        left: 20%;
        right: 20%;
        height: 1px;
        background: linear-gradient(90deg, transparent, #2ac1db, transparent);
    }

    .modal-footer-text {
        color: #6c757d;
        font-size: 0.9rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: 'Outfit', sans-serif;
        font-weight: 500;
    }

    .modal-footer-text i {
        color: #2ac1db;
        font-size: 1rem;
    }

    .btn-close-terms {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: #ffffff;
        border: none;
        padding: 12px 24px;
        border-radius: 12px;
        cursor: pointer;
        font-weight: 600;
        font-family: 'Outfit', sans-serif;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        position: relative;
        overflow: hidden;
    }

    .btn-close-terms::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .btn-close-terms:hover {
        background: linear-gradient(135deg, #495057, #343a40);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
    }

    .btn-close-terms:hover::before {
        left: 100%;
    }

    /* Animaciones mejoradas */
    @keyframes fadeInModal {
        from { 
            opacity: 0;
        }
        to { 
            opacity: 1;
        }
    }

    @keyframes slideInModal {
        from { 
            transform: translateY(-60px) scale(0.95);
            opacity: 0;
        }
        to { 
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }

    @keyframes shimmerModal {
        0%, 100% { 
            opacity: 1; 
            transform: scaleX(1); 
        }
        50% { 
            opacity: 0.8; 
            transform: scaleX(0.98); 
        }
    }

    /* Scrollbar personalizada */
    .modal-body-footer::-webkit-scrollbar {
        width: 8px;
    }

    .modal-body-footer::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .modal-body-footer::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #2ac1db, #363993);
        border-radius: 4px;
    }

    .modal-body-footer::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #1fa8c1, #2d2d75);
    }

    /* Responsive mejorado */
    @media (max-width: 768px) {
        .modal-overlay {
            padding: 15px;
        }

        .modal-content-footer {
            max-height: 95vh;
            border-radius: 15px;
        }

        .modal-header-footer {
            padding: 25px 20px;
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }

        .modal-title {
            font-size: 1.6rem;
            justify-content: center;
        }

        .modal-subtitle {
            text-align: center;
        }

        .terms-content {
            padding: 25px 20px;
        }

        .terms-title {
            font-size: 1.2rem;
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .terms-number {
            width: 35px;
            height: 35px;
            font-size: 1rem;
        }

        .modal-footer-terms {
            padding: 20px;
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .btn-close-terms {
            width: 100%;
            padding: 15px;
        }

        .terms-list li {
            padding-left: 25px;
        }

        .terms-list li:hover {
            padding-left: 28px;
        }
    }

    @media (max-width: 480px) {
        .modal-title {
            font-size: 1.4rem;
        }

        .terms-content {
            padding: 20px 15px;
        }

        .modal-header-footer {
            padding: 20px 15px;
        }

        .modal-footer-terms {
            padding: 15px;
        }
    }
</style>
