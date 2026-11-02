<?php
// ينشئ ويحدّث كل الجداول تلقائياً (آمن للتكرار)
function ensure_schema(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      email VARCHAR(150) NOT NULL UNIQUE,
      phone VARCHAR(20) NOT NULL,
      password_hash VARCHAR(255) NOT NULL,
      email_verified TINYINT(1) NOT NULL DEFAULT 0,
      verify_code_hash VARCHAR(255) NULL,
      verify_expires_at DATETIME NULL,
      verify_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
      verify_sent_at DATETIME NULL,
      verified_at DATETIME NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reservations (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      name VARCHAR(100) NOT NULL,
      phone VARCHAR(20) NOT NULL,
      res_date DATE NOT NULL,
      res_time TIME NOT NULL,
      service_id INT NULL,
      service_name VARCHAR(150) NOT NULL DEFAULT '',
      specialist_id INT NULL,
      specialist_name VARCHAR(100) NOT NULL DEFAULT '',
      notes TEXT NULL,
      status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
      confirmed_by VARCHAR(50) NULL,
      confirmed_at DATETIME NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // ترقية قاعدة قديمة (إن وُجدت أعمدة النسخة السابقة)
    $rc = $pdo->query('SHOW COLUMNS FROM reservations')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['service_id' => 'INT NULL', 'service_name' => "VARCHAR(150) NOT NULL DEFAULT ''", 'specialist_id' => 'INT NULL',
              'specialist_name' => "VARCHAR(100) NOT NULL DEFAULT ''", 'confirmed_by' => 'VARCHAR(50) NULL', 'confirmed_at' => 'DATETIME NULL'] as $c => $def) {
        if (!in_array($c, $rc, true)) $pdo->exec("ALTER TABLE reservations ADD COLUMN `$c` $def");
    }
    foreach (['guests', 'area'] as $old) {
        if (in_array($old, $rc, true)) $pdo->exec("ALTER TABLE reservations MODIFY `$old` VARCHAR(30) NULL");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
      id INT AUTO_INCREMENT PRIMARY KEY,
      category VARCHAR(20) NOT NULL,
      name_ar VARCHAR(100) NOT NULL,
      name_en VARCHAR(100) NOT NULL DEFAULT '',
      desc_ar VARCHAR(255) NOT NULL DEFAULT '',
      desc_en VARCHAR(255) NOT NULL DEFAULT '',
      price VARCHAR(20) NOT NULL,
      badge_ar VARCHAR(40) NOT NULL DEFAULT '',
      badge_en VARCHAR(40) NOT NULL DEFAULT '',
      badge_gold TINYINT(1) NOT NULL DEFAULT 0,
      highlight TINYINT(1) NOT NULL DEFAULT 0,
      sort_order INT NOT NULL DEFAULT 0,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS specialists (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name_ar VARCHAR(100) NOT NULL,
      name_en VARCHAR(100) NOT NULL,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      sort_order INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
      id INT AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(50) NOT NULL UNIQUE,
      email VARCHAR(150) NULL,
      password_hash VARCHAR(255) NOT NULL,
      role ENUM('super_admin','manager','member') NOT NULL DEFAULT 'manager',
      display_name VARCHAR(100) NOT NULL DEFAULT '',
      permissions TEXT NULL,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      two_factor_code_hash VARCHAR(255) NULL,
      two_factor_expires_at DATETIME NULL,
      last_login_at DATETIME NULL,
      last_logout_at DATETIME NULL,
      last_seen_at DATETIME NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // ترقية جدول المدراء (إضافة بريد وصلاحيات ورتبة سوبر أدمن إذا نقصت)
    $admCols = $pdo->query('SHOW COLUMNS FROM admins')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('email', $admCols)) $pdo->exec("ALTER TABLE admins ADD COLUMN email VARCHAR(150) NULL AFTER username");
    if (!in_array('permissions', $admCols)) $pdo->exec("ALTER TABLE admins ADD COLUMN permissions TEXT NULL AFTER display_name");
    if (!in_array('two_factor_code_hash', $admCols)) $pdo->exec("ALTER TABLE admins ADD COLUMN two_factor_code_hash VARCHAR(255) NULL AFTER is_active");
    if (!in_array('two_factor_expires_at', $admCols)) $pdo->exec("ALTER TABLE admins ADD COLUMN two_factor_expires_at DATETIME NULL AFTER two_factor_code_hash");

    // تحديث نوع العمود role ليشمل super_admin
    $pdo->exec("ALTER TABLE admins MODIFY COLUMN role ENUM('super_admin','manager','member') NOT NULL DEFAULT 'manager'");

    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_log (
      id BIGINT AUTO_INCREMENT PRIMARY KEY,
      actor_type VARCHAR(12) NOT NULL,
      actor_name VARCHAR(100) NOT NULL DEFAULT '',
      action VARCHAR(40) NOT NULL,
      details VARCHAR(255) NOT NULL DEFAULT '',
      ip VARCHAR(45) NOT NULL DEFAULT '',
      created_at DATETIME NOT NULL,
      INDEX idx_created (created_at), INDEX idx_type (actor_type), INDEX idx_action (action)
    ) ENGINE=InnoDB");
}
