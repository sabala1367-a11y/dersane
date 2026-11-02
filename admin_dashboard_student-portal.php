<?php
/**
 * admin_dashboard_student-portal.php — Dashboard ya Admin (glassmorphism)
 * Mabadiliko yanayohifadhiwa hapa yanaonekana moja kwa moja kwenye Student_Portal_student-portal.php
 */
require __DIR__ . '/config_student-portal.php';
// Admin HAANA login: dashboard inafunguka tu kutoka IP zilizoruhusiwa (SP_ADMIN_IPS kwenye config_student-portal.php). login_student-portal.php ni ya wanafunzi tu.
sp_require_admin();
?>
<!doctype html>
<html lang="sw">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Source+Sans+3:wght@400;500;600&display=swap">
<style>
:root{
  --f-display:'Outfit','Trebuchet MS',sans-serif;
  --f-body:'Source Sans 3','Segoe UI',Arial,sans-serif;
  --fg:#F2FBF9; --muted:rgba(230,245,242,.68);
  --glass:rgba(255,255,255,.10); --glass-2:rgba(255,255,255,.16); --glass-line:rgba(255,255,255,.26);
  --input:rgba(255,255,255,.10);
  --accent:#4FE3CF; --on-accent:#032621; --amber:#FFC46B; --good:#7BE8A0; --bad:#FF9A93;
  --blur:blur(22px) saturate(160%);
  --shadow:0 8px 32px rgba(0,10,15,.35);
}
*{box-sizing:border-box}
html,body{margin:0;min-height:100%}
body{font-family:var(--f-body);font-size:16px;line-height:1.5;color:var(--fg);
  background:#06231f;min-height:100vh;overflow-x:hidden}
/* Mandharinyuma yenye rangi zinazotembea ili glass ionekane */
.bg{position:fixed;inset:0;z-index:-1;overflow:hidden;
  background:radial-gradient(120% 90% at 10% 0%,#0b6e63 0%,#06322e 45%,#041a22 100%)}
.bg i{position:absolute;border-radius:50%;filter:blur(70px);opacity:.75;animation:drift 22s ease-in-out infinite alternate}
.bg i:nth-child(1){width:46vmax;height:46vmax;background:#19c3a9;left:-12vmax;top:-10vmax}
.bg i:nth-child(2){width:38vmax;height:38vmax;background:#2f6bff;right:-10vmax;top:18vh;animation-delay:-6s;opacity:.55}
.bg i:nth-child(3){width:34vmax;height:34vmax;background:#ffb347;left:30vw;bottom:-16vmax;animation-delay:-12s;opacity:.4}
@keyframes drift{to{transform:translate(6vmax,4vmax) scale(1.12)}}
@media (prefers-reduced-motion:reduce){.bg i{animation:none}*{transition:none!important}}

h1,h2,h3{font-family:var(--f-display);margin:0;line-height:1.2}
button,input,select{font:inherit;color:inherit}
:focus-visible{outline:3px solid var(--amber);outline-offset:2px}
.num{font-variant-numeric:tabular-nums}

.glass{background:var(--glass);backdrop-filter:var(--blur);-webkit-backdrop-filter:var(--blur);
  border:1px solid var(--glass-line);border-radius:20px;box-shadow:var(--shadow), inset 0 1px 0 rgba(255,255,255,.22)}

/* Login */
.login-wrap{min-height:100vh;display:grid;place-items:center;padding:20px}
.login{width:min(400px,100%);padding:32px 28px}
.login .crest{width:52px;height:52px;border-radius:14px;background:var(--accent);color:var(--on-accent);display:grid;place-items:center;font-family:var(--f-display);font-weight:700;font-size:1.2rem;margin-bottom:16px}
.login h1{font-size:1.6rem}
.login p{color:var(--muted);margin:6px 0 22px}

/* Layout */
.shell{display:grid;grid-template-columns:320px minmax(0,1fr);gap:18px;padding:18px;min-height:100vh;
  padding-left:max(18px,env(safe-area-inset-left));padding-right:max(18px,env(safe-area-inset-right))}
.side{display:flex;flex-direction:column;gap:14px;padding:18px;position:sticky;top:18px;height:calc(100vh - 36px)}
.brand{display:flex;align-items:center;gap:12px}
.brand .crest{width:42px;height:42px;border-radius:12px;background:var(--accent);color:var(--on-accent);display:grid;place-items:center;font-family:var(--f-display);font-weight:700}
.brand strong{font-family:var(--f-display);font-size:1.1rem;display:block;line-height:1.1}
.brand small{color:var(--muted)}
.search{width:100%;height:44px;border-radius:12px;border:1px solid var(--glass-line);background:var(--input);padding:0 14px}
.search::placeholder{color:var(--muted)}
.slist{list-style:none;margin:0;padding:0;overflow-y:auto;display:flex;flex-direction:column;gap:8px;flex:1;min-height:0}
.sitem{width:100%;text-align:left;display:flex;gap:12px;align-items:center;padding:10px 12px;border-radius:14px;border:1px solid transparent;background:transparent;cursor:pointer}
.sitem:hover{background:var(--glass)}
.sitem.on{background:var(--glass-2);border-color:var(--glass-line)}
.av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#2f9bff);color:var(--on-accent);display:grid;place-items:center;font-family:var(--f-display);font-weight:700;font-size:.9rem;flex:none}
.av{overflow:hidden}
.av img{width:100%;height:100%;object-fit:cover;display:block}
.photo-row{display:flex;gap:18px;align-items:center;flex-wrap:wrap;margin-bottom:22px;padding-bottom:20px;border-bottom:1px solid var(--glass-line)}
.bigav{width:96px;height:96px;font-size:1.8rem}
.photo-row .acts2{display:flex;gap:8px;flex-wrap:wrap}
.photo-row p{margin:6px 0 0;color:var(--muted);font-size:.88rem}
.sitem b{display:block;font-weight:600;line-height:1.2}
.sitem small{color:var(--muted)}
.mini{height:5px;border-radius:3px;background:rgba(255,255,255,.14);overflow:hidden;margin-top:6px}
.mini i{display:block;height:100%;background:var(--amber)}
.mini i.full{background:var(--good)}
.side-foot{display:flex;gap:8px}

.btn{height:44px;padding:0 20px;border-radius:12px;border:1px solid var(--glass-line);background:var(--glass-2);cursor:pointer;font-weight:600;white-space:nowrap}
.btn:hover{background:rgba(255,255,255,.24)}
.btn.primary{background:var(--accent);color:var(--on-accent);border-color:transparent}
.btn.primary:hover{filter:brightness(1.08)}
.btn.danger{color:var(--bad)}
.btn.sm{height:36px;padding:0 14px;font-size:.9rem}
.btn:disabled{opacity:.5;cursor:not-allowed}
.btn.block{width:100%}

.main{display:flex;flex-direction:column;gap:18px;min-width:0}
.top{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;flex-wrap:wrap}
.top h2{font-size:1.4rem}
.top p{margin:2px 0 0;color:var(--muted);font-size:.92rem}
.top .acts{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.dirty{color:var(--amber);font-weight:600;font-size:.88rem}
.menu-btn{display:none}

.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px}
.stat{padding:16px 18px}
.stat span{display:block;color:var(--muted);font-size:.78rem;font-weight:600;letter-spacing:.07em;text-transform:uppercase}
.stat strong{font-family:var(--f-display);font-size:1.7rem;font-weight:600}
.stat small{color:var(--muted)}

.tabs{display:flex;gap:6px;padding:6px;overflow-x:auto}
.tabs button{border:0;background:transparent;height:42px;padding:0 18px;border-radius:14px;cursor:pointer;font-weight:600;color:var(--muted);white-space:nowrap}
.tabs button.on{background:var(--glass-2);color:var(--fg);box-shadow:inset 0 1px 0 rgba(255,255,255,.25)}

.panel{padding:22px}
.panel h3{font-size:1.05rem;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;gap:10px}
.panel + .panel{margin-top:0}
.form{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px 18px}
.fld{display:flex;flex-direction:column;gap:6px;min-width:0}
.fld label{font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:600}
.fld input,.fld select,.cell{height:44px;border-radius:12px;border:1px solid var(--glass-line);background:var(--input);padding:0 12px;width:100%;min-width:0}
.fld select option{color:#111}
.fld input:focus,.fld select:focus,.cell:focus{outline:none;border-color:var(--accent);background:rgba(79,227,207,.14)}
input[type=date]{color-scheme:dark}
.sep{grid-column:1/-1;margin:6px 0 -4px;color:var(--accent);font-family:var(--f-display);font-weight:600;font-size:.95rem}

.tbl{overflow-x:auto}
table{width:100%;border-collapse:separate;border-spacing:0 8px;min-width:640px}
th{text-align:left;font-size:.74rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:600;padding:0 6px 2px}
td{padding:0 6px}
.cell{height:40px}
.pill{display:inline-block;padding:3px 12px;border-radius:12px;font-size:.78rem;font-weight:700;white-space:nowrap}
.pill.good{background:rgba(123,232,160,.2);color:var(--good)}
.pill.warn{background:rgba(255,196,107,.2);color:var(--amber)}
.pill.bad{background:rgba(255,154,147,.2);color:var(--bad)}
.pill.info{background:rgba(79,227,207,.2);color:var(--accent)}
.x{width:36px;height:36px;border-radius:10px;border:1px solid var(--glass-line);background:transparent;cursor:pointer;color:var(--bad);font-size:1.1rem}
.x:hover{background:rgba(255,154,147,.16)}
.note{color:var(--muted);font-size:.9rem;margin:12px 0 0}
.note.warn{color:var(--amber)}
.sumbar{display:flex;flex-wrap:wrap;gap:8px 24px;margin-top:14px;padding:12px 16px;border-radius:14px;background:rgba(255,255,255,.07);border:1px solid var(--glass-line)}
.sumbar span{color:var(--muted)}
.empty{padding:60px 20px;text-align:center;color:var(--muted)}

.toast{position:fixed;left:50%;bottom:calc(22px + env(safe-area-inset-bottom));transform:translate(-50%,30px);
  padding:12px 22px;border-radius:14px;opacity:0;pointer-events:none;transition:.25s;z-index:100;max-width:calc(100vw - 32px)}
.toast.show{opacity:1;transform:translate(-50%,0)}
.toast.err{color:var(--bad)}
.scrim{display:none}

@media (max-width:900px){
  .shell{grid-template-columns:1fr;padding:12px}
  .side{position:fixed;z-index:60;top:12px;bottom:12px;left:12px;width:min(340px,calc(100vw - 24px));height:auto;transform:translateX(-120%);transition:transform .22s;background:rgba(8,40,44,.82)}
  .side.open{transform:none}
  .scrim.open{display:block;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:55}
  .menu-btn{display:inline-block}
}
</style>
</head>
<body>
<div class="bg" aria-hidden="true"><i></i><i></i><i></i></div>



<div class="scrim" id="scrim"></div>
<div class="shell">
  <aside class="glass side" id="side" aria-label="Orodha ya wanafunzi">
    <div class="brand"><div class="crest">SP</div><div><strong>Student Admin</strong><small id="countLbl">—</small></div></div>
    <input class="search" id="q" type="search" placeholder="Tafuta jina au namba…" aria-label="Tafuta mwanafunzi">
    <ul class="slist" id="slist"></ul>
    <div class="side-foot">
      <button class="btn primary" id="newBtn" style="flex:1">+ Mwanafunzi mpya</button>
    </div>
  </aside>

  <section class="main">
    <div class="stats">
      <div class="glass stat"><span>Wanafunzi</span><strong class="num" id="stN">0</strong></div>
      <div class="glass stat"><span>Jumla ya ada</span><strong class="num" id="stB">0</strong><small> TZS</small></div>
      <div class="glass stat"><span>Zilizolipwa</span><strong class="num" id="stP">0</strong><small> TZS</small></div>
      <div class="glass stat"><span>Zinazodaiwa</span><strong class="num" id="stO">0</strong><small> TZS</small></div>
    </div>

    <div class="glass top">
      <div style="display:flex;gap:12px;align-items:center;min-width:0">
        <button class="btn sm menu-btn" id="menuBtn" aria-label="Fungua orodha">☰</button>
        <div style="min-width:0"><h2 id="curName">Chagua mwanafunzi</h2><p id="curSub">Mabadiliko yanaonekana kwenye Student Portal mara tu ukihifadhi.</p></div>
      </div>
      <div class="acts">
        <span class="dirty" id="dirty" hidden>● Mabadiliko hayajahifadhiwa</span>
        <button class="btn sm" id="viewBtn" title="Ingia kwenye akaunti ya mwanafunzi huyu kama yeye mwenyewe">Ingia kwenye akaunti ↗</button>
        <button class="btn sm danger" id="delBtn" hidden>Futa</button>
        <button class="btn primary" id="saveBtn" disabled>Hifadhi</button>
        <a class="btn sm" href="admin-logout.php" style="display:inline-flex;align-items:center;text-decoration:none;color:inherit">Toka</a>
      </div>
    </div>

    <div class="glass tabs" id="tabs" role="tablist" hidden>
      <button data-t="profile" class="on" role="tab">Wasifu</button>
      <button data-t="fees" role="tab">Ada &amp; Mpango</button>
      <button data-t="payments" role="tab">Malipo</button>
      <button data-t="quizzes" role="tab">Quiz</button>
    </div>

    <div class="glass panel" id="panel"><div class="empty">Chagua mwanafunzi upande wa kushoto, au bonyeza “+ Mwanafunzi mpya”.</div></div>
  </section>
</div>
<div class="toast glass" id="toast" role="status"></div>

<script>
(function(){
  var CSRF=<?= json_encode($_SESSION['csrf']) ?>;
  var $=function(s,r){return (r||document).querySelector(s)};
  var $$=function(s,r){return Array.prototype.slice.call((r||document).querySelectorAll(s))};
  var esc=function(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})};
  var fmt=function(n){return (Number(n)||0).toLocaleString('en-US')};

  var FEE_DEF={meals:200000,accom:100000,meals_billed:0,accom_billed:0},feeBase=null,list=[],cur=null,origReg='',tab='profile',dirty=false,isNew=false,curPhoto='';

  var PROFILE=[
    ['sep','Utambulisho'],
    ['reg_no','Namba ya usajili (inatengenezwa otomatiki)','readonly'],['full_name','Jina kamili','text'],['display_name','Jina la kuonyesha (mf. Mikidadi I. Sabala)','text'],
    ['status','Hali','text'],['gender','Jinsia','select',['Male','Female']],['dob','Tarehe ya kuzaliwa','date'],
    ['sep','Nyumba & Masomo'],
    ['house','Jina la nyumba','text'],['supervisor','Msimamizi','text'],['year','Mwaka wa sasa (mf. Year 2)','text'],
    ['admitted','Mwaka wa kujiunga (mf. 2025/2026)','text'],['university','Chuo','text'],['region','Mkoa anakotoka','text'],
    ['sep','Mawasiliano'],
    ['address','Anwani','text'],['email','Barua pepe (pia ni jina la kuingia)','email'],['phone','Simu','text'],
    ['sep','Kuingia kwenye login_student-portal.php: jina = barua pepe, nenosiri = namba ya usajili (inayotengenezwa na server)'],
    ['kin_name','Jina la ndugu wa karibu','text'],['kin_rel','Uhusiano','text'],['kin_phone','Simu ya ndugu','text']
  ];
  var FEES=[
    ['sep','Mpango wa kila semester (Payment Clearance Plan)'],
    ['plan.meals','Chakula / semester (TZS) — wote','money'],['plan.accom','Malazi / semester (TZS) — wote','money'],
    ['sep','Ada ya mwaka'],
    ['fees.year','Mwaka wa ada','text'],['fees.due_date','Mwisho wa kulipa','date'],['fees.control_number','Control number','text'],
    ['fees.meals_billed','Chakula — kiasi cha kudaiwa — wote','money'],['fees.meals_paid','Chakula — kilicholipwa (inajazwa otomatiki)','readmoney'],
    ['fees.accom_billed','Malazi — kiasi cha kudaiwa — wote','money'],['fees.accom_paid','Malazi — kilicholipwa (inajazwa otomatiki)','readmoney'],
    ['sep','Njia za kulipa'],
    ['fees.mobile_money','Mobile money','text'],['fees.banks','Benki','text']
  ];

  function avHtml(photo,initials,extra){
    return '<div class="av '+(extra||'')+'">'+(photo?'<img src="'+esc(photo)+'" alt="">':esc(initials||'?'))+'</div>';
  }
  function photoBlock(){
    if(isNew)return '<div class="photo-row">'+avHtml('','?','bigav')+'<div><b>Picha ya mwanafunzi</b><p>Hifadhi mwanafunzi kwanza, kisha utaweza kupakia picha hapa.</p></div></div>';
    var ini=(cur.display_name||cur.full_name||'?').split(/\s+/).map(function(w,i,a){return (i===0||i===a.length-1)?w.charAt(0).toUpperCase():''}).join('');
    return '<div class="photo-row">'+avHtml(curPhoto,ini,'bigav')+'<div><b>Picha ya mwanafunzi</b>'+
      '<div class="acts2" style="margin-top:8px"><button class="btn sm primary" id="pickPhoto" type="button">'+(curPhoto?'Badilisha picha':'Pakia picha')+'</button>'+
      (curPhoto?'<button class="btn sm danger" id="rmPhoto" type="button">Ondoa</button>':'')+
      '<input type="file" id="photoFile" accept="image/jpeg,image/png,image/webp" hidden></div>'+
      '<p>JPG, PNG au WEBP, hadi 8MB. Hupakiwa mara moja na huonekana kwenye Student Portal.</p></div></div>';
  }
  function uploadPhoto(file){
    if(!file||!cur||isNew)return;
    if(file.size>8*1024*1024){toast('Picha isizidi 8MB.',true);return}
    var fd=new FormData();fd.append('reg',origReg);fd.append('photo',file);
    var b=$('#pickPhoto');if(b){b.disabled=true;b.textContent='Inapakia…'}
    fetch('api_student-portal.php?action=upload_photo',{method:'POST',headers:{'X-CSRF-Token':CSRF},credentials:'same-origin',body:fd})
      .then(function(r){return r.json().then(function(j){if(!r.ok)throw new Error(j.error||'Hitilafu');return j})})
      .then(function(j){curPhoto=j.photo_url||'';toast('Picha imepakiwa. Student Portal imesasishwa.');renderPanel();return loadList()})
      .catch(function(e){toast(e.message,true);renderPanel()});
  }

  function getv(o,p){return p.split('.').reduce(function(a,k){return a?a[k]:undefined},o)}
  function setv(o,p,v){var k=p.split('.'),l=k.pop(),t=k.reduce(function(a,x){return a[x]=a[x]||{}},o);t[l]=v}

  function blank(){return {reg_no:'',full_name:'',display_name:'',status:'Active house member',house:'',supervisor:'',year:'',admitted:'',gender:'Male',university:'',region:'',dob:'',address:'',email:'',phone:'',kin_name:'',kin_rel:'',kin_phone:'',
    plan:{meals:FEE_DEF.meals,accom:FEE_DEF.accom},
    fees:{year:String(new Date().getFullYear()),due_date:'',control_number:'',mobile_money:'M-Pesa, Tigo Pesa, Airtel Money',banks:'CRDB, NMB',meals_billed:FEE_DEF.meals_billed,meals_paid:0,accom_billed:FEE_DEF.accom_billed,accom_paid:0},
    payments:[],quizzes:[]}}

  function toast(m,err){var t=$('#toast');t.textContent=m;t.classList.toggle('err',!!err);t.classList.add('show');clearTimeout(toast.t);toast.t=setTimeout(function(){t.classList.remove('show')},3400)}

  function api(action,opt){
    opt=opt||{};
    var init={method:opt.body?'POST':'GET',headers:{'X-CSRF-Token':CSRF},credentials:'same-origin'};
    if(opt.body){init.headers['Content-Type']='application/json';init.body=JSON.stringify(opt.body)}
    return fetch('api_student-portal.php?action='+action+(opt.q||''),init).then(function(r){
      return r.json().then(function(j){if(!r.ok){if(r.status===401){location.reload()}throw new Error(j.error||'Hitilafu')}return j});
    });
  }

  function setDirty(d){
    dirty=d;$('#dirty').hidden=!d;$('#saveBtn').disabled=!cur||!d;
  }

  /* ---------- Orodha ---------- */
  function renderList(){
    var q=$('#q').value.trim().toLowerCase();
    var rows=list.filter(function(s){return !q||(s.display_name+' '+s.reg_no).toLowerCase().indexOf(q)>=0});
    $('#slist').innerHTML=rows.length?rows.map(function(s){
      return '<li><button class="sitem'+(!isNew&&s.reg_no===origReg?' on':'')+'" data-reg="'+esc(s.reg_no)+'">'+avHtml(s.photo,s.initials,'')+'<div style="min-width:0;flex:1"><b>'+esc(s.display_name||s.reg_no)+'</b><small>'+esc(s.reg_no)+' · '+esc(s.house||'—')+'</small><div class="mini"><i class="'+(s.pct>=100?'full':'')+'" style="width:'+Math.min(100,s.pct)+'%"></i></div></div></button></li>';
    }).join(''):'<li class="empty" style="padding:30px 10px">Hakuna mwanafunzi.</li>';
    $('#countLbl').textContent=list.length+' wanafunzi';
    var t={b:0,p:0,o:0};list.forEach(function(s){t.b+=s.billed;t.p+=s.paid;t.o+=s.outstanding});
    $('#stN').textContent=list.length;$('#stB').textContent=fmt(t.b);$('#stP').textContent=fmt(t.p);$('#stO').textContent=fmt(t.o);
  }
  function loadList(){return api('list').then(function(j){list=j.students;if(j.fee_defaults)FEE_DEF=j.fee_defaults;renderList()})}
  function setBase(){feeBase={meals:cur.plan.meals,accom:cur.plan.accom,meals_billed:cur.fees.meals_billed,accom_billed:cur.fees.accom_billed}}
  function feeChanged(){
    if(!cur||!feeBase)return [];
    var L=[];
    if(cur.plan.meals!==feeBase.meals)L.push('Chakula / semester');
    if(cur.plan.accom!==feeBase.accom)L.push('Malazi / semester');
    if(cur.fees.meals_billed!==feeBase.meals_billed)L.push('Chakula — kiasi cha kudaiwa');
    if(cur.fees.accom_billed!==feeBase.accom_billed)L.push('Malazi — kiasi cha kudaiwa');
    return L;
  }

  function confirmLeave(){return !dirty||confirm('Una mabadiliko ambayo hayajahifadhiwa. Endelea bila kuhifadhi?')}

  function openStudent(reg){
    if(!confirmLeave())return;
    api('get',{q:'&reg='+encodeURIComponent(reg)}).then(function(j){
      cur=j.student;curPhoto=j.photo_url||'';origReg=cur.reg_no;isNew=false;tab='profile';setBase();setDirty(false);renderAll();closeSide();
    }).catch(function(e){toast(e.message,true)});
  }
  function newStudent(){
    if(!confirmLeave())return;
    cur=blank();setBase();curPhoto='';origReg='';isNew=true;tab='profile';setDirty(false);renderAll();closeSide();
  }

  /* ---------- Mwonekano wa mwanafunzi ---------- */
  function renderAll(){
    $('#tabs').hidden=!cur;
    $('#delBtn').hidden=!cur||isNew;
    $('#viewBtn').hidden=!cur||isNew;
    if(!cur){$('#panel').innerHTML='<div class="empty">Chagua mwanafunzi upande wa kushoto.</div>';return}
    $('#curName').textContent=isNew?'Mwanafunzi mpya':(cur.display_name||cur.full_name);
    $('#curSub').textContent=isNew?'Jaza taarifa kisha bonyeza Hifadhi.':(cur.reg_no+' · '+(cur.house||'—')+' · '+(cur.year||''));
    $$('#tabs button').forEach(function(b){b.classList.toggle('on',b.getAttribute('data-t')===tab)});
    renderList();renderPanel();
  }

  function field(f,pathPrefix){
    var k=f[0],label=f[1],type=f[2],v=getv(cur,k);
    if(k==='sep')return '<div class="sep">'+esc(label)+'</div>';
    var id='f_'+k.replace('.','_');
    var inp;
    if(type==='readonly'){inp='<input id="'+id+'" data-k="'+k+'" value="'+esc(isNew?'Itatengenezwa ukihifadhi':v)+'" readonly disabled style="opacity:.8;font-weight:600">'}
    else if(type==='readmoney'){inp='<input id="'+id+'" data-k="'+k+'" class="num" value="'+esc(fmt(v))+'" readonly disabled style="opacity:.85">'}
    else if(type==='select'){inp='<select id="'+id+'" data-k="'+k+'">'+f[3].map(function(o){return '<option'+(o===v?' selected':'')+'>'+esc(o)+'</option>'}).join('')+'</select>'}
    else if(type==='money'){inp='<input id="'+id+'" data-k="'+k+'" data-money="1" inputmode="numeric" class="num" value="'+esc(fmt(v))+'">'}
    else if(type==='password'){inp='<input id="'+id+'" data-k="'+k+'" type="password" autocomplete="new-password" value="">'}
    else inp='<input id="'+id+'" data-k="'+k+'" type="'+type+'" value="'+esc(v)+'">';
    return '<div class="fld"><label for="'+id+'">'+esc(label)+'</label>'+inp+'</div>';
  }

  function recomputePaid(){
    var recv=cur.payments.reduce(function(a,p){return a+(p.status==='Confirmed'?(p.amount||0):0)},0);
    var mb=cur.fees.meals_billed||0,ab=cur.fees.accom_billed||0,m=Math.min(recv,mb);
    cur.fees.meals_paid=m;cur.fees.accom_paid=Math.min(recv-m,ab);
  }
  function feeSummary(){
    recomputePaid();
    var f=cur.fees,billed=f.meals_billed+f.accom_billed,paid=f.meals_paid+f.accom_paid,out=Math.max(0,billed-paid);
    var recv=cur.payments.reduce(function(a,p){return a+(p.status==='Confirmed'?p.amount:0)},0);
    return {billed:billed,paid:paid,out:out,pct:billed?Math.round(paid/billed*100):0,recv:recv};
  }

  function payRow(r,i){
    var rc=r.receipt?'<a class="code" href="receipt_student-portal.php?no='+encodeURIComponent(r.receipt)+'&reg='+encodeURIComponent(origReg)+'" target="_blank" rel="noopener">'+esc(r.receipt)+'</a>':'—';
    var st=r.status==='Confirmed'?'<span class="pill good">Confirmed'+(r.source==='auto'?' · Auto':'')+'</span>':'<span class="pill warn">'+esc(r.status)+'</span>';
    return '<tr data-i="'+i+'"><td>'+esc(r.date)+' '+esc(r.time||'')+'</td><td>'+rc+'</td><td>'+esc(r.ref)+'</td><td>'+esc(r.channel)+'</td><td class="num">'+fmt(r.amount)+'</td><td>'+st+'</td></tr>';
  }

  function renderPanel(){
    var p=$('#panel'),h='';
    if(tab==='profile'){
      h='<h3>Wasifu wa mwanafunzi</h3>'+photoBlock()+'<div class="form">'+PROFILE.map(field).join('')+'</div>';
    }else if(tab==='fees'){
      h='<h3>Ada &amp; Mpango wa malipo</h3><p class="note">Chakula na malazi (mpango na kiasi cha kudaiwa) vinatumika kwa wanafunzi WOTE: ukibadilisha na kuhifadhi, vinabadilika kwa kila mwanafunzi. Control number na njia za kulipa ni za mwanafunzi mmoja mmoja.</p><div class="form">'+FEES.map(field).join('')+'</div><div class="sumbar" id="feeSum"></div><p class="note" id="feeWarn"></p>';
    }else if(tab==='payments'){
      h='<h3>Historia ya malipo</h3><p class="note">Malipo yanaingia yenyewe kutoka benki / mobile money pamoja na risiti. Admin anaweza kuyaona tu — hawezi kuyabadilisha, kuyaongeza wala kuyafuta.</p><div class="tbl"><table><thead><tr><th>Tarehe</th><th>Risiti</th><th>Reference</th><th>Njia</th><th>Kiasi (TZS)</th><th>Hali</th></tr></thead><tbody>'+
        (cur.payments.map(payRow).join('')||'<tr><td colspan="6" class="empty" style="padding:30px">Hakuna malipo bado.</td></tr>')+
        '</tbody></table></div><div class="sumbar" id="paySum"></div>';
    }else{
      h='<h3>Matokeo ya Quiz <button class="btn sm" id="addQuiz">+ Ongeza quiz</button></h3><div class="tbl"><table><thead><tr><th>Kozi</th><th>Jina la quiz</th><th>Tarehe</th><th>Alama</th><th>Kati ya</th><th>Hali</th><th></th></tr></thead><tbody>'+
        (cur.quizzes.map(function(r,i){
          return '<tr data-i="'+i+'"><td><input class="cell" data-c="course" value="'+esc(r.course)+'" style="min-width:80px"></td><td><input class="cell" data-c="title" value="'+esc(r.title)+'"></td><td><input class="cell" type="date" data-c="date" value="'+esc(r.date)+'" style="color-scheme:dark"></td><td><input class="cell num" inputmode="numeric" data-c="score" placeholder="—" value="'+esc(r.score==null?'':r.score)+'" style="min-width:70px"></td><td><input class="cell num" inputmode="numeric" data-c="out_of" value="'+esc(r.out_of)+'" style="min-width:70px"></td><td data-st>'+quizPill(r)+'</td><td><button class="x" data-del="quiz" aria-label="Futa safu">✕</button></td></tr>';
        }).join('')||'<tr><td colspan="7" class="empty" style="padding:30px">Hakuna quiz bado.</td></tr>')+
        '</tbody></table></div><p class="note">Acha “Alama” wazi kwa quiz inayokuja (Upcoming). Alama ya kufaulu ni 50%.</p>';
    }
    p.innerHTML=h;
    if(tab==='fees')paintFee();
    if(tab==='payments')paintPay();
  }

  function quizPill(r){
    if(r.score==null)return '<span class="pill warn">Upcoming</span>';
    var pc=r.score/r.out_of*100;
    return pc<50?'<span class="pill bad">Failed</span>':(pc>75?'<span class="pill good">Passed</span>':'<span class="pill info">Passed</span>');
  }
  function paintFee(){
    var s=feeSummary();
    $('#feeSum').innerHTML='<div><span>Jumla ya kudaiwa:</span> <strong class="num">'+fmt(s.billed)+'</strong></div><div><span>Imelipwa:</span> <strong class="num">'+fmt(s.paid)+'</strong></div><div><span>Inayodaiwa:</span> <strong class="num">'+fmt(s.out)+'</strong></div><div><span>Asilimia:</span> <strong class="num">'+s.pct+'%</strong></div>';
    var pm=$('#f_fees_meals_paid'),pa=$('#f_fees_accom_paid');
    if(pm)pm.value=fmt(cur.fees.meals_paid);
    if(pa)pa.value=fmt(cur.fees.accom_paid);
    var w=$('#feeWarn');
    if(s.recv>s.paid){w.className='note warn';w.textContent='Malipo yaliyopokelewa ('+fmt(s.recv)+') yanazidi kiasi kilichogawiwa kwa madeni ('+fmt(s.paid)+'). Weka "kiasi cha kudaiwa" ili yagawanywe (chakula kwanza, kisha malazi).'}
    else{w.className='note';w.textContent='Kiasi kilicholipwa kinajazwa otomatiki kutoka kwenye malipo yaliyothibitishwa (Malipo).'}
  }
  function paintPay(){
    var s=feeSummary();
    $('#paySum').innerHTML='<div><span>Jumla iliyothibitishwa:</span> <strong class="num">'+fmt(s.recv)+' TZS</strong></div>';
  }

  /* ---------- Matukio ---------- */
  document.addEventListener('input',function(e){
    var t=e.target;if(!cur)return;
    var v=t.value;
    if(t.hasAttribute('data-money')){var n=parseInt(v.replace(/\D/g,''),10)||0;var pos=t.selectionStart;t.value=fmt(n);v=n}
    if(t.hasAttribute('data-k')){
      var k=t.getAttribute('data-k');
      setv(cur,k,t.hasAttribute('data-money')?v:t.value);
      if(k==='display_name'||k==='full_name'){$('#curName').textContent=cur.display_name||cur.full_name||'Mwanafunzi mpya'}
      setDirty(true);
      if(tab==='fees')paintFee();
    }else if(t.hasAttribute('data-c')){
      var tr=t.closest('tr'),i=+tr.getAttribute('data-i'),c=t.getAttribute('data-c'),row=(tab==='payments'?cur.payments:cur.quizzes)[i];
      if(c==='amount')row[c]=v;
      else if(c==='score')row[c]=t.value.trim()===''?null:(parseInt(t.value.replace(/\D/g,''),10)||0);
      else if(c==='out_of')row[c]=Math.max(1,parseInt(t.value.replace(/\D/g,''),10)||1);
      else row[c]=t.value;
      if(tab==='quizzes')tr.querySelector('[data-st]').innerHTML=quizPill(row);
      if(tab==='payments')paintPay();
      setDirty(true);
    }
  });
  document.addEventListener('click',function(e){
    var t=e.target;
    var si=t.closest('.sitem');if(si){openStudent(si.getAttribute('data-reg'));return}
    var tb=t.closest('#tabs button');if(tb){tab=tb.getAttribute('data-t');renderAll();return}
    if(t.id==='addPay'){cur.payments.unshift({date:new Date().toISOString().slice(0,10),ref:'',channel:'Cash',amount:0,status:'Confirmed',receipt:'',source:'manual',time:''});setDirty(true);renderPanel();return}
    if(t.id==='addQuiz'){cur.quizzes.unshift({course:'',title:'',date:'',score:null,out_of:20});setDirty(true);renderPanel();return}
    if(t.id==='pickPhoto'){$('#photoFile').click();return}
    if(t.id==='rmPhoto'){
      if(!confirm('Ondoa picha ya mwanafunzi huyu?'))return;
      api('remove_photo',{body:{reg:origReg}}).then(function(){curPhoto='';toast('Picha imeondolewa.');renderPanel();return loadList()}).catch(function(e){toast(e.message,true)});
      return;
    }
    var d=t.closest('[data-del]');
    if(d){var i=+d.closest('tr').getAttribute('data-i');(d.getAttribute('data-del')==='pay'?cur.payments:cur.quizzes).splice(i,1);setDirty(true);renderPanel();return}
  });

  document.addEventListener('change',function(e){
    if(e.target.id==='photoFile'){var f=e.target.files&&e.target.files[0];e.target.value='';uploadPhoto(f)}
  });
  /* Malipo mapya (kutoka benki/mobile money) yanaonekana yenyewe bila kupakia ukurasa upya. */
  var pollBusy=false;
  function autoKey(arr){return (arr||[]).map(function(p){return p.receipt+':'+p.amount+':'+p.status}).join('|')}
  setInterval(function(){
    if(!cur||isNew||!origReg||pollBusy||document.hidden)return;
    pollBusy=true;
    api('get',{q:'&reg='+encodeURIComponent(origReg)}).then(function(j){
      var srv=j.student;
      if(autoKey(srv.payments)===autoKey(cur.payments))return;
      if(dirty){
        cur.payments=srv.payments||[];
      }else{cur=srv;setBase();curPhoto=j.photo_url||''}
      renderAll();loadList();
      toast('Malipo mapya yamepokelewa.');
    }).catch(function(){}).then(function(){pollBusy=false});
  },15000);
  $('#q').addEventListener('input',renderList);
  $('#newBtn').addEventListener('click',newStudent);

  $('#viewBtn').addEventListener('click',function(){
    if(!cur||isNew)return;
    if(dirty&&!confirm('Una mabadiliko ambayo hayajahifadhiwa. Endelea bila kuhifadhi?'))return;
    var w=window.open('','_blank');   // fungua dirisha mara moja ili kivinjari kisilizuie
    api('impersonate',{body:{reg:origReg}}).then(function(j){
      if(w)w.location=j.portal;else location.href=j.portal;
    }).catch(function(e){if(w)w.close();toast(e.message,true)});
  });

  $('#saveBtn').addEventListener('click',function(){
    if(!cur)return;
    var fc=feeChanged();
    if(fc.length&&!confirm('Umebadilisha: '+fc.join(', ')+'.\nMabadiliko haya yatatumika kwa wanafunzi WOTE ('+Math.max(list.length+(isNew?1:0),1)+'). Endelea?'))return;
    var b=this;b.disabled=true;b.textContent='Inahifadhi…';
    api('save',{body:{original_reg:origReg,student:cur,fee_base:feeBase}}).then(function(j){
      cur=j.student;setBase();curPhoto=j.photo_url||'';origReg=cur.reg_no;isNew=false;setDirty(false);
      toast(j.warning||'Imehifadhiwa. Student Portal imesasishwa.',!!j.warning);
      return loadList().then(renderAll);
    }).catch(function(e){toast(e.message,true);setDirty(true)}).then(function(){b.textContent='Hifadhi';b.disabled=!dirty});
  });

  $('#delBtn').addEventListener('click',function(){
    if(!cur||isNew)return;
    if(!confirm('Futa '+(cur.display_name||cur.reg_no)+' kabisa? Hili haliwezi kutenduliwa.'))return;
    api('delete',{body:{reg:origReg}}).then(function(){
      cur=null;origReg='';setDirty(false);toast('Mwanafunzi amefutwa.');
      $('#curName').textContent='Chagua mwanafunzi';$('#curSub').textContent='Mabadiliko yanaonekana kwenye Student Portal mara tu ukihifadhi.';
      return loadList().then(renderAll);
    }).catch(function(e){toast(e.message,true)});
  });

  window.addEventListener('beforeunload',function(e){if(dirty){e.preventDefault();e.returnValue=''}});

  /* Mobile drawer */
  var side=$('#side'),scrim=$('#scrim');
  function closeSide(){side.classList.remove('open');scrim.classList.remove('open')}
  $('#menuBtn').addEventListener('click',function(){side.classList.add('open');scrim.classList.add('open')});
  scrim.addEventListener('click',closeSide);

  loadList().then(function(){if(list.length)openStudent(list[0].reg_no)}).catch(function(e){toast(e.message,true)});
})();
</script>

</body>
</html>
