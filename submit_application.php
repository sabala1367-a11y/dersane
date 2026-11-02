<?php
require 'config-admission-system.php';
$u = require_user($pdo);
if ($u['role'] !== 'user') json_out(false, 'Akaunti ya msimamizi haitumi maombi.');

$in = json_decode(file_get_contents('php://input'), true) ?? [];
$s  = function (string $k, int $max = 150) use ($in): string {
    return mb_substr(trim((string)($in[$k] ?? '')), 0, $max);
};

foreach (['fn','ln','sex','dob','nat','tel','mail','reg','dist','ecn','ecr','ect','cs','ci','cy','course','college','lvl','fund'] as $k) {
    if ($s($k) === '') json_out(false, 'Jaza sehemu zote zinazohitajika.');
}
$phone = '/^(\+255|0)[67][0-9]{8}$/';
$index = '/^S[0-9]{4}\/[0-9]{4}\/[0-9]{4}$/i';
if (!in_array($s('sex'), ['Mwanamke', 'Mwanaume'], true))             json_out(false, 'Jinsia si sahihi.');
if (!in_array($s('lvl'), ['Cheti', 'Stashahada', 'Shahada'], true))   json_out(false, 'Ngazi ya masomo si sahihi.');
if (!filter_var($s('mail'), FILTER_VALIDATE_EMAIL))                   json_out(false, 'Barua pepe si sahihi.');
if (!preg_match($phone, $s('tel')) || !preg_match($phone, $s('ect'))) json_out(false, 'Namba ya simu si sahihi.');
if (!preg_match($index, $s('ci')))                                    json_out(false, 'Namba ya mtihani wa kidato cha nne si sahihi.');
if ($s('ai') !== '' && !preg_match($index, $s('ai')))                 json_out(false, 'Namba ya mtihani wa kidato cha sita si sahihi.');

$d = DateTime::createFromFormat('Y-m-d', $s('dob'));
$now = new DateTime();
if (!$d || $d->format('Y-m-d') !== $s('dob') || $d > $now || $d->diff($now)->y < 16 || $d->diff($now)->y > 80) {
    json_out(false, 'Tarehe ya kuzaliwa si sahihi (umri wa chini ni miaka 16).');
}

$subs = []; $seen = [];
for ($i = 0; $i < 4; $i++) {
    $sub = $s("sub$i", 60); $gr = $s("gr$i", 1);
    if ($sub === '' || !in_array($gr, ['A','B','C','D','F'], true) || isset($seen[$sub])) {
        json_out(false, 'Weka masomo manne tofauti yenye madaraja.');
    }
    $seen[$sub] = 1; $subs[] = "$sub: $gr";
}

$sn  = $s('sn') === 'Ndiyo' ? ($s('snText', 255) ?: 'Ndiyo') : null;
$row = [
    'first_name' => $s('fn', 60), 'middle_name' => $s('mn', 60) ?: null, 'last_name' => $s('ln', 60),
    'gender' => $s('sex', 20), 'dob' => $s('dob', 10), 'nationality' => $s('nat', 40), 'special_needs' => $sn,
    'phone' => $s('tel', 20), 'email' => strtolower($s('mail')), 'region' => $s('reg', 60), 'district' => $s('dist', 80),
    'emergency_name' => $s('ecn', 100), 'emergency_relation' => $s('ecr', 30), 'emergency_phone' => $s('ect', 20),
    'csee_school' => $s('cs'), 'csee_index' => strtoupper($s('ci', 25)), 'csee_year' => (int)$s('cy', 4),
    'csee_subjects' => implode(', ', $subs),
    'acsee_school' => $s('as') ?: null, 'acsee_index' => $s('ai', 25) !== '' ? strtoupper($s('ai', 25)) : null,
    'acsee_combination' => $s('ac', 20) ?: null,
    'course' => $s('course'), 'college' => $s('college'), 'study_level' => $s('lvl', 30), 'funding' => $s('fund', 60),
    'source' => $s('src', 60) ?: null,
];
$cols = array_keys($row);

try {
    $q = $pdo->prepare('SELECT status FROM applications WHERE user_id = ?');
    $q->execute([$u['id']]);
    $existing = $q->fetchColumn();

    if ($existing === false) {
        $st = $pdo->prepare('INSERT INTO applications (user_id,' . implode(',', $cols) . ') VALUES (?,' . implode(',', array_fill(0, count($cols), '?')) . ')');
        $st->execute(array_merge([$u['id']], array_values($row)));
    } elseif ($existing === 'rejected') {   // aliyekataliwa anaruhusiwa kutuma upya
        $set = implode(',', array_map(function ($c) { return "$c = ?"; }, $cols));
        $st = $pdo->prepare("UPDATE applications SET $set, status='pending', admission_no=NULL, house_location=NULL, reject_reason=NULL, decided_at=NULL, decided_by=NULL, submitted_at=NOW() WHERE user_id = ?");
        $st->execute(array_merge(array_values($row), [$u['id']]));
    } else {
        json_out(false, 'Tayari umetuma maombi.');
    }
    json_out(true, 'Maombi yametumwa.');
} catch (PDOException $e) {
    http_response_code(500);
    json_out(false, 'Imeshindikana kuhifadhi maombi. Jaribu tena.');
}
