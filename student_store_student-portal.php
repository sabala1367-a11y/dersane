<?php
/**
 * student_store_student-portal.php — hifadhi moja ya data (MySQL: database `db-student_portal`) inayotumiwa na:
 *   - Student_Portal_student-portal.php  (kusoma)
 *   - admin_dashboard_student-portal.php / api_student-portal.php  (kusoma + kubadilisha)
 *   - payment_webhook_student-portal.php (malipo ya otomatiki)
 *
 * Kazi zote za zamani (store_load, store_update, store_find ...) zimebaki na majina yale yale,
 * lakini sasa zinasoma/zinaandika MySQL badala ya data/students_student-portal.json. Kwa hiyo dashboard na
 * student portal zinaendelea kufanya kazi kama zamani, zikiwa zimeunganishwa kupitia database moja.
 */

// Kinga: faili hili likipakiwa mara ya pili halifanyi chochote.
if (!defined('STUDENT_STORE_LOADED')) {
define('STUDENT_STORE_LOADED', true);
require_once __DIR__ . '/config_student-portal.php';

/** Ramani ya kumbukumbu: reg_no => hash ya data wakati ilipopakiwa (ili tuandike wale waliobadilika tu). */
function store_snapshot(?array $set = null): array {
    static $snap = [];
    if ($set !== null) $snap = $set;
    return $snap;
}
function store_hash(array $s): string { return md5(json_encode($s, JSON_UNESCAPED_UNICODE)); }
function n2e($v): string { return $v === null ? '' : (string)$v; }       // NULL -> ''
function e2n($v): ?string { $v = (string)$v; return $v === '' ? null : $v; } // '' -> NULL

/** Soma data yote kutoka MySQL, kwa umbo lile lile la JSON ya zamani. */
function store_load(): array {
    $pdo = db();
    $students = [];
    foreach ($pdo->query('SELECT * FROM students ORDER BY id') as $r) {
        $students[$r['reg_no']] = [
            'reg_no' => $r['reg_no'], 'full_name' => $r['full_name'], 'display_name' => $r['display_name'],
            'status' => $r['status'], 'house' => $r['house'], 'supervisor' => $r['supervisor'],
            'year' => $r['year_level'], 'admitted' => $r['admitted'], 'gender' => $r['gender'],
            'university' => $r['university'], 'region' => $r['region'], 'dob' => n2e($r['dob']),
            'address' => $r['address'], 'email' => $r['email'], 'phone' => $r['phone'], 'photo' => $r['photo'],
            'kin_name' => $r['kin_name'], 'kin_rel' => $r['kin_rel'], 'kin_phone' => $r['kin_phone'],
            'plan' => ['meals' => (int)$r['plan_meals'], 'accom' => (int)$r['plan_accom']],
            'fees' => [
                'year' => $r['fee_year'], 'due_date' => n2e($r['due_date']), 'control_number' => n2e($r['control_number']),
                'mobile_money' => $r['mobile_money'], 'banks' => $r['banks'],
                'meals_billed' => (int)$r['meals_billed'], 'meals_paid' => 0,
                'accom_billed' => (int)$r['accom_billed'], 'accom_paid' => 0,
            ],
            'payments' => [], 'quizzes' => [],
            'initials' => $r['initials'],
        ];
    }
    foreach ($pdo->query('SELECT * FROM payments ORDER BY id') as $r) {
        if (!isset($students[$r['reg_no']])) continue;
        $students[$r['reg_no']]['payments'][] = [
            'date' => n2e($r['pay_date']), 'ref' => $r['ref'], 'channel' => $r['channel'], 'amount' => (int)$r['amount'],
            'status' => $r['status'], 'receipt' => n2e($r['receipt']), 'source' => $r['source'], 'time' => $r['pay_time'],
        ];
    }
    foreach ($pdo->query('SELECT * FROM quizzes ORDER BY id') as $r) {
        if (!isset($students[$r['reg_no']])) continue;
        $students[$r['reg_no']]['quizzes'][] = [
            'course' => $r['course'], 'title' => $r['title'], 'date' => n2e($r['quiz_date']),
            'score' => $r['score'] === null ? null : (int)$r['score'], 'out_of' => (int)$r['out_of'],
        ];
    }
    $out = ['students' => array_values($students)];
    $st = $pdo->prepare("SELECT v FROM settings WHERE k = 'fees'");
    $st->execute();
    $j = $st->fetchColumn();
    if ($j !== false && is_array($f = json_decode((string)$j, true))) $out['settings']['fees'] = $f;

    $snap = [];
    foreach ($out['students'] as $k => $s) {
        recompute_paid($s);                       // kilicholipwa kinahesabiwa kutoka kwenye malipo yaliyothibitishwa
        $out['students'][$k] = $s;
        $snap[$s['reg_no']] = store_hash($s);
    }
    store_snapshot($snap);
    return $out;
}

/**
 * Kiasi kilicholipwa = jumla ya malipo yote "Confirmed", kinagawiwa: chakula kwanza, kisha malazi,
 * kila kimoja hadi kiasi chake cha kudaiwa.
 */
function recompute_paid(array &$s): void {
    $recv = 0;
    foreach (($s['payments'] ?? []) as $p) {
        if (is_array($p) && ($p['status'] ?? '') === 'Confirmed') $recv += (int)($p['amount'] ?? 0);
    }
    $mb = (int)($s['fees']['meals_billed'] ?? 0);
    $ab = (int)($s['fees']['accom_billed'] ?? 0);
    $m = min($recv, $mb);
    $a = min($recv - $m, $ab);
    $s['fees']['meals_paid'] = $m;
    $s['fees']['accom_paid'] = $a;
}

/**
 * Soma upya + badilisha + andika ndani ya lock moja + transaction (hakuna malipo yanayopotea kama
 * admin na webhook wanaandika kwa wakati mmoja). $fn(array &$db) inarudisha array;
 * ikiwa na 'error' (na 'code') hakuna kinachoandikwa.
 */
function store_update(callable $fn): array {
    $pdo = db();
    $got = (int)$pdo->query("SELECT GET_LOCK('db-student_portal:store', 15)")->fetchColumn();
    if ($got !== 1) return ['error' => 'Mfumo una shughuli nyingi. Jaribu tena.', 'code' => 503];
    try {
        $db = store_load();
        $res = $fn($db);
        if (!is_array($res)) $res = ['error' => 'Hitilafu ya ndani.', 'code' => 500];
        if (!isset($res['error']) && !store_write_unlocked($db)) {
            $res = ['error' => 'Imeshindwa kuhifadhi kwenye database.', 'code' => 500];
        }
    } catch (Throwable $e) {
        error_log('[student_portal] store_update: ' . $e->getMessage());
        $res = ['error' => 'Imeshindwa kuhifadhi kwenye database.', 'code' => 500];
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('db-student_portal:store')");
    }
    return $res;
}

/** Mpango wa chakula/malazi unaotumika kwa wanafunzi WOTE (mpango wa semester + kiasi cha kudaiwa). */
function fee_defaults(array $db): array {
    $d = ['meals' => 200000, 'accom' => 100000, 'meals_billed' => 0, 'accom_billed' => 0];
    $src = $db['settings']['fees'] ?? null;
    if (!is_array($src) && !empty($db['students'][0]) && is_array($db['students'][0])) $src = fee_values($db['students'][0]);
    if (is_array($src)) foreach ($d as $k => $_) if (isset($src[$k])) $d[$k] = (int)$src[$k];
    return $d;
}
function fee_values(array $st): array {
    return [
        'meals'        => (int)($st['plan']['meals'] ?? 0),
        'accom'        => (int)($st['plan']['accom'] ?? 0),
        'meals_billed' => (int)($st['fees']['meals_billed'] ?? 0),
        'accom_billed' => (int)($st['fees']['accom_billed'] ?? 0),
    ];
}
function fee_apply(array &$st, array $vals): void {
    if (isset($vals['meals']))        $st['plan']['meals'] = (int)$vals['meals'];
    if (isset($vals['accom']))        $st['plan']['accom'] = (int)$vals['accom'];
    if (isset($vals['meals_billed'])) $st['fees']['meals_billed'] = (int)$vals['meals_billed'];
    if (isset($vals['accom_billed'])) $st['fees']['accom_billed'] = (int)$vals['accom_billed'];
}

/** Namba ya risiti inayofuata: RCT/<mwaka>/<NNNNNN> (ya kipekee kwa mfumo mzima). */
function store_next_receipt(array $db, ?int $year = null): string {
    $year = $year ?? (int)(new DateTime('now', new DateTimeZone('Africa/Dar_es_Salaam')))->format('Y');
    $max = 0;
    foreach ($db['students'] as $st) {
        foreach (($st['payments'] ?? []) as $p) {
            if (preg_match('#^RCT/' . $year . '/(\d+)$#', (string)($p['receipt'] ?? ''), $m)) $max = max($max, (int)$m[1]);
        }
    }
    return sprintf('RCT/%d/%06d', $year, $max + 1);
}

/** Tafuta mwanafunzi kwa control number (tarakimu tu zinalinganishwa). */
function store_find_by_control(array $db, string $cn): ?int {
    $d = preg_replace('/\D/', '', $cn);
    if ($d === '') return null;
    foreach ($db['students'] as $i => $st) {
        if (preg_replace('/\D/', '', (string)($st['fees']['control_number'] ?? '')) === $d) return $i;
    }
    return null;
}

/** Inaandika data kwenye MySQL (mwenye kuiita ndiye anashika lock). Ndani ya transaction moja. */
function store_write_unlocked(array $db): bool {
    $pdo = db();
    $snap = store_snapshot();
    try {
        $pdo->beginTransaction();
        $keep = [];
        $up = $pdo->prepare(
            'INSERT INTO students (reg_no, full_name, display_name, initials, status, house, supervisor, year_level, admitted, gender,
               university, region, dob, address, email, phone, photo, kin_name, kin_rel, kin_phone,
               plan_meals, plan_accom, fee_year, due_date, control_number, mobile_money, banks, meals_billed, accom_billed)
             VALUES (:reg_no,:full_name,:display_name,:initials,:status,:house,:supervisor,:year_level,:admitted,:gender,
               :university,:region,:dob,:address,:email,:phone,:photo,:kin_name,:kin_rel,:kin_phone,
               :plan_meals,:plan_accom,:fee_year,:due_date,:control_number,:mobile_money,:banks,:meals_billed,:accom_billed)
             ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), display_name=VALUES(display_name), initials=VALUES(initials),
               status=VALUES(status), house=VALUES(house), supervisor=VALUES(supervisor), year_level=VALUES(year_level),
               admitted=VALUES(admitted), gender=VALUES(gender), university=VALUES(university), region=VALUES(region),
               dob=VALUES(dob), address=VALUES(address), email=VALUES(email), phone=VALUES(phone), photo=VALUES(photo),
               kin_name=VALUES(kin_name), kin_rel=VALUES(kin_rel), kin_phone=VALUES(kin_phone),
               plan_meals=VALUES(plan_meals), plan_accom=VALUES(plan_accom), fee_year=VALUES(fee_year), due_date=VALUES(due_date),
               control_number=VALUES(control_number), mobile_money=VALUES(mobile_money), banks=VALUES(banks),
               meals_billed=VALUES(meals_billed), accom_billed=VALUES(accom_billed)'
        );
        $delP = $pdo->prepare('DELETE FROM payments WHERE reg_no = ?');
        $insP = $pdo->prepare('INSERT INTO payments (reg_no, pay_date, pay_time, ref, channel, amount, status, receipt, source) VALUES (?,?,?,?,?,?,?,?,?)');
        $delQ = $pdo->prepare('DELETE FROM quizzes WHERE reg_no = ?');
        $insQ = $pdo->prepare('INSERT INTO quizzes (reg_no, course, title, quiz_date, score, out_of) VALUES (?,?,?,?,?,?)');

        foreach ($db['students'] as $s) {
            $reg = (string)$s['reg_no'];
            $keep[] = $reg;
            if (isset($snap[$reg]) && $snap[$reg] === store_hash($s)) continue;    // hakubadilika
            $f = $s['fees']; $p = $s['plan'];
            $up->execute([
                'reg_no' => $reg, 'full_name' => $s['full_name'], 'display_name' => $s['display_name'], 'initials' => $s['initials'] ?? '',
                'status' => $s['status'], 'house' => $s['house'], 'supervisor' => $s['supervisor'], 'year_level' => $s['year'],
                'admitted' => $s['admitted'], 'gender' => $s['gender'], 'university' => $s['university'], 'region' => $s['region'],
                'dob' => e2n($s['dob']), 'address' => $s['address'], 'email' => $s['email'], 'phone' => $s['phone'], 'photo' => $s['photo'],
                'kin_name' => $s['kin_name'], 'kin_rel' => $s['kin_rel'], 'kin_phone' => $s['kin_phone'],
                'plan_meals' => (int)$p['meals'], 'plan_accom' => (int)$p['accom'], 'fee_year' => $f['year'], 'due_date' => e2n($f['due_date']),
                'control_number' => e2n($f['control_number']), 'mobile_money' => $f['mobile_money'], 'banks' => $f['banks'],
                'meals_billed' => (int)$f['meals_billed'], 'accom_billed' => (int)$f['accom_billed'],
            ]);
            $delP->execute([$reg]);
            foreach ($s['payments'] as $r) {
                $insP->execute([$reg, e2n($r['date'] ?? ''), (string)($r['time'] ?? ''), (string)($r['ref'] ?? ''), (string)($r['channel'] ?? ''),
                    (int)($r['amount'] ?? 0), $r['status'] ?? 'Confirmed', e2n($r['receipt'] ?? ''), ($r['source'] ?? '') === 'auto' ? 'auto' : 'manual']);
            }
            $delQ->execute([$reg]);
            foreach ($s['quizzes'] as $r) {
                $insQ->execute([$reg, (string)($r['course'] ?? ''), (string)($r['title'] ?? ''), e2n($r['date'] ?? ''),
                    ($r['score'] ?? null) === null ? null : (int)$r['score'], max(1, (int)($r['out_of'] ?? 20))]);
            }
        }
        // Waliofutwa kwenye $db wanafutwa pia kwenye database (payments/quizzes/users zinafutika kwa CASCADE).
        $existing = $pdo->query('SELECT reg_no FROM students')->fetchAll(PDO::FETCH_COLUMN);
        $del = $pdo->prepare('DELETE FROM students WHERE reg_no = ?');
        foreach (array_diff($existing, $keep) as $gone) $del->execute([$gone]);

        if (isset($db['settings']['fees'])) {
            $pdo->prepare("INSERT INTO settings (k, v) VALUES ('fees', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)")
                ->execute([json_encode($db['settings']['fees'])]);
        }
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[student_portal] store_write: ' . $e->getMessage());
        return false;
    }
}

