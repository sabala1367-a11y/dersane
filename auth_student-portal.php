<?php
/**
 * auth_student-portal.php — inapokea kuingia kutoka login_student-portal.php (JSON: {email, password}).
 * Ni ya WANAFUNZI TU: "email" ni barua pepe au namba ya usajili. Admin hana login (akaunti za admin zinakataliwa).
 * Inarudisha {success, redirect}: mwanafunzi -> Student_Portal_student-portal.php.
 */
declare(strict_types=1);
ob_start();                       // maonyo/makosa ya PHP yasiharibu JSON
ini_set('display_errors', '0');
set_exception_handler(function (Throwable $e) {
    error_log('[student_portal] auth: ' . $e->getMessage());
    auth_out(['success' => false, 'message' => 'Hitilafu ya seva. Hakikisha database `db-student_portal` imeingizwa (database/schema_student-portal.sql) na DB_USER / DB_PASS ni sahihi.'], 500);
});
require __DIR__ . '/config_student-portal.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function auth_out(array $d, int $code = 200) {
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') auth_out(['success' => false, 'message' => 'Tumia POST.'], 405);

$in = json_decode((string)file_get_contents('php://input'), true);
$login = is_array($in) ? trim((string)($in['email'] ?? '')) : '';
$pass  = is_array($in) ? (string)($in['password'] ?? '') : '';
if ($login === '' || $pass === '') auth_out(['success' => false, 'message' => 'Weka jina la kuingia na nenosiri.'], 422);
if (mb_strlen($login) > 190 || strlen($pass) > 200) auth_out(['success' => false, 'message' => 'Taarifa si sahihi.'], 422);

$pdo = db();
$st = $pdo->prepare("SELECT * FROM users WHERE role = 'student' AND (username = ? OR email = ? OR reg_no = ?) ORDER BY (username = ?) DESC, id LIMIT 1");
$st->execute([$login, mb_strtolower($login), $login, $login]);
$u = $st->fetch();

// Hash ya bandia ili muda wa jibu usimwambie mtu kama akaunti ipo.
$dummy = '$2y$10$eFUbNZ8YpZGlLAE8hmHuTujArN5T9vR6pMbB9hGDALeFeffC.vuP6';   // hash halali tu ili muda wa jibu ufanane
if ($u && $u['locked_until'] && strtotime((string)$u['locked_until']) > time()) {
    $mins = (int)ceil((strtotime((string)$u['locked_until']) - time()) / 60);
    auth_out(['success' => false, 'message' => "Akaunti imefungwa kwa muda kwa sababu ya majaribio mengi. Jaribu tena baada ya dakika $mins."], 429);
}
$ok = password_verify($pass, $u ? (string)$u['password_hash'] : $dummy) && $u;

if (!$ok) {
    if ($u) {
        $fails = (int)$u['failed_logins'] + 1;
        if ($fails >= SP_MAX_FAILS) {
            $pdo->prepare('UPDATE users SET failed_logins = 0, locked_until = ? WHERE id = ?')
                ->execute([date('Y-m-d H:i:s', time() + SP_LOCK_SECONDS), $u['id']]);
        } else {
            $pdo->prepare('UPDATE users SET failed_logins = ? WHERE id = ?')->execute([$fails, $u['id']]);
        }
    }
    usleep(600000);
    auth_out(['success' => false, 'message' => 'Jina la kuingia au nenosiri si sahihi.'], 401);
}

// Mwanafunzi lazima awe na rekodi yake kwenye students.
$c = $pdo->prepare('SELECT COUNT(*) FROM students WHERE reg_no = ?');
$c->execute([$u['reg_no']]);
if (!(int)$c->fetchColumn()) auth_out(['success' => false, 'message' => 'Rekodi ya mwanafunzi haipatikani. Wasiliana na msimamizi.'], 403);

$pdo->prepare('UPDATE users SET failed_logins = 0, locked_until = NULL, last_login = NOW() WHERE id = ?')->execute([$u['id']]);
if (password_needs_rehash((string)$u['password_hash'], PASSWORD_DEFAULT)) {
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
}

session_regenerate_id(true);
$_SESSION = [
    'user_id'       => (int)$u['id'],
    'role'          => 'student',
    'is_admin'      => false,
    'reg_no'        => $u['reg_no'],
    'csrf'          => bin2hex(random_bytes(32)),
    'last_activity' => time(),
];
auth_out(['success' => true, 'role' => 'student', 'redirect' => sp_home_for('student')]);
