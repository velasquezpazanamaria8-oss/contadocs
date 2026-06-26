<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('superadmin');

$mensaje = '';
$error   = '';
$modal_resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREAR
    if ($action === 'crear') {
        $nombre  = trim($_POST['nombre'] ?? '');
        $ruc     = trim($_POST['ruc'] ?? '');
        $email   = strtolower(trim($_POST['email_admin'] ?? ''));
        $plan    = $_POST['plan'] ?? 'basico';
        $dias    = (int)($_POST['dias'] ?? 30);

        if (!$nombre || !$ruc || !$email) {
            $error = 'Completa todos los campos obligatorios.';
        } else {
            $existe = Database::fetch("SELECT id FROM usuarios WHERE email = ?", [$email]);
            if ($existe) {
                $error = 'Ya existe un usuario con ese correo.';
            } else {
                $passTemp = Auth::generarPasswordTemporal();
                $passHash = Auth::hashPassword($passTemp);
                $vence    = date('Y-m-d H:i:s', strtotime("+{$dias} days"));
                $id = uuid(); $uid = uuid();
                Database::query("INSERT INTO estudios (id,nombre,ruc,email_admin,plan,estado,vence_en) VALUES (?,?,?,?,?,?,?)",
                    [$id,$nombre,$ruc,$email,$plan,'activo',$vence]);
                Database::query("INSERT INTO usuarios (id,email,password,rol,nombre,primer_login,estudio_id) VALUES (?,?,?,?,?,?,?)",
                    [$uid,$email,$passHash,'contador',$nombre,1,$id]);
                $modal_resultado = ['email'=>$email,'pass'=>$passTemp,'nombre'=>$nombre,'plan'=>$plan];
            }
        }
    }

    // EDITAR
    if ($action === 'editar') {
        $eid    = $_POST['estudio_id'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        $ruc    = trim($_POST['ruc'] ?? '');
        $email  = strtolower(trim($_POST['email_admin'] ?? ''));
        $plan   = $_POST['plan'] ?? 'basico';
        $estado = $_POST['estado'] ?? 'activo';
        $dias   = (int)($_POST['dias'] ?? 30);
        $vence  = date('Y-m-d H:i:s', strtotime("+{$dias} days"));

        if (!$nombre || !$ruc || !$email) {
            $error = 'Completa todos los campos.';
        } else {
            Database::query("UPDATE estudios SET nombre=?,ruc=?,email_admin=?,plan=?,estado=?,vence_en=? WHERE id=?",
                [$nombre,$ruc,$email,$plan,$estado,$vence,$eid]);
            // Actualizar email del usuario contador
            Database::query("UPDATE usuarios SET email=?,nombre=? WHERE estudio_id=? AND rol='contador'",
                [$email,$nombre,$eid]);
            $mensaje = 'Estudio actualizado correctamente.';
        }
    }

    // CAMBIAR CONTRASEÑA
    if ($action === 'cambiar_password') {
        $eid      = $_POST['estudio_id'] ?? '';
        $pass_nueva = $_POST['pass_nueva'] ?? '';
        if (strlen($pass_nueva) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            $hash = Auth::hashPassword($pass_nueva);
            Database::query("UPDATE usuarios SET password=?, primer_login=0 WHERE estudio_id=? AND rol='contador'", [$hash,$eid]);
            $mensaje = 'Contraseña cambiada correctamente.';
        }
    }

    // ELIMINAR
    if ($action === 'eliminar') {
        $eid = $_POST['estudio_id'] ?? '';
        // Eliminar en cascada manual
        $empresas = Database::fetchAll("SELECT id FROM empresas_cliente WHERE estudio_id=?", [$eid]);
        foreach ($empresas as $emp) {
            $docs = Database::fetchAll("SELECT id FROM documentos WHERE empresa_id=?", [$emp['id']]);
            foreach ($docs as $doc) {
                Database::query("DELETE FROM descargas_log WHERE documento_id=?", [$doc['id']]);
            }
            Database::query("DELETE FROM documentos WHERE empresa_id=?", [$emp['id']]);
            Database::query("DELETE FROM usuarios WHERE empresa_id=?", [$emp['id']]);
        }
        Database::query("DELETE FROM empresas_cliente WHERE estudio_id=?", [$eid]);
        Database::query("DELETE FROM categorias WHERE estudio_id=?", [$eid]);
        Database::query("DELETE FROM usuarios WHERE estudio_id=?", [$eid]);
        Database::query("DELETE FROM estudios WHERE id=?", [$eid]);
        $mensaje = 'Estudio eliminado correctamente.';
    }
}

