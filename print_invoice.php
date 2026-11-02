<?php
require 'config.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die('رقم الفاتورة غير صحيح.');
}

$st = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
$st->execute([$id]);
$r = $st->fetch();

if (!$r) {
    die('الحجز غير موجود.');
}

// حساب الأسعار والضريبة
$price     = (float)($r['price'] > 0 ? $r['price'] : 0);
$discount  = (float)($r['discount_amount'] > 0 ? $r['discount_amount'] : 0);
$final     = (float)($r['final_price'] > 0 ? $r['final_price'] : max(0, $price - $discount));

$taxableAmount = $final / 1.15;
$taxAmount     = $final - $taxableAmount;

// إنشاء نص للـ QR Code
$qrData = "أوفلاين سبا | الرقم الضريبي: 310123456700003 | الفاتورة: #" . sprintf('%06d', $r['id']) . " | المجموع: " . number_format($final, 2) . " ر.س";
$qrUrl  = "https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=" . urlencode($qrData);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>فاتورة #<?= sprintf('%06d', $r['id']) ?></title>
  <style>
    @page {
      size: 80mm auto;
      margin: 0;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Courier New', Courier, monospace, sans-serif;
      color: #000 !important;
      background: #fff !important;
    }

    body {
      width: 78mm;
      margin: 0 auto;
      padding: 8px 4px;
      font-size: 11px;
      line-height: 1.3;
    }

    .center {
      text-align: center;
    }

    .dashed-line {
      border-bottom: 1px dashed #000;
      margin: 6px 0;
    }

    .thick-line {
      border-bottom: 2px solid #000;
      margin: 6px 0;
    }

    .logo-title {
      font-size: 20px;
      font-weight: 900;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 2px;
    }

    .sub-title {
      font-size: 10px;
      font-weight: bold;
    }

    .tax-badge {
      font-size: 11px;
      font-weight: bold;
      border: 1px solid #000;
      display: inline-block;
      padding: 2px 8px;
      margin: 4px 0;
    }

    .meta-table {
      width: 100%;
      font-size: 10px;
      margin: 4px 0;
    }

    .meta-table td {
      padding: 2px 0;
    }

    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin: 6px 0;
      font-size: 11px;
    }

    .items-table th {
      border-bottom: 1px solid #000;
      padding-bottom: 4px;
      text-align: right;
    }

    .items-table td {
      padding: 4px 0;
    }

    .totals-area {
      font-size: 11px;
    }

    .row-flex {
      display: flex;
      justify-content: space-between;
      margin-bottom: 3px;
    }

    .grand-total-box {
      font-size: 15px;
      font-weight: bold;
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      padding: 5px 0;
      margin: 5px 0;
    }

    .qr-container {
      margin-top: 8px;
    }

    .qr-container img {
      width: 100px;
      height: 100px;
    }

    .footer-text {
      font-size: 9px;
      margin-top: 6px;
    }

    .btn-print {
      background: #000 !important;
      color: #fff !important;
      border: none;
      padding: 8px 16px;
      font-size: 12px;
      cursor: pointer;
      font-weight: bold;
      width: 100%;
      margin-bottom: 10px;
    }

    @media print {
      .no-print { display: none !important; }
      body { width: 100%; padding: 0; }
    }
  </style>
</head>
<body>

  <div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ طباعة الفاتورة الحرارية</button>
  </div>

  <div class="center">
    <div class="logo-title">OFFLINE SPA</div>
    <div class="sub-title">أوفلاين سبا للعناية والاسترخاء</div>
    <div>الرياض - المملكة العربية السعودية</div>
    <div>جوال: 0500000000</div>
    <div class="tax-badge">فاتورة ضريبية مبسطة</div>
    <div>الرقم الضريبي: 310123456700003</div>
  </div>

  <div class="dashed-line"></div>

  <table class="meta-table">
    <tr>
      <td><b>رقم الفاتورة:</b> #<?= sprintf('%06d', $r['id']) ?></td>
      <td style="text-align: left;"><b>التاريخ:</b> <?= e($r['res_date']) ?></td>
    </tr>
    <tr>
      <td><b>العميل:</b> <?= e($r['name']) ?></td>
      <td style="text-align: left;"><b>الوقت:</b> <?= e(substr($r['res_time'], 0, 5)) ?></td>
    </tr>
    <tr>
      <td colspan="2"><b>الجوال:</b> <?= e($r['phone']) ?></td>
    </tr>
    <tr>
      <td colspan="2"><b>الأخصائية:</b> <?= e($r['specialist_name'] ?: 'أي أخصائية') ?></td>
    </tr>
  </table>

  <div class="dashed-line"></div>

  <table class="items-table">
    <thead>
      <tr>
        <th>الخدمة</th>
        <th style="text-align: center;">العدد</th>
        <th style="text-align: left;">السعر</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?= e($r['service_name']) ?></td>
        <td style="text-align: center;">1</td>
        <td style="text-align: left;"><?= number_format($price, 2) ?></td>
      </tr>
    </tbody>
  </table>

  <div class="dashed-line"></div>

  <div class="totals-area">
    <div class="row-flex"><span>المجموع (غير شامل الضريبة):</span> <span><?= number_format($taxableAmount, 2) ?> ر.س</span></div>
    <?php if ($discount > 0): ?>
      <div class="row-flex"><span>الخصم المباشر:</span> <span>-<?= number_format($discount, 2) ?> ر.س</span></div>
    <?php endif; ?>
    <div class="row-flex"><span>ضريبة القيمة المضافة (15%):</span> <span><?= number_format($taxAmount, 2) ?> ر.س</span></div>
    
    <div class="grand-total-box row-flex">
      <span>الإجمالي النهائي:</span>
      <span><?= number_format($final, 2) ?> ر.س</span>
    </div>
    
    <div class="row-flex"><span>طريقة الدفع:</span> <span>شبكة / كاونتر</span></div>
  </div>

  <div class="dashed-line"></div>

  <div class="center qr-container">
    <img src="<?= $qrUrl ?>" alt="QR Code">
    <div class="footer-text">
      <p><b>شكراً لزيارتكم أوفلاين سبا ✨</b></p>
      <p>الأسعار شاملة ضريبة القيمة المضافة 15%</p>
      <p>نأمل رؤيتكم مجدداً قريبـاً!</p>
    </div>
  </div>

</body>
</html>