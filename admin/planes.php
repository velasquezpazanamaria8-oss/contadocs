<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('superadmin');

$mensaje = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear') {
        $nombre   = trim($_POST['nombre'] ?? '');
  /*       $precio   = (float)($_POST['precio'] ?? 0); */
        $precio   = $_POST['precio'] ;
        $limite   = (int)($_POST['limite_empresas'] ?? 10);
        $dias     = (int)($_POST['dias_acceso'] ?? 30);
        $desc     = trim($_POST['descripcion'] ?? '');
        if (!$nombre || $precio <= 0) { $error = 'Nombre y precio son obligatorios.'; }
        else {
            $id = uuid();
            Database::query("INSERT INTO planes (id,nombre,precio,limite_empresas,dias_acceso,descripcion,activo) VALUES (?,?,?,?,?,?,1)",
                [$id,$nombre,$precio,$limite,$dias,$desc]);
            $mensaje = "Plan \"$nombre\" creado correctamente.";
        }
    }

    if ($action === 'editar') {
        $pid    = $_POST['plan_id'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
      /*   $precio = (float)($_POST['precio'] ?? 0); */
      $precio   = $_POST['precio'] ;
        $limite = (int)($_POST['limite_empresas'] ?? 10);
        $dias   = (int)($_POST['dias_acceso'] ?? 30);
        $desc   = trim($_POST['descripcion'] ?? '');
        $activo = (int)($_POST['activo'] ?? 1);
        if (!$nombre || $precio <= 0) { $error = 'Nombre y precio son obligatorios.'; }
        else {
            Database::query("UPDATE planes SET nombre=?,precio=?,limite_empresas=?,dias_acceso=?,descripcion=?,activo=? WHERE id=?",
                [$nombre,$precio,$limite,$dias,$desc,$activo,$pid]);
            $mensaje = "Plan actualizado correctamente.";
        }
    }

    if ($action === 'eliminar') {
        $pid = $_POST['plan_id'] ?? '';
        $uso = Database::fetch("SELECT COUNT(*) as n FROM estudios WHERE plan_id=?", [$pid]);
        if ($uso['n'] > 0) { $error = 'No puedes eliminar un plan que tiene estudios activos.'; }
        else {
            Database::query("DELETE FROM planes WHERE id=?", [$pid]);
            $mensaje = 'Plan eliminado.';
        }
    }
}

$planes = Database::fetchAll(
    "SELECT p.*, (SELECT COUNT(*) FROM estudios WHERE plan_id=p.id) as total_estudios,
            (SELECT COUNT(*) FROM estudios WHERE plan_id=p.id AND estado='activo') as activos
     FROM planes p ORDER BY p.precio ASC"
);

