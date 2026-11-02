أوفلاين سبا | Offline Spa — خطوات التشغيل على XAMPP

1) انسخ كل الملفات داخل مجلد جديد في htdocs، مثلاً:  C:\xampp\htdocs\offline-spa\
   وضع بجانبها مجلد PHPMailer\src (PHPMailer.php و SMTP.php و Exception.php).
2) شغّل Apache و MySQL من لوحة XAMPP.
3) افتح مرة واحدة فقط:  http://localhost/offline-spa/setup-admin.php
   (ينشئ القاعدة offline_spa والجداول تلقائياً، ويدخل الخدمات والأخصائيات، وينشئ حساب المدير، ثم يحذف نفسه).
4) افتح mail-config.php وضع MAIL_PASS (مفتاح Brevo) و MAIL_FROM (مرسل معتمد في Brevo).
5) الموقع:  http://localhost/offline-spa/
   دخول الإدارة من صفحة login.php:  admin-mn / admin1234  (غيّر كلمة المرور قبل أي نشر حقيقي).
