<?php
require 'config.php';
require 'verification.php';
if (is_logged_in()) { header('Location: ' . after_login_url()); exit; }

$errors = []; $name = $email = $phone = '';
$next = next_param();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) $errors[] = t('انتهت صلاحية الصفحة، حدّثيها وحاولي مرة ثانية.', 'Page expired, please refresh and try again.');
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (mb_strlen($name) < 2)  $errors[] = t('اكتبي اسمكِ الكامل.', 'Please enter your full name.');
    if ($m = check_email($email)) $errors[] = $m;
    if (!valid_phone($phone))  $errors[] = t('رقم الجوال لازم يكون 10 أرقام ويبدأ بـ 05.', 'Phone must be 10 digits starting with 05.');
    if (strlen($pass) < 8)     $errors[] = t('كلمة المرور لازم تكون 8 أحرف على الأقل.', 'Password must be at least 8 characters.');
    if ($pass !== $pass2)      $errors[] = t('كلمتا المرور غير متطابقتين.', 'Passwords do not match.');

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, email_verified FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing && $existing['email_verified']) {
            $errors[] = t('هذا البريد مسجّل من قبل. جرّبي تسجيل الدخول.', 'This email is already registered. Try logging in.');
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            if ($existing) {            // حساب لم يُفعَّل بعد: نحدّث بياناته ونعيد إرسال الرمز
                $pdo->prepare('UPDATE users SET name = ?, phone = ?, password_hash = ? WHERE id = ?')
                    ->execute([$name, $phone, $hash, $existing['id']]);
                $uid = (int)$existing['id'];
            } else {
                $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, email_verified) VALUES (?,?,?,?,0)')
                    ->execute([$name, $email, $phone, $hash]);
                $uid = (int)$pdo->lastInsertId();
            }
            session_regenerate_id(true);
            $_SESSION['pending_user_id'] = $uid;       // لن يدخل فعلياً قبل إدخال الرمز
            $_SESSION['verify_flash']    = issue_verification($pdo, $uid, $email, $name);
            log_event('customer', $name, 'customer_register', $email);
            header('Location: verify.php' . ($next ? '?next=reserve' : '')); exit;
        }
    }
}
auth_header(t('إنشاء حساب', 'Create Account'));
?>
  <div class="auth-card">
    <h1><?= t('إنشاء حساب جديد', 'Create a New Account') ?></h1>
    <p class="auth-sub"><?= t('سجّلي لإكمال حجز موعدكِ في أوفلاين سبا', 'Sign up to complete your table reservation at Offline Spa') ?></p>
    <?php foreach ($errors as $err): ?><div class="auth-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <div class="form-field"><label><?= t('الاسم الكامل', 'Full Name') ?></label>
        <input type="text" name="name" value="<?= e($name) ?>" required></div>
      <div class="form-field"><label><?= t('البريد الإلكتروني', 'Email') ?></label>
        <input type="email" name="email" value="<?= e($email) ?>" dir="ltr" required></div>
      <div class="form-field"><label><?= t('رقم الجوال (10 أرقام)', 'Phone (10 digits)') ?></label>
        <input type="tel" name="phone" value="<?= e($phone) ?>" placeholder="0500000000" maxlength="10" inputmode="numeric" dir="ltr" required></div>
      <div class="form-field"><label><?= t('كلمة المرور', 'Password') ?></label>
        <input type="password" name="password" required></div>
      <div class="form-field"><label><?= t('تأكيد كلمة المرور', 'Confirm Password') ?></label>
        <input type="password" name="password2" required></div>
      <button type="submit" class="btn btn-primary btn-full"><?= t('إنشاء الحساب', 'Create Account') ?></button>
    </form>
    <p class="auth-switch"><?= t('عندكِ حساب؟', 'Already have an account?') ?> <a href="login.php<?= $next ? '?next=reserve' : '' ?>"><?= t('سجّلي دخولكِ', 'Log in') ?></a></p>
    <p class="auth-switch"><a href="index.php"><?= t('← الرجوع للموقع', '← Back to website') ?></a></p>
  </div>
<?php auth_footer(); ?>
