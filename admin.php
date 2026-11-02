<?php
require 'config.php';
require_once 'mailer.php';
require_admin();

$role         = $_SESSION['admin_role'] ?? 'member';
$isSuperAdmin = ($role === 'super_admin');
$adminId      = $_SESSION['admin_id'] ?? 0;
$me           = $_SESSION['admin_name'] ?? '';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `staff_emails` (`id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(100), `email` VARCHAR(150), `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `system_settings` (`setting_key` VARCHAR(50) PRIMARY KEY, `setting_value` VARCHAR(255))");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `expenses` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(255) NOT NULL,
      `amount` DECIMAL(10,2) NOT NULL,
      `category` VARCHAR(100) DEFAULT 'عام',
      `created_by` VARCHAR(100) DEFAULT NULL,
      `expense_date` DATE NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $cols = $pdo->query("SHOW COLUMNS FROM reservations")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('price', $cols))           $pdo->exec("ALTER TABLE reservations ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00 AFTER notes");
    if (!in_array('discount_amount', $cols)) $pdo->exec("ALTER TABLE reservations ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00 AFTER price");
    if (!in_array('final_price', $cols))    $pdo->exec("ALTER TABLE reservations ADD COLUMN final_price DECIMAL(10,2) DEFAULT 0.00 AFTER discount_amount");
    if (!in_array('is_walkin', $cols))       $pdo->exec("ALTER TABLE reservations ADD COLUMN is_walkin TINYINT(1) DEFAULT 0 AFTER status");
    
    $admCols = $pdo->query("SHOW COLUMNS FROM admins")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('permissions', $admCols))  $pdo->exec("ALTER TABLE admins ADD COLUMN permissions TEXT NULL AFTER role");
} catch (PDOException $e) {}

// دالة حساب رتبة الولاء للعميل حسَب عدد زياراته
function get_client_loyalty_badge($visitCount) {
    if ($visitCount >= 10) {
        return ['badge' => '🥇 عميل ذهبي (VIP)', 'class' => 'badge-gold', 'count' => $visitCount];
    } elseif ($visitCount >= 4) {
        return ['badge' => '🥈 عميل فضي', 'class' => 'badge-silver', 'count' => $visitCount];
    } else {
        return ['badge' => '🥉 عميل برونزي', 'class' => 'badge-bronze', 'count' => $visitCount];
    }
}

// جلب صلاحيات الحساب الحالي
$myPerms = [];
if (!$isSuperAdmin && $adminId) {
    $st = $pdo->prepare("SELECT permissions FROM admins WHERE id = ?");
    $st->execute([$adminId]);
    $rawPerms = $st->fetchColumn();
    $myPerms = $rawPerms ? json_decode($rawPerms, true) : ['walkin', 'reservations'];
}

function has_perm($permKey, $isSuperAdmin, $myPerms) {
    if ($isSuperAdmin) return true;
    return in_array($permKey, $myPerms, true);
}

