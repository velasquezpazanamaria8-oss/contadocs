<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');
$user    = Auth::usuario();
$estudio = Database::fetch("SELECT * FROM estudios WHERE id=?", [$user['estudio_id']]);
$plan_id = $estudio['plan_id'] ?? $estudio['plan'] ?? 'basico';
$limite  = getLimite($plan_id);
$e = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$mensaje = ''; $error = ''; $modal_resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear') {
        $usados = Database::fetch("SELECT COUNT(*) as n FROM empresas_cliente WHERE estudio_id=?", [$user['estudio_id']])['n'];
        if ($usados >= $limite) { $error = "Límite de $limite empresas alcanzado. Actualiza tu plan."; }
        else {
            $razon = trim($_POST['razon_social'] ?? '');
            $ruc   = trim($_POST['ruc'] ?? '');
            $email = strtolower(trim($_POST['email_acceso'] ?? ''));
            if (!$razon || !$ruc || !$email) { $error = 'Completa todos los campos.'; }
            elseif (Database::fetch("SELECT id FROM usuarios WHERE email=?", [$email]) || Database::fetch("SELECT id FROM empresas_cliente WHERE ruc=? and estudio_id=?", [$ruc,$user['estudio_id']])) { $error = 'Email o Ruc ya registrado.'; }
          
            else {
                $pass = Auth::generarPasswordTemporal();
                $hash = Auth::hashPassword($pass);
                $eid  = uuid(); $uid = uuid();
                Database::query("INSERT INTO empresas_cliente (id,estudio_id,razon_social,ruc,email_acceso,activo) VALUES (?,?,?,?,?,1)",
                    [$eid, $user['estudio_id'], $razon, $ruc, $email]);
                Database::query("INSERT INTO usuarios (id,email,password,rol,nombre,primer_login,estudio_id,empresa_id) VALUES (?,?,?,?,?,1,?,?)",
                    [$uid, $email, $hash, 'cliente', $razon, $user['estudio_id'], $eid]);
                $modal_resultado = ['email' => $email, 'pass' => $pass, 'nombre' => $razon];
            }
        }
    }

    if ($action === 'editar') {
        $eid   = $_POST['empresa_id'] ?? '';
        $razon = trim($_POST['razon_social'] ?? '');
        $ruc   = trim($_POST['ruc'] ?? '');
        $email = strtolower(trim($_POST['email_acceso'] ?? ''));
        $activo = (int)($_POST['activo'] ?? 1);
        if (!$razon || !$ruc || !$email) { $error = 'Completa todos los campos.'; }
        else {
            Database::query("UPDATE empresas_cliente SET razon_social=?,ruc=?,email_acceso=?,activo=? WHERE id=? AND estudio_id=?",
                [$razon,$ruc,$email,$activo,$eid,$user['estudio_id']]);
            Database::query("UPDATE usuarios SET email=?,nombre=?,activo=? WHERE empresa_id=?", [$email,$razon,$activo,$eid]);
            $mensaje = 'Empresa actualizada.';
        }
    }

    if ($action === 'cambiar_password') {
        $eid  = $_POST['empresa_id'] ?? '';
        $pass = $_POST['pass_nueva'] ?? '';
        if (strlen($pass) < 6) { $error = 'Mínimo 6 caracteres.'; }
        else {
            Database::query("UPDATE usuarios SET password=?,primer_login=0 WHERE empresa_id=?", [Auth::hashPassword($pass), $eid]);
            $mensaje = 'Contraseña cambiada. Envíasela a tu cliente.';
        }
    }

    if ($action === 'eliminar') {
        $eid = $_POST['empresa_id'] ?? '';
        $docs = Database::fetchAll("SELECT id FROM documentos WHERE empresa_id=?", [$eid]);
        foreach ($docs as $doc) Database::query("DELETE FROM descargas_log WHERE documento_id=?", [$doc['id']]);
        Database::query("DELETE FROM documentos WHERE empresa_id=?", [$eid]);
        Database::query("DELETE FROM descargas_log WHERE empresa_id=?", [$eid]);
        Database::query("DELETE FROM usuarios WHERE empresa_id=?", [$eid]);
        Database::query("DELETE FROM empresas_cliente WHERE id=? AND estudio_id=?", [$eid, $user['estudio_id']]);
        $mensaje = 'Empresa eliminada.';
    }
}

