<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('superadmin');
$user = Auth::usuario();

// Acciones POST
$modal_resultado = null;
$modal_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear_estudio') {
        $nombre      = trim($_POST['nombre'] ?? '');
        $ruc         = trim($_POST['ruc'] ?? '');
        $email_admin = strtolower(trim($_POST['email_admin'] ?? ''));
        $plan        = $_POST['plan'] ?? 'basico';

        if (!$nombre || !$ruc || !$email_admin) {
            $modal_error = 'Completa todos los campos.';
        } else {
            $existe = Database::fetch("SELECT id FROM usuarios WHERE email = ?", [$email_admin]);
            if ($existe) {
                $modal_error = 'Ya existe un usuario con ese correo.';
            } else {
                $passTemp = Auth::generarPasswordTemporal();
                $passHash = Auth::hashPassword($passTemp);
                $vence    = date('Y-m-d H:i:s', strtotime('+1 month'));
                $id       = uuid();
                $uid      = uuid();

                Database::query(
                    "INSERT INTO estudios (id, nombre, ruc, email_admin, plan, estado, vence_en) VALUES (?,?,?,?,?,?,?)",
                    [$id, $nombre, $ruc, $email_admin, $plan, 'activo', $vence]
                );
                Database::query(
                    "INSERT INTO usuarios (id, email, password, rol, nombre, primer_login, estudio_id) VALUES (?,?,?,?,?,?,?)",
                    [$uid, $email_admin, $passHash, 'contador', $nombre, 1, $id]
                );
                $modal_resultado = ['email' => $email_admin, 'pass' => $passTemp, 'nombre' => $nombre, 'plan' => $plan];
            }
        }
    }

    if ($action === 'cambiar_estado') {
        $eid    = $_POST['estudio_id'] ?? '';
        $estado = $_POST['estado'] ?? '';
        if ($eid && in_array($estado, ['activo','vencido','suspendido'])) {
            Database::query("UPDATE estudios SET estado = ? WHERE id = ?", [$estado, $eid]);
        }
        redirect('/admin/estudios.php');
    }

    if ($action === 'cambiar_plan') {
        $eid  = $_POST['estudio_id'] ?? '';
        $plan = $_POST['plan'] ?? '';
        $dias = (int)($_POST['dias'] ?? 30);
        if ($eid && in_array($plan, ['basico','profesional','ilimitado'])) {
            $vence = date('Y-m-d H:i:s', strtotime("+{$dias} days"));
            Database::query("UPDATE estudios SET plan = ?, estado = 'activo', vence_en = ? WHERE id = ?", [$plan, $vence, $eid]);
        }
        redirect('/admin/estudios.php');
    }
}

// Filtros
$buscar = trim($_GET['q'] ?? '');
$filtro_estado = $_GET['estado'] ?? '';
$filtro_plan   = $_GET['plan'] ?? '';

$where  = "1=1";
$params = [];
if ($buscar)        { $where .= " AND (e.nombre LIKE ? OR e.ruc LIKE ? OR e.email_admin LIKE ?)"; $params = array_merge($params, ["%$buscar%","%$buscar%","%$buscar%"]); }
if ($filtro_estado) { $where .= " AND e.estado = ?"; $params[] = $filtro_estado; }
if ($filtro_plan)   { $where .= " AND e.plan = ?";   $params[] = $filtro_plan; }

$estudios = Database::fetchAll(
    "SELECT e.*, (SELECT COUNT(*) FROM empresas_cliente WHERE estudio_id = e.id) as total_empresas
     FROM estudios e WHERE $where ORDER BY e.created_at DESC",
    $params
);

// Stats
$stats = Database::fetch(
    "SELECT
        COUNT(*) as total,
        SUM(estado='activo') as activos,
        SUM(estado='vencido') as vencidos,
        SUM(estado='suspendido') as suspendidos
     FROM estudios"
);