$statusNames = ['pending' => 'بانتظار التأكيد', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي'];
$cats        = ['moroccan' => 'الحمام المغربي', 'nails' => 'المناكير والبدكير', 'colors' => 'الطلاء والأرت'];
$filters     = ['upcoming' => 'القادمة', 'today' => 'اليوم', 'pending' => 'بانتظار التأكيد', 'past' => 'السابقة', 'all' => 'الكل'];

// بناء التبويبات المتاحة حسب صلاحيات المستخدم الحالي
$tabs = [];
if ($isSuperAdmin) $tabs[] = 'dashboard';
if (has_perm('expenses', $isSuperAdmin, $myPerms))     $tabs[] = 'expenses';
if (has_perm('walkin', $isSuperAdmin, $myPerms))       $tabs[] = 'walkin';
if (has_perm('reservations', $isSuperAdmin, $myPerms)) {
    $tabs[] = 'reservations';
    $tabs[] = 'clients_search'; // تبويب البحث عن العملاء وسجلهم
}
if (has_perm('products', $isSuperAdmin, $myPerms))     $tabs[] = 'products';
if (has_perm('specialists', $isSuperAdmin, $myPerms))  $tabs[] = 'specialists';
if (has_perm('staff', $isSuperAdmin, $myPerms))        $tabs[] = 'staff';

if ($isSuperAdmin) {
    $tabs = array_merge($tabs, ['members', 'log']);
    $tabs = array_unique($tabs);
}

$defaultTab = reset($tabs) ?: 'walkin';
$tab = $_GET['tab'] ?? $defaultTab; 
if (!in_array($tab, $tabs, true)) $tab = $defaultTab;
$f   = $_GET['f'] ?? 'upcoming'; if (!isset($filters[$f])) $f = 'upcoming';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = '';
    if (!csrf_check()) {
        $msg = 'انتهت صلاحية الجلسة.';
    } else {
        $action = $_POST['action'] ?? '';
        $id     = (int)($_POST['id'] ?? 0);
        $now    = date('Y-m-d H:i:s');

        if ($action === 'update_user_permissions' && $isSuperAdmin) {
            $targetId = (int)($_POST['user_id'] ?? 0);
            $selectedPerms = $_POST['user_perms'] ?? [];
            $jsonPerms = json_encode($selectedPerms);

            $stmt = $pdo->prepare("UPDATE admins SET permissions = ? WHERE id = ? AND role != 'super_admin'");
            $stmt->execute([$jsonPerms, $targetId]);
            log_event('manager', $me, 'update_user_permissions', "ID: $targetId, Perms: $jsonPerms");
            $msg = '⚙️ تم تحديث صلاحيات الحساب بنجاح!';
        } elseif ($action === 'res_status' && has_perm('reservations', $isSuperAdmin, $myPerms)) {
            $new = $_POST['status'] ?? '';
            if ($new === 'confirmed') {
                $pdo->prepare("UPDATE reservations SET status='confirmed', confirmed_by=?, confirmed_at=? WHERE id=?")->execute([$me, $now, $id]); 
                $msg = 'تم تأكيد الحجز بنجاح.';
            } elseif ($new === 'cancelled' && $isSuperAdmin) {
                $pdo->prepare("UPDATE reservations SET status='cancelled' WHERE id=?")->execute([$id]); 
                $msg = 'تم إلغاء الحجز.';
            }
        } elseif ($action === 'res_delete' && $isSuperAdmin) {
            $pdo->prepare("DELETE FROM reservations WHERE id=?")->execute([$id]);
            $msg = 'تم حذف الحجز نهائياً.';
        } elseif ($action === 'expense_add' && has_perm('expenses', $isSuperAdmin, $myPerms)) {
            $title = trim($_POST['title'] ?? '');
            $amt   = (float)($_POST['amount'] ?? 0);
            $cat   = trim($_POST['category'] ?? 'مشتريات/مصاريف');
            $eDate = $_POST['expense_date'] ?? date('Y-m-d');
            if ($amt > 50000) {
                $msg = 'لا يمكن إضافة مصروف يتجاوز 50,000 ريال.';
            } elseif ($title && $amt > 0) {
                $pdo->prepare("INSERT INTO expenses (title, amount, category, created_by, expense_date) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$title, $amt, $cat, $me, $eDate]);
                $msg = '💸 تم تسجيل المصروف/الشراء بنجاح!';
            }
        } elseif ($action === 'expense_delete' && $isSuperAdmin) {
            $pdo->prepare("DELETE FROM expenses WHERE id=?")->execute([$id]);
            $msg = 'تم حذف المصروف.';
        } elseif ($action === 'flush_logs' && $isSuperAdmin) {
            $pdo->exec("TRUNCATE TABLE activity_log");
            $msg = '💥 تم مسح سجل النشاطات بالكامل!';
        } elseif ($action === 'toggle_lockdown' && $isSuperAdmin) {
            $curr = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key='lockdown'")->fetchColumn();
            $newVal = ($curr === '1') ? '0' : '1';
            $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('lockdown', ?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$newVal, $newVal]);
            $msg = ($newVal === '1') ? '🔒 تم قفل النظام!' : '🔓 تم فتح استقبال الحجوزات.';
        } elseif ($action === 'walkin_create' && has_perm('walkin', $isSuperAdmin, $myPerms)) {
            $cName   = trim($_POST['client_name'] ?? ''); 
            $cPhone  = trim($_POST['client_phone'] ?? ''); 
            $prodIds = $_POST['product_ids'] ?? [];

            if (!empty($prodIds) && $cName && $cPhone) {
                $serviceNames   = [];
                $totalOrigPrice = 0;

                foreach ($prodIds as $pId) {
                    $stP = $pdo->prepare("SELECT name_ar, price FROM products WHERE id = ?"); 
                    $stP->execute([(int)$pId]); 
                    if ($prod = $stP->fetch()) {
                        $serviceNames[]  = $prod['name_ar'];
                        $totalOrigPrice += (float)preg_replace('/[^0-9.]/', '', $prod['price']);
                    }
                }

                $allServices   = implode(' + ', $serviceNames);
                $disc          = max(0, (float)($_POST['discount'] ?? 0));
                $finalPrice    = max(0, $totalOrigPrice - $disc);
                $defaultUserId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();

                $pdo->prepare("INSERT INTO reservations (user_id, name, phone, service_name, specialist_name, res_date, res_time, notes, status, confirmed_by, confirmed_at, price, discount_amount, final_price, is_walkin) VALUES (?, ?, ?, ?, 'أي أخصائية', ?, ?, ?, 'confirmed', ?, ?, ?, ?, ?, 1)")
                    ->execute([$defaultUserId ?: null, $cName, $cPhone, $allServices, $_POST['res_date']??date('Y-m-d'), $_POST['res_time']??date('H:i'), $_POST['notes']??'', $me, $now, $totalOrigPrice, $disc, $finalPrice]);
                
                $vStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE phone = ? AND status='confirmed'");
                $vStmt->execute([$cPhone]);
                $vCount = (int)$vStmt->fetchColumn();
                $loyalty = get_client_loyalty_badge($vCount);

                $msg = '✨ تم تسجيل الحجز بنجاح! (' . $loyalty['badge'] . ' - الزيارة رقم ' . $vCount . ')';
            } else {
                $msg = 'يرجى اختيار خدمة واحدة على الأقل وتعبئة بيانات العميل.';
            }
        } elseif ($action === 'product_add' && has_perm('products', $isSuperAdmin, $myPerms)) {
            $pdo->prepare('INSERT INTO products (category, name_ar, name_en, price) VALUES (?, ?, ?, ?)')->execute([$_POST['category']??'', trim($_POST['name_ar']??''), trim($_POST['name_en']??''), trim($_POST['price']??'0')]); 
            $msg = 'تمت إضافة الخدمة بنجاح.';
        } elseif ($action === 'product_delete' && has_perm('products', $isSuperAdmin, $myPerms)) {
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]); 
            $msg = 'تم حذف الخدمة.';
        } elseif ($action === 'spec_add' && has_perm('specialists', $isSuperAdmin, $myPerms)) {
            $pdo->prepare('INSERT INTO specialists (name_ar, is_active) VALUES (?, 1)')->execute([trim($_POST['name_ar']??'')]); 
            $msg = 'تمت إضافة الأخصائية بنجاح.';
        } elseif ($action === 'spec_delete' && has_perm('specialists', $isSuperAdmin, $myPerms)) {
            $pdo->prepare('DELETE FROM specialists WHERE id = ?')->execute([$id]); 
            $msg = 'تم حذف الأخصائية.';
        } elseif ($action === 'staff_add' && has_perm('staff', $isSuperAdmin, $myPerms)) {
            $pdo->prepare('INSERT INTO staff_emails (name, email) VALUES (?, ?)')->execute([trim($_POST['staff_name']??''), trim($_POST['staff_email']??'')]); 
            $msg = 'تم حفظ بيانات الموظف والإيميل.';
        } elseif ($action === 'staff_delete' && has_perm('staff', $isSuperAdmin, $myPerms)) {
            $pdo->prepare('DELETE FROM staff_emails WHERE id = ?')->execute([$id]); 
            $msg = 'تم حذف الموظف.';
        } elseif ($action === 'staff_send_email' && has_perm('staff', $isSuperAdmin, $myPerms)) {
            $st = $pdo->prepare('SELECT name, email FROM staff_emails WHERE id = ?'); $st->execute([(int)$_POST['staff_id']]);
            if ($stM = $st->fetch()) { 
                [$sent, $err] = send_mail($stM['email'], $stM['name'], 'إشعار من إدارة أوفلاين سبا', nl2br(htmlspecialchars($_POST['custom_message']??'')), $_POST['custom_message']??''); 
                $msg = $sent ? '📧 تم إرسال البريد الإلكتروني للموظف بنجاح!' : '❌ خطأ أثناء الإرسال: '.$err; 
            }
        } elseif ($action === 'member_add' && $isSuperAdmin) {
            $u = strtolower(trim($_POST['username'] ?? '')); $pw = $_POST['password'] ?? ''; $rRole = $_POST['role'] ?? 'manager';
            $dName = trim($_POST['display_name'] ?? ''); $email = trim($_POST['email'] ?? '');
            $defaultUserPerms = json_encode(['walkin', 'reservations']);

            if ($u && strlen($pw) >= 6 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $pdo->prepare("INSERT INTO admins (username, email, password_hash, role, display_name, permissions, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)")
                    ->execute([$u, $email, password_hash($pw, PASSWORD_DEFAULT), $rRole, $dName ?: $u, $defaultUserPerms]);
                $msg = 'تمت إضافة الحساب الجديد بنجاح!';
            } else {
                $msg = 'البيانات غير مكتملة. تأكد من كلمة المرور (6 خانات) وصحة البريد الإلكتروني.';
            }
        } elseif ($action === 'member_delete' && $isSuperAdmin) {
            $pdo->prepare("DELETE FROM admins WHERE id = ? AND role != 'super_admin'")->execute([$id]); 
            $msg = 'تم حذف الحساب بنجاح.';
        }
    }
    $_SESSION['flash'] = $msg; header('Location: admin.php?tab=' . $tab . '&f=' . $f); exit;
}