// Filtros
$buscar = trim($_GET['q'] ?? '');
$f_estado = $_GET['estado'] ?? '';
$f_plan   = $_GET['plan'] ?? '';
$where = "1=1"; $params = [];
if ($buscar)   { $where .= " AND (e.nombre LIKE ? OR e.ruc LIKE ? OR e.email_admin LIKE ?)"; $params = array_merge($params, ["%$buscar%","%$buscar%","%$buscar%"]); }
if ($f_estado) { $where .= " AND e.estado=?"; $params[] = $f_estado; }
if ($f_plan)   { $where .= " AND e.plan=?";   $params[] = $f_plan; }

$estudios = Database::fetchAll(
    "SELECT e.*, (SELECT COUNT(*) FROM empresas_cliente WHERE estudio_id=e.id) as total_empresas,
            (SELECT email FROM usuarios WHERE estudio_id=e.id AND rol='contador' LIMIT 1) as email_contador
     FROM estudios e WHERE $where ORDER BY e.created_at DESC", $params);

$stats = Database::fetch("SELECT COUNT(*) as total, SUM(estado='activo') as activos, SUM(estado='vencido') as vencidos FROM estudios");
$ingresos = 0;
foreach (Database::fetchAll("SELECT plan FROM estudios WHERE estado='activo'") as $r) $ingresos += PLAN_PRECIOS[$r['plan']] ?? 0;

$nav_active = 'estudios'; $user_rol = 'superadmin'; $user_nombre = 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Estudios — ContaDocs Admin</title>
<link rel="stylesheet" href="/assets/css/app.css">
<style>
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:50;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal{background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:500px;padding:24px;max-height:90vh;overflow-y:auto;transform:translateY(8px);transition:transform .2s}
.modal-overlay.open .modal{transform:translateY(0)}
.tab-btn{padding:8px 16px;border:none;background:transparent;font-size:13px;color:var(--gris-500);cursor:pointer;border-bottom:2px solid transparent;font-weight:500}
.tab-btn.active{color:var(--verde);border-color:var(--verde)}
.tab-content{display:none}.tab-content.active{display:block}
</style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div><div class="topbar-title">Estudios contables</div><div class="topbar-sub">CRUD completo — crear, editar, eliminar</div></div>
      <div class="topbar-actions">
        <button class="btn btn-primary btn-sm" onclick="abrirModal('modalCrear')">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Nuevo estudio
        </button>
      </div>
    </div>
    <div class="app-content">

      <?php if ($mensaje): ?><div class="alert alert-success"><?= e($mensaje) ?></div><?php endif; ?>
      <?php if ($error && !$modal_resultado):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <!-- Stats -->
      <div class="metrics-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
        <div class="metric-card">
          <div class="metric-icon" style="background:#eff6ff"><svg fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg></div>
          <div><div class="metric-label">Total</div><div class="metric-value"><?= $stats['total'] ?></div></div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#ecfdf5"><svg fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          <div><div class="metric-label">Activos</div><div class="metric-value"><?= $stats['activos'] ?></div></div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#fef2f2"><svg fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          <div><div class="metric-label">Vencidos</div><div class="metric-value" style="color:<?= $stats['vencidos']>0?'#dc2626':'' ?>"><?= $stats['vencidos'] ?></div></div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#ecfdf5"><svg fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          <div><div class="metric-label">Ingreso/mes</div><div class="metric-value" style="font-size:18px">S/ <?= number_format($ingresos,2) ?></div></div>
        </div>
      </div>

      <!-- Filtros -->
      <form method="GET" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
        <div style="position:relative;flex:1;min-width:200px">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--gris-400)"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="text" name="q" value="<?= e($buscar) ?>" placeholder="Buscar nombre, RUC o email..." class="form-input" style="padding-left:34px">
        </div>
        <select name="estado" class="form-select" style="width:auto" onchange="this.form.submit()">
          <option value="">Todos los estados</option>
          <option value="activo" <?= $f_estado==='activo'?'selected':'' ?>>Activos</option>
          <option value="vencido" <?= $f_estado==='vencido'?'selected':'' ?>>Vencidos</option>
          <option value="suspendido" <?= $f_estado==='suspendido'?'selected':'' ?>>Suspendidos</option>
        </select>
        <select name="plan" class="form-select" style="width:auto" onchange="this.form.submit()">
          <option value="">Todos los planes</option>
          <option value="basico" <?= $f_plan==='basico'?'selected':'' ?>>Básico</option>
          <option value="profesional" <?= $f_plan==='profesional'?'selected':'' ?>>Profesional</option>
          <option value="ilimitado" <?= $f_plan==='ilimitado'?'selected':'' ?>>Ilimitado</option>
        </select>
        <?php if ($buscar||$f_estado||$f_plan): ?><a href="/admin/estudios.php" class="btn btn-secondary btn-sm">Limpiar</a><?php endif; ?>
        <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
        <span class="text-muted" style="font-size:12px;margin-left:auto"><?= count($estudios) ?> resultados</span>
      </form>

      <!-- Tabla -->
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Estudio</th><th>RUC</th><th>Plan</th><th>Empresas</th><th>Vence</th><th>Estado</th><th style="min-width:220px">Acciones</th></tr>
            </thead>
            <tbody>
              <?php if (empty($estudios)): ?>
              <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gris-400)">No hay estudios. Crea el primero.</td></tr>
              <?php else: foreach ($estudios as $e):
                $dias_rest = $e['vence_en'] ? ceil((strtotime($e['vence_en'])-time())/86400) : null;
                $pc = ['basico'=>'badge-gray','profesional'=>'badge-blue','ilimitado'=>'badge-purple'];
                $sc = ['activo'=>'badge-green','vencido'=>'badge-red','suspendido'=>'badge-amber'];
              ?>
              <tr>
                <td>
                  <div style="font-weight:500;color:var(--gris-900)"><?= e($e['nombre']) ?></div>
                  <div style="font-size:11px;color:var(--gris-400)"><?= e($e['email_admin']) ?></div>
                </td>
                <td class="mono"><?= e($e['ruc']) ?></td>
                <td><span class="badge <?= $pc[$e['plan']]??'badge-gray' ?>"><?= PLAN_NOMBRES[$e['plan']]??$e['plan'] ?></span></td>
                <td style="font-weight:500"><?= $e['total_empresas'] ?></td>
                <td style="font-size:12px">
                  <?php if ($e['vence_en']): ?>
                    <span style="color:<?= $dias_rest<0?'#dc2626':($dias_rest<=5?'#d97706':'var(--gris-500)') ?>">
                      <?= fechaEs($e['vence_en']) ?>
                      <?php if ($dias_rest!==null && $dias_rest<=5 && $dias_rest>=0): ?>
                        <br><small style="color:#d97706">⚠ <?= $dias_rest ?> días</small>
                      <?php elseif ($dias_rest<0): ?>
                        <br><small style="color:#dc2626">Venció</small>
                      <?php endif; ?>
                    </span>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td><span class="badge <?= $sc[$e['estado']]??'badge-gray' ?>"><?= e($e['estado']) ?></span></td>
                <td>
                  <div style="display:flex;gap:4px;flex-wrap:wrap">
                    <button class="btn btn-secondary btn-sm" onclick='editarEstudio(<?= json_encode($e) ?>)'>✏️ Editar</button>
                    <button class="btn btn-secondary btn-sm" onclick='cambiarPass("<?= e($e['id']) ?>","<?= e($e['nombre']) ?>")'>🔑 Clave</button>
                    <button class="btn btn-ghost btn-sm" style="color:#dc2626" onclick='eliminarEstudio("<?= e($e['id']) ?>","<?= e($e['nombre']) ?>")'>🗑️</button>
                  </div>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CREAR -->
