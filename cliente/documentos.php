<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('cliente');

$user    = Auth::usuario();
$empresa = Database::fetch("SELECT * FROM empresas_cliente WHERE id = ?", [$user['empresa_id']]);
if (!$empresa) { redirect('/login.php'); }

$periodo     = trim($_GET['periodo'] ?? '');
$categoria_id= trim($_GET['categoria'] ?? '');

$where  = "d.empresa_id = ?";
$params = [$user['empresa_id']];
if ($periodo)      { $where .= " AND d.periodo = ?";      $params[] = $periodo; }
if ($categoria_id) { $where .= " AND d.categoria_id = ?"; $params[] = $categoria_id; }

$documentos = Database::fetchAll(
    "SELECT d.*, c.nombre as cat_nombre, c.color as cat_color, c.color_texto as cat_color_texto,
            (SELECT COUNT(*) FROM descargas_log WHERE documento_id = d.id AND empresa_id = ?) as descargado
     FROM documentos d
     LEFT JOIN categorias c ON d.categoria_id = c.id
     WHERE $where
     ORDER BY d.created_at DESC",
    array_merge([$user['empresa_id']], $params)
);

// Periodos disponibles para el filtro
$periodos = Database::fetchAll(
    "SELECT DISTINCT periodo FROM documentos WHERE empresa_id = ? ORDER BY periodo DESC",
    [$user['empresa_id']]
);
$categorias = Database::fetchAll(
    "SELECT DISTINCT c.id, c.nombre FROM categorias c
     INNER JOIN documentos d ON d.categoria_id = c.id
     WHERE d.empresa_id = ? ORDER BY c.nombre",
    [$user['empresa_id']]
);

$nav_active  = 'documentos';
$user_rol    = 'cliente';
$user_nombre = $empresa['razon_social'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis documentos — ContaDocs</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div>
        <div class="topbar-title">Mis documentos</div>
        <div class="topbar-sub">Documentos preparados por tu contador</div>
      </div>
    </div>

    <div class="app-content">
      <!-- Filtros -->
      <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center">
        <select name="periodo" class="form-select" style="width:auto;min-width:150px" onchange="this.form.submit()">
          <option value="">Todos los períodos</option>
          <?php foreach ($periodos as $p): ?>
          <option value="<?= e($p['periodo']) ?>" <?= $periodo === $p['periodo'] ? 'selected' : '' ?>>
            <?= e($p['periodo']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <select name="categoria" class="form-select" style="width:auto;min-width:160px" onchange="this.form.submit()">
          <option value="">Todas las categorías</option>
          <?php foreach ($categorias as $cat): ?>
          <option value="<?= e($cat['id']) ?>" <?= $categoria_id === $cat['id'] ? 'selected' : '' ?>>
            <?= e($cat['nombre']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <?php if ($periodo || $categoria_id): ?>
        <a href="/cliente/documentos.php" class="btn btn-secondary btn-sm">Limpiar filtros</a>
        <?php endif; ?>
        <span class="text-muted" style="font-size:12px;margin-left:auto"><?= count($documentos) ?> documentos</span>
      </form>

      <!-- Lista de documentos -->
      <?php if (empty($documentos)): ?>
      <div style="text-align:center;padding:60px 20px">
        <div style="width:64px;height:64px;background:var(--gris-100);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px">
          <svg fill="none" viewBox="0 0 24 24" stroke="var(--gris-300)" stroke-width="1.5" width="32" height="32">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <p style="font-size:15px;font-weight:500;color:var(--gris-500)">No hay documentos disponibles</p>
        <p style="font-size:13px;color:var(--gris-400);margin-top:6px">Tu contador aún no ha subido documentos para este período.</p>
      </div>
      <?php else: ?>
      <div class="doc-grid">
        <?php foreach ($documentos as $doc): ?>
        <div class="doc-card">
          <div class="doc-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" width="22" height="22">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
          </div>
          <div class="doc-info">
            <div class="doc-name">
              <?= e($doc['nombre']) ?>
              <?php if (!$doc['descargado']): ?>
              <span class="badge badge-green" style="font-size:10px">Nuevo</span>
              <?php endif; ?>
            </div>
            <div class="doc-meta">
              <?php if ($doc['cat_nombre']): ?>
              <span style="background:<?= e($doc['cat_color'] ?? '#f3f4f6') ?>;color:<?= e($doc['cat_color_texto'] ?? '#374151') ?>;padding:2px 7px;border-radius:4px;font-size:11px">
                <?= e($doc['cat_nombre']) ?>
              </span>
              <?php endif; ?>
              <span><?= e($doc['periodo']) ?></span>
              <?php if ($doc['tamanio']): ?>
              <span><?= formatBytes((int)$doc['tamanio']) ?></span>
              <?php endif; ?>
              <span>Subido el <?= fechaEs($doc['created_at']) ?></span>
            </div>
          </div>
          <a href="/cliente/descargar.php?id=<?= e($doc['id']) ?>" class="btn btn-primary btn-sm" target="_blank">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Descargar
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
