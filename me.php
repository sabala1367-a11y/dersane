<?php
// Hali ya mtumiaji aliyeingia na ombi lake (kama lipo). Hutumiwa na fomu na dashboard ya mtumiaji.
require 'config-admission-system.php';
$u = require_user($pdo);
$q = $pdo->prepare('SELECT * FROM applications WHERE user_id = ?');
$q->execute([$u['id']]);
$a = $q->fetch();
$app = null;
if ($a) {
    $app = [
        'status'       => $a['status'],                       // pending | admitted | rejected
        'submitted'    => substr($a['submitted_at'], 0, 10),
        'admission_no' => $a['admission_no'],
        'house'        => $a['house_location'] ?: $a['region'],
        'reason'       => $a['reject_reason'],
        'name'         => trim(implode(' ', array_filter([$a['first_name'], $a['middle_name'], $a['last_name']]))),
        'exam'         => $a['csee_index'],
        'sex'          => $a['gender'],
        'dob'          => date('d/m/Y', strtotime($a['dob'])),
        'nat'          => $a['nationality'],
        'lvl'          => $a['study_level'],
        'reg'          => $a['region'],
        'dist'         => $a['district'],
        'tel'          => $a['phone'],
        'mail'         => $a['email'],
    ];
}
json_out(true, 'OK', [
    'user'        => ['id' => (int)$u['id'], 'name' => $u['full_name'], 'email' => $u['email'], 'role' => $u['role']],
    'application' => $app,
]);
