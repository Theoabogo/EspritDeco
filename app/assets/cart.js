/**
 * Gestion AJAX du panier (offcanvas).
 * Event delegation sur #cartOffcanvas pour éviter de ré-attacher les listeners
 * après chaque rafraîchissement du DOM.
 */
document.addEventListener('DOMContentLoaded', () => {
    const offcanvas = document.getElementById('cartOffcanvas');
    if (!offcanvas) return;

    offcanvas.addEventListener('click', async (e) => {
        // Boutons avec data-id (add, decrease, remove)
        const btn = e.target.closest('.cart-btn[data-id]');
        if (btn) {
            const id = btn.dataset.id;

            if (btn.classList.contains('cart-add')) {
                await cartAction(`/cart/add/${id}`);
            } else if (btn.classList.contains('cart-decrease')) {
                await cartAction(`/cart/decrease/${id}`);
            } else if (btn.classList.contains('cart-remove')) {
                await cartAction(`/cart/remove/${id}`);
            }
            return;
        }

        // Bouton "Vider le panier"
        if (e.target.closest('#cart-clear')) {
            await cartAction('/cart/clear');
        }
    });
});

async function cartAction(url) {
    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();

        if (data.success) {
            updateBadge(data.count);
            await refreshOffcanvasBody();
        }
    } catch (err) {
        console.error('Erreur panier :', err);
    }
}

function updateBadge(count) {
    const badge = document.querySelector('#cartOffcanvas ~ * .badge, .navbar .badge');

    // Le badge est dans la navbar, on le cherche via la nav
    document.querySelectorAll('.navbar .badge').forEach(b => {
        b.textContent = count;
    });
}

async function refreshOffcanvasBody() {
    const response = await fetch('/cart/offcanvas');
    const html = await response.text();

    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');

    const newBody = doc.querySelector('#cartOffcanvasBody');
    const currentBody = document.getElementById('cartOffcanvasBody');

    if (newBody && currentBody) {
        currentBody.innerHTML = newBody.innerHTML;
    }
}
