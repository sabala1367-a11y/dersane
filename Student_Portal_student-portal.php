<?php
require_once __DIR__ . '/student_store_student-portal.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* Admin akiingia kutoka dashboard (kiungo chenye ?as=token) anaona akaunti hiyo bila kuingia kama mwanafunzi. */
$ADMIN_VIEW = false;
$S = admin_view_student();
if ($S === null && !empty($_SESSION['is_admin']) && empty($_SESSION['reg_no'])) {
    // Admin amefungua Student_Portal_student-portal.php moja kwa moja: anaonyeshwa portal ya mwanafunzi (?reg=NAMBA, la sivyo wa kwanza)
    // kama Admin (hali ya kuangalia tu). Hairuhusu mabadiliko ya nenosiri la mwanafunzi.
    $db0 = store_load();
    $i0 = isset($_GET['reg']) ? store_find($db0, (string)$_GET['reg']) : (isset($db0['students'][0]) ? 0 : null);
    if ($i0 !== null) $S = $db0['students'][$i0];
}
if ($S !== null) {
    $ADMIN_VIEW = true;
} else {
    require __DIR__ . '/auth_guard_student-portal.php';
    $S = store_current_student();
}

/* Data ya mwanafunzi inatoka kwenye hifadhi (data/students_student-portal.json) — inabadilishwa kupitia admin_dashboard_student-portal.php */
if (!$S) { http_response_code(404); exit('Student record not found. Please contact the administrator.'); }
$D = student_derived($S);
$F = $S['fees'];
$photoUrl = student_photo_url($S);   // picha inapakiwa kupitia admin_dashboard_student-portal.php

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function n($v): string { return number_format((int)$v); }
function dfmt($d, string $fmt = 'd M Y'): string { return $d ? date($fmt, strtotime($d)) : '—'; }
function item_status(int $billed, int $paid): array {
    if ($billed > 0 && $paid >= $billed) return ['Paid', 'good'];
    if ($paid > 0) return ['Partly paid', 'warn'];
    return ['Unpaid', 'bad'];
}
function quiz_pill(array $q): string {
    if ($q['score'] === null) return '<span class="pill warn">Upcoming</span>';
    $pc = $q['score'] / $q['out_of'] * 100;
    if ($pc < 50) return '<span class="pill bad">Failed</span>';
    return '<span class="pill ' . ($pc > 75 ? 'good' : 'info') . '">Passed</span>';
}

$firstName = explode(' ', trim($S['display_name'] ?: $S['full_name']))[0];
$mealsSt = item_status($F['meals_billed'], $F['meals_paid']);
$accomSt = item_status($F['accom_billed'], $F['accom_paid']);

$quizRows = array_values(array_filter($S['quizzes'], fn($q) => $q['score'] !== null));
usort($quizRows, fn($a, $b) => strcmp($b['date'], $a['date']));
$upRows = array_values(array_filter($S['quizzes'], fn($q) => $q['score'] === null));
usort($upRows, fn($a, $b) => strcmp($a['date'], $b['date']));
$quizRows = array_merge($quizRows, $upRows);

$payRows = $S['payments'];
usort($payRows, fn($a, $b) => strcmp($b['date'], $a['date']));
?>
<!doctype html><html><head><meta charset=utf8><meta name=viewport content="width=device-width,initial-scale=1,viewport-fit=cover"><style>:root{color-scheme:light;box-sizing:border-box;padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px)}html{scroll-padding-top:env(safe-area-inset-top,0px)}body{margin:0;padding:0;font:14px -apple-system,BlinkMacSystemFont,sans-serif;background:#fff;color:#000}img{max-width:100%}[hidden]:not([hidden=until-found i]){display:none!important}</style></head><body>
<title>Student Portal</title>
<meta name="claude:viewport-fit-sides" content="cover">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Source+Sans+3:wght@400;500;600&display=swap">
<style>
/* Layout: flat top app bar + left hamburger drawer with expandable submenus; content area of elevated Material cards */
:root{
  --bg:#E9EEF1; --surface:#FFFFFF; --surface-2:#F3F6F8; --line:#D8E0E5;
  --fg:#16262E; --muted:#5B6E78;
  --primary:#0B6E63; --on-primary:#FFFFFF; --primary-soft:#D4EEEA;
  --amber:#B7791F; --amber-soft:#FBEBCB;
  --good:#1E7B3E; --good-soft:#D9F0E0; --bad:#B3261E; --bad-soft:#F8DAD7;
  --shadow-1:0 1px 2px rgba(22,38,46,.14),0 1px 3px rgba(22,38,46,.10);
  --shadow-2:0 3px 6px rgba(22,38,46,.14),0 2px 4px rgba(22,38,46,.10);
  --shadow-4:0 8px 24px rgba(22,38,46,.22);
  --scrim:rgba(10,20,25,.5);
  --f-display:'Outfit','Trebuchet MS',sans-serif;
  --f-body:'Source Sans 3','Segoe UI',Arial,sans-serif;
}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){
  --bg:#0E181D; --surface:#17252C; --surface-2:#1D2E36; --line:#2B3E47;
  --fg:#E4EDF0; --muted:#93A8B1;
  --primary:#3FBFAE; --on-primary:#06231F; --primary-soft:#173B38;
  --amber:#E7B25A; --amber-soft:#3B2E14;
  --good:#6FD08A; --good-soft:#173A25; --bad:#F2928B; --bad-soft:#40201D;
  --shadow-1:0 1px 2px rgba(0,0,0,.5);--shadow-2:0 3px 6px rgba(0,0,0,.5);--shadow-4:0 8px 24px rgba(0,0,0,.6);
  --scrim:rgba(0,0,0,.65); color-scheme:dark}}