$nav_active='planes'; $user_rol='superadmin'; $user_nombre='Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Planes — ContaDocs Admin</title>
<link rel="stylesheet" href="/assets/css/app.css?v=2">
<link rel="icon" type="image/png" href="/assets/img/logo_icono.svg">
<style>
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:100;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal{background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.2);width:100%;max-width:480px;padding:28px;max-height:90vh;overflow-y:auto;transform:scale(.97);transition:transform .2s}
.modal-overlay.open .modal{transform:scale(1)}
</style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div class="topbar-left"><h1>Planes de suscripción</h1><p>Crea y administra tus propios planes</p></div>
      <div class="topbar-actions">
        <button class="btn btn-primary" onclick="abrirModal('modalCrear')">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Nuevo plan
        </button>
      </div>
    </div>

    <div class="app-content">
      <?php if ($mensaje): ?><div class="alert alert-success"><?= e($mensaje) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <!-- Cards de planes -->
      <div class="grid-3" style="margin-bottom:24px">
        <?php foreach ($planes as $p): ?>
        <div class="card card-body" style="border-top:3px solid var(--azul);position:relative">
          <?php if (!$p['activo']): ?>
          <div style="position:absolute;top:12px;right:12px"><span class="badge badge-gray">Inactivo</span></div>
          <?php endif; ?>
          <div style="font-size:18px;font-weight:800;color:var(--gris-900);margin-bottom:4px"><?= e($p['nombre']) ?></div>
          <div style="font-size:32px;font-weight:900;color:var(--azul);margin:8px 0;letter-spacing:-1px">
            S/ <?= number_format($p['precio'],2) ?>
            <span style="font-size:14px;font-weight:500;color:var(--gris-400)">/mes</span>
          </div>
          <?php if ($p['descripcion']): ?>
          <div style="font-size:13px;color:var(--gris-500);margin-bottom:10px"><?= e($p['descripcion']) ?></div>
          <?php endif; ?>
          <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:14px">
            <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--gris-600)">
              <span style="color:var(--verde)">✓</span>
              <?= $p['limite_empresas']>=999999?'Empresas ilimitadas':'Hasta '.$p['limite_empresas'].' empresas' ?>
            </div>
            <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--gris-600)">
              <span style="color:var(--verde)">✓</span>
              <?= $p['dias_acceso'] ?> días de acceso por pago
            </div>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding-top:12px;border-top:1px solid var(--gris-100)">
            <span style="font-size:12px;color:var(--gris-400)"><?= $p['activos'] ?> estudios activos</span>
            <div style="display:flex;gap:6px">
              <button class="btn btn-secondary btn-sm" onclick='editarPlan(<?= json_encode($p) ?>)'>✏️ Editar</button>
              <?php if ($p['total_estudios'] == 0): ?>
              <button class="btn btn-ghost btn-sm" style="color:var(--rojo)" onclick='eliminarPlan("<?= e($p['id']) ?>","<?= e($p['nombre']) ?>")'>🗑️</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($planes)): ?>
        <div class="card card-body" style="grid-column:1/-1;text-align:center;padding:60px">
          <div style="font-size:48px;margin-bottom:16px">⭐</div>
          <div class="empty-title">No tienes planes aún</div>
          <div class="empty-sub">Crea tu primer plan para poder asignarlo a los estudios</div>
          <button class="btn btn-primary" onclick="abrirModal('modalCrear')" style="margin-top:16px">Crear primer plan</button>
        </div>
        <?php endif; ?>
      </div>

      <!-- Tabla resumen -->
      <?php if (!empty($planes)): ?>
      <div class="card">
        <div class="card-header"><div class="card-title">Resumen de planes</div></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Plan</th><th>Precio/mes</th><th>Límite empresas</th><th>Días acceso</th><th>Estudios activos</th><th>Ingreso mensual</th><th>Estado</th></tr></thead>
            <tbody>
              <?php foreach ($planes as $p): ?>
              <tr>
                <td class="fw"><?= e($p['nombre']) ?></td>
                <td style="font-weight:700;color:var(--verde-dark)">S/ <?= number_format($p['precio'],2) ?></td>
                <td><?= $p['limite_empresas']>=999999?'∞':$p['limite_empresas'] ?></td>
                <td><?= $p['dias_acceso'] ?> días</td>
                <td style="font-weight:600"><?= $p['activos'] ?></td>
                <td style="font-weight:700;color:var(--azul)">S/ <?= number_format($p['precio']*$p['activos'],2) ?></td>
                <td><span class="badge <?= $p['activo']?'badge-green':'badge-gray' ?>"><?= $p['activo']?'Activo':'Inactivo' ?></span></td>
              </tr>
              <?php endforeach; ?>
              <tr style="background:var(--gris-50)">
                <td colspan="5" style="font-weight:700;color:var(--gris-900)">TOTAL</td>
                <td style="font-weight:800;color:var(--verde-dark);font-size:15px">S/ <?= number_format(array_sum(array_map(fn($p)=>$p['precio']*$p['activos'],$planes)),2) ?></td>
                <td></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal Crear -->
