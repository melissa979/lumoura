// ============================================
// FICHIER SCRIPT PRINCIPAL - LUMOURA BIJOUX
// ============================================

document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // NAVBAR SCROLL EFFECT
    // ============================================

    const navbar = document.getElementById('mainNavbar');
    let lastScroll = 0;

    if (navbar) {
        window.addEventListener('scroll', function () {
            const currentScroll = window.pageYOffset;
            if (currentScroll > 100) {
                navbar.classList.add('scrolled');
                if (currentScroll > lastScroll && currentScroll > 200) {
                    navbar.style.transform = 'translateY(-100%)';
                } else {
                    navbar.style.transform = 'translateY(0)';
                }
            } else {
                navbar.classList.remove('scrolled');
                navbar.style.transform = 'translateY(0)';
            }
            lastScroll = currentScroll;
        });
    }

    // ============================================
    // MENU MOBILE TOGGLE
    // ============================================

    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navMenu       = document.getElementById('navMenu');
    const mobileSearch  = document.getElementById('mobileSearch');

    if (mobileMenuBtn && navMenu) {
        mobileMenuBtn.addEventListener('click', function () {
            navMenu.classList.toggle('active');
            mobileMenuBtn.innerHTML = navMenu.classList.contains('active')
                ? '<i class="fas fa-times"></i>'
                : '<i class="fas fa-bars"></i>';
            if (mobileSearch) mobileSearch.classList.remove('active');
        });
    }

    // ============================================
    // RECHERCHE TOGGLE
    // ============================================

    const searchToggle = document.getElementById('searchToggle');
    const searchBox    = document.getElementById('searchBox');

    if (searchToggle && searchBox) {
        searchToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            searchBox.classList.toggle('active');
            const input = searchBox.querySelector('input');
            if (input && searchBox.classList.contains('active')) {
                setTimeout(() => input.focus(), 100);
            }
        });

        document.addEventListener('click', function (e) {
            if (!searchBox.contains(e.target) && e.target !== searchToggle) {
                searchBox.classList.remove('active');
            }
        });

        const searchInput = searchBox.querySelector('input');
        if (searchInput) {
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && this.value.trim().length > 0) {
                    window.location.href = `catalogue.php?search=${encodeURIComponent(this.value.trim())}`;
                }
            });
        }
    }

    // ============================================
    // CAROUSEL HERO
    // ============================================

    const slides  = document.querySelectorAll('.slide');
    const dots    = document.querySelectorAll('.slider-dot');
    const prevBtn = document.querySelector('.slider-prev');
    const nextBtn = document.querySelector('.slider-next');

    let currentSlide  = 0;
    let slideInterval;

    function showSlide(n) {
        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));
        currentSlide = (n + slides.length) % slides.length;
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    }

    if (slides.length > 0) {
        if (nextBtn) nextBtn.addEventListener('click', () => showSlide(currentSlide + 1));
        if (prevBtn) prevBtn.addEventListener('click', () => showSlide(currentSlide - 1));
        dots.forEach((dot, i) => dot.addEventListener('click', () => showSlide(i)));

        slideInterval = setInterval(() => showSlide(currentSlide + 1), 5000);

        const hero = document.querySelector('.hero');
        if (hero) {
            hero.addEventListener('mouseenter', () => clearInterval(slideInterval));
            hero.addEventListener('mouseleave', () => {
                slideInterval = setInterval(() => showSlide(currentSlide + 1), 5000);
            });
        }

        // Swipe mobile
        let touchStartX = 0;
        const heroSlider = document.querySelector('.hero-slider');
        if (heroSlider) {
            heroSlider.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].clientX; }, { passive: true });
            heroSlider.addEventListener('touchend', e => {
                const diff = touchStartX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 50) diff > 0 ? showSlide(currentSlide + 1) : showSlide(currentSlide - 1);
            }, { passive: true });
        }
    }

    // ============================================
    // GALERIE PRODUIT (ZOOM ET THUMBNAILS)
    // ============================================

    const mainImage  = document.querySelector('.main-image img');
    const thumbnails = document.querySelectorAll('.thumbnail');

    if (mainImage && thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function () {
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                mainImage.style.opacity = '0';
                mainImage.style.transform = 'scale(0.97)';
                setTimeout(() => {
                    mainImage.src = this.querySelector('img').src;
                    mainImage.style.opacity = '1';
                    mainImage.style.transform = 'scale(1)';
                }, 200);
            });
        });

        const mainWrapper = mainImage.closest('.main-image');
        if (mainWrapper) {
            mainWrapper.addEventListener('mousemove', function (e) {
                const { left, top, width, height } = mainImage.getBoundingClientRect();
                const x = ((e.clientX - left) / width)  * 100;
                const y = ((e.clientY - top)  / height) * 100;
                mainImage.style.transformOrigin = `${x}% ${y}%`;
                mainImage.style.transform = 'scale(1.6)';
            });
            mainWrapper.addEventListener('mouseleave', () => {
                mainImage.style.transform = 'scale(1)';
            });
        }
    }

    // ============================================
    // SÉLECTEUR DE QUANTITÉ
    // ============================================

    document.querySelectorAll('.quantity-btn.minus').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.nextElementSibling;
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                animateQty(input);
                updateCartItem(this);
            }
        });
    });

    document.querySelectorAll('.quantity-btn.plus').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.previousElementSibling;
            const max = parseInt(input.getAttribute('max')) || 99;
            if (parseInt(input.value) < max) {
                input.value = parseInt(input.value) + 1;
                animateQty(input);
                updateCartItem(this);
            }
        });
    });

    function animateQty(input) {
        input.style.transform = 'scale(1.3)';
        input.style.color = '#d4af37';
        setTimeout(() => { input.style.transform = ''; input.style.color = ''; }, 200);
    }

    function updateCartItem(btn) {
        const cartItem      = btn.closest('.cart-item');
        if (!cartItem) return;
        const priceEl       = cartItem.querySelector('.cart-item-price');
        const totalEl       = cartItem.querySelector('.cart-item-total');
        const qtyInput      = cartItem.querySelector('.quantity-input');
        if (priceEl && totalEl && qtyInput) {
            const price = parseFloat(priceEl.textContent.replace(/[€\s]/g, '').replace(',', '.'));
            totalEl.textContent = (price * parseInt(qtyInput.value)).toFixed(2) + ' €';
            updateCartSummary();
        }
    }

    function updateCartSummary() {
        let sum = 0;
        document.querySelectorAll('.cart-item-total').forEach(el => {
            sum += parseFloat(el.textContent.replace(/[€\s]/g, '').replace(',', '.')) || 0;
        });
        const summaryEl = document.querySelector('.cart-total-amount, .summary-total');
        if (summaryEl) summaryEl.textContent = sum.toFixed(2) + ' €';
    }

    // ============================================
    // AJOUT AU PANIER
    // ============================================

    document.querySelectorAll('.btn-add-to-cart, .btn-cart').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const card    = this.closest('.product-card, .product-info-detail');
            if (!card) return;
            const nameEl  = card.querySelector('.product-name, .product-title, h3, h2');
            const name    = nameEl ? nameEl.textContent.trim() : 'Produit';
            const orig    = this.innerHTML;

            this.innerHTML  = '<i class="fas fa-check"></i> Ajouté !';
            this.style.background = '#27ae60';
            this.disabled   = true;

            setTimeout(() => {
                this.innerHTML = orig;
                this.style.background = '';
                this.disabled  = false;
            }, 2000);

            updateCartCounter(1);
            showNotification(`<strong>${name}</strong> ajouté au panier 🛍️`);
        });
    });

    function updateCartCounter(inc) {
        const cartCount = document.getElementById('cartCount');
        if (cartCount) {
            cartCount.textContent = (parseInt(cartCount.textContent) || 0) + inc;
            cartCount.style.transform = 'scale(1.8)';
            cartCount.style.background = '#27ae60';
            setTimeout(() => { cartCount.style.transform = ''; cartCount.style.background = ''; }, 400);
        }
    }

    // ============================================
    // ✅ WISHLIST TOGGLE — CHEMIN CORRIGÉ
    // Appelle : ../includes/toggle_wishlist.php
    // ============================================

    function initWishlistButtons() {
        document.querySelectorAll('.btn-wishlist').forEach(btn => {
            if (btn.dataset.wishlistInit) return;
            btn.dataset.wishlistInit = 'true';

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const idProduit = this.dataset.productId;
                if (!idProduit) {
                    showNotification('Identifiant produit manquant', 'error');
                    return;
                }

                const self = this;
                self.style.pointerEvents = 'none';
                self.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                // ✅ BON CHEMIN : toggle_wishlist.php dans includes/
                fetch('../includes/toggle_wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id_produit=${encodeURIComponent(idProduit)}`
                })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(data => {
                    self.style.pointerEvents = '';

                    if (data.success) {
                        if (data.action === 'added') {
                            self.classList.add('active');
                            self.innerHTML = '<i class="fas fa-heart"></i>';
                            self.title = 'Retirer des favoris';
                            showNotification('Ajouté à vos favoris ❤️');
                            updateWishlistCounter(1);
                        } else {
                            self.classList.remove('active');
                            self.innerHTML = '<i class="far fa-heart"></i>';
                            self.title = 'Ajouter aux favoris';
                            showNotification('Retiré de vos favoris');
                            updateWishlistCounter(-1);

                            // Retire la carte si on est sur liste_envies.php
                            if (window.location.href.includes('liste_envies')) {
                                const card = self.closest('.envie-card, .product-card');
                                if (card) {
                                    card.style.transition = 'all 0.4s ease';
                                    card.style.opacity = '0';
                                    card.style.transform = 'scale(0.9)';
                                    setTimeout(() => {
                                        card.remove();
                                        checkEmptyWishlist();
                                    }, 400);
                                }
                            }
                        }
                    } else {
                        // Non connecté → redirection
                        self.innerHTML = '<i class="far fa-heart"></i>';
                        showNotification('Connectez-vous pour gérer vos favoris', 'error');
                        setTimeout(() => window.location.href = 'connexion.php', 1800);
                    }
                })
                .catch(err => {
                    self.style.pointerEvents = '';
                    self.innerHTML = '<i class="far fa-heart"></i>';
                    console.error('Wishlist error:', err);
                    showNotification('Erreur de connexion, réessayez', 'error');
                });
            });
        });
    }

    function checkEmptyWishlist() {
        const remaining = document.querySelectorAll('.envie-card');
        const grid      = document.querySelector('.envies-grid');
        if (remaining.length === 0 && grid) {
            grid.innerHTML = `
                <div style="grid-column:1/-1;text-align:center;padding:60px 20px;">
                    <div style="font-size:3.5rem;color:#f0c0c0;margin-bottom:16px;">
                        <i class="far fa-heart"></i>
                    </div>
                    <h2 style="color:#3d2b1f;margin-bottom:10px;">Votre liste d'envies est vide</h2>
                    <p style="color:#888;margin-bottom:24px;">Ajoutez des bijoux en cliquant sur le cœur ♡</p>
                    <a href="catalogue.php" style="display:inline-block;padding:12px 28px;background:#d4af37;color:white;border-radius:10px;text-decoration:none;font-weight:600;">
                        <i class="fas fa-store"></i> Découvrir nos bijoux
                    </a>
                </div>`;
        }
        const countEl = document.querySelector('.envies-hero p, .envies-subtitle');
        if (countEl) {
            const n = remaining.length;
            countEl.textContent = `${n} bijou${n > 1 ? 'x' : ''} sauvegardé${n > 1 ? 's' : ''}`;
        }
    }

    function updateWishlistCounter(delta) {
        const wishCount = document.getElementById('wishlistCount');
        if (wishCount) {
            const next = Math.max(0, (parseInt(wishCount.textContent) || 0) + delta);
            wishCount.textContent = next;
            wishCount.style.display = next > 0 ? '' : 'none';
            wishCount.style.transform = 'scale(1.8)';
            wishCount.style.background = delta > 0 ? '#e74c3c' : '#888';
            setTimeout(() => { wishCount.style.transform = ''; wishCount.style.background = ''; }, 400);
        }
    }

    // Initialise maintenant + observe les futures cartes ajoutées dynamiquement
    initWishlistButtons();

    const wishlistObserver = new MutationObserver(() => initWishlistButtons());
    wishlistObserver.observe(document.body, { childList: true, subtree: true });

    // ============================================
    // NOTIFICATIONS PREMIUM
    // ============================================

    window.showNotification = function (message, type = 'success') {
        document.querySelectorAll('.lumoura-notif').forEach(n => n.remove());

        const notif = document.createElement('div');
        notif.className = `lumoura-notif lumoura-notif--${type}`;
        notif.innerHTML = `
            <div class="notif-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            </div>
            <div class="notif-body"><span>${message}</span></div>
            <button class="notif-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
            <div class="notif-bar"></div>
        `;
        document.body.appendChild(notif);
        requestAnimationFrame(() => notif.classList.add('show'));

        const timer = setTimeout(() => closeNotif(notif), 3500);
        notif.querySelector('.notif-close').addEventListener('click', () => {
            clearTimeout(timer); closeNotif(notif);
        });
    };

    function closeNotif(el) {
        el.classList.remove('show');
        el.classList.add('hiding');
        setTimeout(() => el.remove(), 350);
    }

    if (!document.getElementById('lumoura-notif-css')) {
        const style = document.createElement('style');
        style.id = 'lumoura-notif-css';
        style.textContent = `
            .lumoura-notif {
                position:fixed; top:90px; right:24px;
                background:#fff; border-radius:12px;
                box-shadow:0 8px 32px rgba(0,0,0,0.14);
                display:flex; align-items:center; gap:12px;
                padding:14px 18px; z-index:99999;
                transform:translateX(calc(100% + 40px)); opacity:0;
                transition:transform .35s cubic-bezier(.21,1.02,.73,1), opacity .35s ease;
                max-width:340px; min-width:220px; overflow:hidden;
            }
            .lumoura-notif.show    { transform:translateX(0); opacity:1; }
            .lumoura-notif.hiding  { transform:translateX(calc(100% + 40px)); opacity:0; }
            .lumoura-notif--success{ border-left:4px solid #d4af37; }
            .lumoura-notif--error  { border-left:4px solid #e74c3c; }
            .notif-icon { font-size:20px; flex-shrink:0; }
            .lumoura-notif--success .notif-icon { color:#d4af37; }
            .lumoura-notif--error   .notif-icon { color:#e74c3c; }
            .notif-body { flex:1; font-size:14px; color:#3d2b1f; line-height:1.4; }
            .notif-close { background:none; border:none; color:#bbb; cursor:pointer; font-size:13px; flex-shrink:0; padding:2px 4px; border-radius:4px; transition:color .2s; }
            .notif-close:hover { color:#666; }
            .notif-bar {
                position:absolute; bottom:0; left:0; height:3px;
                background:linear-gradient(90deg,#d4af37,#f0c94d);
                animation:notif-progress 3.5s linear forwards;
                border-radius:0 0 0 12px;
            }
            .lumoura-notif--error .notif-bar { background:linear-gradient(90deg,#e74c3c,#ff6b6b); }
            @keyframes notif-progress { from{width:100%} to{width:0%} }
            @media(max-width:480px){
                .lumoura-notif { top:auto; bottom:20px; right:12px; left:12px; max-width:none;
                    transform:translateY(120%); }
                .lumoura-notif.show   { transform:translateY(0); }
                .lumoura-notif.hiding { transform:translateY(120%); }
            }
        `;
        document.head.appendChild(style);
    }

    // ============================================
    // FILTRES PRODUITS
    // ============================================

    const filterButtons  = document.querySelectorAll('.filter-btn');
    const productCards   = document.querySelectorAll('.product-card');

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;

            productCards.forEach((card, i) => {
                const show = filter === 'all' || card.dataset.category === filter;
                card.style.transition = `opacity .3s ease ${i*30}ms, transform .3s ease ${i*30}ms`;
                if (show) {
                    card.style.display = 'block';
                    requestAnimationFrame(() => { card.style.opacity='1'; card.style.transform='translateY(0)'; });
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(10px)';
                    setTimeout(() => { card.style.display = 'none'; }, 300 + i*30);
                }
            });
        });
    });

    // ============================================
    // VALIDATION FORMULAIRE
    // ============================================

    document.querySelectorAll('form').forEach(form => {
        const inputs = form.querySelectorAll('.form-input[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => { if (input.classList.contains('error')) validateField(input); });
        });

        form.addEventListener('submit', function (e) {
            let valid = true;
            inputs.forEach(input => { if (!validateField(input)) valid = false; });
            if (!valid) {
                e.preventDefault();
                showNotification('Veuillez remplir tous les champs obligatoires', 'error');
                const firstErr = form.querySelector('.form-input.error');
                if (firstErr) firstErr.scrollIntoView({ behavior:'smooth', block:'center' });
            }
        });
    });

    function validateField(input) {
        const errorEl = input.nextElementSibling;
        let valid = true, msg = '';

        if (!input.value.trim()) { valid = false; msg = 'Ce champ est requis'; }
        else if (input.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) { valid = false; msg = 'Email invalide'; }
        else if (input.type === 'tel' && !/^[\d\s\+\-\(\)]{8,}$/.test(input.value)) { valid = false; msg = 'Téléphone invalide'; }

        input.classList.toggle('error', !valid);
        input.style.borderColor = valid ? '#27ae60' : '#e74c3c';
        if (errorEl?.classList.contains('form-error')) errorEl.textContent = valid ? '' : msg;

        return valid;
    }

    // ============================================
    // SCROLL TO TOP
    // ============================================

    const scrollBtn = document.createElement('button');
    scrollBtn.id = 'scrollTopBtn';
    scrollBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
    scrollBtn.setAttribute('aria-label', 'Retour en haut');
    document.body.appendChild(scrollBtn);

    if (!document.getElementById('scroll-top-css')) {
        const st = document.createElement('style');
        st.id = 'scroll-top-css';
        st.textContent = `
            #scrollTopBtn {
                position:fixed; bottom:30px; right:24px;
                width:44px; height:44px;
                background:#d4af37; color:white;
                border:none; border-radius:50%; cursor:pointer;
                font-size:16px; display:flex; align-items:center; justify-content:center;
                box-shadow:0 4px 16px rgba(212,175,55,0.4);
                opacity:0; transform:translateY(20px);
                transition:all .3s ease; z-index:1000;
            }
            #scrollTopBtn.visible { opacity:1; transform:translateY(0); }
            #scrollTopBtn:hover   { background:#b8972e; transform:translateY(-3px); box-shadow:0 6px 20px rgba(212,175,55,0.5); }
        `;
        document.head.appendChild(st);
    }

    window.addEventListener('scroll', () => {
        scrollBtn.classList.toggle('visible', window.pageYOffset > 400);
    });
    scrollBtn.addEventListener('click', () => window.scrollTo({ top:0, behavior:'smooth' }));

    // ============================================
    // ANIMATION AU SCROLL (Intersection Observer)
    // ============================================

    const animEls = document.querySelectorAll('.product-card, .section-title, .category-card, .envie-card');
    if (animEls.length > 0) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        animEls.forEach((el, i) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(24px)';
            el.style.transition = `opacity .5s ease ${i*60}ms, transform .5s ease ${i*60}ms`;
            observer.observe(el);
        });
    }

    // ============================================
    // LOADING SCREEN PREMIUM
    // ============================================

    window.addEventListener('load', function () {
        if (!document.getElementById('lumoura-loader-css')) {
            const ls = document.createElement('style');
            ls.id = 'lumoura-loader-css';
            ls.textContent = `
                .page-loader {
                    position:fixed; inset:0;
                    background:#fdfbf7;
                    display:flex; flex-direction:column;
                    align-items:center; justify-content:center;
                    z-index:99999; transition:opacity .6s ease;
                }
                .loader-logo {
                    font-family:'Cinzel',serif; font-size:28px;
                    color:#3d2b1f; letter-spacing:6px;
                    margin-bottom:24px;
                    animation:fadeInDown .6s ease forwards;
                }
                .loader-bar-wrap { width:140px; height:2px; background:#e8e0d5; border-radius:2px; overflow:hidden; }
                .loader-bar {
                    height:100%;
                    background:linear-gradient(90deg,#d4af37,#f0c94d,#d4af37);
                    animation:loaderSlide 1.2s ease forwards;
                }
                @keyframes loaderSlide { from{width:0} to{width:100%} }
                @keyframes fadeInDown { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }
            `;
            document.head.appendChild(ls);
        }

        const loader = document.createElement('div');
        loader.className = 'page-loader';
        loader.innerHTML = `
            <div class="loader-logo">LUMOURA</div>
            <div class="loader-bar-wrap"><div class="loader-bar"></div></div>
        `;
        document.body.prepend(loader);

        setTimeout(() => {
            loader.style.opacity = '0';
            setTimeout(() => loader.remove(), 600);
        }, 1400);
    });

});
// ============================================
// AUTOCOMPLÉTION ADRESSE — API gouv.fr (jQuery)
// ============================================