:root[data-theme="dark"]{
  --bg:#0E181D; --surface:#17252C; --surface-2:#1D2E36; --line:#2B3E47;
  --fg:#E4EDF0; --muted:#93A8B1;
  --primary:#3FBFAE; --on-primary:#06231F; --primary-soft:#173B38;
  --amber:#E7B25A; --amber-soft:#3B2E14;
  --good:#6FD08A; --good-soft:#173A25; --bad:#F2928B; --bad-soft:#40201D;
  --shadow-1:0 1px 2px rgba(0,0,0,.5);--shadow-2:0 3px 6px rgba(0,0,0,.5);--shadow-4:0 8px 24px rgba(0,0,0,.6);
  --scrim:rgba(0,0,0,.65); color-scheme:dark}

*{box-sizing:border-box}
body{background:var(--bg);color:var(--fg);font-family:var(--f-body);font-size:16px;line-height:1.5;min-height:100vh}
body::before{content:"";position:fixed;inset:0 0 auto;height:env(safe-area-inset-top,0px);background:var(--primary);z-index:60}
h1,h2,h3{font-family:var(--f-display);margin:0;line-height:1.2;text-wrap:balance}
button,input{font:inherit;color:inherit}
:focus-visible{outline:3px solid var(--amber);outline-offset:2px}
.num{font-variant-numeric:tabular-nums}

/* App bar */
.appbar{position:sticky;top:env(safe-area-inset-top,0px);z-index:40;background:var(--primary);color:var(--on-primary);
  height:60px;display:flex;align-items:center;gap:8px;
  padding-left:max(8px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px));box-shadow:var(--shadow-2)}
.icon-btn{width:44px;height:44px;border:0;background:transparent;border-radius:50%;display:grid;place-items:center;cursor:pointer;color:inherit;flex:none}
.icon-btn:hover{background:rgba(255,255,255,.16)}
.icon-btn svg{width:24px;height:24px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.appbar h1{font-size:1.15rem;font-weight:600;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.me-chip{display:flex;align-items:center;gap:10px;font-size:.9rem;font-weight:600}
.me-chip .av{width:34px;height:34px;border-radius:50%;background:var(--on-primary);color:var(--primary);display:grid;place-items:center;font-family:var(--f-display);font-size:.85rem}
@media (max-width:520px){.me-chip span{display:none}}

/* Drawer */
.scrim{position:fixed;inset:0;background:var(--scrim);z-index:70;opacity:0;pointer-events:none;transition:opacity .2s}
.scrim.open{opacity:1;pointer-events:auto}
.drawer{position:fixed;top:0;bottom:0;left:0;z-index:80;width:min(300px,86vw);background:var(--surface);box-shadow:var(--shadow-4);
  transform:translateX(-105%);transition:transform .22s ease;display:flex;flex-direction:column;overflow-y:auto;
  padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px)}
.drawer.open{transform:none}
.drawer-head{background:var(--primary);color:var(--on-primary);padding:20px 20px 18px;padding-left:max(20px,env(safe-area-inset-left,0px))}
.drawer-head .crest{width:44px;height:44px;background:var(--on-primary);color:var(--primary);display:grid;place-items:center;font-family:var(--f-display);font-weight:700;font-size:1.1rem;border-radius:4px;margin-bottom:12px}
.drawer-head strong{font-family:var(--f-display);font-size:1.1rem;display:block}
.drawer-head small{opacity:.85;font-size:.85rem}
.nav{list-style:none;margin:8px 0;padding:0 8px;padding-left:max(8px,env(safe-area-inset-left,0px))}
.nav button{width:100%;display:flex;align-items:center;gap:14px;border:0;background:transparent;text-align:left;
  padding:0 14px;height:48px;border-radius:4px;cursor:pointer;font-weight:600;font-size:.98rem}
.nav button:hover{background:var(--surface-2)}
.nav button svg{width:22px;height:22px;fill:none;stroke:var(--muted);stroke-width:2;stroke-linecap:round;stroke-linejoin:round;flex:none}
.nav button .grow{flex:1}
.nav button .chev{width:18px;height:18px;transition:transform .2s}
.nav button[aria-expanded="true"] .chev{transform:rotate(180deg)}
.nav button.active{background:var(--primary-soft);color:var(--primary)}
.nav button.active svg{stroke:var(--primary)}
.sub{list-style:none;margin:0;padding:0;overflow:hidden;max-height:0;transition:max-height .22s ease}
.sub.open{max-height:200px}
.sub button{height:42px;padding-left:56px;font-weight:500;font-size:.93rem;color:var(--muted)}
.sub button.active{color:var(--primary);font-weight:600}
.nav{flex:1 0 auto}
.nav-logout{margin:0 8px 8px;padding-left:max(0px,env(safe-area-inset-left,0px));border-top:1px solid var(--line);padding-top:8px;position:sticky;bottom:0;background:var(--surface)}
.nav-logout a{display:flex;align-items:center;gap:14px;height:48px;padding:0 14px;border-radius:4px;font-weight:600;font-size:.98rem;color:var(--bad);text-decoration:none}
.nav-logout a:hover{background:var(--surface-2)}
.nav-logout a svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;flex:none}
.drawer-foot{margin-top:0;padding:12px 20px 16px;color:var(--muted);font-size:.8rem;border-top:1px solid var(--line)}

/* Content */
main{width:100%;max-width:none;margin:0;
  padding-block:16px 48px;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px))}