<div class="modal-overlay" id="modalCrear" onclick="if(event.target===this)cerrarModal('modalCrear')">
  <div class="modal">
    <div class="modal-title">Nuevo plan</div>
    <div class="modal-sub">Define el nombre, precio y límites del plan.</div>
    <form method="POST">
      <input type="hidden" name="action" value="crear">
      <div class="form-group"><label class="form-label">Nombre del plan *</label><input type="text" name="nombre" class="form-input" required placeholder="Ej: Básico, Profesional, VIP..."></div>
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Precio mensual (S/) *</label><input type="number" name="precio" class="form-input" required step="0.01" min="1" placeholder="49.90"></div>
        <div class="form-group"><label class="form-label">Días de acceso</label><input type="number" name="dias_acceso" class="form-input" value="30" min="1"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Límite de empresas cliente</label>
        <input type="number" name="limite_empresas" class="form-input" value="10" min="1">
        <div class="form-hint">Escribe 999999 para ilimitado</div>
      </div>
      <div class="form-group"><label class="form-label">Descripción (opcional)</label><input type="text" name="descripcion" class="form-input" placeholder="Ej: Ideal para estudios pequeños"></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCrear')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Crear plan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar -->
<div class="modal-overlay" id="modalEditar" onclick="if(event.target===this)cerrarModal('modalEditar')">
  <div class="modal">
    <div class="modal-title">Editar plan</div>
    <div class="modal-sub">Los cambios aplican a nuevos estudios. Los existentes mantienen su precio actual.</div>
    <form method="POST">
      <input type="hidden" name="action" value="editar">
      <input type="hidden" name="plan_id" id="editPlanId">
      <div class="form-group"><label class="form-label">Nombre *</label><input type="text" name="nombre" id="editNombrePlan" class="form-input" required></div>
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Precio mensual (S/) *</label><input type="number" name="precio" id="editPrecioPlan" class="form-input" required step="0.01" min="1"></div>
        <div class="form-group"><label class="form-label">Días de acceso</label><input type="number" name="dias_acceso" id="editDiasPlan" class="form-input" min="1"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Límite de empresas</label>
        <input type="number" name="limite_empresas" id="editLimitePlan" class="form-input" min="1">
      </div>
      <div class="form-group"><label class="form-label">Descripción</label><input type="text" name="descripcion" id="editDescPlan" class="form-input"></div>
      <div class="form-group"><label class="form-label">Estado</label>
        <select name="activo" id="editActivoPlan" class="form-select">
          <option value="1">Activo</option>
          <option value="0">Inactivo</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditar')">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Eliminar -->
<div class="modal-overlay" id="modalEliminar" onclick="if(event.target===this)cerrarModal('modalEliminar')">
  <div class="modal" style="max-width:400px">
    <div style="text-align:center;margin-bottom:16px"><div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:26px">⚠️</div></div>
    <div class="modal-title" style="text-align:center">¿Eliminar plan?</div>
    <div class="modal-sub" style="text-align:center" id="eliminarPlanNombre"></div>
    <form method="POST">
      <input type="hidden" name="action" value="eliminar">
      <input type="hidden" name="plan_id" id="eliminarPlanId">
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEliminar')">Cancelar</button>
        <button type="submit" class="btn btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModal(id)  { document.getElementById(id).classList.add('open'); }
function cerrarModal(id) { document.getElementById(id).classList.remove('open'); }
function editarPlan(p) {
  document.getElementById('editPlanId').value     = p.id;
  document.getElementById('editNombrePlan').value = p.nombre;
  document.getElementById('editPrecioPlan').value = p.precio;
  document.getElementById('editDiasPlan').value   = p.dias_acceso;
  document.getElementById('editLimitePlan').value = p.limite_empresas;
  document.getElementById('editDescPlan').value   = p.descripcion || '';
  document.getElementById('editActivoPlan').value = p.activo;
  abrirModal('modalEditar');
}
function eliminarPlan(id, nombre) {
  document.getElementById('eliminarPlanId').value   = id;
  document.getElementById('eliminarPlanNombre').textContent = nombre;
  abrirModal('modalEliminar');
}
</script>
</body>
</html>