$nav_active  = 'estudios';
$user_rol    = 'superadmin';
$user_nombre = 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Estudios — ContaDocs Admin</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div>
        <div class="topbar-title">Estudios contables</div>
        <div class="topbar-sub">Gestiona todos los estudios registrados</div>
      </div>
      <div class="topbar-actions">
        <button class="btn btn-primary btn-sm" onclick="abrirModal('modalCrear')">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Nuevo estudio
        </button>
      </div>
    </div>

    <div class="app-content">
      <!-- Stats rápidas -->
      <div class="metrics-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
        <div class="metric-card">
          <div class="metric-icon" style="background:#eff6ff">
            <svg fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
          </div>
          <div><div class="metric-label">Total estudios</div><div class="metric-value"><?= $stats['total'] ?></div></div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#ecfdf5">
            <svg fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div><div class="metric-label">Activos</div><div class="metric-value"><?= $stats['activos'] ?></div></div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#fef2f2">
            <svg fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div><div class="metric-label">Vencidos</div><div class="metric-value" style="color:<?= $stats['vencidos']>0?'#dc2626':'' ?>"><?= $stats['vencidos'] ?></div></div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#fffbeb">
            <svg fill="none" viewBox="0 0 24 24" stroke="#d97706" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
          </div>
          <div><div class="metric-label">Suspendidos</div><div class="metric-value"><?= $stats['suspendidos'] ?></div></div>
        </div>
      </div>

      <!-- Filtros -->
      <form method="GET" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
        <div style="position:relative;flex:1;min-width:200px">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--gris-400)"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="text" name="q" value="<?= e($buscar) ?>" placeholder="Buscar por nombre, RUC o email..." class="form-input" style="padding-left:34px">
        </div>
        <select name="estado" class="form-select" style="width:auto" onchange="this.form.submit()">
          <option value="">Todos los estados</option>
          <option value="activo"     <?= $filtro_estado==='activo'?'selected':'' ?>>Activos</option>
          <option value="vencido"    <?= $filtro_estado==='vencido'?'selected':'' ?>>Vencidos</option>
          <option value="suspendido" <?= $filtro_estado==='suspendido'?'selected':'' ?>>Suspendidos</option>
        </select>
        <select name="plan" class="form-select" style="width:auto" onchange="this.form.submit()">
          <option value="">Todos los planes</option>
          <option value="basico"      <?= $filtro_plan==='basico'?'selected':'' ?>>Básico</option>
          <option value="profesional" <?= $filtro_plan==='profesional'?'selected':'' ?>>Profesional</option>
          <option value="ilimitado"   <?= $filtro_plan==='ilimitado'?'selected':'' ?>>Ilimitado</option>
        </select>
        <?php if ($buscar||$filtro_estado||$filtro_plan): ?>
        <a href="/admin/estudios.php" class="btn btn-secondary btn-sm">Limpiar</a>
        <?php endif; ?>
        <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
        <span class="text-muted" style="font-size:12px;margin-left:auto"><?= count($estudios) ?> resultados</span>
      </form>

      <!-- Tabla -->
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Estudio</th>
                <th>RUC</th>
                <th>Plan</th>
                <th>Empresas</th>
                <th>Vence</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($estudios)): ?>
              <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gris-400)">No hay estudios que coincidan con tu búsqueda.</td></tr>
              <?php else: foreach ($estudios as $e):
                $vence_ts   = $e['vence_en'] ? strtotime($e['vence_en']) : null;
                $dias_restantes = $vence_ts ? ceil(($vence_ts - time()) / 86400) : null;
                $por_vencer = $dias_restantes !== null && $dias_restantes <= 5 && $dias_restantes > 0;
              ?>
              <tr>
                <td>
                  <div style="font-weight:500;color:var(--gris-900)"><?= e($e['nombre']) ?></div>
                  <div style="font-size:11px;color:var(--gris-400)"><?= e($e['email_admin']) ?></div>
                </td>
                <td class="mono"><?= e($e['ruc']) ?></td>
                <td>
                  <?php $pc=['basico'=>'badge-gray','profesional'=>'badge-blue','ilimitado'=>'badge-purple']; ?>
                  <span class="badge <?= $pc[$e['plan']]??'badge-gray' ?>"><?= PLAN_NOMBRES[$e['plan']]??$e['plan'] ?></span>
                </td>
                <td style="font-weight:500"><?= $e['total_empresas'] ?></td>
                <td>
                  <?php if ($vence_ts): ?>
                  <span style="font-size:12px;color:<?= $por_vencer?'#dc2626':($dias_restantes<0?'#dc2626':'var(--gris-500)') ?>">
                    <?= fechaEs($e['vence_en']) ?>
                    <?php if ($por_vencer): ?><br><small style="color:#dc2626">Vence en <?= $dias_restantes ?> días</small><?php endif; ?>
                    <?php if ($dias_restantes < 0): ?><br><small style="color:#dc2626">Venció hace <?= abs($dias_restantes) ?> días</small><?php endif; ?>
                  </span>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                  <?php $sc=['activo'=>'badge-green','vencido'=>'badge-red','suspendido'=>'badge-amber']; ?>
                  <span class="badge <?= $sc[$e['estado']]??'badge-gray' ?>"><?= e($e['estado']) ?></span>
                </td>
                <td>
                  <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <!-- Renovar -->
                    <button class="btn btn-secondary btn-sm" onclick="abrirRenovar('<?= e($e['id']) ?>','<?= e($e['nombre']) ?>','<?= e($e['plan']) ?>')">
                      Renovar
                    </button>
                    <!-- Cambiar estado -->
                    <?php if ($e['estado'] === 'activo'): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('¿Suspender este estudio?')">
                      <input type="hidden" name="action" value="cambiar_estado">
                      <input type="hidden" name="estudio_id" value="<?= e($e['id']) ?>">
                      <input type="hidden" name="estado" value="suspendido">
                      <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--rojo)">Suspender</button>
                    </form>
                    <?php else: ?>
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="action" value="cambiar_estado">
                      <input type="hidden" name="estudio_id" value="<?= e($e['id']) ?>">
                      <input type="hidden" name="estado" value="activo">
                      <button type="submit" class="btn btn-ghost btn-sm" style="color:#059669">Activar</button>
                    </form>
                    <?php endif; ?>
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

