<?php
require 'config.php';
require 'verification.php';

if (is_logged_in()) { header('Location: ' . after_login_url()); exit; }
$pid = (int)($_SESSION['pending_user_id'] ?? 0);
if (!$pid) { header('Location: login.php'); exit; }

$load = $pdo->prepare('SELECT id, name, email, phone, email_verified, verify_code_hash, verify_expires_at, verify_attempts, verify_sent_at FROM users WHERE id = ?');
$load->execute([$pid]);
$user = $load->fetch();
if (!$user) { unset($_SESSION['pending_user_id']); header('Location: login.php'); exit; }

$next = next_param();
$errors = []; $info = '';

/** يكمل الدخول بعد نجاح التحقق */
function finish_login(array $user) {
    session_regenerate_id(true);
    unset($_SESSION['pending_user_id'], $_SESSION['verify_flash']);
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_phone'] = $user['phone'];
    header('Location: ' . after_login_url()); exit;
}

// حساب مفعّل أصلاً (مثلاً فُتحت الصفحة من تبويب ثاني)
if ($user['email_verified']) finish_login($user);

// رسالة الإرسال القادمة من التسجيل/الدخول
function flash_message($flash) {
    global $errors, $info, $user;
    $to = mask_email($user['email']);
    if (($flash['status'] ?? '') === 'sent' || ($flash['status'] ?? '') === 'wait') {
        $info = t("أرسلنا رمز التحقق إلى $to", "We sent a verification code to $to");
    } elseif (($flash['status'] ?? '') === 'failed') {
        $errors[] = t('تعذر إرسال الرمز الآن. اضغطي "إعادة الإرسال" بعد قليل.', 'Could not send the code. Press "Resend" in a moment.');
        if (MAIL_DEBUG && !empty($flash['error'])) $errors[] = 'DEBUG: ' . $flash['error'];
    }
}
if (!empty($_SESSION['verify_flash'])) { flash_message($_SESSION['verify_flash']); unset($_SESSION['verify_flash']); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors[] = t('انتهت صلاحية الصفحة، حدّثيها وحاولي مرة ثانية.', 'Page expired, please refresh and try again.');
    } elseif (($_POST['action'] ?? '') === 'resend') {
        $res = issue_verification($pdo, $pid, $user['email'], $user['name']);
        if ($res['status'] === 'wait') {
            $errors[] = t('انتظري ' . $res['seconds'] . ' ثانية قبل طلب رمز جديد.', 'Please wait ' . $res['seconds'] . ' seconds before requesting a new code.');
        } else {
            flash_message($res);
        }
    } else {
        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
        $fresh = $pdo->prepare('SELECT verify_code_hash, verify_expires_at, verify_attempts FROM users WHERE id = ?');
        $fresh->execute([$pid]);
        $row = $fresh->fetch();

        if (strlen($code) !== 6) {
            $errors[] = t('اكتبي الرمز المكوّن من 6 أرقام.', 'Enter the 6-digit code.');
        } elseif (!$row['verify_code_hash'] || strtotime((string)$row['verify_expires_at']) < time()) {
            $errors[] = t('انتهت صلاحية الرمز. اضغطي "إعادة الإرسال".', 'The code has expired. Press "Resend".');
        } elseif ((int)$row['verify_attempts'] >= VERIFY_MAX_ATTEMPTS) {
            $errors[] = t('محاولات خاطئة كثيرة. اطلبي رمزاً جديداً.', 'Too many wrong attempts. Request a new code.');
        } elseif (password_verify($code, $row['verify_code_hash'])) {
            $pdo->prepare('UPDATE users SET email_verified = 1, verified_at = ?, verify_code_hash = NULL, verify_expires_at = NULL, verify_attempts = 0 WHERE id = ?')
                ->execute([date('Y-m-d H:i:s'), $pid]);
            log_event('customer', $user['name'], 'customer_verified', $user['email']);
            finish_login($user);
        } else {
            $pdo->prepare('UPDATE users SET verify_attempts = verify_attempts + 1 WHERE id = ?')->execute([$pid]);
            $left = VERIFY_MAX_ATTEMPTS - ((int)$row['verify_attempts'] + 1);
            $errors[] = $left > 0
                ? t("الرمز غير صحيح. المحاولات المتبقية: $left", "Incorrect code. Attempts left: $left")
                : t('الرمز غير صحيح. اطلبي رمزاً جديداً.', 'Incorrect code. Please request a new code.');
        }
    }
    // تحديث وقت آخر إرسال للعدّاد
    $load->execute([$pid]);
    $user = $load->fetch();
}

$wait = $user['verify_sent_at'] ? max(0, VERIFY_COOLDOWN - (time() - strtotime($user['verify_sent_at']))) : 0;

auth_header(t('تأكيد البريد', 'Verify Email'));
?>
  <div class="auth-card">
    <h1><?= t('تأكيد بريدك الإلكتروني', 'Verify Your Email') ?></h1>
    <p class="auth-sub"><?= t('اكتبي الرمز المرسل إلى', 'Enter the code sent to') ?> <b dir="ltr"><?= e(mask_email($user['email'])) ?></b></p>

    <?php if ($info): ?><div class="auth-success"><?= e($info) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="auth-error"><?= e($err) ?></div><?php endforeach; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <input type="hidden" name="action" value="verify">
      <div class="form-field">
        <input type="text" name="code" class="otp-input" inputmode="numeric" autocomplete="one-time-code"
               maxlength="6" placeholder="••••••" dir="ltr" autofocus required>
      </div>
      <button type="submit" class="btn btn-primary btn-full"><?= t('تأكيد', 'Verify') ?></button>
    </form>

    <form method="post" style="margin-top:10px">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <input type="hidden" name="action" value="resend">
      <button type="submit" id="resendBtn" class="btn btn-secondary btn-full"
              data-wait="<?= (int)$wait ?>" data-label="<?= e(t('إعادة إرسال الرمز', 'Resend code')) ?>"><?= t('إعادة إرسال الرمز', 'Resend code') ?></button>
    </form>

    <p class="auth-switch"><?= t('بريد غير صحيح؟', 'Wrong email?') ?> <a href="register.php<?= $next ? '?next=reserve' : '' ?>"><?= t('سجّلي من جديد', 'Register again') ?></a></p>
    <p class="auth-switch"><a href="index.php"><?= t('← الرجوع للموقع', '← Back to website') ?></a></p>
  </div>
  <script>
    (function () {
      var otp = document.querySelector('.otp-input');
      otp.addEventListener('input', function () { otp.value = otp.value.replace(/\D/g, '').slice(0, 6); });
      var b = document.getElementById('resendBtn'), s = parseInt(b.dataset.wait, 10) || 0;
      if (s > 0) {
        b.disabled = true; b.textContent = b.dataset.label + ' (' + s + ')';
        var t = setInterval(function () {
          s--;
          if (s <= 0) { clearInterval(t); b.disabled = false; b.textContent = b.dataset.label; }
          else b.textContent = b.dataset.label + ' (' + s + ')';
        }, 1000);
      }
    })();
  </script>
<?php auth_footer(); ?>
