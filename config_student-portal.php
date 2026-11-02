<?php
/**
 * config_student-portal.php — mipangilio ya MySQL + session. Inatumiwa na faili zote (login_student-portal.php, auth_student-portal.php,
 * admin_dashboard_student-portal.php, Student_Portal_student-portal.php, api_student-portal.php n.k.).
 *
 * Badilisha DB_USER / DB_PASS ziendane na seva yako (XAMPP/WAMP kwa kawaida: root na nenosiri tupu).
 * Unaweza pia kuziweka kwenye config_student-portal.local.php (haihitaji kuhaririwa hapa) au kwa environment variables.
 */
declare(strict_types=1);

if (is_file(__DIR__ . '/config_student-portal.local.php')) require __DIR__ . '/config_student-portal.local.php';

defined('DB_HOST')    || define('DB_HOST',    getenv('SP_DB_HOST') ?: 'localhost');
defined('DB_PORT')    || define('DB_PORT',    getenv('SP_DB_PORT') ?: '3306');
defined('DB_NAME')    || define('DB_NAME',    getenv('SP_DB_NAME') ?: 'db-student_portal');
defined('DB_USER')    || define('DB_USER',    getenv('SP_DB_USER') ?: 'root');
defined('DB_PASS')    || define('DB_PASS',    getenv('SP_DB_PASS') !== false ? (string)getenv('SP_DB_PASS') : '');

/** Muda wa kutokuwa na shughuli (sekunde) kabla session haijaisha. Mwanafunzi: dakika 15 (kama portal inavyosema). */
const SP_IDLE_STUDENT = 15 * 60;
const SP_IDLE_ADMIN   = 60 * 60;
/**
 * ADMIN HANA LOGIN. Dashboard ya admin inafunguka tu kutoka kwenye kompyuta/anwani (IP) zilizoruhusiwa hapa.
 * Kwa chaguo-msingi: kompyuta ya seva yenyewe (localhost). Ongeza IP nyingine kwenye config_student-portal.local.php:
 *   define('SP_ADMIN_IPS', ['127.0.0.1', '::1', '203.0.113.10']);
 */
defined('SP_ADMIN_IPS') || define('SP_ADMIN_IPS', ['127.0.0.1', '::1']);

/** Majaribio ya nenosiri yasiyofanikiwa kabla akaunti haijafungwa kwa muda. */
const SP_MAX_FAILS    = 5;
const SP_LOCK_SECONDS = 10 * 60;

date_default_timezone_set('Africa/Dar_es_Salaam');

/* ---------------- Hifadhidata (PDO) ---------------- */
function sp_fail(int $code, string $msg) {
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    $wantsHtml = stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'text/html') !== false
        && ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === '';
    if ($wantsHtml) { header('Content-Type: text/plain; charset=utf-8'); echo $msg; }
    else { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success' => false, 'error' => $msg, 'message' => $msg], JSON_UNESCAPED_UNICODE); }
    exit;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec("SET time_zone = '+03:00'");
    } catch (PDOException $e) {
        error_log('[student_portal] DB connect: ' . $e->getMessage());
        sp_fail(500, 'Imeshindwa kuunganisha na database. Angalia DB_USER / DB_PASS kwenye config_student-portal.php na kwamba database `db-student_portal` imeundwa.');
    }
    return $pdo;
}

/* ---------------- Session ---------------- */
if (PHP_SAPI !== 'cli' && !defined('SP_NO_SESSION') && session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('SPSESSID');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    ini_set('session.use_strict_mode', '1');
    session_start();

    // Kuisha kwa session baada ya kukaa bila shughuli.
    $idle = !empty($_SESSION['is_admin']) ? SP_IDLE_ADMIN : SP_IDLE_STUDENT;
    if (!empty($_SESSION['last_activity']) && time() - (int)$_SESSION['last_activity'] > $idle) {
        $_SESSION = [];
    }
    if (!empty($_SESSION['role'])) $_SESSION['last_activity'] = time();
}