$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$csrf  = csrf_token(); $today = date('Y-m-d');

function hidden($csrf, $action, $id, $tab, $f) {
    echo '<input type="hidden" name="csrf" value="'.e($csrf).'"><input type="hidden" name="action" value="'.e($action).'"><input type="hidden" name="id" value="'.(int)$id.'"><input type="hidden" name="tab" value="'.e($tab).'"><input type="hidden" name="f" value="'.e($f).'">';
}

$products = $pdo->query('SELECT * FROM products ORDER BY category, id')->fetchAll();
$specs    = $pdo->query('SELECT * FROM specialists ORDER BY id')->fetchAll();
$reservations = [];
if ($tab === 'reservations') {
    $where = ['upcoming' => "res_date >= '$today' AND status <> 'cancelled'", 'today' => "res_date = '$today'", 'pending' => "status = 'pending'", 'past' => "res_date < '$today'", 'all' => '1=1'][$f] ?? '1=1';
    $reservations = $pdo->query("SELECT * FROM reservations WHERE $where ORDER BY res_date DESC, res_time DESC LIMIT 100")->fetchAll();
}
$byCat = []; foreach ($products as $p) $byCat[$p['category']][] = $p;

$totalRevenue  = (float)$pdo->query("SELECT SUM(final_price) FROM reservations WHERE status='confirmed'")->fetchColumn();
$todayRevenue  = (float)$pdo->query("SELECT SUM(final_price) FROM reservations WHERE status='confirmed' AND res_date='$today'")->fetchColumn();
$totalExpenses = (float)$pdo->query("SELECT SUM(amount) FROM expenses")->fetchColumn();
$todayExpenses = (float)$pdo->query("SELECT SUM(amount) FROM expenses WHERE expense_date='$today'")->fetchColumn();
$netProfit     = $totalRevenue - $totalExpenses;
$todayNet      = $todayRevenue - $todayExpenses;

