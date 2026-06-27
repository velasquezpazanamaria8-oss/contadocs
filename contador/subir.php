<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');
$user    = Auth::usuario();
$estudio = Database::fetch("SELECT * FROM estudios WHERE id=?", [$user['estudio_id']]);

$empresa_id_pre = trim($_GET['empresa_id'] ?? '');
$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id    = trim($_POST['empresa_id'] ?? '');
    $cat_id    = trim($_POST['categoria_id'] ?? '');
    $periodo   = trim($_POST['periodo'] ?? '');
    $nombre    = trim($_POST['nombre'] ?? '');

    if (!$emp_id || !$cat_id || !$periodo) {
        $error = 'Selecciona empresa, categoría y período.';
    } elseif (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Debes seleccionar un archivo.';
    } elseif ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $codigos = [1=>'Archivo muy grande (límite del servidor)',2=>'Archivo muy grande',3=>'Subida incompleta',4=>'No se seleccionó archivo',6=>'Sin carpeta temporal',7=>'Error al escribir en disco'];
        $error = 'Error al subir: ' . ($codigos[$_FILES['archivo']['error']] ?? 'Error '.$_FILES['archivo']['error']);
    } else {
        $archivo = $_FILES['archivo'];
        $ext     = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['pdf','jpg','jpeg','png'];

        if (!in_array($ext, $permitidos)) {
            $error = 'Solo se permiten archivos PDF, JPG o PNG.';
        } elseif ($archivo['size'] > 15 * 1024 * 1024) {
            $error = 'El archivo no puede superar 15 MB.';
        } else {
            // Verificar que empresa pertenece al estudio
            $empresa = Database::fetch("SELECT id FROM empresas_cliente WHERE id=? AND estudio_id=?", [$emp_id, $user['estudio_id']]);
            if (!$empresa) {
                $error = 'Empresa no encontrada.';
            } else {
                // Usar DOCUMENT_ROOT para ruta absoluta correcta en Hostinger
                $doc_root   = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
                $carpeta_rel = '/uploads/' . $user['estudio_id'] . '/' . $emp_id . '/' . $periodo;
                $carpeta_abs = $doc_root . $carpeta_rel;

                if (!is_dir($carpeta_abs)) {
                    if (!mkdir($carpeta_abs, 0755, true)) {
                        $error = 'No se pudo crear la carpeta de destino. Verifica permisos de uploads/.';
                    }
                }

                if (!$error) {
                    $nombre_archivo = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo['name']);
                    $ruta_abs       = $carpeta_abs . '/' . $nombre_archivo;
                    $storage_path   = $user['estudio_id'] . '/' . $emp_id . '/' . $periodo . '/' . $nombre_archivo;

                    if (move_uploaded_file($archivo['tmp_name'], $ruta_abs)) {
                        $doc_id = uuid();
                        Database::query(
                            "INSERT INTO documentos (id,empresa_id,categoria_id,nombre,storage_path,periodo,tamanio,subido_por) VALUES (?,?,?,?,?,?,?,?)",
                            [$doc_id, $emp_id, $cat_id, $nombre ?: $archivo['name'], $storage_path, $periodo, $archivo['size'], $user['id']]
                        );
                        $mensaje = '✅ Documento "' . ($nombre ?: $archivo['name']) . '" subido correctamente para el período ' . $periodo . '.';
                        $empresa_id_pre = $emp_id; // Mantener empresa seleccionada
                    } else {
                        $error = 'No se pudo guardar el archivo. Verifica que la carpeta uploads/ tenga permisos 755.';
                    }
                }
            }
        }
    }
}

$empresas   = Database::fetchAll("SELECT id, razon_social, ruc FROM empresas_cliente WHERE estudio_id=? AND activo=1 ORDER BY razon_social", [$user['estudio_id']]);
$categorias = Database::fetchAll("SELECT id, nombre, color, color_texto FROM categorias WHERE estudio_id=? AND activo=1 ORDER BY orden, nombre", [$user['estudio_id']]);

// Últimos docs subidos (para referencia rápida)
$ultimos = Database::fetchAll(
    "SELECT d.nombre, d.periodo, d.created_at, ec.razon_social, c.nombre as cat_nombre
     FROM documentos d
     JOIN empresas_cliente ec ON d.empresa_id=ec.id
     LEFT JOIN categorias c ON d.categoria_id=c.id
     WHERE ec.estudio_id=?
     ORDER BY d.created_at DESC LIMIT 5",
    [$user['estudio_id']]
);

