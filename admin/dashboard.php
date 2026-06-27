<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('superadmin');

$stats = Database::fetch("SELECT COUNT(*) as total, SUM(estado='activo') as activos, SUM(estado='vencido') as vencidos FROM estudios");
$empresas_total = Database::fetch("SELECT COUNT(*) as n FROM empresas_cliente")['n'];
$docs_total     = Database::fetch("SELECT COUNT(*) as n FROM documentos")['n'];
$descargas_total= Database::fetch("SELECT COUNT(*) as n FROM descargas_log")['n'];
$por_vencer     = Database::fetch("SELECT COUNT(*) as n FROM estudios WHERE estado='activo' AND vence_en BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)")['n'];

$planes_lista = getPlanes();
$estudios_raw = Database::fetchAll("SELECT e.*, e.plan_id, (SELECT COUNT(*) FROM empresas_cliente WHERE estudio_id=e.id) as total_empresas, DATEDIFF(e.vence_en,NOW()) as dias_rest FROM estudios e ORDER BY e.created_at DESC LIMIT 8");
$ingresos = 0;
foreach(Database::fetchAll("SELECT plan,plan_id,estado FROM estudios") as $r){
  if($r['estado']!=='activo') continue;
  $pid=$r['plan_id']??$r['plan'];
  foreach($planes_lista as $p){
    if($p['id']===$pid||$p['nombre']===(PLAN_NOMBRES[$pid]??'')){$ingresos+=$p['precio'];break;}
  }
}

$modal_resultado=null; $modal_error='';
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='crear_estudio'){
  $nombre=trim($_POST['nombre']??'');$ruc=trim($_POST['ruc']??'');
  $email=strtolower(trim($_POST['email_admin']??''));$plan_id=$_POST['plan_id']??'';
  if(!$nombre||!$ruc||!$email){$modal_error='Completa todos los campos.';}
  elseif(Database::fetch("SELECT id FROM usuarios WHERE email=?",[$email])){$modal_error='Email ya registrado.';}
  else{
    $plan_sel=null;foreach($planes_lista as $p)if($p['id']===$plan_id){$plan_sel=$p;break;}
    $dias=$plan_sel['dias_acceso']??30;
    $vence=date('Y-m-d H:i:s',strtotime("+{$dias} days"));
    $passT=Auth::generarPasswordTemporal();$passH=Auth::hashPassword($passT);
    $id=uuid();$uid=uuid();
    Database::query("INSERT INTO estudios (id,nombre,ruc,email_admin,plan,plan_id,estado,vence_en) VALUES (?,?,?,?,?,?,?,?)",[$id,$nombre,$ruc,$email,'basico',$plan_id,'activo',$vence]);
    Database::query("INSERT INTO usuarios (id,email,password,rol,nombre,primer_login,estudio_id) VALUES (?,?,?,?,?,?,?)",[$uid,$email,$passH,'contador',$nombre,1,$id]);
    $modal_resultado=['email'=>$email,'pass'=>$passT,'nombre'=>$nombre,'plan'=>$plan_sel['nombre']??''];
    $estudios_raw=Database::fetchAll("SELECT e.*,(SELECT COUNT(*) FROM empresas_cliente WHERE estudio_id=e.id) as total_empresas,DATEDIFF(e.vence_en,NOW()) as dias_rest FROM estudios e ORDER BY e.created_at DESC LIMIT 8");
  }
}

function planNombre($e,$planes){
  $pid=$e['plan_id']??$e['plan']??'';
  foreach($planes as $p)if($p['id']===$pid||$p['nombre']===(PLAN_NOMBRES[$pid]??''))return $p['nombre'];
  return PLAN_NOMBRES[$pid]??$pid;
}
function planPrecio($e,$planes){
  $pid=$e['plan_id']??$e['plan']??'';
  foreach($planes as $p)if($p['id']===$pid||$p['nombre']===(PLAN_NOMBRES[$pid]??''))return $p['precio'];
  return PLAN_PRECIOS[$pid]??0;
}

