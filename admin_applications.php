<?php
// Orodha ya maombi yote kwa admin.
require 'config-admission-system.php';
require_admin($pdo);
$map  = ['pending' => 'p', 'admitted' => 'a', 'rejected' => 'r'];
$rows = $pdo->query('SELECT * FROM applications ORDER BY submitted_at DESC')->fetchAll();
$out  = array_map(function ($a) use ($map) {
    return [
        'dbid' => (int)$a['id'], 'fn' => $a['first_name'], 'ln' => $a['last_name'], 'sex' => $a['gender'], 'dob' => $a['dob'],
        'nat' => $a['nationality'], 'tel' => $a['phone'], 'mail' => $a['email'], 'reg' => $a['region'], 'dist' => $a['district'],
        'course' => $a['course'], 'college' => $a['college'], 'lvl' => $a['study_level'], 'fund' => $a['funding'],
        'cs' => $a['csee_school'], 'ci' => $a['csee_index'], 'sub' => $a['csee_subjects'],
        'date' => substr($a['submitted_at'], 0, 10), 'st' => $map[$a['status']],
        'why' => $a['reject_reason'] ?? '', 'adm' => $a['admission_no'] ?? '', 'house' => $a['house_location'] ?: $a['region'],
    ];
}, $rows);
json_out(true, 'OK', ['applications' => $out]);
