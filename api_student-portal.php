<?php
/**
 * api_student-portal.php — API ya admin dashboard (JSON). Inahitaji kuingia kama admin.
 *   GET  ?action=list
 *   GET  ?action=get&reg=DSN/2026/0001
 *   POST ?action=save    {original_reg?, student:{...}}
 *   POST ?action=impersonate {reg}   (admin anaingia kwenye akaunti ya mwanafunzi)
 *   POST ?action=delete  {reg}
 * POST zote zinahitaji kichwa X-CSRF-Token.
 */
require_once __DIR__ . '/student_store_student-portal.php';   // inapakia config_student-portal.php (MySQL + session)
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(int $code, array $data) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

sp_require_admin(true);   // admin hana login: anaruhusiwa kwa IP (SP_ADMIN_IPS)

$action = $_GET['action'] ?? 'list';
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

$body = [];
if ($isPost) {
    $tok = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $tok)) out(403, ['error' => 'Token si sahihi. Pakia ukurasa upya.']);
    if ($action !== 'upload_photo') {   // upload_photo ni multipart, si JSON
        $body = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($body)) out(400, ['error' => 'Data si sahihi.']);
    }
}

const PHOTO_DIR = __DIR__ . '/uploads/photos';
function photo_delete(string $name): void {
    if (preg_match('/^[a-f0-9]{16,40}\.jpg$/', $name) && is_file(PHOTO_DIR . '/' . $name)) @unlink(PHOTO_DIR . '/' . $name);
}

$db = store_load();

