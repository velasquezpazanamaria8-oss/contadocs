<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('superadmin');

$user = Auth::usuario();

// Métricas
$total     = Database::fetch("SELECT COUNT(*) as n FROM estudios")['n'];
$activos   = Database::fetch("SELECT COUNT(*) as n FROM estudios WHERE estado='activo'")['n'];
$vencidos  = Database::fetch("SELECT COUNT(*) as n FROM estudios WHERE estado='vencido'")['n'];
$empresas  = Database::fetch("SELECT COUNT(*) as n FROM empresas_cliente")['n'];

// Calcular ingresos según plan
$ingresos_data = Database::fetchAll("SELECT plan, COUNT(*) as n FROM estudios WHERE estado='activo' GROUP BY plan");
$ingresos = 0;
$precios = PLAN_PRECIOS;
foreach ($ingresos_data as $row) {
    $ingresos += ($precios[$row['plan']] ?? 0) * $row['n'];
}

// Lista de estudios
$estudios = Database::fetchAll(
    "SELECT e.*, (SELECT COUNT(*) FROM empresas_cliente WHERE estudio_id = e.id) as total_empresas
     FROM estudios e ORDER BY e.created_at DESC"
);

// Procesar nuevo estudio
$modal_resultado = null;
$modal_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_estudio') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $ruc         = trim($_POST['ruc'] ?? '');
    $email_admin = strtolower(trim($_POST['email_admin'] ?? ''));
    $plan        = $_POST['plan'] ?? 'basico';

    if (!$nombre || !$ruc || !$email_admin) {
        $modal_error = 'Completa todos los campos.';
    } else {
        $passTemp = Auth::generarPasswordTemporal();
        $passHash = Auth::hashPassword($passTemp);
        $vence    = date('Y-m-d H:i:s', strtotime('+1 month'));
        $id       = uuid();

        Database::query(
            "INSERT INTO estudios (id, nombre, ruc, email_admin, plan, estado, vence_en) VALUES (?,?,?,?,?,?,?)",
            [$id, $nombre, $ruc, $email_admin, $plan, 'activo', $vence]
        );
        $uid = uuid();
        Database::query(
            "INSERT INTO usuarios (id, email, password, rol, nombre, primer_login, estudio_id) VALUES (?,?,?,?,?,?,?)",
            [$uid, $email_admin, $passHash, 'contador', $nombre, 1, $id]
        );

        $modal_resultado = ['email' => $email_admin, 'pass' => $passTemp, 'nombre' => $nombre];
        // Recargar estudios
        $estudios = Database::fetchAll(
            "SELECT e.*, (SELECT COUNT(*) FROM empresas_cliente WHERE estudio_id = e.id) as total_empresas
             FROM estudios e ORDER BY e.created_at DESC"
        );
    }
}

