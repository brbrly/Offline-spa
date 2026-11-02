<?php
// خدمات السبا وقائمة الأخصائيات (من قاعدة البيانات) + دوال العرض ثنائية اللغة
$CATS = [
    'moroccan' => ['الحمام المغربي', 'Moroccan Bath', '🛁'],
    'nails'    => ['المناكير والبدكير', 'Manicure & Pedicure', '💅'],
    'colors'   => ['الطلاء والأرت', 'Polish & Nail Art', '🎨'],
];

$menu = []; $specialists = [];
try {
    foreach ($pdo->query('SELECT * FROM products WHERE is_active = 1 ORDER BY category, sort_order, id')->fetchAll() as $r) $menu[$r['category']][] = $r;
    $specialists = $pdo->query('SELECT * FROM specialists WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
} catch (PDOException $ex) { /* الجداول غير جاهزة بعد */ }

function ba($ar, $en) { return 'data-ar="' . e($ar) . '" data-en="' . e($en) . '"'; }
function tx($ar, $en, $tag = 'span', $cls = '', $extra = '') {
    return '<' . $tag . ($cls ? ' class="' . $cls . '"' : '') . ' ' . ba($ar, $en) . ($extra ? ' ' . $extra : '') . '>' . e($ar) . '</' . $tag . '>';
}

function render_items($items) {
    if (!$items) {
        echo '<p style="grid-column:1/-1;text-align:center;opacity:.6">' . tx('لا توجد خدمات حالياً.', 'No services available right now.') . '</p>';
        return;
    }
    foreach ($items as $p) {
        $nameEn = $p['name_en'] !== '' ? $p['name_en'] : $p['name_ar'];
        $descEn = $p['desc_en'] !== '' ? $p['desc_en'] : $p['desc_ar'];
        echo '<div class="menu-item' . ($p['highlight'] ? ' highlight' : '') . '">';
        echo '<div class="item-head"><h4 ' . ba($p['name_ar'], $nameEn) . '>' . e($p['name_ar']) . '</h4>';
        echo '<span class="item-price" data-ar="' . e($p['price']) . ' ر.س" data-en="' . e($p['price']) . ' SAR">' . e($p['price']) . ' ر.س</span></div>';
        if ($p['desc_ar'] !== '') echo '<p ' . ba($p['desc_ar'], $descEn) . '>' . e($p['desc_ar']) . '</p>';
        if ($p['badge_ar'] !== '') {
            $badgeEn = $p['badge_en'] !== '' ? $p['badge_en'] : $p['badge_ar'];
            echo '<span class="item-badge' . ($p['badge_gold'] ? ' gold' : '') . '" ' . ba($p['badge_ar'], $badgeEn) . '>' . e($p['badge_ar']) . '</span>';
        }
        echo '</div>';
    }
}
