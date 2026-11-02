<?php require 'config.php'; require 'menu-lib.php'; ?>
<!DOCTYPE html>
<html lang="<?= lang() ?>" dir="<?= lang() === 'en' ? 'ltr' : 'rtl' ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?= t('أوفلاين سبا | صالون تجميل وسبا نسائي فاخر في حي الربوة، شرق الرياض', "Offline Spa | A luxury women's beauty salon & spa in Al Rabwah, East Riyadh") ?>" />
    <meta name="theme-color" content="#4a2c3d" />
    <title><?= t('أوفلاين سبا — صالون تجميل وسبا نسائي فاخر', "Offline Spa — Luxury Women's Beauty Salon & Spa") ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css?v=<?= @filemtime(__DIR__ . '/styles.css') ?>" />
</head>
<body<?= lang() === 'en' ? ' class="en"' : '' ?>>
    <!-- ============== NAVBAR ============== -->
    <header class="navbar" id="navbar">
        <div class="container nav-inner">
            <a href="#hero" class="brand">
                <span class="brand-mark"><?= BRAND_MARK ?></span>
                <div class="brand-text">
                    <span class="brand-ar">أوفلاين سبا</span>
                    <span class="brand-en">Offline Spa</span>
                </div>
            </a>
            <nav class="nav-links" id="navLinks">
                <a href="#about" <?= ba('تجربتنا', 'Our Experience') ?>>تجربتنا</a>
                <a href="#menu" <?= ba('الخدمات', 'Services') ?>>الخدمات</a>
                <a href="#reserve" <?= ba('احجزي موعدكِ', 'Book') ?>>احجزي موعدكِ</a>
                <a href="#reviews" <?= ba('آراء العميلات', 'Reviews') ?>>آراء العميلات</a>
                <a href="#location" <?= ba('الموقع', 'Location') ?>>الموقع</a>
            </nav>
            <div class="nav-actions">
                <button class="lang-toggle" id="langToggle" aria-label="Toggle language">
                    <span data-l="ar" class="<?= lang() === 'ar' ? 'lang-active' : 'lang-passive' ?>">AR</span>
                    <span class="lang-divider">/</span>
                    <span data-l="en" class="<?= lang() === 'en' ? 'lang-active' : 'lang-passive' ?>">EN</span>
                </button>
                <a href="#reserve" class="btn btn-primary nav-cta"><?= tx('احجزي الآن', 'Book Now') ?></a>
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu"><span></span><span></span><span></span></button>
            </div>
        </div>
    </header>

    <!-- ============== HERO WITH AMBIENT RELAXATION VIDEO BACKGROUND ============== -->
    <section class="hero" id="hero">
        <!-- فيديو خلفية استرخائي هادئ للمساج والسبا النسائي -->
        <div class="hero-bg" aria-hidden="true">
            <video autoplay muted loop playsinline class="hero-video-bg">
                <source src="https://assets.mixkit.co/videos/preview/mixkit-woman-getting-a-back-massage-at-a-spa-41527-large.mp4" type="video/mp4">
            </video>
            <div class="hero-gradient"></div>
            <div class="hero-particles"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
            <div class="hero-orbs"><div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div></div>
        </div>
        <div class="container hero-content">
            <?= tx('صالون تجميل وسبا نسائي فاخر • الربوة، الرياض', "Luxury Women's Beauty Salon & Spa • Al Rabwah, Riyadh", 'span', 'eyebrow') ?>
            <h1 class="hero-title"><?= tx('ملاذكِ الخاص للاسترخاء والجمال في شرق الرياض', 'Your private sanctuary of relaxation and beauty in East Riyadh', 'span', 'title-ar') ?></h1>
            <?= tx('أوفلاين سبا وجهتكِ الراقية للعناية بنفسكِ: حمام مغربي، مناكير وبدكير، وعناية متكاملة بأيدٍ خفيفة وأخصائيات محترفات.',
                   'Offline Spa is your refined destination for self-care: Moroccan bath, manicure and pedicure, and complete care by gentle-handed, professional specialists.', 'p', 'hero-subtitle') ?>
            <div class="hero-ctas">
                <a href="#reserve" class="btn btn-primary btn-large">
                    <?= tx('احجزي موعدكِ الآن', 'Book Your Appointment') ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="#menu" class="btn btn-ghost btn-large"><?= tx('استكشفي قائمة الخدمات', 'Explore Our Services') ?></a>
            </div>
            
            <!-- أيقونات وسائل التواصل الاجتماعي تحت الأزرار -->
            <div class="hero-socials">
                <a href="https://instagram.com" target="_blank" rel="noopener" class="social-icon social-icon-ig">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    <span class="social-label">Instagram</span>
                </a>
                <a href="https://tiktok.com" target="_blank" rel="noopener" class="social-icon social-icon-tt">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>
                    <span class="social-label">TikTok</span>
                </a>
            </div>

            <div class="hero-meta">
                <div class="meta-item"><span class="meta-num">4.8</span><?= tx('تقييم قوقل', 'Google Rating', 'span', 'meta-label') ?></div>
                <div class="meta-divider"></div>
                <div class="meta-item"><span class="meta-num">289+</span><?= tx('مراجعة', 'Reviews', 'span', 'meta-label') ?></div>
                <div class="meta-divider"></div>
                <div class="meta-item"><span class="meta-num">✦</span><?= tx('أخصائيات محترفات', 'Expert Specialists', 'span', 'meta-label') ?></div>
            </div>
        </div>
        <a href="#about" class="scroll-cue" aria-label="Scroll down"><span></span></a>
    </section>

    <!-- ============== ABOUT ============== -->
    <section class="section about" id="about">
        <div class="container">
            <div class="section-head">
                <?= tx('تجربتنا', 'Our Experience', 'span', 'eyebrow') ?>
                <?= tx('يد خفيفة، وراحة من أول دقيقة', 'A gentle touch, and comfort from the very first minute', 'h2', 'section-title') ?>
                <?= tx('في أوفلاين سبا نهتم بأدق التفاصيل: أخصائيات خفيفات اليد، ومنتجات احترافية، وأجواء هادئة تجعل كل زيارة وقتاً خاصاً بكِ.',
                       'At Offline Spa we care about every detail: gentle-handed specialists, professional products, and a calm atmosphere that makes every visit your own time.', 'p', 'section-lead') ?>
            </div>

            <div class="feature-grid">
                <article class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3s6 6.2 6 10.5A6 6 0 0 1 6 13.5C6 9.2 12 3 12 3z"/></svg></div>
                    <?= tx('الحمام المغربي الملكي', 'Royal Moroccan Bath', 'h3') ?>
                    <?= tx('تجربة تنظيف وترطيب عميقة تمنح بشرتكِ نعومة وانتعاشاً، بأيدي أخصائيات متمرّسات.', 'A deep cleansing and hydrating experience that leaves your skin soft and refreshed, by skilled specialists.', 'p') ?>
                    <?= tx('كلاسيك • أوفلاين سبا • سيغنتشر', 'Classic • Offline Spa • Signature', 'span', 'feature-tag') ?>
                </article>
                <article class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.2 6.3L20 10l-5.8 1.7L12 18l-2.2-6.3L4 10l5.8-1.7z"/></svg></div>
                    <?= tx('العناية بالأظافر والبدكير', 'Nail Care & Pedicure', 'h3') ?>
                    <?= tx('مناكير وبدكير بمنتجات عالمية، وأحجار ساخنة وقفازات كولاجين لراحة ونعومة تدوم.', 'Manicure and pedicure with premium brands, plus hot stones and collagen gloves for lasting comfort and softness.', 'p') ?>
                    <?= tx('مناكير • بدكير • أحجار ساخنة', 'Manicure • Pedicure • Hot Stones', 'span', 'feature-tag') ?>
                </article>
                <article class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 14c1 1.6 2.4 2.4 4 2.4s3-.8 4-2.4M9 9.5h.01M15 9.5h.01"/></svg></div>
                    <?= tx('تنظيف البشرة', 'Facial Cleansing', 'h3') ?>
                    <?= tx('عناية احترافية بالبشرة بيد خفيفة وشغل دقيق، لإطلالة صافية ومشرقة.', 'Professional skin care with a gentle hand and precise work, for a clear and radiant look.', 'p') ?>
                    <?= tx('عناية • نقاء • إشراقة', 'Care • Purity • Glow', 'span', 'feature-tag') ?>
                </article>
                <article class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4L8.1 15.9M14.5 14.5L20 20M8.1 8.1L12 12"/></svg></div>
                    <?= tx('العناية بالشعر والقص', 'Hair Care & Cutting', 'h3') ?>
                    <?= tx('قص وعناية بالشعر تناسب إطلالتكِ وذوقكِ، بلمسة أخصائية تهتم بالتفاصيل.', 'Haircuts and hair care tailored to your look and taste, with the touch of a detail-oriented specialist.', 'p') ?>
                    <?= tx('قص • عناية • تسريحة', 'Cut • Care • Styling', 'span', 'feature-tag') ?>
                </article>
            </div>

            <div class="experience-banner">
                <div class="banner-content">
                    <?= tx('التجربة المميزة', 'Signature Experience', 'span', 'banner-eyebrow') ?>
                    <?= tx('الحمام المغربي أوفلاين سبا سيغنتشر', 'Offline Spa Signature Moroccan Bath', 'h3') ?>
                    <?= tx('تجربتنا الأرقى للاسترخاء والعناية الكاملة، خصصي لنفسكِ وقتاً تستحقينه.', 'Our finest experience for relaxation and complete care. Set aside the time you deserve.', 'p') ?>
                </div>
                <a href="#reserve" class="btn btn-secondary"><?= tx('احجزي الآن', 'Book Now') ?></a>
            </div>
        </div>
    </section>

    <!-- ============== SERVICES ============== -->
    <section class="section menu" id="menu">
        <div class="container">
            <div class="section-head">
                <?= tx('قائمة الخدمات', 'Our Services', 'span', 'eyebrow') ?>
                <?= tx('خدمات مختارة بعناية لكِ', 'Services carefully chosen for you', 'h2', 'section-title') ?>
                <?= tx('استعرضي خدماتنا حسب القسم. الأسعار بالريال السعودي وقابلة للتحديث.', 'Browse our services by category. Prices are in Saudi riyals and subject to update.', 'p', 'section-lead') ?>
            </div>

            <div class="menu-tabs" role="tablist">
                <?php $first = true; foreach ($CATS as $key => [$ar, $en, $emoji]): ?>
                    <button class="menu-tab<?= $first ? ' active' : '' ?>" data-tab="<?= e($key) ?>" role="tab">
                        <span class="tab-emoji"><?= $emoji ?></span><?= tx($ar, $en) ?>
                    </button>
                <?php $first = false; endforeach; ?>
            </div>

            <div class="menu-panels">
                <?php $first = true; foreach ($CATS as $key => [$ar, $en, $emoji]): ?>
                    <div class="menu-panel<?= $first ? ' active' : '' ?>" data-panel="<?= e($key) ?>">
                        <?php if ($first): ?>
                            <div class="panel-banner"><?= tx('✨ جميع خدماتنا بأيدي أخصائيات محترفات', '✨ All our services by professional specialists') ?></div>
                        <?php endif; ?>
                        <div class="menu-list"><?php render_items($menu[$key] ?? []); ?></div>
                    </div>
                <?php $first = false; endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============== RESERVE ============== -->
    <section class="section reserve" id="reserve">
        <div class="container">
            <div class="reserve-card">
                <div class="reserve-side">
                    <?= tx('حجز المواعيد', 'Appointments', 'span', 'eyebrow') ?>
                    <?= tx('احجزي موعدكِ بثقة', 'Book your appointment with confidence', 'h2', 'section-title') ?>
                    <?= tx('اختاري الخدمة والأخصائية والوقت المناسب لكِ، وسيتواصل معكِ فريق أوفلاين سبا لتأكيد الموعد.', 'Choose your service, specialist and a time that suits you, and the Offline Spa team will contact you to confirm.', 'p') ?>
                    <div class="status-bar"><span class="status-dot"></span><?= tx('نستقبلكِ يومياً من 1:00 م حتى 10:00 م', 'We welcome you daily from 1:00 PM to 10:00 PM') ?></div>
                    <ul class="reserve-list">
                        <?php foreach ([['اختاري الخدمة المطلوبة من قائمتنا', 'Choose your service from our list'], ['حددي أخصائيتكِ المفضلة إن وُجدت', 'Pick your preferred specialist, if any'], ['أضيفي ملاحظاتكِ وطلباتكِ الخاصة', 'Add your notes and special requests']] as [$a, $b]): ?>
                            <li><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg><?= tx($a, $b) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="https://wa.me/966566319009" target="_blank" rel="noopener" class="reserve-contact">
                        <?= tx('أو احجزي مباشرة عبر واتساب', 'Or book directly via WhatsApp', 'span', 'rc-label') ?><span class="rc-arrow">→</span>
                    </a>
                </div>

                <form class="reserve-form" id="reserveForm" novalidate>
                    <?= tx('تفاصيل الموعد', 'Appointment Details', 'h3', 'form-title') ?>
                    <div class="form-grid">
                        <div class="form-field"><?= tx('الاسم الكامل', 'Full Name', 'label') ?>
                            <input type="text" name="name" required <?= 'data-ar-placeholder="اكتبي اسمكِ" data-en-placeholder="Your full name" placeholder="' . t('اكتبي اسمكِ', 'Your full name') . '"' ?> /></div>
                        <div class="form-field"><?= tx('رقم الجوال', 'Phone Number', 'label') ?>
                            <input type="tel" name="phone" required placeholder="0500000000" data-ar-placeholder="0500000000" data-en-placeholder="0500000000" /></div>
                        <div class="form-field"><?= tx('التاريخ', 'Date', 'label') ?><input type="date" name="date" required /></div>
                        <div class="form-field"><?= tx('الوقت', 'Time', 'label') ?><input type="time" name="time" required value="14:00" min="13:00" max="21:30" step="900" /></div>
                        <div class="form-field"><?= tx('الخدمة المطلوبة', 'Requested Service', 'label') ?>
                            <select name="service_id" required>
                                <option value="" <?= ba('اختاري الخدمة', 'Select a service') ?>><?= e(t('اختاري الخدمة', 'Select a service')) ?></option>
                                <?php foreach ($CATS as $key => [$ar, $en, $emoji]): if (empty($menu[$key])) continue; ?>
                                    <optgroup label="<?= e(t($ar, $en)) ?>" <?= ba($ar, $en) ?>>
                                        <?php foreach ($menu[$key] as $p): $nEn = $p['name_en'] !== '' ? $p['name_en'] : $p['name_ar']; ?>
                                            <option value="<?= (int)$p['id'] ?>" <?= ba($p['name_ar'] . ' — ' . $p['price'] . ' ر.س', $nEn . ' — ' . $p['price'] . ' SAR') ?>><?= e(t($p['name_ar'] . ' — ' . $p['price'] . ' ر.س', $nEn . ' — ' . $p['price'] . ' SAR')) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="form-field"><?= tx('الأخصائية المفضلة', 'Preferred Specialist', 'label') ?>
                            <select name="specialist_id">
                                <option value="0" <?= ba('أي أخصائية متاحة', 'Any available specialist') ?>><?= e(t('أي أخصائية متاحة', 'Any available specialist')) ?></option>
                                <?php foreach ($specialists as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= ba($s['name_ar'], $s['name_en']) ?>><?= e(t($s['name_ar'], $s['name_en'])) ?></option>
                                <?php endforeach; ?>
                            </select></div>
                    </div>
                    <div class="form-field"><?= tx('ملاحظات خاصة', 'Special Notes', 'label') ?>
                        <textarea name="notes" rows="3" maxlength="500" data-ar-placeholder="مناسبة، تفضيلات، أو أي طلبات خاصة..." data-en-placeholder="Occasion, preferences, or any special requests..." placeholder="<?= e(t('مناسبة، تفضيلات، أو أي طلبات خاصة...', 'Occasion, preferences, or any special requests...')) ?>"></textarea></div>
                    <button type="submit" class="btn btn-primary btn-full"><?= tx('إرسال طلب الموعد', 'Submit Appointment Request') ?></button>
                    <?= tx('بإرسال النموذج، سيتواصل معكِ فريق أوفلاين سبا لتأكيد موعدكِ.', 'By submitting, the Offline Spa team will contact you to confirm your appointment.', 'p', 'form-fineprint') ?>
                </form>
            </div>
        </div>
    </section>

    <!-- ============== REVIEWS ============== -->
    <section class="section reviews" id="reviews">
        <div class="container">
            <div class="section-head">
                <?= tx('آراء عميلاتنا', 'Client Reviews', 'span', 'eyebrow') ?>
                <?= tx('تجارب حقيقية من زائراتنا', 'Real experiences from our guests', 'h2', 'section-title') ?>
                <?= tx('تعليقات من تقييمات قوقل ماب (4.8 من 5 بناءً على 289+ مراجعة).', 'Highlights from Google Maps reviews (4.8 out of 5 based on 289+ reviews).', 'p', 'section-lead') ?>
            </div>
            <div class="reviews-carousel">
                <div class="reviews-track" id="reviewsTrack">
                    <?php $reviews = [
                        ['شهد محمد', 'Shahad Mohammed', '"أحب أشكر الأونر على الجمال هذا.. الحمدلله إني لقيت سبا راقي في شرق الرياض خيال بكل ما تحمله الكلمة من معنى."', '"I want to thank the owner for all this beauty.. Thank God I found a classy spa in East Riyadh, simply dreamy in every sense of the word."'],
                        ['ميم المطيري', 'Mim Almutairi', '"سويت تنظيف بشرة عند الأخصائية رنيم، ايدها خفيفة مرة وشغلها احترافي.. وجربت البدكير والمناكير شغل خيال وأكيد مو آخر زيارة."', '"I had a facial cleansing with specialist Raneem; her hand is very gentle and her work is professional.. I also tried the pedicure and manicure, dreamy work, and definitely not my last visit."'],
                        ['ريم م.', 'Reem M.', '"سويت الحمام المغربي عند غزلان وكان أكثر من رائع، شغل نظيف واحترافي وتهتم بأدق التفاصيل."', '"I had the Moroccan bath with Ghazlan and it was more than wonderful: clean, professional work, and she cares about the smallest details."'],
                    ]; foreach ($reviews as [$nAr, $nEn, $tAr, $tEn]): ?>
                    <article class="review-card">
                        <div class="review-stars">★★★★★</div>
                        <p <?= ba($tAr, $tEn) ?>><?= e($tAr) ?></p>
                        <div class="review-meta"><?= tx($nAr, $nEn, 'span', 'review-name') ?><?= tx('قوقل', 'Google', 'span', 'review-source') ?></div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-controls">
                    <button class="carousel-btn" id="prevReview" aria-label="Previous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>
                    <div class="carousel-dots" id="reviewDots"></div>
                    <button class="carousel-btn" id="nextReview" aria-label="Next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button>
                </div>
            </div>
        </div>
    </section>

    <!-- ============== LOCATION ============== -->
    <section class="section location" id="location">
        <div class="container">
            <div class="location-grid">
                <div class="location-info">
                    <?= tx('موقعنا', 'Find Us', 'span', 'eyebrow') ?>
                    <?= tx('نستقبلكِ في حي الربوة، شرق الرياض', 'We welcome you in Al Rabwah, East Riyadh', 'h2', 'section-title') ?>

                    <div class="info-block">
                        <div class="info-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                        <div><?= tx('العنوان', 'Address', 'h4') ?>
                            <?= tx('7787 طريق عمر بن عبد العزيز الفرعي، حي الربوة، الرياض 12834', '7787 Omar Bin Abdulaziz Branch Road, Al Rabwah District, Riyadh 12834', 'p') ?></div>
                    </div>
                    <div class="info-block">
                        <div class="info-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.86 19.86 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.86 19.86 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                        <div><?= tx('الحجز والاستفسار', 'Reservations & Inquiries', 'h4') ?>
                            <p><a href="tel:+966566319009" dir="ltr" style="color:inherit;font-weight:700">+966 56 631 9009</a></p>
                            <p style="font-size:.85rem;opacity:.8"><?= tx('واتساب ومكالمات', 'WhatsApp & calls') ?></p></div>
                    </div>
                    <div class="info-block">
                        <div class="info-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
                        <div><?= tx('ساعات العمل', 'Operating Hours', 'h4') ?>
                            <p><?= tx('يومياً', 'Daily') ?> <?= tx('1:00 م — 10:00 م', '1:00 PM — 10:00 PM', 'strong') ?></p></div>
                    </div>
                    <div class="info-block">
                        <div class="info-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0z"/><path d="M16 8l-4 4-2-2"/></svg></div>
                        <div><?= tx('رمز الموقع', 'Plus Code', 'h4') ?>
                            <?= tx('MQQ5+P9 الربوة، الرياض', 'MQQ5+P9 Al Rabwah, Riyadh', 'p') ?></div>
                    </div>

                    <a class="btn btn-primary directions-btn" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=MQQ5%2BP9%20Riyadh">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?= tx('احصلي على الاتجاهات', 'Get Directions') ?>
                    </a>
                </div>
                <div class="location-map">
                    <iframe title="Map" src="https://www.google.com/maps?q=MQQ5%2BP9%20Riyadh&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- ============== FOOTER ============== -->
    <footer class="footer">
        <div class="container footer-inner">
            <div class="footer-brand">
                <a href="#hero" class="brand">
                    <span class="brand-mark"><?= BRAND_MARK ?></span>
                    <div class="footer-brand-text"><span class="brand-ar">أوفلاين سبا</span><span class="brand-en">Offline Spa</span></div>
                </a>
                <?= tx('ملاذكِ الخاص للاسترخاء والجمال. صالون تجميل وسبا نسائي في حي الربوة، شرق الرياض.', "Your private sanctuary of relaxation and beauty. A women's beauty salon & spa in Al Rabwah, East Riyadh.", 'p') ?>
            </div>
            <div class="footer-col">
                <?= tx('روابط', 'Links', 'h5') ?>
                <?= tx('تجربتنا', 'Our Experience', 'a', '', 'href="#about"') ?>
                <?= tx('الخدمات', 'Services', 'a', '', 'href="#menu"') ?>
                <?= tx('احجزي', 'Book', 'a', '', 'href="#reserve"') ?>
                <?= tx('الموقع', 'Location', 'a', '', 'href="#location"') ?>
            </div>
            <div class="footer-col">
                <?= tx('تواصل', 'Contact', 'h5') ?>
                <?= tx('واتساب مباشر', 'WhatsApp Direct', 'a', '', 'href="https://wa.me/966566319009" target="_blank" rel="noopener"') ?>
                <a href="tel:+966566319009" dir="ltr" style="unicode-bidi:plaintext">+966 56 631 9009</a>
            </div>
            <div class="footer-col">
                <?= tx('ساعات العمل', 'Hours', 'h5') ?>
                <p><?= tx('يومياً', 'Daily') ?> <?= tx('1:00 م — 10:00 م', '1:00 PM — 10:00 PM') ?></p>
            </div>
        </div>
        <div class="footer-bottom">
            <?= tx('© 2026 أوفلاين سبا. جميع الحقوق محفوظة.', '© 2026 Offline Spa. All rights reserved.') ?>
        </div>
    </footer>

    <a class="whatsapp-fab" href="https://wa.me/966566319009" target="_blank" rel="noopener" aria-label="WhatsApp">
        <svg viewBox="0 0 32 32" width="28" height="28" fill="currentColor"><path d="M16 3C9.4 3 4 8.4 4 15c0 2.5.8 4.9 2.2 6.9L4 29l7.4-2.1c1.9 1 4 1.5 6.2 1.5 6.6 0 12-5.4 12-12S22.6 3 16 3zm0 21.9c-1.9 0-3.7-.5-5.3-1.4l-.4-.2-4.4 1.2 1.2-4.3-.2-.4C5.7 18.4 5 16.7 5 15c0-6.1 4.9-11 11-11s11 4.9 11 11-4.9 10.9-11 10.9zm6.1-8.3c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7.1c-.3-.2-1.3-.5-2.5-1.5-.9-.8-1.5-1.8-1.7-2.1s0-.4.2-.6.3-.4.4-.5c.2-.2.2-.3.3-.5s0-.4 0-.5-.7-1.7-1-2.3c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4s-1 1-1 2.4 1.1 2.8 1.2 3 2.1 3.2 5.1 4.5c.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.9-.8 2.1-1.5s.2-1.4.2-1.5-.3-.3-.6-.4z"/></svg>
        <span class="fab-pulse"></span>
    </a>

    <div class="modal" id="successModal" aria-hidden="true">
        <div class="modal-overlay" data-close="modal"></div>
        <div class="modal-card">
            <button class="modal-close" data-close="modal" aria-label="Close">×</button>
            <div class="modal-icon">✓</div>
            <?= tx('تم استلام طلبكِ!', 'Request received!', 'h3') ?>
            <?= tx('سيتواصل معكِ فريق أوفلاين سبا قريباً لتأكيد موعدكِ. شكراً لاختياركِ لنا.', 'The Offline Spa team will contact you shortly to confirm your appointment. Thank you for choosing us.', 'p') ?>
            <button class="btn btn-primary" data-close="modal" <?= ba('حسناً', 'Got it') ?>>حسناً</button>
        </div>
    </div>

    <script>window.ROA_USER = <?= user_json() ?>;</script>
    <script src="site-auth.js?v=<?= @filemtime(__DIR__ . '/site-auth.js') ?>"></script>
    <script src="script.js?v=<?= @filemtime(__DIR__ . '/script.js') ?>"></script>
</body>
</html>