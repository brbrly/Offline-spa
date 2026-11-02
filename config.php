<?php
// ===== تحميل الإعدادات الحساسة =====
if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
}

// ===== إعدادات قاعدة البيانات =====
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'offline_spa';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

define('DB_HOST', $dbHost);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);

date_default_timezone_set('Asia/Riyadh');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

$pdoOpts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $pdoOpts);
} catch (PDOException $e) {
    // عرض الخطأ الحقيقي للتشخيص (سنقوم بحذفه بعد الإصلاح)
    die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage() . '<br>HOST: ' . DB_HOST . '<br>USER: ' . DB_USER);
}

const BRAND_MARK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3.5c2.3 2.3 3.3 4.6 3.3 6.9S13.8 15 12 15s-3.3-2.3-3.3-4.6S9.7 5.8 12 3.5z"/><path d="M3.5 10c2.7.1 4.6 1.3 5.8 3.4M20.5 10c-2.7.1-4.6 1.3-5.8 3.4"/><path d="M6 18.5c2.2-1.7 4-2.5 6-2.5s3.8.8 6 2.5"/></svg>';

require __DIR__ . '/schema.php';
if (empty($_SESSION['schema_ok'])) {
    try { ensure_schema($pdo); $_SESSION['schema_ok'] = true; } catch (Throwable $e) { /* يكمل بدون تحديث */ }
}

