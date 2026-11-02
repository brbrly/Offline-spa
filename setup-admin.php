<?php
// يُشغَّل مرة واحدة من جهازك (localhost) ثم يحذف نفسه: يدخل الخدمات والأخصائيات وينشئ حساب المدير.
require 'config.php';
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(403); die('Forbidden'); }

$log = [];
$seed = json_decode(<<<'JSON'
{
 "services": [
  {
   "category": "moroccan",
   "name_ar": "حمام مغربي كلاسيك بالأعشاب",
   "name_en": "Classic Herbal Moroccan Bath",
   "desc_ar": "",
   "desc_en": "",
   "price": "300",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "moroccan",
   "name_ar": "حمام مغربي أوفلاين سبا لترطيب اليدين",
   "name_en": "Offline Spa Moroccan Bath with Hand Hydration",
   "desc_ar": "",
   "desc_en": "",
   "price": "400",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "moroccan",
   "name_ar": "حمام مغربي أوفلاين سبا سيغنتشر",
   "name_en": "Offline Spa Signature Moroccan Bath",
   "desc_ar": "",
   "desc_en": "",
   "price": "470",
   "badge_ar": "تجربة مميزة",
   "badge_en": "Signature Experience",
   "badge_gold": 1,
   "highlight": 1
  },
  {
   "category": "nails",
   "name_ar": "مناكير كلاسيك",
   "name_en": "Classic Manicure",
   "desc_ar": "",
   "desc_en": "",
   "price": "80",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "nails",
   "name_ar": "بدكير كلاسيك",
   "name_en": "Classic Pedicure",
   "desc_ar": "",
   "desc_en": "",
   "price": "90",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "nails",
   "name_ar": "مناكير زيت دادي العضوي",
   "name_en": "Organic Dadi Oil Manicure",
   "desc_ar": "",
   "desc_en": "",
   "price": "115",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "nails",
   "name_ar": "مناكير من أورالي أوفلاين سبا",
   "name_en": "Orly Manicure by Offline Spa",
   "desc_ar": "",
   "desc_en": "",
   "price": "130",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "nails",
   "name_ar": "بدكير من أورالي أوفلاين سبا",
   "name_en": "Orly Pedicure by Offline Spa",
   "desc_ar": "",
   "desc_en": "",
   "price": "120",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "nails",
   "name_ar": "مناكير من بوندس فوش نيويورك",
   "name_en": "Bonds Fosh New York Manicure",
   "desc_ar": "",
   "desc_en": "",
   "price": "135",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "nails",
   "name_ar": "بدكير من بوندس فوش نيويورك",
   "name_en": "Bonds Fosh New York Pedicure",
   "desc_ar": "",
   "desc_en": "",
   "price": "170",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "nails",
   "name_ar": "مناكير الأحجار الساخنة",
   "name_en": "Hot Stone Manicure",
   "desc_ar": "",
   "desc_en": "",
   "price": "150",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "nails",
   "name_ar": "بدكير الأحجار الساخنة",
   "name_en": "Hot Stone Pedicure",
   "desc_ar": "",
   "desc_en": "",
   "price": "200",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "nails",
   "name_ar": "قفازات الكولاجين",
   "name_en": "Collagen Gloves",
   "desc_ar": "",
   "desc_en": "",
   "price": "35",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "colors",
   "name_ar": "لون عادي يد/أرجل",
   "name_en": "Regular Polish – Hands/Feet",
   "desc_ar": "",
   "desc_en": "",
   "price": "30",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "colors",
   "name_ar": "تغيير لون مناكير عادي مع تنظيف سريع",
   "name_en": "Regular Polish Change with Quick Clean",
   "desc_ar": "",
   "desc_en": "",
   "price": "50",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "colors",
   "name_ar": "لون أومبري مناكير عادي",
   "name_en": "Regular Ombré Manicure",
   "desc_ar": "",
   "desc_en": "",
   "price": "83",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "colors",
   "name_ar": "لون جل يد/أرجل",
   "name_en": "Gel Polish – Hands/Feet",
   "desc_ar": "",
   "desc_en": "",
   "price": "100",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "colors",
   "name_ar": "لون أومبري مناكير جل",
   "name_en": "Gel Ombré Manicure",
   "desc_ar": "",
   "desc_en": "",
   "price": "169",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "colors",
   "name_ar": "بياب يد/أرجل",
   "name_en": "Baby Boomer – Hands/Feet",
   "desc_ar": "",
   "desc_en": "",
   "price": "199",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "colors",
   "name_ar": "فرينش بياب يد/أرجل",
   "name_en": "French Baby Boomer – Hands/Feet",
   "desc_ar": "",
   "desc_en": "",
   "price": "239",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "colors",
   "name_ar": "ستكر أظافر - ظفر واحد",
   "name_en": "Nail Sticker – Per Nail",
   "desc_ar": "",
   "desc_en": "",
   "price": "10",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "colors",
   "name_ar": "ديزاين أظافر ثري دي أو أحجار - ظفر واحد",
   "name_en": "3D or Gem Nail Design – Per Nail",
   "desc_ar": "",
   "desc_en": "",
   "price": "25",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  },
  {
   "category": "colors",
   "name_ar": "رسم أظافر اليد كاملة",
   "name_en": "Full Hand Nail Art",
   "desc_ar": "",
   "desc_en": "",
   "price": "200",
   "badge_ar": "",
   "badge_en": "",
   "badge_gold": 0,
   "highlight": 0
  }
 ],
 "specialists": [
  {
   "name_ar": "رنيم",
   "name_en": "Raneem"
  },
  {
   "name_ar": "غزلان",
   "name_en": "Ghazlan"
  },
  {
   "name_ar": "أمل",
   "name_en": "Amal"
  }
 ]
}
JSON
, true);