.page{display:none}.page.show{display:block}
.page-head{margin-bottom:16px}
.page-head h2{font-size:1.6rem}
.page-head p{margin:4px 0 0;color:var(--muted)}
.grid{display:grid;gap:16px}
.g4{grid-template-columns:repeat(auto-fit,minmax(210px,1fr))}
.g2{grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr))}
.card{background:var(--surface);border-radius:4px;box-shadow:var(--shadow-1);padding:20px;min-width:0}
.card.raise:hover{box-shadow:var(--shadow-2)}
.card h3{font-size:1.05rem;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:8px}
.card h3 a,.link{color:var(--primary);font-size:.88rem;font-weight:600;font-family:var(--f-body);background:none;border:0;cursor:pointer;padding:0}
.link:hover{text-decoration:underline}

.stat{display:flex;flex-direction:column;gap:2px;position:relative;padding-top:56px}
.stat .ic{position:absolute;top:16px;left:20px;width:40px;height:40px;border-radius:4px;display:grid;place-items:center;background:var(--primary-soft);color:var(--primary)}
.stat .ic.amber{background:var(--amber-soft);color:var(--amber)}
.stat .ic svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.stat .label{color:var(--muted);font-size:.85rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em}
.stat .value{font-family:var(--f-display);font-size:1.9rem;font-weight:600;line-height:1.15}
.stat .value small{font-size:1rem;color:var(--muted);font-weight:500}
.stat .note{color:var(--muted);font-size:.88rem}

.hello{background:var(--primary);color:var(--on-primary);box-shadow:var(--shadow-2);border-radius:4px;padding:22px 24px;margin-bottom:16px;
  display:flex;flex-wrap:wrap;gap:12px 24px;align-items:center;justify-content:space-between}
.hello h2{font-size:1.5rem}
.hello p{margin:4px 0 0;opacity:.9}
.hello .term{background:rgba(255,255,255,.16);padding:8px 14px;border-radius:4px;font-size:.9rem;font-weight:600}

.bar{height:10px;background:var(--surface-2);border-radius:2px;overflow:hidden;border:1px solid var(--line)}
.bar i{display:block;height:100%;background:var(--primary)}
.bar.amber i{background:var(--amber)}
.rowline{display:flex;justify-content:space-between;gap:12px;font-size:.92rem;margin-bottom:6px}
.rowline span:first-child{color:var(--muted)}

.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.93rem}
th{text-align:left;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:600;padding:8px 12px;border-bottom:2px solid var(--line);white-space:nowrap}
td{padding:12px;border-bottom:1px solid var(--line);vertical-align:middle}
tr:last-child td{border-bottom:0}
td.r,th.r{text-align:right}
.code{font-weight:600;white-space:nowrap}
.pill{display:inline-block;padding:2px 10px;border-radius:12px;font-size:.8rem;font-weight:600;white-space:nowrap}
.pill.good{background:var(--good-soft);color:var(--good)}
.pill.warn{background:var(--amber-soft);color:var(--amber)}
.pill.bad{background:var(--bad-soft);color:var(--bad)}
.pill.info{background:var(--primary-soft);color:var(--primary)}

.list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column}
.list li{display:flex;gap:14px;padding:12px 0;border-bottom:1px solid var(--line);align-items:flex-start}
.list li:last-child{border-bottom:0;padding-bottom:0}
.list li:first-child{padding-top:0}
.time{width:76px;flex:none;font-family:var(--f-display);font-weight:600;font-size:.92rem;color:var(--primary)}
.list .t{font-weight:600}
.list .s{color:var(--muted);font-size:.88rem}

.tabs{display:flex;gap:4px;border-bottom:1px solid var(--line);margin-bottom:16px;overflow-x:auto}
.tabs button{border:0;background:none;padding:12px 16px;font-weight:600;color:var(--muted);cursor:pointer;border-bottom:3px solid transparent;white-space:nowrap}
.tabs button.on{color:var(--primary);border-bottom-color:var(--primary)}

.kv{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px 24px}
.kv dt{font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:600}
.kv dd{margin:2px 0 0;font-weight:500;overflow-wrap:anywhere}
.kv2{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:18px 40px}
.kcol{margin:0;display:grid;gap:18px;align-content:start;min-width:0}
.kcol dt{font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:600}
.kcol dd{margin:2px 0 0;font-weight:500;overflow-wrap:anywhere}
.profile-top{display:flex;gap:20px;align-items:center;flex-wrap:wrap;margin-bottom:22px;padding-bottom:20px;border-bottom:1px solid var(--line)}
.profile-top .big{width:84px;height:84px;border-radius:50%;background:var(--primary);color:var(--on-primary);display:grid;place-items:center;font-family:var(--f-display);font-size:1.9rem;font-weight:600;overflow:hidden}
.big img,.me-chip .av img{width:100%;height:100%;object-fit:cover;display:block}
.me-chip .av{overflow:hidden}
.photo{position:relative;width:84px;height:84px;flex:none}
.profile-top h3{font-size:1.4rem;margin:0}
.profile-top p{margin:2px 0 0;color:var(--muted)}

.tt-day{margin-bottom:6px;font-family:var(--f-display);font-weight:600}
.tt-day.today{color:var(--primary)}

