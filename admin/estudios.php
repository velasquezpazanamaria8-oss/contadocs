<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('superadmin');

$mensaje = '';
$error   = '';
$modal_resultado = null;

// Cargar planes (con fallback si la tabla no existe aún)
try {
    $planes_lista = Database::fetchAll("SELECT * FROM planes WHERE activo=1 ORDER BY precio ASC");
} catch (Exception $e) {
    $planes_lista = [
        ['id'=>'basico',       'nombre'=>'Básico',       'precio'=>49.90,  'limite_empresas'=>10,     'dias_acceso'=>30],
        ['id'=>'profesional',  'nombre'=>'Profesional',  'precio'=>99.90,  'limite_empresas'=>25,     'dias_acceso'=>30],
        ['id'=>'ilimitado',    'nombre'=>'Ilimitado',    'precio'=>200.00, 'limite_empresas'=>999999, 'dias_acceso'=>30],
    ];
}

// Verificar si columna plan_id existe
try {
    $tiene_plan_id = Database::fetch("SHOW COLUMNS FROM estudios LIKE 'plan_id'");
} catch (Exception $e) {
    $tiene_plan_id = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear') {
        $nombre  = trim($_POST['nombre'] ?? '');
        $ruc     = trim($_POST['ruc'] ?? '');
        $email   = strtolower(trim($_POST['email_admin'] ?? ''));
        $plan_id = $_POST['plan_id'] ?? '';
        $dias    = (int)($_POST['dias'] ?? 30);

        if (!$nombre || !$ruc || !$email) {
            $error = 'Completa todos los campos obligatorios.';
        } else {
            $existe = Database::fetch("SELECT id FROM usuarios WHERE email=?", [$email]);
            if ($existe) {
                $error = 'Ya existe un usuario con ese correo.';
            } else {
                // Buscar datos del plan
                $plan_sel = null;
                foreach ($planes_lista as $p) {
                    if ($p['id'] === $plan_id) { $plan_sel = $p; break; }
                }
                $dias_plan = $plan_sel ? ($plan_sel['dias_acceso'] ?? $dias) : $dias;
                $vence     = date('Y-m-d H:i:s', strtotime("+{$dias_plan} days"));

                $passTemp = Auth::generarPasswordTemporal();
                $passHash = Auth::hashPassword($passTemp);
                $id = uuid(); $uid = uuid();

                // Insertar con o sin plan_id según si existe la columna
                if ($tiene_plan_id) {
                    // Determinar valor enum plan para compatibilidad
                    $plan_enum = 'basico';
                    if ($plan_sel) {
                        if ($plan_sel['limite_empresas'] >= 999999) $plan_enum = 'ilimitado';
                        elseif ($plan_sel['limite_empresas'] > 10)  $plan_enum = 'profesional';
                    }
                    Database::query(
                        "INSERT INTO estudios (id,nombre,ruc,email_admin,plan,plan_id,estado,vence_en) VALUES (?,?,?,?,?,?,?,?)",
                        [$id,$nombre,$ruc,$email,$plan_enum,$plan_id,'activo',$vence]
                    );
                } else {
                    $plan_enum = 'basico';
                    Database::query(
                        "INSERT INTO estudios (id,nombre,ruc,email_admin,plan,estado,vence_en) VALUES (?,?,?,?,?,?,?)",
                        [$id,$nombre,$ruc,$email,$plan_enum,'activo',$vence]
                    );
                }

                Database::query(
                    "INSERT INTO usuarios (id,email,password,rol,nombre,primer_login,estudio_id) VALUES (?,?,?,?,?,?,?)",
                    [$uid,$email,$passHash,'contador',$nombre,1,$id]
                );
                $modal_resultado = ['email'=>$email,'pass'=>$passTemp,'nombre'=>$nombre,'plan'=>$plan_sel['nombre']??''];
            }
        }
    }

    if ($action === 'editar') {
        $eid    = $_POST['estudio_id'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        $ruc    = trim($_POST['ruc'] ?? '');
        $email  = strtolower(trim($_POST['email_admin'] ?? ''));
        $plan_id= $_POST['plan_id'] ?? '';
        $estado = $_POST['estado'] ?? 'activo';
        $dias   = (int)($_POST['dias'] ?? 30);
        $vence  = date('Y-m-d H:i:s', strtotime("+{$dias} days"));

        if (!$nombre || !$ruc || !$email) {
            $error = 'Completa todos los campos.';
        } else {
            if ($tiene_plan_id) {
                Database::query(
                    "UPDATE estudios SET nombre=?,ruc=?,email_admin=?,plan_id=?,estado=?,vence_en=? WHERE id=?",
                    [$nombre,$ruc,$email,$plan_id,$estado,$vence,$eid]
                );
            } else {
                Database::query(
                    "UPDATE estudios SET nombre=?,ruc=?,email_admin=?,estado=?,vence_en=? WHERE id=?",
                    [$nombre,$ruc,$email,$estado,$vence,$eid]
                );
            }
            Database::query("UPDATE usuarios SET email=?,nombre=? WHERE estudio_id=? AND rol='contador'",[$email,$nombre,$eid]);
            $mensaje = 'Estudio actualizado correctamente.';
        }
    }

    if ($action === 'cambiar_password') {
        $eid        = $_POST['estudio_id'] ?? '';
        $pass_nueva = $_POST['pass_nueva'] ?? '';
        if (strlen($pass_nueva) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            $hash = Auth::hashPassword($pass_nueva);
            Database::query("UPDATE usuarios SET password=?,primer_login=0 WHERE estudio_id=? AND rol='contador'",[$hash,$eid]);
            $mensaje = 'Contraseña cambiada. Envíala al contador por WhatsApp.';
        }
    }

    if ($action === 'eliminar') {
        $eid = $_POST['estudio_id'] ?? '';
        $empresas_e = Database::fetchAll("SELECT id FROM empresas_cliente WHERE estudio_id=?",[$eid]);
        foreach ($empresas_e as $emp) {
            $docs_e = Database::fetchAll("SELECT id FROM documentos WHERE empresa_id=?",[$emp['id']]);
            foreach ($docs_e as $doc) {
                Database::query("DELETE FROM descargas_log WHERE documento_id=?",[$doc['id']]);
            }
            Database::query("DELETE FROM documentos WHERE empresa_id=?",[$emp['id']]);
            Database::query("DELETE FROM usuarios WHERE empresa_id=?",[$emp['id']]);
        }
        Database::query("DELETE FROM empresas_cliente WHERE estudio_id=?",[$eid]);
        Database::query("DELETE FROM categorias WHERE estudio_id=?",[$eid]);
        Database::query("DELETE FROM usuarios WHERE estudio_id=?",[$eid]);
        Database::query("DELETE FROM estudios WHERE id=?",[$eid]);
        $mensaje = 'Estudio eliminado.';
    }
}