$buscar = trim($_GET['q'] ?? '');
$where  = "ec.estudio_id=?"; $params = [$user['estudio_id']];
if ($buscar) { $where .= " AND (ec.razon_social LIKE ? OR ec.ruc LIKE ? OR ec.email_acceso LIKE ?)"; $params = array_merge($params, ["%$buscar%","%$buscar%","%$buscar%"]); }

$empresas = Database::fetchAll(
    "SELECT ec.*, (SELECT COUNT(*) FROM documentos WHERE empresa_id=ec.id) as total_docs,
            (SELECT COUNT(*) FROM descargas_log WHERE empresa_id=ec.id) as total_desc,
            (SELECT MAX(descargado_at) FROM descargas_log WHERE empresa_id=ec.id) as ultima_desc
     FROM empresas_cliente ec WHERE $where ORDER BY ec.razon_social ASC", $params);
$usados = Database::fetch("SELECT COUNT(*) as n FROM empresas_cliente WHERE estudio_id=?", [$user['estudio_id']])['n'];

$nav_active='clientes'; $user_rol='contador';
$user_nombre=$estudio['nombre']??''; $user_plan=$plan_id;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mis clientes — ContaDocs</title>
<link rel="stylesheet" href="/assets/css/app.css?v=2">
<link rel="icon" type="image/png" href="/assets/img/logo_icono.svg">
<style>
.empresa-card{background:#fff;border:1.5px solid var(--g200);border-radius:16px;padding:20px;transition:all .2s;margin-bottom:12px}
.empresa-card:hover{border-color:var(--azul);box-shadow:0 8px 24px rgba(79,110,247,.1);transform:translateY(-1px)}
.empresa-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}
.empresa-av{width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--azul-l),var(--verde-l));border:1.5px solid var(--g200);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;color:var(--azul);flex-shrink:0}
.empresa-stats{display:flex;gap:16px;flex-wrap:wrap}
.empresa-stat{text-align:center}
.empresa-stat-val{font-size:18px;font-weight:800;color:var(--g900);line-height:1}
.empresa-stat-lbl{font-size:10px;color:var(--g400);margin-top:2px}
.empresa-actions{display:flex;gap:6px;flex-wrap:wrap}
.header-grid{display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start}
.filters-bar{display:flex;gap:10px;margin-bottom:20px;align-items:center;flex-wrap:wrap}
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;z-index:10000;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal{background:#fff;border-radius:20px;box-shadow:0 24px 64px rgba(0,0,0,.25);width:100%;max-width:480px;padding:28px;max-height:90vh;overflow-y:auto;transform:scale(.96) translateY(10px);transition:transform .2s}
.modal-overlay.open .modal{transform:scale(1) translateY(0)}
@media(max-width:768px){.header-grid{grid-template-columns:1fr}.empresa-stats{gap:12px}}
</style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__.'/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Mis clientes</h1>
        <p><?= $e($estudio['nombre']??'') ?> · <?= $usados ?>/<?= $limite>=999999?'∞':$limite ?> empresas</p>
      </div>
      <div class="topbar-right">
        <?php if($usados<$limite): ?>
        <button class="btn btn-primary btn-sm" onclick="abrirModal('modalCrear')">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Agregar empresa
        </button>
        <?php else: ?>
        <span class="badge badge-amber badge-none" style="font-size:12px;padding:7px 14px">Límite alcanzado</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="app-content">
      <?php if($mensaje): ?><div class="alert alert-success"><?= $e($mensaje) ?></div><?php endif; ?>
      <?php if($error&&!$modal_resultado): ?><div class="alert alert-error"><?= $e($error) ?></div><?php endif; ?>

      <!-- Métricas -->
      <div class="metrics-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
        <div class="metric-card blue">
          <div class="metric-top">
            <div><div class="metric-label">Empresas</div><div class="metric-value"><?= $usados ?></div></div>
            <div class="metric-icon blue"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
          </div>
          <div class="progress-track" style="margin-top:8px"><div class="progress-fill" style="width:<?= $limite>=999999?15:min(100,round($usados/$limite*100)) ?>%"></div></div>
          <div class="metric-sub" style="margin-top:5px"><?= $limite>=999999?'Ilimitado':($limite-$usados).' cupos libres' ?></div>
        </div>
        <div class="metric-card green">
          <div class="metric-top">
            <div><div class="metric-label">Total documentos</div><div class="metric-value"><?= array_sum(array_column($empresas,'total_docs')) ?></div></div>
            <div class="metric-icon green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
          </div>
        </div>
        <div class="metric-card purple">
          <div class="metric-top">
            <div><div class="metric-label">Total descargas</div><div class="metric-value"><?= array_sum(array_column($empresas,'total_desc')) ?></div></div>
            <div class="metric-icon purple"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg></div>
          </div>
        </div>
      </div>

      <!-- Buscador -->
      <form method="GET" class="filters-bar">
        <div style="position:relative;flex:1;min-width:200px">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--g400)"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="text" name="q" value="<?= $e($buscar) ?>" placeholder="Buscar empresa, RUC o email..." class="form-input" style="padding-left:38px">
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
        <?php if($buscar): ?><a href="/contador/clientes.php" class="btn btn-ghost btn-sm">✕ Limpiar</a><?php endif; ?>
        <span class="text-muted" style="margin-left:auto"><?= count($empresas) ?> resultados</span>
      </form>

      <!-- Cards de empresas -->
      <?php if(empty($empresas)): ?>
      <div class="empty-state">
        <div class="empty-icon">🏢</div>
        <div class="empty-title"><?= $buscar?'Sin resultados':'Sin empresas aún' ?></div>
        <div class="empty-sub"><?= $buscar?'Prueba con otro nombre o RUC':'Agrega tu primera empresa cliente para empezar a subir documentos.' ?></div>
        <?php if(!$buscar&&$usados<$limite): ?><button class="btn btn-primary" style="margin-top:16px" onclick="abrirModal('modalCrear')">+ Agregar empresa</button><?php endif; ?>
      </div>
      <?php else: ?>
      <?php foreach($empresas as $emp): ?>
      <div class="empresa-card">
        <div class="empresa-header">
          <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:0">
            <div class="empresa-av"><?= strtoupper(substr($emp['razon_social'],0,1)) ?></div>
            <div style="min-width:0">
              <div style="font-size:15px;font-weight:700;color:var(--g900);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= $e($emp['razon_social']) ?></div>
              <div style="font-size:12px;color:var(--g400);margin-top:2px">RUC <?= $e($emp['ruc']) ?> · <?= $e($emp['email_acceso']) ?></div>
            </div>
          </div>
          <span class="badge <?= $emp['activo']?'badge-green':'badge-red' ?>"><?= $emp['activo']?'Activo':'Inactivo' ?></span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div class="empresa-stats">
            <div class="empresa-stat">
              <div class="empresa-stat-val" style="color:var(--azul)"><?= $emp['total_docs'] ?></div>
              <div class="empresa-stat-lbl">Documentos</div>
            </div>
            <div style="width:1px;background:var(--g200)"></div>
            <div class="empresa-stat">
              <div class="empresa-stat-val" style="color:var(--purple)"><?= $emp['total_desc'] ?></div>
              <div class="empresa-stat-lbl">Descargas</div>
            </div>
            <?php if($emp['ultima_desc']): ?>
            <div style="width:1px;background:var(--g200)"></div>
            <div class="empresa-stat">
              <div style="font-size:12px;font-weight:600;color:var(--g500)"><?= tiempoRelativo($emp['ultima_desc']) ?></div>
              <div class="empresa-stat-lbl">Última descarga</div>
            </div>
            <?php endif; ?>
          </div>
          <div class="empresa-actions">
            <a href="/contador/subir.php?empresa_id=<?= $e($emp['id']) ?>" class="btn btn-success btn-sm">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
              Subir doc
            </a>
            <a href="/contador/documentos.php?empresa_id=<?= $e($emp['id']) ?>" class="btn btn-secondary btn-sm">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              Ver docs
            </a>
            <button class="btn btn-secondary btn-sm" onclick='editarEmpresa(<?= json_encode($emp) ?>)'>✏️</button>
            <button class="btn btn-ghost btn-sm" style="color:var(--rojo)" onclick='eliminarEmpresa("<?= $e($emp['id']) ?>","<?= $e($emp['razon_social']) ?>")'>🗑️</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- MODAL CREAR -->