$nav_active = 'dashboard';
$user_rol   = 'superadmin';
$user_nombre= 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — ContaDocs Admin</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>

  <div class="app-main">
    <!-- Topbar -->
    <div class="topbar">
      <div>
        <div class="topbar-title">Dashboard</div>
        <div class="topbar-sub">Resumen general del sistema</div>
      </div>
      <div class="topbar-actions">
        <button class="btn btn-primary btn-sm" onclick="abrirModal()">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="15" height="15">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Nuevo estudio
        </button>
      </div>
    </div>

    <div class="app-content">
      <!-- Métricas -->
      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-icon" style="background:#ecfdf5;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <div>
            <div class="metric-label">Estudios activos</div>
            <div class="metric-value"><?= $activos ?></div>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#eff6ff;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div class="metric-label">Ingreso mensual</div>
            <div class="metric-value">S/ <?= number_format($ingresos, 2) ?></div>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#f5f3ff;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#7c3aed" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div>
            <div class="metric-label">Empresas cliente</div>
            <div class="metric-value"><?= $empresas ?></div>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:<?= $vencidos > 0 ? '#fef2f2' : '#f3f4f6' ?>;">
            <svg fill="none" viewBox="0 0 24 24" stroke="<?= $vencidos > 0 ? '#dc2626' : '#9ca3af' ?>" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div>
            <div class="metric-label">Vencidos</div>
            <div class="metric-value" style="color:<?= $vencidos > 0 ? '#dc2626' : 'inherit' ?>"><?= $vencidos ?></div>
            <div class="metric-sub" style="color:<?= $vencidos > 0 ? '#dc2626' : '' ?>"><?= $vencidos > 0 ? 'Requieren cobro' : 'Todo al día' ?></div>
          </div>
        </div>
      </div>

      <!-- Tabla estudios -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Estudios registrados</span>
          <span class="text-muted" style="font-size:12px"><?= count($estudios) ?> total</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Estudio</th><th>RUC</th><th>Plan</th><th>Empresas</th><th>Vence</th><th>Estado</th><th></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($estudios)): ?>
              <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--gris-400);">No hay estudios aún. Crea el primero.</td></tr>
              <?php else: foreach ($estudios as $e): ?>
              <tr>
                <td class="strong">
                  <?= e($e['nombre']) ?>
                  <div style="font-size:11px;color:var(--gris-400)"><?= e($e['email_admin']) ?></div>
                </td>
                <td class="mono"><?= e($e['ruc']) ?></td>
                <td>
                  <?php
                    $pc = ['basico'=>'badge-gray','profesional'=>'badge-blue','ilimitado'=>'badge-purple'];
                    $pn = PLAN_NOMBRES;
                  ?>
                  <span class="badge <?= $pc[$e['plan']] ?? 'badge-gray' ?>"><?= $pn[$e['plan']] ?? $e['plan'] ?></span>
                </td>
                <td><?= $e['total_empresas'] ?></td>
                <td style="font-size:12px;color:var(--gris-400)">
                  <?= $e['vence_en'] ? fechaEs($e['vence_en']) : '—' ?>
                </td>
                <td>
                  <?php
                    $sc = ['activo'=>'badge-green','vencido'=>'badge-red','suspendido'=>'badge-amber'];
                  ?>
                  <span class="badge <?= $sc[$e['estado']] ?? 'badge-gray' ?>"><?= e($e['estado']) ?></span>
                </td>
                <td>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle_estado">
                    <input type="hidden" name="estudio_id" value="<?= e($e['id']) ?>">
                    <button type="submit" class="btn btn-ghost btn-sm" style="font-size:11px">
                      <?= $e['estado'] === 'activo' ? 'Suspender' : 'Activar' ?>
                    </button>
                  </form>
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

<!-- Modal nuevo estudio -->
<div class="modal-overlay <?= ($modal_resultado || $modal_error) ? 'open' : '' ?>" id="modalOverlay" onclick="if(event.target===this)cerrarModal()">
  <div class="modal">
    <?php if ($modal_resultado): ?>
      <div style="text-align:center;margin-bottom:16px">
        <div style="width:48px;height:48px;background:#ecfdf5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:22px;color:#059669">✓</div>
      </div>
      <div class="modal-title">Estudio creado exitosamente</div>
      <div class="modal-sub">Envía estas credenciales por WhatsApp al contador:</div>
      <div style="background:var(--gris-50);border:1px solid var(--gris-200);border-radius:10px;padding:16px;font-family:monospace;font-size:13px;line-height:2">
        <div><span style="color:var(--gris-400);font-family:sans-serif">Email:</span> <?= e($modal_resultado['email']) ?></div>
        <div><span style="color:var(--gris-400);font-family:sans-serif">Clave:</span> <strong><?= e($modal_resultado['pass']) ?></strong></div>
        <div><span style="color:var(--gris-400);font-family:sans-serif">Web:</span> <?= APP_URL ?>/login.php</div>
      </div>
      <button class="btn btn-primary w-full" style="justify-content:center;margin-top:16px" onclick="cerrarModal()">Listo</button>
    <?php else: ?>
      <div class="modal-title">Nuevo estudio contable</div>
      <div class="modal-sub">Se creará el acceso automáticamente para el contador.</div>
      <?php if ($modal_error): ?>
      <div class="alert alert-error"><?= e($modal_error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <input type="hidden" name="action" value="crear_estudio">
        <div class="form-group">
          <label class="form-label">Nombre del estudio</label>
          <input type="text" name="nombre" class="form-input" placeholder="Estudio Rodríguez y Asoc." required>
        </div>
        <div class="form-group">
          <label class="form-label">RUC</label>
          <input type="text" name="ruc" class="form-input" placeholder="20512345678" required maxlength="11">
        </div>
        <div class="form-group">
          <label class="form-label">Email del contador</label>
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
          <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear y activar</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
function abrirModal()  { document.getElementById('modalOverlay').classList.add('open'); }
function cerrarModal() { document.getElementById('modalOverlay').classList.remove('open'); }
</script>
</body>
</html>
