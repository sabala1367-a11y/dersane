<?php
declare(strict_types=1);
require __DIR__ . '/config_student-portal.php';

// Kila anayefungua ukurasa huu lazima aingie upya: session ya zamani inafutwa.
header('Cache-Control: no-store');
$_SESSION = [];
if (session_id() !== '') session_destroy();
?>
<!doctype html>
<html lang="sw">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Student Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#e6e9ef; --light:#ffffff; --dark:#c3c8d4;
    --text:#1f2430; --muted:#8a91a3; --accent:#1a73e8; --err:#d93a4a;
  }
  *{box-sizing:border-box}
  html,body{height:100%}
  body{
    margin:0; background:var(--bg); color:var(--text);
    font-family:'Poppins',system-ui,sans-serif;
    display:flex; align-items:center; justify-content:center;
    overflow:hidden; padding:16px;
  }

  /* Miduara ya mapambo (neumorphic) inayoelea polepole */
  .orb{position:fixed; border-radius:50%; background:var(--bg);
    box-shadow:14px 14px 30px var(--dark), -14px -14px 30px var(--light); z-index:0}
  .orb.a{width:150px;height:150px;top:6%;left:10%;animation:float 9s ease-in-out infinite}
  .orb.b{width:60px;height:60px;top:34%;right:24%;animation:float 7s ease-in-out infinite reverse}
  .orb.c{width:110px;height:110px;bottom:8%;right:12%;animation:float 11s ease-in-out infinite}
  @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-18px)}}

  .card{
    position:relative; z-index:1; width:340px; max-width:100%;
    padding:36px 30px 32px; border-radius:30px; background:var(--bg);
    box-shadow:18px 18px 40px var(--dark), -18px -18px 40px var(--light);
    animation:rise .7s cubic-bezier(.2,.8,.2,1) both;
  }
  @keyframes rise{from{opacity:0;transform:translateY(24px) scale(.97)}to{opacity:1;transform:none}}
  .card.shake{animation:shake .45s}
  @keyframes shake{20%,60%{transform:translateX(-8px)}40%,80%{transform:translateX(8px)}}

  .lock{
    width:64px;height:64px;margin:-68px auto 14px;border-radius:50%;
    background:var(--bg); display:grid; place-items:center;
    box-shadow:7px 7px 15px var(--dark), -7px -7px 15px var(--light);
    animation:pulse 3.2s ease-in-out infinite;
  }
  @keyframes pulse{50%{box-shadow:4px 4px 10px var(--dark), -4px -4px 10px var(--light)}}
  .lock svg{width:26px;height:26px;fill:var(--accent)}

  h1{margin:0;text-align:center;font-size:1.45rem;font-weight:700;letter-spacing:.2px}
  .sub{margin:4px 0 26px;text-align:center;font-size:.72rem;color:var(--muted)}

  /* Input zenye animation: label inayopanda, mstari unaokua, kivuli kinachozama */
  .field{position:relative;margin-bottom:22px}
  .field input{
    width:100%; height:52px; padding:18px 46px 0 18px; border:0; outline:0;
    border-radius:14px; background:var(--bg); color:var(--text);
    font:500 .92rem 'Poppins',sans-serif;
    box-shadow:inset 5px 5px 10px var(--dark), inset -5px -5px 10px var(--light);
    transition:box-shadow .3s, transform .3s;
  }
  .field label{
    position:absolute; left:18px; top:15px; font-size:.86rem; color:var(--muted);
    pointer-events:none; transform-origin:left top;
    transition:transform .25s cubic-bezier(.2,.8,.2,1), color .25s;
  }
  .field input:focus + label,
  .field input:not(:placeholder-shown) + label{transform:translateY(-9px) scale(.78);color:var(--accent)}
  .field input:focus{
    box-shadow:inset 7px 7px 14px var(--dark), inset -7px -7px 14px var(--light);
    transform:translateY(1px);
  }
  .field .bar{
    position:absolute; left:50%; bottom:0; height:2.5px; width:0; border-radius:2px;
    background:linear-gradient(90deg,var(--accent),#5aa8ff); transform:translateX(-50%);
    transition:width .35s cubic-bezier(.2,.8,.2,1);
  }
  .field input:focus ~ .bar{width:calc(100% - 28px)}
  .field.error input{box-shadow:inset 5px 5px 10px var(--dark), inset -5px -5px 10px var(--light), 0 0 0 1.5px var(--err)}
  .field.error label{color:var(--err)}

  .eye{
    position:absolute; right:10px; top:8px; width:36px; height:36px; border:0; border-radius:50%;
    background:var(--bg); cursor:pointer; display:grid; place-items:center;
    box-shadow:3px 3px 6px var(--dark), -3px -3px 6px var(--light);
    transition:box-shadow .2s, transform .2s;
  }
  .eye:active{box-shadow:inset 2px 2px 5px var(--dark), inset -2px -2px 5px var(--light);transform:scale(.94)}
  .eye svg{width:17px;height:17px;stroke:var(--muted);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
  .eye .off{display:none}
  .eye.on .off{display:block}.eye.on .open{display:none}

  .msg{
    min-height:0; max-height:0; overflow:hidden; opacity:0; margin:0 0 0;
    font-size:.78rem; color:var(--err); text-align:center;
    transition:max-height .3s, opacity .3s, margin .3s;
  }
  .msg.show{max-height:60px;opacity:1;margin-bottom:14px}

  .btn{
    position:relative; overflow:hidden; width:100%; height:50px; border:0; border-radius:14px;
    background:var(--bg); color:var(--accent); font:600 .95rem 'Poppins',sans-serif; letter-spacing:.4px;
    cursor:pointer; box-shadow:7px 7px 15px var(--dark), -7px -7px 15px var(--light);
    transition:box-shadow .2s, transform .2s, color .3s;
  }
  .btn:hover{transform:translateY(-2px)}
  .btn:active{transform:none;box-shadow:inset 4px 4px 9px var(--dark), inset -4px -4px 9px var(--light)}
  .btn:disabled{cursor:wait;opacity:.85}
  .spin{display:inline-block;width:20px;height:20px;margin-right:10px;vertical-align:-5px;border-radius:50%;
    border:3px solid rgba(26,115,232,.22);border-top-color:var(--accent);animation:sp .75s linear infinite}
  @keyframes sp{to{transform:rotate(360deg)}}

  @media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
</style>
</head>
<body>
  <div class="orb a"></div><div class="orb b"></div><div class="orb c"></div>

  <main class="card" id="card">
    <div class="lock" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M12 1a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-1V6a5 5 0 0 0-5-5zm-3 8V6a3 3 0 1 1 6 0v3H9zm3 4a2 2 0 0 1 1 3.73V19h-2v-2.27A2 2 0 0 1 12 13z"/></svg>
    </div>
    <h1>Welcome</h1>
    <p class="sub">Login to your student portal</p>

    <form id="loginForm" novalidate>
      <div class="field" id="fEmail">
        <input id="email" name="email" type="text" placeholder=" " autocomplete="username" required>
        <label for="email">Email / Reg. No</label>
        <span class="bar"></span>
      </div>

      <div class="field" id="fPwd">
        <input id="pwd" name="password" type="password" placeholder=" " autocomplete="current-password" required>
        <label for="pwd">Password</label>
        <button type="button" class="eye" id="eye" aria-label="Onyesha nenosiri">
          <svg class="open" viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
          <svg class="off" viewBox="0 0 24 24"><path d="M17.94 17.94A10.9 10.9 0 0 1 12 19c-7 0-11-7-11-7a19.8 19.8 0 0 1 5.06-5.94M9.9 4.24A10.9 10.9 0 0 1 12 5c7 0 11 7 11 7a19.8 19.8 0 0 1-3.17 4.19M1 1l22 22"/></svg>
        </button>
        <span class="bar"></span>
      </div>

      <p class="msg" id="msg" role="alert"></p>
      <button type="submit" class="btn" id="btn">Login</button>
    </form>
  </main>

<script>
(function(){
  var $=function(i){return document.getElementById(i)};
  var form=$('loginForm'),email=$('email'),pwd=$('pwd'),btn=$('btn'),msg=$('msg'),card=$('card'),eye=$('eye');

  eye.addEventListener('click',function(){
    var show=pwd.type==='password';
    pwd.type=show?'text':'password';
    eye.classList.toggle('on',show);
    eye.setAttribute('aria-label',show?'Ficha nenosiri':'Onyesha nenosiri');
  });

  function clearErr(){
    msg.classList.remove('show');
    $('fEmail').classList.remove('error');$('fPwd').classList.remove('error');
  }
  function showErr(t){
    msg.textContent=t;msg.classList.add('show');
    $('fEmail').classList.add('error');$('fPwd').classList.add('error');
    card.classList.remove('shake');void card.offsetWidth;card.classList.add('shake');
  }
  [email,pwd].forEach(function(el){el.addEventListener('input',clearErr)});

  form.addEventListener('submit',async function(e){
    e.preventDefault();clearErr();
    if(!email.value.trim()||!pwd.value){showErr('Weka jina la kuingia na nenosiri.');return}
    btn.disabled=true;btn.innerHTML='<span class="spin"></span>Loading...';var t0=Date.now();
    try{
      var res=await fetch('auth_student-portal.php',{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({email:email.value.trim(),password:pwd.value})});
      var raw=await res.text(),data=null;
      try{data=JSON.parse(raw)}catch(_){}
      if(!data){
        var why=res.status===404?'Faili auth_student-portal.php halipatikani (404). Hakikisha mafile yote yako kwenye folda moja.'
          :res.status>=500?'Kosa la seva ('+res.status+'). Angalia PHP error log / toleo la PHP.'
          :'Seva haikurudisha JSON. Fungua ukurasa kupitia http://localhost/ (si file://).';
        showErr(why);btn.disabled=false;btn.textContent='Login';return;
      }
      if(data.success){
        await new Promise(function(r){setTimeout(r,Math.max(0,3000-(Date.now()-t0)))});
        btn.textContent='Umeingia ✓';
        window.location.href=data.redirect||'Student_Portal_student-portal.php';
      }else{
        showErr(data.message||'Imeshindikana kuingia.');
        btn.disabled=false;btn.textContent='Login';
      }
    }catch(err){
      showErr('Hitilafu ya mtandao. Jaribu tena.');
      btn.disabled=false;btn.textContent='Login';
    }
  });
})();
</script>
</body>
</html>
