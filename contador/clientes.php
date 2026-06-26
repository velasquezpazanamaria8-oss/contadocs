<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');

$user      = Auth::usuario();
$estudio   = Database::fetch("SELECT * FROM estudios WHERE id = ?", [$user['estudio_id']]);
$limite    = PLAN_LIMITES[$estudio['plan']] ?? 10;
$empresas  = Database::fetchAll(
    "SELECT ec.*, (SELECT COUNT(*) FROM documentos WHERE empresa_id = ec.id) as total_docs
     FROM empresas_cliente ec WHERE ec.estudio_id = ? ORDER BY ec.razon_social ASC",
    [$user['estudio_id']]
);
$usados    = count($empresas);

$modal_resultado = null;
$modal_error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'crear_empresa') {
    if ($usados >= $limite) {
        $modal_error = "Alcanzaste el límite de {$limite} empresas de tu plan. Actualiza tu plan para agregar más.";
    } else {
        $razon   = trim($_POST['razon_social'] ?? '');
        $ruc     = trim($_POST['ruc'] ?? '');
        $email   = strtolower(trim($_POST['email_acceso'] ?? ''));

        if (!$razon || !$ruc || !$email) {
            $modal_error = 'Completa todos los campos.';
        } else {
            $existe = Database::fetch("SELECT id FROM usuarios WHERE email = ?", [$email]);
            if ($existe) {
                $modal_error = 'Ya existe un usuario con ese correo.';
            } else {
                $passTemp = Auth::generarPasswordTemporal();
                $passHash = Auth::hashPassword($passTemp);
                $emp_id   = uuid();
                $usr_id   = uuid();

                Database::query(
                    "INSERT INTO empresas_cliente (id, estudio_id, razon_social, ruc, email_acceso) VALUES (?,?,?,?,?)",
                    [$emp_id, $user['estudio_id'], $razon, $ruc, $email]
                );
                Database::query(
                    "INSERT INTO usuarios (id, email, password, rol, nombre, primer_login, estudio_id, empresa_id) VALUES (?,?,?,?,?,?,?,?)",
                    [$usr_id, $email, $passHash, 'cliente', $razon, 1, $user['estudio_id'], $emp_id]
                );

                $modal_resultado = ['email' => $email, 'pass' => $passTemp, 'nombre' => $razon];
                $empresas = Database::fetchAll(
                    "SELECT ec.*, (SELECT COUNT(*) FROM documentos WHERE empresa_id = ec.id) as total_docs
                     FROM empresas_cliente ec WHERE ec.estudio_id = ? ORDER BY ec.razon_social ASC",
                    [$user['estudio_id']]
                );
                $usados = count($empresas);
            }
        }
    }
}

