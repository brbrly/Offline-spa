/* أوفلاين سبا: ربط الموقع الرئيسي بنظام الحساب */
(function () {
    'use strict';
    const user = window.ROA_USER || null;

    /* ---- 1) الجوال السعودي: 10 أرقام فقط ويبدأ بـ 05 ---- */
    document.querySelectorAll('input[type="tel"]').forEach(el => {
        el.maxLength = 10;
        el.inputMode = 'numeric';
        const fix = () => {
            let d = el.value.replace(/\D/g, '');
            if (d.length > 0 && d[0] !== '0') d = '0' + d;
            if (d.length > 1 && d[1] !== '5') d = '05' + d.slice(2);
            el.value = d.slice(0, 10);
        };
        el.addEventListener('input', fix);
        fix();
    });

    /* ---- زر اللغة في صفحات الحساب (يحفظ الاختيار ويعيد تحميل الصفحة) ---- */
    const authLang = document.getElementById('authLang');
    if (authLang) {
        authLang.addEventListener('click', () => {
            const next = document.documentElement.lang === 'en' ? 'ar' : 'en';
            document.cookie = 'spa_lang=' + next + '; path=/; max-age=31536000; SameSite=Lax';
            location.reload();
        });
    }

    /* ---- بقية الكود للموقع الرئيسي فقط ---- */
    const navbar = document.getElementById('navbar');
    if (!navbar) return;

    /* ---- 2) أزرار الحجز: تفتح التسجيل إذا ما سجّل دخول ---- */
    document.querySelectorAll('a[href="#reserve"]').forEach(a => {
        a.addEventListener('click', e => {
            if (!user) {
                e.preventDefault();
                e.stopImmediatePropagation();
                location.href = 'register.php?next=reserve';
            }
        });
    });

    /* ---- 3) نموذج الحجز: لازم حساب + تعبئة تلقائية ---- */
    const form = document.getElementById('reserveForm');
    if (form) {
        form.addEventListener('submit', e => {
            if (!user) {
                e.preventDefault();
                e.stopImmediatePropagation();
                location.href = 'register.php?next=reserve';
            }
        });
        if (user) {
            const n = form.querySelector('[name="name"]');
            const p = form.querySelector('[name="phone"]');
            if (n && !n.value) n.value = user.name;
            if (p && !p.value) p.value = user.phone || '';
        }
    }

    /* ---- 4) بعد الدخول: زر "احجز الآن" يصير صورة بروفايل ---- */
    if (user) {
        const cta = document.querySelector('.nav-cta');
        const avatar = document.createElement('a');
        avatar.href = 'profile.php';
        avatar.className = 'nav-avatar';
        avatar.title = user.name;
        avatar.setAttribute('aria-label', document.documentElement.lang === 'en' ? 'My account' : 'حسابي');
        avatar.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>';
        if (cta) cta.replaceWith(avatar);
        else document.querySelector('.nav-actions')?.prepend(avatar);
    }

    /* ---- 5) حقوق الطبع والنشر ---- */
    const footer = document.querySelector('.footer');
    if (footer) {
        const box = document.createElement('div');
        box.className = 'dev-credit';
        box.innerHTML =
            '<span data-ar="تطوير وتصميم: عبد المحسن • حقوق الطبع والنشر محفوظة" data-en="Developed by Abdulmohsen • Copyright &amp; publishing rights reserved"></span>' +
            ' • <a href="https://instagram.com/c01xm" target="_blank" rel="noopener"><span data-ar="انستقرام" data-en="Instagram"></span> @c01xm</a>';
        const L = document.documentElement.lang === 'en' ? 'data-en' : 'data-ar';
        box.querySelectorAll('[data-ar]').forEach(sp => { sp.textContent = sp.getAttribute(L); });
        footer.appendChild(box);
    }
})();
