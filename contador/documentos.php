<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');
$user    = Auth::usuario();
$estudio = Database::fetch("SELECT * FROM estudios WHERE id=?", [$user['estudio_id']]);

// Filtros
$emp_id_filtro = trim($_GET['empresa_id'] ?? '');
$periodo_filtro = trim($_GET['periodo'] ?? '');
$buscar = trim($_GET['q'] ?? '');

// Mensaje/error de eliminación
$mensaje = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'eliminar_doc') {
    $doc_id = $_POST['doc_id'] ?? '';
    $doc = Database::fetch(
        "SELECT d.* FROM documentos d 
         JOIN empresas_cliente ec ON d.empresa_id=ec.id 
         WHERE d.id=? AND ec.estudio_id=?",
        [$doc_id, $user['estudio_id']]
    );
    if ($doc) {
        $filepath = __DIR__ . '/../../uploads/' . $doc['storage_path'];
        if (file_exists($filepath)) @unlink($filepath);
        Database::query("DELETE FROM descargas_log WHERE documento_id=?", [$doc_id]);
        Database::query("DELETE FROM documentos WHERE id=?", [$doc_id]);
        $mensaje = 'Documento eliminado correctamente.';
    }
}

// Query de documentos
$where = "ec.estudio_id=?";
$params = [$user['estudio_id']];
if ($emp_id_filtro) { $where .= " AND d.empresa_id=?"; $params[] = $emp_id_filtro; }
if ($periodo_filtro){ $where .= " AND d.periodo=?";     $params[] = $periodo_filtro; }
if ($buscar)        { $where .= " AND (d.nombre LIKE ? OR ec.razon_social LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }

$documentos = Database::fetchAll(
    "SELECT d.*, ec.razon_social, ec.ruc, c.nombre as cat_nombre, c.color, c.color_texto,
            (SELECT COUNT(*) FROM descargas_log WHERE documento_id=d.id) as total_descargas,
            (SELECT MAX(descargado_at) FROM descargas_log WHERE documento_id=d.id) as ultima_descarga
     FROM documentos d
     JOIN empresas_cliente ec ON d.empresa_id=ec.id
     LEFT JOIN categorias c ON d.categoria_id=c.id
     WHERE $where
     ORDER BY d.created_at DESC",
    $params
);

// Para los filtros desplegables
$empresas = Database::fetchAll(
    "SELECT id, razon_social FROM empresas_cliente WHERE estudio_id=? ORDER BY razon_social",
    [$user['estudio_id']]
);
$periodos = Database::fetchAll(
    "SELECT DISTINCT d.periodo FROM documentos d 
     JOIN empresas_cliente ec ON d.empresa_id=ec.id 
     WHERE ec.estudio_id=? ORDER BY d.periodo DESC",
    [$user['estudio_id']]
);

// Stats rápidas
$total_docs      = Database::fetch("SELECT COUNT(*) as n FROM documentos d JOIN empresas_cliente ec ON d.empresa_id=ec.id WHERE ec.estudio_id=?", [$user['estudio_id']])['n'];
$total_descargas = Database::fetch("SELECT COUNT(*) as n FROM descargas_log dl JOIN empresas_cliente ec ON dl.empresa_id=ec.id WHERE ec.estudio_id=?", [$user['estudio_id']])['n'];
$este_mes        = Database::fetch("SELECT COUNT(*) as n FROM documentos d JOIN empresas_cliente ec ON d.empresa_id=ec.id WHERE ec.estudio_id=? AND d.periodo=?", [$user['estudio_id'], date('Y-m')])['n'];

$nav_active='documentos'; $user_rol='contador';
$user_nombre=$estudio['nombre']??''; $user_plan=$estudio['plan_id']??$estudio['plan']??'';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Documentos — ContaDocs</title>
<link rel="stylesheet" href="/assets/css/app.css">
<style>
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:100;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal{background:#fff;border-radius:18px;box-shadow:0 24px 64px rgba(0,0,0,.2);width:100%;max-width:420px;padding:28px;transform:scale(.97);transition:transform .2s}
.modal-overlay.open .modal{transform:scale(1)}
</style>
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Documentos subidos</h1>
        <p>Todos los documentos de tus clientes · <?= $total_docs ?> total</p>
      </div>
      <div class="topbar-actions">
        <a href="/contador/subir.php" class="btn btn-primary">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="15" height="15">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
          </svg>
          Subir documento
        </a>
      </div>
    </div>

    <div class="app-content">
      <?php if ($mensaje): ?><div class="alert alert-success"><?= e($mensaje) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <!-- Stats -->
      <div class="metrics-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
        <div class="metric-card blue">
          <div class="metric-top">
            <div><div class="metric-label">Total documentos</div><div class="metric-value"><?= $total_docs ?></div></div>
            <div class="metric-icon blue">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
          </div>
          <div class="metric-sub">en todos tus clientes</div>
        </div>
        <div class="metric-card green">
          <div class="metric-top">
            <div><div class="metric-label">Subidos este mes</div><div class="metric-value"><?= $este_mes ?></div></div>
            <div class="metric-icon green">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
              </svg>
            </div>
          </div>
          <div class="metric-sub"><?= date('F Y') ?></div>
        </div>
        <div class="metric-card purple">
          <div class="metric-top">
            <div><div class="metric-label">Total descargas</div><div class="metric-value"><?= $total_descargas ?></div></div>
            <div class="metric-icon purple">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
            </div>
          </div>
          <div class="metric-sub">por todos los clientes</div>
        </div>
      </div>

      <!-- Filtros -->
      <form method="GET" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
        <div style="position:relative;flex:1;min-width:200px">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--gris-400)">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input type="text" name="q" value="<?= e($buscar) ?>" placeholder="Buscar por nombre o empresa..." class="form-input" style="padding-left:34px">
        </div>
        <select name="empresa_id" class="form-select" style="width:auto;min-width:180px" onchange="this.form.submit()">
          <option value="">Todas las empresas</option>
          <?php foreach ($empresas as $emp): ?>
          <option value="<?= e($emp['id']) ?>" <?= $emp_id_filtro===$emp['id']?'selected':'' ?>>
            <?= e($emp['razon_social']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <select name="periodo" class="form-select" style="width:auto" onchange="this.form.submit()">
          <option value="">Todos los períodos</option>
          <?php foreach ($periodos as $p): ?>
          <option value="<?= e($p['periodo']) ?>" <?= $periodo_filtro===$p['periodo']?'selected':'' ?>>
            <?= e($p['periodo']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <?php if ($buscar||$emp_id_filtro||$periodo_filtro): ?>
        <a href="/contador/documentos.php" class="btn btn-secondary btn-sm">Limpiar</a>
        <?php endif; ?>
        <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
        <span class="text-muted" style="margin-left:auto"><?= count($documentos) ?> resultados</span>
      </form>

      <!-- Tabla documentos -->
      <div class="card">
        <?php if (empty($documentos)): ?>
        <div class="empty-state">
          <div class="empty-icon">📄</div>
          <div class="empty-title">No hay documentos<?= $buscar?" para \"$buscar\"":'' ?></div>
          <div class="empty-sub">
            <?= ($emp_id_filtro||$periodo_filtro||$buscar) ? 'Prueba cambiando los filtros' : 'Sube el primer documento usando el botón de arriba' ?>
          </div>
        </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Documento</th>
                <th>Empresa cliente</th>
                <th>Categoría</th>
                <th>Período</th>
                <th>Tamaño</th>
                <th>Subido</th>
                <th>Descargas</th>
                <th>Última desc.</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($documentos as $doc): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:34px;height:34px;background:#fee2e2;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px">📄</div>
                    <div>
                      <div style="font-weight:600;color:var(--gris-900);font-size:13px"><?= e($doc['nombre']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="font-weight:500;color:var(--gris-900)"><?= e($doc['razon_social']) ?></div>
                  <div style="font-size:11px;color:var(--gris-400)"><?= e($doc['ruc']) ?></div>
                </td>
                <td>
                  <?php if ($doc['cat_nombre']): ?>
                  <span style="background:<?= e($doc['color']??'#f1f5f9') ?>;color:<?= e($doc['color_texto']??'#475569') ?>;padding:3px 8px;border-radius:5px;font-size:11px;font-weight:600">
                    <?= e($doc['cat_nombre']) ?>
                  </span>
                  <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td><span class="badge badge-blue badge-none"><?= e($doc['periodo']) ?></span></td>
                <td class="text-muted"><?= $doc['tamanio'] ? formatBytes((int)$doc['tamanio']) : '—' ?></td>
                <td class="text-muted" style="font-size:12px"><?= tiempoRelativo($doc['created_at']) ?></td>
                <td>
                  <span class="badge <?= $doc['total_descargas']>0?'badge-green':'badge-gray' ?> badge-none">
                    <?= $doc['total_descargas'] ?> <?= $doc['total_descargas']==1?'vez':'veces' ?>
                  </span>
                </td>
                <td class="text-muted" style="font-size:12px">
                  <?= $doc['ultima_descarga'] ? tiempoRelativo($doc['ultima_descarga']) : 'Nunca' ?>
                </td>
                <td>
                  <button class="btn btn-ghost btn-sm" style="color:var(--rojo)"
                    onclick='confirmarEliminar("<?= e($doc['id']) ?>","<?= e(addslashes($doc['nombre'])) ?>")'>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal confirmar eliminar -->
<div class="modal-overlay" id="modalEliminar" onclick="if(event.target===this)cerrarModal()">
  <div class="modal">
    <div style="text-align:center;margin-bottom:16px">
      <div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:26px">🗑️</div>
    </div>
    <div class="modal-title" style="text-align:center">¿Eliminar documento?</div>
    <div id="eliminarNombre" style="text-align:center;font-size:13px;color:var(--gris-500);margin:8px 0 16px;font-weight:500"></div>
    <div class="alert alert-error">
      <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      El documento se eliminará permanentemente. El cliente ya no podrá descargarlo.
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="eliminar_doc">
      <input type="hidden" name="doc_id" id="eliminarDocId">
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
        <button type="submit" class="btn btn-danger">Sí, eliminar</button>
      </div>
    </form>
  </div>
</div>

<script>
function cerrarModal() { document.getElementById('modalEliminar').classList.remove('open'); }
function confirmarEliminar(id, nombre) {
  document.getElementById('eliminarDocId').value = id;
  document.getElementById('eliminarNombre').textContent = nombre;
  document.getElementById('modalEliminar').classList.add('open');
}
</script>
</body>
</html>