/* ---------------- Admin (bila login) ---------------- */
function sp_admin_ip_ok(): bool {
    return in_array((string)($_SERVER['REMOTE_ADDR'] ?? ''), SP_ADMIN_IPS, true);
}
// Alama ya admin kwenye session inakubaliwa tu kutoka IP iliyoruhusiwa.
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['is_admin']) && !sp_admin_ip_ok()) {
    $_SESSION = [];
}

/* ---------------- Walinzi wa kurasa ---------------- */
function sp_redirect(string $to) {
    header('Location: ' . $to);
    exit;
}
/** Ukurasa/API ya admin: hakuna login. Inaruhusiwa tu kutoka IP kwenye SP_ADMIN_IPS; wengine wanakataliwa. */
function sp_require_admin(bool $json = false): void {
    if (!sp_admin_ip_ok()) {
        if ($json) sp_fail(403, 'Huna ruhusa ya kufungua ukurasa huu.');
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Huna ruhusa ya kufungua ukurasa huu.');
    }
    if (($_SESSION['role'] ?? '') !== 'admin') {
        // Admin hapati akaunti ya mwanafunzi: session mpya ya admin tu (bila reg_no).
        $_SESSION = ['role' => 'admin', 'is_admin' => true, 'reg_no' => null];
    }
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $_SESSION['last_activity'] = time();
}

/** Wapi mtumiaji aelekezwe baada ya kuingia, kulingana na role yake. */
function sp_home_for(string $role): string {
    return 'Student_Portal_student-portal.php';   // login_student-portal.php ni ya wanafunzi tu
}

/* ---------------- Akaunti za kuingia (users) ---------------- */
/**
 * Tengeneza / sasisha akaunti ya mwanafunzi (inaitwa na dashboard anapohifadhi mwanafunzi).
 * - $password === null au '' : akaunti mpya inapewa nenosiri = namba ya usajili; akaunti iliyopo haibadilishwi.
 * Inarudisha null ikiwa sawa, au ujumbe wa kosa.
 */
function sp_sync_student_user(string $reg, string $email, ?string $password): ?string {
    $pdo = db();
    $email = mb_strtolower(trim($email));
    // Barua pepe inatumika kuingia tu kama haijatumiwa na akaunti nyingine; la sivyo mwanafunzi huingia kwa namba ya usajili.
    $useEmail = null;
    if ($email !== '') {
        $c = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (email = ? OR username = ?) AND (reg_no IS NULL OR reg_no <> ?)');
        $c->execute([$email, $email, $reg]);
        if ((int)$c->fetchColumn() === 0) $useEmail = $email;
    }
    $login = $useEmail ?? $reg;
    $st = $pdo->prepare("SELECT id FROM users WHERE reg_no = ? AND role = 'student'");
    $st->execute([$reg]);
    $uid = $st->fetchColumn();
    try {
        if ($uid === false) {
            // Nenosiri la mwanzo la mwanafunzi = namba yake ya usajili (iliyotengenezwa na server).
            if ($password === null || $password === '') $password = $reg;
            $pdo->prepare("INSERT INTO users (username, email, password_hash, role, reg_no) VALUES (?,?,?,'student',?)")
                ->execute([$login, $useEmail, password_hash($password, PASSWORD_DEFAULT), $reg]);
        } else {
            $pdo->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?')->execute([$login, $useEmail, $uid]);
            if ($password !== null && $password !== '') {
                $pdo->prepare('UPDATE users SET password_hash = ?, failed_logins = 0, locked_until = NULL WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $uid]);
            }
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') return 'Jina la kuingia hilo tayari linatumika na mtu mwingine.';
        error_log('[student_portal] sync user: ' . $e->getMessage());
        return 'Imeshindwa kuhifadhi akaunti ya kuingia.';
    }
    return null;
}