// Períodos para el select (últimos 18 meses)
$periodos_sel = [];
for ($i = 0; $i < 18; $i++) {
    $periodos_sel[] = date('Y-m', strtotime("-$i months"));
}

$nav_active  = 'subir';
$user_rol    = 'contador';
$user_nombre = $estudio['nombre'] ?? '';
$user_plan   = $estudio['plan_id'] ?? $estudio['plan'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subir documentos — ContaDocs</title>
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
        <h1>Subir documentos</h1>
        <p>Sube PDFs e imágenes para tus clientes</p>
      </div>
      <div class="topbar-actions">
        <a href="/contador/documentos.php" class="btn btn-secondary btn-sm">Ver todos los docs</a>
      </div>
    </div>

    <div class="app-content">
      <?php if ($mensaje): ?>
      <div class="alert alert-success">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= e($mensaje) ?>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-error">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= e($error) ?>
      </div>
      <?php endif; ?>

      <?php if (empty($categorias)): ?>
      <div class="alert alert-warning">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Antes de subir documentos, <a href="/contador/categorias.php" style="font-weight:700;text-decoration:underline">crea al menos una categoría</a>.
      </div>
      <?php endif; ?>

      <?php if (empty($empresas)): ?>
      <div class="alert alert-warning">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Antes de subir documentos, <a href="/contador/clientes.php" style="font-weight:700;text-decoration:underline">agrega al menos una empresa cliente</a>.
      </div>
      <?php endif; ?>

      <div class="grid-2">
        <!-- Formulario -->
        <div class="card card-body">
          <div style="font-size:15px;font-weight:700;color:var(--gris-900);margin-bottom:18px;display:flex;align-items:center;gap:8px">
            <span style="font-size:20px">📤</span> Subir nuevo documento
          </div>

          <form method="POST" enctype="multipart/form-data" id="formSubir">
            <div class="form-group">
              <label class="form-label">Empresa cliente *</label>
              <select name="empresa_id" class="form-select" required id="selEmpresa">
                <option value="">— Selecciona una empresa —</option>
                <?php foreach ($empresas as $emp): ?>
                <option value="<?= e($emp['id']) ?>" <?= $empresa_id_pre===$emp['id']?'selected':'' ?>>
                  <?= e($emp['razon_social']) ?> · <?= e($emp['ruc']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Tipo de documento *</label>
              <?php if (empty($categorias)): ?>
              <div style="padding:10px;background:var(--gris-50);border-radius:8px;font-size:13px;color:var(--gris-400);text-align:center">
                Sin categorías — <a href="/contador/categorias.php">Crear ahora</a>
              </div>
              <?php else: ?>
              <select name="categoria_id" class="form-select" required>
                <option value="">— Selecciona categoría —</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?= e($cat['id']) ?>"><?= e($cat['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
              <div style="margin-top:6px">
                <a href="/contador/categorias.php" style="font-size:12px;color:var(--azul)">+ Agregar nueva categoría</a>
              </div>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label class="form-label">Período *</label>
              <select name="periodo" class="form-select" required>
                <option value="">— Selecciona período —</option>
                <?php foreach ($periodos_sel as $p): ?>
                <option value="<?= $p ?>" <?= $p===date('Y-m')?'selected':'' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </select>
              <div class="form-hint">Formato: YYYY-MM (ej: 2025-06)</div>
            </div>

            <div class="form-group">
              <label class="form-label">Nombre del documento <span style="font-weight:400;color:var(--gris-400)">(opcional)</span></label>
              <input type="text" name="nombre" class="form-input" placeholder="Ej: Ficha RUC actualizada junio 2025">
              <div class="form-hint">Si no pones nombre, se usa el nombre del archivo</div>
            </div>

            <div class="form-group">
              <label class="form-label">Archivo *</label>
              <label class="dropzone" id="dropzone" for="archivoInput">
                <div class="dropzone-icon">📁</div>
                <div class="dropzone-title">Arrastra el archivo aquí</div>
                <div class="dropzone-sub">PDF, JPG o PNG · máx. 15 MB</div>
                <div style="margin-top:12px">
                  <span class="btn btn-secondary btn-sm">Seleccionar archivo</span>
                </div>
              </label>
              <input type="file" id="archivoInput" name="archivo" accept=".pdf,.jpg,.jpeg,.png" style="display:none" required>

              <div id="filePreview" class="file-list" style="display:none">
                <div class="file-item">
                  <span style="font-size:22px">📄</span>
                  <div style="flex:1;min-width:0">
                    <div class="file-item-name" id="fileName"></div>
                    <div class="file-item-size" id="fileSize"></div>
                  </div>
                  <button type="button" onclick="quitarArchivo()" style="background:none;border:none;cursor:pointer;color:var(--gris-400);font-size:18px">×</button>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-full" id="btnSubir" <?= (empty($categorias)||empty($empresas))?'disabled':'' ?>>
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
              </svg>
              <span id="btnTexto">Subir documento</span>
            </button>
          </form>
        </div>

        <!-- Panel derecho -->
        <div style="display:flex;flex-direction:column;gap:16px">

          <!-- Últimos documentos subidos -->
          <div class="card">
            <div class="card-header">
              <div class="card-title">Últimos subidos</div>
              <a href="/contador/documentos.php" style="font-size:12px;color:var(--azul)">Ver todos →</a>
            </div>
            <?php if (empty($ultimos)): ?>
            <div class="card-body" style="text-align:center;color:var(--gris-400);font-size:13px">Aún no has subido documentos</div>
            <?php else: ?>
            <div>
              <?php foreach ($ultimos as $u): ?>
              <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--gris-100)">
                <span style="font-size:20px;flex-shrink:0">📄</span>
                <div style="flex:1;min-width:0">
                  <div style="font-size:13px;font-weight:500;color:var(--gris-900);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($u['nombre']) ?></div>
                  <div style="font-size:11px;color:var(--gris-400)"><?= e($u['razon_social']) ?> · <?= e($u['periodo']) ?></div>
                </div>
                <div style="font-size:11px;color:var(--gris-400);flex-shrink:0"><?= tiempoRelativo($u['created_at']) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Tips -->
          <div class="card card-body" style="background:var(--azul-light);border-color:#bfdbfe">
            <div style="font-size:14px;font-weight:700;color:var(--azul);margin-bottom:10px">💡 Tips para subir</div>
            <div style="display:flex;flex-direction:column;gap:8px">
              <?php foreach ([
                'Los PDFs son el formato preferido para documentos contables',
                'El nombre del período debe coincidir con el mes declarado (ej: 2025-06)',
                'Puedes subir múltiples documentos de una misma empresa uno por uno',
                'El cliente recibe acceso inmediato tras la subida',
              ] as $tip): ?>
              <div style="display:flex;gap:8px;align-items:flex-start">
                <span style="color:var(--azul);flex-shrink:0">→</span>
                <span style="font-size:12px;color:var(--gris-600)"><?= $tip ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const input    = document.getElementById('archivoInput');
const dropzone = document.getElementById('dropzone');
const preview  = document.getElementById('filePreview');
const btnSubir = document.getElementById('btnSubir');
const btnTexto = document.getElementById('btnTexto');

input.addEventListener('change', mostrarArchivo);

dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone.addEventListener('drop', e => {
  e.preventDefault();
  dropzone.classList.remove('dragover');
  if (e.dataTransfer.files[0]) {
    const dt = new DataTransfer();
    dt.items.add(e.dataTransfer.files[0]);
    input.files = dt.files;
    mostrarArchivo();
  }
});

function mostrarArchivo() {
  const f = input.files[0];
  if (!f) return;
  document.getElementById('fileName').textContent = f.name;
  document.getElementById('fileSize').textContent = formatBytes(f.size);
  preview.style.display = 'flex';
  dropzone.style.display = 'none';
}

function quitarArchivo() {
  input.value = '';
  preview.style.display = 'none';
  dropzone.style.display = 'block';
}

function formatBytes(b) {
  if (b < 1024) return b + ' B';
  if (b < 1048576) return (b/1024).toFixed(0) + ' KB';
  return (b/1048576).toFixed(1) + ' MB';
}

document.getElementById('formSubir').addEventListener('submit', function() {
  btnSubir.disabled = true;
  btnTexto.textContent = 'Subiendo...';
});
</script>
</body>
</html>