function store_save(array $db): bool {
    $r = store_update(function (array &$d) use ($db) { $d = $db; return ['ok' => true]; });
    return isset($r['ok']);
}

/** Inatoa namba inayofuata: DSN/<mwaka>/<NNNN>. Mfuatano unaanza 0001 kila mwaka mpya. */
function store_next_reg(array $db, ?int $year = null): string {
    $year = $year ?? (int)(new DateTime('now', new DateTimeZone('Africa/Dar_es_Salaam')))->format('Y');
    $max = 0;
    foreach ($db['students'] as $s) {
        if (preg_match('#^DSN/' . $year . '/(\d+)$#i', (string)($s['reg_no'] ?? ''), $m)) $max = max($max, (int)$m[1]);
    }
    return sprintf('DSN/%d/%04d', $year, $max + 1);
}

/**
 * Inaongeza mwanafunzi mpya kwa usalama: inashika lock, inatengeneza namba ya usajili na kuhifadhi
 * vyote ndani ya lock moja. Inarudisha mwanafunzi mwenye namba, au null ikishindwa.
 */
function store_add_student(array $s): ?array {
    $r = store_update(function (array &$d) use ($s) {
        $s['reg_no'] = store_next_reg($d);
        $d['students'][] = $s;
        return ['ok' => true, 'student' => $s];
    });
    return $r['student'] ?? null;
}

