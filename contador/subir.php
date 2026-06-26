<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');
$user    = Auth::usuario();
$empresa_id = trim($_GET['empresa_id'] ?? $_POST['empresa_id'] ?? '');

$empresas   = Database::fetchAll(
    "SELECT id, razon_social FROM empresas_cliente WHERE estudio_id = ? AND activo = 1 ORDER BY razon_social",
    [$user['estudio_id']]
);
$categorias = Database::fetchAll(
    "SELECT id, nombre, color, color_texto FROM categorias WHERE estudio_id = ? AND activo = 1 ORDER BY orden, nombre",
    [$user['estudio_id']]
);

$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $emp_id  = $_POST['empresa_id'] ?? '';
    $cat_id  = $_POST['categoria_id'] ?? '';
    $periodo = trim($_POST['periodo'] ?? '');
    $nombre  = trim($_POST['nombre'] ?? '');

    $empresa = Database::fetch("SELECT id FROM empresas_cliente WHERE id = ? AND estudio_id = ?", [$emp_id, $user['estudio_id']]);

    if (!$empresa || !$cat_id || !$periodo) {
        $error = 'Completa todos los campos obligatorios.';
    } elseif ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el archivo.';
    } else {
        $archivo    = $_FILES['archivo'];
        $ext        = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['pdf', 'jpg', 'jpeg', 'png'];

        if (!in_array($ext, $permitidos)) {
            $error = 'Solo se permiten archivos PDF, JPG o PNG.';
        } elseif ($archivo['size'] > 10 * 1024 * 1024) {
            $error = 'El archivo no puede superar 10 MB.';
        } else {
            $carpeta = UPLOADS_PATH . $user['estudio_id'] . '/' . $emp_id . '/' . $periodo . '/';
            if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

            $nombre_archivo = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo['name']);
            $ruta_completa  = $carpeta . $nombre_archivo;
            $storage_path   = $user['estudio_id'] . '/' . $emp_id . '/' . $periodo . '/' . $nombre_archivo;

            if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                $doc_id = uuid();
                Database::query(
                    "INSERT INTO documentos (id, empresa_id, categoria_id, nombre, storage_path, periodo, tamanio, subido_por) VALUES (?,?,?,?,?,?,?,?)",
                    [$doc_id, $emp_id, $cat_id, $nombre ?: $archivo['name'], $storage_path, $periodo, $archivo['size'], $user['id']]
                );
                $mensaje = 'Documento subido correctamente.';
            } else {
                $error = 'No se pudo guardar el archivo. Verifica los permisos de la carpeta uploads/.';
            }
        }
    }
}

$estudio    = Database::fetch("SELECT * FROM estudios WHERE id = ?", [$user['estudio_id']]);
$nav_active = 'subir'; $user_rol = 'contador';
$user_nombre= $estudio['nombre'] ?? ''; $user_plan = $estudio['plan'] ?? 'basico';
$periodos_opciones = [];
for ($i = 0; $i < 12; $i++) {
    $periodos_opciones[] = date('Y-m', strtotime("-$i months"));
}
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Subir documentos — ContaDocs</title>
<link rel="stylesheet" href="/assets/css/app.css">
</head><body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div><div class="topbar-title">Subir documentos</div><div class="topbar-sub">Sube archivos PDF o imágenes para tus clientes</div></div>
    </div>
    <div class="app-content">
      <?php if ($mensaje): ?><div class="alert alert-success"><?= e($mensaje) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <div class="grid-2">
        <div>
          <div class="card card-body">
            <div class="card-title" style="margin-bottom:16px">1. Configurar documento</div>
            <form method="POST" enctype="multipart/form-data" id="formSubir">
              <div class="form-group">
                <label class="form-label">Empresa cliente *</label>
                <select name="empresa_id" class="form-select" required>
                  <option value="">Selecciona una empresa</option>
                  <?php foreach ($empresas as $emp): ?>
                  <option value="<?= e($emp['id']) ?>" <?= $empresa_id === $emp['id'] ? 'selected' : '' ?>><?= e($emp['razon_social']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Tipo de documento *</label>
                <select name="categoria_id" class="form-select" required>
                  <option value="">Selecciona categoría</option>
                  <?php foreach ($categorias as $cat): ?>
                  <option value="<?= e($cat['id']) ?>"><?= e($cat['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
                <div style="margin-top:6px"><a href="/contador/categorias.php" style="font-size:12px;color:var(--verde)">+ Crear nueva categoría</a></div>
              </div>
              <div class="form-group">
                <label class="form-label">Período *</label>
                <select name="periodo" class="form-select" required>
                  <option value="">Selecciona período</option>
                  <?php foreach ($periodos_opciones as $p): ?>
                  <option value="<?= $p ?>"><?= $p ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Nombre del documento (opcional)</label>
                <input type="text" name="nombre" class="form-input" placeholder="Ej: Ficha RUC actualizada">
              </div>
          </div>
          <div style="margin-top:16px">
        </div>
        <div>
          <div class="card card-body">
            <div class="card-title" style="margin-bottom:16px">2. Subir archivo</div>
            <label class="dropzone" id="dropzone" for="archivoInput">
              <div class="dropzone-icon">📄</div>
              <div class="dropzone-title">Arrastra el archivo aquí</div>
              <div class="dropzone-sub">PDF, JPG o PNG · máx. 10 MB</div>
              <div style="margin-top:12px">
                <span class="btn btn-secondary btn-sm">Seleccionar archivo</span>
              </div>
            </label>
            <input type="file" id="archivoInput" name="archivo" accept=".pdf,.jpg,.jpeg,.png" style="display:none" required>
            <div id="filePreview" class="file-list" style="display:none">
              <div class="file-item">
                <span style="font-size:20px">📄</span>
                <span class="file-item-name" id="fileName"></span>
                <span class="file-item-size" id="fileSize"></span>
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="justify-content:center;margin-top:16px">
              Guardar documento
            </button>
            </form>
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
input.addEventListener('change', mostrarArchivo);
dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone.addEventListener('drop', e => {
    e.preventDefault(); dropzone.classList.remove('dragover');
    if (e.dataTransfer.files[0]) { input.files = e.dataTransfer.files; mostrarArchivo(); }
});
function mostrarArchivo() {
    const f = input.files[0];
    if (!f) return;
    document.getElementById('fileName').textContent = f.name;
    document.getElementById('fileSize').textContent = (f.size/1024).toFixed(0) + ' KB';
    preview.style.display = 'flex';
    dropzone.style.display = 'none';
}
</script>
</body></html>