$isLockdown    = ($pdo->query("SELECT setting_value FROM system_settings WHERE setting_key='lockdown'")->fetchColumn() === '1');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"><title>لوحة أوفلاين سبا الذكية</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="admin.css">
  <style>
    body.dark-mode { background: #121212 !important; color: #e0e0e0 !important; }
    body.dark-mode .adm-card, body.dark-mode .dash-card, body.dark-mode .calc-summary-box, body.dark-mode .prod-item-label { background: #1e1e1e !important; color: #fff !important; border-color: #333 !important; }
    body.dark-mode input, body.dark-mode select, body.dark-mode textarea { background: #2a2a2a !important; color: #fff !important; border-color: #444 !important; }
    
    .role-badge { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block; }
    .role-super { background: #ffd700; color: #000; box-shadow: 0 0 6px rgba(255,215,0,0.5); }
    .role-normal { background: #0288d1; color: #fff; }

    /* شارات رتب ولاء العملاء */
    .badge-loyalty { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; margin-right: 6px; display: inline-block; }
    .badge-gold { background: #fff8e1; color: #f57f17; border: 1px solid #ffe082; }
    .badge-silver { background: #eceff1; color: #455a64; border: 1px solid #b0bec5; }
    .badge-bronze { background: #efebe9; color: #5d4037; border: 1px solid #bcaaa4; }

    .idle-timer-bar { background: #fff3e0; border: 1px solid #ffe0b2; color: #e65100; font-size: 12px; padding: 6px 12px; border-radius: 6px; font-weight: bold; margin-left: 10px; }

    .super-dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .dash-card { background: #fff; padding: 16px; border-radius: 10px; border-right: 5px solid #2e7d32; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .dash-card.gold { border-right-color: #ffd700; }
    .dash-card.info { border-right-color: #0288d1; }
    .dash-card.red { border-right-color: #d32f2f; }
    .dash-card.net { border-right-color: #388e3c; background: #f1f8e9; }
    .dash-card h3 { font-size: 11px; color: #666; margin-bottom: 4px; }
    .dash-card .amount { font-size: 20px; font-weight: 900; }
    
    .prod-box-container { grid-column: 1 / -1; margin-bottom: 10px; }
    .product-grid-select { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 8px; max-height: 220px; overflow-y: auto; padding: 10px; background: #fafafa; border: 1px solid #e0e0e0; border-radius: 8px; }
    .prod-item-label { display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-size: 12px; }
    .search-input-box { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; margin-bottom: 10px; }
    .calc-summary-box { background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 12px 16px; margin-top: 15px; }
    .calc-row-line { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin-bottom: 5px; }
    .calc-row-line.total-line { font-size: 18px; color: #2e7d32; border-top: 1px solid #ccc; padding-top: 8px; margin-top: 6px; }
    
    .perm-grid-box { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 8px; background: #f9f9f9; padding: 10px; border-radius: 6px; border: 1px solid #eee; margin-bottom: 10px; }
    .perm-item-check { font-size: 11px; display: flex; align-items: center; gap: 4px; cursor: pointer; }
  </style>
</head>
<body class="admin-body">
<header class="admin-top">
  <div class="admin-top-in">
    <div>
      <strong>أوفلاين سبا</strong> 
      <span class="role-badge <?= $isSuperAdmin ? 'role-super' : 'role-normal' ?>">
        <?= $isSuperAdmin ? '👑 المدير العام' : '🛡️ مدير عادي' ?>
      </span>
      <small>(<?= e($me) ?>)</small>
    </div>
    <div style="display:flex; align-items:center;">
      <span class="idle-timer-bar">⏳ خمول: <span id="timer_disp">30:00</span></span>
      <button onclick="toggleDarkMode()" class="adm-btn sm ghost" style="margin-left:8px;">🌙</button>
      <a href="logout.php" class="adm-btn danger">تسجيل خروج</a>
    </div>
  </div>
</header>
<main class="admin-main">
  <nav class="admin-tabs">
    <?php if ($isSuperAdmin): ?>
      <a href="admin.php?tab=dashboard" class="<?= $tab==='dashboard'?'active':'' ?>">📊 الإحصائيات والأرباح</a>
    <?php endif; ?>
    <?php if (has_perm('expenses', $isSuperAdmin, $myPerms)): ?>
      <a href="admin.php?tab=expenses" class="<?= $tab==='expenses'?'active':'' ?>">💸 المشتريات والمصاريف</a>
    <?php endif; ?>
    <?php if (has_perm('walkin', $isSuperAdmin, $myPerms)): ?>
      <a href="admin.php?tab=walkin" class="<?= $tab==='walkin'?'active':'' ?>">🛍️ الكاونتر والحاسبة</a>
    <?php endif; ?>
    <?php if (has_perm('reservations', $isSuperAdmin, $myPerms)): ?>
      <a href="admin.php?tab=reservations" class="<?= $tab==='reservations'?'active':'' ?>">📋 الحجوزات</a>
      <a href="admin.php?tab=clients_search" class="<?= $tab==='clients_search'?'active':'' ?>">🔍 بحث العملاء والزيارات</a>
    <?php endif; ?>
    <?php if (has_perm('products', $isSuperAdmin, $myPerms)): ?>
      <a href="admin.php?tab=products" class="<?= $tab==='products'?'active':'' ?>">💅 الخدمات والأسعار</a>
    <?php endif; ?>
    <?php if (has_perm('specialists', $isSuperAdmin, $myPerms)): ?>
      <a href="admin.php?tab=specialists" class="<?= $tab==='specialists'?'active':'' ?>">👩‍🎨 الأخصائيات</a>
    <?php endif; ?>
    <?php if (has_perm('staff', $isSuperAdmin, $myPerms)): ?>
      <a href="admin.php?tab=staff" class="<?= $tab==='staff'?'active':'' ?>">📧 إيميلات الموظفين</a>
    <?php endif; ?>
    <?php if ($isSuperAdmin): ?>
      <a href="admin.php?tab=members" class="<?= $tab==='members'?'active':'' ?>">👑 المدراء والموظفين</a>
      <a href="admin.php?tab=log" class="<?= $tab==='log'?'active':'' ?>">🔍 سجل النشاطات</a>
    <?php endif; ?>
  </nav>

  <?php if ($flash): ?><div class="adm-flash"><?= e($flash) ?></div><?php endif; ?>

  <?php if ($tab === 'dashboard' && $isSuperAdmin): ?>
    <section class="super-dashboard-cards">
      <div class="dash-card gold"><h3>مبيعات اليوم الصافية</h3><div class="amount"><?= number_format($todayRevenue, 2) ?> ر.س</div></div>
      <div class="dash-card red"><h3>مصاريف ومشتريات اليوم</h3><div class="amount"><?= number_format($todayExpenses, 2) ?> ر.س</div></div>
      <div class="dash-card net"><h3>الربح الصافي لليوم</h3><div class="amount"><?= number_format($todayNet, 2) ?> ر.س</div></div>
      <div class="dash-card info"><h3>إجمالي المبيعات الكلي</h3><div class="amount"><?= number_format($totalRevenue, 2) ?> ر.س</div></div>
      <div class="dash-card red"><h3>إجمالي المصاريف والمشتريات</h3><div class="amount"><?= number_format($totalExpenses, 2) ?> ر.س</div></div>
      <div class="dash-card net"><h3>صافي الأرباح الكلي 💰</h3><div class="amount"><?= number_format($netProfit, 2) ?> ر.س</div></div>
    </section>

    <section class="adm-card">
      <h2>⚡ أزرار التحكم والطوارئ والتقارير</h2>
      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
        <button onclick="window.print()" class="adm-btn ok">📊 طباعة التقرير المالي الشامل</button>
        <form method="post">
          <?php hidden($csrf, 'toggle_lockdown', 0, $tab, $f); ?>
          <button class="adm-btn <?= $isLockdown ? 'ok' : 'danger' ?>" type="submit">
            <?= $isLockdown ? '🔓 إيقاف قفل النظام' : '🔒 قفل النظام والطوارئ' ?>
          </button>
        </form>
        <form method="post" onsubmit="return confirm('تأكيد مسح كل سجلات النشاط؟');">
          <?php hidden($csrf, 'flush_logs', 0, $tab, $f); ?>
          <button class="adm-btn danger" type="submit">💥 مسح سجل النشاطات</button>
        </form>
      </div>
    </section>

  <?php elseif ($tab === 'clients_search' && has_perm('reservations', $isSuperAdmin, $myPerms)): ?>
    <section class="adm-card">
      <h2>🔍 البحث المتقدم عن العملاء وسجل زياراتهم وطلباتهم</h2>
      <form method="get" class="adm-form" style="display:flex; gap:10px; margin-top:10px;">
        <input type="hidden" name="tab" value="clients_search">
        <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="ابحث باسم العميل أو رقم الجوال..." style="flex:1; padding:10px; border-radius:6px; border:1px solid #ccc;">
        <button class="adm-btn ok" type="submit">بحث</button>
      </form>
    </section>

    <?php 
    $searchQuery = trim($_GET['q'] ?? '');
    if ($searchQuery !== ''):
        $stmtClient = $pdo->prepare("SELECT DISTINCT phone, name FROM reservations WHERE name LIKE ? OR phone LIKE ?");
        $stmtClient->execute(["%$searchQuery%", "%$searchQuery%"]);
        $clientsFound = $stmtClient->fetchAll();
    ?>
        <section class="adm-card" style="margin-top:15px;">
          <h2>نتائج البحث عن العملاء</h2>
          <?php if (empty($clientsFound)): ?>
            <p style="color:#666; padding:10px;">لم يتم العثور على أي عميل بهذا الاسم أو الرقم.</p>
          <?php else: ?>
            <?php foreach ($clientsFound as $c): 
                // جلب كافة حجوزات هذا العميل
                $historyStmt = $pdo->prepare("SELECT * FROM reservations WHERE phone = ? ORDER BY res_date DESC, res_time DESC");
                $historyStmt->execute([$c['phone']]);
                $clientVisits = $historyStmt->fetchAll();
                
                $vCount = count($clientVisits);
                $loyalty = get_client_loyalty_badge($vCount);

                $totalSpent = 0;
                foreach ($clientVisits as $vis) {
                    if ($vis['status'] === 'confirmed') $totalSpent += (float)$vis['final_price'];
                }
            ?>
              <div style="background:#fcfcfc; border:1px solid #ddd; border-radius:8px; padding:15px; margin-bottom:15px;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:8px; margin-bottom:10px;">
                  <div>
                    <h3 style="margin:0; font-size:18px; color:#2c3e50;"><?= e($c['name']) ?></h3>
                    <span dir="ltr" style="font-size:13px; color:#666;"><?= e($c['phone']) ?></span>
                  </div>
                  <div>
                    <span class="badge-loyalty <?= $loyalty['class'] ?>"><?= $loyalty['badge'] ?></span>
                    <span style="font-weight:bold; font-size:14px; margin-right:10px;">الزيارات: <?= $vCount ?></span>
                    <span style="font-weight:bold; font-size:14px; color:#2e7d32; margin-right:10px;">إجمالي المدفوعات: <?= number_format($totalSpent, 2) ?> ر.س</span>
                  </div>
                </div>

                <h4 style="font-size:13px; color:#444; margin-bottom:5px;">📋 سجل الزيارات والخدمات المطلوبة:</h4>
                <div class="adm-table-wrap">
                  <table class="adm-table" style="font-size:12px;">
                    <thead>
                      <tr>
                        <th>التاريخ والوقت</th>
                        <th>الخدمات والطلبات</th>
                        <th>الأخصائية</th>
                        <th>السعر النهائي</th>
                        <th>الحالة</th>
                        <th>ملاحظات</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($clientVisits as $vis): ?>
                        <tr>
                          <td dir="ltr"><?= e($vis['res_date']) ?> <?= e(substr($vis['res_time'],0,5)) ?></td>
                          <td><b><?= e($vis['service_name']) ?></b></td>
                          <td><?= e($vis['specialist_name'] ?: 'أي أخصائية') ?></td>
                          <td style="font-weight:bold; color:#2e7d32;"><?= number_format($vis['final_price'], 2) ?> ر.س</td>
                          <td><span class="res-status"><?= e($statusNames[$vis['status']] ?? $vis['status']) ?></span></td>
                          <td><?= e($vis['notes'] ?: '-') ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </section>
    <?php endif; ?>

  <?php elseif ($tab === 'members' && $isSuperAdmin): ?>
    <section class="adm-card">
      <h2>👑 إضافة مدير أو موظف جديد للصلاحيات</h2>
      <form method="post" class="adm-form">
        <?php hidden($csrf, 'member_add', 0, $tab, $f); ?>
        <div class="adm-grid">
          <label>اسم المستخدم<input type="text" name="username" required dir="ltr" placeholder="مثال: nawaf2"></label>
          <label>الاسم الظاهر<input type="text" name="display_name" placeholder="مثال: نواف العتيبي"></label>
          <label>البريد الإلكتروني للـ 2FA<input type="email" name="email" required dir="ltr" placeholder="admin@example.com"></label>
          <label>كلمة المرور<input type="password" name="password" required dir="ltr" placeholder="******"></label>
          <label>الصلاحية والدور
            <select name="role">
              <option value="manager">مدير عادي</option>
              <option value="member">موظف كاونتر</option>
              <option value="super_admin">المدير العام</option>
            </select>
          </label>
        </div>
        <button class="adm-btn ok" type="submit" style="margin-top:10px;">➕ إضافة الحساب</button>
      </form>
    </section>

    <section class="adm-card" style="margin-top:15px;">
      <h2>👥 قائمة المدراء والموظفين وتخصيص الصلاحيات لكل شخص</h2>
      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr>
              <th>المستخدم / الاسم</th>
              <th>الرتبة</th>
              <th>البريد الإلكتروني</th>
              <th>تخصيص الصلاحيات لهذا الشخص</th>
              <th>حفظ الصلاحيات</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $allAdmins = $pdo->query("SELECT * FROM admins ORDER BY id DESC")->fetchAll();
            $allPermList = [
              'walkin'       => '🛍️ الكاونتر والحاسبة',
              'reservations' => '📋 الحجوزات والبحث',
              'products'     => '💅 الخدمات والأسعار',
              'specialists'  => '👩‍🎨 الأخصائيات',
              'staff'        => '📧 إيميلات الموظفين',
              'expenses'     => '💸 المصاريف والمشتريات'
            ];

            foreach ($allAdmins as $adm): 
              $uPerms = $adm['permissions'] ? json_decode($adm['permissions'], true) : ['walkin', 'reservations'];
            ?>
              <tr>
                <td>
                  <strong dir="ltr"><?= e($adm['username']) ?></strong><br>
                  <small style="color:#666;"><?= e($adm['display_name'] ?: 'بدون اسم') ?></small>
                </td>
                <td>
                  <span class="role-badge <?= $adm['role'] === 'super_admin' ? 'role-super' : 'role-normal' ?>">
                    <?= $adm['role'] === 'super_admin' ? '👑 المدير العام' : ($adm['role'] === 'manager' ? '🛡️ مدير عادي' : '👤 موظف كاونتر') ?>
                  </span>
                </td>
                <td dir="ltr"><?= e($adm['email'] ?? 'غير محدد') ?></td>
                <td>
                  <?php if ($adm['role'] !== 'super_admin'): ?>
                    <form method="post" id="perm_form_<?= $adm['id'] ?>">
                      <?php hidden($csrf, 'update_user_permissions', $adm['id'], $tab, $f); ?>
                      <input type="hidden" name="user_id" value="<?= $adm['id'] ?>">
                      <div class="perm-grid-box">
                        <?php foreach ($allPermList as $pKey => $pLabel): ?>
                          <label class="perm-item-check">
                            <input type="checkbox" name="user_perms[]" value="<?= $pKey ?>" <?= in_array($pKey, $uPerms, true) ? 'checked' : '' ?>>
                            <span><?= $pLabel ?></span>
                          </label>
                        <?php endforeach; ?>
                      </div>
                    </form>
                  <?php else: ?>
                    <span style="color:#2e7d32; font-weight:bold;">👑 يمتلك صلاحيات شمولية كاملة</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($adm['role'] !== 'super_admin'): ?>
                    <button form="perm_form_<?= $adm['id'] ?>" class="adm-btn sm ok" type="submit">💾 حفظ الصلاحيات</button>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($adm['role'] !== 'super_admin'): ?>
                    <form method="post" onsubmit="return confirm('تأكيد حذف حساب <?= e($adm['username']) ?>؟');">
                      <?php hidden($csrf, 'member_delete', $adm['id'], $tab, $f); ?>
                      <button class="adm-btn sm danger" type="submit">حذف الحساب</button>
                    </form>
                  <?php else: ?>
                    <small style="color:#888;">(محمي من الحذف)</small>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

  <?php elseif ($tab === 'expenses' && has_perm('expenses', $isSuperAdmin, $myPerms)): ?>
    <section class="adm-card">
      <h2>💸 تسجيل شراء / مصروف جديد</h2>
      <form method="post" class="adm-form">
        <?php hidden($csrf, 'expense_add', 0, $tab, $f); ?>
        <div class="adm-grid">
          <label>بيان المشتريات / المصروف<input type="text" name="title" required placeholder="مثال: شراء زيوت معطرة ومناشف"></label>
          <label>المبلغ (ر.س)<input type="number" step="0.01" name="amount" required placeholder="0.00"></label>
          <label>الفئة
            <select name="category">
              <option value="مشتريات خامات">مشتريات خامات ومستلزمات</option>
              <option value="فواتير وصيانة">فواتير وصيانة</option>
              <option value="رواتب ونثريات">رواتب ونثريات</option>
            </select>
          </label>
          <label>التاريخ<input type="date" name="expense_date" value="<?= date('Y-m-d') ?>"></label>
        </div>
        <button class="adm-btn ok" type="submit" style="margin-top:10px;">➕ حفظ المصروف</button>
      </form>
    </section>

    <section class="adm-card" style="margin-top:15px;">
      <h2>قائمة المشتريات والمصاريف المسجلة</h2>
      <div class="adm-table-wrap"><table class="adm-table">
        <thead><tr><th>التاريخ</th><th>البيان</th><th>الفئة</th><th>المبلغ</th><th>بواسطة</th><th>إجراء</th></tr></thead>
        <tbody>
          <?php foreach ($pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC, id DESC LIMIT 50")->fetchAll() as $ex): ?>
            <tr>
              <td><?= e($ex['expense_date']) ?></td>
              <td><b><?= e($ex['title']) ?></b></td>
              <td><?= e($ex['category']) ?></td>
              <td style="color:#c62828; font-weight:bold;"><?= number_format($ex['amount'], 2) ?> ر.س</td>
              <td><?= e($ex['created_by']) ?></td>
              <td>
                <?php if ($isSuperAdmin): ?>
                  <form method="post" onsubmit="return confirm('حذف هذا المصروف؟');">
                    <?php hidden($csrf, 'expense_delete', $ex['id'], $tab, $f); ?>
                    <button class="adm-btn sm danger" type="submit">حذف</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    </section>

  <?php elseif ($tab === 'walkin' && has_perm('walkin', $isSuperAdmin, $myPerms)): ?>
    <section class="adm-card">
      <h2>🛍️ حجز كاونتر فوري (البحث والتحديد السريع)</h2>
      <form method="post" class="adm-form">
        <?php hidden($csrf, 'walkin_create', 0, $tab, $f); ?>
        <div class="adm-grid">
          <label>اسم العميل<input type="text" name="client_name" required placeholder="مثال: سارة أحمد"></label>
          <label>رقم الجوال<input type="text" name="client_phone" required dir="ltr" placeholder="05xxxxxxxx"></label>
          <label>الخصم المباشر (ر.س)<input type="number" id="discount_field" step="0.01" name="discount" value="0.00" oninput="updateCalculations()"></label>
          <label>ملاحظات العميل / VIP<input type="text" name="notes" placeholder="أي ملاحظات خاصة بالعميل"></label>
        </div>

        <div class="prod-box-container" style="margin-top:10px;">
          <input type="text" id="prod_search" class="search-input-box" placeholder="🔍 ابحث عن خدمة أو منتج هنا..." onkeyup="filterServices()">
          
          <div class="product-grid-select" id="services_container">
            <?php foreach ($products as $p): $pPrice = (float)preg_replace('/[^0-9.]/', '', $p['price']); ?>
              <label class="prod-item-label service-card" data-name="<?= e($p['name_ar']) ?>">
                <div>
                  <input type="checkbox" name="product_ids[]" value="<?= $p['id'] ?>" data-price="<?= $pPrice ?>" onchange="updateCalculations()">
                  <span><?= e($p['name_ar']) ?></span>
                </div>
                <b><?= $pPrice ?> ر.س</b>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="calc-summary-box">
          <div class="calc-row-line"><span>مجموع الخدمات:</span> <span id="subtotal_val">0.00 ر.س</span></div>
          <div class="calc-row-line"><span>قيمة الخصم:</span> <span id="discount_val" style="color:#c62828;">0.00 ر.س</span></div>
          <div class="calc-row-line total-line"><span>الصافي النهائي المطلوب:</span> <span id="final_val">0.00 ر.س</span></div>
        </div>

        <button class="adm-btn ok" type="submit" style="margin-top:15px; width:100%; font-size:16px;">✔ حفظ وإصدار الفاتورة فوراً</button>
      </form>
    </section>

  <?php elseif ($tab === 'reservations' && has_perm('reservations', $isSuperAdmin, $myPerms)): ?>
    <div class="adm-filters"><?php foreach ($filters as $k => $lbl): ?><a href="admin.php?tab=reservations&f=<?= $k ?>" class="<?= $f===$k?'active':'' ?>"><?= $lbl ?></a><?php endforeach; ?></div>
    <?php foreach ($reservations as $r): 
      $vStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE phone = ? AND status='confirmed'");
      $vStmt->execute([$r['phone']]);
      $vCount = (int)$vStmt->fetchColumn();
      $loyalty = get_client_loyalty_badge($vCount);
    ?>
      <article class="adm-res">
        <div>
          <strong><?= e($r['name']) ?></strong> (<?= e($r['phone']) ?>) — <?= e($r['service_name']) ?>
          <span class="badge-loyalty <?= $loyalty['class'] ?>"><?= $loyalty['badge'] ?> (<?= $vCount ?> زيارات)</span>
          <br><small><?= e($r['res_date']) ?> <?= e(substr($r['res_time'],0,5)) ?></small>
        </div>
        <div class="adm-res-side">
          <span class="res-status"><?= e($statusNames[$r['status']] ?? $r['status']) ?></span>
          <?php if ($r['status'] === 'pending'): ?><form method="post"><?php hidden($csrf, 'res_status', $r['id'], $tab, $f); ?><input type="hidden" name="status" value="confirmed"><button class="adm-btn sm ok" type="submit">تأكيد</button></form><?php endif; ?>
          <?php if ($isSuperAdmin): ?>
            <form method="post" onsubmit="return confirm('حذف نهائي؟');"><?php hidden($csrf, 'res_delete', $r['id'], $tab, $f); ?><button class="adm-btn sm danger" type="submit">حذف</button></form>
          <?php endif; ?>
          <a href="print_invoice.php?id=<?= $r['id'] ?>" target="_blank" class="adm-btn sm ghost">🖨️ فاتورة حرارية</a>
        </div>
      </article>
    <?php endforeach; ?>

  <?php elseif ($tab === 'products' && has_perm('products', $isSuperAdmin, $myPerms)): ?>
    <section class="adm-card">
      <h2>➕ إضافة خدمة جديدة</h2>
      <form method="post" class="adm-form">
        <?php hidden($csrf, 'product_add', 0, $tab, $f); ?>
        <div class="adm-grid">
          <label>الفئة<select name="category"><?php foreach ($cats as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></label>
          <label>الاسم عربي<input type="text" name="name_ar" required></label>
          <label>الاسم إنجليزي<input type="text" name="name_en" dir="ltr"></label>
          <label>السعر (ر.س)<input type="text" name="price" required></label>
        </div>
        <button class="adm-btn ok" type="submit">إضافة الخدمة</button>
      </form>
    </section>
    <?php foreach ($cats as $k => $label): ?>
      <section class="adm-card" style="margin-top:10px;">
        <h3><?= e($label) ?></h3>
        <?php foreach ($byCat[$k] ?? [] as $p): ?>
          <div class="adm-prod" style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #eee;">
            <span><b><?= e($p['name_ar']) ?></b> — <?= e($p['price']) ?> ر.س</span>
            <form method="post" onsubmit="return confirm('حذف؟');">
              <?php hidden($csrf, 'product_delete', $p['id'], $tab, $f); ?>
              <button class="adm-btn sm danger" type="submit">حذف</button>
            </form>
          </div>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>

  <?php elseif ($tab === 'specialists' && has_perm('specialists', $isSuperAdmin, $myPerms)): ?>
    <section class="adm-card">
      <h2>👩‍🎨 إضافة أخصائية جديدة</h2>
      <form method="post" class="adm-form">
        <?php hidden($csrf, 'spec_add', 0, $tab, $f); ?>
        <label>اسم الأخصائية<input type="text" name="name_ar" required placeholder="مثال: مريم علي"></label>
        <button class="adm-btn ok" type="submit" style="margin-top:10px;">إضافة الأخصائية</button>
      </form>
    </section>
    <section class="adm-card" style="margin-top:15px;">
      <h2>قائمة الأخصائيات المتاحة</h2>
      <?php foreach ($specs as $sp): ?>
        <div class="adm-prod" style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #eee;">
          <span>👩‍🎨 <b><?= e($sp['name_ar']) ?></b></span>
          <form method="post" onsubmit="return confirm('حذف هذه الأخصائية؟');">
            <?php hidden($csrf, 'spec_delete', $sp['id'], $tab, $f); ?>
            <button class="adm-btn sm danger" type="submit">حذف</button>
          </form>
        </div>
      <?php endforeach; ?>
    </section>

  <?php elseif ($tab === 'staff' && has_perm('staff', $isSuperAdmin, $myPerms)): ?>
    <section class="adm-card">
      <h2>📧 إضافة موظف لاستلام الإشعارات</h2>
      <form method="post" class="adm-form">
        <?php hidden($csrf, 'staff_add', 0, $tab, $f); ?>
        <div class="adm-grid">
          <label>اسم الموظف<input type="text" name="staff_name" required placeholder="مثال: خالد"></label>
          <label>البريد الإلكتروني<input type="email" name="staff_email" required dir="ltr" placeholder="staff@example.com"></label>
        </div>
        <button class="adm-btn ok" type="submit">حفظ الموظف</button>
      </form>
    </section>

    <section class="adm-card" style="margin-top:15px;">
      <h2>📧 إرسال إشعار بريدي مباشر لموظف</h2>
      <form method="post" class="adm-form">
        <?php hidden($csrf, 'staff_send_email', 0, $tab, $f); ?>
        <label>اختر الموظف
          <select name="staff_id" required>
            <option value="">-- اختر الموظف --</option>
            <?php foreach ($pdo->query('SELECT * FROM staff_emails')->fetchAll() as $st): ?>
              <option value="<?= $st['id'] ?>"><?= e($st['name']) ?> (<?= e($st['email']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
        <label style="margin-top:10px; display:block;">نص الرسالة الإشعارية<textarea name="custom_message" rows="4" required placeholder="اكتب نص الرسالة هنا..."></textarea></label>
        <button class="adm-btn ok" type="submit" style="margin-top:10px;">إرسال البريد الآن</button>
      </form>

      <div style="margin-top:20px;">
        <h3>قائمة إيميلات الموظفين المسجلة</h3>
        <?php foreach ($pdo->query('SELECT * FROM staff_emails')->fetchAll() as $st): ?>
          <div class="adm-prod" style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #eee;">
            <span><b><?= e($st['name']) ?></b> (<?= e($st['email']) ?>)</span>
            <form method="post" onsubmit="return confirm('حذف هذا الموظف؟');">
              <?php hidden($csrf, 'staff_delete', $st['id'], $tab, $f); ?>
              <button class="adm-btn sm danger" type="submit">حذف</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

  <?php elseif ($tab === 'log' && $isSuperAdmin): ?>
    <section class="adm-card">
      <h2>🔍 سجل النشاطات والعمليات العظيم</h2>
      <div class="adm-table-wrap"><table class="adm-table">
        <thead><tr><th>الوقت</th><th>المنفذ</th><th>الحدث</th><th>التفاصيل</th></tr></thead>
        <tbody>
          <?php foreach ($pdo->query("SELECT * FROM activity_log ORDER BY id DESC LIMIT 100")->fetchAll() as $l): ?>
            <tr><td dir="ltr"><?= e($l['created_at']) ?></td><td><?= e($l['actor_name']) ?></td><td><?= e($l['action']) ?></td><td><?= e($l['details']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    </section>
  <?php endif; ?>
</main>

<script>
  function updateCalculations() {
    let subtotal = 0;
    let selectedBoxes = document.querySelectorAll('input[name="product_ids[]"]:checked');
    selectedBoxes.forEach(function(box) {
      subtotal += parseFloat(box.getAttribute('data-price')) || 0;
    });
    let discount = parseFloat(document.getElementById('discount_field').value) || 0;
    let finalAmount = Math.max(0, subtotal - discount);

    document.getElementById('subtotal_val').innerText = subtotal.toFixed(2) + ' ر.س';
    document.getElementById('discount_val').innerText = discount.toFixed(2) + ' ر.س';
    document.getElementById('final_val').innerText = finalAmount.toFixed(2) + ' ر.س';
  }

  function filterServices() {
    let q = document.getElementById('prod_search').value.toLowerCase();
    let cards = document.querySelectorAll('.service-card');
    cards.forEach(function(card) {
      let name = card.getAttribute('data-name').toLowerCase();
      card.style.display = name.includes(q) ? 'flex' : 'none';
    });
  }

  function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
  }

  let idleTimeInSeconds = 30 * 60;
  let timerInterval;

  function startIdleTimer() {
    clearInterval(timerInterval);
    timerInterval = setInterval(function() {
      idleTimeInSeconds--;
      
      let minutes = Math.floor(idleTimeInSeconds / 60);
      let seconds = idleTimeInSeconds % 60;
      let formattedTime = (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
      
      let timerDisp = document.getElementById("timer_disp");
      if (timerDisp) timerDisp.innerText = formattedTime;

      if (idleTimeInSeconds <= 0) {
        clearInterval(timerInterval);
        alert("⚠️ تم تسجيل الخروج تلقائياً بسبب الخمول لمدة 30 دقيقة.");
        window.location.href = "logout.php";
      }
    }, 1000);
  }

  function resetIdleTimer() {
    idleTimeInSeconds = 30 * 60;
  }

  window.onload = startIdleTimer;
  window.onmousemove = resetIdleTimer;
  window.onkeypress = resetIdleTimer;
  window.onclick = resetIdleTimer;
  window.onscroll = resetIdleTimer;
</script>
</body>
</html>