<?php
require 'config.php';
require_once 'mailer.php';

if (is_admin() && empty($_SESSION['admin_2fa_pending'])) {
    header('Location: admin.php'); exit;
}

$adminId = (int)($_SESSION['pending_admin_id'] ?? 0);
if (!$adminId) { header('Location: login.php'); exit; }

$stmt = $pdo->prepare('SELECT id, username, email, display_name, role, two_factor_code_hash, two_factor_expires_at FROM admins WHERE id = ?');
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin) {
    unset($_SESSION['pending_admin_id'], $_SESSION['admin_2fa_pending']);
    header('Location: login.php'); exit;
}

$errors = []; $info = '';

// إرسال رمز 2FA جديد
function trigger_admin_2fa(PDO $pdo, array $admin): array {
    if (empty($admin['email'])) {
        return [false, t('لا يوجد بريد إلكتروني مسجّل لهذا الحساب. تواصل مع المدير العام.', 'No email registered for this account.')];
    }
    return send_admin_2fa($pdo, $admin);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = t('انتهت صلاحية الصفحة، حدّثيها وحاولي مرة ثانية.', 'Page expired, please refresh and try again.');
    } elseif (($_POST['action'] ?? '') === 'resend') {
        [$ok, $err] = trigger_admin_2fa($pdo, $admin);
        if ($ok) {
            $info = t('تم إعادة إرسال رمز التحقق إلى بريدك.', 'Verification code resent to your email.');
        } else {
            $errors[] = $err;
        }
    } else {
        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');

        if (strlen($code) !== 6) {
            $errors[] = t('اكتبي الرمز المكوّن من 6 أرقام.', 'Enter the 6-digit code.');
        } elseif (!$admin['two_factor_code_hash'] || strtotime((string)$admin['two_factor_expires_at']) < time()) {
            $errors[] = t('انتهت صلاحية الرمز. اضغطي "إعادة الإرسال".', 'The code has expired. Press "Resend".');
        } elseif (password_verify($code, $admin['two_factor_code_hash'])) {
            // نجاح التحقق 2FA
            $pdo->prepare('UPDATE admins SET two_factor_code_hash = NULL, two_factor_expires_at = NULL, last_login_at = ?, last_seen_at = ? WHERE id = ?')
                ->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $admin['id']]);

            session_regenerate_id(true);
            $_SESSION['is_admin']      = true;
            $_SESSION['admin_id']      = (int)$admin['id'];
            $_SESSION['admin_role']    = $admin['role'];
            $_SESSION['admin_name']    = $admin['username'];
            $_SESSION['last_activity'] = time();
            unset($_SESSION['pending_admin_id'], $_SESSION['admin_2fa_pending']);

            log_event($admin['role'] === 'super_admin' ? 'manager' : $admin['role'], $admin['username'], 'admin_login_2fa_success');
            header('Location: admin.php'); exit;
        } else {
            $errors[] = t('الرمز غير صحيح.', 'Incorrect code.');
            log_event($admin['role'] === 'super_admin' ? 'manager' : $admin['role'], $admin['username'], 'admin_login_2fa_failed');
        }
    }
}

auth_header(t('التحقق الثنائي للمدراء', 'Admin 2FA Verification'));
?>
  <div class="auth-card">
    <h1><?= t('التحقق الثنائي (2FA)', 'Two-Factor Authentication') ?></h1>
    <p class="auth-sub"><?= t('أدخل رمز التحقق المرسل إلى بريدك الإلكتروني المسجل', 'Enter the verification code sent to your registered email') ?></p>

    <?php if ($info): ?><div class="auth-success"><?= e($info) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="auth-error"><?= e($err) ?></div><?php endforeach; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="form-field">
        <input type="text" name="code" class="otp-input" inputmode="numeric" autocomplete="one-time-code"
               maxlength="6" placeholder="••••••" dir="ltr" autofocus required>
      </div>
      <button type="submit" class="btn btn-primary btn-full"><?= t('تأكيد الدخول', 'Verify & Login') ?></button>
    </form>

    <form method="post" style="margin-top:10px">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="resend">
      <button type="submit" class="btn btn-secondary btn-full"><?= t('إعادة إرسال الرمز', 'Resend code') ?></button>
    </form>
    <p class="auth-switch"><a href="login.php"><?= t('← إلغاء والدخول بحساب آخر', '← Cancel and use another account') ?></a></p>
  </div>
<?php auth_footer(); ?>