// Filtros
$buscar   = trim($_GET['q'] ?? '');
$f_estado = $_GET['estado'] ?? '';
$f_plan   = $_GET['plan'] ?? '';
$where = "1=1"; $params = [];
if ($buscar)   { $where .= " AND (e.nombre LIKE ? OR e.ruc LIKE ? OR e.email_admin LIKE ?)"; $params = array_merge($params,["%$buscar%","%$buscar%","%$buscar%"]); }
if ($f_estado) { $where .= " AND e.estado=?"; $params[] = $f_estado; }

// Consulta estudios con plan
$estudios = Database::fetchAll(
    "SELECT e.*,
        (SELECT COUNT(*) FROM empresas_cliente WHERE estudio_id=e.id) as total_empresas,
        DATEDIFF(e.vence_en, NOW()) as dias_rest
     FROM estudios e WHERE $where ORDER BY e.created_at DESC",
    $params
);

// Enriquecer con nombre del plan
foreach ($estudios as &$est) {
    $est['plan_nombre'] = 'Sin plan';
    $est['plan_precio'] = 0;
    $est['plan_limite'] = 10;
    if ($tiene_plan_id && !empty($est['plan_id'])) {
        foreach ($planes_lista as $p) {
            if ($p['id'] === $est['plan_id']) {
                $est['plan_nombre'] = $p['nombre'];
                $est['plan_precio'] = $p['precio'];
                $est['plan_limite'] = $p['limite_empresas'];
                break;
            }
        }
    } else {
        // Fallback al enum
        $mapa = ['basico'=>'Básico','profesional'=>'Profesional','ilimitado'=>'Ilimitado'];
        $est['plan_nombre'] = $mapa[$est['plan']] ?? $est['plan'];
        foreach ($planes_lista as $p) {
            if ($p['nombre'] === $est['plan_nombre']) {
                $est['plan_precio'] = $p['precio'];
                $est['plan_limite'] = $p['limite_empresas'];
                break;
            }
        }
    }
}
unset($est);

