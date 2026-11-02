/* ============================================================
   Offline Spa (أوفلاين سبا) — Frontend Interactions
   ============================================================ */

(function () {
    'use strict';

    /* ----------- 1. NAVBAR SCROLL EFFECT ----------- */
    const navbar = document.getElementById('navbar');
    const handleScroll = () => {
        if (window.scrollY > 40) navbar.classList.add('scrolled');
        else navbar.classList.remove('scrolled');
    };
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();

    /* ----------- 2. MOBILE NAV TOGGLE ----------- */
    const menuToggleBtn = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');
    if (menuToggleBtn) {
        menuToggleBtn.addEventListener('click', () => {
            navLinks.classList.toggle('mobile-open');
        });
        // Close on link click
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('mobile-open');
            });
        });
    }

    /* ----------- 3. LANGUAGE TOGGLE (AR ↔ EN) ----------- */
    const langToggle = document.getElementById('langToggle');
    let currentLang = 'ar';

    /**
     * Update the visible text of any element that carries BOTH data-ar and data-en.
     * We preserve child nodes (e.g. inline SVGs inside buttons) by only updating
     * the first text node, or by inserting a text node if none exists.
     */
    const updateI18nText = (lang) => {
        document.querySelectorAll('[data-ar][data-en]').forEach(el => {
            // Skip elements whose only translation target is a placeholder (handled separately)
            if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') return;
            const newText = el.getAttribute(`data-${lang}`);
            if (newText === null) return;
            // Find existing first text node
            let textNode = null;
            for (const node of el.childNodes) {
                if (node.nodeType === Node.TEXT_NODE && node.textContent.trim().length > 0) {
                    textNode = node;
                    break;
                }
            }
            if (textNode) {
                textNode.textContent = newText;
            } else {
                // No text node yet (e.g. only child SVGs); insert at the front
                el.insertBefore(document.createTextNode(newText), el.firstChild);
            }
        });
    };

    const setLanguage = (lang) => {
        currentLang = lang;
        document.cookie = 'spa_lang=' + lang + '; path=/; max-age=31536000; SameSite=Lax';
        const html = document.documentElement;
        html.setAttribute('lang', lang);
        html.setAttribute('dir', lang === 'en' ? 'ltr' : 'rtl');
        document.body.classList.toggle('en', lang === 'en');

        // Highlight the CURRENT language only
        if (langToggle) {
            langToggle.querySelectorAll('[data-l]').forEach(sp => {
                const on = sp.dataset.l === lang;
                sp.classList.toggle('lang-active', on);
                sp.classList.toggle('lang-passive', !on);
            });
        }

        // Update visible text on every dual-language element
        updateI18nText(lang);

        // Update placeholders for inputs
        document.querySelectorAll('[data-ar-placeholder]').forEach(el => {
            const placeholder = el.getAttribute(`data-${lang}-placeholder`);
            if (placeholder) el.setAttribute('placeholder', placeholder);
        });

        // Update optgroup labels
        document.querySelectorAll('optgroup[data-ar]').forEach(g => g.setAttribute('label', g.getAttribute('data-' + lang)));

        // Update select options
        document.querySelectorAll('select option').forEach(opt => {
            const text = opt.getAttribute(`data-${lang}`);
            if (text) opt.textContent = text;
        });
    };

    if (langToggle) {
        langToggle.addEventListener('click', () => {
            setLanguage(currentLang === 'ar' ? 'en' : 'ar');
        });
    }

    // تطبيق اللغة المحفوظة (إنجليزي) عند فتح الصفحة
    if (document.documentElement.getAttribute('lang') === 'en') setLanguage('en');

    /* ----------- 4. MENU TABS ----------- */
    const tabs = document.querySelectorAll('.menu-tab');
    const panels = document.querySelectorAll('.menu-panel');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            const panel = document.querySelector(`.menu-panel[data-panel="${target}"]`);
            if (panel) panel.classList.add('active');
        });
    });

    /* ----------- 5. RESERVATION FORM ----------- */
    const reserveForm = document.getElementById('reserveForm');
    const successModal = document.getElementById('successModal');
    if (reserveForm) {
        // Set min date to today
        const dateInput = reserveForm.querySelector('input[type="date"]');
        if (dateInput) {
            const today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('min', today);
            dateInput.value = today;
        }

        reserveForm.addEventListener('submit', (e) => {
            e.preventDefault();
            // Basic validation
            const requiredFields = reserveForm.querySelectorAll('[required]');
            let valid = true;
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = 'var(--burgundy-700)';
                    setTimeout(() => field.style.borderColor = '', 2000);
                }
            });
            if (!valid) return;
            // Save reservation in database
            const fd = new FormData(reserveForm);
            fd.append('csrf', (window.ROA_USER || {}).csrf || '');
            const submitBtn = reserveForm.querySelector('[type="submit"]');
            submitBtn.disabled = true;
            fetch('reserve.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) { alert(d.error || (currentLang === 'en' ? 'Something went wrong, please try again.' : 'حدث خطأ، حاول مرة ثانية.')); return; }
                    successModal.classList.add('open');
                    successModal.setAttribute('aria-hidden', 'false');
                    setTimeout(() => {
                        reserveForm.reset();
                        if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
                    }, 800);
                })
                .catch(() => alert(currentLang === 'en' ? 'Could not reach the server.' : 'تعذر الاتصال بالخادم.'))
                .finally(() => { submitBtn.disabled = false; });
        });
    }

    /* ----------- 6. MODAL CLOSE ----------- */
    document.querySelectorAll('[data-close="modal"]').forEach(el => {
        el.addEventListener('click', () => {
            successModal.classList.remove('open');
            successModal.setAttribute('aria-hidden', 'true');
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && successModal.classList.contains('open')) {
            successModal.classList.remove('open');
        }
    });

    /* ----------- 7. REVIEWS CAROUSEL ----------- */
    const track = document.getElementById('reviewsTrack');
    const prevBtn = document.getElementById('prevReview');
    const nextBtn = document.getElementById('nextReview');
    const dotsContainer = document.getElementById('reviewDots');
    let reviewIndex = 0;

    if (track && dotsContainer) {
        const cards = track.querySelectorAll('.review-card');
        const totalCards = cards.length;

        const getVisibleCount = () => {
            if (window.innerWidth < 768) return 1;
            if (window.innerWidth < 1024) return 2;
            return 3;
        };

        const updateCarousel = () => {
            const visible = getVisibleCount();
            const maxIndex = Math.max(0, totalCards - visible);
            if (reviewIndex > maxIndex) reviewIndex = maxIndex;
            const card = cards[0];
            const style = window.getComputedStyle(card);
            const cardWidth = card.offsetWidth;
            const gap = parseFloat(window.getComputedStyle(track).gap) || 22;
            const offset = -(reviewIndex * (cardWidth + gap));
            track.style.transform = `translateX(${offset}px)`;
            // Build dots
            dotsContainer.innerHTML = '';
            for (let i = 0; i <= maxIndex; i++) {
                const dot = document.createElement('span');
                if (i === reviewIndex) dot.classList.add('active');
                dot.addEventListener('click', () => {
                    reviewIndex = i;
                    updateCarousel();
                });
                dotsContainer.appendChild(dot);
            }
        };

        if (prevBtn) prevBtn.addEventListener('click', () => {
            const visible = getVisibleCount();
            const maxIndex = Math.max(0, totalCards - visible);
            reviewIndex = Math.max(0, reviewIndex - 1);
            updateCarousel();
        });
        if (nextBtn) nextBtn.addEventListener('click', () => {
            const visible = getVisibleCount();
            const maxIndex = Math.max(0, totalCards - visible);
            reviewIndex = Math.min(maxIndex, reviewIndex + 1);
            updateCarousel();
        });

        window.addEventListener('resize', updateCarousel);
        updateCarousel();

        // Touch swipe support
        let touchStartX = 0;
        let touchEndX = 0;
        track.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        track.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) nextBtn?.click();
                else prevBtn?.click();
            }
        }, { passive: true });
    }

    /* ----------- 8. SCROLL REVEAL ----------- */
    const revealElements = document.querySelectorAll('.feature-card, .menu-item, .review-card, .info-block, .experience-banner, .section-head');
    revealElements.forEach(el => el.classList.add('reveal'));

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });

    revealElements.forEach(el => observer.observe(el));

    /* ----------- 9. SMOOTH SCROLL FOR ANCHORS ----------- */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#' || targetId.length < 2) return;
            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                const offset = 80;
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });

    /* ----------- 10. CONSOLE SIGNATURE ----------- */
    console.log('%cأوفلاين سبا  Offline Spa', 'font-weight:900;color:#C5A059;font-size:18px;');
    console.log('%cملاذكِ للاسترخاء والجمال', 'color:#A56877;font-size:12px;');
})();