<div class="modal-overlay <?= $modal_resultado?'open':'' ?>" id="modalCrear" onclick="if(event.target===this)cerrarModal('modalCrear')">
  <div class="modal">
    <?php if ($modal_resultado): ?>
      <div style="text-align:center;margin-bottom:16px">
        <div style="width:52px;height:52px;background:#ecfdf5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:24px;color:#059669">✓</div>
      </div>
      <div class="modal-title">Estudio creado</div>
      <div class="modal-sub">Envía estas credenciales al contador por WhatsApp:</div>
      <div style="background:var(--gris-50);border:1px solid var(--gris-200);border-radius:10px;padding:16px;font-size:13px;line-height:2.4;margin-bottom:16px">
        <div>📧 <strong>Email:</strong> <?= e($modal_resultado['email']) ?></div>
        <div>🔑 <strong>Clave:</strong> <strong style="background:#fef9c3;padding:2px 8px;border-radius:4px;font-size:15px"><?= e($modal_resultado['pass']) ?></strong></div>
        <div>🌐 <strong>Web:</strong> <?= APP_URL ?>/login.php</div>
        <div>📋 <strong>Plan:</strong> <?= PLAN_NOMBRES[$modal_resultado['plan']] ?> — S/ <?= PLAN_PRECIOS[$modal_resultado['plan']] ?>/mes</div>
      </div>
      <button class="btn btn-primary w-full" style="justify-content:center" onclick="cerrarModal('modalCrear');location.href='/admin/estudios.php'">Listo</button>
    <?php else: ?>
      <div class="modal-title">Nuevo estudio contable</div>
      <div class="modal-sub">Se crea el acceso automáticamente.</div>
      <?php if ($error && !$modal_resultado): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="action" value="crear">
        <div class="form-group"><label class="form-label">Nombre del estudio *</label><input type="text" name="nombre" class="form-input" required placeholder="Estudio Rodríguez y Asoc."></div>
        <div class="form-group"><label class="form-label">RUC *</label><input type="text" name="ruc" class="form-input" required maxlength="11" placeholder="20512345678"></div>
        <div class="form-group"><label class="form-label">Email del contador *</label><input type="email" name="email_admin" class="form-input" required placeholder="contador@email.com"></div>
        <div class="form-group"><label class="form-label">Plan</label>
          <select name="plan" class="form-select">
            <option value="basico">Básico — S/49.90/mes (hasta 10 empresas)</option>
            <option value="profesional">Profesional — S/99.90/mes (hasta 25 empresas)</option>
            <option value="ilimitado">Ilimitado — S/200.00/mes (sin límite)</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Días de acceso</label>
          <select name="dias" class="form-select">
            <option value="30">30 días (1 mes)</option>
            <option value="60">60 días (2 meses)</option>
            <option value="90">90 días (3 meses)</option>
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