$(document).ready(function () {

    const $input = $('input[name="adresse"]').first();
    if ($input.length === 0) return;

    if (!$('#autocomplete-style').length) {
        $('head').append(`<style id="autocomplete-style">
        .adresse-wrapper { position:relative; }
        .adresse-suggestions {
            position:absolute; top:100%; left:0; right:0;
            background:#fff; border:1px solid #e0d5c5;
            border-top:none; border-radius:0 0 10px 10px;
            box-shadow:0 8px 24px rgba(61,43,31,.13);
            z-index:9999; max-height:250px; overflow-y:auto;
        }
        .adresse-item {
            padding:11px 16px; cursor:pointer; font-size:14px;
            color:#3d2b1f; border-bottom:1px solid #f5f0ea;
            display:flex; align-items:center; gap:10px; transition:background .15s;
        }
        .adresse-item:last-child { border-bottom:none; }
        .adresse-item:hover, .adresse-item.actif { background:#fff9ec; }
        .adresse-item i { color:#d4af37; flex-shrink:0; }
        .adresse-item-text strong { display:block; font-weight:600; }
        .adresse-item-text span { font-size:12px; color:#999; }
        </style>`);
    }

    if (!$input.parent().hasClass('adresse-wrapper')) {
        $input.wrap('<div class="adresse-wrapper"></div>');
    }
    const $wrap = $input.parent();

    let timer = null, $liste = null, idx = -1;

    function fermer() { if ($liste) { $liste.remove(); $liste = null; } idx = -1; }

    function remplir(f) {
        const p = f.properties;
        $input.val(p.name || '');
        $('input[name="code_postal"]').val(p.postcode || '');
        $('input[name="ville"]').val(p.city || '');
        fermer();
    }

    function afficher(features) {
        fermer();
        if (!features.length) return;
        $liste = $('<div class="adresse-suggestions"></div>');
        $.each(features, function(i, f) {
            const p = f.properties;
            const $item = $(`
                <div class="adresse-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="adresse-item-text">
                        <strong>${p.name || ''}</strong>
                        <span>${p.postcode || ''} ${p.city || ''}</span>
                    </div>
                </div>`);
            $item.on('click', function() { remplir(f); });
            $liste.append($item);
        });
        $wrap.append($liste);
    }

    $input.on('input', function() {
        clearTimeout(timer);
        const val = $(this).val().trim();
        if (val.length < 3) { fermer(); return; }
        timer = setTimeout(function() {
            $.getJSON('https://api-adresse.data.gouv.fr/search/', {
                q: val, limit: 6, autocomplete: 1
            }, function(data) { afficher(data.features || []); });
        }, 300);
    });

    $input.on('keydown', function(e) {
        if (!$liste) return;
        const $items = $liste.find('.adresse-item');
        if (e.key === 'ArrowDown') { e.preventDefault(); idx = Math.min(idx+1, $items.length-1); $items.removeClass('actif').eq(idx).addClass('actif'); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); idx = Math.max(idx-1, 0); $items.removeClass('actif').eq(idx).addClass('actif'); }
        else if (e.key === 'Enter' && idx >= 0) { e.preventDefault(); $items.eq(idx).trigger('click'); }
        else if (e.key === 'Escape') { fermer(); }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.adresse-wrapper').length) fermer();
    });
});