<!-- Modal: Crear estudio -->
<div class="modal-overlay <?= ($modal_resultado||$modal_error)?'open':'' ?>" id="modalCrear" onclick="if(event.target===this)cerrarModal('modalCrear')">
  <div class="modal">
    <?php if ($modal_resultado): ?>
      <div style="text-align:center;margin-bottom:16px">
        <div style="width:52px;height:52px;background:#ecfdf5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:24px">✓</div>
      </div>
      <div class="modal-title">Estudio creado exitosamente</div>
      <div class="modal-sub">Comparte estas credenciales por WhatsApp con el contador:</div>
      <div style="background:var(--gris-50);border:1px solid var(--gris-200);border-radius:10px;padding:16px;font-size:13px;line-height:2.2;margin-bottom:16px">
        <div>📧 <strong>Email:</strong> <?= e($modal_resultado['email']) ?></div>
        <div>🔑 <strong>Clave:</strong> <strong style="background:#fef9c3;padding:2px 6px;border-radius:4px"><?= e($modal_resultado['pass']) ?></strong></div>
        <div>🌐 <strong>Web:</strong> <?= APP_URL ?>/login.php</div>
        <div>📋 <strong>Plan:</strong> <?= PLAN_NOMBRES[$modal_resultado['plan']] ?></div>
      </div>
      <button class="btn btn-primary w-full" style="justify-content:center" onclick="cerrarModal('modalCrear');location.reload()">Listo</button>
    <?php else: ?>
      <div class="modal-title">Nuevo estudio contable</div>
      <div class="modal-sub">Se creará el acceso automáticamente para el contador.</div>
      <?php if ($modal_error): ?><div class="alert alert-error"><?= e($modal_error) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="action" value="crear_estudio">
        <div class="form-group">
          <label class="form-label">Nombre del estudio *</label>
          <input type="text" name="nombre" class="form-input" placeholder="Estudio Rodríguez y Asoc." required>
        </div>
        <div class="form-group">
          <label class="form-label">RUC *</label>
          <input type="text" name="ruc" class="form-input" placeholder="20512345678" required maxlength="11">
        </div>
        <div class="form-group">
          <label class="form-label">Email del contador *</label>
          <input type="email" name="email_admin" class="form-input" placeholder="contador@email.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Plan</label>
          <select name="plan" class="form-select">
            <option value="basico">Básico — S/49.90/mes (hasta 10 empresas)</option>
            <option value="profesional">Profesional — S/99.90/mes (hasta 25 empresas)</option>
            <option value="ilimitado">Ilimitado — S/200.00/mes (sin límite)</option>
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

<!-- Modal: Renovar plan -->
<div class="modal-overlay" id="modalRenovar" onclick="if(event.target===this)cerrarModal('modalRenovar')">
  <div class="modal">
    <div class="modal-title">Renovar / cambiar plan</div>
    <div class="modal-sub" id="renovarNombre"></div>
    <form method="POST">
      <input type="hidden" name="action" value="cambiar_plan">
      <input type="hidden" name="estudio_id" id="renovarId">
      <div class="form-group">
        <label class="form-label">Plan</label>
        <select name="plan" id="renovarPlan" class="form-select">
          <option value="basico">Básico — S/49.90/mes</option>
          <option value="profesional">Profesional — S/99.90/mes</option>
          <option value="ilimitado">Ilimitado — S/200.00/mes</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Días de acceso</label>
        <select name="dias" class="form-select">
          <option value="30">30 días (1 mes)</option>
          <option value="60">60 días (2 meses)</option>
          <option value="90">90 días (3 meses)</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalRenovar')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModal(id)  { document.getElementById(id).classList.add('open'); }
function cerrarModal(id) { document.getElementById(id).classList.remove('open'); }
function abrirRenovar(id, nombre, plan) {
  document.getElementById('renovarId').value = id;
  document.getElementById('renovarNombre').textContent = nombre;
  document.getElementById('renovarPlan').value = plan;
  abrirModal('modalRenovar');
}
</script>
</body>
</html>