<div class="modal-overlay <?= $modal_resultado?'open':'' ?>" id="modalCrear" onclick="if(event.target===this)cerrarModal('modalCrear')">
  <div class="modal">
    <?php if($modal_resultado): ?>
      <div style="text-align:center;margin-bottom:16px"><div style="width:56px;height:56px;background:#dcfce7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:26px">✓</div></div>
      <div class="modal-title">Empresa creada</div>
      <div class="modal-sub">Comparte estas credenciales con tu cliente:</div>
      <div style="background:var(--dark);border-radius:12px;padding:18px;font-family:monospace;font-size:13px;line-height:2.4;margin-bottom:16px">
        <div style="color:rgba(255,255,255,.5)">Email: <span style="color:#60a5fa"><?= $e($modal_resultado['email']) ?></span></div>
        <div style="color:rgba(255,255,255,.5)">Clave: <span style="color:#34d399;font-size:16px;font-weight:700"><?= $e($modal_resultado['pass']) ?></span></div>
        <div style="color:rgba(255,255,255,.5)">Web: <span style="color:#a78bfa"><?= APP_URL ?>/login.php</span></div>
      </div>
      <a href="https://wa.me/?text=Hola+<?= urlencode($modal_resultado['nombre']) ?>%2C+tus+datos+de+acceso+ContaDocs:%0AEmail:+<?= urlencode($modal_resultado['email']) ?>%0AClave:+<?= urlencode($modal_resultado['pass']) ?>%0AWeb:+<?= urlencode(APP_URL.'/login.php') ?>" target="_blank" class="btn btn-success w-full" style="margin-bottom:10px">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.553 4.112 1.524 5.84L0 24l6.335-1.524A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.374l-.36-.214-3.733.898.914-3.643-.234-.374A9.817 9.817 0 012.182 12C2.182 6.578 6.578 2.182 12 2.182S21.818 6.578 21.818 12 17.422 21.818 12 21.818z"/></svg>
        Enviar por WhatsApp
      </a>
      <button class="btn btn-secondary w-full" onclick="cerrarModal('modalCrear');location.href='/contador/clientes.php'">Listo</button>
    <?php else: ?>
      <div class="modal-title">Agregar empresa cliente</div>
      <div class="modal-sub">Se crea un acceso automático para este cliente.</div>
      <?php if($error): ?><div class="alert alert-error"><?= $e($error) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="action" value="crear">
        <div class="form-group"><label class="form-label">Razón social *</label><input type="text" name="razon_social" class="form-input" required placeholder="Inversiones Quispe SAC"></div>
        <div class="form-group"><label class="form-label">RUC *</label><input type="text" name="ruc" class="form-input" required maxlength="11" placeholder="20501234567"></div>
        <div class="form-group"><label class="form-label">Email del cliente *</label><input type="email" name="email_acceso" class="form-input" required placeholder="gerencia@empresa.com"></div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCrear')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear acceso</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<!-- MODAL EDITAR -->
