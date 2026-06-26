<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('superadmin');

// Stats de ingresos
$estudios_activos = Database::fetchAll(
    "SELECT e.nombre, e.plan, e.email_admin, e.vence_en, e.estado,
            (SELECT COUNT(*) FROM empresas_cliente WHERE estudio_id = e.id) as empresas
     FROM estudios e WHERE e.estado = 'activo' ORDER BY e.vence_en ASC"
);

$por_vencer = Database::fetchAll(
    "SELECT nombre, email_admin, plan, vence_en,
            DATEDIFF(vence_en, NOW()) as dias_restantes
     FROM estudios WHERE estado = 'activo' AND vence_en <= DATE_ADD(NOW(), INTERVAL 7 DAY)
     ORDER BY vence_en ASC"
);

$ingresos_total = 0;
foreach ($estudios_activos as $e) {
    $ingresos_total += PLAN_PRECIOS[$e['plan']] ?? 0;
}

$por_plan = Database::fetchAll(
    "SELECT plan, COUNT(*) as cantidad FROM estudios WHERE estado='activo' GROUP BY plan"
);

$nav_active  = 'pagos';
$user_rol    = 'superadmin';
$user_nombre = 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pagos — ContaDocs Admin</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div>
        <div class="topbar-title">Control de pagos</div>
        <div class="topbar-sub">Suscripciones activas y vencimientos próximos</div>
      </div>
    </div>
    <div class="app-content">

      <!-- Ingreso mensual -->
      <div class="metrics-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
        <div class="metric-card">
          <div class="metric-icon" style="background:#ecfdf5">
            <svg fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div class="metric-label">Ingreso mensual estimado</div>
            <div class="metric-value">S/ <?= number_format($ingresos_total, 2) ?></div>
            <div class="metric-sub"><?= count($estudios_activos) ?> estudios activos</div>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#fef2f2">
            <svg fill="none" viewBox="0 0 24 24" stroke="#dc2626" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div class="metric-label">Vencen en 7 días</div>
            <div class="metric-value" style="color:<?= count($por_vencer)>0?'#dc2626':'' ?>"><?= count($por_vencer) ?></div>
            <div class="metric-sub" style="color:#dc2626"><?= count($por_vencer)>0?'Requieren cobro':'' ?></div>
          </div>
        </div>
        <div class="metric-card">
          <div class="metric-icon" style="background:#f5f3ff">
            <svg fill="none" viewBox="0 0 24 24" stroke="#7c3aed" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
          <div>
            <div class="metric-label">Ingreso anual proyectado</div>
            <div class="metric-value">S/ <?= number_format($ingresos_total * 12, 0) ?></div>
          </div>
        </div>
      </div>

      <!-- Por vencer -->
      <?php if (!empty($por_vencer)): ?>
      <div class="card" style="margin-bottom:16px;border-color:#fca5a5">
        <div class="card-header" style="background:#fef2f2">
          <span class="card-title" style="color:#dc2626">⚠️ Vencen en los próximos 7 días</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Estudio</th><th>Plan</th><th>Vence</th><th>Días restantes</th><th>Acción</th></tr></thead>
            <tbody>
              <?php foreach ($por_vencer as $e): ?>
              <tr>
                <td>
                  <div style="font-weight:500"><?= e($e['nombre']) ?></div>
                  <div style="font-size:11px;color:var(--gris-400)"><?= e($e['email_admin']) ?></div>
                </td>
                <td><span class="badge <?= ['basico'=>'badge-gray','profesional'=>'badge-blue','ilimitado'=>'badge-purple'][$e['plan']]??'badge-gray' ?>"><?= PLAN_NOMBRES[$e['plan']] ?></span></td>
                <td style="font-size:12px"><?= fechaEs($e['vence_en']) ?></td>
                <td><span class="badge badge-red"><?= $e['dias_restantes'] ?> días</span></td>
                <td>
                  <a href="https://wa.me/?text=Hola+<?= urlencode($e['nombre']) ?>+tu+suscripci%C3%B3n+ContaDocs+vence+en+<?= $e['dias_restantes'] ?>+d%C3%ADas.+Renueva+por+S%2F+<?= PLAN_PRECIOS[$e['plan']] ?>"
                     target="_blank" class="btn btn-primary btn-sm">
                    WhatsApp
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <!-- Distribución por plan -->
      <div class="card" style="margin-bottom:16px">
        <div class="card-header"><span class="card-title">Distribución por plan</span></div>
        <div class="card-body">
          <div style="display:flex;gap:24px;flex-wrap:wrap">
            <?php foreach ($por_plan as $p):
              $colores = ['basico'=>['#f3f4f6','#374151'],'profesional'=>['#eff6ff','#1e40af'],'ilimitado'=>['#f5f3ff','#5b21b6']];
              $c = $colores[$p['plan']] ?? ['#f3f4f6','#374151'];
            ?>
            <div style="flex:1;min-width:120px;background:<?= $c[0] ?>;border-radius:10px;padding:16px;text-align:center">
              <div style="font-size:28px;font-weight:700;color:<?= $c[1] ?>"><?= $p['cantidad'] ?></div>
              <div style="font-size:12px;color:<?= $c[1] ?>;margin-top:4px"><?= PLAN_NOMBRES[$p['plan']] ?></div>
              <div style="font-size:11px;color:var(--gris-400);margin-top:2px">S/ <?= number_format(PLAN_PRECIOS[$p['plan']] * $p['cantidad'], 2) ?>/mes</div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Tabla completa -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Todos los estudios activos</span>
          <span class="text-muted" style="font-size:12px"><?= count($estudios_activos) ?> estudios</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Estudio</th><th>Plan</th><th>Precio/mes</th><th>Empresas</th><th>Vence</th></tr></thead>
            <tbody>
              <?php foreach ($estudios_activos as $e): ?>
              <tr>
                <td>
                  <div style="font-weight:500"><?= e($e['nombre']) ?></div>
                  <div style="font-size:11px;color:var(--gris-400)"><?= e($e['email_admin']) ?></div>
                </td>
                <td><span class="badge <?= ['basico'=>'badge-gray','profesional'=>'badge-blue','ilimitado'=>'badge-purple'][$e['plan']]??'badge-gray' ?>"><?= PLAN_NOMBRES[$e['plan']] ?></span></td>
                <td style="font-weight:500;color:#059669">S/ <?= number_format(PLAN_PRECIOS[$e['plan']], 2) ?></td>
                <td><?= $e['empresas'] ?></td>
                <td style="font-size:12px;color:var(--gris-500)"><?= $e['vence_en'] ? fechaEs($e['vence_en']) : '—' ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!empty($estudios_activos)): ?>
              <tr style="background:var(--gris-50)">
                <td colspan="2" style="font-weight:600">TOTAL MENSUAL</td>
                <td style="font-weight:700;color:#059669;font-size:15px">S/ <?= number_format($ingresos_total, 2) ?></td>
                <td colspan="2"></td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