switch ($action) {
    case 'list':
        $rows = [];
        foreach ($db['students'] as $s) {
            $d = student_derived($s);
            $rows[] = [
                'reg_no' => $s['reg_no'], 'display_name' => $s['display_name'], 'initials' => $s['initials'],
                'photo' => student_photo_url($s),
                'house' => $s['house'], 'year' => $s['year'],
                'billed' => $d['billed'], 'paid' => $d['paid'], 'outstanding' => $d['outstanding'], 'pct' => $d['pct'],
            ];
        }
        out(200, ['students' => $rows, 'fee_defaults' => fee_defaults($db)]);

    case 'get':
        $i = store_find($db, (string)($_GET['reg'] ?? ''));
        if ($i === null) out(404, ['error' => 'Mwanafunzi hajapatikana.']);
        $hl = db()->prepare('SELECT 1 FROM users WHERE reg_no = ?'); $hl->execute([$db['students'][$i]['reg_no']]);
        out(200, ['has_login' => (bool)$hl->fetchColumn(), 'student' => $db['students'][$i], 'photo_url' => student_photo_url($db['students'][$i]), 'derived' => student_derived($db['students'][$i])]);

    case 'save':
        if (!$isPost) out(405, ['error' => 'Tumia POST.']);
        $s = normalize_student((array)($body['student'] ?? []));
        if ($s['full_name'] === '') out(422, ['error' => 'Jina kamili linahitajika.']);
        // Nenosiri la kuingia = namba ya usajili (inawekwa otomatiki akaunti inapotengenezwa).
        $loginPass = '';
        $orig = (string)($body['original_reg'] ?? '');
        $feeBase = is_array($body['fee_base'] ?? null) ? $body['fee_base'] : null;
        $res = store_update(function (array &$d) use ($s, $orig, $feeBase) {
            $oi = $orig !== '' ? store_find($d, $orig) : null;
            $old = $oi !== null ? $d['students'][$oi] : null;
            $defaults = fee_defaults($d);

            // Chakula/malazi (mpango + kiasi cha kudaiwa): kilichobadilishwa na admin sasa kinatumika kwa wanafunzi WOTE.
            // "Kilichobadilishwa" = tofauti na thamani alizozikuta alipofungua mwanafunzi (fee_base); vingine vinabaki kama server ilivyo sasa.
            $cur  = $old ? fee_values($old) : $defaults;
            $base = $cur;
            if ($feeBase) foreach (array_keys($cur) as $k) if (isset($feeBase[$k])) $base[$k] = s_int($feeBase[$k]);
            $changed = [];
            foreach (fee_values($s) as $k => $v) if ($v !== $base[$k]) $changed[$k] = $v;
            fee_apply($s, array_diff_key($cur, $changed));

            // Control number lazima iwe ya kipekee (malipo ya otomatiki yanatambulika kwa hiyo).
            if ($s['fees']['control_number'] !== '') {
                $ci = store_find_by_control($d, $s['fees']['control_number']);
                if ($ci !== null && $ci !== $oi) return ['error' => 'Control number hiyo tayari inatumika na mwanafunzi mwingine.', 'code' => 422];
            }

            $s['photo']  = $old['photo'] ?? '';
            $s['reg_no'] = $old ? $old['reg_no'] : store_next_reg($d);   // namba ya usajili: server tu

            // Malipo hayabadilishwi na admin kamwe: tunatumia nakala ya server (iliyotoka benki/mobile money).
            // Chochote kinachotumwa na dashboard kuhusu malipo kinapuuzwa.
            $s['payments'] = $old['payments'] ?? [];

            if ($oi !== null) { $d['students'][$oi] = $s; }
            else { $d['students'][] = $s; $oi = array_key_last($d['students']); }

            // Malipo ya zamani yaliyothibitishwa yasiyo na risiti yanapewa risiti mara moja tu.
            foreach ($d['students'][$oi]['payments'] as $k => $p) {
                if (($p['status'] ?? '') === 'Confirmed' && ($p['receipt'] ?? '') === '') {
                    $d['students'][$oi]['payments'][$k]['receipt'] = store_next_receipt($d);
                }
            }
            if ($changed) {
                $d['settings']['fees'] = array_merge($defaults, $changed);
                foreach (array_keys($d['students']) as $k) {
                    fee_apply($d['students'][$k], $changed);
                    recompute_paid($d['students'][$k]);
                }
            }
            recompute_paid($d['students'][$oi]);
            return ['ok' => true, 'student' => $d['students'][$oi]];
        });
        if (isset($res['error'])) out($res['code'] ?? 500, ['error' => $res['error']]);
        // Akaunti ya kuingia (login_student-portal.php) ya mwanafunzi huyu: tengeneza/sasisha pamoja na barua pepe na nenosiri.
        $uerr = sp_sync_student_user($res['student']['reg_no'], $res['student']['email'], $loginPass);
        $hasLogin = (bool)db()->query('SELECT 1 FROM users WHERE reg_no = ' . db()->quote($res['student']['reg_no']))->fetchColumn();
        out(200, ['ok' => true, 'student' => $res['student'], 'photo_url' => student_photo_url($res['student']), 'derived' => student_derived($res['student']),
            'has_login' => $hasLogin, 'warning' => $uerr]);

    case 'upload_photo':
        if (!$isPost) out(405, ['error' => 'Tumia POST.']);
        $i = store_find($db, (string)($_POST['reg'] ?? ''));
        if ($i === null) out(404, ['error' => 'Hifadhi mwanafunzi kwanza, kisha upakie picha.']);
        $f = $_FILES['photo'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
            $big = $f && in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
            out(422, ['error' => $big ? 'Picha ni kubwa mno (angalia upload_max_filesize kwenye php.ini).' : 'Picha haikupokelewa. Jaribu tena.']);
        }
        if ($f['size'] > 8 * 1024 * 1024) out(422, ['error' => 'Picha isizidi 8MB.']);
        if (!is_uploaded_file($f['tmp_name'])) out(400, ['error' => 'Faili si sahihi.']);
        if (!function_exists('imagecreatefromstring')) out(500, ['error' => 'Extension ya GD haijawezeshwa kwenye PHP (php.ini: extension=gd).']);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) out(422, ['error' => 'Tumia picha ya JPG, PNG au WEBP.']);
        $info = @getimagesize($f['tmp_name']);
        if (!$info || $info[0] < 50 || $info[1] < 50 || $info[0] > 9000 || $info[1] > 9000) out(422, ['error' => 'Picha ni ndogo mno au kubwa mno.']);
        $img = @imagecreatefromstring((string)file_get_contents($f['tmp_name']));
        if (!$img) out(422, ['error' => 'Picha haikuweza kusomwa.']);
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {   // sahihisha mzunguko wa simu
            $ex = @exif_read_data($f['tmp_name']);
            $rot = [3 => 180, 6 => -90, 8 => 90][$ex['Orientation'] ?? 0] ?? 0;
            if ($rot) { $r = imagerotate($img, $rot, 0); if ($r) { imagedestroy($img); $img = $r; } }
        }
        // Kata mraba katikati, punguza hadi 400x400, hifadhi upya kama JPEG (inaondoa data yoyote iliyofichwa)
        $w = imagesx($img); $h = imagesy($img); $side = min($w, $h);
        $out = imagecreatetruecolor(400, 400);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $img, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), 400, 400, $side, $side);
        if (!is_dir(PHOTO_DIR) && !mkdir(PHOTO_DIR, 0755, true)) out(500, ['error' => 'Imeshindwa kutengeneza folda ya uploads/photos.']);
        $name = bin2hex(random_bytes(12)) . '.jpg';
        if (!imagejpeg($out, PHOTO_DIR . '/' . $name, 85)) out(500, ['error' => 'Imeshindwa kuhifadhi picha.']);
        imagedestroy($img); imagedestroy($out);
        $regKey = $db['students'][$i]['reg_no'];
        $res = store_update(function (array &$d) use ($regKey, $name) {
            $k = store_find($d, $regKey);
            if ($k === null) return ['error' => 'Mwanafunzi hajapatikana.', 'code' => 404];
            photo_delete((string)($d['students'][$k]['photo'] ?? ''));
            $d['students'][$k]['photo'] = $name;
            return ['ok' => true, 'student' => $d['students'][$k]];
        });
        if (isset($res['error'])) { photo_delete($name); out($res['code'] ?? 500, ['error' => $res['error']]); }
        out(200, ['ok' => true, 'photo_url' => student_photo_url($res['student'])]);

    case 'remove_photo':
        if (!$isPost) out(405, ['error' => 'Tumia POST.']);
        $i = store_find($db, (string)($body['reg'] ?? ''));
        if ($i === null) out(404, ['error' => 'Mwanafunzi hajapatikana.']);
        $regKey = $db['students'][$i]['reg_no'];
        $res = store_update(function (array &$d) use ($regKey) {
            $k = store_find($d, $regKey);
            if ($k === null) return ['error' => 'Mwanafunzi hajapatikana.', 'code' => 404];
            photo_delete((string)($d['students'][$k]['photo'] ?? ''));
            $d['students'][$k]['photo'] = '';
            return ['ok' => true];
        });
        if (isset($res['error'])) out($res['code'] ?? 500, ['error' => $res['error']]);
        out(200, ['ok' => true]);

    case 'impersonate':
        // Admin anaingia kwenye akaunti ya mwanafunzi (anaiona kama mwanafunzi mwenyewe).
        if (!$isPost) out(405, ['error' => 'Tumia POST.']);
        $i = store_find($db, (string)($body['reg'] ?? ''));
        if ($i === null) out(404, ['error' => 'Mwanafunzi hajapatikana. Hifadhi kwanza.']);
        // Hatugusi session ya mwanafunzi: tunatoa kiungo chenye token ya muda mfupi.
        out(200, ['ok' => true, 'portal' => 'Student_Portal_student-portal.php?as=' . admin_view_token($db['students'][$i]['reg_no'])]);

    case 'delete':
        if (!$isPost) out(405, ['error' => 'Tumia POST.']);
        $i = store_find($db, (string)($body['reg'] ?? ''));
        if ($i === null) out(404, ['error' => 'Mwanafunzi hajapatikana.']);
        $regKey = $db['students'][$i]['reg_no'];
        $res = store_update(function (array &$d) use ($regKey) {
            $k = store_find($d, $regKey);
            if ($k === null) return ['error' => 'Mwanafunzi hajapatikana.', 'code' => 404];
            if (!empty($d['students'][$k]['payments'])) return ['error' => 'Mwanafunzi mwenye historia ya malipo hawezi kufutwa (risiti zinahifadhiwa).', 'code' => 422];
            photo_delete((string)($d['students'][$k]['photo'] ?? ''));
            array_splice($d['students'], $k, 1);
            return ['ok' => true];
        });
        if (isset($res['error'])) out($res['code'] ?? 500, ['error' => $res['error']]);
        out(200, ['ok' => true]);

    default:
        out(400, ['error' => 'Action haijulikani.']);
}
