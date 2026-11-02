<?php
/**
 * import_json_student-portal.php — HUTUMIKA MARA MOJA (command line): inahamisha wanafunzi kutoka data/students_student-portal.json
 * kwenda MySQL (`db-student_portal`) na kutengeneza akaunti zao za kuingia.
 *
 *   php import_json_student-portal.php   -> kila mwanafunzi anapewa nenosiri = namba yake ya usajili
 *
 * Hakikisha umeingiza database/schema_student-portal.sql kwanza. Inaweza kuendeshwa tena bila kudhuru (wenye namba ile ile wanasasishwa).
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('SP_NO_SESSION', true);
require_once __DIR__ . '/student_store_student-portal.php';

$file = __DIR__ . '/data/students_student-portal.json';
if (!is_file($file)) { fwrite(STDERR, "Faili $file halipo.\n"); exit(1); }
$j = json_decode((string)file_get_contents($file), true);
if (!is_array($j) || !isset($j['students'])) { fwrite(STDERR, "students_student-portal.json si sahihi.\n"); exit(1); }

$res = store_update(function (array &$d) use ($j) {
    foreach ($j['students'] as $in) {
        $s = normalize_student($in);
        if ($s['reg_no'] === '') $s['reg_no'] = store_next_reg($d);
        // Malipo & quiz zinahamishwa kama zilivyo (normalize_student tayari inazisafisha).
        $i = store_find($d, $s['reg_no']);
        if ($i === null) $d['students'][] = $s; else $d['students'][$i] = $s;
        foreach ($s['payments'] as $k => $p) {
            $idx = $i ?? array_key_last($d['students']);
            if ($p['status'] === 'Confirmed' && $p['receipt'] === '') $d['students'][$idx]['payments'][$k]['receipt'] = store_next_receipt($d);
        }
    }
    if (!empty($j['settings']['fees'])) $d['settings']['fees'] = $j['settings']['fees'];
    return ['ok' => true];
});
if (isset($res['error'])) { fwrite(STDERR, $res['error'] . "\n"); exit(1); }

echo "Wanafunzi wamehamishwa. Akaunti za kuingia:\n";
foreach ($j['students'] as $in) {
    $s = normalize_student($in);
    $pass = $s['reg_no'];   // nenosiri = namba ya usajili
    $err = sp_sync_student_user($s['reg_no'], $s['email'], $pass);
    $q = db()->prepare('SELECT username FROM users WHERE reg_no = ?'); $q->execute([$s['reg_no']]);
    printf("  %-14s  kuingia: %-30s  nenosiri: %s%s\n", $s['reg_no'], $q->fetchColumn() ?: '-', $pass, $err ? "  (KOSA: $err)" : '');
}