$stats   = Database::fetch("SELECT COUNT(*) as total, SUM(estado='activo') as activos, SUM(estado='vencido') as vencidos, SUM(estado='suspendido') as suspendidos FROM estudios");
$ingresos = array_sum(array_map(fn($e) => $e['estado']==='activo' ? $e['plan_precio'] : 0, $estudios));

$nav_active='estudios'; $user_rol='superadmin'; $user_nombre='Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Estudios — ContaDocs Admin</title>
<link rel="stylesheet" href="/assets/css/app.css">
<style>
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:100;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal{background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.2);width:100%;max-width:500px;padding:28px;max-height:90vh;overflow-y:auto;transform:scale(.97);transition:transform .2s}
.modal-overlay.open .modal{transform:scale(1)}
.tabs{display:flex;border-bottom:2px solid var(--gris-100);margin-bottom:20px}
.tab-btn{padding:9px 16px;border:none;background:transparent;font-size:13px;font-weight:600;color:var(--gris-400);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s}
.tab-btn.active{color:var(--azul);border-color:var(--azul)}
.tab-content{display:none}.tab-content.active{display:block}
</style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div class="topbar-left"><h1>Estudios contables</h1><p>CRUD completo de estudios</p></div>
      <div class="topbar-actions">
        <button class="btn btn-primary btn-sm" onclick="abrirModal('modalCrear')">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Nuevo estudio
        </button>
      </div>
    </div>
    <div class="app-content">
      <?php if ($mensaje): ?><div class="alert alert-success"><?= e($mensaje) ?></div><?php endif; ?>
      <?php if ($error && !$modal_resultado): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <div class="metrics-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
        <div class="metric-card blue"><div class="metric-top"><div><div class="metric-label">Total</div><div class="metric-value"><?= $stats['total'] ?></div></div><div class="metric-icon blue"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg></div></div></div>
        <div class="metric-card green"><div class="metric-top"><div><div class="metric-label">Activos</div><div class="metric-value"><?= $stats['activos'] ?></div></div><div class="metric-icon green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div></div></div>
        <div class="metric-card <?= $stats['vencidos']>0?'red':'amber' ?>"><div class="metric-top"><div><div class="metric-label">Vencidos</div><div class="metric-value" style="color:<?= $stats['vencidos']>0?'var(--rojo)':'' ?>"><?= $stats['vencidos'] ?></div></div><div class="metric-icon <?= $stats['vencidos']>0?'red':'amber' ?>"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div></div></div>
        <div class="metric-card green"><div class="metric-top"><div><div class="metric-label">Ingreso/mes</div><div class="metric-value" style="font-size:18px">S/ <?= number_format($ingresos,2) ?></div></div><div class="metric-icon green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div></div></div>
      </div>

      <form method="GET" style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
        <div style="position:relative;flex:1;min-width:180px">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--gris-400)"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="text" name="q" value="<?= e($buscar) ?>" placeholder="Buscar nombre, RUC o email..." class="form-input" style="padding-left:32px">
        </div>
        <select name="estado" class="form-select" style="width:auto" onchange="this.form.submit()">
          <option value="">Todos los estados</option>
          <option value="activo" <?= $f_estado==='activo'?'selected':'' ?>>Activos</option>
          <option value="vencido" <?= $f_estado==='vencido'?'selected':'' ?>>Vencidos</option>
          <option value="suspendido" <?= $f_estado==='suspendido'?'selected':'' ?>>Suspendidos</option>
        </select>
        <?php if ($buscar||$f_estado): ?><a href="/admin/estudios.php" class="btn btn-secondary btn-sm">Limpiar</a><?php endif; ?>
        <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
        <span class="text-muted" style="margin-left:auto"><?= count($estudios) ?> estudios</span>
      </form>

      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Estudio</th><th>RUC</th><th>Plan</th><th>Empresas</th><th>Vence</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
              <?php if (empty($estudios)): ?>
              <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gris-400)">No hay estudios. Crea el primero.</td></tr>
              <?php else: foreach ($estudios as $e):
                $sc=['activo'=>'badge-green','vencido'=>'badge-red','suspendido'=>'badge-amber'];
              ?>
              <tr>
                <td><div class="fw"><?= e($e['nombre']) ?></div><div style="font-size:11px;color:var(--gris-400)"><?= e($e['email_admin']) ?></div></td>
                <td class="mono"><?= e($e['ruc']) ?></td>
                <td>
                  <div style="font-weight:600;font-size:13px"><?= e($e['plan_nombre']) ?></div>
                  <div style="font-size:11px;color:var(--verde-dark)">S/ <?= number_format($e['plan_precio'],2) ?>/mes</div>
                </td>
                <td style="font-weight:600"><?= $e['total_empresas'] ?> / <?= $e['plan_limite']>=999999?'∞':$e['plan_limite'] ?></td>
                <td style="font-size:12px">
                  <?php if ($e['vence_en']): ?>
                  <span style="color:<?= $e['dias_rest']<0?'var(--rojo)':($e['dias_rest']<=5?'var(--amber)':'var(--gris-500)') ?>">
                    <?= fechaEs($e['vence_en']) ?>
                    <?php if ($e['dias_rest']!==null && $e['dias_rest']<=5 && $e['dias_rest']>=0): ?>
                    <br><small style="color:var(--amber)">⚠ <?= $e['dias_rest'] ?> días</small>
                    <?php elseif ($e['dias_rest']<0): ?>
                    <br><small style="color:var(--rojo)">Venció</small>
                    <?php endif; ?>
                  </span>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td><span class="badge <?= $sc[$e['estado']]??'badge-gray' ?>"><?= e($e['estado']) ?></span></td>
                <td>
                  <div style="display:flex;gap:4px">
                    <button class="btn btn-secondary btn-sm" onclick='editarEstudio(<?= json_encode($e) ?>)'>✏️ Editar</button>
                    <button class="btn btn-secondary btn-sm" onclick='cambiarPass("<?= e($e['id']) ?>","<?= e($e['nombre']) ?>")'>🔑</button>
                    <button class="btn btn-ghost btn-sm" style="color:var(--rojo)" onclick='eliminarEstudio("<?= e($e['id']) ?>","<?= e($e['nombre']) ?>")'>🗑️</button>
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
      <div style="text-align:center;margin-bottom:16px"><div style="width:52px;height:52px;background:#ecfdf5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:24px">✓</div></div>
      <div class="modal-title">Estudio creado</div>
      <div class="modal-sub">Envía estas credenciales al contador por WhatsApp:</div>
      <div style="background:var(--gris-50);border:1px solid var(--gris-200);border-radius:10px;padding:16px;font-size:13px;line-height:2.4;margin-bottom:16px">
        <div>📧 <strong>Email:</strong> <?= e($modal_resultado['email']) ?></div>
        <div>🔑 <strong>Clave:</strong> <strong style="background:#fef9c3;padding:2px 8px;border-radius:4px;font-size:15px"><?= e($modal_resultado['pass']) ?></strong></div>
        <div>🌐 <strong>Web:</strong> <?= APP_URL ?>/login.php</div>
        <div>📋 <strong>Plan:</strong> <?= e($modal_resultado['plan']) ?></div>
      </div>
      <button class="btn btn-primary w-full" onclick="cerrarModal('modalCrear');location.href='/admin/estudios.php'">Listo</button>
    <?php else: ?>
      <div class="modal-title">Nuevo estudio contable</div>
      <div class="modal-sub">Se crea el acceso automáticamente para el contador.</div>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="action" value="crear">
        <div class="form-group"><label class="form-label">Nombre del estudio *</label><input type="text" name="nombre" class="form-input" required placeholder="Estudio Rodríguez y Asoc."></div>
        <div class="form-group"><label class="form-label">RUC *</label><input type="text" name="ruc" class="form-input" required maxlength="11" placeholder="20512345678"></div>
        <div class="form-group"><label class="form-label">Email del contador *</label><input type="email" name="email_admin" class="form-input" required placeholder="contador@email.com"></div>
        <div class="form-group">
          <label class="form-label">Plan *</label>
          <select name="plan_id" class="form-select" required>
            <option value="">— Selecciona un plan —</option>
            <?php foreach ($planes_lista as $p): ?>
            <option value="<?= e($p['id']) ?>">
              <?= e($p['nombre']) ?> — S/ <?= number_format($p['precio'],2) ?>/mes
              (<?= $p['limite_empresas']>=999999?'ilimitado':$p['limite_empresas'].' empresas' ?>)
            </option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($planes_lista)): ?>
          <div class="form-hint"><a href="/admin/planes.php">Crea tus planes primero →</a></div>
          <?php endif; ?>
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
          <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCrear')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear y activar</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<!-- MODAL EDITAR (con tabs) -->