<div class="modal-overlay" id="modalEditar" onclick="if(event.target===this)cerrarModal('modalEditar')">
  <div class="modal">
    <div class="tabs">
      <button class="tab-btn active" onclick="showTab('tDatos')">📋 Datos</button>
      <button class="tab-btn" onclick="showTab('tPass')">🔑 Contraseña</button>
    </div>
    <div id="tDatos" class="tab-content active">
      <div class="modal-title" style="margin-bottom:4px">Editar empresa</div>
      <div class="modal-sub" id="editRazonSub" style="margin-bottom:16px"></div>
      <form method="POST">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="empresa_id" id="editEmpId">
        <div class="form-group"><label class="form-label">Razón social *</label><input type="text" name="razon_social" id="editRazon" class="form-input" required></div>
        <div class="form-group"><label class="form-label">RUC *</label><input type="text" name="ruc" id="editRuc" class="form-input" required maxlength="11"></div>
        <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email_acceso" id="editEmail" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Estado</label>
          <select name="activo" id="editActivo" class="form-select">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditar')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
    <div id="tPass" class="tab-content">
      <div class="modal-title" style="margin-bottom:4px">Cambiar contraseña</div>
      <div class="modal-sub" style="margin-bottom:16px">El cliente usará esta clave para ingresar.</div>
      <form method="POST">
        <input type="hidden" name="action" value="cambiar_password">
        <input type="hidden" name="empresa_id" id="editEmpIdPass">
        <div class="form-group"><label class="form-label">Nueva contraseña *</label><input type="text" name="pass_nueva" class="form-input" required minlength="6" placeholder="Mínimo 6 caracteres"></div>
        <div class="alert alert-warning"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Envía la nueva clave al cliente por WhatsApp.</div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditar')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Cambiar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL ELIMINAR -->