/* Form */
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:18px}
.field label{font-weight:600;font-size:.9rem}
.input-wrap{position:relative}
.input-wrap input{width:100%;height:50px;padding:0 52px 0 14px;background:var(--surface-2);border:0;border-bottom:2px solid var(--muted);border-radius:4px 4px 0 0}
.input-wrap input:focus{outline:none;border-bottom-color:var(--primary);background:var(--primary-soft)}
.input-wrap .eye{position:absolute;right:4px;top:3px;width:44px;height:44px;border:0;background:none;color:var(--muted);cursor:pointer;border-radius:50%;display:grid;place-items:center}
.input-wrap .eye svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.hint{font-size:.85rem;color:var(--muted)}
.err{color:var(--bad);font-size:.85rem;min-height:1.2em}
.meter{display:grid;grid-template-columns:repeat(4,1fr);gap:4px;margin-top:2px}
.meter i{height:6px;background:var(--line);border-radius:1px}
.meter[data-s="1"] i:nth-child(-n+1){background:var(--bad)}
.meter[data-s="2"] i:nth-child(-n+2){background:var(--amber)}
.meter[data-s="3"] i:nth-child(-n+3){background:var(--good)}
.meter[data-s="4"] i{background:var(--good)}
.rules{list-style:none;margin:0;padding:0;display:grid;gap:6px;font-size:.9rem}
.rules li{display:flex;gap:8px;color:var(--muted)}
.rules li::before{content:"○";width:1em;flex:none}
.rules li.ok{color:var(--good)}
.rules li.ok::before{content:"●"}
.btn{height:44px;padding:0 24px;border:0;border-radius:4px;background:var(--primary);color:var(--on-primary);font-weight:600;letter-spacing:.03em;cursor:pointer;box-shadow:var(--shadow-1)}
.btn:hover{box-shadow:var(--shadow-2)}
.btn.flat{background:transparent;color:var(--primary);box-shadow:none}
.btn.flat:hover{background:var(--primary-soft)}
.actions{display:flex;gap:8px;flex-wrap:wrap}

.sem{display:flex;flex-direction:column;gap:18px}
.sem-head{display:flex;justify-content:space-between;align-items:center;gap:8px 16px;flex-wrap:wrap}
.chip{background:var(--surface-2);padding:6px 16px;border-radius:16px;font-weight:700;font-size:.8rem;letter-spacing:.06em;text-transform:uppercase}
.sem-year{color:var(--muted);font-size:.76rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase}
.costbox{border:1.5px dashed var(--line);border-radius:8px;padding:14px 18px;background:var(--surface-2);display:grid;gap:10px}
.costbox div{display:flex;justify-content:space-between;gap:12px}
.costbox span{color:var(--muted)}
.costbox strong{font-variant-numeric:tabular-nums}
.stages-title{text-align:center;color:var(--muted);font-size:.78rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
.stages{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));position:relative}
.stages::before{content:"";position:absolute;top:14px;left:25%;right:25%;height:2px;background:var(--line)}
.stage{text-align:center;position:relative;display:flex;flex-direction:column;align-items:center;gap:4px;min-width:0;padding:0 4px}
.dot{width:30px;height:30px;border-radius:50%;border:3px solid var(--primary);background:var(--surface);position:relative;z-index:1;margin-bottom:6px}
.dot.amber{border-color:var(--amber)}
.dot.good{border-color:var(--good);background:var(--good-soft)}
.stage .nm{font-size:.7rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--muted)}
.stage .amt{font-family:var(--f-display);font-size:1.25rem;font-weight:700;font-variant-numeric:tabular-nums}
.stage .amt.good{color:var(--good)}
.stage .sub{font-size:.74rem;color:var(--muted)}
@media (max-width:420px){.stage .amt{font-size:1.05rem}.stage .nm{font-size:.62rem}}
.toast{position:fixed;left:50%;bottom:calc(20px + env(safe-area-inset-bottom,0px));transform:translate(-50%,30px);background:var(--fg);color:var(--bg);
  padding:12px 20px;border-radius:4px;box-shadow:var(--shadow-4);opacity:0;pointer-events:none;transition:.25s;z-index:90;max-width:calc(100vw - 32px)}
.toast.show{opacity:1;transform:translate(-50%,0)}
@media (prefers-reduced-motion:reduce){*{transition:none!important}}
</style>

<?php if ($ADMIN_VIEW): ?>
<div style="position:fixed;left:0;right:0;bottom:0;z-index:9999;background:#B7791F;color:#fff;padding:10px 16px calc(10px + env(safe-area-inset-bottom,0px));display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;font:600 14px sans-serif;box-shadow:0 -2px 10px rgba(0,0,0,.25)">
  <span>Unaangalia akaunti ya <?= h($S['display_name'] ?: $S['full_name']) ?> (<?= h($S['reg_no']) ?>) kama Admin.</span>
  <a href="admin_dashboard_student-portal.php" style="background:#fff;color:#7a4f0e;padding:6px 14px;border-radius:6px;text-decoration:none;font-weight:700">← Rudi kwenye Dashboard</a>
</div>
<?php endif; ?>
<header class="appbar">
  <button class="icon-btn" id="menuBtn" aria-label="Open menu" aria-expanded="false" aria-controls="drawer">
    <svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
  </button>
  <h1 id="pageTitle">Dashboard</h1>
  <button class="icon-btn" id="themeBtn" type="button" aria-label="Switch to dark mode" title="Dark mode">
    <svg id="icoMoon" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
    <svg id="icoSun" viewBox="0 0 24 24" hidden><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
  </button>
  <a class="icon-btn" id="logoutBar" href="<?= $ADMIN_VIEW ? 'admin_dashboard_student-portal.php' : 'logout_student-portal.php' ?>" aria-label="Logout" title="Logout"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg></a>
  <div class="me-chip"><span><?= h($S['display_name']) ?></span><div class="av" id="topAv"><?php if ($photoUrl): ?><img src="<?= h($photoUrl) ?>" alt=""><?php else: ?><?= h($S['initials']) ?><?php endif; ?></div></div>
</header>