<div class="modal-overlay" id="modalEditar" onclick="if(event.target===this)cerrarModal('modalEditar')">
  <div class="modal">
    <div class="tabs">
      <button class="tab-btn active" onclick="showTab('tabDatos')">📋 Datos</button>
      <button class="tab-btn" onclick="showTab('tabPlan')">⭐ Plan</button>
      <button class="tab-btn" onclick="showTab('tabPass')">🔑 Contraseña</button>
    </div>

    <div id="tabDatos" class="tab-content active">
      <div class="modal-title" style="margin-bottom:4px">Editar estudio</div>
      <div class="modal-sub" id="editNombreSub" style="margin-bottom:16px"></div>
      <form method="POST">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="estudio_id" id="editId">
        <input type="hidden" name="plan_id" id="editPlanIdHidden">
        <div class="form-group"><label class="form-label">Nombre *</label><input type="text" name="nombre" id="editNombre" class="form-input" required></div>
        <div class="form-group"><label class="form-label">RUC *</label><input type="text" name="ruc" id="editRuc" class="form-input" required maxlength="11"></div>
        <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email_admin" id="editEmail" class="form-input" required></div>
        <div class="form-group"><label class="form-label">Estado</label>
          <select name="estado" id="editEstado" class="form-select">
            <option value="activo">Activo</option>
            <option value="vencido">Vencido</option>
            <option value="suspendido">Suspendido</option>
          </select>
        </div>
        <input type="hidden" name="dias" value="30">
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditar')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>

    <div id="tabPlan" class="tab-content">
      <div class="modal-title" style="margin-bottom:16px">Cambiar plan y renovar</div>
      <form method="POST">
        <input type="hidden" name="action" value="editar">
        <input type="hidden" name="estudio_id" id="editIdPlan">
        <input type="hidden" name="nombre" id="editNombrePlan">
        <input type="hidden" name="ruc" id="editRucPlan">
        <input type="hidden" name="email_admin" id="editEmailPlan">
        <input type="hidden" name="estado" id="editEstadoPlan">
        <div class="form-group"><label class="form-label">Plan</label>
          <select name="plan_id" id="editPlanSel" class="form-select">
            <?php foreach ($planes_lista as $p): ?>
            <option value="<?= e($p['id']) ?>"><?= e($p['nombre']) ?> — S/ <?= number_format($p['precio'],2) ?>/mes</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Renovar por</label>
          <select name="dias" class="form-select">
            <option value="30">30 días (1 mes)</option>
            <option value="60">60 días (2 meses)</option>
            <option value="90">90 días (3 meses)</option>
            <option value="180">180 días (6 meses)</option>
            <option value="365">365 días (1 año)</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditar')">Cancelar</button>
          <button type="submit" class="btn btn-primary">Actualizar plan</button>
        </div>
      </form>
    </div>

    <div id="tabPass" class="tab-content">
      <div class="modal-title" style="margin-bottom:4px">Cambiar contraseña del contador</div>
      <div class="modal-sub" style="margin-bottom:16px">La nueva clave la envías por WhatsApp al contador.</div>
      <form method="POST">
        <input type="hidden" name="action" value="cambiar_password">
        <input type="hidden" name="estudio_id" id="editIdPass">
        <div class="form-group">
          <label class="form-label">Nueva contraseña *</label>
          <input type="text" name="pass_nueva" class="form-input" required minlength="6" placeholder="Mínimo 6 caracteres">
        </div>
        <div class="alert alert-warning">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Recuerda enviarle la nueva clave al contador.
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
    <div style="text-align:center;margin-bottom:16px"><div style="width:52px;height:52px;background:#fee2e2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:24px">⚠️</div></div>
    <div class="modal-title" style="text-align:center">¿Eliminar estudio?</div>
    <div class="modal-sub" style="text-align:center" id="eliminarNombre"></div>
    <div class="alert alert-error">Se eliminarán TODOS los datos: empresas, documentos, usuarios y descargas. Irreversible.</div>
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
function abrirModal(id)  { document.getElementById(id).classList.add('open'); }
function cerrarModal(id) { document.getElementById(id).classList.remove('open'); }

