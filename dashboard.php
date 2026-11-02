<?php
/*
 * Dersane Gallery - Dashboard + MySQL (bila login)
 *
 * HATUA ZA KUANZA:
 *  1. Tengeneza database (tumia database.sql kwenye phpMyAdmin) - au acha, itatengenezwa yenyewe kama MySQL user ana ruhusa.
 *  2. Badilisha DB_HOST / DB_NAME / DB_USER / DB_PASS hapa chini.
 *  3. Fungua dashboard.php kupitia http://localhost/... (si kwa kubonyeza mara mbili faili).
 *
 * - Picha zenyewe (faili) zinakaa kwenye folda ya uploads/ ; majina na taarifa zake zinakaa MySQL (jedwali "photos").
 * - gallery.html inasoma orodha kupitia dashboard.php?list=1
 * - Kama kuna uploads/photos.json ya toleo la zamani, picha zake zinahamishiwa MySQL mara moja (kisha faili hubadilishwa jina kuwa photos.json.imported).
 *
 * LATEST UPDATES vs ALL MEDIA:
 *   Picha mpya huingia "Latest Updates". Baada ya LATEST_DAYS siku huhamia zenyewe "All Media" (za zamani).
 */
require __DIR__ . '/admin-user-config.php';    // session ya login

const DB_HOST = 'localhost';
const DB_NAME = 'dersane_gallery';
const DB_USER = 'root';
const DB_PASS = '';

const LATEST_DAYS = 7;              // picha mpya ziwe Latest kwa siku ngapi
const TIMEZONE    = 'Africa/Nairobi';

const MAX_FILE = 8 * 1024 * 1024;   // 8MB kwa picha
const EXTS = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];