<div class="modal-overlay" id="modalEliminar" onclick="if(event.target===this)cerrarModal('modalEliminar')">
  <div class="modal" style="max-width:420px">
    <div style="text-align:center;margin-bottom:16px"><div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:26px">⚠️</div></div>
    <div class="modal-title" style="text-align:center">¿Eliminar empresa?</div>
    <div class="modal-sub" style="text-align:center" id="eliminarNombre"></div>
    <div class="alert alert-error"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Se eliminarán todos sus documentos y descargas. Irreversible.</div>
    <form method="POST">
      <input type="hidden" name="action" value="eliminar">
      <input type="hidden" name="empresa_id" id="eliminarEmpId">
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEliminar')">Cancelar</button>
        <button type="submit" class="btn btn-danger">Sí, eliminar</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModal(id){document.getElementById(id).classList.add('open')}
function cerrarModal(id){document.getElementById(id).classList.remove('open')}
function showTab(id){
  document.querySelectorAll('#modalEditar .tab-content').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('#modalEditar .tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  event.target.classList.add('active');
}
function editarEmpresa(e){
  document.getElementById('editEmpId').value=e.id;
  document.getElementById('editRazonSub').textContent=e.razon_social;
  document.getElementById('editRazon').value=e.razon_social;
  document.getElementById('editRuc').value=e.ruc;
  document.getElementById('editEmail').value=e.email_acceso;
  document.getElementById('editActivo').value=e.activo;
  document.getElementById('editEmpIdPass').value=e.id;
  document.querySelectorAll('#modalEditar .tab-content').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('#modalEditar .tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tDatos').classList.add('active');
  document.querySelectorAll('#modalEditar .tab-btn')[0].classList.add('active');
  abrirModal('modalEditar');
}
function eliminarEmpresa(id,nombre){
  document.getElementById('eliminarEmpId').value=id;
  document.getElementById('eliminarNombre').textContent=nombre;
  abrirModal('modalEliminar');
}
</script>
</body>
</html>
