<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('cliente');
$user    = Auth::usuario();
$empresa = Database::fetch("SELECT * FROM empresas_cliente WHERE id = ?", [$user['empresa_id']]);

$historial = Database::fetchAll(
    "SELECT dl.descargado_at, d.nombre as doc_nombre, d.periodo,
            c.nombre as cat_nombre, c.color, c.color_texto
     FROM descargas_log dl
     INNER JOIN documentos d ON dl.documento_id = d.id
     LEFT JOIN categorias c ON d.categoria_id = c.id
     WHERE dl.empresa_id = ?
     ORDER BY dl.descargado_at DESC
     LIMIT 100",
    [$user['empresa_id']]
);

$nav_active  = 'historial';
$user_rol    = 'cliente';
$user_nombre = $empresa['razon_social'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial — ContaDocs</title>
  <link rel="stylesheet" href="/assets/css/app.css?v=2">
  <link rel="icon" type="image/png" href="/assets/img/logo_icono.svg">
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div>
        <div class="topbar-title">Historial de descargas</div>
        <div class="topbar-sub">Registro de todos los documentos que descargaste</div>
      </div>
    </div>
    <div class="app-content">
      <div class="card">
        <div class="card-header">
          <span class="card-title">Mis descargas</span>
          <span class="text-muted" style="font-size:12px"><?= count($historial) ?> registros</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Documento</th><th>Categoría</th><th>Período</th><th>Descargado el</th></tr></thead>
            <tbody>
              <?php if (empty($historial)): ?>
              <tr><td colspan="4" style="text-align:center;padding:40px;color:var(--gris-400)">
                Aún no has descargado ningún documento.
              </td></tr>
              <?php else: foreach ($historial as $h): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:18px">📄</span>
                    <span style="font-weight:500;color:var(--gris-900)"><?= e($h['doc_nombre']) ?></span>
                  </div>
                </td>
                <td>
                  <?php if ($h['cat_nombre']): ?>
                  <span style="background:<?= e($h['color']??'#f3f4f6') ?>;color:<?= e($h['color_texto']??'#374151') ?>;padding:3px 8px;border-radius:4px;font-size:11px;font-weight:500">
                    <?= e($h['cat_nombre']) ?>
                  </span>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--gris-500)"><?= e($h['periodo']) ?></td>
                <td style="font-size:12px;color:var(--gris-500)">
                  <?= fechaEs($h['descargado_at']) ?>
                  <span style="color:var(--gris-400)"> · <?= date('H:i', strtotime($h['descargado_at'])) ?></span>
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
