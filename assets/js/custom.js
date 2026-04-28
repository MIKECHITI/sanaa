/* ============================================================
   assets/js/custom.js  — Sanaa Ya Kenya
   ============================================================ */

// ── Mobile Menu Toggle ───────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent event from bubbling up
            mobileMenuToggle.classList.toggle('active');
            mobileMenu.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (mobileMenu.classList.contains('active') && 
                !mobileMenuToggle.contains(e.target) && 
                !mobileMenu.contains(e.target)) {
                mobileMenuToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
            }
        });
        
        // Close menu when clicking on a link
        const menuLinks = mobileMenu.querySelectorAll('a');
        menuLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
            });
        });
        
        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                mobileMenuToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
            }
        });
    }
});

// ── Add to Cart (AJAX) ───────────────────────────────────────
function addToCart(productId, qty = 1) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('qty', qty);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    fetch(window.APP_URL + '/api/add-to-cart.php', {
        method: 'POST',
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update cart badge
            const badge = document.getElementById('cartBadge');
            if (badge) badge.textContent = data.cart_count;

            // Show notification
            showNotification(data.message || 'Added to cart!', 'success');

            // Animate badge
            if (badge) {
                badge.classList.add('cart-bounce');
                setTimeout(() => badge.classList.remove('cart-bounce'), 400);
            }
        } else {
            showNotification(data.error || 'Could not add to cart.', 'error');
        }
    })
    .catch(() => showNotification('Network error. Please try again.', 'error'));
}

// ── Toast Notification ───────────────────────────────────────
function showNotification(msg, type = 'success') {
    // Remove existing
    const existing = document.getElementById('toastNotif');
    if (existing) existing.remove();

    const colors = { success: '#2D5A27', error: '#B03A2E', info: '#2C1810' };
    const el = document.createElement('div');
    el.id = 'toastNotif';
    el.style.cssText = `
        position:fixed; top:80px; right:1.5rem; z-index:9998;
        background:${colors[type] || colors.success}; color:#fff;
        padding:0.875rem 1.25rem; border-radius:8px;
        box-shadow:0 8px 32px rgba(0,0,0,0.2);
        border-left:4px solid #C9A84C; font-size:0.875rem; font-weight:500;
        max-width:320px; font-family:'DM Sans',sans-serif;
        transform:translateX(120%); transition:transform 0.35s cubic-bezier(0.4,0,0.2,1);
    `;
    el.textContent = msg;
    document.body.appendChild(el);

    requestAnimationFrame(() => {
        requestAnimationFrame(() => { el.style.transform = 'translateX(0)'; });
    });

    setTimeout(() => {
        el.style.transform = 'translateX(120%)';
        setTimeout(() => el.remove(), 400);
    }, 3500);
}

// ── Auto-dismiss flash banners ───────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const flash = document.getElementById('flashBanner');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity 0.4s';
            flash.style.opacity = '0';
            setTimeout(() => flash.remove(), 400);
        }, 4000);
    }
});

// ── Cart badge bounce animation ──────────────────────────────
const style = document.createElement('style');
style.textContent = `
  @keyframes cartBounce {
    0%,100% { transform: scale(1); }
    50%      { transform: scale(1.4); }
  }
  .cart-bounce { animation: cartBounce 0.4s ease; }
`;
document.head.appendChild(style);

// ── Password visibility toggle ───────────────────────────────
function togglePassword(btn) {
    const input = btn.previousElementSibling;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.textContent = isPassword ? 'Hide' : 'Show';
}