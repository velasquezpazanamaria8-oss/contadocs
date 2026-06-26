<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');
$user    = Auth::usuario();
$estudio = Database::fetch("SELECT * FROM estudios WHERE id=?", [$user['estudio_id']]);
$limite  = PLAN_LIMITES[$estudio['plan']] ?? 10;

$mensaje = ''; $error = ''; $modal_resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear') {
        $empresas_count = Database::fetch("SELECT COUNT(*) as n FROM empresas_cliente WHERE estudio_id=?", [$user['estudio_id']])['n'];
        if ($empresas_count >= $limite) {
            $error = "Límite de $limite empresas alcanzado. Actualiza tu plan.";
        } else {
            $razon = trim($_POST['razon_social'] ?? '');
            $ruc   = trim($_POST['ruc'] ?? '');
            $email = strtolower(trim($_POST['email_acceso'] ?? ''));
            if (!$razon || !$ruc || !$email) { $error = 'Completa todos los campos.'; }
            else {
                $existe = Database::fetch("SELECT id FROM usuarios WHERE email=?", [$email]);
                if ($existe) { $error = 'Ya existe un usuario con ese correo.'; }
                else {
                    $passTemp = Auth::generarPasswordTemporal();
                    $passHash = Auth::hashPassword($passTemp);
                    $emp_id = uuid(); $usr_id = uuid();
                    Database::query("INSERT INTO empresas_cliente (id,estudio_id,razon_social,ruc,email_acceso) VALUES (?,?,?,?,?)",
                        [$emp_id,$user['estudio_id'],$razon,$ruc,$email]);
                    Database::query("INSERT INTO usuarios (id,email,password,rol,nombre,primer_login,estudio_id,empresa_id) VALUES (?,?,?,?,?,?,?,?)",
                        [$usr_id,$email,$passHash,'cliente',$razon,1,$user['estudio_id'],$emp_id]);
                    $modal_resultado = ['email'=>$email,'pass'=>$passTemp,'nombre'=>$razon];
                }
            }
        }
    }

    if ($action === 'editar') {
        $emp_id = $_POST['empresa_id'] ?? '';
        $razon  = trim($_POST['razon_social'] ?? '');
        $ruc    = trim($_POST['ruc'] ?? '');
        $email  = strtolower(trim($_POST['email_acceso'] ?? ''));
        $activo = $_POST['activo'] ?? '1';
        if (!$razon || !$ruc || !$email) { $error = 'Completa todos los campos.'; }
        else {
            Database::query("UPDATE empresas_cliente SET razon_social=?,ruc=?,email_acceso=?,activo=? WHERE id=? AND estudio_id=?",
                [$razon,$ruc,$email,$activo,$emp_id,$user['estudio_id']]);
            Database::query("UPDATE usuarios SET email=?,nombre=?,activo=? WHERE empresa_id=?",
                [$email,$razon,$activo,$emp_id]);
            $mensaje = 'Empresa actualizada correctamente.';
        }
    }

    if ($action === 'cambiar_password') {
        $emp_id    = $_POST['empresa_id'] ?? '';
        $pass_nueva = $_POST['pass_nueva'] ?? '';
        if (strlen($pass_nueva) < 6) { $error = 'La contraseña debe tener al menos 6 caracteres.'; }
        else {
            $hash = Auth::hashPassword($pass_nueva);
            Database::query("UPDATE usuarios SET password=?,primer_login=0 WHERE empresa_id=?", [$hash,$emp_id]);
            $mensaje = 'Contraseña cambiada. Comunícala a tu cliente.';
        }
    }

    if ($action === 'eliminar') {
        $emp_id = $_POST['empresa_id'] ?? '';
        $docs = Database::fetchAll("SELECT id FROM documentos WHERE empresa_id=?", [$emp_id]);
        foreach ($docs as $doc) {
            Database::query("DELETE FROM descargas_log WHERE documento_id=?", [$doc['id']]);
        }
        Database::query("DELETE FROM documentos WHERE empresa_id=?", [$emp_id]);
        Database::query("DELETE FROM descargas_log WHERE empresa_id=?", [$emp_id]);
        Database::query("DELETE FROM usuarios WHERE empresa_id=?", [$emp_id]);
        Database::query("DELETE FROM empresas_cliente WHERE id=? AND estudio_id=?", [$emp_id,$user['estudio_id']]);
        $mensaje = 'Empresa eliminada correctamente.';
    }
}

$buscar = trim($_GET['q'] ?? '');
$where = "ec.estudio_id=?"; $params = [$user['estudio_id']];
if ($buscar) { $where .= " AND (ec.razon_social LIKE ? OR ec.ruc LIKE ? OR ec.email_acceso LIKE ?)"; $params = array_merge($params,["%$buscar%","%$buscar%","%$buscar%"]); }

