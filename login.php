<?php
require_once __DIR__ . '/bootstrap.php';
if(Auth::estaLogueado()){
  $r=['superadmin'=>'/admin/dashboard.php','contador'=>'/contador/clientes.php','cliente'=>'/cliente/documentos.php'];
  redirect($r[Auth::rol()]??'/login.php');
}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email=trim(strtolower($_POST['email']??''));
  $pass=$_POST['password']??'';
  if(!$email||!$pass){$error='Completa todos los campos.';}
  else{
    $u=Database::fetch("SELECT u.*,e.estado as est_estado,e.plan as est_plan FROM usuarios u LEFT JOIN estudios e ON u.estudio_id=e.id WHERE u.email=? AND u.activo=1",[$email]);
    if(!$u||!Auth::verificarPassword($pass,$u['password'])){$error='Correo o contraseña incorrectos.';}
    elseif($u['estudio_id']&&$u['est_estado']==='vencido'){$error='Tu acceso está vencido. Contacta a tu contador.';}
    elseif($u['estudio_id']&&$u['est_estado']==='suspendido'){$error='Cuenta suspendida. Contacta a soporte.';}
    else{
      $u['plan']=$u['est_plan']??'';
      Auth::iniciarSesion($u);
      if($u['primer_login'])redirect('/cambiar-password.php');
      $r=['superadmin'=>'/admin/dashboard.php','contador'=>'/contador/clientes.php','cliente'=>'/cliente/documentos.php'];
      redirect($r[$u['rol']]??'/login.php');
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ingresar — ContaDocs</title>
<link rel="icon" type="image/png" href="/assets/img/logo_icono.svg">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --azul:#4f6ef7;--azul-d:#3a56e0;
  --verde:#0ea472;--verde-d:#0b8a5f;
  --g200:#e2e8f0;--g400:#94a3b8;--g500:#64748b;--g700:#334155;--g900:#0f172a;
  --rojo:#ef4444;
}
html,body{height:100%;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;-webkit-font-smoothing:antialiased}
body{display:flex;min-height:100vh;background:#0f172a}

/* PANEL IZQUIERDO */
.left{
  flex:1;display:flex;flex-direction:column;justify-content:space-between;
  padding:40px 48px;
  background:linear-gradient(145deg,#0f172a 0%,#1a2d4a 50%,#0f2744 100%);
  position:relative;overflow:hidden;
  min-height:100vh;
}
.left::before{
  content:'';position:absolute;top:-200px;left:-200px;
  width:600px;height:600px;
  background:radial-gradient(circle,rgba(79,110,247,.18) 0%,transparent 70%);
  pointer-events:none;
}
.left::after{
  content:'';position:absolute;bottom:-150px;right:-100px;
  width:500px;height:500px;
  background:radial-gradient(circle,rgba(14,164,114,.12) 0%,transparent 70%);
  pointer-events:none;
}

/* Grid decorativo de puntos */
.dots-grid{
  position:absolute;inset:0;
  background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);
  background-size:32px 32px;
  pointer-events:none;
}

.left-nav{display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1}
.logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--azul),var(--verde));border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(79,110,247,.4)}
.logo-icon svg{width:18px;height:18px;color:#fff}
.logo-text{font-size:16px;font-weight:800;color:#fff}
.logo-text span{color:#60a5fa}
.back-link{display:flex;align-items:center;gap:6px;font-size:13px;color:rgba(255,255,255,.45);text-decoration:none;transition:color .15s}
.back-link:hover{color:rgba(255,255,255,.8)}
.back-link svg{width:15px;height:15px}

.left-content{position:relative;z-index:1;flex:1;display:flex;flex-direction:column;justify-content:center;padding:48px 0}
.left-tag{display:inline-flex;align-items:center;gap:8px;background:rgba(79,110,247,.15);border:1px solid rgba(79,110,247,.25);color:#93c5fd;font-size:12px;font-weight:600;padding:6px 14px;border-radius:20px;margin-bottom:28px}
.left-tag-dot{width:6px;height:6px;border-radius:50%;background:#60a5fa;animation:blink 2s infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.4}}
.left-title{font-size:40px;font-weight:900;color:#fff;line-height:1.15;letter-spacing:-1.5px;margin-bottom:18px}
.left-title span{background:linear-gradient(135deg,#60a5fa,#34d399);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.left-desc{font-size:15px;color:rgba(255,255,255,.5);line-height:1.7;margin-bottom:36px;max-width:420px}

/* Features list */
.feat-list{display:flex;flex-direction:column;gap:14px;margin-bottom:40px}
.feat-item{display:flex;align-items:center;gap:12px}
.feat-check{width:28px;height:28px;border-radius:8px;background:rgba(14,164,114,.15);border:1px solid rgba(14,164,114,.25);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.feat-check svg{width:14px;height:14px;color:#34d399}
.feat-text{font-size:13px;color:rgba(255,255,255,.65);font-weight:500}

/* Stats */
.stats{display:flex;gap:32px}
.stat-val{font-size:26px;font-weight:900;color:#fff;line-height:1}
.stat-label{font-size:11px;color:rgba(255,255,255,.4);margin-top:3px}

.left-footer{position:relative;z-index:1;font-size:12px;color:rgba(255,255,255,.25)}

/* PANEL DERECHO */
.right{
  width:480px;flex-shrink:0;
  background:#fff;
  display:flex;flex-direction:column;justify-content:center;
  padding:60px 48px;
  position:relative;
}
.right-header{margin-bottom:36px}
.right-eyebrow{font-size:12px;font-weight:700;color:var(--azul);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px}
.right-title{font-size:28px;font-weight:900;color:var(--g900);letter-spacing:-.8px;margin-bottom:6px}
.right-sub{font-size:14px;color:var(--g400)}

/* FORM */
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:12px;font-weight:700;color:var(--g700);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em}
.input-wrap{position:relative}
.input-wrap svg.icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);width:17px;height:17px;color:var(--g400);pointer-events:none}
.form-input{
  width:100%;padding:13px 14px 13px 44px;
  border:1.5px solid var(--g200);border-radius:10px;
  font-size:14px;color:var(--g900);background:#f8fafc;
  outline:none;transition:all .15s;
}
.form-input:focus{border-color:var(--azul);background:#fff;box-shadow:0 0 0 3px rgba(79,110,247,.1)}
.form-input.err{border-color:var(--rojo);background:#fef2f2}
.eye-btn{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--g400);padding:2px;transition:color .15s}
.eye-btn:hover{color:var(--g700)}
.eye-btn svg{width:17px;height:17px}

.alert-err{
  display:flex;align-items:center;gap:10px;
  background:#fef2f2;border:1px solid #fecaca;
  border-radius:10px;padding:12px 14px;
  font-size:13px;color:#dc2626;font-weight:500;
  margin-bottom:20px;
}
.alert-err svg{width:16px;height:16px;flex-shrink:0}

.btn-submit{
  width:100%;padding:14px;
  background:linear-gradient(135deg,var(--azul),var(--azul-d));
  color:#fff;border:none;border-radius:10px;
  font-size:15px;font-weight:700;cursor:pointer;
  box-shadow:0 6px 20px rgba(79,110,247,.35);
  transition:all .2s;position:relative;overflow:hidden;
  display:flex;align-items:center;justify-content:center;gap:8px;
  margin-top:8px;
}
.btn-submit:hover{transform:translateY(-1px);box-shadow:0 10px 28px rgba(79,110,247,.45)}
.btn-submit:active{transform:translateY(0)}
.btn-submit:disabled{opacity:.6;cursor:not-allowed;transform:none}
.btn-submit svg{width:18px;height:18px}

.divider{display:flex;align-items:center;gap:12px;margin:24px 0}
.divider-line{flex:1;height:1px;background:var(--g200)}
.divider-text{font-size:12px;color:var(--g400);font-weight:500}

.btn-back-home{
  width:100%;padding:12px;
  background:#f8fafc;color:var(--g600);
  border:1.5px solid var(--g200);border-radius:10px;
  font-size:14px;font-weight:600;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:8px;
  text-decoration:none;transition:all .15s;
}
.btn-back-home:hover{border-color:var(--g400);background:#f1f5f9;color:var(--g900)}
.btn-back-home svg{width:16px;height:16px}

.right-footer{margin-top:32px;padding-top:24px;border-top:1px solid var(--g200);text-align:center;font-size:12px;color:var(--g400)}

/* Decoración derecha */
.right-deco{position:absolute;bottom:0;right:0;width:120px;height:120px;background:linear-gradient(135deg,rgba(79,110,247,.06),rgba(14,164,114,.06));border-radius:100px 0 0 0;pointer-events:none}

/* RESPONSIVE */
@media(max-width:900px){
  .left{display:none}
  .right{width:100%;padding:40px 28px;justify-content:center;min-height:100vh}
  .right-mobile-logo{display:flex!important}
}
.right-mobile-logo{display:none;align-items:center;gap:10px;margin-bottom:32px;text-decoration:none}
.right-mobile-logo-icon{width:36px;height:36px;background:linear-gradient(135deg,var(--azul),var(--verde));border-radius:10px;display:flex;align-items:center;justify-content:center}
.right-mobile-logo-icon svg{width:18px;height:18px;color:#fff}
.right-mobile-logo-text{font-size:16px;font-weight:800;color:var(--g900)}
.right-mobile-logo-text span{color:var(--azul)}
</style>
</head>
<body>

<!-- PANEL IZQUIERDO -->
<div class="left">
  <div class="dots-grid"></div>

  <nav class="left-nav">
    <a href="/index.php" class="logo">
      <div class="logo-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      </div>
      <span class="logo-text">Conta<span>Docs</span></span>
    </a>
    <a href="/index.php" class="back-link">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      Volver al inicio
    </a>
  </nav>

  <div class="left-content">
    <div class="left-tag">
      <div class="left-tag-dot"></div>
      Portal de documentos contables · Perú
    </div>
    <h1 class="left-title">Tu estudio contable<br><span>sin interrupciones</span></h1>
    <p class="left-desc">Sube la ficha RUC, planillas y PDTs de cada cliente. Ellos los descargan cuando quieran, sin llamarte ni escribirte.</p>

    <div class="feat-list">
      <div class="feat-item">
        <div class="feat-check"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
        <span class="feat-text">Portal exclusivo por empresa cliente</span>
      </div>
      <div class="feat-item">
        <div class="feat-check"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
        <span class="feat-text">Sube PDFs e imágenes en segundos</span>
      </div>
      <div class="feat-item">
        <div class="feat-check"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
        <span class="feat-text">Categorías personalizadas para tu estudio</span>
      </div>
      <div class="feat-item">
        <div class="feat-check"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
        <span class="feat-text">Control de quién descargó qué y cuándo</span>
      </div>
    </div>

    <div class="stats">
      <div>
        <div class="stat-val">3</div>
        <div class="stat-label">Planes disponibles</div>
      </div>
      <div>
        <div class="stat-val">S/49</div>
        <div class="stat-label">Desde por mes</div>
      </div>
      <div>
        <div class="stat-val">∞</div>
        <div class="stat-label">Documentos subidos</div>
      </div>
    </div>
  </div>

  <div class="left-footer">© 2025 ContaDocs · Hecho para contadores peruanos</div>
</div>

<!-- PANEL DERECHO -->
<div class="right">
  <div class="right-deco"></div>

  <!-- Logo móvil -->
  <a href="/index.php" class="right-mobile-logo">
    <div class="right-mobile-logo-icon">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </div>
    <span class="right-mobile-logo-text">Conta<span>Docs</span></span>
  </a>

  <div class="right-header">
    <div class="right-eyebrow">Bienvenido de vuelta</div>
    <div class="right-title">Ingresa a tu cuenta</div>
    <div class="right-sub">Escribe tus credenciales para continuar</div>
  </div>

  <?php if($error): ?>
  <div class="alert-err">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <form method="POST" id="loginForm">
    <div class="form-group">
      <label class="form-label">Correo electrónico</label>
      <div class="input-wrap">
        <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <input type="email" name="email" class="form-input <?= $error?'err':'' ?>"
          placeholder="tucorreo@email.com"
          value="<?= htmlspecialchars($_POST['email']??'', ENT_QUOTES, 'UTF-8') ?>"
          required autofocus autocomplete="email">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Contraseña</label>
      <div class="input-wrap">
        <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <input type="password" name="password" id="passInput" class="form-input <?= $error?'err':'' ?>"
          placeholder="Tu contraseña" required autocomplete="current-password">
        <button type="button" class="eye-btn" onclick="togglePass()" id="eyeBtn" aria-label="Ver contraseña">
          <svg id="eyeOpen" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          <svg id="eyeClosed" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
        </button>
      </div>
    </div>

    <button type="submit" class="btn-submit" id="submitBtn">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
      <span id="btnText">Ingresar al sistema</span>
    </button>
  </form>

  <div class="divider">
    <div class="divider-line"></div>
    <span class="divider-text">o</span>
    <div class="divider-line"></div>
  </div>

  <a href="/index.php" class="btn-back-home">
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    Volver a la página de inicio
  </a>

  <div class="right-footer">
    ¿Problemas para ingresar? Contacta a tu contador o
    <a href="https://wa.me/51999999999" target="_blank" style="color:#0ea472;font-weight:600;text-decoration:none">escríbenos por WhatsApp</a>
  </div>
</div>

<script>
function togglePass(){
  const i=document.getElementById('passInput');
  const o=document.getElementById('eyeOpen');
  const c=document.getElementById('eyeClosed');
  if(i.type==='password'){i.type='text';o.style.display='none';c.style.display='block';}
  else{i.type='password';o.style.display='block';c.style.display='none';}
}
document.getElementById('loginForm').addEventListener('submit',function(){
  const btn=document.getElementById('submitBtn');
  const txt=document.getElementById('btnText');
  btn.disabled=true;
  txt.textContent='Ingresando...';
});
</script>
</body>
</html>
