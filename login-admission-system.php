<?php
require 'config-admission-system.php';

$in       = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = strtolower(trim($in['email'] ?? ''));
$password = $in['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    json_out(false, 'Enter your email and password.');
}

$stmt = $pdo->prepare('SELECT id, full_name, password_hash, role FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_out(false, 'Incorrect email or password.');
}

if ($user['role'] !== 'user') json_out(false, 'Akaunti hii si ya mwombaji. Tumia ukurasa wa msimamizi.');

session_regenerate_id(true);          // new session ID for this user
$_SESSION['user_id'] = $user['id'];   // identifies this user on every later page

json_out(true, 'Welcome back, ' . $user['full_name'] . '!', ['redirect' => 'dashboard-admission-system.php']);