if ((int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() === 0) {
    $ins = $pdo->prepare('INSERT INTO products (category,name_ar,name_en,desc_ar,desc_en,price,badge_ar,badge_en,badge_gold,highlight,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($seed['services'] as $i => $p) {
        $ins->execute([$p['category'], $p['name_ar'], $p['name_en'], $p['desc_ar'], $p['desc_en'], $p['price'],
                       $p['badge_ar'], $p['badge_en'], $p['badge_gold'], $p['highlight'], $i]);
    }
    $log[] = 'تم إدخال ' . count($seed['services']) . ' خدمة.';
} else { $log[] = 'الخدمات موجودة من قبل، لم يتم تغييرها.'; }

if ((int)$pdo->query('SELECT COUNT(*) FROM specialists')->fetchColumn() === 0) {
    $ins = $pdo->prepare('INSERT INTO specialists (name_ar, name_en, sort_order) VALUES (?,?,?)');
    foreach ($seed['specialists'] as $i => $s) $ins->execute([$s['name_ar'], $s['name_en'], $i]);
    $log[] = 'تم إدخال ' . count($seed['specialists']) . ' أخصائيات.';
} else { $log[] = 'الأخصائيات موجودات من قبل.'; }

$hash = password_hash('admin1234', PASSWORD_DEFAULT);
$find = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
$find->execute(['admin-mn']);
if ($row = $find->fetch()) {
    $pdo->prepare("UPDATE admins SET password_hash = ?, role = 'manager', is_active = 1 WHERE id = ?")->execute([$hash, $row['id']]);
    $log[] = 'تم تحديث حساب المدير.';
} else {
    $pdo->prepare("INSERT INTO admins (username, password_hash, role) VALUES (?, ?, 'manager')")->execute(['admin-mn', $hash]);
    $log[] = 'تم إنشاء حساب المدير.';
}

$deleted = @unlink(__FILE__);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>تهيئة أوفلاين سبا</title>
<style>body{font-family:Tahoma,sans-serif;background:#F9F6F0;padding:40px;line-height:2}.box{max-width:560px;margin:auto;background:#fff;padding:30px;border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.1)}</style></head>
<body><div class="box">
<h2>✓ تمت التهيئة</h2>
<ul><?php foreach ($log as $l): ?><li><?= htmlspecialchars($l) ?></li><?php endforeach; ?></ul>
<p><?= $deleted ? 'تم حذف ملف التهيئة تلقائياً للأمان.' : '⚠ احذف الملف setup-admin.php يدوياً الحين.' ?></p>
<p><a href="login.php">الذهاب لتسجيل الدخول</a></p>
</div></body></html>