function store_find(array $db, string $reg): ?int {
    foreach ($db['students'] as $i => $s) {
        if (strcasecmp($s['reg_no'] ?? '', $reg) === 0) return $i;
    }
    return null;
}

/** Mwanafunzi aliyeingia (kutoka session ya login_student-portal.php). Hakuna mwanafunzi wa "default": asiyeingia anapata null. */
function store_current_student(): ?array {
    if (empty($_SESSION['reg_no']) || !is_string($_SESSION['reg_no'])) return null;
    $db = store_load();
    $i = store_find($db, $_SESSION['reg_no']);
    return $i === null ? null : $db['students'][$i];
}

/**
 * Admin kuingia kwenye akaunti ya mwanafunzi — BILA kugusa session ya mwanafunzi.
 * Dashboard inatoa kiungo chenye token iliyosainiwa (reg + muda + HMAC kwa CSRF ya admin).
 * Token inakubaliwa tu pale session ina is_admin, na inaisha baada ya saa 2.
 * Mwanafunzi halisi hana token hii, kwa hiyo haoni chochote cha admin.
 */
function admin_view_token(string $reg): string {
    $p = $reg . '|' . (time() + 2 * 3600);
    return rtrim(strtr(base64_encode($p . '|' . hash_hmac('sha256', $p, (string)$_SESSION['csrf'])), '+/', '-_'), '=');
}
function admin_view_student(): ?array {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['is_admin']) || empty($_SESSION['csrf']) || empty($_GET['as']) || !is_string($_GET['as'])) return null;
    $raw = base64_decode(strtr($_GET['as'], '-_', '+/'), true);
    if ($raw === false) return null;
    $parts = explode('|', $raw);
    if (count($parts) < 3) return null;
    $sig = array_pop($parts); $until = array_pop($parts); $reg = implode('|', $parts);
    if (!hash_equals(hash_hmac('sha256', $reg . '|' . $until, (string)$_SESSION['csrf']), $sig)) return null;
    if ((int)$until < time()) return null;
    $db = store_load();
    $i = store_find($db, $reg);
    return $i === null ? null : $db['students'][$i];
}

