<?php
require 'config.php';
require 'verification.php';
require_login();

$errors = []; $ok = false;
$stmt = $pdo->prepare('SELECT id, name, email, phone, password_hash FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) { session_destroy(); header('Location: login.php'); exit; }

$name = $user['name']; $email = $user['email']; $phone = $user['phone'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) $errors[] = t('انتهت صلاحية الصفحة، حدّثيها وحاولي مرة ثانية.', 'Page expired, please refresh and try again.');
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $cur   = $_POST['current_password'] ?? '';
    $new   = $_POST['new_password'] ?? '';
    $new2  = $_POST['new_password2'] ?? '';

    if (mb_strlen($name) < 2) $errors[] = t('اكتبي اسمكِ الكامل.', 'Please enter your full name.');
    if ($m = check_email($email)) $errors[] = $m;
    if (!valid_phone($phone)) $errors[] = t('رقم الجوال لازم يكون 10 أرقام ويبدأ بـ 05.', 'Phone must be 10 digits starting with 05.');

    if (!$errors && $email !== $user['email']) {
        $q = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $q->execute([$email, $user['id']]);
        if ($q->fetch()) $errors[] = t('هذا البريد مستخدم في حساب ثاني.', 'This email is used by another account.');
    }

    $newHash = null;
    if ($new !== '' || $new2 !== '' || $cur !== '') {
        if (!password_verify($cur, $user['password_hash'])) $errors[] = t('كلمة المرور الحالية غير صحيحة.', 'Current password is incorrect.');
        if (strlen($new) < 8) $errors[] = t('كلمة المرور الجديدة لازم تكون 8 أحرف على الأقل.', 'New password must be at least 8 characters.');
        if ($new !== $new2)   $errors[] = t('كلمتا المرور الجديدتان غير متطابقتين.', 'New passwords do not match.');
        if (!$errors) $newHash = password_hash($new, PASSWORD_DEFAULT);
    }

    if (!$errors) {
        $changed = ($email !== $user['email']);
        $setVer  = $changed ? ', email_verified = 0, verify_code_hash = NULL, verify_expires_at = NULL, verify_sent_at = NULL' : '';
        if ($newHash) {
            $pdo->prepare('UPDATE users SET name=?, email=?, phone=?, password_hash=?' . $setVer . ' WHERE id=?')->execute([$name, $email, $phone, $newHash, $user['id']]);
            log_event('customer', $name, 'password_change');
        } else {
            $pdo->prepare('UPDATE users SET name=?, email=?, phone=?' . $setVer . ' WHERE id=?')->execute([$name, $email, $phone, $user['id']]);
        }
        log_event('customer', $name, 'profile_update', $email);
        if ($changed) {                      // بريد جديد: لازم يتفعّل برمز قبل الاستمرار
            unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_phone']);
            $_SESSION['pending_user_id'] = (int)$user['id'];
            $_SESSION['verify_flash']    = issue_verification($pdo, (int)$user['id'], $email, $name);
            header('Location: verify.php'); exit;
        }
        $_SESSION['user_name'] = $name;
        $_SESSION['user_phone'] = $phone;
        $ok = true;
    }
}

$rs = $pdo->prepare('SELECT r.res_date, r.res_time, r.status, r.service_name, r.specialist_name,
        pr.name_ar AS svc_ar, pr.name_en AS svc_en, sp.name_ar AS spc_ar, sp.name_en AS spc_en
    FROM reservations r
    LEFT JOIN products pr ON pr.id = r.service_id
    LEFT JOIN specialists sp ON sp.id = r.specialist_id
    WHERE r.user_id = ? ORDER BY r.res_date DESC, r.res_time DESC LIMIT 10');
$rs->execute([$user['id']]);
$reservations = $rs->fetchAll();
$statusNames = [
    'pending'   => t('بانتظار التأكيد', 'Pending'),
    'confirmed' => t('مؤكد', 'Confirmed'),
    'cancelled' => t('ملغي', 'Cancelled'),
];
auth_header(t('حسابي', 'My Account'));
?>
  <div class="auth-card">
    <div class="profile-avatar-lg">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
    </div>
    <h1><?= t('معلوماتي الشخصية', 'My Profile') ?></h1>
    <p class="auth-sub"><?= t('عدّلي بياناتكِ متى ما رغبتِ', 'Edit your details anytime') ?></p>
    <?php if ($ok): ?><div class="auth-success"><?= t('تم حفظ التعديلات ✓', 'Changes saved ✓') ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="auth-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="form-field"><label><?= t('الاسم الكامل', 'Full Name') ?></label>
        <input type="text" name="name" value="<?= e($name) ?>" required></div>
      <div class="form-field"><label><?= t('البريد الإلكتروني', 'Email') ?></label>
        <input type="email" name="email" value="<?= e($email) ?>" dir="ltr" required></div>
      <div class="form-field"><label><?= t('رقم الجوال (10 أرقام)', 'Phone (10 digits)') ?></label>
        <input type="tel" name="phone" value="<?= e($phone) ?>" maxlength="10" inputmode="numeric" dir="ltr" required></div>
      <details class="pw-box" <?= ($errors && ($_POST['current_password'] ?? '') !== '') ? 'open' : '' ?>>
        <summary><?= t('تغيير كلمة المرور (اختياري)', 'Change password (optional)') ?></summary>
        <div class="form-field"><label><?= t('كلمة المرور الحالية', 'Current password') ?></label><input type="password" name="current_password"></div>
        <div class="form-field"><label><?= t('كلمة المرور الجديدة', 'New password') ?></label><input type="password" name="new_password"></div>
        <div class="form-field"><label><?= t('تأكيد الجديدة', 'Confirm new password') ?></label><input type="password" name="new_password2"></div>
      </details>
      <button type="submit" class="btn btn-primary btn-full"><?= t('حفظ التعديلات', 'Save Changes') ?></button>
    </form>
    <h2 class="res-title"><?= t('مواعيدي', 'My Reservations') ?></h2>
    <?php if (!$reservations): ?>
      <p class="auth-sub"><?= t('ما عندكِ مواعيد للحين.', 'No reservations yet.') ?></p>
    <?php else: foreach ($reservations as $r): ?>
      <div class="res-item">
        <div><strong><?= e($r['res_date']) ?></strong> — <?= e(substr($r['res_time'], 0, 5)) ?></div>
        <div class="res-meta"><?= e(lang() === 'en' ? ($r['svc_en'] ?: $r['service_name']) : ($r['svc_ar'] ?: $r['service_name'])) ?>
          • <?= e($r['specialist_name'] !== '' ? (lang() === 'en' ? ($r['spc_en'] ?: $r['specialist_name']) : ($r['spc_ar'] ?: $r['specialist_name'])) : t('أي أخصائية', 'Any specialist')) ?></div>
        <span class="res-status st-<?= e($r['status']) ?>"><?= e($statusNames[$r['status']] ?? '') ?></span>
      </div>
    <?php endforeach; endif; ?>
    <a href="index.php" class="btn btn-secondary btn-full" style="margin-top:10px"><?= t('← الرجوع للموقع', '← Back to website') ?></a>
    <form method="post" action="logout.php" style="margin-top:10px">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <button type="submit" class="btn btn-full logout-btn"><?= t('تسجيل الخروج', 'Log Out') ?></button>
    </form>
  </div>
<?php auth_footer(); ?>
