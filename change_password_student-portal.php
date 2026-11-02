<?php
/** change_password_student-portal.php — mwanafunzi anabadilisha nenosiri lake (POST JSON: current, new; kichwa X-CSRF-Token). */
declare(strict_types=1);
require __DIR__ . '/config_student-portal.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function cp_out(int $code, array $d) { http_response_code($code); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') cp_out(405, ['error' => 'Tumia POST.']);
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') cp_out(401, ['error' => 'Hujaingia.']);
$tok = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['csrf']) || !hash_equals((string)$_SESSION['csrf'], $tok)) cp_out(403, ['error' => 'Token si sahihi. Pakia ukurasa upya.']);

$in = json_decode((string)file_get_contents('php://input'), true);
$cur = is_array($in) ? (string)($in['current'] ?? '') : '';
$new = is_array($in) ? (string)($in['new'] ?? '') : '';
if (strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/\d/', $new) || !preg_match('/[^A-Za-z0-9]/', $new)) {
    cp_out(422, ['error' => 'Nenosiri jipya halikidhi masharti (herufi 8+, herufi kubwa, namba na alama).']);
}
$pdo = db();
$st = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? AND role = 'student'");
$st->execute([$_SESSION['user_id']]);
$hash = $st->fetchColumn();
if ($hash === false || !password_verify($cur, (string)$hash)) { usleep(600000); cp_out(422, ['error' => 'Nenosiri la sasa si sahihi.']); }
if ($cur === $new) cp_out(422, ['error' => 'Nenosiri jipya lazima litofautiane na la sasa.']);
$pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
cp_out(200, ['ok' => true]);
