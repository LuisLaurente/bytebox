<?php
use Core\Helpers\CookieHelper;
?>

<?php if (!CookieHelper::exists('cookies_consent')): ?>
<div id="cookies-banner" class="cookies-banner" style="
    position: fixed; 
    bottom: 0; 
    left: 0; 
    right: 0; 
    background: #ffffff;
    border-top: 3px solid #2ac1db;
    padding: 20px;
    z-index: 1060;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
    font-family: 'Outfit', sans-serif;
">
    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 20px;">
            <div style="flex: 1;">
                <h5 style="
                    margin: 0 0 8px 0; 
                    color: #1b1b1b; 
                    font-size: 18px; 
                    font-weight: 600;
                    font-family: 'Orbitron', monospace;
                    background: #1b1b1b;
                    color: #ffffff;
                    padding: 8px 16px;
                    border-radius: 6px;
                    display: inline-block;
                ">
                    🍪 POLÍTICA DE COOKIES
                </h5>
                <p style="
                    margin: 0; 
                    color: #1b1b1b; 
                    font-size: 14px; 
                    line-height: 1.5;
                    font-weight: 400;
                ">
                    Utilizamos cookies esenciales y analíticas para mejorar tu experiencia, 
                    recordar tu carrito de compras y preferencias de inicio de sesión. 
                    <a href="/privacidad" style="
                        color: #2ac1db; 
                        text-decoration: none;
                        font-weight: 500;
                        transition: color 0.3s ease;
                    " onmouseover="this.style.color='#1b1b1b'" onmouseout="this.style.color='#2ac1db'">
                        Más información
                    </a>
                </p>
            </div>
            <div style="display: flex; gap: 12px; margin-left: 20px; flex-shrink: 0;">
                <button type="button" onclick="rechazarCookies()" style="
                    padding: 10px 20px;
                    background: transparent;
                    border: 2px solid #1b1b1b;
                    color: #1b1b1b;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 500;
                    font-family: 'Outfit', sans-serif;
                    transition: all 0.3s ease;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                " onmouseover="this.style.background='#1b1b1b'; this.style.color='#ffffff'" 
                   onmouseout="this.style.background='transparent'; this.style.color='#1b1b1b'">
                    Rechazar
                </button>
                <button type="button" onclick="aceptarCookies()" style="
                    padding: 10px 24px;
                    background: #2ac1db;
                    border: 2px solid #2ac1db;
                    color: #ffffff;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: 600;
                    font-family: 'Outfit', sans-serif;
                    transition: all 0.3s ease;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    box-shadow: 0 2px 8px rgba(42, 193, 219, 0.3);
                " onmouseover="this.style.background='#1b1b1b'; this.style.borderColor='#1b1b1b'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(27, 27, 27, 0.4)'" 
                   onmouseout="this.style.background='#2ac1db'; this.style.borderColor='#2ac1db'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(42, 193, 219, 0.3)'">
                    Aceptar Cookies
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap');

.cookies-banner {
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .cookies-banner > div > div {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .cookies-banner > div > div > div:last-child {
        margin-left: 0;
        justify-content: center;
    }
    
    .cookies-banner h5 {
        font-size: 16px !important;
        padding: 6px 12px !important;
    }
    
    .cookies-banner p {
        font-size: 13px !important;
    }
    
    .cookies-banner button {
        padding: 8px 16px !important;
        font-size: 13px !important;
    }
}
</style>

<script>
function aceptarCookies() {
    fetch('/auth/aceptar-cookies', { 
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animación de salida
            const banner = document.getElementById('cookies-banner');
            banner.style.transform = 'translateY(100%)';
            banner.style.opacity = '0';
            setTimeout(() => {
                banner.style.display = 'none';
            }, 500);
            // Recargar para aplicar cookies inmediatamente
            setTimeout(() => location.reload(), 300);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('cookies-banner').style.display = 'none';
    });
}

function rechazarCookies() {
    fetch('/auth/rechazar-cookies', { 
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animación de salida
            const banner = document.getElementById('cookies-banner');
            banner.style.transform = 'translateY(100%)';
            banner.style.opacity = '0';
            setTimeout(() => {
                banner.style.display = 'none';
            }, 500);
            // Limpiar cookies existentes
            document.cookie = "cart_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "remember_me=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('cookies-banner').style.display = 'none';
    });
}
</script>

<script>
function aceptarCookies() {
    fetch('<?= url('auth/aceptar-cookies') ?>', { 
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cookies-banner').style.display = 'none';
            // Recargar para aplicar cookies inmediatamente
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('cookies-banner').style.display = 'none';
    });
}

function rechazarCookies() {
    fetch('<?= url('auth/rechazar-cookies') ?>', { 
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cookies-banner').style.display = 'none';
            // Limpiar cookies existentes
            document.cookie = "cart_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "remember_me=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('cookies-banner').style.display = 'none';
    });
}
</script>
<?php endif; ?>