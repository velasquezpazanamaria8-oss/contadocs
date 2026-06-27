<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');
$user    = Auth::usuario();
$estudio = Database::fetch("SELECT * FROM estudios WHERE id = ?", [$user['estudio_id']]);
$usuario = Database::fetch("SELECT * FROM usuarios WHERE id = ?", [$user['id']]);

$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass_actual = $_POST['pass_actual'] ?? '';
    $pass_nueva  = $_POST['pass_nueva'] ?? '';
    $pass_conf   = $_POST['pass_conf'] ?? '';

    if (!Auth::verificarPassword($pass_actual, $usuario['password'])) {
        $error = 'La contraseña actual es incorrecta.';
    } elseif (strlen($pass_nueva) < 8) {
        $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } elseif ($pass_nueva !== $pass_conf) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hash = Auth::hashPassword($pass_nueva);
        Database::query("UPDATE usuarios SET password = ?, primer_login = 0 WHERE id = ?", [$hash, $user['id']]);
        $mensaje = 'Contraseña actualizada correctamente.';
    }
}

$total_empresas = Database::fetch("SELECT COUNT(*) as n FROM empresas_cliente WHERE estudio_id = ?", [$user['estudio_id']])['n'];
$total_docs     = Database::fetch(
    "SELECT COUNT(*) as n FROM documentos d
     INNER JOIN empresas_cliente ec ON d.empresa_id = ec.id
     WHERE ec.estudio_id = ?", [$user['estudio_id']]
)['n'];
$limite = PLAN_LIMITES[$estudio['plan']] ?? 10;

$nav_active  = 'cuenta';
$user_rol    = 'contador';
$user_nombre = $estudio['nombre'] ?? '';
$user_plan   = $estudio['plan'] ?? 'basico';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi cuenta — ContaDocs</title>
  <link rel="stylesheet" href="/assets/css/app.css?v=2">
  <link rel="icon" type="image/png" href="/assets/img/logo_icono.svg">
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div><div class="topbar-title">Mi cuenta</div><div class="topbar-sub">Información de tu estudio y configuración</div></div>
    </div>
    <div class="app-content">
      <?php if ($mensaje): ?><div class="alert alert-success"><?= e($mensaje) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <div class="grid-2">
        <div>
          <!-- Info del estudio -->
          <div class="card card-body" style="margin-bottom:14px">
            <div class="card-title" style="margin-bottom:14px">📋 Información del estudio</div>
            <div style="display:flex;flex-direction:column;gap:12px">
              <?php foreach ([
                ['Nombre', $estudio['nombre']],
                ['RUC', $estudio['ruc']],
                ['Email', $estudio['email_admin']],
              ] as [$label, $valor]): ?>
              <div style="display:flex;justify-content:space-between;padding-bottom:10px;border-bottom:1px solid var(--gris-100)">
                <span style="font-size:12px;color:var(--gris-400)"><?= $label ?></span>
                <span style="font-size:13px;font-weight:500;color:var(--gris-900)"><?= e($valor ?? '') ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Plan -->
          <div class="card card-body">
            <div class="card-title" style="margin-bottom:14px">⭐ Plan actual</div>
            <div style="background:var(--gris-50);border-radius:10px;padding:16px;text-align:center;margin-bottom:14px">
              <div style="font-size:22px;font-weight:700;color:var(--gris-900)"><?= PLAN_NOMBRES[$estudio['plan']] ?></div>
              <div style="font-size:13px;color:var(--gris-400);margin-top:4px">S/ <?= number_format(PLAN_PRECIOS[$estudio['plan']], 2) ?> / mes</div>
              <?php if ($estudio['vence_en']): ?>
              <div style="font-size:12px;color:var(--gris-400);margin-top:6px">Vence el <?= fechaEs($estudio['vence_en']) ?></div>
              <?php endif; ?>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:6px">
              <span style="font-size:12px;color:var(--gris-500)">Empresas usadas</span>
              <span style="font-size:13px;font-weight:600"><?= $total_empresas ?> / <?= $limite === 999999 ? '∞' : $limite ?></span>
            </div>
            <div style="background:var(--gris-200);border-radius:4px;height:8px;overflow:hidden">
              <?php $pct = $limite === 999999 ? 10 : min(100, ($total_empresas / $limite) * 100); ?>
              <div style="height:100%;border-radius:4px;background:<?= $pct > 80 ? '#dc2626' : '#0ea472' ?>;width:<?= $pct ?>%"></div>
            </div>
            <div style="font-size:11px;color:var(--gris-400);margin-top:6px"><?= $total_docs ?> documentos subidos en total</div>

            <div style="margin-top:14px;padding:12px;background:#eff6ff;border-radius:8px;font-size:12px;color:#1e40af">
              <strong>¿Necesitas más capacidad?</strong><br>
              Escribe por WhatsApp para cambiar tu plan sin perder tus datos.
            </div>
          </div>
        </div>

        <!-- Cambiar contraseña -->
        <div>
          <div class="card card-body">
            <div class="card-title" style="margin-bottom:16px">🔒 Cambiar contraseña</div>
            <form method="POST">
              <div class="form-group">
                <label class="form-label">Contraseña actual</label>
                <input type="password" name="pass_actual" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label">Nueva contraseña</label>
                <input type="password" name="pass_nueva" class="form-input" required minlength="8" placeholder="Mínimo 8 caracteres">
              </div>
              <div class="form-group">
                <label class="form-label">Confirmar nueva contraseña</label>
                <input type="password" name="pass_conf" class="form-input" required>
              </div>
              <button type="submit" class="btn btn-primary w-full" style="justify-content:center">
                Actualizar contraseña
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
