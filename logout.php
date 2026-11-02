<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    if (is_admin()) {
        admin_close(date('Y-m-d H:i:s'), 'admin_logout');
    } elseif (is_logged_in()) {
        log_event('customer', $_SESSION['user_name'] ?? '', 'customer_logout');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
header('Location: index.php'); exit;