$nav_active='dashboard';$user_rol='superadmin';$user_nombre='Administrador';$user_plan='';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — ContaDocs</title>
<link rel="stylesheet" href="/assets/css/app.css?v=2">
<link rel="icon" type="image/png" href="/assets/img/logo_icono.svg">
<style>
.kpi-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px}
.qa-card{background:#fff;border:1.5px solid var(--g200);border-radius:14px;padding:18px;display:flex;align-items:center;gap:14px;cursor:pointer;transition:all .18s;text-decoration:none}
.qa-card:hover{border-color:var(--azul);transform:translateY(-2px);box-shadow:0 8px 24px rgba(79,110,247,.1)}
.qa-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.qa-icon svg{width:22px;height:22px}
.qa-title{font-size:14px;font-weight:700;color:var(--g900)}
.qa-sub{font-size:12px;color:var(--g400);margin-top:2px}
.content-grid{display:grid;grid-template-columns:1fr 320px;gap:20px}
.warn-bar{display:flex;align-items:center;gap:10px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#92400e;font-weight:500}
.warn-bar svg{width:18px;height:18px;color:#d97706;flex-shrink:0}
@media(max-width:900px){.kpi-strip{grid-template-columns:1fr 1fr}.content-grid{grid-template-columns:1fr}.quick-actions{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__.'/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">

    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-left">
        <h1>Dashboard</h1>
        <p>Bienvenido · <?= fechaEs(date('Y-m-d')) ?></p>
      </div>
      <div class="topbar-right">
        <?php if($por_vencer>0): ?>
        <span class="badge badge-amber badge-none" style="font-size:12px;padding:6px 12px">⚠ <?= $por_vencer ?> vencen esta semana</span>
        <?php endif; ?>
        <button class="btn btn-primary btn-sm" onclick="abrirModal('modalCrear')">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Nuevo estudio
        </button>
      </div>
    </div>

    <div class="app-content">

      <?php if($por_vencer>0): ?>
      <div class="warn-bar">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= $por_vencer ?> estudio(s) vencen en los próximos 7 días. <a href="/admin/estudios.php" style="color:#d97706;font-weight:700;margin-left:4px">Revisar →</a>
      </div>
      <?php endif; ?>

      <!-- KPIs -->
      <div class="kpi-strip">
        <div class="metric-card blue">
          <div class="metric-top">
            <div><div class="metric-label">Estudios activos</div><div class="metric-value"><?= $stats['activos']??0 ?></div></div>
            <div class="metric-icon blue"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg></div>
          </div>
          <div class="metric-sub"><?= $stats['total']??0 ?> total registrados</div>
        </div>
        <div class="metric-card green">
          <div class="metric-top">
            <div><div class="metric-label">Ingreso mensual</div><div class="metric-value" style="font-size:22px">S/ <?= number_format($ingresos,0) ?></div></div>
            <div class="metric-icon green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          </div>
          <div class="metric-sub up">S/ <?= number_format($ingresos*12,0) ?>/año proyectado</div>
        </div>
        <div class="metric-card purple">
          <div class="metric-top">
            <div><div class="metric-label">Empresas cliente</div><div class="metric-value"><?= $empresas_total ?></div></div>
            <div class="metric-icon purple"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
          </div>
          <div class="metric-sub"><?= $docs_total ?> docs · <?= $descargas_total ?> descargas</div>
        </div>
        <div class="metric-card <?= ($stats['vencidos']??0)>0?'red':'dark' ?>">
          <div class="metric-top">
            <div><div class="metric-label">Vencidos</div><div class="metric-value" style="color:<?= ($stats['vencidos']??0)>0?'var(--rojo)':'' ?>"><?= $stats['vencidos']??0 ?></div></div>
            <div class="metric-icon <?= ($stats['vencidos']??0)>0?'red':'dark' ?>"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          </div>
          <div class="metric-sub <?= ($stats['vencidos']??0)>0?'down':'' ?>"><?= ($stats['vencidos']??0)>0?'Requieren cobro':'Todo al día ✓' ?></div>
        </div>
      </div>

      <!-- Accesos rápidos -->
      <div class="quick-actions">
        <a href="/admin/estudios.php" class="qa-card">
          <div class="qa-icon" style="background:var(--azul-l)"><svg fill="none" viewBox="0 0 24 24" stroke="#4f6ef7" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg></div>
          <div><div class="qa-title">Gestionar estudios</div><div class="qa-sub">Crear, editar y controlar accesos</div></div>
        </a>
        <a href="/admin/planes.php" class="qa-card">
          <div class="qa-icon" style="background:var(--purple-l)"><svg fill="none" viewBox="0 0 24 24" stroke="#7c3aed" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          <div><div class="qa-title">Gestionar planes</div><div class="qa-sub">Precios, límites y días de acceso</div></div>
        </a>
        <a href="/admin/pagos.php" class="qa-card">
          <div class="qa-icon" style="background:var(--verde-l)"><svg fill="none" viewBox="0 0 24 24" stroke="#0ea472" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg></div>
          <div><div class="qa-title">Control de pagos</div><div class="qa-sub">Ingresos y vencimientos</div></div>
        </a>
        <div class="qa-card" onclick="abrirModal('modalCrear')" style="cursor:pointer">
          <div class="qa-icon" style="background:#fef3c7"><svg fill="none" viewBox="0 0 24 24" stroke="#d97706" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg></div>
          <div><div class="qa-title">Nuevo estudio</div><div class="qa-sub">Activar nuevo contador</div></div>
        </div>
      </div>

      <!-- Tabla + lateral -->
      <div class="content-grid">
        <div class="card">
          <div class="card-header">
            <div><div class="card-title">Estudios recientes</div><div class="card-sub">Últimos 8 registrados</div></div>
            <a href="/admin/estudios.php" class="btn btn-secondary btn-sm">Ver todos →</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Estudio</th><th>Plan</th><th>Empresas</th><th>Vence</th><th>Estado</th></tr></thead>
              <tbody>
                <?php foreach($estudios_raw as $e):
                  $sc=['activo'=>'badge-green','vencido'=>'badge-red','suspendido'=>'badge-amber'];
                  $dr=(int)($e['dias_rest']??0);
                ?>
                <tr>
                  <td><div class="fw"><?= htmlspecialchars($e['nombre'],ENT_QUOTES,'UTF-8') ?></div><div style="font-size:11px;color:var(--g400)"><?= htmlspecialchars($e['email_admin'],ENT_QUOTES,'UTF-8') ?></div></td>
                  <td><span class="badge badge-blue badge-none"><?= planNombre($e,$planes_lista) ?></span></td>
                  <td class="fw"><?= $e['total_empresas'] ?></td>
                  <td style="font-size:12px;color:<?= $dr<0?'var(--rojo)':($dr<=5?'var(--amber)':'var(--g500)') ?>">
                    <?= $e['vence_en']?fechaEs($e['vence_en']):'—' ?>
                    <?php if($dr<=5&&$dr>=0&&$e['vence_en']): ?><br><small>⚠ <?= $dr ?> días</small><?php endif; ?>
                  </td>
                  <td><span class="badge <?= $sc[$e['estado']]??'badge-gray' ?>"><?= $e['estado'] ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Panel lateral -->
        <div style="display:flex;flex-direction:column;gap:16px">
          <div class="card card-body">
            <div class="card-title" style="margin-bottom:14px">Por plan</div>
            <?php
            $por_plan=[];
            foreach($planes_lista as $pl){
              $cnt=Database::fetch("SELECT COUNT(*) as n FROM estudios WHERE (plan_id=? OR plan=?) AND estado='activo'",[$pl['id'],$pl['nombre']??'x'])['n']??0;
              $por_plan[]=array_merge($pl,['cnt'=>$cnt]);
            }
            $max_cnt=max(array_column($por_plan,'cnt')?:[1]);
            foreach($por_plan as $pl):
            ?>
            <div style="margin-bottom:14px">
              <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px">
                <span style="font-weight:600;color:var(--g700)"><?= htmlspecialchars($pl['nombre'],ENT_QUOTES,'UTF-8') ?></span>
                <span style="font-weight:700;color:var(--g900)"><?= $pl['cnt'] ?> estudios</span>
              </div>
              <div class="progress-track">
                <div class="progress-fill" style="width:<?= $max_cnt>0?round(($pl['cnt']/$max_cnt)*100):0 ?>%"></div>
              </div>
              <div style="font-size:11px;color:var(--g400);margin-top:3px">S/ <?= number_format($pl['precio']*$pl['cnt'],2) ?>/mes</div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="card card-body" style="background:linear-gradient(135deg,var(--dark),var(--dark2));border-color:var(--dark3)">
            <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:6px">💰 Ingreso anual proyectado</div>
            <div style="font-size:28px;font-weight:900;background:linear-gradient(135deg,#60a5fa,#34d399);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;letter-spacing:-1px">S/ <?= number_format($ingresos*12,0) ?></div>
            <div style="font-size:12px;color:rgba(255,255,255,.4);margin-top:4px">Basado en suscripciones activas</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal crear estudio -->
<div class="modal-overlay <?= $modal_resultado?'open':'' ?>" id="modalCrear" onclick="if(event.target===this)cerrarModal('modalCrear')">
  <div class="modal">
    <?php if($modal_resultado): ?>
      <div style="text-align:center;margin-bottom:16px"><div style="width:56px;height:56px;background:linear-gradient(135deg,#dcfce7,#bbf7d0);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:28px">✓</div></div>
      <div class="modal-title">Estudio creado</div>
      <div class="modal-sub">Envía estas credenciales al contador por WhatsApp:</div>
      <div style="background:var(--dark);border-radius:12px;padding:18px;font-family:monospace;font-size:13px;line-height:2.4;margin-bottom:16px">
        <div style="color:rgba(255,255,255,.5)">Email: <span style="color:#60a5fa"><?= htmlspecialchars($modal_resultado['email'],ENT_QUOTES,'UTF-8') ?></span></div>
        <div style="color:rgba(255,255,255,.5)">Clave: <span style="color:#34d399;font-size:16px;font-weight:700"><?= htmlspecialchars($modal_resultado['pass'],ENT_QUOTES,'UTF-8') ?></span></div>
        <div style="color:rgba(255,255,255,.5)">Web: <span style="color:#a78bfa"><?= APP_URL ?>/login.php</span></div>
        <div style="color:rgba(255,255,255,.5)">Plan: <span style="color:#fbbf24"><?= htmlspecialchars($modal_resultado['plan'],ENT_QUOTES,'UTF-8') ?></span></div>
      </div>
      <button class="btn btn-primary w-full" onclick="cerrarModal('modalCrear');location.href='/admin/estudios.php'">Listo, ver estudios</button>
    <?php else: ?>
      <div class="modal-title">Nuevo estudio contable</div>
      <div class="modal-sub">Se crea el acceso automáticamente para el contador.</div>
      <?php if($modal_error): ?><div class="alert alert-error"><?= htmlspecialchars($modal_error,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="action" value="crear_estudio">
        <div class="form-group"><label class="form-label">Nombre del estudio *</label><input type="text" name="nombre" class="form-input" required placeholder="Estudio Rodríguez y Asoc."></div>
        <div class="form-group"><label class="form-label">RUC *</label><input type="text" name="ruc" class="form-input" required maxlength="11" placeholder="20512345678"></div>
        <div class="form-group"><label class="form-label">Email del contador *</label><input type="email" name="email_admin" class="form-input" required placeholder="contador@email.com"></div>
        <div class="form-group"><label class="form-label">Plan *</label>
          <select name="plan_id" class="form-select" required>
            <option value="">— Selecciona un plan —</option>
            <?php foreach($planes_lista as $p): ?>
            <option value="<?= htmlspecialchars($p['id'],ENT_QUOTES,'UTF-8') ?>"><?= htmlspecialchars($p['nombre'],ENT_QUOTES,'UTF-8') ?> — S/ <?= number_format($p['precio'],2) ?>/mes</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCrear')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear y activar</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
function abrirModal(id){document.getElementById(id).classList.add('open')}
function cerrarModal(id){document.getElementById(id).classList.remove('open')}
</script>
</body>
</html>
