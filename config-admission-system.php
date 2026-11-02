<?php
// Database connection + helpers. Change user/pass to match your MySQL setup.
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

$DB_HOST = 'localhost';
$DB_NAME = 'neon_orbit';
$DB_USER = 'root';   // XAMPP default
$DB_PASS = '';       // XAMPP default is empty

function json_out(bool $ok, string $message, array $extra = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
    exit;
}

// Hitilafu yoyote isiyotarajiwa irudi kama JSON ili ionekane kwenye fomu/dashboard (weka $DEBUG = false ukiweka mtandaoni).
$DEBUG = true;
set_exception_handler(function ($e) use ($DEBUG) {
    http_response_code(500);
    json_out(false, $DEBUG ? 'Hitilafu ya seva: ' . $e->getMessage() : 'Hitilafu ya seva.');
});

if (!defined('SKIP_DB')) try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    json_out(false, 'Database connection failed.');
}

// Mtumiaji aliyeingia sasa (au null)
function current_user(PDO $pdo): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $s = $pdo->prepare('SELECT id, full_name, email, role FROM users WHERE id = ?');
    $s->execute([$_SESSION['user_id']]);
    return $s->fetch() ?: null;
}
function require_user(PDO $pdo): array {
    $u = current_user($pdo);
    if (!$u) { http_response_code(401); json_out(false, 'Ingia kwanza.', ['redirect' => 'Neon_Orbit_Login.html']); }
    return $u;
}
function require_admin(PDO $pdo): array {
    // Hakuna tena login wala nenosiri la admin: ukurasa wa admin unafunguka moja kwa moja.
    return ['id' => null, 'full_name' => 'Admin', 'email' => '', 'role' => 'admin'];
}
