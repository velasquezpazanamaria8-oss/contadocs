<?php
// Si ya está logueado, redirigir al sistema
session_start();
if (!empty($_SESSION['uid'])) {
    $rutas = ['superadmin'=>'/admin/dashboard.php','contador'=>'/contador/clientes.php','cliente'=>'/cliente/documentos.php'];
    $rol = $_SESSION['rol'] ?? '';
    header('Location: ' . ($rutas[$rol] ?? '/login.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="ContaDocs — Portal de documentos contables para estudios en Perú. Sube fichas RUC, planillas y PDTs. Tus clientes los descargan en segundos.">
<title>ContaDocs — Portal de documentos para estudios contables en Perú</title>
<link rel="icon" type="image/png" href="/assets/img/logo_icono.svg">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --azul:#4f6ef7;--azul-d:#3a56e0;--azul-l:#eef1fe;
  --verde:#0ea472;--verde-d:#0b8a5f;--verde-l:#e8f8f2;
  --g50:#f8fafc;--g100:#f1f5f9;--g200:#e2e8f0;--g300:#cbd5e1;
  --g400:#94a3b8;--g500:#64748b;--g600:#475569;--g700:#334155;--g900:#0f172a;
  --rojo:#ef4444;--amber:#f59e0b;--purple:#7c3aed;
}
html{scroll-behavior:smooth}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:var(--g900);background:#fff;-webkit-font-smoothing:antialiased;line-height:1.6}

/* NAV */
.nav{position:sticky;top:0;z-index:100;background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--g200);padding:0 6%}
.nav-inner{display:flex;align-items:center;justify-content:space-between;height:64px;max-width:1100px;margin:0 auto}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.nav-logo-icon{width:34px;height:34px;background:linear-gradient(135deg,var(--azul),var(--verde));border-radius:9px;display:flex;align-items:center;justify-content:center}
.nav-logo-icon svg{width:18px;height:18px;color:#fff}
.nav-logo-text{font-weight:800;font-size:16px;color:var(--g900)}
.nav-logo-text span{color:var(--azul)}
.nav-links{display:flex;gap:32px}
.nav-links a{font-size:13px;font-weight:500;color:var(--g500);text-decoration:none;transition:color .15s}
.nav-links a:hover{color:var(--g900)}
.nav-btns{display:flex;gap:10px;align-items:center}
.btn-nav-out{font-size:13px;font-weight:600;padding:8px 18px;border-radius:8px;border:1.5px solid var(--g200);background:#fff;color:var(--g700);text-decoration:none;transition:all .15s}
.btn-nav-out:hover{border-color:var(--g300);background:var(--g50)}
.btn-nav-sol{font-size:13px;font-weight:700;padding:9px 20px;border-radius:8px;background:linear-gradient(135deg,var(--azul),var(--azul-d));color:#fff;text-decoration:none;box-shadow:0 4px 12px rgba(79,110,247,.3);transition:all .15s}
.btn-nav-sol:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(79,110,247,.4)}
.nav-mobile-btn{display:none;background:none;border:none;cursor:pointer;padding:6px}
.nav-mobile-btn svg{width:22px;height:22px;color:var(--g700)}
.nav-mobile-menu{display:none;position:fixed;top:64px;left:0;right:0;background:#fff;border-bottom:1px solid var(--g200);padding:20px 6%;z-index:99;flex-direction:column;gap:12px}
.nav-mobile-menu.open{display:flex}
.nav-mobile-menu a{font-size:14px;font-weight:500;color:var(--g700);text-decoration:none;padding:10px 0;border-bottom:1px solid var(--g100)}
.nav-mobile-menu .btn-nav-sol{text-align:center;margin-top:8px}

/* HERO */
.hero{padding:90px 6% 70px;background:linear-gradient(180deg,var(--g50) 0%,#fff 100%);position:relative;overflow:hidden;text-align:center}
.hero::before{content:'';position:absolute;top:-200px;left:50%;transform:translateX(-50%);width:800px;height:800px;background:radial-gradient(circle,rgba(79,110,247,.06) 0%,transparent 70%);pointer-events:none}
.hero-inner{max-width:720px;margin:0 auto;position:relative}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:var(--azul-l);color:var(--azul-d);font-size:12px;font-weight:700;padding:6px 16px;border-radius:20px;margin-bottom:24px;border:1px solid rgba(79,110,247,.2)}
.hero-badge-dot{width:7px;height:7px;background:var(--azul);border-radius:50%;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(.8)}}
.hero h1{font-size:46px;font-weight:900;letter-spacing:-2px;line-height:1.1;margin-bottom:20px;color:var(--g900)}
.hero h1 .grad{background:linear-gradient(135deg,var(--azul),var(--verde));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero-sub{font-size:17px;color:var(--g500);line-height:1.7;margin-bottom:36px;max-width:580px;margin-left:auto;margin-right:auto}
.hero-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:36px}
.btn-hero-p{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--azul),var(--azul-d));color:#fff;font-size:15px;font-weight:700;padding:14px 28px;border-radius:12px;text-decoration:none;box-shadow:0 8px 24px rgba(79,110,247,.35);transition:all .2s}
.btn-hero-p:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(79,110,247,.45)}
.btn-hero-s{display:inline-flex;align-items:center;gap:8px;background:#fff;color:var(--g700);font-size:15px;font-weight:600;padding:14px 24px;border-radius:12px;text-decoration:none;border:1.5px solid var(--g200);transition:all .2s}
.btn-hero-s:hover{border-color:var(--g300);background:var(--g50)}
.hero-trust{display:flex;align-items:center;justify-content:center;gap:24px;flex-wrap:wrap}
.hero-trust-item{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--g400);font-weight:500}
.hero-trust-item svg{width:15px;height:15px;color:var(--verde)}

/* MOCKUP */
.mockup-wrap{padding:0 6% 70px;max-width:1100px;margin:0 auto}
.mockup{background:var(--g900);border-radius:18px;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,.25);border:1px solid #1e293b}
.mockup-bar{background:#1e293b;padding:12px 20px;display:flex;align-items:center;gap:10px}
.mockup-dots{display:flex;gap:6px}
.mockup-dot{width:11px;height:11px;border-radius:50%}
.mockup-url{background:#0f172a;border-radius:7px;padding:5px 16px;font-size:12px;color:#475569;margin:0 auto;font-family:monospace}
.mockup-body{display:flex;height:260px}
.m-sidebar{width:160px;background:#111827;border-right:1px solid #1e293b;padding:14px;flex-shrink:0}
.m-logo{display:flex;align-items:center;gap:7px;margin-bottom:18px;padding:0 4px}
.m-logo-icon{width:24px;height:24px;background:linear-gradient(135deg,var(--azul),var(--verde));border-radius:6px;flex-shrink:0}
.m-logo-text{font-size:12px;font-weight:700;color:#fff}
.m-nav-label{font-size:9px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.06em;padding:0 8px;margin-bottom:6px}
.m-item{display:flex;align-items:center;gap:7px;padding:6px 8px;border-radius:6px;font-size:10px;color:#64748b;margin-bottom:2px}
.m-item.act{background:#1e3a5f;color:#60a5fa;font-weight:600}
.m-item svg{width:12px;height:12px;flex-shrink:0}
.m-main{flex:1;padding:16px;background:#0f172a;overflow:hidden}
.m-topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.m-title{font-size:12px;font-weight:700;color:#fff}
.m-btn{background:var(--azul);color:#fff;font-size:9px;font-weight:700;padding:4px 10px;border-radius:5px}
.m-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
.m-card{background:#1e293b;border-radius:8px;padding:10px;border-top:2px solid}
.m-card.bl{border-color:var(--azul)}.m-card.gr{border-color:var(--verde)}.m-card.pu{border-color:var(--purple)}
.m-card-val{font-size:20px;font-weight:900;color:#fff;line-height:1}
.m-card-lbl{font-size:9px;color:#64748b;margin-top:3px}
.m-table{background:#1e293b;border-radius:8px;overflow:hidden}
.m-thead{display:grid;grid-template-columns:2.5fr 1fr 1fr 1fr;padding:6px 10px;background:#0f172a}
.m-thead span{font-size:8px;font-weight:700;color:#475569;text-transform:uppercase}
.m-row{display:grid;grid-template-columns:2.5fr 1fr 1fr 1fr;padding:7px 10px;border-top:1px solid #0f172a;align-items:center}
.m-row .n{font-size:10px;color:#e2e8f0;font-weight:600}
.m-row .s{font-size:10px;color:#64748b}
.m-badge{display:inline-block;padding:2px 7px;border-radius:4px;font-size:8px;font-weight:700}
.bg-g{background:#064e3b;color:#34d399}.bg-a{background:#451a03;color:#fbbf24}

/* SECCION BASE */
.section{padding:70px 6%}
.section-inner{max-width:1100px;margin:0 auto}
.sec-tag{font-size:11px;font-weight:700;color:var(--azul);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;text-align:center}
.sec-title{font-size:32px;font-weight:900;letter-spacing:-1px;color:var(--g900);text-align:center;margin-bottom:10px}
.sec-sub{font-size:16px;color:var(--g400);text-align:center;margin-bottom:48px;max-width:560px;margin-left:auto;margin-right:auto}

/* FEATURES */
.features{background:var(--g50)}
.feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.feat-card{background:#fff;border:1px solid var(--g200);border-radius:16px;padding:24px;transition:all .2s}
.feat-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,.08);border-color:var(--g300)}
.feat-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:16px}
.feat-icon svg{width:24px;height:24px}
.fi-blue{background:var(--azul-l);color:var(--azul)}
.fi-green{background:var(--verde-l);color:var(--verde-d)}
.fi-purple{background:#f5f3ff;color:var(--purple)}
.fi-amber{background:#fffbeb;color:#d97706}
.fi-red{background:#fef2f2;color:var(--rojo)}
.fi-teal{background:#e1f5ee;color:#0f6e56}
.feat-title{font-size:15px;font-weight:700;color:var(--g900);margin-bottom:8px}
.feat-desc{font-size:13px;color:var(--g500);line-height:1.6}

/* COMO FUNCIONA */
.how{background:#fff}
.how-steps{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;position:relative}
.how-steps::before{content:'';position:absolute;top:32px;left:12%;right:12%;height:2px;background:linear-gradient(90deg,var(--azul),var(--verde));z-index:0}
.how-step{text-align:center;position:relative;z-index:1}
.how-num{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,var(--azul),var(--verde));color:#fff;font-size:22px;font-weight:900;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 6px 20px rgba(79,110,247,.35)}
.how-step-title{font-size:15px;font-weight:700;color:var(--g900);margin-bottom:6px}
.how-step-desc{font-size:13px;color:var(--g500);line-height:1.55}

/* ROLES */
.roles{background:var(--g50)}
.roles-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.role-card{background:#fff;border-radius:16px;padding:28px;border:1.5px solid var(--g200);transition:all .2s}
.role-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,.08)}
.role-card.rc-blue{border-top:4px solid var(--azul)}
.role-card.rc-green{border-top:4px solid var(--verde)}
.role-card.rc-purple{border-top:4px solid var(--purple)}
.role-tag{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px}
.role-tag.blue{color:var(--azul)}.role-tag.green{color:var(--verde)}.role-tag.purple{color:var(--purple)}
.role-title{font-size:18px;font-weight:800;color:var(--g900);margin-bottom:16px}
.role-list{list-style:none;display:flex;flex-direction:column;gap:8px}
.role-list li{font-size:13px;color:var(--g600);display:flex;align-items:flex-start;gap:8px;line-height:1.4}
.role-list li svg{width:16px;height:16px;flex-shrink:0;margin-top:1px}
.li-blue svg{color:var(--azul)}.li-green svg{color:var(--verde)}.li-purple svg{color:var(--purple)}

/* PLANES */
.planes{background:#fff}
.planes-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;max-width:900px;margin:0 auto}
.plan-card{border:1.5px solid var(--g200);border-radius:16px;padding:28px;transition:all .2s;position:relative}
.plan-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,.08)}
.plan-card.popular{border-color:var(--azul);box-shadow:0 0 0 3px rgba(79,110,247,.12)}
.plan-pop-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,var(--azul),var(--azul-d));color:#fff;font-size:11px;font-weight:700;padding:4px 16px;border-radius:12px;white-space:nowrap}
.plan-name{font-size:17px;font-weight:800;color:var(--g900);margin-bottom:6px}
.plan-price{font-size:36px;font-weight:900;color:var(--g900);letter-spacing:-1px;margin:12px 0 4px;line-height:1}
.plan-price span{font-size:14px;font-weight:500;color:var(--g400)}
.plan-limit{font-size:13px;color:var(--g400);margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--g100)}
.plan-feats{display:flex;flex-direction:column;gap:10px;margin-bottom:24px}
.plan-feat{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--g600)}
.plan-feat svg{width:16px;height:16px;color:var(--verde);flex-shrink:0}
.btn-plan{width:100%;padding:12px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;border:none;text-align:center;display:block;text-decoration:none;transition:all .15s}
.btn-plan-out{background:#fff;border:1.5px solid var(--g200);color:var(--g700)}
.btn-plan-out:hover{border-color:var(--azul);color:var(--azul)}
.btn-plan-sol{background:linear-gradient(135deg,var(--azul),var(--azul-d));color:#fff;box-shadow:0 4px 12px rgba(79,110,247,.3)}
.btn-plan-sol:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(79,110,247,.4)}

/* FAQ */
.faq{background:var(--g50)}
.faq-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:900px;margin:0 auto}
.faq-item{background:#fff;border:1px solid var(--g200);border-radius:12px;padding:20px}
.faq-q{font-size:14px;font-weight:700;color:var(--g900);margin-bottom:8px;display:flex;align-items:flex-start;gap:8px}
.faq-q-icon{width:20px;height:20px;background:var(--azul-l);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:10px;font-weight:900;color:var(--azul);margin-top:1px}
.faq-a{font-size:13px;color:var(--g500);line-height:1.6;padding-left:28px}

/* CTA FINAL */
.cta-final{padding:80px 6%;background:linear-gradient(135deg,var(--g900) 0%,#1e3a5f 100%);text-align:center;position:relative;overflow:hidden}
.cta-final::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 50% 0%,rgba(79,110,247,.2) 0%,transparent 60%)}
.cta-final-inner{max-width:600px;margin:0 auto;position:relative}
.cta-final h2{font-size:36px;font-weight:900;color:#fff;letter-spacing:-1px;line-height:1.2;margin-bottom:14px}
.cta-final h2 span{background:linear-gradient(135deg,#60a5fa,#34d399);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.cta-final p{font-size:16px;color:rgba(255,255,255,.55);margin-bottom:36px;line-height:1.6}
.cta-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap}
.btn-cta-wa{display:inline-flex;align-items:center;gap:10px;background:#25d366;color:#fff;font-size:15px;font-weight:700;padding:14px 28px;border-radius:12px;text-decoration:none;box-shadow:0 8px 24px rgba(37,211,102,.35);transition:all .2s}
.btn-cta-wa:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(37,211,102,.45)}
.btn-cta-login{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.1);color:#fff;font-size:15px;font-weight:600;padding:14px 24px;border-radius:12px;text-decoration:none;border:1px solid rgba(255,255,255,.2);transition:all .2s}
.btn-cta-login:hover{background:rgba(255,255,255,.15)}

/* FOOTER */
.footer{padding:32px 6%;border-top:1px solid var(--g200);background:#fff}
.footer-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px}
.footer-logo{display:flex;align-items:center;gap:8px;font-weight:800;font-size:15px;color:var(--g900);text-decoration:none}
.footer-logo-icon{width:28px;height:28px;background:linear-gradient(135deg,var(--azul),var(--verde));border-radius:7px;display:flex;align-items:center;justify-content:center}
.footer-logo-icon svg{width:14px;height:14px;color:#fff}
.footer-logo span{color:var(--azul)}
.footer-copy{font-size:12px;color:var(--g400)}
.footer-links{display:flex;gap:24px}
.footer-links a{font-size:13px;color:var(--g400);text-decoration:none;transition:color .15s}
.footer-links a:hover{color:var(--g700)}

/* WHATSAPP FLOAT */
.wa-float{position:fixed;bottom:24px;right:24px;z-index:200;width:56px;height:56px;background:#25d366;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 24px rgba(37,211,102,.4);text-decoration:none;transition:all .2s;animation:waFloat 3s ease-in-out infinite}
.wa-float:hover{transform:scale(1.1);box-shadow:0 10px 32px rgba(37,211,102,.5)}
.wa-float svg{width:28px;height:28px;color:#fff}
@keyframes waFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
.wa-tooltip{position:absolute;right:70px;background:var(--g900);color:#fff;font-size:12px;font-weight:600;padding:6px 12px;border-radius:8px;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity .2s}
.wa-float:hover .wa-tooltip{opacity:1}

/* RESPONSIVE */
@media(max-width:900px){
  .feat-grid,.roles-grid,.planes-grid{grid-template-columns:1fr 1fr}
  .how-steps{grid-template-columns:1fr 1fr}
  .how-steps::before{display:none}
  .faq-grid{grid-template-columns:1fr}
  .hero h1{font-size:34px}
  .nav-links{display:none}
  .nav-mobile-btn{display:block}
}
@media(max-width:600px){
  .feat-grid,.roles-grid,.planes-grid,.how-steps{grid-template-columns:1fr}
  .hero{padding:60px 5% 50px}
  .hero h1{font-size:28px;letter-spacing:-1px}
  .hero-sub{font-size:15px}
  .btn-hero-p,.btn-hero-s{width:100%;justify-content:center;font-size:14px}
  .hero-btns{flex-direction:column}
  .section{padding:50px 5%}
  .sec-title{font-size:26px}
  .cta-final h2{font-size:26px}
  .btn-cta-wa,.btn-cta-login{width:100%;justify-content:center}
  .cta-btns{flex-direction:column}
  .footer-inner{flex-direction:column;text-align:center}
  .mockup-body{height:180px}
  .m-grid{grid-template-columns:repeat(3,1fr)}
}
</style>
</head>
<body>

<!-- BOTÓN WHATSAPP FLOTANTE -->
<a href="https://wa.me/51972309300?text=Hola%2C+quiero+información+sobre+ContaDocs" class="wa-float" target="_blank">
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.553 4.112 1.524 5.84L0 24l6.335-1.524A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.733.898.914-3.643-.234-.374A9.817 9.817 0 012.182 12C2.182 6.578 6.578 2.182 12 2.182S21.818 6.578 21.818 12 17.422 21.818 12 21.818z"/></svg>
  <div class="wa-tooltip">¿Tienes dudas?</div>
</a>

<!-- NAV -->
<nav class="nav">
  <div class="nav-inner">
    <a href="#" class="nav-logo">
      <div class="nav-logo-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      </div>
      <span class="nav-logo-text">Conta<span>Docs</span></span>
    </a>
    <div class="nav-links">
      <a href="#caracteristicas">Características</a>
      <a href="#como-funciona">Cómo funciona</a>
      <a href="#planes">Planes</a>
      <a href="#faq">Preguntas</a>
    </div>
    <div class="nav-btns">
      <a href="/login.php" class="btn-nav-out">Ingresar</a>
      <a href="https://wa.me/51972309300?text=Quiero+acceso+a+ContaDocs" class="btn-nav-sol" target="_blank">Solicitar acceso →</a>
    </div>
    <button class="nav-mobile-btn" onclick="toggleMenu()" aria-label="Menú">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
  </div>
  <div class="nav-mobile-menu" id="mobileMenu">
    <a href="#caracteristicas" onclick="toggleMenu()">Características</a>
    <a href="#como-funciona" onclick="toggleMenu()">Cómo funciona</a>
    <a href="#planes" onclick="toggleMenu()">Planes</a>
    <a href="#faq" onclick="toggleMenu()">Preguntas frecuentes</a>
    <a href="/login.php" class="btn-nav-out" style="text-align:center;padding:10px">Ingresar al sistema</a>
    <a href="https://wa.me/51972309300?text=Quiero+acceso+a+ContaDocs" class="btn-nav-sol" target="_blank" style="text-align:center;padding:12px">Solicitar acceso por WhatsApp →</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-badge">
      <div class="hero-badge-dot"></div>
      Diseñado para estudios contables en Perú
    </div>
    <h1>Deja de responder el mismo<br><span class="grad">WhatsApp todos los días</span></h1>
    <p class="hero-sub">Sube la ficha RUC, planillas, PDTs y más. Tus clientes los descargan desde su portal personal. Sin llamadas, sin interrupciones, a cualquier hora.</p>
    <div class="hero-btns">
      <a href="https://wa.me/51972309300?text=Quiero+acceso+a+ContaDocs" class="btn-hero-p" target="_blank">
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.553 4.112 1.524 5.84L0 24l6.335-1.524A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.733.898.914-3.643-.234-.374A9.817 9.817 0 012.182 12C2.182 6.578 6.578 2.182 12 2.182S21.818 6.578 21.818 12 17.422 21.818 12 21.818z"/></svg>
        Solicitar acceso por WhatsApp
      </a>
      <a href="/login.php" class="btn-hero-s">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
        Ingresar al sistema
      </a>
    </div>
    <div class="hero-trust">
      <div class="hero-trust-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Sin contratos
      </div>
      <div class="hero-trust-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Cancela cuando quieras
      </div>
      <div class="hero-trust-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Soporte por WhatsApp
      </div>
      <div class="hero-trust-item">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Activación en minutos
      </div>
    </div>
  </div>
</section>

<!-- MOCKUP DEL SISTEMA -->
<div class="mockup-wrap">
  <div class="mockup">
    <div class="mockup-bar">
      <div class="mockup-dots">
        <div class="mockup-dot" style="background:#ef4444"></div>
        <div class="mockup-dot" style="background:#f59e0b"></div>
        <div class="mockup-dot" style="background:#22c55e"></div>
      </div>
      <div class="mockup-url">contadocs.tudominio.com.pe/contador/clientes</div>
    </div>
    <div class="mockup-body">
      <div class="m-sidebar">
        <div class="m-logo">
          <div class="m-logo-icon"></div>
          <span class="m-logo-text">ContaDocs</span>
        </div>
        <div class="m-nav-label">Gestión</div>
        <div class="m-item act">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          Mis clientes
        </div>
        <div class="m-item">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Documentos
        </div>
        <div class="m-item">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
          Subir docs
        </div>
        <div class="m-item">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
          Categorías
        </div>
      </div>
      <div class="m-main">
        <div class="m-topbar">
          <span class="m-title">Mis clientes · Estudio Noruega · 8/25 empresas</span>
          <span class="m-btn">+ Agregar empresa</span>
        </div>
        <div class="m-grid">
          <div class="m-card bl"><div class="m-card-val">8</div><div class="m-card-lbl">Empresas activas</div></div>
          <div class="m-card gr"><div class="m-card-val">43</div><div class="m-card-lbl">Docs subidos</div></div>
          <div class="m-card pu"><div class="m-card-val">127</div><div class="m-card-lbl">Descargas totales</div></div>
        </div>
        <div class="m-table">
          <div class="m-thead"><span>Empresa cliente</span><span>RUC</span><span>Docs</span><span>Estado</span></div>
          <div class="m-row"><span class="n">Inversiones Quispe SAC</span><span class="s">20501234</span><span class="n">4</span><span><div class="m-badge bg-g">Activo</div></span></div>
          <div class="m-row"><span class="n">Comercial Flores EIRL</span><span class="s">20598123</span><span class="n">0</span><span><div class="m-badge bg-a">Pendiente</div></span></div>
          <div class="m-row"><span class="n">Minera Los Andes SAC</span><span class="s">20412876</span><span class="n">6</span><span><div class="m-badge bg-g">Activo</div></span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CARACTERÍSTICAS -->
<section class="section features" id="caracteristicas">
  <div class="section-inner">
    <div class="sec-tag">Características</div>
    <div class="sec-title">Todo lo que necesitas, nada de más</div>
    <div class="sec-sub">Diseñado para contadores que valoran su tiempo</div>
    <div class="feat-grid">
      <div class="feat-card">
        <div class="feat-icon fi-blue">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
        </div>
        <div class="feat-title">Portal exclusivo por empresa</div>
        <div class="feat-desc">Cada cliente tiene su propio acceso y solo ve sus documentos. Sin mezclas, sin errores, sin llamadas preguntando dónde está su ficha RUC.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon fi-green">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        </div>
        <div class="feat-title">Sube documentos en segundos</div>
        <div class="feat-desc">Arrastra el PDF o imagen, selecciona empresa, tipo y período. Sin formularios complicados. Tus clientes lo ven al instante.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon fi-purple">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        </div>
        <div class="feat-title">Categorías que tú defines</div>
        <div class="feat-desc">Crea tus propios tipos: Ficha RUC, Planilla, PDT 621, T-Registro, Boletas, CTS, Gratificación y los que necesites.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon fi-amber">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div class="feat-title">Control de descargas</div>
        <div class="feat-desc">Sabe exactamente quién descargó qué documento y cuándo. Historial completo por empresa, período y tipo de archivo.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon fi-red">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <div class="feat-title">Seguridad garantizada</div>
        <div class="feat-desc">Cada empresa accede solo a sus documentos. Contraseñas individuales y descarga directa sin URLs públicas.</div>
      </div>
      <div class="feat-card">
        <div class="feat-icon fi-teal">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2v-2a4 4 0 00-8 0v2a2 2 0 002 2zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="feat-title">Funciona en cualquier dispositivo</div>
        <div class="feat-desc">Tus clientes descargan desde el celular, tablet o computadora. Sin instalar nada, solo con el navegador.</div>
      </div>
    </div>
  </div>
</section>

<!-- CÓMO FUNCIONA -->
<section class="section how" id="como-funciona">
  <div class="section-inner">
    <div class="sec-tag">Cómo funciona</div>
    <div class="sec-title">Listo en 4 pasos simples</div>
    <div class="sec-sub">Sin configuraciones complicadas ni instalaciones</div>
    <div class="how-steps">
      <div class="how-step">
        <div class="how-num">1</div>
        <div class="how-step-title">Nos contactas por WhatsApp</div>
        <div class="how-step-desc">Escríbenos, te activamos tu cuenta en minutos y te enviamos tus credenciales de acceso.</div>
      </div>
      <div class="how-step">
        <div class="how-num">2</div>
        <div class="how-step-title">Registras tus clientes</div>
        <div class="how-step-desc">Agrega las empresas con su RUC y email. El sistema crea sus accesos automáticamente.</div>
      </div>
      <div class="how-step">
        <div class="how-num">3</div>
        <div class="how-step-title">Subes sus documentos</div>
        <div class="how-step-desc">PDFs organizados por empresa, categoría y mes. Arrastra y listo, sin complicaciones.</div>
      </div>
      <div class="how-step">
        <div class="how-num">4</div>
        <div class="how-step-title">Ellos descargan solos</div>
        <div class="how-step-desc">Tu cliente entra con su usuario y descarga lo que necesita. Sin llamarte, sin WhatsApp.</div>
      </div>
    </div>
  </div>
</section>

<!-- ROLES -->
<section class="section roles">
  <div class="section-inner">
    <div class="sec-tag">Roles del sistema</div>
    <div class="sec-title">Tres tipos de acceso</div>
    <div class="sec-sub">Cada usuario ve exactamente lo que necesita</div>
    <div class="roles-grid">
      <div class="role-card rc-blue">
        <div class="role-tag blue">Administrador (tú)</div>
        <div class="role-title">Control total del sistema</div>
        <ul class="role-list">
          <li class="li-blue"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Gestiona todos los estudios contables</li>
          <li class="li-blue"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Crea y edita planes de suscripción</li>
          <li class="li-blue"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Controla pagos y vencimientos</li>
          <li class="li-blue"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Activa y suspende cuentas</li>
          <li class="li-blue"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Métricas de ingresos y uso</li>
        </ul>
      </div>
      <div class="role-card rc-green">
        <div class="role-tag green">Contador (tu cliente)</div>
        <div class="role-title">Gestión de clientes y docs</div>
        <ul class="role-list">
          <li class="li-green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Agrega y gestiona sus empresas</li>
          <li class="li-green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Sube PDFs e imágenes</li>
          <li class="li-green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Crea categorías personalizadas</li>
          <li class="li-green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Ve quién descargó qué y cuándo</li>
          <li class="li-green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Cambia contraseñas de clientes</li>
        </ul>
      </div>
      <div class="role-card rc-purple">
        <div class="role-tag purple">Empresa cliente</div>
        <div class="role-title">Descarga sus documentos</div>
        <ul class="role-list">
          <li class="li-purple"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Accede con usuario y contraseña</li>
          <li class="li-purple"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Ve solo sus propios documentos</li>
          <li class="li-purple"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Filtra por mes y categoría</li>
          <li class="li-purple"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Descarga en un clic</li>
          <li class="li-purple"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/></svg>Historial de sus descargas</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- PLANES -->
<section class="section planes" id="planes">
  <div class="section-inner">
    <div class="sec-tag">Precios</div>
    <div class="sec-title">Planes simples y transparentes</div>
    <div class="sec-sub">Pago mensual por Yape, Plin o transferencia. Sin pasarela, sin comisiones.</div>
    <div class="planes-grid">
      <div class="plan-card">
        <div class="plan-name">Gratis</div>
        <div class="plan-price">S/ 0 <span>/mes</span></div>
        <div class="plan-limit">Hasta 1 empresa cliente</div>
        <div class="plan-feats">
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Portal de documentos</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>8 Categorías</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Descarga para clientes</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Soporte por WhatsApp</div>
        </div>
        <a href="https://wa.me/51972309300?text=Quiero+el+plan+Básico+de+ContaDocs" class="btn-plan btn-plan-out" target="_blank">Solicitar por WhatsApp</a>
      </div>
      <div class="plan-card">
        <div class="plan-name">Básico</div>
        <div class="plan-price">S/ 29.90 <span>/mes</span></div>
        <div class="plan-limit">Hasta 5 empresas cliente</div>
        <div class="plan-feats">
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Portal de documentos</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>90 Categorías</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Descarga para clientes</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Soporte por WhatsApp</div>
        </div>
        <a href="https://wa.me/51972309300?text=Quiero+el+plan+Básico+de+ContaDocs" class="btn-plan btn-plan-out" target="_blank">Solicitar por WhatsApp</a>
      </div>
      <div class="plan-card popular">
        <div class="plan-pop-badge">⭐ Más popular</div>
        <div class="plan-name">Profesional</div>
        <div class="plan-price">S/ 49.90 <span>/mes</span></div>
        <div class="plan-limit">Hasta 20 empresas cliente</div>
        <div class="plan-feats">
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Todo lo del Básico</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Historial de descargas</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Categorías ilimitadas</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Control detallado por empresa</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Soporte prioritario</div>
        </div>
        <a href="https://wa.me/51972309300?text=Quiero+el+plan+Profesional+de+ContaDocs" class="btn-plan btn-plan-sol" target="_blank">Solicitar por WhatsApp</a>
      </div>
      <div class="plan-card">
        <div class="plan-name">Ilimitado</div>
        <div class="plan-price">S/ 99.90 <span>/mes</span></div>
        <div class="plan-limit">Empresas ilimitadas</div>
        <div class="plan-feats">
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Todo lo anterior</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Sin límite de empresas</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Precio fijo siempre</div>
          <div class="plan-feat"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>Ideal para estudios grandes</div>
        </div>
        <a href="https://wa.me/51972309300?text=Quiero+el+plan+Ilimitado+de+ContaDocs" class="btn-plan btn-plan-out" target="_blank">Solicitar por WhatsApp</a>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section faq" id="faq">
  <div class="section-inner">
    <div class="sec-tag">Preguntas frecuentes</div>
    <div class="sec-title">Resolvemos tus dudas</div>
    <div class="sec-sub" style="margin-bottom:36px">Si tienes otra pregunta, escríbenos por WhatsApp</div>
    <div class="faq-grid">
      <div class="faq-item">
        <div class="faq-q"><div class="faq-q-icon">?</div>¿Cómo pago la suscripción?</div>
        <div class="faq-a">Por Yape, Plin o transferencia bancaria. Nos envías el voucher por WhatsApp y activamos tu cuenta en minutos. Sin pasarelas de pago, sin comisiones.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q"><div class="faq-q-icon">?</div>¿Mis clientes tienen que instalar algo?</div>
        <div class="faq-a">No. Solo necesitan un navegador (Chrome, Safari, etc.) en su celular o computadora. Entran a la URL, ponen su email y contraseña, y descargan.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q"><div class="faq-q-icon">?</div>¿Qué tipos de archivos puedo subir?</div>
        <div class="faq-a">PDF, JPG y PNG. El límite por archivo es 15 MB. Puedes subir tantos documentos como quieras dentro de tu plan.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q"><div class="faq-q-icon">?</div>¿Un cliente puede ver documentos de otra empresa?</div>
        <div class="faq-a">No, es imposible. Cada empresa accede solo a sus propios documentos. El sistema está diseñado para que un cliente nunca pueda ver información de otro.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q"><div class="faq-q-icon">?</div>¿Puedo cambiar de plan después?</div>
        <div class="faq-a">Sí, cuando quieras. Si necesitas más capacidad, nos avisas por WhatsApp y te cambiamos el plan. Tus datos y documentos se mantienen intactos.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q"><div class="faq-q-icon">?</div>¿Qué pasa si no renuevo a tiempo?</div>
        <div class="faq-a">Te avisamos con 7 días de anticipación por WhatsApp. Si no renuevas, el acceso se suspende temporalmente pero tus datos no se eliminan.</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA FINAL -->
<section class="cta-final">
  <div class="cta-final-inner">
    <h2>¿Listo para ahorrar horas<br><span>cada semana</span>?</h2>
    <p>Únete a los contadores que ya dejaron de responder el mismo WhatsApp todos los días. Activación en minutos, soporte inmediato.</p>
    <div class="cta-btns">
      <a href="https://wa.me/51972309300?text=Quiero+acceso+a+ContaDocs" class="btn-cta-wa" target="_blank">
        <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.553 4.112 1.524 5.84L0 24l6.335-1.524A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.733.898.914-3.643-.234-.374A9.817 9.817 0 012.182 12C2.182 6.578 6.578 2.182 12 2.182S21.818 6.578 21.818 12 17.422 21.818 12 21.818z"/></svg>
        Solicitar acceso ahora
      </a>
      <a href="/login.php" class="btn-cta-login">
        Ingresar al sistema →
      </a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-inner">
    <a href="#" class="footer-logo">
      <div class="footer-logo-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      </div>
      Conta<span>Docs</span>
    </a>
    <div class="footer-copy">© 2025 ContaDocs · Hecho para contadores peruanos</div>
    <div class="footer-links">
      <a href="/login.php">Ingresar</a>
      <a href="https://wa.me/51972309300" target="_blank">WhatsApp</a>
    </div>
  </div>
</footer>

<script>
function toggleMenu(){
  const m = document.getElementById('mobileMenu');
  m.classList.toggle('open');
}
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const t = document.querySelector(a.getAttribute('href'));
    if(t){ e.preventDefault(); t.scrollIntoView({behavior:'smooth'}); }
  });
});
</script>
</body>
</html>
