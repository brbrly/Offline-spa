<?php
// ===== تحميل الإعدادات الحساسة =====
if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

// إعدادات البريد (Brevo SMTP) — القيم الافتراضية إذا لم تكن في env.php
const MAIL_HOST      = 'smtp-relay.brevo.com';                 // SMTP Server
const MAIL_PORT      = 587;                                     // Port

// يتم جلب القيم الحساسة من env.php
if (!defined('MAIL_USER')) define('MAIL_USER', 'your_user_here');
if (!defined('MAIL_PASS')) define('MAIL_PASS', 'your_pass_here');
if (!defined('MAIL_FROM')) define('MAIL_FROM', 'your_from_here');
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'Offline Spa');
if (!defined('MAIL_DEBUG')) define('MAIL_DEBUG', false);
