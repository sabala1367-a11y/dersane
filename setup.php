<?php
// Fungua mara moja: http://localhost/<folda>/setup.php  -> inatengeneza/inarekebisha database. Inaweza kurudiwa bila madhara.
define('SKIP_DB', true);
require 'config-admission-system.php';
header('Content-Type: text/html; charset=utf-8');
$log = [];
try {
    $opt  = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    $root = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, $opt);
    $root->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, $opt);
    $log[] = "Database <b>$DB_NAME</b>: sawa";

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $col = function ($t, $c) use ($db) {
        $q = $db->prepare('SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $q->execute([$t, $c]); return $q->fetchColumn();
    };
    if ($col('users', 'role') === false) {
        $db->exec("ALTER TABLE users ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER password_hash");
        $log[] = 'Nimeongeza safu ya <b>role</b> kwenye users (hii ndiyo ilikuwa inakosekana)';
    } else { $log[] = 'Safu ya role: ipo tayari'; }

    $t = $col('users', 'id');   // aina halisi ya users.id, ili foreign key ilingane
    $db->exec("CREATE TABLE IF NOT EXISTS applications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id $t NOT NULL UNIQUE,
        first_name VARCHAR(60) NOT NULL, middle_name VARCHAR(60) NULL, last_name VARCHAR(60) NOT NULL,
        gender VARCHAR(20) NOT NULL, dob DATE NOT NULL, nationality VARCHAR(40) NOT NULL, special_needs VARCHAR(255) NULL,
        phone VARCHAR(20) NOT NULL, email VARCHAR(150) NOT NULL, region VARCHAR(60) NOT NULL, district VARCHAR(80) NOT NULL,
        emergency_name VARCHAR(100) NOT NULL, emergency_relation VARCHAR(30) NOT NULL, emergency_phone VARCHAR(20) NOT NULL,
        csee_school VARCHAR(150) NOT NULL, csee_index VARCHAR(25) NOT NULL, csee_year SMALLINT NOT NULL, csee_subjects TEXT NOT NULL,
        acsee_school VARCHAR(150) NULL, acsee_index VARCHAR(25) NULL, acsee_combination VARCHAR(20) NULL,
        course VARCHAR(150) NOT NULL, college VARCHAR(150) NOT NULL, study_level VARCHAR(30) NOT NULL, funding VARCHAR(60) NOT NULL, source VARCHAR(60) NULL,
        status ENUM('pending','admitted','rejected') NOT NULL DEFAULT 'pending',
        admission_no VARCHAR(20) NULL UNIQUE, house_location VARCHAR(100) NULL, reject_reason TEXT NULL,
        submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, decided_at DATETIME NULL, decided_by $t NULL,
        INDEX idx_status (status),
        CONSTRAINT fk_app_user  FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_app_admin FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL)");
    $log[] = 'Jedwali la <b>applications</b>: sawa';

    $n = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $a = $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    $p = $db->query('SELECT COUNT(*) FROM applications')->fetchColumn();
    $log[] = "Watumiaji: $n &middot; Admin: $a &middot; Maombi yaliyohifadhiwa: $p";
    if ((int)$a === 0) $log[] = "Bado hakuna admin. Kwenye phpMyAdmin endesha: <code>UPDATE users SET role='admin' WHERE email='barua-yako@mfano.com';</code>";
    $ok = true;
} catch (Throwable $e) { $log[] = 'KOSA: ' . htmlspecialchars($e->getMessage()); $ok = false; }
echo '<!doctype html><meta charset="utf-8"><title>Setup</title><body style="font:16px system-ui;max-width:640px;margin:40px auto;line-height:1.7">';
echo '<h2>' . ($ok ? 'Imekamilika ✓' : 'Imeshindikana ✕') . '</h2><ul><li>' . implode('</li><li>', $log) . '</li></ul>';
echo $ok ? '<p><a href="Neon_Orbit_Login.html">Nenda kwenye login</a></p>' : '<p>Angalia jina/neno la siri la MySQL ndani ya config-admission-system.php.</p>';
