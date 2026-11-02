<?php
/**
 * receipt_student-portal.php — Risiti ya malipo (inaweza kuchapishwa / kuhifadhiwa kama PDF).
 *   ?no=RCT/2026/000001
 * Mwanafunzi anaona risiti zake tu. Admin (aliyeingia dashboard) anaweza kuona za mwanafunzi yeyote (&reg=...).
 */
require_once __DIR__ . '/student_store_student-portal.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$S = null;
if (!empty($_SESSION['is_admin']) && !empty($_GET['reg'])) {
    $db = store_load();
    $i = store_find($db, (string)$_GET['reg']);
    $S = $i !== null ? $db['students'][$i] : null;
} else {
    $S = admin_view_student();           // admin ndani ya portal ya mwanafunzi (?as=token)
    if ($S === null) {
        require __DIR__ . '/auth_guard_student-portal.php';
        $S = store_current_student();    // mwanafunzi halisi aliyeingia
    }
}
if (!$S) { http_response_code(404); exit('Risiti haijapatikana.'); }

$no = (string)($_GET['no'] ?? '');
$pay = null;
foreach ($S['payments'] as $p) {
    if ($no !== '' && ($p['receipt'] ?? '') === $no && ($p['status'] ?? '') === 'Confirmed') { $pay = $p; break; }
}
if (!$pay) { http_response_code(404); exit('Risiti haijapatikana.'); }

$D = student_derived($S);
$F = $S['fees'];
?><!doctype html>
<html lang="sw"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Risiti <?= h($no) ?></title>
<style>
  body{margin:0;background:#eef1f3;font:15px/1.5 -apple-system,Segoe UI,Arial,sans-serif;color:#16262e}
  .wrap{max-width:640px;margin:24px auto;padding:0 12px}
  .rc{background:#fff;border:1px solid #d8e0e5;border-radius:10px;padding:28px 30px}
  h1{margin:0;font-size:1.3rem;letter-spacing:.04em}
  .sub{color:#5b6e78;font-size:.9rem;margin-top:2px}
  .no{margin:18px 0 6px;font-size:1.15rem;font-weight:700;color:#0b6e63}
  table{width:100%;border-collapse:collapse;margin-top:10px}
  td{padding:8px 0;border-bottom:1px solid #e6ecef;vertical-align:top}
  td:first-child{color:#5b6e78;width:42%}
  .amt td{font-size:1.2rem;font-weight:700;border-bottom:2px solid #16262e}
  h2{font-size:.95rem;margin:22px 0 0;color:#5b6e78;text-transform:uppercase;letter-spacing:.05em}
  .stamp{display:inline-block;margin-top:18px;border:2px solid #1e7b3e;color:#1e7b3e;border-radius:6px;padding:4px 14px;font-weight:700;letter-spacing:.08em}
  .foot{margin-top:18px;color:#5b6e78;font-size:.8rem}
  .bar{display:flex;gap:10px;justify-content:flex-end;margin-bottom:10px}
  .bar button{padding:8px 16px;border:0;border-radius:6px;background:#0b6e63;color:#fff;font-weight:600;cursor:pointer}
  @media print{body{background:#fff}.wrap{margin:0;max-width:none}.rc{border:0;padding:0}.bar{display:none}}
</style></head><body><div class="wrap">
<div class="bar"><button onclick="window.print()">Chapisha / Hifadhi PDF</button></div>
<div class="rc">
  <h1>RISITI YA MALIPO · PAYMENT RECEIPT</h1>
  <div class="sub">Student Portal</div>
  <div class="no">Namba ya risiti: <?= h($no) ?></div>
  <table>
    <tr><td>Tarehe &amp; saa</td><td><?= h($pay['date']) ?> <?= h($pay['time'] ?? '') ?></td></tr>
    <tr><td>Imepokelewa kutoka</td><td><?= h($S['full_name']) ?></td></tr>
    <tr><td>Namba ya usajili</td><td><?= h($S['reg_no']) ?></td></tr>
    <tr><td>Control number</td><td><?= h($F['control_number'] ?: '—') ?></td></tr>
    <tr><td>Njia ya malipo</td><td><?= h($pay['channel'] ?: '—') ?></td></tr>
    <tr><td>Namba ya muamala</td><td><?= h($pay['ref'] ?: '—') ?></td></tr>
    <tr><td>Chanzo</td><td><?= ($pay['source'] ?? '') === 'auto' ? 'Imethibitishwa kiotomatiki na mfumo wa malipo' : 'Imeingizwa na msimamizi' ?></td></tr>
    <tr class="amt"><td>Kiasi kilicholipwa</td><td>TZS <?= number_format((int)$pay['amount']) ?></td></tr>
  </table>
  <h2>Hali ya akaunti (sasa)</h2>
  <table>
    <tr><td>Jumla ya kudaiwa</td><td>TZS <?= number_format($D['billed']) ?></td></tr>
    <tr><td>Jumla iliyolipwa</td><td>TZS <?= number_format($D['paid']) ?></td></tr>
    <tr><td>Inayodaiwa</td><td>TZS <?= number_format($D['outstanding']) ?></td></tr>
  </table>
  <span class="stamp">PAID</span>
  <div class="foot">Risiti hii imetolewa na kompyuta na ni halali bila saini.</div>
</div>
</div></body></html>