<div class="scrim" id="scrim"></div>
<aside class="drawer" id="drawer" aria-label="Main menu">
  <div class="drawer-head">
    <div class="crest">SP</div>
    <strong>Student Portal</strong>
    <small>Academic year <span data-ay>2026/2027</span></small>
  </div>
  <ul class="nav" id="nav">
    <li><button data-go="dashboard"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg><span class="grow">Dashboard</span></button></li>
    <li><button data-go="profile"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg><span class="grow">Profile</span></button></li>
    <li><button data-go="quizzes"><svg viewBox="0 0 24 24"><path d="M4 4h12a4 4 0 014 4v12H8a4 4 0 01-4-4z"/><path d="M8 9h8M8 13h5"/></svg><span class="grow">Quiz Results</span></button></li>
    <li>
      <button data-parent="finance" aria-expanded="false"><svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20M6 15h4"/></svg><span class="grow">Financial Hub</span><svg class="chev" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></button>
      <ul class="sub" id="sub-finance">
        <li><button data-go="fees">Fee Statement</button></li>
        <li><button data-go="payments">Payment History</button></li>
      </ul>
    </li>
    <li><button data-go="password"><svg viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg><span class="grow">Change Password</span></button></li>
  </ul>
  <div class="nav-logout">
    <a id="logoutTop" href="<?= $ADMIN_VIEW ? 'admin_dashboard_student-portal.php' : 'logout_student-portal.php' ?>" aria-label="Logout"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg><span>Logout</span></a>
  </div>
  <div class="drawer-foot">Reg. No. <?= h($S['reg_no']) ?><br>Session ends after 15 minutes of inactivity.</div>
</aside>

<main>
  <!-- DASHBOARD -->
  <section class="page" data-page="dashboard" data-title="Dashboard">
    <div class="hello">
      <div>
        <h2>Karibu, <?= h($firstName) ?></h2>
        <p><?= h(trim(($S['house'] ? $S['house'] . ' House' : '') . ($S['house'] && $S['year'] ? ' · ' : '') . $S['year'])) ?></p>
      </div>
      <div class="term">Supervisor: <?= h($S['supervisor'] ?: '—') ?></div>
    </div>

    <h2 style="font-size:1.25rem;margin:28px 0 4px">Payment Clearance Plan</h2>
    <p style="margin:0 0 14px;color:var(--muted)">Amounts to clear at each stage of the semester. All amounts in Tanzanian shillings (TZS).</p>
    <div class="grid g2">
<?php foreach (['Semester I', 'Semester II'] as $semLabel): ?>
      <div class="card sem">
        <div class="sem-head"><span class="chip"><?= $semLabel ?></span><span class="sem-year">Academic year <span data-ay>2026/2027</span></span></div>
        <div class="costbox">
          <div><span>Meals</span><strong><?= n($S['plan']['meals']) ?> /=</strong></div>
          <div><span>House Accommodation</span><strong><?= n($S['plan']['accom']) ?> /=</strong></div>
        </div>
        <div class="stages-title">Amount payable at each stage</div>
        <div class="stages">
            <div class="stage"><div class="dot "></div><span class="nm">Meals</span><span class="amt "><?= n($S['plan']['meals']) ?></span></div>
            <div class="stage"><div class="dot good"></div><span class="nm">House Accommodation</span><span class="amt good"><?= n($S['plan']['accom']) ?></span></div>
        </div>
      </div>
<?php endforeach; ?>
    </div>

    <div class="grid g2" style="margin-top:28px">
      <div class="card">
        <h3>Fee payment progress <button class="link" data-go="fees">Fee statement</button></h3>
        <div class="rowline"><span>Paid</span><strong class="num"><?= n($D['paid']) ?> TZS</strong></div>
        <div class="bar<?= $D['pct'] < 100 ? ' amber' : '' ?>"><i style="width:<?= min(100, $D['pct']) ?>%"></i></div>
        <div class="rowline" style="margin-top:8px"><span>Total for <?= h($F['year']) ?></span><strong class="num"><?= n($D['billed']) ?> TZS</strong></div>
        <p class="hint" style="margin:14px 0 0">Meals <?= n($F['meals_billed']) ?> (<?= h(strtolower($mealsSt[0])) ?>) and house accommodation <?= n($F['accom_billed']) ?> (<?= h(strtolower($accomSt[0])) ?>).
          <?= $D['outstanding'] > 0
              ? 'The remaining ' . n($D['outstanding']) . ' TZS is due' . ($F['due_date'] ? ' on ' . h(dfmt($F['due_date'], 'j F Y')) : '') . '.'
              : 'All fees are fully paid.' ?></p>
      </div>
    </div>
  </section>

  <!-- PROFILE -->
  <section class="page" data-page="profile" data-title="Profile">
    <div class="page-head"><h2>Profile</h2><p>Registered student details.</p></div>
    <div class="card">
      <div class="profile-top">
        <div class="photo"><div class="big" id="bigAv"><?php if ($photoUrl): ?><img src="<?= h($photoUrl) ?>" alt="Profile photo"><?php else: ?><?= h($S['initials']) ?><?php endif; ?></div></div>
        <div><h3><?= h(mb_strtoupper($S['full_name'])) ?></h3><p>Reg. No. <?= h($S['reg_no']) ?></p><span class="pill good" style="margin-top:8px"><?= h($S['status'] ?: 'Active house member') ?></span></div>
      </div>
      <div class="kv2">
        <dl class="kcol">
          <div><dt>House name</dt><dd><?= h($S['house'] ?: '—') ?></dd></div>
          <div><dt>Supervisor</dt><dd><?= h($S['supervisor'] ?: '—') ?></dd></div>
          <div><dt>Address</dt><dd><?= h($S['address'] ?: '—') ?></dd></div>
          <div><dt>Email</dt><dd><?= h($S['email'] ?: '—') ?></dd></div>
          <div><dt>Phone</dt><dd class="num"><?= h($S['phone'] ?: '—') ?></dd></div>
          <div><dt>Next of kin</dt><dd><?php if ($S['kin_name']): ?><?= h($S['kin_name']) ?><?= $S['kin_rel'] ? ' (' . h($S['kin_rel']) . ')' : '' ?><?= $S['kin_phone'] ? ' · <span class="num">' . h($S['kin_phone']) . '</span>' : '' ?><?php else: ?>—<?php endif; ?></dd></div>
        </dl>
        <dl class="kcol">
          <div><dt>Admitted year</dt><dd><?= h($S['admitted'] ?: '—') ?></dd></div>
          <div><dt>Current year</dt><dd><?= h($S['year'] ?: '—') ?></dd></div>
          <div><dt>Gender</dt><dd><?= h($S['gender'] ?: '—') ?></dd></div>
          <div><dt>University name</dt><dd><?= h($S['university'] ?: '—') ?></dd></div>
          <div><dt>Region from</dt><dd><?= h($S['region'] ?: '—') ?></dd></div>
          <div><dt>Date of birth</dt><dd><?= h(dfmt($S['dob'], 'j F Y')) ?></dd></div>
        </dl>
      </div>
    </div>
  </section>

  <!-- QUIZ RESULTS -->
  <section class="page" data-page="quizzes" data-title="Quiz Results">
    <div class="page-head"><h2>Quiz Results</h2><p>Scores for your quizzes this year. Pass mark is 50%.</p></div>
    <div class="grid g4" style="margin-bottom:16px">
      <div class="card stat"><div class="ic"><svg viewBox="0 0 24 24"><path d="M3 17l6-6 4 4 8-8"/></svg></div><span class="label">Average score</span><span class="value num"><?= $D['quiz_avg'] === null ? '—' : $D['quiz_avg'] . '<small>%</small>' ?></span><span class="note"><?= $D['quiz_taken'] ?> quiz<?= $D['quiz_taken'] === 1 ? '' : 'zes' ?> taken</span></div>
      <div class="card stat"><div class="ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg></div><span class="label">Current score</span>