/* ===== اللغة (تُحفظ في كوكي من زر اللغة وتنطبق على كل الصفحات) ===== */
function lang() { return (($_COOKIE['spa_lang'] ?? 'ar') === 'en') ? 'en' : 'ar'; }
function t($ar, $en) { return lang() === 'en' ? $en : $ar; }

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf_token() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_check() { return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']); }
function is_logged_in() { return !empty($_SESSION['user_id']); }
function require_login() { if (!is_logged_in()) { header('Location: login.php'); exit; } }
function client_ip() { return $_SERVER['REMOTE_ADDR'] ?? ''; }

function next_param() { return (($_POST['next'] ?? $_GET['next'] ?? '') === 'reserve') ? 'reserve' : ''; }
function after_login_url() { return next_param() === 'reserve' ? 'index.php#reserve' : 'index.php'; }

function user_json() {
    $u = is_logged_in() ? ['name' => $_SESSION['user_name'] ?? '', 'phone' => $_SESSION['user_phone'] ?? '', 'csrf' => csrf_token()] : null;
    return json_encode($u, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function valid_phone($p) { return (bool)preg_match('/^05\d{8}$/', $p); }

function check_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return t('البريد الإلكتروني غير صحيح.', 'Invalid email address.');
    $domain = substr(strrchr($email, '@'), 1);
    if (!preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/', $domain)) return t('نطاق البريد غير صحيح.', 'Invalid email domain.');
    $popular = ['gmail.com','hotmail.com','outlook.com','yahoo.com','icloud.com','live.com','msn.com','me.com','proton.me','protonmail.com'];
    $other   = ['mail.com','aol.com','zoho.com','gmx.com','yandex.com','hotmail.co.uk','yahoo.co.uk','outlook.sa'];
    if (in_array($domain, $popular, true) || in_array($domain, $other, true)) return null;
    foreach ($popular as $p) {
        if (levenshtein($domain, $p) <= 2) return t('هل تقصدين @' . $p . ' ؟ راجعي كتابة البريد.', 'Did you mean @' . $p . '? Please check your email.');
    }
    if (function_exists('checkdnsrr') && !checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
        return t('نطاق البريد (' . $domain . ') غير موجود. راجعي كتابته.', 'Email domain (' . $domain . ') does not exist. Please check it.');
    }
    return null;
}

/* ===== سجل النشاط (الموقع + الإدارة) ===== */
function log_event($type, $name, $action, $details = '', $when = null) {
    global $pdo;
    try {
        $pdo->prepare('INSERT INTO activity_log (actor_type, actor_name, action, details, ip, created_at) VALUES (?,?,?,?,?,?)')
            ->execute([$type, mb_substr((string)$name, 0, 100), $action, mb_substr((string)$details, 0, 255), client_ip(), $when ?: date('Y-m-d H:i:s')]);
    } catch (Throwable $e) { /* السجل لا يوقف الموقع */ }
}

/* ===== الإدارة: مدير (manager) وعضو (member) ===== */
function is_admin()   { return !empty($_SESSION['is_admin']) && empty($_SESSION['admin_2fa_pending']); }
function is_manager() { return is_admin() && ($_SESSION['admin_role'] ?? '') === 'manager'; }

function admin_close($when, $action, $details = '') {
    global $pdo;
    $type = (($_SESSION['admin_role'] ?? '') === 'manager') ? 'manager' : 'member';
    log_event($type, $_SESSION['admin_name'] ?? '', $action, $details, $when);
    try { $pdo->prepare('UPDATE admins SET last_logout_at = ? WHERE id = ?')->execute([$when, (int)($_SESSION['admin_id'] ?? 0)]); } catch (Throwable $e) {}
    foreach (['is_admin', 'admin_id', 'admin_role', 'admin_name', 'last_activity'] as $k) unset($_SESSION[$k]);
}

function require_admin() {
    global $pdo;
    if (!is_admin()) { header('Location: login.php'); exit; }
    $now = time();
    $last = (int)($_SESSION['last_activity'] ?? $now);
    if ($now - $last > 1800) {                       // خمول 30 دقيقة
        admin_close(date('Y-m-d H:i:s', $last), 'admin_timeout', 'خمول أكثر من 30 دقيقة');
        header('Location: login.php?timeout=1'); exit;
    }
    $st = $pdo->prepare('SELECT username, role, is_active FROM admins WHERE id = ?');
    $st->execute([(int)($_SESSION['admin_id'] ?? 0)]);
    $a = $st->fetch();
    if (!$a || !$a['is_active']) {
        foreach (['is_admin', 'admin_id', 'admin_role', 'admin_name', 'last_activity'] as $k) unset($_SESSION[$k]);
        header('Location: login.php'); exit;
    }
    $_SESSION['admin_role'] = $a['role'];
    $_SESSION['admin_name'] = $a['username'];
    $_SESSION['last_activity'] = $now;
    $pdo->prepare('UPDATE admins SET last_seen_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), (int)$_SESSION['admin_id']]);
}

/* ===== قالب صفحات الحساب ===== */
function auth_header($title) { $en = lang() === 'en'; ?>
<!DOCTYPE html>
<html lang="<?= lang() ?>" dir="<?= $en ? 'ltr' : 'rtl' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?> | <?= $en ? 'Offline Spa' : 'أوفلاين سبا' ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=<?= @filemtime(__DIR__ . '/styles.css') ?>">
  <link rel="stylesheet" href="auth.css?v=<?= @filemtime(__DIR__ . '/auth.css') ?>">
  <style>
    .auth-lang{direction:ltr;position:fixed;top:18px;inset-inline-end:18px;z-index:50;display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;border:1px solid rgba(249,246,240,.35);background:rgba(27,33,30,.45);backdrop-filter:blur(6px);color:rgba(249,246,240,.6);font-weight:700;font-size:.85rem;letter-spacing:.1em;cursor:pointer;transition:all .28s}
    .auth-lang:hover{border-color:#C5A059}
    .auth-lang span.on{color:#E2C98C;border-bottom:2px solid #E2C98C}
    .auth-lang span:not(.on){opacity:.65}
    html[lang="en"] .brand-ar{display:none}
    html[lang="ar"] .brand-en{display:none}
    html[lang="en"] .brand-en{font-size:1.05rem;letter-spacing:.22em;color:#F9F6F0;font-weight:700}
    .auth-lang i{font-style:normal;opacity:.4}
  </style>
</head>
<body class="auth-body<?= $en ? ' en' : '' ?>">
<main class="auth-wrap">
  <button type="button" class="auth-lang" id="authLang" title="<?= $en ? 'Switch to Arabic' : 'التبديل إلى الإنجليزية' ?>" aria-label="Change language">
    <span class="<?= !$en ? 'on' : '' ?>">AR</span><i>/</i><span class="<?= $en ? 'on' : '' ?>">EN</span>
  </button>
  <a href="index.php" class="brand auth-brand">
    <span class="brand-mark"><?= BRAND_MARK ?></span>
    <div class="brand-text"><span class="brand-ar">أوفلاين سبا</span><span class="brand-en">Offline Spa</span></div>
  </a>
<?php }

function auth_footer() { ?>
  <p class="dev-credit auth-credit">
    <?= t('تطوير وتصميم: عبد المحسن • حقوق الطبع والنشر محفوظة © 2026', 'Developed by Abdulmohsen • Copyright &amp; publishing rights reserved © 2026') ?><br>
    <?= t('للتواصل:', 'Contact:') ?> <a href="https://instagram.com/c01xm" target="_blank" rel="noopener"><?= t('انستقرام', 'Instagram') ?> @c01xm</a>
  </p>
</main>
<script src="site-auth.js"></script>
</body>
</html>
<?php }
