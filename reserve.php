<?php
require 'config.php';
header('Content-Type: application/json; charset=utf-8');
function out($ok, $err = '') { echo json_encode(['ok' => $ok, 'error' => $err], JSON_UNESCAPED_UNICODE); exit; }

const SPA_FIRST = '13:00';   // أول موعد
const SPA_LAST  = '21:30';   // آخر موعد (الإغلاق 10:00 م)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, t('طلب غير صالح.', 'Invalid request.'));
if (!is_logged_in()) out(false, t('سجّلي دخولكِ أولاً.', 'Please log in first.'));
if (!csrf_check()) out(false, t('انتهت صلاحية الصفحة، حدّثيها وحاولي مرة ثانية.', 'Page expired, please refresh and try again.'));

$name   = trim($_POST['name'] ?? '');
$phone  = trim($_POST['phone'] ?? '');
$date   = $_POST['date'] ?? '';
$time   = $_POST['time'] ?? '';
$svcId  = (int)($_POST['service_id'] ?? 0);
$spcId  = (int)($_POST['specialist_id'] ?? 0);
$notes  = mb_substr(trim($_POST['notes'] ?? ''), 0, 500);

if (mb_strlen($name) < 2) out(false, t('اكتبي اسمكِ الكامل.', 'Please enter your full name.'));
if (!valid_phone($phone)) out(false, t('رقم الجوال لازم يكون 10 أرقام ويبدأ بـ 05.', 'Phone must be 10 digits starting with 05.'));
if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) out(false, t('الوقت غير صحيح.', 'Invalid time.'));
if ($time < SPA_FIRST || $time > SPA_LAST) out(false, t('مواعيدنا من 1:00 ظهراً حتى 9:30 مساءً.', 'Appointments are from 1:00 PM to 9:30 PM.'));
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) out(false, t('التاريخ غير صحيح.', 'Invalid date.'));
if (new DateTime("$date $time") < new DateTime()) out(false, t('لا يمكن الحجز في وقت مضى.', 'Cannot book a time in the past.'));

$s = $pdo->prepare('SELECT id, name_ar FROM products WHERE id = ? AND is_active = 1');
$s->execute([$svcId]);
$svc = $s->fetch();
if (!$svc) out(false, t('اختاري الخدمة المطلوبة.', 'Please choose a service.'));

$spcName = '';
if ($spcId > 0) {
    $s = $pdo->prepare('SELECT id, name_ar FROM specialists WHERE id = ? AND is_active = 1');
    $s->execute([$spcId]);
    $spc = $s->fetch();
    if (!$spc) out(false, t('الأخصائية غير متاحة.', 'Specialist unavailable.'));
    $spcName = $spc['name_ar'];
} else { $spcId = null; }

$pdo->prepare('INSERT INTO reservations (user_id, name, phone, res_date, res_time, service_id, service_name, specialist_id, specialist_name, notes) VALUES (?,?,?,?,?,?,?,?,?,?)')
    ->execute([$_SESSION['user_id'], $name, $phone, $date, $time, $svc['id'], $svc['name_ar'], $spcId, $spcName, $notes ?: null]);
log_event('customer', $name, 'reservation_created', $svc['name_ar'] . " • $date $time" . ($spcName ? " • $spcName" : ''));
out(true);