<?php if ($D['quiz_current']): $qc = $D['quiz_current']; ?>
        <span class="value num"><?= (int)round($qc['score'] / $qc['out_of'] * 100) ?><small>%</small></span><span class="note"><?= h($qc['course']) ?> <?= h($qc['title']) ?> · <?= $qc['score'] ?>/<?= $qc['out_of'] ?></span>
<?php else: ?>
        <span class="value num">—</span><span class="note">No quiz taken yet</span>
<?php endif; ?></div>
      <div class="card stat"><div class="ic amber"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div><span class="label">Upcoming</span><span class="value num"><?= $D['quiz_upcoming'] ?></span><span class="note"><?= $D['quiz_next'] ? 'Next: ' . h(dfmt($D['quiz_next'], 'j M Y')) : 'Nothing scheduled' ?></span></div>
    </div>
    <div class="card">
      <h3>All quizzes</h3>
      <div class="tbl-wrap"><table>
        <thead><tr><th>Course</th><th>Quiz</th><th>Date</th><th class="r">Score</th><th class="r">Out of</th><th>Result</th></tr></thead>
        <tbody>
<?php foreach ($quizRows as $q): ?>
          <tr><td class="code"><?= h($q['course']) ?></td><td><?= h($q['title']) ?></td><td class="num"><?= h(dfmt($q['date'])) ?></td><td class="r num"><?= $q['score'] === null ? '-' : $q['score'] ?></td><td class="r num"><?= $q['out_of'] ?></td><td><?= quiz_pill($q) ?></td></tr>
<?php endforeach; ?>
<?php if (!$quizRows): ?>
          <tr><td colspan="6" class="hint" style="text-align:center;padding:24px">No quizzes recorded yet.</td></tr>
<?php endif; ?>
        </tbody>
      </table></div>
    </div>
  </section>

  <!-- FEES -->
  <section class="page" data-page="fees" data-title="Financial Hub · Fee Statement">
    <div class="page-head"><h2>Fee Statement</h2><p>Current year <?= h($F['year']) ?>. All amounts paid in Tanzanian shillings (TZS).</p></div>
    <div class="grid g4" style="margin-bottom:16px">
      <div class="card stat"><div class="ic"><svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20"/></svg></div><span class="label">Total billed</span><span class="value num"><?= n($D['billed']) ?></span></div>
      <div class="card stat"><div class="ic"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg></div><span class="label">Paid</span><span class="value num"><?= n($D['paid']) ?></span></div>
      <div class="card stat"><div class="ic amber"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div><span class="label">Outstanding</span><span class="value num"><?= n($D['outstanding']) ?></span><span class="note"><?= $D['outstanding'] > 0 ? ($F['due_date'] ? 'Due ' . h(dfmt($F['due_date'], 'j M Y')) : 'Payment pending') : 'Fully paid' ?></span></div>
    </div>
    <div class="card">
      <h3>Billing breakdown</h3>
      <div class="tbl-wrap"><table>
        <thead><tr><th>Item</th><th class="r">Amount</th><th>Status</th></tr></thead>
        <tbody>
          <tr><td>Meals</td><td class="r num"><?= n($F['meals_billed']) ?></td><td><span class="pill <?= $mealsSt[1] ?>"><?= h($mealsSt[0]) ?></span></td></tr>
          <tr><td>House accommodation</td><td class="r num"><?= n($F['accom_billed']) ?></td><td><span class="pill <?= $accomSt[1] ?>"><?= h($accomSt[0]) ?></span></td></tr>
          <tr><td><strong>Total</strong></td><td class="r num"><strong><?= n($D['billed']) ?></strong></td><td></td></tr>
        </tbody>
      </table></div>
      <div style="margin-top:18px">
        <div class="rowline"><span>Payment progress</span><strong class="num"><?= $D['pct'] ?>%</strong></div>
        <div class="bar<?= $D['pct'] < 100 ? ' amber' : '' ?>"><i style="width:<?= min(100, $D['pct']) ?>%"></i></div>
      </div>
    </div>
    <div class="card" style="margin-top:16px">
      <h3>How to pay</h3>
      <p class="hint" style="margin:0 0 12px">Use your registration number as the payment reference.</p>
      <dl class="kv">
        <div><dt>Control number</dt><dd class="num"><?= h($F['control_number'] ?: '—') ?></dd></div>
        <div><dt>Mobile money</dt><dd><?= h($F['mobile_money'] ?: '—') ?></dd></div>
        <div><dt>Banks</dt><dd><?= h($F['banks'] ?: '—') ?></dd></div>
      </dl>
    </div>
  </section>

  <!-- PAYMENTS -->
  <section class="page" data-page="payments" data-title="Financial Hub · Payment History">
    <div class="page-head"><h2>Payment History</h2><p>Payments received in <?= h($F['year']) ?>.</p></div>
    <div class="card">
      <div class="tbl-wrap"><table>
        <thead><tr><th>Date</th><th>Reference</th><th>Channel</th><th class="r">Amount (TZS)</th><th>Status</th><th>Receipt</th></tr></thead>
        <tbody>
