<?php
/**
 * simulate_payment_student-portal.php — KUJARIBU TU (command line). Inatuma malipo bandia kwenye payment_webhook_student-portal.php.
 *   php simulate_payment_student-portal.php <control_number> <kiasi> [channel] [url]
 *   mf: php simulate_payment_student-portal.php 12345678909876 150000 M-Pesa http://localhost/payment_webhook_student-portal.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$cfg = require __DIR__ . '/payment_config_student-portal.php';
[$_, $cn, $amount] = array_pad($argv, 3, null);
if (!$cn || !$amount) { fwrite(STDERR, "Matumizi: php simulate_payment_student-portal.php <control_number> <kiasi> [channel] [url]\n"); exit(1); }
$channel = $argv[3] ?? 'M-Pesa';
$url = $argv[4] ?? 'http://localhost/payment_webhook_student-portal.php';
$body = json_encode([
    'control_number' => $cn, 'amount' => (int)$amount, 'txn_id' => 'TEST' . bin2hex(random_bytes(5)),
    'channel' => $channel, 'paid_at' => date('c'), 'status' => 'SUCCESS',
]);
$ctx = stream_context_create(['http' => [
    'method' => 'POST', 'ignore_errors' => true, 'content' => $body,
    'header' => "Content-Type: application/json\r\nX-Signature: " . hash_hmac('sha256', $body, $cfg['secret']) . "\r\n",
]]);
echo file_get_contents($url, false, $ctx), "\n";