const DDL = <<<'SQL'
CREATE TABLE IF NOT EXISTS photos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  filename VARCHAR(255) NOT NULL,
  title VARCHAR(255) NOT NULL,
  size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
  is_latest TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  latest_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_filename (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

/* Announcements (zinaonekana kwenye index page) - database yake tofauti: Announcement_db, jedwali: news */
const ANN_DB_HOST = 'localhost';
const ANN_DB_NAME = 'Announcement_db';
const ANN_DB_USER = 'root';
const ANN_DB_PASS = '';

const DDL_ANN = <<<'SQL'
CREATE TABLE IF NOT EXISTS news (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  body VARCHAR(1000) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

date_default_timezone_set(TIMEZONE);
$UP = __DIR__ . '/uploads';

function out(array $d, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function ini_bytes($v): int {
    $v = trim((string)$v);
    if ($v === '' || (int)$v < 0) return PHP_INT_MAX;
    $n = (float)$v;
    switch (strtolower(substr($v, -1))) { case 'g': $n *= 1024; case 'm': $n *= 1024; case 'k': $n *= 1024; }
    return (int)$n;
}
function cut(string $s, int $n): string {
    return function_exists('mb_substr') ? mb_substr($s, 0, $n) : substr($s, 0, $n);
}
function ts(int $t): string { return date('Y-m-d H:i:s', $t); }

/* Ujumbe wa error unaoeleweka (bila kutoa siri za database) */
function friendly(Throwable $e): string {
    $m = $e->getMessage();
    if (stripos($m, 'could not find driver') !== false) return 'The PHP MySQL driver (pdo_mysql) is not enabled. Enable extension=pdo_mysql in php.ini.';
    if (preg_match('/\[1045\]/', $m)) return 'MySQL login failed. Check DB_USER and DB_PASS at the top of dashboard.php.';
    if (preg_match('/\[(2002|2003|2006)\]/', $m)) return 'Cannot reach MySQL. Start MySQL (XAMPP Control Panel) or check DB_HOST.';
    if (preg_match('/\[1044\]/', $m)) return 'The MySQL user has no permission on this database. Create the database with database.sql.';
    if (preg_match('/\[1049\]/', $m)) return 'Database not found. Create it with database.sql (phpMyAdmin) or check DB_NAME.';
    if ($e instanceof PDOException) return 'Database error. Check the MySQL settings at the top of dashboard.php.';
    return $m;
}

/* ---------- MySQL ---------- */
function connect(): PDO {
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    try {
        return new PDO($dsn . ';dbname=' . DB_NAME, DB_USER, DB_PASS, $opts);
    } catch (PDOException $e) {
        if (!preg_match('/\[1049\]/', $e->getMessage())) throw $e;       // database haipo -> jaribu kuitengeneza
        $c = new PDO($dsn, DB_USER, DB_PASS, $opts);
        $c->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        return new PDO($dsn . ';dbname=' . DB_NAME, DB_USER, DB_PASS, $opts);
    }
}
function db(): PDO {
    static $pdo = null;
    global $UP;
    if ($pdo) return $pdo;
    if (!is_dir($UP) && !@mkdir($UP, 0755, true)) throw new RuntimeException('Cannot create the uploads folder. Check folder permissions.');
    $pdo = connect();
    $pdo->exec(DDL);
    import_json($pdo);
    return $pdo;
}

/* Hamisha picha za toleo la zamani (uploads/photos.json) kwenda MySQL - mara moja tu */
function import_json(PDO $pdo): void {
    global $UP;
    $j = "$UP/photos.json";
    if (!is_file($j)) return;
    $old = json_decode((string)@file_get_contents($j), true);
    if (is_array($old)) {
        $chk = $pdo->prepare('SELECT 1 FROM photos WHERE filename = ?');
        $ins = $pdo->prepare('INSERT INTO photos (filename, title, size_bytes, is_latest, created_at, latest_at) VALUES (?,?,?,?,?,?)');
        foreach (array_reverse($old) as $p) {                      // za zamani kwanza, ili id ziwe kwa mpangilio
            if (!is_array($p) || empty($p['file'])) continue;
            $f = basename((string)$p['file']);
            if (!is_file("$UP/$f")) continue;
            $chk->execute([$f]);
            if ($chk->fetch()) continue;
            $t = (int)($p['time'] ?? 0) ?: time();
            $recent = !empty($p['recent']);
            try {
                $ins->execute([$f, cut((string)($p['title'] ?? $f), 255), (int)($p['size'] ?? filesize("$UP/$f")), $recent ? 1 : 0, ts($t), $recent ? ts($t) : null]);
            } catch (PDOException $e) { /* tayari ipo */ }
        }
    }
    @rename($j, $j . '.imported');
}

/* Picha zote, mpya kwanza. Zilizozidi LATEST_DAYS huhamia All Media zenyewe. */
function all_photos(): array {
    global $UP;
    $pdo = db();
    $pdo->prepare('UPDATE photos SET is_latest = 0 WHERE is_latest = 1 AND COALESCE(latest_at, created_at) < ?')
        ->execute([ts(time() - LATEST_DAYS * 86400)]);
    $rows = $pdo->query('SELECT * FROM photos ORDER BY COALESCE(latest_at, created_at) DESC, id DESC')->fetchAll();
    $list = [];
    foreach ($rows as $r) {
        if (!is_file("$UP/" . basename($r['filename']))) continue;      // faili halipo -> usionyeshe
        $list[] = [
            'id' => (string)$r['id'], 'title' => $r['title'], 'url' => 'uploads/' . rawurlencode($r['filename']),
            'recent' => (bool)$r['is_latest'], 'size' => (int)$r['size_bytes'],
            'time' => (int)strtotime($r['latest_at'] ?: $r['created_at']),
        ];
    }
    return $list;
}
function limits(): array {
    return ['file' => min(MAX_FILE, ini_bytes(ini_get('upload_max_filesize'))), 'post' => ini_bytes(ini_get('post_max_size')),
            'days' => LATEST_DAYS, 'now' => time()];
}
function reply(array $extra = [], int $code = 200): void {
    out(array_merge(['ok' => true, 'photos' => all_photos(), 'limits' => limits()], $extra), $code);
}

/* ---------- Announcements ---------- */
/* Muunganisho wa Announcement_db (database ya gallery haiguswi) */
function ann_db(): PDO {
    static $a = null;
    if ($a) return $a;
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    $dsn = 'mysql:host=' . ANN_DB_HOST . ';charset=utf8mb4';
    try {
        $a = new PDO($dsn . ';dbname=' . ANN_DB_NAME, ANN_DB_USER, ANN_DB_PASS, $opts);
    } catch (PDOException $e) {
        if (!preg_match('/\[1049\]/', $e->getMessage())) throw $e;       // database haipo -> jaribu kuitengeneza
        $c = new PDO($dsn, ANN_DB_USER, ANN_DB_PASS, $opts);
        $c->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', ANN_DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $a = new PDO($dsn . ';dbname=' . ANN_DB_NAME, ANN_DB_USER, ANN_DB_PASS, $opts);
    }
    ann_init($a);
    return $a;
}
/* Jedwali "news" likitengenezwa mara ya kwanza, linapewa announcements 4 za sasa za index page */
function ann_init(PDO $pdo): void {
    $exists = $pdo->query("SHOW TABLES LIKE 'news'")->fetch();
    $pdo->exec(DDL_ANN);
    if ($exists) return;
    $seed = [
        'Karibu Dersane House.',
        'Upokeaji wa wanafunzi mwaka wa kwanza wenye house za dersane bado unaendelea.',
        'Mchakato wa kupanga wanafunzi kwenye nyumba zao husika bado unaendelea.',
        'Utaratibu wa kuchagua ma-imam na wasaidizi wao bado unaendelea.',
    ];
    $ins = $pdo->prepare('INSERT INTO news (body, created_at) VALUES (?, ?)');
    foreach ($seed as $b) $ins->execute([$b, ts(time())]);
}
function ann_list(): array {
    $rows = ann_db()->query('SELECT id, body FROM news ORDER BY id ASC')->fetchAll();
    return array_map(function ($r) { return ['id' => (string)$r['id'], 'body' => $r['body']]; }, $rows);
}

/* ---------- Contact messages (kutoka contact.html) - MySQL ---------- */
/* Ujumbe unahifadhiwa kwenye jedwali "contact_messages" ndani ya database ya dersane_gallery (angalia contact_messages.sql).
   Jedwali linatengenezwa lenyewe mara ya kwanza. Ujumbe wa zamani (contact_messages.php) unahamishiwa MySQL mara moja. */
const MSG_FILE  = __DIR__ . '/contact_messages.php';   // faili la zamani (la kuhamisha tu)
const MAIL_FROM = 'sabala1367@gmail.com';              // anwani ya kutuma majibu (From / Reply-To)

/* SMTP ya kutuma majibu ya email (Gmail). Weka "App Password" ya herufi 16 hapa chini:
   Google Account > Security > 2-Step Verification (iwashe) > App passwords. Usitumie password ya kawaida ya Gmail. */
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 465;                                 // SSL
const SMTP_USER = 'sabala1367@gmail.com';
const SMTP_PASS = 'mvqpcmwhsyknmaog';                                  // <-- WEKA APP PASSWORD HAPA
const SMTP_VERIFY_TLS = true;                          // kama XAMPP inalalamika kuhusu certificate, weka false

const DDL_MSG = <<<'SQL'
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  body TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

/* ---- Kutuma email kwa SMTP (bila library ya nje) ---- */
function smtp_read($fp): string {
    $data = '';
    while (($line = fgets($fp, 515)) !== false) {
        $data .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') break;
    }
    return $data;
}
function smtp_cmd($fp, string $cmd, array $ok): string {
    if ($cmd !== '') fwrite($fp, $cmd . "\r\n");
    $r = smtp_read($fp);
    if (!in_array((int)substr($r, 0, 3), $ok, true)) throw new RuntimeException('SMTP: ' . trim($r));
    return $r;
}
function smtp_send(string $to, string $subject, string $body): void {
    $ctx = stream_context_create(['ssl' => [
        'verify_peer' => SMTP_VERIFY_TLS, 'verify_peer_name' => SMTP_VERIFY_TLS, 'allow_self_signed' => !SMTP_VERIFY_TLS,
    ]]);
    $fp = @stream_socket_client('ssl://' . SMTP_HOST . ':' . SMTP_PORT, $en, $es, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) throw new RuntimeException('Cannot connect to the mail server (' . $es . ').');
    stream_set_timeout($fp, 20);
    try {
        smtp_cmd($fp, '', [220]);
        smtp_cmd($fp, 'EHLO localhost', [250]);
        smtp_cmd($fp, 'AUTH LOGIN', [334]);
        smtp_cmd($fp, base64_encode(SMTP_USER), [334]);
        smtp_cmd($fp, base64_encode(SMTP_PASS), [235]);
        smtp_cmd($fp, 'MAIL FROM:<' . MAIL_FROM . '>', [250]);
        smtp_cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtp_cmd($fp, 'DATA', [354]);
        $h = 'From: ' . MAIL_FROM . "\r\n" .
             'To: ' . $to . "\r\n" .
             'Reply-To: ' . MAIL_FROM . "\r\n" .
             'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n" .
             'Date: ' . date('r') . "\r\n" .
             'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . SMTP_HOST . ">\r\n" .
             "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        fwrite($fp, $h . chunk_split(base64_encode($body)) . ".\r\n");
        smtp_cmd($fp, '', [250]);
        @fwrite($fp, "QUIT\r\n");
    } finally {
        fclose($fp);
    }
}
/* SMTP ikiwa imewekwa; vinginevyo mail() ya PHP */
function send_mail(string $to, string $subject, string $body): void {
    if (SMTP_PASS !== '') { smtp_send($to, $subject, $body); return; }
    $hdr = 'From: ' . MAIL_FROM . "\r\nReply-To: " . MAIL_FROM . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8";
    if (!@mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $hdr)) {
        throw new RuntimeException('SMTP_PASS is empty in dashboard.php and PHP mail() is not configured.');
    }
}

/* ---- MySQL ya ujumbe ---- */
function msg_db(): PDO {
    static $m = null;
    if ($m) return $m;
    $m = connect();
    $m->exec(DDL_MSG);
    msg_import_file($m);
    return $m;
}
function msg_import_file(PDO $pdo): void {
    if (!is_file(MSG_FILE)) return;
    $ins = $pdo->prepare('INSERT INTO contact_messages (name, email, phone, body, is_read, created_at) VALUES (?,?,?,?,?,?)');
    foreach (msg_decode((string)@file_get_contents(MSG_FILE)) as $o) {
        if (!is_array($o)) continue;
        $ins->execute([cut((string)($o['name'] ?? ''), 120), cut((string)($o['email'] ?? ''), 190), cut((string)($o['phone'] ?? ''), 40),
                       (string)($o['body'] ?? ''), !empty($o['read']) ? 1 : 0, (string)($o['time'] ?? '') ?: ts(time())]);
    }
    @rename(MSG_FILE, MSG_FILE . '.imported');
}
function msg_add(string $name, string $email, string $phone, string $body): void {
    msg_db()->prepare('INSERT INTO contact_messages (name, email, phone, body, is_read, created_at) VALUES (?,?,?,?,0,?)')
            ->execute([$name, $email, $phone, $body, ts(time())]);
}
function msg_set_read(int $id, bool $read): array {
    msg_db()->prepare('UPDATE contact_messages SET is_read = ? WHERE id = ?')->execute([$read ? 1 : 0, $id]);
    return msg_load();
}
function msg_remove(int $id): array {
    msg_db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
    return msg_load();
}

function msg_decode(string $raw): array {
    $p = strpos($raw, "?>\n");
    $a = json_decode($p === false ? '' : substr($raw, $p + 3), true);
    return is_array($a) ? $a : [];
}
/* Ujumbe wote, wa zamani kwanza (msg_payload inazigeuza ziwe mpya kwanza) */
function msg_load(): array {
    $rows = msg_db()->query('SELECT id, name, email, phone, body, is_read, created_at FROM contact_messages ORDER BY id ASC')->fetchAll();
    return array_map(function ($r) {
        return ['id' => (int)$r['id'], 'name' => $r['name'], 'email' => $r['email'], 'phone' => $r['phone'],
                'body' => $r['body'], 'read' => (bool)$r['is_read'], 'time' => $r['created_at']];
    }, $rows);
}
function msg_payload(array $l): array {
    $l = array_reverse($l);                                   // mpya kwanza
    $unread = 0;
    foreach ($l as $m) if (empty($m['read'])) $unread++;
    return ['messages' => array_map(function ($m) {
        return ['id' => (string)$m['id'], 'name' => (string)$m['name'], 'email' => (string)$m['email'], 'phone' => (string)($m['phone'] ?? ''),
                'body' => (string)$m['body'], 'read' => !empty($m['read']), 'time' => (string)$m['time']];
    }, array_slice($l, 0, 200)), 'unread' => $unread];
}

/* ---------- API ---------- */
if (isset($_GET['list']) || isset($_GET['action']) || isset($_GET['announcements'])) {
    ini_set('display_errors', '0');
    try {
        if (isset($_GET['announcements'])) out(['ok' => true, 'announcements' => ann_list()]);
        if (isset($_GET['list'])) reply();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') {
            out(['ok' => false, 'error' => 'Invalid request.'], 400);
        }
        if ($_GET['action'] === 'contact_send') {      // ya wazi (bila login): fomu ya contact.html
            $cname  = trim(cut((string)($_POST['name'] ?? ''), 120));
            $cemail = trim(cut((string)($_POST['email'] ?? ''), 190));
            $cphone = trim(cut((string)($_POST['phone'] ?? ''), 40));
            $cmsg   = trim(cut((string)($_POST['message'] ?? ''), 2000));
            if ($cname === '' || $cmsg === '') out(['ok' => false, 'error' => 'Please fill in your name and message.'], 422);
            if (!filter_var($cemail, FILTER_VALIDATE_EMAIL)) out(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
            if (time() - (int)($_SESSION['msg_last'] ?? 0) < 15) out(['ok' => false, 'error' => 'Please wait a few seconds before sending another message.'], 429);
            try {
                msg_add($cname, $cemail, $cphone, $cmsg);
            } catch (Throwable $e) {
                out(['ok' => false, 'error' => 'Your message could not be sent. Please try again later.'], 500);
            }
            $_SESSION['msg_last'] = time();
            out(['ok' => true]);
        }
        if (empty($_SESSION['user_id'])) {      // vitendo vya admin (upload/rename/delete/ann_*) vinahitaji login
            out(['ok' => false, 'error' => 'Session expired. Please log in again.'], 401);
        }
        $action = $_GET['action'];
        if (strpos($action, 'msg_') === 0) {          // ujumbe wa contact: bila database
            $mid = (int)($_POST['id'] ?? 0);
            if ($action === 'msg_list') {
                $ml = msg_load();
            } elseif ($action === 'msg_read') {
                $ml = msg_set_read($mid, !empty($_POST['read']));
            } elseif ($action === 'msg_reply') {          // tuma jibu kwa email ya aliyetuma ujumbe
                $rbody = trim(cut((string)($_POST['body'] ?? ''), 5000));
                $rsubj = trim(str_replace(["\r", "\n"], ' ', cut((string)($_POST['subject'] ?? ''), 200)));
                if ($rbody === '') out(['ok' => false, 'error' => 'Write your reply first.'], 422);
                if ($rsubj === '') $rsubj = 'Re: your message';
                $to = null;
                foreach (msg_load() as $m) if ((int)$m['id'] === $mid) $to = $m;
                if (!$to || !filter_var((string)$to['email'], FILTER_VALIDATE_EMAIL)) out(['ok' => false, 'error' => 'Message or email address not found.'], 404);
                try {
                    send_mail((string)$to['email'], $rsubj, $rbody);
                } catch (Throwable $e) {
                    error_log('Reply email failed: ' . $e->getMessage());
                    out(['ok' => false, 'error' => 'The email could not be sent. ' . cut($e->getMessage(), 180)], 500);
                }
                $ml = msg_set_read($mid, true);      // ujumbe uliojibiwa umekuwa "read"
            } elseif ($action === 'msg_delete') {
                $ml = msg_remove($mid);
            } else {
                out(['ok' => false, 'error' => 'Invalid request.'], 400);
            }
            out(array_merge(['ok' => true], msg_payload($ml)));
        }
        $pdo = db();

        if ($action === 'upload') {
            $f = $_FILES['files'] ?? null;
            if (!$f) {
                $cl = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
                out(['ok' => false, 'error' => $cl > ini_bytes(ini_get('post_max_size'))
                    ? 'Upload is larger than the server limit (post_max_size = ' . ini_get('post_max_size') . ').'
                    : 'No files received.'], 413);
            }
            $names = (array)$f['name'];
            $title = trim(cut((string)($_POST['title'] ?? ''), 200));
            $recent = !empty($_POST['recent']);
            $ins = $pdo->prepare('INSERT INTO photos (filename, title, size_bytes, is_latest, created_at, latest_at) VALUES (?,?,?,?,?,?)');
            $errors = []; $added = 0;
            foreach ($names as $i => $orig) {
                $err = (array)$f['error'];
                if ($err[$i] === UPLOAD_ERR_INI_SIZE || $err[$i] === UPLOAD_ERR_FORM_SIZE) { $errors[] = "$orig: larger than the server limit (upload_max_filesize = " . ini_get('upload_max_filesize') . ')'; continue; }
                if ($err[$i] !== UPLOAD_ERR_OK) { $errors[] = "$orig: upload failed."; continue; }
                $tmp = ((array)$f['tmp_name'])[$i]; $size = (int)((array)$f['size'])[$i];
                if ($size > MAX_FILE) { $errors[] = "$orig: larger than 8 MB."; continue; }
                $info = @getimagesize($tmp);
                $ext = EXTS[$info[2] ?? 0] ?? null;
                if (!$ext) { $errors[] = "$orig: not a valid image (JPG, PNG, WEBP or GIF)."; continue; }
                $file = bin2hex(random_bytes(10)) . '.' . $ext;
                if (!move_uploaded_file($tmp, "$UP/$file")) { $errors[] = "$orig: could not be saved."; continue; }
                $t = $title !== '' ? (count($names) > 1 ? $title . ' ' . ($added + 1) : $title)
                                   : trim(str_replace(['_', '-'], ' ', pathinfo($orig, PATHINFO_FILENAME)));
                $now = time();
                try {
                    $ins->execute([$file, cut($t !== '' ? $t : 'Untitled', 255), $size, $recent ? 1 : 0, ts($now), $recent ? ts($now) : null]);
                    $added++;
                } catch (Throwable $e) { @unlink("$UP/$file"); $errors[] = "$orig: could not be saved to the database."; }
            }
            reply(['ok' => $added > 0, 'added' => $added, 'error' => implode(' ', $errors) ?: null], $added ? 200 : 422);
        }

        if (strpos($action, 'ann_') === 0) {
            $aid = (int)($_POST['id'] ?? 0);
            $body = trim(cut((string)($_POST['body'] ?? ''), 1000));
            if ($action === 'ann_add') {
                if ($body === '') out(['ok' => false, 'error' => 'Announcement cannot be empty.'], 422);
                ann_db()->prepare('INSERT INTO news (body, created_at) VALUES (?, ?)')->execute([$body, ts(time())]);
            } elseif ($action === 'ann_edit') {
                if ($body === '') out(['ok' => false, 'error' => 'Announcement cannot be empty.'], 422);
                ann_db()->prepare('UPDATE news SET body = ? WHERE id = ?')->execute([$body, $aid]);
            } elseif ($action === 'ann_delete') {
                ann_db()->prepare('DELETE FROM news WHERE id = ?')->execute([$aid]);
            } else {
                out(['ok' => false, 'error' => 'Invalid request.'], 400);
            }
            out(['ok' => true, 'announcements' => ann_list()]);
        }

        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT * FROM photos WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) out(['ok' => false, 'error' => 'Photo not found.'], 404);

        if ($action === 'rename') {
            $t = trim(cut((string)($_POST['title'] ?? ''), 255));
            if ($t === '') out(['ok' => false, 'error' => 'Title cannot be empty.'], 422);
            $pdo->prepare('UPDATE photos SET title = ? WHERE id = ?')->execute([$t, $id]);
        } elseif ($action === 'recent') {
            if (!empty($_POST['recent'])) $pdo->prepare('UPDATE photos SET is_latest = 1, latest_at = ? WHERE id = ?')->execute([ts(time()), $id]);
            else $pdo->prepare('UPDATE photos SET is_latest = 0 WHERE id = ?')->execute([$id]);
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM photos WHERE id = ?')->execute([$id]);
            @unlink("$UP/" . basename($row['filename']));
        } else {
            out(['ok' => false, 'error' => 'Invalid request.'], 400);
        }
        reply();
    } catch (Throwable $e) {
        out(['ok' => false, 'error' => friendly($e)], 500);
    }
}
/* Ukurasa wa dashboard: asiyeingia apelekwe admin-login.php */
if (empty($_SESSION['user_id'])) {
    header('Location: admin-login.php');
    exit;
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dersane Gallery - Media Dashboard</title>
<style>
  :root {
    --side:#262d35; --side-2:#323a44; --side-fg:#aab3be; --blue:#2f6fdb; --blue-d:#2459b5;
    --bg:#f3f6fa; --card:#fff; --ink:#18202a; --muted:#6b7684; --line:#dde3ea; --ph:#d9dde2; --red:#c9372c; --gold:#f5b800;
    --font: system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
    --ease: cubic-bezier(.2,.8,.2,1); --spring: cubic-bezier(.3,1.5,.5,1);
  }
  * { box-sizing:border-box; }
  body { margin:0; background:var(--bg); color:var(--ink); font:15px/1.4 var(--font); }
  button, input, select { font:inherit; color:inherit; }
  button { cursor:pointer; }
  :focus-visible { outline:3px solid rgba(47,111,219,.45); outline-offset:2px; }
  .app { display:grid; grid-template-columns:240px 1fr; min-height:100vh; }

  /* ---------- Sidebar ---------- */
  aside { background:var(--side); color:var(--side-fg); padding:26px 14px; display:flex; flex-direction:column; position:sticky; top:0; height:100vh; }
  .brand { color:#fff; font-size:21px; font-weight:800; letter-spacing:.2px; padding:0 12px 26px; }
  .brand span { font-weight:400; color:#c3cad3; }
  nav { display:grid; gap:4px; }
  nav a, nav button { display:flex; align-items:center; gap:12px; padding:11px 12px; border-radius:10px; color:var(--side-fg); background:none; border:0; text-align:left; text-decoration:none; width:100%;
    transition:background .2s, color .2s, transform .15s var(--ease); }
  nav a svg, nav button svg { transition:transform .3s var(--spring); }
  nav a:hover, nav button:hover { background:var(--side-2); color:#fff; transform:translateX(3px); }
  nav a:hover svg, nav button:hover svg { transform:scale(1.18) rotate(-4deg); }
  nav a:active, nav button:active { transform:scale(.97); }
  nav .on { background:var(--side-2); color:#fff; }
  .stat { margin-top:auto; padding:14px 12px; border-top:1px solid #38414c; font-size:13px; }
  .stat b { display:block; color:#fff; font-size:18px; }

  /* ---------- Main / toolbar ---------- */
  main { padding:26px clamp(16px,3vw,32px) 56px; min-width:0; }
  .bar { display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-bottom:24px; animation:fadeDown .5s var(--ease) backwards; }
  h1 { margin:0 auto 0 0; font-size:25px; font-weight:800; letter-spacing:.3px; }
  .search { position:relative; flex:0 1 250px; }
  .search svg { position:absolute; left:12px; top:50%; translate:0 -50%; color:var(--muted); transition:color .2s, transform .3s var(--spring); }
  .search:focus-within svg { color:var(--blue); transform:scale(1.15); }
  .search input { width:100%; padding:10px 12px 10px 40px; border:1px solid var(--line); border-radius:9px; background:#fff; transition:border-color .2s, box-shadow .25s; }
  .search input:focus, select:focus { outline:none; border-color:var(--blue); box-shadow:0 0 0 4px rgba(47,111,219,.15); }
  select { padding:10px 12px; border:1px solid var(--line); border-radius:9px; background:#fff; transition:border-color .2s, box-shadow .25s, transform .15s; }
  select:hover { border-color:#b9c4d2; }

  /* ---------- Buttons (animated) ---------- */
  .btn { position:relative; overflow:hidden; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:0; border-radius:9px; padding:10px 16px; font-weight:600; background:var(--blue); color:#fff;
    transition:background .2s, transform .18s var(--spring), box-shadow .25s; }
  .btn:hover { background:var(--blue-d); transform:translateY(-2px); box-shadow:0 8px 18px rgba(47,111,219,.38); }
  .btn:active { transform:translateY(0) scale(.95); box-shadow:none; }
  .btn svg { transition:transform .35s var(--spring); }
  .btn:hover .plus { transform:rotate(180deg) scale(1.1); }
  .btn.ghost { background:#fff; color:var(--ink); border:1px solid var(--line); }
  .btn.ghost:hover { background:#f0f3f7; box-shadow:0 6px 14px rgba(24,32,42,.12); }
  .btn.red { background:var(--red); } .btn.red:hover { background:#a82b22; box-shadow:0 8px 18px rgba(201,55,44,.38); }
  .btn:disabled { opacity:.65; cursor:progress; transform:none; box-shadow:none; }
  .btn.load::after { content:""; width:14px; height:14px; border-radius:50%; border:2px solid rgba(255,255,255,.4); border-top-color:#fff; animation:spin .7s linear infinite; }
  .rip { position:absolute; border-radius:50%; background:rgba(255,255,255,.5); transform:scale(0); animation:ripple .6s ease-out forwards; pointer-events:none; }
  .ghost .rip { background:rgba(47,111,219,.25); }

  /* ---------- Sections: Latest Updates vs All Media ---------- */
  .sec-wrap { margin-bottom:34px; }
  .sec { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin:0 0 14px; font-size:17px; font-weight:700; animation:fadeUp .45s var(--ease) backwards; }
  .sec em { font-style:normal; font-size:12.5px; font-weight:700; background:#e3eaf4; color:#3b4a5e; padding:2px 9px; border-radius:99px; }
  .sec small { flex-basis:100%; font-size:13px; font-weight:400; color:var(--muted); margin-top:-4px; }
  .sec .ic { width:26px; height:26px; border-radius:8px; display:grid; place-items:center; background:var(--gold); color:#3a2c00; }
  .sec.old .ic { background:#cfd6df; color:#3b4a5e; }
  .sec .ic svg { animation:bolt 2.6s ease-in-out infinite; }
  .sec.old .ic svg { animation:none; }
  .cards { display:grid; gap:22px 18px; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); }

  /* ---------- Photo cards (animated) ---------- */
  .item { min-width:0; }
  .item.in { animation:rise .55s var(--ease) backwards; animation-delay:calc(var(--i,0) * 60ms); }
  .item.out { animation:out .3s ease-in forwards; pointer-events:none; }
  .thumb { display:block; width:100%; aspect-ratio:16/10; border-radius:8px; overflow:hidden; position:relative; border:0; padding:0;
    background:linear-gradient(100deg,#d9dde2 30%,#e9ecf0 50%,#d9dde2 70%) 0 0/220% 100%; animation:shimmer 1.4s linear infinite;
    transition:transform .3s var(--ease), box-shadow .3s var(--ease); }
  .thumb.ld { animation:none; background:var(--ph); }
  .thumb img { width:100%; height:100%; object-fit:cover; display:block; opacity:0; transform:scale(1.04); transition:opacity .5s ease, transform .7s var(--ease); }
  .thumb.ld img { opacity:1; transform:none; }
  .item:hover .thumb { transform:translateY(-5px); box-shadow:0 16px 30px rgba(24,32,42,.24); }
  .item:hover .thumb.ld img { transform:scale(1.08); }
  .item:active .thumb { transform:translateY(-1px) scale(.98); }
  .item.fresh .thumb { animation:glow 1.9s ease-out; }
  .tag { position:absolute; left:8px; top:8px; background:rgba(24,32,42,.78); color:#fff; font-size:11px; font-weight:600; padding:3px 8px; border-radius:99px; backdrop-filter:blur(3px); }
  .info { display:flex; align-items:flex-start; gap:6px; padding:9px 2px 0 4px; position:relative; }
  .txt { min-width:0; flex:1; }
  .name { font-weight:600; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .meta { color:var(--muted); font-size:12.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .dots { border:0; background:none; width:32px; height:32px; border-radius:8px; color:var(--muted); display:grid; place-items:center; flex:none; transition:background .2s, color .2s, transform .2s var(--spring); }
  .dots svg { transition:transform .3s var(--spring); }
  .dots:hover, .dots[aria-expanded="true"] { background:#e2e8ef; color:var(--ink); transform:scale(1.1); }
  .dots:hover svg { transform:scale(1.2); }
  .dots[aria-expanded="true"] svg { transform:rotate(90deg); }
  .dots:active { transform:scale(.9); }
  .menu { position:absolute; right:0; top:40px; z-index:5; min-width:230px; background:#fff; border:1px solid var(--line); border-radius:10px; box-shadow:0 14px 34px rgba(24,32,42,.2); padding:6px; transform-origin:top right; animation:menuIn .2s var(--spring); }
  .menu button { white-space:nowrap; display:block; width:100%; text-align:left; background:none; border:0; padding:9px 10px; border-radius:7px; transition:background .15s, padding-left .2s var(--ease); }
  .menu button:hover { background:#eef2f7; padding-left:15px; } .menu .del { color:var(--red); }
  .empty { text-align:center; padding:70px 20px; color:var(--muted); background:#fff; border:1px dashed var(--line); border-radius:14px; animation:fadeUp .45s var(--ease); }
  .empty strong { display:block; color:var(--ink); font-size:17px; margin-bottom:6px; }

  /* ---------- Dialogs / toast ---------- */
  dialog { border:0; border-radius:16px; padding:0; width:min(480px,calc(100vw - 28px)); box-shadow:0 24px 70px rgba(0,0,0,.3); color:var(--ink); }
  dialog[open] { animation:pop .32s var(--spring); }
  dialog::backdrop { background:rgba(20,26,34,.55); }
  dialog[open]::backdrop { animation:fade .25s ease; }
  .dlg { padding:22px; display:grid; gap:14px; }
  .dlg h2 { margin:0; font-size:19px; }
  .dlg label { display:grid; gap:6px; font-weight:600; font-size:13.5px; }
  .dlg input[type=text] { padding:10px 12px; border:1px solid var(--line); border-radius:9px; width:100%; font-weight:400; transition:border-color .2s, box-shadow .25s; }
  .dlg input[type=text]:focus { outline:none; border-color:var(--blue); box-shadow:0 0 0 4px rgba(47,111,219,.15); }
  .drop { border:2px dashed #b9c4d2; border-radius:12px; padding:26px 14px; text-align:center; color:var(--muted); background:#f8fafc; cursor:pointer; transition:border-color .2s, background .2s, transform .25s var(--spring); }
  .drop:hover { border-color:var(--blue); background:#eef4ff; color:var(--ink); transform:scale(1.015); }
  .drop.over { border-color:var(--blue); background:#e3eeff; color:var(--ink); transform:scale(1.03); animation:pulse 1s ease-in-out infinite; }
  .drop b { color:var(--blue); }
  .chosen { font-size:13px; color:var(--muted); max-height:90px; overflow:auto; }
  .ck { display:flex !important; grid-auto-flow:column; align-items:center; gap:8px; font-weight:500 !important; }
  .acts { display:flex; justify-content:flex-end; gap:10px; margin-top:4px; }
  .msg { color:var(--red); font-size:13.5px; }
  .pv { width:100%; aspect-ratio:16/9; object-fit:cover; border-radius:10px; background:var(--ph); }
  #toast { position:fixed; left:50%; bottom:24px; translate:-50% 24px; background:#18202a; color:#fff; padding:11px 18px; border-radius:10px; z-index:50; opacity:0; pointer-events:none; transition:opacity .25s, translate .35s var(--spring); max-width:calc(100vw - 32px); box-shadow:0 10px 30px rgba(0,0,0,.25); }
  #toast.show { opacity:1; translate:-50% 0; } #toast.bad { background:var(--red); }
  .hide { display:none !important; }

  /* ---------- Keyframes ---------- */
  @keyframes rise { from { opacity:0; transform:translateY(26px) scale(.94); } to { opacity:1; transform:none; } }
  @keyframes out { to { opacity:0; transform:scale(.72) translateY(10px); } }
  @keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
  @keyframes fadeDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:none; } }
  @keyframes fade { from { opacity:0; } to { opacity:1; } }
  @keyframes pop { from { opacity:0; transform:translateY(16px) scale(.93); } to { opacity:1; transform:none; } }
  @keyframes menuIn { from { opacity:0; transform:scale(.85) translateY(-6px); } to { opacity:1; transform:none; } }
  @keyframes shimmer { to { background-position:-220% 0; } }
  @keyframes glow { 0% { box-shadow:0 0 0 0 rgba(47,111,219,.65); } 100% { box-shadow:0 0 0 16px rgba(47,111,219,0); } }
  @keyframes ripple { to { transform:scale(4); opacity:0; } }
  @keyframes spin { to { transform:rotate(360deg); } }
  @keyframes pulse { 50% { box-shadow:0 0 0 8px rgba(47,111,219,.15); } }
  @keyframes bolt { 0%,100% { transform:rotate(0) scale(1); } 40% { transform:rotate(-14deg) scale(1.18); } 70% { transform:rotate(8deg) scale(1.05); } }

  /* ---------- Announcements dialog ---------- */
  #dAnn { width:min(620px,calc(100vw - 28px)); }
  .ann-list { display:grid; gap:10px; max-height:46vh; overflow:auto; padding-right:2px; }
  .ann-row { display:grid; gap:8px; padding:10px; border:1px solid var(--line); border-radius:10px; background:#f8fafc; }
  .ann-row textarea, .ann-new textarea { width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:9px; font:inherit; resize:vertical; background:#fff; }
  .ann-row textarea:focus, .ann-new textarea:focus { outline:none; border-color:var(--blue); box-shadow:0 0 0 4px rgba(47,111,219,.15); }
  .ann-acts { display:flex; justify-content:flex-end; gap:8px; }
  .ann-acts .btn { padding:7px 12px; font-size:13.5px; }
  .ann-new { display:grid; gap:8px; border-top:1px solid var(--line); padding-top:14px; }
  .ann-new label { font-weight:600; font-size:13.5px; }

  /* ---------- Messages (from contact page) ---------- */
  #dMsg { width:min(680px,calc(100vw - 28px)); }
  .cm-badge { margin-left:auto; min-width:20px; padding:1px 7px; border-radius:99px; background:var(--red); color:#fff; font-size:12px; font-weight:700; text-align:center; line-height:1.5; }
  .cm-list { display:grid; gap:10px; max-height:58vh; overflow:auto; padding-right:2px; }
  .cm-row { display:grid; gap:8px; padding:12px 14px; border:1px solid var(--line); border-radius:10px; background:#f8fafc; }
  .cm-row.unread { background:#fff; border-left:4px solid var(--blue); }
  .cm-head { display:flex; flex-wrap:wrap; justify-content:space-between; gap:4px 12px; align-items:baseline; }
  .cm-contact { font-size:13.5px; color:var(--muted); overflow-wrap:anywhere; }
  .cm-contact a { color:var(--blue); text-decoration:none; }
  .cm-contact a:hover { text-decoration:underline; }
  .cm-body { margin:0; white-space:pre-wrap; overflow-wrap:anywhere; line-height:1.5; }
  .cm-acts { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }
  .cm-acts .btn { padding:7px 12px; font-size:13.5px; text-decoration:none; }
  .cm-reply { display:grid; gap:8px; border-top:1px solid var(--line); padding-top:10px; }
  .cm-reply input, .cm-reply textarea { width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:9px; font:inherit; background:#fff; resize:vertical; }
  .cm-reply input:focus, .cm-reply textarea:focus { outline:none; border-color:var(--blue); box-shadow:0 0 0 4px rgba(47,111,219,.15); }

  @media (max-width:820px) {
    .app { grid-template-columns:minmax(0,1fr); }
    aside { position:static; height:auto; padding:12px 14px; display:block; }
    .brand { padding:0 4px 10px; font-size:18px; }
    nav { display:flex; gap:6px; overflow-x:auto; }
    nav a, nav button { width:auto; padding:9px 12px; white-space:nowrap; flex:none; }
    .stat { display:none; }
    .search { flex:1 1 100%; order:5; }
    h1 { font-size:21px; }
  }
  @media (prefers-reduced-motion:reduce) {
    *, *::before, *::after { animation:none !important; transition:none !important; }
    .thumb img { opacity:1; transform:none; }
  }
</style>
</head>
<body>
<div class="app">
  <aside>
    <div class="brand">ADMIN<span> DASHBOURD</span></div>
    <nav aria-label="Dashboard">
      <a class="on" href="dashboard.php" aria-current="page">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="9" cy="10" r="1.8"/><path d="m21 16-5-5-8 9"/></svg>
        Media Library</a>
      <button type="button" id="navUpload">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
        Upload</button>
      <button type="button" id="navAnn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11v2a1 1 0 0 0 1 1h3l5 4V6L7 10H4a1 1 0 0 0-1 1zM16 9a4 4 0 0 1 0 6M19 6a8 8 0 0 1 0 12"/></svg>
        Announcements</button>
      <button type="button" id="navMsg">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
        Messages <span class="cm-badge hide" id="msgBadge" aria-label="Unread messages">0</span></button>
      <a href="Admissions-admin-dashboard.html">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 2 8l10 5 10-5-10-5z"/><path d="M6 10.5V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-5.5"/></svg>
        Admissions</a>
      <a href="gallery.html" target="_blank" rel="noopener">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6m0-6-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
        View Gallery</a>
      <a href="admin_dashboard_student-portal.php">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5a3.5 3.5 0 0 1 0 6.5M18 14.5a6.5 6.5 0 0 1 3.5 5.5"/></svg>
        Student Portal</a>
      <a href="admin-logout.php">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h4M16 8l4 4-4 4M20 12H9"/></svg>
        Logout</a>
    </nav>
    <div class="stat"><b id="statN">0 photos</b><span id="statS">0 KB</span></div>
  </aside>

  <main>
    <div class="bar">
      <h1>MEDIA GALLERY</h1>
      <label class="search">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input id="q" type="search" placeholder="Search images..." autocomplete="off" aria-label="Search images">
      </label>
      <select id="cat" aria-label="Show">
        <option value="all">All Media</option>
        <option value="latest">Latest Updates only</option>
        <option value="older">Older only</option>
      </select>
      <button class="btn" type="button" id="btnUpload">
        <svg class="plus" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Upload New Media</button>
    </div>
    <div id="grid" aria-live="polite"></div>
  </main>
</div>

<!-- Upload -->
<dialog id="dUp">
  <form class="dlg" id="fUp" method="dialog">
    <h2>Upload New Media</h2>
    <div class="drop" id="drop" tabindex="0" role="button" aria-label="Choose photos to upload">
      <b>Click to choose</b> or drag photos here<br><small>JPG, PNG, WEBP or GIF, up to 8 MB each</small>
    </div>
    <input id="file" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="hide">
    <div class="chosen" id="chosen">No photos selected.</div>
    <label>Title / event (optional)
      <input type="text" id="upTitle" maxlength="200" placeholder="Leave empty to use the file name">
    </label>
    <label class="ck"><input type="checkbox" id="upRecent" checked> Put in "Latest Updates" (new photos)</label>
    <div class="msg" id="upMsg" role="alert"></div>
    <div class="acts">
      <button type="button" class="btn ghost" data-close>Cancel</button>
      <button type="submit" class="btn" id="upGo">Upload</button>
    </div>
  </form>
</dialog>

<!-- Rename -->
<dialog id="dRen">
  <form class="dlg" id="fRen" method="dialog">
    <h2>Rename photo</h2>
    <label>Title <input type="text" id="renTitle" maxlength="255" required></label>
    <div class="msg" id="renMsg" role="alert"></div>
    <div class="acts">
      <button type="button" class="btn ghost" data-close>Cancel</button>
      <button type="submit" class="btn" id="renGo">Save</button>
    </div>
  </form>
</dialog>

<!-- Delete -->
<dialog id="dDel">
  <form class="dlg" id="fDel" method="dialog">
    <h2>Delete this photo?</h2>
    <img class="pv" id="delImg" alt="">
    <div><b id="delName"></b><div class="meta" style="white-space:normal">It will be removed from the gallery and the server. This cannot be undone.</div></div>
    <div class="msg" id="delMsg" role="alert"></div>
    <div class="acts">
      <button type="button" class="btn ghost" data-close>Cancel</button>
      <button type="submit" class="btn red" id="delGo">Delete</button>
    </div>
  </form>
</dialog>

<!-- Announcements -->
<dialog id="dAnn">
  <div class="dlg">
    <h2>Announcements</h2>
    <div class="meta" style="white-space:normal">These appear on the home page (index). Edit the text and press Save, or add a new one.</div>
    <div class="ann-list" id="annList" aria-live="polite"></div>
    <div class="ann-new">
      <label for="annNew">New announcement</label>
      <textarea id="annNew" rows="2" maxlength="300" placeholder="Write the announcement here (max 300 characters)..."></textarea>
      <div class="msg" id="annMsg" role="alert"></div>
      <div class="acts">
        <button type="button" class="btn ghost" data-close>Close</button>
        <button type="button" class="btn" id="annAdd">Add announcement</button>
      </div>
    </div>
  </div>
</dialog>

<!-- Messages (from contact page) -->
<dialog id="dMsg">
  <div class="dlg">
    <h2>Messages</h2>
    <div class="meta" style="white-space:normal">Messages sent from the Contact Us page. Newest first.</div>
    <div class="cm-list" id="cmList" aria-live="polite"></div>
    <div class="acts">
      <button type="button" class="btn ghost" data-close>Close</button>
    </div>
  </div>
</dialog>

<div id="toast" role="status" aria-live="polite"></div>

<script>
(function () {
  var $ = function (s) { return document.querySelector(s); };
  var photos = [], limits = { file: 8388608, post: Infinity, days: 7 }, skew = 0;
  var loaded = false, loadErr = "", openMenu = null, target = null, chosen = [], prev = {};
  var grid = $("#grid");
  var BOLT = '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2 4 14h6l-1 8 9-12h-6z"/></svg>';
  var CLOCK = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg>';

  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" }[c]; }); }
  function size(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + " MB" : Math.max(1, Math.round(b / 1024)) + " KB"; }
  var toastT;
  function toast(t, bad) { var e = $("#toast"); e.textContent = t; e.className = "show" + (bad ? " bad" : ""); clearTimeout(toastT); toastT = setTimeout(function () { e.className = ""; }, 3400); }
  function daysLeft(p) {
    var d = Math.ceil((p.time + limits.days * 86400 - (Date.now() / 1000 + skew)) / 86400);
    return d <= 0 ? "moving to All Media soon" : d + (d === 1 ? " day" : " days") + " left";
  }

  /* opts: { mode: "all"|"new"|"none", outId, onOk } */
  function call(action, fd, opts) {
    opts = opts || {};
    return fetch("dashboard.php?action=" + action, { method: "POST", headers: { "X-Requested-With": "fetch" }, body: fd })
      .then(function (r) { return r.json().catch(function () { throw new Error("Server error. Check that PHP is running and the uploads folder is writable."); }); })
      .then(function (j) {
        if (!j.ok && !j.photos) throw new Error(j.error || "Request failed.");
        var go = function () { if (j.photos) apply(j, opts.mode); if (!j.ok) throw new Error(j.error || "Request failed."); return j; };
        if (j.ok && opts.onOk) opts.onOk(j);
        var el = j.ok && opts.outId && grid.querySelector('.item[data-id="' + opts.outId + '"]');
        if (el) { el.classList.remove("in"); el.classList.add("out"); return new Promise(function (r) { setTimeout(r, 290); }).then(go); }
        return go();
      });
  }
  function apply(j, mode) {
    photos = j.photos;
    if (j.limits) { limits = { file: Math.min(8388608, j.limits.file), post: j.limits.post, days: j.limits.days }; skew = j.limits.now - Date.now() / 1000; }
    loaded = true; render(mode || "none");
  }
  function load() {
    fetch("dashboard.php?list=1", { cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (!j.ok) throw new Error(j.error); apply(j, "all"); })
      .catch(function (e) { loaded = true; loadErr = e.message || "Could not load photos."; render("none"); });
  }

  function section(kind, arr, anim) {
    var latest = kind === "latest";
    var head = '<h2 class="sec ' + (latest ? "lat" : "old") + '"><span class="ic">' + (latest ? BOLT : CLOCK) + '</span>' +
      (latest ? "Latest Updates" : "All Media &middot; Older photos") + ' <em>' + arr.length + '</em>' +
      '<small>' + (latest ? "New uploads stay here for " + limits.days + " days, then move to All Media automatically."
                          : "Older photos. They stay in the gallery under \"All Media\".") + '</small></h2>';
    var na = 0;
    var cards = arr.map(function (p, i) {
      var isNew = anim === "new" && (!(p.id in prev) || prev[p.id] !== p.recent);
      var play = anim === "all" || isNew;
      var idx = anim === "all" ? i : na; if (isNew) na++;
      return '<article class="item' + (play ? " in" : "") + (isNew ? " fresh" : "") + '" data-id="' + esc(p.id) + '" style="--i:' + Math.min(idx, 14) + '">' +
        '<a class="thumb" href="' + esc(p.url) + '" target="_blank" rel="noopener" aria-label="Open ' + esc(p.title) + '">' +
        '<img src="' + esc(p.url) + '" alt="' + esc(p.title) + '" loading="lazy" onload="this.parentNode.classList.add(\'ld\')" onerror="this.parentNode.classList.add(\'ld\')">' +
        (p.recent ? '<span class="tag">Latest</span>' : '') + '</a>' +
        '<div class="info"><div class="txt"><div class="name" title="' + esc(p.title) + '">' + esc(p.title) + '</div>' +
        '<div class="meta">' + size(p.size) + (p.recent ? " &middot; " + daysLeft(p) : "") + '</div></div>' +
        '<button class="dots" type="button" data-act="menu" aria-haspopup="true" aria-expanded="false" aria-label="Options for ' + esc(p.title) + '"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg></button></div></article>';
    }).join("");
    return '<section class="sec-wrap">' + head + '<div class="cards">' + cards + '</div></section>';
  }

  function render(mode) {
    var nLatest = photos.filter(function (p) { return p.recent; }).length;
    var total = photos.reduce(function (a, p) { return a + p.size; }, 0);
    $("#statN").textContent = photos.length + (photos.length === 1 ? " photo" : " photos");
    $("#statS").textContent = size(total) + " used · " + nLatest + " latest";
    if (!loaded) { grid.innerHTML = '<div class="empty"><strong>Loading...</strong></div>'; return; }
    if (loadErr && !photos.length) { grid.innerHTML = '<div class="empty"><strong>Could not load photos</strong>' + esc(loadErr) + '<br><br>Make sure MySQL is running, then open this page through http://localhost/... (not by double-clicking the file).</div>'; return; }
    var q = $("#q").value.trim().toLowerCase(), c = $("#cat").value;
    var list = photos.filter(function (p) { return !q || p.title.toLowerCase().indexOf(q) !== -1; });
    var lat = list.filter(function (p) { return p.recent; }), old = list.filter(function (p) { return !p.recent; });
    var html = "";
    if (!photos.length) html = '<div class="empty"><strong>No photos yet</strong>Click "Upload New Media" to add the first one.</div>';
    else if (!list.length) html = '<div class="empty"><strong>No photos match</strong>Try a different search.</div>';
    else {
      if (c !== "older") html += lat.length ? section("latest", lat, mode)
        : (c === "latest" ? '<div class="empty"><strong>No new updates</strong>Photos uploaded in the last ' + limits.days + ' days appear here.</div>' : "");
      if (c !== "latest") html += old.length ? section("older", old, mode)
        : (c === "older" ? '<div class="empty"><strong>No older photos yet</strong>Photos move here automatically after ' + limits.days + ' days.</div>' : "");
    }
    grid.innerHTML = html;
    prev = {}; photos.forEach(function (p) { prev[p.id] = p.recent; });
    openMenu = null;
  }

  function find(id) { return photos.filter(function (p) { return p.id === id; })[0]; }
  function closeMenu() { if (openMenu) { var b = openMenu.parentNode.querySelector(".dots"); if (b) b.setAttribute("aria-expanded", "false"); openMenu.remove(); openMenu = null; } }

  grid.addEventListener("click", function (e) {
    var b = e.target.closest("[data-act]"); if (!b) return;
    var item = b.closest(".item"), id = item.dataset.id, p = find(id), act = b.dataset.act;
    if (act === "menu") {
      var had = openMenu && openMenu.parentNode === item.querySelector(".info");
      closeMenu(); if (had) return;
      var m = document.createElement("div"); m.className = "menu"; m.setAttribute("role", "menu");
      m.innerHTML = '<button type="button" role="menuitem" data-act="rename">Rename</button>' +
        '<button type="button" role="menuitem" data-act="recent">' + (p.recent ? 'Move to All Media (older)' : 'Move to Latest Updates') + '</button>' +
        '<button type="button" role="menuitem" class="del" data-act="delete">Delete</button>';
      item.querySelector(".info").appendChild(m); openMenu = m; b.setAttribute("aria-expanded", "true");
      m.querySelector("button").focus(); return;
    }
    closeMenu(); target = p;
    if (act === "rename") { $("#renTitle").value = p.title; $("#renMsg").textContent = ""; $("#dRen").showModal(); $("#renTitle").select(); }
    if (act === "delete") { $("#delImg").src = p.url; $("#delName").textContent = p.title; $("#delMsg").textContent = ""; $("#dDel").showModal(); }
    if (act === "recent") {
      var fd = new FormData(); fd.append("id", p.id); fd.append("recent", p.recent ? "" : "1");
      var toLatest = !p.recent;
      call("recent", fd, { mode: "new" }).then(function () { toast(toLatest ? "Moved to Latest Updates" : "Moved to All Media"); }, function (er) { toast(er.message, true); });
    }
  });
  document.addEventListener("click", function (e) { if (openMenu && !e.target.closest(".info")) closeMenu(); });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape") closeMenu(); });

  /* Ripple on buttons */
  document.addEventListener("pointerdown", function (e) {
    var b = e.target.closest(".btn"); if (!b || b.disabled) return;
    var r = b.getBoundingClientRect(), d = Math.max(r.width, r.height), s = document.createElement("span");
    s.className = "rip"; s.style.cssText = "width:" + d + "px;height:" + d + "px;left:" + (e.clientX - r.left - d / 2) + "px;top:" + (e.clientY - r.top - d / 2) + "px";
    b.appendChild(s); setTimeout(function () { s.remove(); }, 650);
  });

  /* Dialogs */
  document.querySelectorAll("[data-close]").forEach(function (b) { b.addEventListener("click", function () { b.closest("dialog").close(); }); });
  document.querySelectorAll("dialog").forEach(function (d) { d.addEventListener("click", function (e) { if (e.target === d) d.close(); }); });
  function busy(btn, on, label) { btn.disabled = on; btn.classList.toggle("load", on); btn.firstChild.textContent = on ? label[0] : label[1]; }

  $("#fRen").addEventListener("submit", function (e) {
    e.preventDefault();
    var fd = new FormData(); fd.append("id", target.id); fd.append("title", $("#renTitle").value);
    busy($("#renGo"), true, ["Saving...", "Save"]);
    call("rename", fd, { mode: "none" }).then(function () { $("#dRen").close(); toast("Saved"); }, function (er) { $("#renMsg").textContent = er.message; })
      .then(function () { busy($("#renGo"), false, ["", "Save"]); });
  });
  $("#fDel").addEventListener("submit", function (e) {
    e.preventDefault();
    var fd = new FormData(); fd.append("id", target.id);
    busy($("#delGo"), true, ["Deleting...", "Delete"]);
    call("delete", fd, { mode: "none", outId: target.id, onOk: function () { $("#dDel").close(); } })
      .then(function () { toast("Photo deleted"); }, function (er) { $("#delMsg").textContent = er.message; })
      .then(function () { busy($("#delGo"), false, ["", "Delete"]); });
  });

  /* Upload */
  function openUpload() { chosen = []; $("#file").value = ""; $("#upTitle").value = ""; $("#upMsg").textContent = ""; showChosen(); $("#dUp").showModal(); }
  function showChosen() {
    var t = $("#chosen");
    if (!chosen.length) { t.textContent = "No photos selected."; return; }
    t.innerHTML = chosen.map(function (f) { return esc(f.name) + " (" + size(f.size) + ")"; }).join("<br>");
  }
  function addFiles(fl) {
    var bad = [], msg = "";
    Array.prototype.forEach.call(fl, function (f) {
      if (!/^image\/(jpeg|png|webp|gif)$/.test(f.type)) { bad.push(f.name + ": not a supported image"); return; }
      if (f.size > limits.file) { bad.push(f.name + ": larger than " + size(limits.file)); return; }
      chosen.push(f);
    });
    var tot = chosen.reduce(function (a, f) { return a + f.size; }, 0);
    if (tot > limits.post * 0.95) { msg = "Total size is above the server limit (" + size(limits.post) + "). Upload fewer photos at once."; }
    $("#upMsg").textContent = bad.concat(msg ? [msg] : []).join(" · ");
    showChosen();
  }
  $("#btnUpload").addEventListener("click", openUpload);
  $("#navUpload").addEventListener("click", openUpload);
  $("#drop").addEventListener("click", function () { $("#file").click(); });
  $("#drop").addEventListener("keydown", function (e) { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); $("#file").click(); } });
  $("#file").addEventListener("change", function (e) { chosen = []; addFiles(e.target.files); });
  ["dragenter", "dragover"].forEach(function (ev) { $("#drop").addEventListener(ev, function (e) { e.preventDefault(); this.classList.add("over"); }); });
  ["dragleave", "drop"].forEach(function (ev) { $("#drop").addEventListener(ev, function (e) { e.preventDefault(); this.classList.remove("over"); }); });
  $("#drop").addEventListener("drop", function (e) { addFiles(e.dataTransfer.files); });

  $("#fUp").addEventListener("submit", function (e) {
    e.preventDefault();
    if (!chosen.length) { $("#upMsg").textContent = "Choose at least one photo."; return; }
    var fd = new FormData();
    chosen.forEach(function (f) { fd.append("files[]", f); });
    fd.append("title", $("#upTitle").value);
    if ($("#upRecent").checked) fd.append("recent", "1");
    busy($("#upGo"), true, ["Uploading...", "Upload"]);
    call("upload", fd, { mode: "new" }).then(function (j) { $("#dUp").close(); toast(j.added + (j.added === 1 ? " photo uploaded" : " photos uploaded") + (j.error ? " (some skipped)" : ""), !!j.error); },
      function (er) { $("#upMsg").textContent = er.message; })
      .then(function () { busy($("#upGo"), false, ["", "Upload"]); });
  });

  $("#q").addEventListener("input", function () { render("none"); });
  $("#cat").addEventListener("change", function () { render("all"); });
  render("none"); load();
})();
</script>

<!-- Announcements manager (separate from the gallery script) -->
<script>
(function () {
  var $ = function (s) { return document.querySelector(s); };
  var list = $("#annList"), items = [];
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" }[c]; }); }
  function say(t, bad) {
    var e = $("#toast"); e.textContent = t; e.className = "show" + (bad ? " bad" : "");
    setTimeout(function () { e.className = ""; }, 3000);
  }
  function api(action, data) {
    var fd = new FormData();
    Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
    return fetch("dashboard.php?action=" + action, { method: "POST", headers: { "X-Requested-With": "fetch" }, body: fd })
      .then(function (r) { return r.json().catch(function () { throw new Error("Server error."); }); })
      .then(function (j) { if (!j.ok) throw new Error(j.error || "Request failed."); items = j.announcements; draw(); return j; });
  }
  function draw() {
    list.innerHTML = items.length ? items.map(function (a, i) {
      return '<div class="ann-row" data-id="' + esc(a.id) + '"><textarea rows="2" maxlength="300" aria-label="Announcement ' + (i + 1) + '">' + esc(a.body) + '</textarea>' +
        '<div class="ann-acts"><button type="button" class="btn" data-a="save">Save</button><button type="button" class="btn red" data-a="del">Delete</button></div></div>';
    }).join("") : '<div class="meta">No announcements yet.</div>';
  }
  function open() {
    $("#annMsg").textContent = ""; $("#annNew").value = "";
    fetch("dashboard.php?announcements=1", { cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (!j.ok) throw new Error(j.error); items = j.announcements; draw(); })
      .catch(function (e) { list.innerHTML = '<div class="msg">' + esc(e.message || "Could not load announcements.") + '</div>'; });
    $("#dAnn").showModal();
  }
  $("#navAnn").addEventListener("click", open);
  $("#annAdd").addEventListener("click", function () {
    var v = $("#annNew").value.trim();
    if (!v) { $("#annMsg").textContent = "Write the announcement first."; return; }
    $("#annMsg").textContent = "";
    api("ann_add", { body: v }).then(function () { $("#annNew").value = ""; say("Announcement added"); },
      function (e) { $("#annMsg").textContent = e.message; });
  });
  list.addEventListener("click", function (e) {
    var b = e.target.closest("[data-a]"); if (!b) return;
    var row = b.closest(".ann-row"), id = row.dataset.id;
    if (b.dataset.a === "save") {
      api("ann_edit", { id: id, body: row.querySelector("textarea").value }).then(function () { say("Saved"); }, function (er) { say(er.message, true); });
    } else if (b.dataset.a === "del") {
      if (!confirm("Delete this announcement?")) return;
      api("ann_delete", { id: id }).then(function () { say("Announcement deleted"); }, function (er) { say(er.message, true); });
    }
  });
})();
</script>

<!-- Contact messages manager (separate from the gallery and announcements scripts) -->
<script>
(function () {
  var $ = function (s) { return document.querySelector(s); };
  var list = $("#cmList"), items = [];
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" }[c]; }); }
  function say(t, bad) {
    var e = $("#toast"); e.textContent = t; e.className = "show" + (bad ? " bad" : "");
    setTimeout(function () { e.className = ""; }, 3000);
  }
  function badge(n) { var b = $("#msgBadge"); b.textContent = n > 99 ? "99+" : n; b.classList.toggle("hide", !n); }
  function api(action, data) {
    var fd = new FormData();
    Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
    return fetch("dashboard.php?action=" + action, { method: "POST", headers: { "X-Requested-With": "fetch" }, body: fd })
      .then(function (r) { return r.json().catch(function () { throw new Error("Server error."); }); })
      .then(function (j) { if (!j.ok) throw new Error(j.error || "Request failed."); items = j.messages; badge(j.unread); draw(); return j; });
  }
  function draw() {
    list.innerHTML = items.length ? items.map(function (m) {
      var reply = "mailto:" + m.email + "?subject=" + encodeURIComponent("Re: your message");
      return '<article class="cm-row' + (m.read ? "" : " unread") + '" data-id="' + esc(m.id) + '" data-read="' + (m.read ? "1" : "") + '">' +
        '<div class="cm-head"><b>' + esc(m.name) + '</b><span class="meta">' + esc(m.time) + '</span></div>' +
        '<div class="cm-contact"><a href="mailto:' + esc(m.email) + '">' + esc(m.email) + '</a>' +
        (m.phone ? ' &middot; <a href="tel:' + esc(m.phone.replace(/[^\d+]/g, "")) + '">' + esc(m.phone) + '</a>' : '') + '</div>' +
        '<p class="cm-body">' + esc(m.body) + '</p>' +
        '<div class="cm-acts"><button type="button" class="btn ghost" data-a="read">' + (m.read ? "Mark as unread" : "Mark as read") + '</button>' +
        '<button type="button" class="btn" data-a="reply">Reply</button>' +
        '<button type="button" class="btn red" data-a="del">Delete</button></div>' +
        '<div class="cm-reply hide"><input type="text" maxlength="200" value="Re: your message" aria-label="Subject">' +
        '<textarea rows="4" maxlength="5000" placeholder="Write your reply to ' + esc(m.email) + '..." aria-label="Reply"></textarea>' +
        '<div class="cm-acts"><button type="button" class="btn ghost" data-a="rcancel">Cancel</button>' +
        '<button type="button" class="btn" data-a="rsend">Send email</button></div></div></article>';
    }).join("") : '<div class="meta">No messages yet.</div>';
  }
  $("#navMsg").addEventListener("click", function () {
    list.innerHTML = '<div class="meta">Loading...</div>';
    $("#dMsg").showModal();
    api("msg_list").catch(function (e) { list.innerHTML = '<div class="msg">' + esc(e.message || "Could not load messages.") + '</div>'; });
  });
  list.addEventListener("click", function (e) {
    var b = e.target.closest("[data-a]"); if (!b) return;
    var row = b.closest(".cm-row"), id = row.dataset.id;
    if (b.dataset.a === "read") {
      api("msg_read", { id: id, read: row.dataset.read ? "" : "1" }).catch(function (er) { say(er.message, true); });
    } else if (b.dataset.a === "reply") {
      var rf = row.querySelector(".cm-reply"); rf.classList.toggle("hide");
      if (!rf.classList.contains("hide")) rf.querySelector("textarea").focus();
    } else if (b.dataset.a === "rcancel") {
      row.querySelector(".cm-reply").classList.add("hide");
    } else if (b.dataset.a === "rsend") {
      var rbox = row.querySelector(".cm-reply"), rtxt = rbox.querySelector("textarea").value.trim();
      if (!rtxt) { say("Write your reply first.", true); return; }
      b.disabled = true; b.textContent = "Sending...";
      api("msg_reply", { id: id, subject: rbox.querySelector("input").value, body: rtxt })
        .then(function () { say("Reply sent by email"); }, function (er) { b.disabled = false; b.textContent = "Send email"; say(er.message, true); });
    } else if (b.dataset.a === "del") {
      if (!confirm("Delete this message?")) return;
      api("msg_delete", { id: id }).then(function () { say("Message deleted"); }, function (er) { say(er.message, true); });
    }
  });
  api("msg_list").catch(function () { /* badge tu - kimya */ });
})();
</script>
</body>
</html>
