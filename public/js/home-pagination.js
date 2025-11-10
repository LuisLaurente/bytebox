// public/js/home-pagination.js
(() => {
  const productsSelector = '#productsWrapper';
  const topSelector = '.pagination-top';
  const bottomSelector = '.pagination-bottom';
  const filtroForm = document.getElementById('filtroForm');
  const baseUrl = window.location.pathname + ''; // /TECNOVEDADES/public/index.php? (use helper in PHP to generate absolute if needed)

  async function fetchPage(href, push = true) {
    try {
      const url = new URL(href, window.location.origin);
      url.searchParams.set('ajax', '1');

      const res = await fetch(url.toString(), { credentials: 'same-origin' });
      if (!res.ok) throw new Error('Network error');

      const data = await res.json();

      // Update DOM
      const pw = document.querySelector(productsSelector);
      if (pw) pw.innerHTML = data.products_html;

      const top = document.querySelector(topSelector);
      const bottom = document.querySelector(bottomSelector);
      if (top) top.innerHTML = data.pagination_html;
      if (bottom) bottom.innerHTML = data.pagination_html;

      if (push) {
        // push URL without ajax param
        const pushUrl = href.replace(/[?&]ajax=1/, '').replace(/[?&]$/, '');
        history.pushState(null, '', pushUrl);
      }

      // scroll to products
      const productsTop = document.querySelector('.products-content');
      if (productsTop) {
        window.scrollTo({ top: productsTop.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
      }
    } catch (err) {
      console.error(err);
      // fallback: navigate to href normally (remove ajax param)
      window.location.href = href.replace(/[?&]ajax=1/, '').replace(/[?&]$/, '');
    }
  }

  // Delegación clicks de paginación
  document.addEventListener('click', (e) => {
    const a = e.target.closest('.page-link');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href) return;
    e.preventDefault();
    fetchPage(href, true);
  });

  // Interceptar cambios de filtros
  if (filtroForm) {
    filtroForm.addEventListener('submit', (ev) => {
      ev.preventDefault();
      const params = new URLSearchParams(new FormData(filtroForm));
      params.set('pagina', '1');
      const href = window.location.pathname + '?' + params.toString();
      fetchPage(href, true);
    });

    filtroForm.querySelectorAll('input,select').forEach(el => {
      el.addEventListener('change', () => {
        const params = new URLSearchParams(new FormData(filtroForm));
        params.set('pagina', '1');
        const href = window.location.pathname + '?' + params.toString();
        fetchPage(href, true);
      });
    });
  }

  // Back/forward
  window.addEventListener('popstate', (e) => {
    // carga la URL actual por AJAX (no push)
    fetchPage(window.location.href, false);
  });
})();
