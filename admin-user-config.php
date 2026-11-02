<?php
/*
 * admin-user-config.php - Session + muunganisho wa MySQL ya login (database: login_db, jedwali: admin_user)
 * Majina ya constants ni LOGIN_DB_* ili yasigongane na DB_* / ANN_DB_* za dashboard.php
 * Badilisha LOGIN_DB_USER / LOGIN_DB_PASS kama MySQL yako ina user/password tofauti (XAMPP: root na tupu).
 *
 * Kama database login_db au jedwali admin_user halipo, litatengenezwa lenyewe mara ya kwanza
 * (kama MySQL user ana ruhusa), na admin wa kwanza ataingizwa: sabala1367@gmail.com / 123.
 */
const LOGIN_DB_HOST = 'localhost';
const LOGIN_DB_NAME = 'login_db';
const LOGIN_DB_USER = 'root';
const LOGIN_DB_PASS = '';

const LOGIN_SEED_EMAIL = 'sabala1367@gmail.com';
const LOGIN_SEED_HASH  = '$2y$10$pIl6RMb7m3Zd7kf6GXBcWuekSpPV3kZE3vWmuhwUUMmXsEvXmyx/S';   // hash ya password: 123

const LOGIN_DDL = <<<'SQL'
CREATE TABLE IF NOT EXISTS admin_user (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(150) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    session_set_cookie_params(0, '/');          // fomu hii inafanya kazi kwenye toleo lolote la PHP
    session_start();
}

function login_db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    $dsn  = 'mysql:host=' . LOGIN_DB_HOST . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn . ';dbname=' . LOGIN_DB_NAME, LOGIN_DB_USER, LOGIN_DB_PASS, $opts);
    } catch (PDOException $e) {
        if (!preg_match('/\[1049\]/', $e->getMessage())) throw $e;          // database haipo -> jaribu kuitengeneza
        $c = new PDO($dsn, LOGIN_DB_USER, LOGIN_DB_PASS, $opts);
        $c->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', LOGIN_DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo = new PDO($dsn . ';dbname=' . LOGIN_DB_NAME, LOGIN_DB_USER, LOGIN_DB_PASS, $opts);
    }
    login_init($pdo);
    return $pdo;
}

/* Jedwali likikosekana: litengeneze na uweke admin wa kwanza. Likiwepo, halitaguswa. */
function login_init(PDO $pdo): void {
    if ($pdo->query("SHOW TABLES LIKE 'admin_user'")->fetch()) return;
    $pdo->exec(LOGIN_DDL);
    $pdo->prepare('INSERT INTO admin_user (email, password) VALUES (?, ?)')->execute([LOGIN_SEED_EMAIL, LOGIN_SEED_HASH]);
}

/* Ujumbe unaoeleweka wa kosa la database (bila kutoa siri) */
function login_friendly(Throwable $e): string {
    $m = $e->getMessage();
    if (stripos($m, 'could not find driver') !== false) return 'Driver ya MySQL (pdo_mysql) haijawashwa. Washa extension=pdo_mysql kwenye php.ini.';
    if (preg_match('/\[1045\]/', $m))                   return 'MySQL imekataa user/password. Angalia LOGIN_DB_USER na LOGIN_DB_PASS kwenye admin-user-config.php.';
    if (preg_match('/\[(2002|2003|2006)\]/', $m))       return 'Siwezi kufikia MySQL. Washa MySQL kwenye XAMPP Control Panel.';
    if (preg_match('/\[1044\]/', $m))                   return 'User wa MySQL hana ruhusa kwenye login_db. Endesha database.sql kwenye phpMyAdmin.';
    if (preg_match('/\[1049\]/', $m))                   return 'Database login_db haipo. Endesha database.sql kwenye phpMyAdmin.';
    if (preg_match('/\[(1054|1146)\]|42S02|42S22/', $m)) return 'Jedwali admin_user halipo au lina muundo tofauti. Endesha database.sql kwenye phpMyAdmin.';
    return 'Hitilafu ya database. Angalia mipangilio ya MySQL kwenye admin-user-config.php.';
}
