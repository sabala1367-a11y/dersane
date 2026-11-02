<?php
declare(strict_types=1);
require __DIR__ . '/admin-user-config.php';

// Tayari ameingia? Mpeleke moja kwa moja dashboard.
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="sw">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard — Ingia</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    font-family: 'Manrope', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background:
      radial-gradient(1100px 500px at 15% -10%, rgba(255,255,255,0.10), transparent 60%),
      linear-gradient(165deg, #0E2A5E 0%, #16356F 42%, #5C2440 78%, #8E1F2F 100%);
  }
  a { color: #0E2A5E; text-decoration: none; }
  a:hover { color: #8E1F2F; }

  .screen {
    width: 390px;
    max-width: 100%;
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .blob-a, .blob-b { position: absolute; border-radius: 50%; }
  .blob-a {
    top: -90px; right: -70px; width: 260px; height: 260px;
    background: radial-gradient(circle, rgba(255,255,255,0.14), transparent 70%);
    animation: floatBg 9s ease-in-out infinite;
  }
  .blob-b {
    bottom: -120px; left: -90px; width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.10), transparent 70%);
    animation: floatBg2 11s ease-in-out infinite;
  }
  @keyframes floatBg { 0%,100% { transform: translate(0,0); } 50% { transform: translate(-10px,14px); } }
  @keyframes floatBg2 { 0%,100% { transform: translate(0,0); } 50% { transform: translate(12px,-10px); } }
  @keyframes fadeUp { from { opacity:0; transform: translateY(18px);} to { opacity:1; transform: translateY(0);} }
  @keyframes popIn { from { opacity:0; transform: translateY(10px) scale(.98);} to { opacity:1; transform: translateY(0) scale(1);} }
  @keyframes shake { 10%,90% { transform: translateX(-1px);} 20%,80% { transform: translateX(2px);} 30%,50%,70% { transform: translateX(-4px);} 40%,60% { transform: translateX(4px);} }

  .brand-block {
    position: relative; z-index: 2;
    display: flex; flex-direction: column; align-items: center;
    margin-top: 64px; padding: 0 32px; text-align: center;
    animation: fadeUp .7s ease both;
  }
  .logo-badge {
    width: 74px; height: 74px; border-radius: 20px;
    background: linear-gradient(150deg,#F7F8FA,#E7E9EF);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 10px 24px rgba(0,0,0,0.25);
    margin-bottom: 16px;
  }
  .brand-title {
    color: #F7F8FA; font-family: 'Sora', sans-serif; font-weight: 800;
    font-size: 18px; letter-spacing: 0.2px; line-height: 1.3;
  }

  .login-card {
    position: relative; z-index: 2; width: 338px; max-width: calc(100% - 32px);
    margin-top: 28px;
    background: rgba(255,255,255,0.97); border-radius: 24px;
    padding: 30px 26px 26px 26px;
    box-shadow: 0 24px 60px rgba(7,16,38,0.35);
    border: 1px solid rgba(255,255,255,0.5);
    animation: fadeUp .7s .1s ease both;
  }
  .card-title { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 20px; color: #131A2B; }
  .card-subtitle { font-size: 13px; color: #6B7284; margin-top: 4px; margin-bottom: 22px; }

  .field-group { animation: popIn .5s ease both; }
  .field-group.fld-1 { animation-delay: .28s; }
  .field-group.fld-2 { animation-delay: .38s; }

  .field-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 7px; }
  label { display: block; font-size: 12.5px; font-weight: 600; color: #2A3146; margin-bottom: 7px; transition: color .2s ease; }
  .forgot-link { font-size: 12px; font-weight: 600; }

  .input-wrap { position: relative; margin-bottom: 18px; }
  .input-wrap.pwd { margin-bottom: 10px; }

  .field-icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    display: flex; color: #8A93A6;
    transition: color .25s ease, transform .25s ease;
  }

  input {
    width: 100%; height: 46px; border-radius: 12px;
    border: 1.5px solid #E3E6ED; background: #FAFBFC;
    padding: 0 14px 0 42px;
    font-size: 14.5px; font-family: 'Manrope', sans-serif; color: #131A2B;
    transition: border-color .25s ease, box-shadow .25s ease, transform .25s ease;
  }
  input.pwd-input { padding-right: 42px; }
  input::placeholder { color: #9AA3B2; }
  input:focus {
    outline: none; border-color: #0E2A5E; box-shadow: 0 0 0 4px rgba(14,42,94,0.10);
    transform: translateY(-1px);
  }
  input.input-error { border-color: #C0394A !important; }
  .field-group:focus-within .field-icon { color: #0E2A5E; transform: translateY(-50%) scale(1.08); animation: iconPop .45s ease; }
  .field-group:focus-within label { color: #0E2A5E; transform: translateX(4px); }
  label { transition: color .2s ease, transform .25s ease; }

  /* Mstari unaokua chini ya kisanduku unapokiingia (focus) */
  .input-wrap::after {
    content: ''; position: absolute; left: 12px; right: 12px; bottom: 1px; height: 2px; border-radius: 2px;
    background: linear-gradient(90deg, #0E2A5E, #8E1F2F);
    transform: scaleX(0); transform-origin: left; transition: transform .35s ease; pointer-events: none;
  }
  .input-wrap:focus-within::after { transform: scaleX(1); }
  .input-wrap:hover input:not(:focus) { border-color: #B9C0CF; background: #fff; }
  input:focus { background: #fff; animation: fieldGlow 1.6s ease-in-out infinite; }
  @keyframes fieldGlow {
    0%,100% { box-shadow: 0 0 0 4px rgba(14,42,94,0.10); }
    50%     { box-shadow: 0 0 0 6px rgba(14,42,94,0.16); }
  }
  @keyframes iconPop {
    0%   { transform: translateY(-50%) scale(1); }
    40%  { transform: translateY(-50%) scale(1.35) rotate(-10deg); }
    100% { transform: translateY(-50%) scale(1.08); }
  }

  .eye-btn {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    width: 28px; height: 28px; border: none; background: transparent; cursor: pointer;
    display: flex; align-items: center; justify-content: center; border-radius: 8px;
    transition: opacity .2s ease, transform .2s ease;
  }
  .eye-btn:hover { opacity: 0.7; }
  .eye-btn:active { transform: translateY(-50%) scale(0.9); }
  .eye-closed { display: none; }

  .form-error {
    display: none;
    background: #FDEDEF; color: #9A2436; border: 1px solid #F3C4CC;
    font-size: 12.5px; font-weight: 600; border-radius: 10px;
    padding: 9px 12px; margin-bottom: 16px;
  }
  .form-error.show { display: block; animation: shake .4s ease; }

  .login-btn {
    width: 100%; height: 48px; border: none; border-radius: 13px; cursor: pointer;
    background: linear-gradient(100deg,#0E2A5E,#2F4A86 48%,#8E1F2F);
    color: #fff; font-family: 'Sora', sans-serif; font-weight: 700; font-size: 15px;
    letter-spacing: 0.3px; box-shadow: 0 10px 22px rgba(14,42,94,0.30);
    transition: filter .2s ease, transform .2s ease, box-shadow .2s ease, opacity .2s ease;
    animation: popIn .5s .48s ease backwards;   /* backwards: animation isishikilie transform, ili hover/click zifanye kazi */
    margin-top: 14px;
    position: relative; overflow: hidden;
    touch-action: manipulation; -webkit-tap-highlight-color: transparent; user-select: none;
  }
  .login-btn::after {              /* mwanga unaopita juu ya kitufe */
    content: ''; position: absolute; top: 0; left: -60%; width: 40%; height: 100%;
    background: linear-gradient(100deg, transparent, rgba(255,255,255,0.28), transparent);
    transform: skewX(-20deg); pointer-events: none;
  }
  .login-btn:hover:not(:disabled)::after { left: 130%; transition: left .6s ease; }
  /* Hakuna transform (kuinuka/kupungua) ili eneo la kubonyeza libaki palepale */
  .login-btn:hover:not(:disabled) { filter: brightness(1.08); box-shadow: 0 14px 28px rgba(14,42,94,0.38); }
  .login-btn:active:not(:disabled) { filter: brightness(0.94); box-shadow: 0 4px 10px rgba(14,42,94,0.30), inset 0 3px 8px rgba(0,0,0,0.25); }
  .login-btn:focus-visible { outline: 3px solid rgba(14,42,94,0.35); outline-offset: 3px; }
  .btn-ripple {                    /* wimbi linalotokea unapobonyeza */
    position: absolute; border-radius: 50%; background: rgba(255,255,255,0.4);
    transform: scale(0); pointer-events: none; animation: ripple .6s ease-out forwards;
  }
  @keyframes ripple { to { transform: scale(1); opacity: 0; } }
  .login-btn:disabled { opacity: .7; cursor: not-allowed; transform: none; }

  .btn-spinner {
    display: inline-block; width: 15px; height: 15px; margin-right: 9px;
    vertical-align: -2px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.35); border-top-color: #fff;
    animation: spin .7s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  .footer {
    position: relative; z-index: 2; margin-top: auto; margin-bottom: 22px;
    text-align: center; color: rgba(247,248,250,0.55); font-size: 11px; letter-spacing: 0.3px;
  }
</style>
</head>
<body>

<div class="screen">
  <div class="blob-a"></div>
  <div class="blob-b"></div>

  <div class="brand-block">
    <div class="logo-badge">
      <svg width="38" height="38" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 3 2 8l10 5 10-5-10-5z" fill="#0E2A5E"/>
        <path d="M6 11.5V16.3c0 2.1 2.8 3.7 6 3.7s6-1.6 6-3.7V11.5l-6 3-6-3z" fill="#8E1F2F"/>
        <path d="M21 9v6" stroke="#0E2A5E" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
    </div>
    <div class="brand-title">WELCOME BACK TO THE<br>ADMIN DASHBOARD</div>
  </div>

  <div class="login-card">
    <div class="card-title">Karibu tena</div>
    <div class="card-subtitle">Ingia kwenye akaunti yako kuendelea</div>

    <div class="form-error" id="formError"></div>

    <form id="loginForm" novalidate>
      <div class="field-group fld-1">
        <label for="email">Username</label>
        <div class="input-wrap">
          <span class="field-icon" aria-hidden="true">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4z" stroke="currentColor" stroke-width="1.6"/><path d="M4.5 6.5 12 13l7.5-6.5" stroke="currentColor" stroke-width="1.6"/></svg>
          </span>
          <input id="email" name="email" type="email" placeholder="Enter username" autocomplete="username" required>
        </div>
      </div>

      <div class="field-group fld-2">
        <div class="field-row">
          <label for="pwd" style="margin-bottom:0;">Password</label>
          <a href="#" class="forgot-link">Umesahau?</a>
        </div>
        <div class="input-wrap pwd">
          <span class="field-icon" aria-hidden="true">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none"><rect x="5" y="10.5" width="14" height="9.5" rx="2.2" stroke="currentColor" stroke-width="1.6"/><path d="M8 10.5V7.8a4 4 0 0 1 8 0v2.7" stroke="currentColor" stroke-width="1.6"/></svg>
          </span>
          <input id="pwd" name="password" class="pwd-input" type="password" placeholder="Enter password" autocomplete="current-password" required>
          <button type="button" class="eye-btn" id="eyeBtn" aria-label="Onyesha nenosiri">
            <svg id="eyeOpen" width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="#6B7284" stroke-width="1.6"/><circle cx="12" cy="12" r="3" stroke="#6B7284" stroke-width="1.6"/></svg>
            <svg id="eyeClosed" class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 3l18 18" stroke="#6B7284" stroke-width="1.6" stroke-linecap="round"/><path d="M9.9 5.3C10.6 5.1 11.3 5 12 5c6.5 0 10 7 10 7a16.6 16.6 0 0 1-3.2 4.1M6.6 6.6C4 8.3 2 12 2 12s3.5 7 10 7c1.4 0 2.6-.3 3.7-.8" stroke="#6B7284" stroke-width="1.6" stroke-linecap="round"/><path d="M9.9 14.1A3 3 0 0 1 14.1 9.9" stroke="#6B7284" stroke-width="1.6" stroke-linecap="round"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="login-btn" id="submitBtn">Ingia</button>
    </form>
  </div>

  <div class="footer">© Institute of Accountancy Arusha</div>
</div>

<script>
  const eyeBtn = document.getElementById('eyeBtn');
  const pwd = document.getElementById('pwd');
  const email = document.getElementById('email');
  const eyeOpen = document.getElementById('eyeOpen');
  const eyeClosed = document.getElementById('eyeClosed');
  const form = document.getElementById('loginForm');
  const submitBtn = document.getElementById('submitBtn');
  const formError = document.getElementById('formError');

  eyeBtn.addEventListener('click', () => {
    const show = pwd.type === 'password';
    pwd.type = show ? 'text' : 'password';
    eyeOpen.classList.toggle('eye-closed', show);
    eyeClosed.classList.toggle('eye-closed', !show);
    eyeBtn.setAttribute('aria-label', show ? 'Ficha nenosiri' : 'Onyesha nenosiri');
  });

  function showError(msg) {
    formError.textContent = msg;
    formError.classList.remove('show');
    void formError.offsetWidth; // restart animation
    formError.classList.add('show');
    email.classList.add('input-error');
    pwd.classList.add('input-error');
  }

  function clearError() {
    formError.classList.remove('show');
    email.classList.remove('input-error');
    pwd.classList.remove('input-error');
  }

  [email, pwd].forEach(el => el.addEventListener('input', clearError));

  // Wimbi (ripple) kwenye kitufe cha Ingia
  submitBtn.addEventListener('pointerdown', (e) => {
    if (submitBtn.disabled) return;
    const r = submitBtn.getBoundingClientRect();
    const size = Math.max(r.width, r.height) * 2;
    const dot = document.createElement('span');
    dot.className = 'btn-ripple';
    dot.style.width = dot.style.height = size + 'px';
    dot.style.left = (e.clientX - r.left - size / 2) + 'px';
    dot.style.top = (e.clientY - r.top - size / 2) + 'px';
    submitBtn.appendChild(dot);
    dot.addEventListener('animationend', () => dot.remove());
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearError();

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="btn-spinner"></span>Inaingia...';
    const startedAt = Date.now();

    try {
      const res = await fetch('admin-auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email.value.trim(), password: pwd.value }),
      });
      const data = await res.json();

      if (data.success) {
        // Loading ichelewe sekunde 2 kabla ya kuingia
        await new Promise(r => setTimeout(r, Math.max(0, 2000 - (Date.now() - startedAt))));
        submitBtn.textContent = 'Umeingia ✓';
        window.location.href = 'dashboard.php';
      } else {
        showError(data.message || 'Imeshindikana kuingia.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Ingia';
      }
    } catch (err) {
      showError('Hitilafu ya mtandao. Jaribu tena.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Ingia';
    }
  });
</script>
</body>
</html>
