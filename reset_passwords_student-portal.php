<?php
/**
 * reset_passwords_student-portal.php — HUTUMIKA MARA MOJA (command line): inaweka nenosiri la KILA mwanafunzi
 * liwe namba yake ya usajili, na kutengeneza akaunti kwa wasio nayo.
 *   php reset_passwords_student-portal.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('SP_NO_SESSION', true);
require_once __DIR__ . '/student_store_student-portal.php';
foreach (store_load()['students'] as $s) {
    $err = sp_sync_student_user($s['reg_no'], $s['email'], $s['reg_no']);
    $q = db()->prepare('SELECT username FROM users WHERE reg_no = ?'); $q->execute([$s['reg_no']]);
    printf("  %-16s kuingia: %-30s nenosiri: %s%s\n", $s['reg_no'], $q->fetchColumn() ?: '-', $s['reg_no'], $err ? "  (KOSA: $err)" : '');
}