$empresas = Database::fetchAll(
    "SELECT ec.*, (SELECT COUNT(*) FROM documentos WHERE empresa_id=ec.id) as total_docs,
            (SELECT COUNT(*) FROM descargas_log WHERE empresa_id=ec.id) as total_descargas
     FROM empresas_cliente ec WHERE $where ORDER BY ec.razon_social ASC", $params);
$usados = Database::fetch("SELECT COUNT(*) as n FROM empresas_cliente WHERE estudio_id=?", [$user['estudio_id']])['n'];

$nav_active='clientes'; $user_rol='contador'; $user_nombre=$estudio['nombre']??''; $user_plan=$estudio['plan']??'basico';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mis clientes — ContaDocs</title>
<link rel="stylesheet" href="/assets/css/app.css">
<style>
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:50;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal{background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:480px;padding:24px;max-height:90vh;overflow-y:auto}
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
      <div><div class="topbar-title">Mis clientes</div><div class="topbar-sub"><?= e($estudio['nombre']??'') ?> · <?= $usados ?>/<?= $limite===999999?'∞':$limite ?> empresas</div></div>
      <div class="topbar-actions">
        <?php if ($usados < $limite): ?>
        <button class="btn btn-primary btn-sm" onclick="abrirModal('modalCrear')">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Agregar empresa
        </button>
        <?php else: ?>
        <span class="badge badge-amber">Límite alcanzado</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="app-content">
      <?php if ($mensaje): ?><div class="alert alert-success"><?= e($mensaje) ?></div><?php endif; ?>
      <?php if ($error && !$modal_resultado): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <!-- Métricas -->
      <div class="metrics-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
        <div class="metric-card">
          <div class="metric-icon" style="background:#ecfdf5"><svg fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
          <div><div class="metric-label">Empresas</div><div class="metric-value"><?= $usados ?></div><div class="metric-sub"><?= $limite-$usados ?> cupos libres</div></div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#eff6ff"><svg fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
          <div><div class="metric-label">Total docs</div><div class="metric-value"><?= array_sum(array_column($empresas,'total_docs')) ?></div></div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#f5f3ff"><svg fill="none" viewBox="0 0 24 24" stroke="#7c3aed" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg></div>
          <div><div class="metric-label">Descargas</div><div class="metric-value"><?= array_sum(array_column($empresas,'total_descargas')) ?></div></div>
        </div>
      </div>

      <!-- Filtro búsqueda -->
      <form method="GET" style="display:flex;gap:10px;margin-bottom:16px">
        <div style="position:relative;flex:1">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--gris-400)"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="text" name="q" value="<?= e($buscar) ?>" placeholder="Buscar por nombre, RUC o email..." class="form-input" style="padding-left:34px">
        </div>
        <button type="submit" class="btn btn-secondary">Buscar</button>
        <?php if ($buscar): ?><a href="/contador/clientes.php" class="btn btn-secondary">Limpiar</a><?php endif; ?>
      </form>

      <!-- Tabla -->
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Empresa</th><th>RUC</th><th>Docs</th><th>Descargas</th><th>Estado</th><th style="min-width:200px">Acciones</th></tr></thead>
            <tbody>
              <?php if (empty($empresas)): ?>
              <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--gris-400)">No tienes clientes aún. Agrega el primero.</td></tr>
              <?php else: foreach ($empresas as $emp): ?>
              <tr>
                <td>
                  <div style="font-weight:500;color:var(--gris-900)"><?= e($emp['razon_social']) ?></div>
                  <div style="font-size:11px;color:var(--gris-400)"><?= e($emp['email_acceso']) ?></div>
                </td>
                <td class="mono"><?= e($emp['ruc']) ?></td>
                <td><span class="badge <?= $emp['total_docs']>0?'badge-green':'badge-amber' ?>"><?= $emp['total_docs'] ?></span></td>
                <td style="font-size:12px;color:var(--gris-500)"><?= $emp['total_descargas'] ?></td>
                <td><span class="badge <?= $emp['activo']?'badge-green':'badge-red' ?>"><?= $emp['activo']?'Activo':'Inactivo' ?></span></td>
                <td>
                  <div style="display:flex;gap:4px;flex-wrap:wrap">
                    <a href="/contador/subir.php?empresa_id=<?= e($emp['id']) ?>" class="btn btn-primary btn-sm">📤 Subir</a>
                    <button class="btn btn-secondary btn-sm" onclick='editarEmpresa(<?= json_encode($emp) ?>)'>✏️ Editar</button>
                    <button class="btn btn-ghost btn-sm" style="color:#dc2626" onclick='eliminarEmpresa("<?= e($emp['id']) ?>","<?= e($emp['razon_social']) ?>")'>🗑️</button>
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
      <div style="text-align:center;margin-bottom:16px"><div style="width:52px;height:52px;background:#ecfdf5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:24px;color:#059669">✓</div></div>
      <div class="modal-title">Empresa creada</div>
      <div class="modal-sub">Envía estas credenciales a tu cliente:</div>
      <div style="background:var(--gris-50);border:1px solid var(--gris-200);border-radius:10px;padding:16px;font-size:13px;line-height:2.4;margin-bottom:16px">
        <div>📧 <strong>Email:</strong> <?= e($modal_resultado['email']) ?></div>
        <div>🔑 <strong>Clave:</strong> <strong style="background:#fef9c3;padding:2px 8px;border-radius:4px;font-size:15px"><?= e($modal_resultado['pass']) ?></strong></div>
        <div>🌐 <strong>Web:</strong> <?= APP_URL ?>/login.php</div>
      </div>
      <button class="btn btn-primary w-full" style="justify-content:center" onclick="location.href='/contador/clientes.php'">Listo</button>
    <?php else: ?>
      <div class="modal-title">Agregar empresa cliente</div>
      <div class="modal-sub">Se crea un acceso automático para este cliente.</div>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
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
    <div style="display:flex;border-bottom:1px solid var(--gris-200);margin-bottom:20px">
      <button class="tab-btn active" onclick="showTab('tabDatos2')">📋 Datos</button>
      <button class="tab-btn" onclick="showTab('tabPass2')">🔑 Contraseña</button>
    </div>
    <div id="tabDatos2" class="tab-content active">
      <div class="modal-title" style="margin-bottom:16px">Editar empresa cliente</div>
      <form method="POST">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="empresa_id" id="editEmpId">
        <div class="form-group"><label class="form-label">Razón social *</label><input type="text" name="razon_social" id="editRazon" class="form-input" required></div>
        <div class="form-group"><label class="form-label">RUC *</label><input type="text" name="ruc" id="editRucEmp" class="form-input" required maxlength="11"></div>
        <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email_acceso" id="editEmailEmp" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Estado</label>
          <select name="activo" id="editActivoEmp" class="form-select">
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
    <div id="tabPass2" class="tab-content">
      <div class="modal-title" style="margin-bottom:4px">Cambiar contraseña del cliente</div>
      <div class="modal-sub" style="margin-bottom:16px">El cliente deberá usar esta nueva clave para ingresar.</div>
      <form method="POST">
        <input type="hidden" name="action" value="cambiar_password">
        <input type="hidden" name="empresa_id" id="editEmpIdPass">
        <div class="form-group">
          <label class="form-label">Nueva contraseña *</label>
          <input type="text" name="pass_nueva" class="form-input" required minlength="6" placeholder="Ej: 12345678">
          <div style="font-size:11px;color:var(--gris-400);margin-top:4px">Mínimo 6 caracteres.</div>
        </div>
        <div class="alert alert-warning">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Comunica la nueva clave al cliente.
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
    <div style="text-align:center;margin-bottom:16px"><div style="width:52px;height:52px;background:#fef2f2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:24px">⚠️</div></div>
    <div class="modal-title" style="text-align:center">¿Eliminar empresa?</div>
    <div class="modal-sub" style="text-align:center" id="eliminarEmpNombre"></div>
    <div class="alert alert-error" style="margin-top:12px">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Se eliminarán todos sus documentos y descargas. Irreversible.
    </div>
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
function abrirModal(id) { document.getElementById(id).classList.add('open'); }
function cerrarModal(id){ document.getElementById(id).classList.remove('open'); }
function showTab(tab) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById(tab).classList.add('active');
  event.target.classList.add('active');
}
function editarEmpresa(e) {
  document.getElementById('editEmpId').value    = e.id;
  document.getElementById('editRazon').value    = e.razon_social;
  document.getElementById('editRucEmp').value   = e.ruc;
  document.getElementById('editEmailEmp').value = e.email_acceso;
  document.getElementById('editActivoEmp').value= e.activo;
  document.getElementById('editEmpIdPass').value= e.id;
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tabDatos2').classList.add('active');
  document.querySelectorAll('#modalEditar .tab-btn')[0].classList.add('active');
  abrirModal('modalEditar');
}
function eliminarEmpresa(id, nombre) {
  document.getElementById('eliminarEmpId').value = id;
  document.getElementById('eliminarEmpNombre').textContent = nombre;
  abrirModal('modalEliminar');
}
</script>
</body>
</html>
