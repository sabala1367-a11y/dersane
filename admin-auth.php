<?php
declare(strict_types=1);
ini_set('display_errors', '0');          // onyo la PHP lisiharibu jibu la JSON
require __DIR__ . '/admin-user-config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply(bool $ok, string $msg = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(false, 'Ombi si sahihi.', 405);

$in = json_decode((string)file_get_contents('php://input'), true);
$email    = strtolower(trim((string)($in['email'] ?? '')));
$password = (string)($in['password'] ?? '');

if ($email === '' || $password === '') reply(false, 'Jaza username na password.', 422);

try {
    $st = login_db()->prepare('SELECT id, email, password FROM admin_user WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $u = $st->fetch();
} catch (Throwable $e) {
    reply(false, login_friendly($e), 500);
}

if (!$u || !password_verify($password, (string)$u['password'])) {
    usleep(400000);                                   // punguza majaribio ya kubahatisha
    reply(false, 'Username au password si sahihi.', 401);
}

session_regenerate_id(true);
$_SESSION['user_id']  = (int)$u['id'];
$_SESSION['username'] = $u['email'];

reply(true, 'Umeingia.');