/* ---------- Usafishaji wa data ---------- */
function s_str($v, int $max = 200): string {
    return mb_substr(trim(strip_tags((string)$v)), 0, $max);
}
function s_int($v): int {
    return min(999999999, max(0, (int)preg_replace('/[^0-9]/', '', (string)$v)));
}
function s_date($v): string {
    $v = (string)$v;
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return ($d && $d->format('Y-m-d') === $v) ? $v : '';
}
/** URL ya picha ya mwanafunzi (au '' kama hana). Picha zinahifadhiwa kwenye uploads/photos/. */
function student_photo_url(array $s): string {
    $p = $s['photo'] ?? '';
    return ($p !== '' && is_file(__DIR__ . '/uploads/photos/' . $p)) ? 'uploads/photos/' . $p : '';
}
function make_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $a = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1));
    $b = mb_strtoupper(mb_substr(count($parts) > 1 ? end($parts) : '', 0, 1));
    return $a . $b ?: '?';
}

function normalize_student(array $in): array {
    $f = $in['fees'] ?? [];
    $p = $in['plan'] ?? [];
    $s = [
        'reg_no'       => s_str($in['reg_no'] ?? '', 40),
        'full_name'    => s_str($in['full_name'] ?? '', 120),
        'display_name' => s_str($in['display_name'] ?? '', 80),
        'status'       => s_str($in['status'] ?? 'Active house member', 60),
        'house'        => s_str($in['house'] ?? '', 60),
        'supervisor'   => s_str($in['supervisor'] ?? '', 80),
        'year'         => s_str($in['year'] ?? '', 20),
        'admitted'     => s_str($in['admitted'] ?? '', 20),
        'gender'       => s_str($in['gender'] ?? '', 20),
        'university'   => s_str($in['university'] ?? '', 100),
        'region'       => s_str($in['region'] ?? '', 60),
        'dob'          => s_date($in['dob'] ?? ''),
        'address'      => s_str($in['address'] ?? '', 120),
        'email'        => filter_var($in['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '',
        'phone'        => s_str($in['phone'] ?? '', 30),
        'photo'        => preg_match('/^[a-f0-9]{16,40}\.jpg$/', (string)($in['photo'] ?? '')) ? $in['photo'] : '',
        'kin_name'     => s_str($in['kin_name'] ?? '', 80),
        'kin_rel'      => s_str($in['kin_rel'] ?? '', 40),
        'kin_phone'    => s_str($in['kin_phone'] ?? '', 30),
        'plan' => [
            'meals' => s_int($p['meals'] ?? 0),
            'accom' => s_int($p['accom'] ?? 0),
        ],
        'fees' => [
            'year'           => s_str($f['year'] ?? date('Y'), 10),
            'due_date'       => s_date($f['due_date'] ?? ''),
            'control_number' => s_str($f['control_number'] ?? '', 40),
            'mobile_money'   => s_str($f['mobile_money'] ?? '', 120),
            'banks'          => s_str($f['banks'] ?? '', 120),
            'meals_billed'   => s_int($f['meals_billed'] ?? 0),
            'meals_paid'     => s_int($f['meals_paid'] ?? 0),
            'accom_billed'   => s_int($f['accom_billed'] ?? 0),
            'accom_paid'     => s_int($f['accom_paid'] ?? 0),
        ],
        'payments' => [],
        'quizzes'  => [],
    ];
    if ($s['display_name'] === '') $s['display_name'] = ucwords(strtolower($s['full_name']));
    $s['initials'] = make_initials($s['display_name'] ?: $s['full_name']);

    foreach (array_slice((array)($in['payments'] ?? []), 0, 200) as $r) {
        if (!is_array($r)) continue;
        $s['payments'][] = [
            'date'    => s_date($r['date'] ?? ''),
            'ref'     => s_str($r['ref'] ?? '', 40),
            'channel' => s_str($r['channel'] ?? '', 40),
            'amount'  => s_int($r['amount'] ?? 0),
            'status'  => in_array($r['status'] ?? '', ['Confirmed', 'Pending'], true) ? $r['status'] : 'Confirmed',
            'receipt' => s_str($r['receipt'] ?? '', 30),
            'source'  => (($r['source'] ?? '') === 'auto') ? 'auto' : 'manual',
            'time'    => s_str($r['time'] ?? '', 8),
        ];
    }
    foreach (array_slice((array)($in['quizzes'] ?? []), 0, 200) as $r) {
        if (!is_array($r)) continue;
        $score = $r['score'] ?? null;
        $s['quizzes'][] = [
            'course' => s_str($r['course'] ?? '', 20),
            'title'  => s_str($r['title'] ?? '', 80),
            'date'   => s_date($r['date'] ?? ''),
            'score'  => ($score === null || $score === '') ? null : s_int($score),
            'out_of' => max(1, s_int($r['out_of'] ?? 20)),
        ];
    }
    return $s;
}

/** Hesabu zinazotokana na data (jumla, asilimia, wastani n.k.) */
function student_derived(array $s): array {
    $f = $s['fees'];
    $billed = $f['meals_billed'] + $f['accom_billed'];
    $paid = $f['meals_paid'] + $f['accom_paid'];
    $received = 0;
    foreach ($s['payments'] as $p) if ($p['status'] === 'Confirmed') $received += $p['amount'];

    $taken = array_values(array_filter($s['quizzes'], fn($q) => $q['score'] !== null));
    $upcoming = array_values(array_filter($s['quizzes'], fn($q) => $q['score'] === null));
    $avg = null;
    if ($taken) {
        $avg = (int)round(array_sum(array_map(fn($q) => $q['score'] / $q['out_of'] * 100, $taken)) / count($taken));
    }
    usort($taken, fn($a, $b) => strcmp($b['date'], $a['date']));
    $next = null;
    foreach ($upcoming as $q) if ($q['date'] !== '' && ($next === null || $q['date'] < $next)) $next = $q['date'];

    return [
        'billed' => $billed, 'paid' => $paid, 'outstanding' => max(0, $billed - $paid),
        'pct' => $billed > 0 ? (int)round($paid / $billed * 100) : 0,
        'received' => $received,
        'quiz_avg' => $avg, 'quiz_taken' => count($taken), 'quiz_upcoming' => count($upcoming),
        'quiz_current' => $taken[0] ?? null, 'quiz_next' => $next,
    ];
}

} // end STUDENT_STORE_LOADED guard
