<?php
require 'config.php';
require 'verification.php';
if (is_admin()) { header('Location: admin.php'); exit; }
if (is_logged_in()) { header('Location: ' . after_login_url()); exit; }

$errors = []; $ident = '';
$next = next_param();
$_SESSION['attempts']   = $_SESSION['attempts'] ?? 0;
$_SESSION['lock_until'] = $_SESSION['lock_until'] ?? 0;
if (isset($_GET['timeout'])) $errors[] = t('انتهت جلستك بسبب الخمول. سجّلي دخولكِ من جديد.', 'Your session expired due to inactivity. Please log in again.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (time() < $_SESSION['lock_until']) {
        $mins = ceil(($_SESSION['lock_until'] - time()) / 60);
        $errors[] = t("محاولات كثيرة. حاولي بعد $mins دقائق.", "Too many attempts. Try again in $mins minutes.");
    } elseif (!csrf_check()) {
        $errors[] = t('انتهت صلاحية الصفحة، حدّثيها وحاولي مرة ثانية.', 'Page expired, please refresh and try again.');
    } else {
        $ident = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';

        if (strpos($ident, '@') === false) {
            // اسم مستخدم: دخول الإدارة (مدير أو عضو)
            $isMgrName = in_array($ident, ['admin-mn', 'adimn-mn'], true);
            $names = $isMgrName ? ['admin-mn', 'adimn-mn'] : [$ident, $ident];
            $adm = false; $setupMissing = false;
            try {
                $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role, is_active FROM admins WHERE username IN (?, ?)');
                $stmt->execute($names);
                $adm = $stmt->fetch();
                if ((int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) $setupMissing = true;
            } catch (PDOException $ex) { $setupMissing = true; }

            if ($adm && password_verify($pass, $adm['password_hash'])) {
                if (!$adm['is_active']) {
                    $errors[] = t('هذا الحساب موقوف. تواصل مع المدير.', 'This account is suspended. Contact the manager.');
                    log_event($adm['role'] === 'manager' ? 'manager' : 'member', $adm['username'], 'admin_login_failed', 'حساب موقوف');
                } elseif ($adm['role'] === 'super_admin' || $adm['username'] === 'admin-mn') {
                    session_regenerate_id(true);
                    $_SESSION['is_admin']      = true;
                    $_SESSION['admin_id']      = (int)$adm['id'];
                    $_SESSION['admin_role']    = $adm['role'];
                    $_SESSION['admin_name']    = $adm['username'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['attempts']      = 0;
                    $now = date('Y-m-d H:i:s');
                    $pdo->prepare('UPDATE admins SET last_login_at = ?, last_seen_at = ? WHERE id = ?')->execute([$now, $now, $adm['id']]);
                    log_event($adm['role'] === 'manager' ? 'manager' : 'member', $adm['username'], 'admin_login');
                    header('Location: admin.php'); exit;
                } else {
                    session_regenerate_id(true);
                    $_SESSION['pending_admin_id']  = (int)$adm['id'];
                    $_SESSION['admin_2fa_pending'] = true;
                    send_admin_2fa($pdo, $adm);
                    header('Location: admin-verify.php'); exit;
                }
            } elseif ($setupMissing && $isMgrName) {
                $errors[] = t('حساب الأدمن غير مهيأ بعد. افتح setup-admin.php مرة واحدة من المتصفح.', 'Admin account is not set up yet. Open setup-admin.php once in your browser.');
            }
        } else {
            // عميل عادي بالبريد
            $stmt = $pdo->prepare('SELECT id, name, phone, password_hash, email_verified FROM users WHERE email = ?');
            $stmt->execute([$ident]);
            $user = $stmt->fetch();
            if ($user && password_verify($pass, $user['password_hash'])) {
                if (!$user['email_verified']) {        // البريد غير مفعّل: نرسل رمز جديد ونحوّله لصفحة التحقق
                    session_regenerate_id(true);
                    $_SESSION['pending_user_id'] = (int)$user['id'];
                    $_SESSION['verify_flash']    = issue_verification($pdo, (int)$user['id'], $ident, $user['name']);
                    header('Location: verify.php' . ($next ? '?next=reserve' : '')); exit;
                }
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_phone'] = $user['phone'];
                $_SESSION['attempts']   = 0;
                log_event('customer', $user['name'], 'customer_login', $ident);
                header('Location: ' . after_login_url()); exit;
            }
        }
        if (!$errors) {
            $_SESSION['attempts']++;
            log_event('guest', $ident, strpos($ident, '@') === false ? 'admin_login_failed' : 'customer_login_failed');
            if ($_SESSION['attempts'] >= 5) { $_SESSION['lock_until'] = time() + 300; $_SESSION['attempts'] = 0; }
            $errors[] = t('بيانات الدخول غير صحيحة.', 'Incorrect login details.');
        }
    }
}
auth_header(t('تسجيل الدخول', 'Log In'));
?>
  <div class="auth-card">
    <h1><?= t('أهلاً بعودتكِ', 'Welcome Back') ?></h1>
    <p class="auth-sub"><?= t('سجّلي دخولكِ لإكمال الحجز', 'Log in to complete your reservation') ?></p>
    <?php foreach ($errors as $err): ?><div class="auth-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <div class="form-field"><label><?= t('البريد الإلكتروني أو اسم المستخدم', 'Email or username') ?></label>
        <input type="text" name="email" value="<?= e($ident) ?>" dir="ltr" autocapitalize="off" autocomplete="username" required autofocus></div>
      <div class="form-field"><label><?= t('كلمة المرور', 'Password') ?></label>
        <input type="password" name="password" required></div>
      <button type="submit" class="btn btn-primary btn-full"><?= t('دخول', 'Log In') ?></button>
    </form>
    <p class="auth-switch"><?= t('ما عندكِ حساب؟', "Don't have an account?") ?> <a href="register.php<?= $next ? '?next=reserve' : '' ?>"><?= t('أنشئ حساب جديد', 'Create one') ?></a></p>
    <p class="auth-switch"><a href="index.php"><?= t('← الرجوع للموقع', '← Back to website') ?></a></p>
  </div>
<?php auth_footer(); ?>
