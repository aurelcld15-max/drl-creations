/**
 * DRL CRÉATIONS — JavaScript Natif (Thème Clair/Sombre, GSAP, IntersectionObserver,
 * Recherche AJAX, Favoris Fetch API, Partage & Parallaxe)
 */
document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Diaporama des pages d'authentification : une photo toutes les 3 secondes.
    document.querySelectorAll('[data-auth-slideshow]').forEach(slideshow => {
        const slides = Array.from(slideshow.querySelectorAll('img'));
        if (slides.length < 2) return;

        let activeIndex = slides.findIndex(slide => slide.classList.contains('is-active'));
        if (activeIndex < 0) activeIndex = 0;

        window.setInterval(() => {
            slides[activeIndex].classList.remove('is-active');
            activeIndex = (activeIndex + 1) % slides.length;
            slides[activeIndex].classList.add('is-active');
        }, 3000);
    });

    // 1. BASCULE MODE CLAIR / MODE SOMBRE (Soleil / Lune + localStorage - Section 13)
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeIcon = document.getElementById('themeIcon');

    function updateThemeIcon(theme) {
        if (!themeIcon) return;
        themeIcon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }

    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    updateThemeIcon(currentTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const active = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', active);
            try {
                localStorage.setItem('drl_theme', active);
            } catch (e) {}
            updateThemeIcon(active);
        });
    }

    // 2. MENU PROFIL CIRCULAIRE
    const profileBtn = document.getElementById('profileDropdownBtn');
    const profileMenu = document.getElementById('profileDropdownMenu');
    if (profileBtn && profileMenu) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileMenu.classList.toggle('open');
        });
        document.addEventListener('click', (e) => {
            if (!profileMenu.contains(e.target) && e.target !== profileBtn) {
                profileMenu.classList.remove('open');
            }
        });
    }

    // 3. MODALE DE RECHERCHE DYNAMIQUE (Fetch API / JSON)
    const openSearchBtn = document.getElementById('openSearchModalBtn');
    const closeSearchBtn = document.getElementById('closeSearchModalBtn');
    const searchOverlay = document.getElementById('searchModalOverlay');
    const liveSearchInput = document.getElementById('liveSearchInput');
    const liveSearchResults = document.getElementById('liveSearchResults');

    if (openSearchBtn && searchOverlay) {
        openSearchBtn.addEventListener('click', () => {
            searchOverlay.classList.add('open');
            setTimeout(() => liveSearchInput?.focus(), 100);
        });
        closeSearchBtn?.addEventListener('click', () => searchOverlay.classList.remove('open'));
        searchOverlay.addEventListener('click', (e) => {
            if (e.target === searchOverlay) searchOverlay.classList.remove('open');
        });
    }

    let searchTimer = null;
    if (liveSearchInput && liveSearchResults) {
        liveSearchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            const q = liveSearchInput.value.trim();
            if (q.length < 2) return;

            searchTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`${baseUrl}/api/search.php?q=${encodeURIComponent(q)}`);
                    const data = await res.json();
                    if (!data.results || data.results.length === 0) {
                        liveSearchResults.innerHTML = `<p class="drl-search-hint">Aucun résultat trouvé pour « ${q.replace(/[<>&"']/g, '')} ».</p>`;
                        return;
                    }
                    liveSearchResults.innerHTML = data.results.map(item => `
                        <a href="${item.url}" class="drl-search-item">
                            <img src="${item.image}" alt="">
                            <div>
                                <strong>${item.title}</strong>
                                <div><small>${item.type} • ${item.ref} • ${item.meta}</small></div>
                            </div>
                        </a>
                    `).join('');
                } catch (err) {
                    console.error(err);
                }
            }, 240);
        });
    }

    // 4. BOUTON FAVORIS DYNAMIQUE (AJAX / Fetch API)
    document.querySelectorAll('[data-fav-toggle]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const itemType = btn.getAttribute('data-type');
            const itemId = btn.getAttribute('data-id');

            try {
                const res = await fetch(`${baseUrl}/api/favorites.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        item_type: itemType,
                        item_id: Number(itemId),
                        csrf_token: csrfToken
                    })
                });
                const data = await res.json();
                if (data.auth_required && data.login_url) {
                    window.location.href = data.login_url;
                    return;
                }
                if (data.success) {
                    const icon = btn.querySelector('i');
                    if (data.action === 'added') {
                        btn.classList.add('active');
                        if (icon) icon.className = 'fa-solid fa-heart';
                    } else {
                        btn.classList.remove('active');
                        if (icon) icon.className = 'fa-regular fa-heart';
                    }
                    const badge = document.getElementById('headerFavCount');
                    if (badge && typeof data.count !== 'undefined') {
                        badge.textContent = String(data.count);
                    }
                }
            } catch (e) {
                console.error(e);
            }
        });
    });

    // 5. BOUTON PARTAGER (Web Share API + Fallback Presse-papiers)
    document.querySelectorAll('[data-share-url]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const url = btn.getAttribute('data-share-url');
            const title = btn.getAttribute('data-share-title') || 'DRL CRÉATIONS';
            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                } catch (e) {}
            } else if (navigator.clipboard) {
                await navigator.clipboard.writeText(url);
                alert('Lien copié dans le presse-papiers : ' + url);
            }
        });
    });

    // 6. ANIMATIONS D'APPARITION & PARALLAXE (IntersectionObserver + GSAP)
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!prefersReducedMotion) {
        const revealEls = document.querySelectorAll('.reveal-up, .reveal-scale');
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        if (window.gsap) {
                            gsap.fromTo(entry.target,
                                { opacity: 0, y: 24 },
                                { opacity: 1, y: 0, duration: 0.65, ease: 'power2.out' }
                            );
                        }
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });
            revealEls.forEach(el => io.observe(el));
        }

        // Parallaxe subtil Hero
        window.addEventListener('scroll', () => {
            const scrolled = window.scrollY;
            document.querySelectorAll('[data-parallax-speed]').forEach(layer => {
                const speed = parseFloat(layer.getAttribute('data-parallax-speed') || '0.1');
                layer.style.transform = `translate3d(0, ${scrolled * speed}px, 0)`;
            });
        }, { passive: true });
    }
});