<!-- MODAL EDITAR -->
<div class="modal-overlay" id="modalEditar" onclick="if(event.target===this)cerrarModal('modalEditar')">
  <div class="modal">
    <!-- Tabs -->
    <div style="display:flex;border-bottom:1px solid var(--gris-200);margin-bottom:20px;gap:0">
      <button class="tab-btn active" onclick="showTab('tabDatos')">📋 Datos</button>
      <button class="tab-btn" onclick="showTab('tabPlan')">⭐ Plan</button>
      <button class="tab-btn" onclick="showTab('tabPass')">🔑 Contraseña</button>
    </div>

    <!-- Tab datos -->
    <div id="tabDatos" class="tab-content active">
      <div class="modal-title" style="margin-bottom:4px">Editar estudio</div>
      <div class="modal-sub" id="editNombre" style="margin-bottom:16px"></div>
      <form method="POST" id="formEditar">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="estudio_id" id="editId">
        <div class="form-group"><label class="form-label">Nombre *</label><input type="text" name="nombre" id="editNombreInput" class="form-input" required></div>
        <div class="form-group"><label class="form-label">RUC *</label><input type="text" name="ruc" id="editRuc" class="form-input" required maxlength="11"></div>
        <div class="form-group"><label class="form-label">Email del contador *</label><input type="email" name="email_admin" id="editEmail" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Estado</label>
          <select name="estado" id="editEstado" class="form-select">
            <option value="activo">Activo</option>
            <option value="vencido">Vencido</option>
            <option value="suspendido">Suspendido</option>
          </select>
        </div>
        <!-- Plan oculto para no perderlo -->
        <input type="hidden" name="plan" id="editPlanHidden">
        <input type="hidden" name="dias" value="30">
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditar')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>

    <!-- Tab plan -->
    <div id="tabPlan" class="tab-content">
      <div class="modal-title" style="margin-bottom:16px">Cambiar plan</div>
      <form method="POST">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="estudio_id" id="editIdPlan">
        <input type="hidden" name="nombre" id="editNombrePlan">
        <input type="hidden" name="ruc" id="editRucPlan">
        <input type="hidden" name="email_admin" id="editEmailPlan">
        <input type="hidden" name="estado" id="editEstadoPlan">
        <div class="form-group"><label class="form-label">Plan</label>
          <select name="plan" id="editPlan" class="form-select">
            <option value="basico">Básico — S/49.90/mes (hasta 10 empresas)</option>
            <option value="profesional">Profesional — S/99.90/mes (hasta 25 empresas)</option>
            <option value="ilimitado">Ilimitado — S/200.00/mes (sin límite)</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Renovar por</label>
          <select name="dias" class="form-select">
            <option value="30">30 días (1 mes)</option>
            <option value="60">60 días (2 meses)</option>
            <option value="90">90 días (3 meses)</option>
            <option value="365">365 días (1 año)</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditar')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Actualizar plan</button>
        </div>
      </form>
    </div>

    <!-- Tab contraseña -->
    <div id="tabPass" class="tab-content">
      <div class="modal-title" style="margin-bottom:4px">Cambiar contraseña</div>
      <div class="modal-sub" style="margin-bottom:16px">La nueva clave se enviará al contador por WhatsApp.</div>
      <form method="POST">
        <input type="hidden" name="action" value="cambiar_password">
        <input type="hidden" name="estudio_id" id="editIdPass">
        <div class="form-group">
          <label class="form-label">Nueva contraseña *</label>
          <input type="text" name="pass_nueva" class="form-input" required minlength="6" placeholder="Escribe la nueva clave">
          <div style="font-size:11px;color:var(--gris-400);margin-top:4px">Mínimo 6 caracteres. Puedes poner algo fácil de recordar.</div>
        </div>
        <div class="alert alert-warning" style="margin-bottom:0">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Recuerda enviar la nueva clave al contador por WhatsApp.
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditar')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL ELIMINAR -->
<div class="modal-overlay" id="modalEliminar" onclick="if(event.target===this)cerrarModal('modalEliminar')">
  <div class="modal" style="max-width:420px">
    <div style="text-align:center;margin-bottom:16px">
      <div style="width:52px;height:52px;background:#fef2f2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:24px">⚠️</div>
    </div>
    <div class="modal-title" style="text-align:center">¿Eliminar estudio?</div>
    <div class="modal-sub" style="text-align:center" id="eliminarNombre"></div>
    <div class="alert alert-error" style="margin-top:12px">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Esta acción eliminará TODOS los datos: empresas, documentos, usuarios y descargas. Es irreversible.
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="eliminar">
      <input type="hidden" name="estudio_id" id="eliminarId">
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEliminar')">Cancelar</button>
        <button type="submit" class="btn btn-danger">Sí, eliminar todo</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModal(id) { document.getElementById(id).classList.add('open'); }