<?php foreach ($payRows as $p): ?>
          <tr><td class="num"><?= h(dfmt($p['date'])) ?></td><td class="code"><?= h($p['ref']) ?></td><td><?= h($p['channel']) ?></td><td class="r num"><?= n($p['amount']) ?></td><td><span class="pill <?= $p['status'] === 'Confirmed' ? 'good' : 'warn' ?>"><?= h($p['status']) ?></span></td><td class="code"><?php if ($p['status'] === 'Confirmed' && ($p['receipt'] ?? '') !== ''): ?><a href="receipt_student-portal.php?no=<?= rawurlencode($p['receipt']) ?><?= $ADMIN_VIEW ? '&amp;as=' . rawurlencode((string)($_GET['as'] ?? '')) : '' ?>" target="_blank" rel="noopener"><?= h($p['receipt']) ?></a><?php else: ?>—<?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if (!$payRows): ?>
          <tr><td colspan="6" class="hint" style="text-align:center;padding:24px">No payments recorded yet.</td></tr>
<?php endif; ?>
          <tr><td colspan="3"><strong>Total received</strong></td><td class="r num"><strong><?= n($D['received']) ?></strong></td><td></td><td></td></tr>
        </tbody>
      </table></div>
    </div>
  </section>

  <!-- PASSWORD -->
  <section class="page" data-page="password" data-title="Change Password">
    <div class="page-head"><h2>Change Password</h2><p>Choose a password you do not use on any other site.</p></div>
    <div class="grid g2">
      <form class="card" id="pwForm" novalidate>
        <div class="field">
          <label for="cur">Current password</label>
          <div class="input-wrap"><input id="cur" type="password" autocomplete="current-password"><button type="button" class="eye" data-eye="cur" aria-label="Show password"><svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
          <span class="err" id="e-cur"></span>
        </div>
        <div class="field">
          <label for="new">New password</label>
          <div class="input-wrap"><input id="new" type="password" autocomplete="new-password"><button type="button" class="eye" data-eye="new" aria-label="Show password"><svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
          <div class="meter" id="meter" data-s="0"><i></i><i></i><i></i><i></i></div>
          <span class="err" id="e-new"></span>
        </div>
        <div class="field">
          <label for="conf">Confirm new password</label>
          <div class="input-wrap"><input id="conf" type="password" autocomplete="new-password"><button type="button" class="eye" data-eye="conf" aria-label="Show password"><svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg></button></div>
          <span class="err" id="e-conf"></span>
        </div>
        <div class="actions"><button class="btn" type="submit">Update password</button><button class="btn flat" type="reset" id="pwReset">Clear</button></div>
      </form>
      <div class="card">
        <h3>Password requirements</h3>
        <ul class="rules" id="rules">
          <li data-r="len">At least 8 characters</li>
          <li data-r="up">One uppercase letter</li>
          <li data-r="num">One number</li>
          <li data-r="sym">One symbol, for example ! # @</li>
        </ul>
        <p class="hint" style="margin:18px 0 0">After the change you stay signed in on this device. Other devices are signed out.</p>
      </div>
    </div>
  </section>
</main>

<div class="toast" id="toast" role="status"></div>