function showTab(tab) {
  document.querySelectorAll('#modalEditar .tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('#modalEditar .tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById(tab).classList.add('active');
  event.target.classList.add('active');
}

function editarEstudio(e) {
  document.getElementById('editId').value          = e.id;
  document.getElementById('editNombreSub').textContent = e.nombre;
  document.getElementById('editNombre').value      = e.nombre;
  document.getElementById('editRuc').value         = e.ruc;
  document.getElementById('editEmail').value       = e.email_admin;
  document.getElementById('editEstado').value      = e.estado;
  document.getElementById('editPlanIdHidden').value= e.plan_id || '';
  // Tab plan
  document.getElementById('editIdPlan').value      = e.id;
  document.getElementById('editNombrePlan').value  = e.nombre;
  document.getElementById('editRucPlan').value     = e.ruc;
  document.getElementById('editEmailPlan').value   = e.email_admin;
  document.getElementById('editEstadoPlan').value  = e.estado;
  if (e.plan_id) document.getElementById('editPlanSel').value = e.plan_id;
  // Tab pass
  document.getElementById('editIdPass').value      = e.id;
  // Reset tabs
  document.querySelectorAll('#modalEditar .tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('#modalEditar .tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tabDatos').classList.add('active');
  document.querySelectorAll('#modalEditar .tab-btn')[0].classList.add('active');
  abrirModal('modalEditar');
}

function cambiarPass(id, nombre) {
  document.getElementById('editIdPass').value = id;
  document.getElementById('editId').value = id;
  document.getElementById('editNombreSub').textContent = nombre;
  document.getElementById('editIdPlan').value = id;
  document.getElementById('editNombrePlan').value = nombre;
  document.getElementById('editRucPlan').value = '';
  document.getElementById('editEmailPlan').value = '';
  document.getElementById('editEstadoPlan').value = 'activo';
  document.getElementById('editPlanIdHidden').value = '';
  document.querySelectorAll('#modalEditar .tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('#modalEditar .tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tabPass').classList.add('active');
  document.querySelectorAll('#modalEditar .tab-btn')[2].classList.add('active');
  abrirModal('modalEditar');
}

function eliminarEstudio(id, nombre) {
  document.getElementById('eliminarId').value = id;
  document.getElementById('eliminarNombre').textContent = nombre;
  abrirModal('modalEliminar');
}
</script>
<script src="/assets/js/app.js"></script>
</body>
</html>
