<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('superadmin');

$stats = Database::fetch("SELECT COUNT(*) as total, SUM(estado='activo') as activos, SUM(estado='vencido') as vencidos, SUM(estado='suspendido') as suspendidos FROM estudios");
$empresas_total = Database::fetch("SELECT COUNT(*) as n FROM empresas_cliente")['n'];
$docs_total     = Database::fetch("SELECT COUNT(*) as n FROM documentos")['n'];
$descargas_total= Database::fetch("SELECT COUNT(*) as n FROM descargas_log")['n'];

$planes_activos = Database::fetchAll("SELECT p.nombre, p.precio, COUNT(e.id) as cantidad FROM estudios e JOIN planes p ON e.plan_id=p.id WHERE e.estado='activo' GROUP BY p.id ORDER BY p.precio") ?: [];
$ingresos = 0;
foreach ($planes_activos as $p) $ingresos += $p['precio'] * $p['cantidad'];

$estudios = Database::fetchAll(
    "SELECT e.*, p.nombre as plan_nombre, p.precio as plan_precio,
            (SELECT COUNT(*) FROM empresas_cliente WHERE estudio_id=e.id) as total_empresas,
            DATEDIFF(e.vence_en, NOW()) as dias_rest
     FROM estudios e LEFT JOIN planes p ON e.plan_id=p.id
     ORDER BY e.created_at DESC LIMIT 8"
);

$por_vencer = Database::fetch("SELECT COUNT(*) as n FROM estudios WHERE estado='activo' AND vence_en BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)")['n'];

$nav_active='dashboard'; $user_rol='superadmin'; $user_nombre='Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — ContaDocs Admin</title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Dashboard</h1>
        <p>Resumen general del sistema · <?= fechaEs(date('Y-m-d H:i:s')) ?></p>
      </div>
      <div class="topbar-actions">
        <?php if ($por_vencer > 0): ?>
        <a href="/admin/estudios.php?estado=activo" class="badge badge-amber badge-none" style="font-size:12px;padding:6px 12px">
          ⚠️ <?= $por_vencer ?> vencen esta semana
        </a>
        <?php endif; ?>
        <a href="/admin/estudios.php" class="btn btn-primary btn-sm">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Nuevo estudio
        </a>
      </div>
    </div>

    <div class="app-content">
      <!-- Métricas principales -->
      <div class="metrics-grid" style="grid-template-columns:repeat(4,1fr)">
        <div class="metric-card blue">
          <div class="metric-top">
            <div><div class="metric-label">Estudios activos</div><div class="metric-value"><?= $stats['activos'] ?></div></div>
            <div class="metric-icon blue"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg></div>
          </div>
          <div class="metric-sub"><?= $stats['total'] ?> total registrados</div>
        </div>
        <div class="metric-card green">
          <div class="metric-top">
            <div><div class="metric-label">Ingreso mensual</div><div class="metric-value" style="font-size:22px">S/ <?= number_format($ingresos,2) ?></div></div>
            <div class="metric-icon green"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          </div>
          <div class="metric-sub up">S/ <?= number_format($ingresos*12,0) ?> proyectado año</div>
        </div>
        <div class="metric-card purple">
          <div class="metric-top">
            <div><div class="metric-label">Empresas cliente</div><div class="metric-value"><?= $empresas_total ?></div></div>
            <div class="metric-icon purple"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
          </div>
          <div class="metric-sub"><?= $docs_total ?> documentos · <?= $descargas_total ?> descargas</div>
        </div>
        <div class="metric-card <?= $stats['vencidos']>0?'red':'amber' ?>">
          <div class="metric-top">
            <div><div class="metric-label">Vencidos / Suspendidos</div><div class="metric-value" style="color:<?= $stats['vencidos']>0?'var(--rojo)':'' ?>"><?= $stats['vencidos'] ?> / <?= $stats['suspendidos'] ?></div></div>
            <div class="metric-icon <?= $stats['vencidos']>0?'red':'amber' ?>"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
          </div>
          <div class="metric-sub <?= $stats['vencidos']>0?'down':'' ?>"><?= $stats['vencidos']>0?'Requieren cobro':'Todo al día ✓' ?></div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 320px;gap:20px">
        <!-- Tabla estudios recientes -->
        <div class="card">
          <div class="card-header">
            <div><div class="card-title">Estudios recientes</div><div class="card-sub">Últimos 8 registrados</div></div>
            <a href="/admin/estudios.php" class="btn btn-secondary btn-sm">Ver todos</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Estudio</th><th>Plan</th><th>Empresas</th><th>Vence</th><th>Estado</th></tr></thead>
              <tbody>
                <?php foreach ($estudios as $e):
                  $sc=['activo'=>'badge-green','vencido'=>'badge-red','suspendido'=>'badge-amber'];
                ?>
                <tr>
                  <td>
                    <div class="fw"><?= e($e['nombre']) ?></div>
                    <div style="font-size:11px;color:var(--gris-400)"><?= e($e['email_admin']) ?></div>
                  </td>
                  <td><span class="badge badge-blue badge-none"><?= e($e['plan_nombre']??'—') ?></span></td>
                  <td style="font-weight:600"><?= $e['total_empresas'] ?></td>
                  <td style="font-size:12px">
                    <?php if ($e['vence_en']): ?>
                    <span style="color:<?= $e['dias_rest']<0?'var(--rojo)':($e['dias_rest']<=5?'var(--amber)':'var(--gris-500)') ?>">
                      <?= fechaEs($e['vence_en']) ?>
                    </span>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                  <td><span class="badge <?= $sc[$e['estado']]??'badge-gray' ?>"><?= e($e['estado']) ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Panel lateral -->
        <div style="display:flex;flex-direction:column;gap:16px">
          <!-- Por plan -->
          <div class="card card-body">
            <div class="card-title" style="margin-bottom:14px">Distribución por plan</div>
            <?php
            $colores = ['badge-gray','badge-blue','badge-purple','badge-green'];
            $ci = 0;
            foreach ($planes_activos as $p):
            ?>
            <div style="margin-bottom:12px">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
                <span style="font-size:13px;font-weight:500;color:var(--gris-700)"><?= e($p['nombre']) ?></span>
                <span style="font-size:13px;font-weight:700;color:var(--gris-900)"><?= $p['cantidad'] ?> estudios</span>
              </div>
              <div class="progress-track">
                <?php $max_cant = max(array_column($planes_activos,'cantidad') ?: [1]); ?>
                <div class="progress-fill" style="width:<?= $max_cant>0?round(($p['cantidad']/$max_cant)*100):0 ?>%"></div>
              </div>
              <div style="font-size:11px;color:var(--gris-400);margin-top:3px">S/ <?= number_format($p['precio']*$p['cantidad'],2) ?>/mes</div>
            </div>
            <?php $ci++; endforeach; ?>
            <?php if (empty($planes_activos)): ?>
            <div style="text-align:center;color:var(--gris-400);font-size:13px;padding:20px 0">Sin datos</div>
            <?php endif; ?>
          </div>

          <!-- Accesos rápidos -->
          <div class="card card-body">
            <div class="card-title" style="margin-bottom:12px">Accesos rápidos</div>
            <div style="display:flex;flex-direction:column;gap:8px">
              <a href="/admin/estudios.php" class="btn btn-secondary w-full" style="justify-content:flex-start">🏢 Gestionar estudios</a>
              <a href="/admin/planes.php"   class="btn btn-secondary w-full" style="justify-content:flex-start">⭐ Gestionar planes</a>
              <a href="/admin/pagos.php"    class="btn btn-secondary w-full" style="justify-content:flex-start">💰 Control de pagos</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