<script>
(function(){
  var CSRF=<?= json_encode((string)($_SESSION['csrf'] ?? '')) ?>;
  var $=function(s,r){return (r||document).querySelector(s)};
  var $$=function(s,r){return Array.prototype.slice.call((r||document).querySelectorAll(s))};
  var drawer=$('#drawer'),scrim=$('#scrim'),menuBtn=$('#menuBtn');
  var parentOf={fees:'finance',payments:'finance'};
  var pages=$$('.page').map(function(p){return p.getAttribute('data-page')});

  function openDrawer(o){
    drawer.classList.toggle('open',o);scrim.classList.toggle('open',o);
    menuBtn.setAttribute('aria-expanded',o);
    if(o){var a=$('#nav button.active')||$('#nav button');a&&a.focus()}else menuBtn.focus();
  }
  menuBtn.addEventListener('click',function(){openDrawer(!drawer.classList.contains('open'))});
  scrim.addEventListener('click',function(){openDrawer(false)});
  /* Swipe right from the left edge opens the menu; swipe left closes it */
  var sx=0,sy=0,tracking=false;
  document.addEventListener('touchstart',function(e){
    var t=e.touches[0];sx=t.clientX;sy=t.clientY;
    tracking=e.touches.length===1&&(drawer.classList.contains('open')||sx<=56);
  },{passive:true});
  document.addEventListener('touchend',function(e){
    if(!tracking)return;tracking=false;
    var t=e.changedTouches[0],dx=t.clientX-sx,dy=t.clientY-sy;
    if(Math.abs(dx)<60||Math.abs(dy)>Math.abs(dx)*0.6)return;
    var isOpen=drawer.classList.contains('open');
    if(dx>0&&!isOpen)openDrawer(true);
    else if(dx<0&&isOpen)openDrawer(false);
  },{passive:true});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&drawer.classList.contains('open'))openDrawer(false)});

  function toggleSub(key,force){
    var btn=$('[data-parent="'+key+'"]'),sub=$('#sub-'+key);
    var open=typeof force==='boolean'?force:!sub.classList.contains('open');
    sub.classList.toggle('open',open);btn.setAttribute('aria-expanded',open);
  }
  $$('[data-parent]').forEach(function(b){b.addEventListener('click',function(){toggleSub(b.getAttribute('data-parent'))})});

  function show(id){
    if(pages.indexOf(id)<0)id='dashboard';
    $$('.page').forEach(function(p){p.classList.toggle('show',p.getAttribute('data-page')===id)});
    var cur=$('.page.show');
    $('#pageTitle').textContent=cur.getAttribute('data-title');
    document.title='Student Portal';
    $$('#nav [data-go]').forEach(function(b){b.classList.toggle('active',b.getAttribute('data-go')===id)});
    $$('[data-parent]').forEach(function(b){b.classList.toggle('active',parentOf[id]===b.getAttribute('data-parent'))});
    if(parentOf[id])toggleSub(parentOf[id],true);
    window.scrollTo(0,0);
  }
  function go(id){
    try{history.replaceState(null,'','#'+id)}catch(e){}
    show(id);openDrawer(false);
  }
  $$('[data-go]').forEach(function(b){b.addEventListener('click',function(){go(b.getAttribute('data-go'))})});
  window.addEventListener('hashchange',function(){show(location.hash.slice(1))});
  show(location.hash.slice(1)||'dashboard');

  /* Current academic year (starts in September) */
  (function(){
    var d=new Date(),y=d.getFullYear(),st=d.getMonth()>=8?y:y-1;
    $$('[data-ay]').forEach(function(el){el.textContent=st+'/'+(st+1)});
  })();

  /* Logout */
  ['#logoutTop','#logoutBar'].forEach(function(sel){
    $(sel).addEventListener('click',function(){ try{sessionStorage.clear()}catch(err){} });
  });

  /* Dark mode */
  var themeBtn=$('#themeBtn'),root=document.documentElement;
  function isDark(){
    var t=root.getAttribute('data-theme');
    if(t)return t==='dark';
    return window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;
  }
  function paintTheme(){
    var d=isDark();
    $('#icoMoon').hidden=d;$('#icoSun').hidden=!d;
    themeBtn.setAttribute('aria-label',d?'Switch to light mode':'Switch to dark mode');
    themeBtn.title=d?'Light mode':'Dark mode';
  }
  try{var th=localStorage.getItem('portal-theme');if(th==='dark'||th==='light')root.setAttribute('data-theme',th)}catch(e){}
  paintTheme();
  themeBtn.addEventListener('click',function(){
    var next=isDark()?'light':'dark';
    root.setAttribute('data-theme',next);
    try{localStorage.setItem('portal-theme',next)}catch(e){}
    paintTheme();
  });

  /* Password form */
  var cur=$('#cur'),nw=$('#new'),conf=$('#conf'),meter=$('#meter');
  function checks(v){return{len:v.length>=8,up:/[A-Z]/.test(v),num:/[0-9]/.test(v),sym:/[^A-Za-z0-9]/.test(v)}}
  function paint(){
    var c=checks(nw.value),n=0;
    $$('#rules li').forEach(function(li){var ok=c[li.getAttribute('data-r')];li.classList.toggle('ok',ok);if(ok)n++});
    meter.setAttribute('data-s',nw.value?Math.max(1,n):0);
  }
  nw.addEventListener('input',paint);
  $$('[data-eye]').forEach(function(b){b.addEventListener('click',function(){
    var i=document.getElementById(b.getAttribute('data-eye'));
    var show=i.type==='password';i.type=show?'text':'password';
    b.setAttribute('aria-label',show?'Hide password':'Show password');
  })});
  function toast(m){var t=$('#toast');t.textContent=m;t.classList.add('show');clearTimeout(toast.t);toast.t=setTimeout(function(){t.classList.remove('show')},3200)}
  $('#pwReset').addEventListener('click',function(){setTimeout(function(){paint();['cur','new','conf'].forEach(function(k){$('#e-'+k).textContent=''})},0)});
  $('#pwForm').addEventListener('submit',function(e){
    e.preventDefault();
    var bad=false,c=checks(nw.value);
    $('#e-cur').textContent=cur.value?'':'Enter your current password.';
    if(!cur.value)bad=true;
    if(!(c.len&&c.up&&c.num&&c.sym)){$('#e-new').textContent='The new password does not meet all requirements.';bad=true}
    else if(nw.value===cur.value){$('#e-new').textContent='The new password must differ from the current one.';bad=true}
    else $('#e-new').textContent='';
    if(conf.value!==nw.value||!conf.value){$('#e-conf').textContent='The two passwords do not match.';bad=true}
    else $('#e-conf').textContent='';
    if(bad)return;
    var form=e.target,btn=form.querySelector('button[type=submit]');if(btn)btn.disabled=true;
    fetch('change_password_student-portal.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({current:cur.value,'new':nw.value})})
      .then(function(r){return r.json().then(function(j){if(!r.ok)throw new Error(j.error||'Error');return j})})
      .then(function(){form.reset();paint();toast('Password updated.')})
      .catch(function(err){$('#e-cur').textContent=err.message;toast(err.message)})
      .then(function(){if(btn)btn.disabled=false});
  });
})();
</script>

</body></html>