function cerrarModal(id){ document.getElementById(id).classList.remove('open'); }

function showTab(tab) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById(tab).classList.add('active');
  event.target.classList.add('active');
}

function editarEstudio(e) {
  document.getElementById('editId').value       = e.id;
  document.getElementById('editNombre').textContent = e.nombre;
  document.getElementById('editNombreInput').value  = e.nombre;
  document.getElementById('editRuc').value      = e.ruc;
  document.getElementById('editEmail').value    = e.email_admin;
  document.getElementById('editEstado').value   = e.estado;
  document.getElementById('editPlanHidden').value = e.plan;
  // Tab plan
  document.getElementById('editIdPlan').value    = e.id;
  document.getElementById('editNombrePlan').value = e.nombre;
  document.getElementById('editRucPlan').value   = e.ruc;
  document.getElementById('editEmailPlan').value = e.email_admin;
  document.getElementById('editEstadoPlan').value = e.estado;
  document.getElementById('editPlan').value      = e.plan;
  // Tab pass
  document.getElementById('editIdPass').value   = e.id;
  // Reset tabs
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tabDatos').classList.add('active');
  document.querySelectorAll('.tab-btn')[0].classList.add('active');
  abrirModal('modalEditar');
}

function cambiarPass(id, nombre) {
  // Atajo directo al tab de contraseña
  document.getElementById('editIdPass').value = id;
  document.getElementById('editId').value = id;
  document.getElementById('editNombre').textContent = nombre;
  document.getElementById('editIdPlan').value = id;
  document.getElementById('editEstadoPlan').value = 'activo';
  document.getElementById('editNombrePlan').value = nombre;
  document.getElementById('editRucPlan').value = '';
  document.getElementById('editEmailPlan').value = '';
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tabPass').classList.add('active');
  document.querySelectorAll('.tab-btn')[2].classList.add('active');
  abrirModal('modalEditar');
}

function eliminarEstudio(id, nombre) {
  document.getElementById('eliminarId').value = id;
  document.getElementById('eliminarNombre').textContent = 'Estudio: ' + nombre;
  abrirModal('modalEliminar');
}
</script>
</body>
</html>