$nav_active  = 'clientes';
$user_rol    = 'contador';
$user_nombre = $estudio['nombre'] ?? $user['email'];
$user_plan   = $estudio['plan'] ?? 'basico';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis clientes — ContaDocs</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div>
        <div class="topbar-title">Mis clientes</div>
        <div class="topbar-sub"><?= e($estudio['nombre'] ?? '') ?> · Plan <?= PLAN_NOMBRES[$estudio['plan']] ?? '' ?></div>
      </div>
      <div class="topbar-actions">
        <?php if ($usados < $limite): ?>
        <button class="btn btn-primary btn-sm" onclick="abrirModal()">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Agregar empresa
        </button>
        <?php else: ?>
        <span class="badge badge-amber">Límite alcanzado (<?= $usados ?>/<?= $limite ?>)</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="app-content">
      <!-- Métricas -->
      <div class="metrics-grid" style="grid-template-columns:repeat(3,1fr)">
        <div class="metric-card">
          <div class="metric-icon" style="background:#ecfdf5;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div>
            <div class="metric-label">Empresas activas</div>
            <div class="metric-value"><?= $usados ?></div>
            <div class="metric-sub"><?= $limite - $usados ?> cupos disponibles</div>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#eff6ff;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#1d4ed8" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          </div>
          <div>
            <div class="metric-label">Total documentos</div>
            <div class="metric-value"><?= array_sum(array_column($empresas, 'total_docs')) ?></div>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#f5f3ff;">
            <svg fill="none" viewBox="0 0 24 24" stroke="#7c3aed" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
          <div>
            <div class="metric-label">Plan actual</div>
            <div class="metric-value" style="font-size:18px"><?= PLAN_NOMBRES[$estudio['plan']] ?? '' ?></div>
            <div class="metric-sub">S/ <?= number_format(PLAN_PRECIOS[$estudio['plan']] ?? 0, 2) ?>/mes</div>
          </div>
        </div>
      </div>

      <!-- Tabla clientes -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Lista de empresas cliente</span>
          <span class="text-muted" style="font-size:12px"><?= $usados ?>/<?= $limite === 999999 ? '∞' : $limite ?> empresas</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Empresa</th><th>RUC</th><th>Documentos</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
              <?php if (empty($empresas)): ?>
              <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--gris-400);">
                Aún no tienes clientes. Agrega el primero con el botón de arriba.
              </td></tr>
              <?php else: foreach ($empresas as $emp): ?>
              <tr>
                <td class="strong">
                  <?= e($emp['razon_social']) ?>
                  <div style="font-size:11px;color:var(--gris-400)"><?= e($emp['email_acceso']) ?></div>
                </td>
                <td class="mono"><?= e($emp['ruc']) ?></td>
                <td>
                  <span class="badge <?= $emp['total_docs'] > 0 ? 'badge-green' : 'badge-amber' ?>">
                    <?= $emp['total_docs'] ?> docs
                  </span>
                </td>
                <td><span class="badge <?= $emp['activo'] ? 'badge-green' : 'badge-red' ?>"><?= $emp['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                <td>
                  <div class="flex gap-2">
                    <a href="/contador/subir.php?empresa_id=<?= e($emp['id']) ?>" class="btn btn-ghost btn-sm">
                      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                      Subir
                    </a>
                    <a href="/contador/empresa.php?id=<?= e($emp['id']) ?>" class="btn btn-ghost btn-sm">Ver</a>
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

<!-- Modal -->
<div class="modal-overlay <?= ($modal_resultado || $modal_error) ? 'open' : '' ?>" id="modalOverlay" onclick="if(event.target===this)cerrarModal()">
  <div class="modal">
    <?php if ($modal_resultado): ?>
      <div style="text-align:center;margin-bottom:16px">
        <div style="width:48px;height:48px;background:#ecfdf5;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:22px;color:#059669">✓</div>
      </div>
      <div class="modal-title">Empresa creada</div>
      <div class="modal-sub">Envía estas credenciales a tu cliente:</div>
      <div style="background:var(--gris-50);border:1px solid var(--gris-200);border-radius:10px;padding:16px;font-family:monospace;font-size:13px;line-height:2.2">
        <div><span style="color:var(--gris-400);font-family:sans-serif;">Email:</span> <?= e($modal_resultado['email']) ?></div>
        <div><span style="color:var(--gris-400);font-family:sans-serif;">Clave:</span> <strong><?= e($modal_resultado['pass']) ?></strong></div>
        <div><span style="color:var(--gris-400);font-family:sans-serif;">Web:</span> <?= APP_URL ?>/login.php</div>
      </div>
      <button class="btn btn-primary w-full" style="justify-content:center;margin-top:16px" onclick="cerrarModal()">Listo</button>
    <?php else: ?>
      <div class="modal-title">Agregar empresa cliente</div>
      <div class="modal-sub">Se crea un acceso automático para este cliente.</div>
      <?php if ($modal_error): ?>
      <div class="alert alert-error"><?= e($modal_error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <input type="hidden" name="action" value="crear_empresa">
        <div class="form-group">
          <label class="form-label">Razón social</label>
          <input type="text" name="razon_social" class="form-input" placeholder="Inversiones Quispe SAC" required>
        </div>
        <div class="form-group">
          <label class="form-label">RUC</label>
          <input type="text" name="ruc" class="form-input" placeholder="20501234567" required maxlength="11">
        </div>
        <div class="form-group">
          <label class="form-label">Email del cliente</label>
          <input type="email" name="email_acceso" class="form-input" placeholder="gerencia@empresa.com" required>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear acceso</button>
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
