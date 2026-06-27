<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');
$user    = Auth::usuario();
$estudio = Database::fetch("SELECT * FROM estudios WHERE id = ?", [$user['estudio_id']]);

$descargas = Database::fetchAll(
    "SELECT dl.descargado_at, d.nombre as doc_nombre, d.periodo,
            ec.razon_social, c.nombre as cat_nombre
     FROM descargas_log dl
     INNER JOIN documentos d ON dl.documento_id = d.id
     INNER JOIN empresas_cliente ec ON dl.empresa_id = ec.id
     LEFT JOIN categorias c ON d.categoria_id = c.id
     WHERE ec.estudio_id = ?
     ORDER BY dl.descargado_at DESC
     LIMIT 200",
    [$user['estudio_id']]
);

$nav_active  = 'descargas';
$user_rol    = 'contador';
$user_nombre = $estudio['nombre'] ?? '';
$user_plan   = $estudio['plan'] ?? 'basico';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Descargas — ContaDocs</title>
  <link rel="stylesheet" href="/assets/css/app.css?v=2">
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div>
        <div class="topbar-title">Registro de descargas</div>
        <div class="topbar-sub">Historial de documentos descargados por tus clientes</div>
      </div>
    </div>
    <div class="app-content">
      <div class="card">
        <div class="card-header">
          <span class="card-title">Actividad de descargas</span>
          <span class="text-muted" style="font-size:12px"><?= count($descargas) ?> registros</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Empresa</th><th>Documento</th><th>Categoría</th><th>Período</th><th>Descargado</th></tr></thead>
            <tbody>
              <?php if (empty($descargas)): ?>
              <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--gris-400)">
                Aún no hay descargas registradas.
              </td></tr>
              <?php else: foreach ($descargas as $d): ?>
              <tr>
                <td style="font-weight:500"><?= e($d['razon_social']) ?></td>
                <td><?= e($d['doc_nombre']) ?></td>
                <td style="font-size:12px;color:var(--gris-500)"><?= e($d['cat_nombre'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--gris-500)"><?= e($d['periodo']) ?></td>
                <td style="font-size:12px;color:var(--gris-500)">
                  <?= fechaEs($d['descargado_at']) ?>
                  <span style="color:var(--gris-400)"> · <?= date('H:i', strtotime($d['descargado_at'])) ?></span>
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
</body>
</html>
