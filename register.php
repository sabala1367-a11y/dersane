<?php
require 'config-admission-system.php';

$in       = json_decode(file_get_contents('php://input'), true) ?? [];
$name     = trim($in['name'] ?? '');
$email    = strtolower(trim($in['email'] ?? ''));
$password = $in['password'] ?? '';

if (mb_strlen($name) < 2)                       json_out(false, 'Enter your full name.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(false, 'Enter a valid email address.');
if (strlen($password) < 6)                      json_out(false, 'Password must be at least 6 characters.');

try {
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) json_out(false, 'This email is already registered.');

    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);

    json_out(true, 'Account created! You can now log in.');
} catch (PDOException $e) {
    if ($e->getCode() === '23000') json_out(false, 'This email is already registered.');
    http_response_code(500);
    json_out(false, 'Something went wrong. Try again.');
}
