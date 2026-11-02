<?php
require_once __DIR__ . '/mail-config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * يرسل بريد عبر SMTP (Brevo). يرجّع [true, ''] أو [false, 'سبب الفشل'].
 */
function send_mail(string $to, string $toName, string $subject, string $html, string $text): array {
    $base = __DIR__ . '/PHPMailer/src/';
    if (!is_file($base . 'PHPMailer.php')) {
        return [false, 'PHPMailer is not installed: put its src folder in PHPMailer/src/'];
    }
    require_once $base . 'Exception.php';
    require_once $base . 'PHPMailer.php';
    require_once $base . 'SMTP.php';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->Port       = MAIL_PORT;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // المنفذ 587
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 15;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text;
        $mail->send();
        return [true, ''];
    } catch (Exception $e) {
        error_log('[ROA mail] ' . $mail->ErrorInfo);
        return [false, $mail->ErrorInfo];
    }
}

/** رسالة رمز التحقق (عربي/إنجليزي حسب لغة الزائر) */
function send_verification_email(string $to, string $name, string $code, int $minutes): array {
    $en   = lang() === 'en';
    $dir  = $en ? 'ltr' : 'rtl';
    $n    = e($name);
    $subject = $en ? 'Verify your email — ROA Lounge' : 'تأكيد بريدك الإلكتروني — روى لاونج';
    $title   = $en ? "Hello $n," : "أهلاً $n،";
    $intro   = $en ? 'Use this code to verify your email and activate your account:' : 'استخدم الرمز التالي لتأكيد بريدك وتفعيل حسابك:';
    $note    = $en ? "This code expires in $minutes minutes. If you did not create an account, ignore this email."
                   : "ينتهي هذا الرمز خلال $minutes دقائق. إذا لم تنشئ حساباً فتجاهل هذه الرسالة.";
    $brand   = $en ? 'ROA Lounge' : 'روى لاونج';

    $html = <<<HTML
<div dir="$dir" style="font-family:Tahoma,Arial,sans-serif;background:#F5F3E9;padding:24px">
  <div style="max-width:460px;margin:auto;background:#fff;border-radius:16px;padding:32px;text-align:center;border-top:6px solid #7E3B33">
    <h2 style="margin:0 0 6px;color:#38402D">$brand</h2>
    <p style="color:#4F5448">$title</p>
    <p style="color:#4F5448">$intro</p>
    <div style="font-size:34px;font-weight:bold;letter-spacing:10px;color:#7E3B33;background:#F5F3E9;border-radius:12px;padding:16px;margin:18px 0" dir="ltr">$code</div>
    <p style="color:#888;font-size:13px">$note</p>
  </div>
</div>
HTML;
    $text = ($en ? "Your verification code: $code\nExpires in $minutes minutes." : "رمز التحقق: $code\nينتهي خلال $minutes دقائق.");
    return send_mail($to, $name, $subject, $html, $text);
}
