<?php
require_once __DIR__ . '/mailer.php';

const VERIFY_TTL          = 600;   // صلاحية الرمز: 10 دقائق
const VERIFY_COOLDOWN     = 60;    // أقل مدة بين إرسالين (ثانية)
const VERIFY_MAX_ATTEMPTS = 5;     // محاولات خاطئة قبل إبطال الرمز

/** a***@gmail.com */
function mask_email(string $email): string {
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) return $email;
    return mb_substr($parts[0], 0, 1) . str_repeat('*', max(2, mb_strlen($parts[0]) - 1)) . '@' . $parts[1];
}

/**
 * يولّد رمز OTP من 6 أرقام، يخزّن "هاش" الرمز فقط في قاعدة البيانات، ويرسله بالبريد.
 * يرجّع ['status' => 'sent'|'wait'|'failed', 'seconds' => int, 'error' => string]
 */
function issue_verification(PDO $pdo, int $userId, string $email, string $name): array {
    $st = $pdo->prepare('SELECT verify_sent_at FROM users WHERE id = ?');
    $st->execute([$userId]);
    $sentAt = $st->fetchColumn();
    if ($sentAt) {
        $left = VERIFY_COOLDOWN - (time() - strtotime($sentAt));
        if ($left > 0) return ['status' => 'wait', 'seconds' => $left, 'error' => ''];
    }

    $code = sprintf('%06d', random_int(0, 999999));
    $now  = date('Y-m-d H:i:s');
    $pdo->prepare('UPDATE users SET verify_code_hash = ?, verify_expires_at = ?, verify_attempts = 0, verify_sent_at = ? WHERE id = ?')
        ->execute([password_hash($code, PASSWORD_DEFAULT), date('Y-m-d H:i:s', time() + VERIFY_TTL), $now, $userId]);

    [$ok, $err] = send_verification_email($email, $name, $code, (int)(VERIFY_TTL / 60));
    if (!$ok) {   // فشل الإرسال: نسمح بإعادة المحاولة فوراً
        $pdo->prepare('UPDATE users SET verify_sent_at = NULL WHERE id = ?')->execute([$userId]);
        return ['status' => 'failed', 'seconds' => 0, 'error' => $err];
    }
    return ['status' => 'sent', 'seconds' => VERIFY_COOLDOWN, 'error' => ''];
}

/** إرسال رمز 2FA للإدارة */
function send_admin_2fa(PDO $pdo, array $admin): array {
    if (empty($admin['email'])) return [false, 'لا يوجد بريد إلكتروني مسجّل.'];
    $code = sprintf('%06d', random_int(0, 999999));
    $hash = password_hash($code, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE admins SET two_factor_code_hash = ?, two_factor_expires_at = ? WHERE id = ?')
        ->execute([$hash, date('Y-m-d H:i:s', time() + 600), $admin['id']]);
    return send_mail($admin['email'], $admin['display_name'] ?: $admin['username'], 'رمز التحقق (2FA)', "رمزك هو: $code", "رمزك هو: $code");
}
