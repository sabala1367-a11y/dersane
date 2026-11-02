<?php
/**
 * payment_webhook_student-portal.php — Mlango wa malipo ya otomatiki.
 *
 * Benki / mobile money / GePG (au mtoa huduma wa malipo) inaita URL hii kila mwanafunzi akilipa
 * kupitia control number. Malipo yanaingia yenyewe, kiasi kilicholipwa kinasasishwa, na risiti inatolewa.
 *
 *   POST payment_webhook_student-portal.php
 *   Header:  X-Signature: <HMAC-SHA256 ya body mbichi (hex), kwa kutumia 'secret' ya payment_config_student-portal.php>
 *   Body (JSON):
 *     {
 *       "control_number": "12345678909876",   // inahitajika
 *       "amount":         150000,             // inahitajika (TZS)
 *       "txn_id":         "MP2610281234",     // inahitajika — namba ya muamala (ya kipekee)
 *       "channel":        "M-Pesa",           // si lazima
 *       "paid_at":        "2026-10-28T10:15:00+03:00",   // si lazima
 *       "status":         "SUCCESS"           // si lazima; ikiwa si mafanikio, inapuuzwa
 *     }
 * Muamala ule ule (txn_id) ukitumwa mara mbili hauongezwi mara ya pili.
 */
define('SP_NO_SESSION', true);   // webhook ni mashine-kwa-mashine: haihitaji session
require_once __DIR__ . '/student_store_student-portal.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function wh_out(int $code, array $data) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') wh_out(405, ['error' => 'Tumia POST.']);

$cfg = require __DIR__ . '/payment_config_student-portal.php';
$secret = (string)($cfg['secret'] ?? '');
if ($secret === '' || $secret === 'BADILISHA-HII-NA-MSTARI-MREFU-WA-SIRI') {
    wh_out(503, ['error' => 'Webhook haijawekwa: badilisha secret kwenye payment_config_student-portal.php.']);
}
$allowed = (array)($cfg['allowed_ips'] ?? []);
if ($allowed && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed, true)) wh_out(403, ['error' => 'IP hairuhusiwi.']);

$raw = (string)file_get_contents('php://input');
if ($raw === '' || strlen($raw) > 65536) wh_out(400, ['error' => 'Data si sahihi.']);
$sig = strtolower(trim((string)($_SERVER['HTTP_X_SIGNATURE'] ?? '')));
if (!hash_equals(hash_hmac('sha256', $raw, $secret), $sig)) wh_out(401, ['error' => 'Saini si sahihi.']);

$in = json_decode($raw, true);
if (!is_array($in)) wh_out(400, ['error' => 'JSON si sahihi.']);

$status = strtolower(trim((string)($in['status'] ?? 'success')));
if (!in_array($status, ['success', 'paid', 'confirmed', 'completed', 'successful'], true)) {
    wh_out(200, ['ok' => true, 'ignored' => true, 'reason' => 'Muamala haujafanikiwa.']);
}

$control = preg_replace('/\D/', '', (string)($in['control_number'] ?? ''));
$amount  = is_numeric($in['amount'] ?? null) ? (int)round((float)$in['amount']) : 0;
$txn     = s_str($in['txn_id'] ?? '', 60);
$channel = s_str($in['channel'] ?? '', 40);
if ($channel === '') $channel = 'Control number';
if ($control === '' || $txn === '' || $amount <= 0 || $amount > 999999999) {
    wh_out(422, ['error' => 'control_number, amount na txn_id vinahitajika.']);
}

$tz = new DateTimeZone('Africa/Dar_es_Salaam');
try { $when = !empty($in['paid_at']) ? (new DateTime((string)$in['paid_at']))->setTimezone($tz) : new DateTime('now', $tz); }
catch (Exception $e) { $when = new DateTime('now', $tz); }
$date = $when->format('Y-m-d');
$time = $when->format('H:i');

$res = store_update(function (array &$d) use ($control, $amount, $txn, $channel, $date, $time) {
    // Muamala ule ule usiongezwe mara mbili (benki zinajaribu tena).
    foreach ($d['students'] as $st) {
        foreach (($st['payments'] ?? []) as $p) {
            if (($p['source'] ?? '') === 'auto' && ($p['ref'] ?? '') === $txn) {
                return ['ok' => true, 'duplicate' => true, 'receipt' => $p['receipt'] ?? ''];
            }
        }
    }
    $i = store_find_by_control($d, $control);
    if ($i === null) return ['error' => 'Control number haijapatikana.', 'code' => 404];

    $d['students'][$i]['payments'][] = [
        'date' => $date, 'ref' => $txn, 'channel' => $channel, 'amount' => $amount,
        'status' => 'Confirmed', 'receipt' => '', 'source' => 'auto', 'time' => $time,
    ];
    $k = array_key_last($d['students'][$i]['payments']);
    $d['students'][$i]['payments'][$k]['receipt'] = store_next_receipt($d);
    recompute_paid($d['students'][$i]);
    return ['ok' => true, 'duplicate' => false, 'receipt' => $d['students'][$i]['payments'][$k]['receipt'], 'reg_no' => $d['students'][$i]['reg_no']];
});

if (isset($res['error'])) {
    if (($res['code'] ?? 0) === 404) {
        // Malipo ambayo hayana mwenyewe — yahifadhiwe ili mhasibu ayafuatilie.
        @file_put_contents(__DIR__ . '/data/payments_unmatched.log',
            json_encode(['at' => date('c'), 'control_number' => $control, 'amount' => $amount, 'txn_id' => $txn, 'channel' => $channel]) . "\n",
            FILE_APPEND | LOCK_EX);
    }
    wh_out($res['code'] ?? 500, ['error' => $res['error']]);
}
wh_out(200, ['ok' => true, 'duplicate' => (bool)$res['duplicate'], 'receipt_no' => $res['receipt']]);
