<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DERSANE HOUSE | Student Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root{
    --ink:#F4F6FB; --ink-2:rgba(244,246,251,.74); --gold:#F5A400;
    --glass:rgba(255,255,255,.10); --glass-2:rgba(255,255,255,.17);
    --edge:rgba(255,255,255,.26); --shadow:0 18px 44px rgba(3,9,24,.40);
}
* { box-sizing: border-box; }
html { background:#0b1830 linear-gradient(165deg,#0a1530 0%,#102035 40%,#2a2150 75%,#4a1f3d 100%) fixed; scroll-behavior:smooth; min-height:100%; }

body {
    margin: 0;
    font-family: Arial, sans-serif;
    color: var(--ink);
    min-height: 100vh;
    overflow-x: hidden;
}
/* Mipira ya rangi nyuma ya glass */
body::before, body::after {
    content:""; position:fixed; z-index:0; border-radius:50%; filter:blur(70px); pointer-events:none;
    animation: drift 18s ease-in-out infinite;
}
body::before { width:420px; height:420px; background:#2f63d0; opacity:.45; top:-120px; left:-100px; }
body::after  { width:380px; height:380px; background:#f5a400; opacity:.22; bottom:-120px; right:-80px; animation-delay:-7s; }
@keyframes drift { 0%,100%{transform:translate(0,0)} 50%{transform:translate(36px,-28px)} }

/* Msingi wa glass */
.header-container, .nav-bar, .announcement-box, .info-box-main, .portal-card, #portal-notice, .remember-scroll-box {
    background: var(--glass);
    border: 1px solid var(--edge);
    -webkit-backdrop-filter: blur(20px) saturate(160%);
    backdrop-filter: blur(20px) saturate(160%);
    box-shadow: var(--shadow), inset 0 1px 0 rgba(255,255,255,.22);
}
@supports not ((backdrop-filter:blur(1px)) or (-webkit-backdrop-filter:blur(1px))) {
    .header-container, .nav-bar, .announcement-box, .info-box-main, .portal-card, #portal-notice { background: rgba(18,32,58,.88); }
}

/* HERO SECTION: picha kubwa (slideshow) + overlay nyeusi + maandishi makubwa */
.hero { position: relative; z-index: 2; min-height: 100vh; min-height: 100svh; display: flex; align-items: center; background: #0b1830; }
.hero-media { position: absolute; inset: 0; z-index: 0; overflow: hidden; }
.hero-media::after {   /* overlay nyeusi juu ya picha */
    content: ""; position: absolute; inset: 0;
    background:
        linear-gradient(180deg, rgba(4,8,20,.65) 0%, transparent 24%),
        linear-gradient(90deg, rgba(4,8,20,.88) 0%, rgba(4,8,20,.62) 48%, rgba(4,8,20,.25) 100%),
        linear-gradient(0deg, rgba(4,8,20,.65) 0%, transparent 40%);
}
.hero-content { position: relative; z-index: 2; padding: 120px 5vw 80px; max-width: 720px; }
.main-title {
    margin: 0 0 18px; font-family: "Times New Roman", serif;
    font-size: clamp(36px, 6vw, 66px); line-height: 1.08; letter-spacing: 1px; color: #fff;
}
.header-subtitle { margin: 0 0 30px; font-size: clamp(16px, 2vw, 19px); line-height: 1.6; color: var(--ink-2); max-width: 52ch; }
.hero-btns { display: flex; gap: 14px; flex-wrap: wrap; }
.hero-btn {
    padding: 14px 28px; border-radius: 999px; font-weight: 700; font-size: 15px;
    color: #fff; text-decoration: none; border: 1px solid rgba(255,255,255,.5);
    background: rgba(255,255,255,.08); transition: transform .2s, filter .2s;
}
.hero-btn.main { background: linear-gradient(100deg,#f5a400,#e08a00); color: #1b1200; border-color: transparent; }
.hero-btn:hover { transform: translateY(-2px); filter: brightness(1.1); }
.hero-btn:focus-visible, .menu-btn:focus-visible, .nav-menu a:focus-visible, .brand:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }

/* NAVIGATION: logo + jina kushoto, links kulia (simu: kitufe cha menu) */
.nav-bar {
    position: absolute; top: 0; left: 0; right: 0; z-index: 50;
    display: flex; align-items: center; gap: 28px; padding: 18px 5vw;
}
.brand { display: flex; align-items: center; gap: 14px; color: #fff; text-decoration: none; }
.header-logo { width: 48px; height: 48px; object-fit: cover; border-radius: 12px; border: 2px solid var(--edge); }
.brand-title { font-family: "Times New Roman", serif; font-size: 24px; letter-spacing: 3px; font-weight: 700; }
.menu-btn {
    display: none; width: 48px; height: 45px; margin-left: auto;
    background: var(--glass-2); border: 1px solid var(--edge); border-radius: 14px;
    flex-direction: column; justify-content: center; align-items: center;
    gap: 6px; cursor: pointer;
}
.bar { width: 22px; height: 2px; background: white; border-radius: 2px; }
.nav-menu { display: flex; flex-wrap: wrap; gap: 8px 22px; list-style: none; margin: 0 0 0 auto; padding: 0; font-size: 15px; }
.nav-menu a { color: #fff; text-decoration: none; padding: 6px 2px; border-bottom: 2px solid transparent; transition: border-color .2s; }
.nav-menu a:hover { border-color: var(--gold); }

/* SLIDESHOW NA MATANGAZO */
.main-content {
    position: relative; z-index: 1;
    max-width: none; margin: 0; padding: 30px 16px 40px;   /* inaanzia kushoto, sawa na Student Portal */
    display: grid; grid-template-columns: minmax(0, 560px) minmax(0, 1fr); gap: 20px; align-items: start;
}
.slide { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity .8s; }
.slide.active { opacity: 1; }

.announcement-box {
    padding: 22px; border-radius: 24px; border-top: 3px solid var(--gold);
    width: 100%; max-width: 560px; justify-self: start;
    height: 420px; display: flex; flex-direction: column; overflow: hidden;   /* ukubwa umefungwa: hauongezeki */
}
.announcement-header { flex: none; }
.announcement-body {   /* matangazo yakizidi, yanasogezwa (scroll) ndani ya kadi tu */
    flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden; padding-right: 8px;
    overflow-wrap: anywhere; word-break: break-word;
    scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.4) transparent;
}
.announcement-header { display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,.18); padding-bottom: 10px; }
.announcement-logo { width: 35px; height: 35px; object-fit: contain; }
.announcement-header h3 { color: #fff; margin: 0; }
.announcement-box, .announcement-header h3, .announcement-body p { font-family: "Montserrat", Arial, sans-serif; }   /* fonti ya sehemu ya Announcements */
.announcement-body p { font-size: 14px; line-height: 1.6; padding-bottom: 10px; border-bottom: 1px dashed rgba(255,255,255,.2); color: var(--ink-2); }

/* TAARIFA ZA DERSANE HOUSE */
.main-content .extra-sections-container { max-width: none; margin: 0; padding: 0; }
.remember-card .info-item h5 { margin-top: 0; }
.extra-sections-container {
    position: relative; z-index: 1;
    max-width: none; margin: 0 0 40px; padding: 0 16px;
    display: flex; flex-direction: column; gap: 20px;
}
.info-box-main { padding: 28px; border-radius: 24px; border-top: 3px solid var(--gold); display: flex; flex-direction: column; gap: 20px; }
.info-item h4 { color: #fff; border-bottom: 1px solid rgba(255,255,255,.2); padding-bottom: 6px; margin-bottom: 8px; letter-spacing: 1px; }
.info-item h5 { color: var(--gold); font-size: 14px; letter-spacing: 1px; margin: 18px 0 8px; }
.info-item p, .info-item li { font-size: 14px; line-height: 1.7; color: var(--ink-2); }
.info-item a { color: var(--ink-2); text-decoration: none; }
.info-item a:hover { color: #fff; text-decoration: underline; }

.remember-scroll-box {
    max-height: 130px; overflow-y: auto; padding: 5px 10px; border-radius: 14px;
    background: rgba(255,255,255,.06); box-shadow: inset 0 1px 6px rgba(0,0,0,.2);
    scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.4) transparent;
}

/* STUDENT PORTAL - CHINI */
.student-portal {
    position: relative; z-index: 1;
    margin: 0 16px; padding: 30px 24px; min-height: 280px; color: white;
    border-radius: 28px; overflow: hidden;
    border: 1px solid var(--edge); box-shadow: var(--shadow);
    background:
        linear-gradient(90deg, rgba(7,19,41,.82), rgba(7,19,41,.45)),
        url("image1.png") center/cover no-repeat;
}
.portal-inner { max-width: 1200px; margin: auto; }
.portal-welcome { max-width: 760px; font-size: 16px; line-height: 1.6; margin: 0 0 18px; }
.portal-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; max-width: 850px; }
.portal-card {
    display: flex; align-items: center; gap: 14px; padding: 15px; min-height: 70px;
    border-left: 4px solid var(--gold); border-radius: 18px;
    text-decoration: none; color: white; transition: .25s;
}
.portal-card:hover { background: var(--glass-2); transform: translateY(-4px); }
.portal-icon {
    display: grid; place-items: center; flex-shrink: 0; width: 38px; height: 38px;
    background: rgba(245,164,0,.18); border: 1px solid rgba(245,164,0,.35);
    color: #ffb21b; border-radius: 12px; font-size: 20px;
}
.portal-card strong { display: block; font-size: 14px; margin-bottom: 6px; }
.portal-card small { color: var(--ink-2); font-size: 12px; }
#portal-notice { display: none; padding: 12px; border-radius: 14px; max-width: 850px; line-height: 1.5; }

footer {
    position: relative; z-index: 1;
    margin: 22px 16px 16px; padding: 18px; border-radius: 20px;
    background: var(--glass); border: 1px solid var(--edge);
    -webkit-backdrop-filter: blur(16px); backdrop-filter: blur(16px);
    color: var(--ink-2); text-align: center; font-size: 13px;
}

/* TEXT REVEAL: kila neno linapanda kutoka chini ya mstari wake */
.rv-ready .w { display: inline-block; overflow: hidden; vertical-align: top; padding-bottom: .12em; margin-bottom: -.12em; }
.rv-ready .w > span {
    display: inline-block; transform: translateY(110%); opacity: 0;
    transition: transform .7s cubic-bezier(.2,.8,.2,1), opacity .7s ease;
    transition-delay: calc(var(--i) * 45ms);
}
.rv-ready.rv-in .w > span { transform: none; opacity: 1; }

/* SIMU */
@media (max-width: 768px) {
    .menu-btn { display: flex; }
    .nav-menu {
        display: none; position: absolute; top: 76px; left: 5vw; right: 5vw;
        flex-direction: column; gap: 4px; margin: 0; padding: 14px; border-radius: 16px;
        background: rgba(16,32,53,.97); border: 1px solid var(--edge); box-shadow: var(--shadow);
    }
    .nav-menu.active { display: flex; }
    .nav-menu a { display: block; padding: 10px 6px; }
    .brand-title { font-size: 19px; letter-spacing: 2px; }
    .hero-media::after { background: linear-gradient(0deg, rgba(4,8,20,.9) 0%, rgba(4,8,20,.55) 100%); }
    .hero-content { padding: 110px 22px 60px; }
    .main-content { grid-template-columns: 1fr; padding: 20px 10px 30px; }
    .announcement-box { height: 360px; }
    .extra-sections-container { padding: 0 10px; }
    .student-portal { margin: 0 10px; }
    .portal-grid { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
    body::before, body::after { animation: none; }
    .rv-ready .w > span { transform: none; opacity: 1; transition: none; }
}
</style>
</head>

<body>

<!-- HERO SECTION: nav juu, picha kubwa (slideshow), overlay nyeusi na maandishi -->
<section class="hero" id="home">

    <div class="hero-media">
        <img src="image1.png" class="slide active"
             alt="Dersane House picha ya kwanza">
        <img src="image2.png" class="slide"
             alt="Dersane House picha ya pili">
        <img src="image3.png" class="slide"
             alt="Dersane House picha ya tatu">
        <img src="image4.png" class="slide"
             alt="Dersane House picha ya nne">
        <img src="image5.png" class="slide"
             alt="Dersane House picha ya tano">
    </div>

    <!-- MENU -->
    <nav class="nav-bar">
        <a class="brand" href="#home">
            <img src="image3.png" alt="Dersane House Logo" class="header-logo">
            <span class="brand-title">DERSANE HOUSE</span>
        </a>

        <button class="menu-btn" id="menuBtn"
                aria-label="Fungua menu"
                aria-expanded="false">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php">Home</a></li>
            <li><a href="about-dersane.html">About Us</a></li>
            <li><a href="gallery.html">gallery</a></li>
            <li><a href="Neon_Orbit_Login.html">Admissions</a></li>
            <li><a href="login_student-portal.php">Student Portal</a></li>
            <li><a href="admin-login.php">admin portal</a></li>
            <li><a href="contact.html">Contact Us</a></li>
        </ul>
    </nav>

    <div class="hero-content">
        <h1 class="main-title">welcome dersane  leave near by your university </h1>
        <p class="header-subtitle">
            DERSANE HOUSE ina nyumba Dar es Salaam, Dodoma, Arusha, Mwanza na Zanzibar. pamoja na brothers(ABI) husika wanao simamia kila eneo.
        </p>

        <div class="hero-btns">
            <a class="hero-btn main" href="Neon_Orbit_Login.html">Omba nafasi</a>
            <a class="hero-btn" href="location.html">Angalia mahali pa nyumba</a>
        </div>
    </div>

</section>

<!-- PICHA NA MATANGAZO -->
<main class="main-content">

    <section class="announcement-box" id="announcements">
        <div class="announcement-header">
            <img src="Anounce.png"
                 class="announcement-logo"
                 alt="Announcement icon">
            <h3>Announcements</h3>
        </div>

        <div class="announcement-body" id="announcementBody">
            <p>1. Karibu Dersane House.</p>

            <p>
                2. Upokeaji wa wanafunzi mwaka wa kwanza
                wenye house za dersane bado unaendelea.
            </p>

            <p>
                3. Mchakato wa kupanga wanafunzi kwenye
                nyumba zao husika bado unaendelea.
            </p>

            <p>
                4. Utaratibu wa kuchagua ma-imam na
                wasaidizi wao bado unaendelea.
            </p>
        </div>
    </section>

    <!-- TAARIFA ZA NYUMBA: kulia mwa Announcements (Responsibility juu, Remember chini) -->
    <section class="extra-sections-container">

    <div class="info-box-main">

        <div class="info-item" id="about">
            <h4>RESPONSIBILITY</h4>
            <ul>
                <li>Imam should oversee every responsibility.</li>
                <li>Assistant Imam.</li>
                <li>Accountant.</li>
                <li>program follow up</li>
                <li>cleanliness</li>
                <li>House friends</li>
                <li>Students supervisor</li>
            </ul>
        </div>

    </div>

    <div class="info-box-main remember-card">

        <div class="info-item">
           <h5>REMEMBER</h5>
           <!-- Sehemu iliyoongezwa kibox cha kuslide ndani -->
           <div class="remember-scroll-box">
               <ul>
                   <li>Respect your imam</li>
                   <li>Respect and help each other</li>
                   <li>No women in house under any circumstance</li>
                   <li>No abuse language</li>
                   <li>cleanlines</li>
                   <li>safe keep all House items</li>
                   <li>follow all house program</li>
                   <li>Greet and show the Neighbors kindness</li>
                   <li>people should eat meal together</li>
                   <li>Avoid unethical discussion</li>
                   <li>NO shorts, Vest or wearing towers outside your room</li>
                   <li>Do not sleep nacked or with boxers only</li>
                   <li>No music even reels on tiktok</li>
                   <li>No friends without consulting imam</li>
                   <li>No one apart from house member should sleep in house</li>
               </ul>
           </div>
        </div>

    </div>

    </section>

</main>

<!-- 2. STUDENT PORTAL SASA IKO CHINI -->
<section class="student-portal" id="student-portal">
    <div class="portal-inner">

        <p class="portal-welcome">
            Welcome to your dersane house check the link below to view for more
        </p>

        <div class="portal-grid">

            <a class="portal-card" href="books.html">
                <span class="portal-icon">📚</span>
                <span>
                    <strong>Books</strong>
                    <small>Explore target books &amp; readings</small>
                </span>
            </a>

            <a class="portal-card" href="programs.html">
                <span class="portal-icon">🏅</span>
                <span>
                    <strong>Programs</strong>
                    <small>House activities &amp; schedules</small>
                </span>
            </a>

            <a class="portal-card" href="Quick-links.html">
                <span class="portal-icon">▤</span>
                <span>
                    <strong>Quick links</strong>
                    <small>links essential in house &amp; programs </small>
                </span>
            </a>

            <a class="portal-card" href="location.html">
                <span class="portal-icon">▤</span>
                <span>
                    <strong>House Location</strong>
                    <small>Directions &amp; addresses</small>
                </span>
            </a>

        </div>

    </div>
</section>

<footer>
    &copy; <span id="year"></span> DERSANE HOUSE.
    All Rights Reserved.
</footer>

<script>
/* MENU */
const menuBtn = document.getElementById("menuBtn");
const navMenu = document.getElementById("navMenu");

menuBtn.addEventListener("click", function () {
    navMenu.classList.toggle("active");

    menuBtn.setAttribute(
        "aria-expanded",
        navMenu.classList.contains("active")
    );
});

document.querySelectorAll(".nav-menu a").forEach(function (link) {
    link.addEventListener("click", function () {
        navMenu.navMenu?.classList.remove("active");
        navMenu.classList.remove("active");
        menuBtn.setAttribute("aria-expanded", "false");
    });
});

/* SLIDESHOW */
const slides = document.querySelectorAll(".slide");
let currentSlide = 0;

if (slides.length > 1) {
    setInterval(function () {
        slides[currentSlide].classList.remove("active");

        currentSlide = (currentSlide + 1) % slides.length;

        slides[currentSlide].classList.add("active");
    }, 5000);
}

/* ANNOUNCEMENTS ZINAZOTOKA KWENYE DASHBOARD
   (kama zimeshindwa kupakia, zile za kawaida zilizoandikwa hapo juu zinabaki)
   Zina-update zenyewe: ukurasa unaangalia matangazo mapya kila sekunde 10,
   na ukiwa umerudi kwenye tab. Zinabadilika tu endapo kuna mabadiliko. */
(function () {
    var box = document.getElementById("announcementBody");
    if (!box) return;

    var REFRESH_MS = 10000;
    var lastSignature = null;

    function render(list) {
        box.innerHTML = "";

        if (!list.length) {
            var empty = document.createElement("p");
            empty.textContent = "Hakuna matangazo kwa sasa.";
            box.appendChild(empty);
            return;
        }

        list.forEach(function (a, i) {
            var p = document.createElement("p");
            p.textContent = (i + 1) + ". " + a.body;
            box.appendChild(p);
        });
    }

    function loadAnnouncements() {
        fetch("dashboard.php?announcements=1", { cache: "no-store" })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j.ok || !Array.isArray(j.announcements)) return;

                var signature = JSON.stringify(j.announcements);
                if (signature === lastSignature) return;   /* hakuna jipya: usiguse chochote */

                lastSignature = signature;
                render(j.announcements);
            })
            .catch(function () { /* acha matangazo ya kawaida */ });
    }

    loadAnnouncements();
    setInterval(loadAnnouncements, REFRESH_MS);

    document.addEventListener("visibilitychange", function () {
        if (!document.hidden) loadAnnouncements();
    });
})();

/* MWAKA WA SASA */
document.getElementById("year").textContent =
    new Date().getFullYear();
</script>

<script>
/* TEXT REVEAL (nyongeza tu: haiguswi code ya menu, slideshow wala matangazo) */
(function () {
    /* Motion ya maandishi imebaki kwenye hero tu; maandishi ya chini ya hero hayana motion */
    var SEL = ".main-title, .header-subtitle";
    var reduce = window.matchMedia && matchMedia("(prefers-reduced-motion: reduce)").matches;
    var io = ("IntersectionObserver" in window) && !reduce ? new IntersectionObserver(function (es) {
        es.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add("rv-in"); io.unobserve(e.target); }
        });
    }, { threshold: 0.15 }) : null;

    function prep(el) {
        if (el.classList.contains("rv-ready") || el.children.length) return;
        var txt = el.textContent;
        if (!txt.trim()) return;
        var words = txt.trim().split(/\s+/), i = 0;
        el.setAttribute("aria-label", txt.trim());
        el.textContent = "";
        words.forEach(function (w, n) {
            var o = document.createElement("span"), s = document.createElement("span");
            o.className = "w"; o.setAttribute("aria-hidden", "true");
            s.textContent = w; s.style.setProperty("--i", Math.min(i++, 14));
            o.appendChild(s); el.appendChild(o);
            if (n < words.length - 1) el.appendChild(document.createTextNode(" "));
        });
        el.classList.add("rv-ready");
        if (io) io.observe(el); else el.classList.add("rv-in");
    }
    function run(root) { (root || document).querySelectorAll(SEL).forEach(prep); }

    run();
    /* Matangazo yanapobadilishwa na yale ya dashboard, yapewe reveal pia */
    var box = document.getElementById("announcementBody");
    if (box) new MutationObserver(function () { run(box); }).observe(box, { childList: true });
})();
</script>

</body>
</html>