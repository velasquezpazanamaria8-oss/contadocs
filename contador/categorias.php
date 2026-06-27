<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');
$user    = Auth::usuario();
$estudio = Database::fetch("SELECT * FROM estudios WHERE id = ?", [$user['estudio_id']]);

$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear') {
        $nombre      = trim($_POST['nombre'] ?? '');
        $color       = $_POST['color'] ?? '#E6F1FB';
        $color_texto = $_POST['color_texto'] ?? '#0C447C';
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (!$nombre) {
            $error = 'El nombre es obligatorio.';
        } else {
            $max = Database::fetch("SELECT MAX(orden) as m FROM categorias WHERE estudio_id = ?", [$user['estudio_id']]);
            $id  = uuid();
            Database::query(
                "INSERT INTO categorias (id, estudio_id, nombre, color, color_texto, descripcion, orden) VALUES (?,?,?,?,?,?,?)",
                [$id, $user['estudio_id'], $nombre, $color, $color_texto, $descripcion, ($max['m'] ?? 0) + 1]
            );
            $mensaje = "Categoría \"$nombre\" creada correctamente.";
        }
    }

    if ($action === 'toggle') {
        $cid    = $_POST['cat_id'] ?? '';
        $activo = $_POST['activo'] ?? '1';
        Database::query("UPDATE categorias SET activo = ? WHERE id = ? AND estudio_id = ?",
            [$activo === '1' ? 0 : 1, $cid, $user['estudio_id']]);
        redirect('/contador/categorias.php');
    }

    if ($action === 'eliminar') {
        $cid = $_POST['cat_id'] ?? '';
        $uso = Database::fetch("SELECT COUNT(*) as n FROM documentos WHERE categoria_id = ?", [$cid]);
        if ($uso['n'] > 0) {
            $error = 'No puedes eliminar esta categoría porque tiene documentos asociados.';
        } else {
            Database::query("DELETE FROM categorias WHERE id = ? AND estudio_id = ?", [$cid, $user['estudio_id']]);
            $mensaje = 'Categoría eliminada.';
        }
    }
}

$categorias = Database::fetchAll(
    "SELECT c.*, (SELECT COUNT(*) FROM documentos WHERE categoria_id = c.id) as total_docs
     FROM categorias c WHERE c.estudio_id = ? ORDER BY c.orden ASC, c.nombre ASC",
    [$user['estudio_id']]
);

$colores_opciones = [
    ['bg' => '#E6F1FB', 'texto' => '#0C447C', 'label' => 'Azul'],
    ['bg' => '#E1F5EE', 'texto' => '#0F6E56', 'label' => 'Verde'],
    ['bg' => '#EEEDFE', 'texto' => '#3C3489', 'label' => 'Morado'],
    ['bg' => '#FAEEDA', 'texto' => '#633806', 'label' => 'Ámbar'],
    ['bg' => '#FCEBEB', 'texto' => '#7F1D1D', 'label' => 'Rojo'],
    ['bg' => '#F1EFE8', 'texto' => '#44403C', 'label' => 'Gris'],
];

$nav_active  = 'categorias';
$user_rol    = 'contador';
$user_nombre = $estudio['nombre'] ?? '';
$user_plan   = $estudio['plan'] ?? 'basico';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categorías — ContaDocs</title>
  <link rel="stylesheet" href="/assets/css/app.css?v=2">
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../app/Views/layouts/sidebar.php'; ?>
  <div class="app-main">
    <div class="topbar">
      <div>
        <div class="topbar-title">Tipos de documentos</div>
        <div class="topbar-sub">Crea y personaliza las categorías de documentos para tus clientes</div>
      </div>
    </div>
    <div class="app-content">
      <?php if ($mensaje): ?><div class="alert alert-success"><?= e($mensaje) ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <div class="grid-2">
        <!-- Lista de categorías -->
        <div>
          <div class="card">
            <div class="card-header">
              <span class="card-title">Categorías activas</span>
              <span class="text-muted" style="font-size:12px"><?= count($categorias) ?> categorías</span>
            </div>
            <?php if (empty($categorias)): ?>
            <div class="card-body" style="text-align:center;padding:40px;color:var(--gris-400)">
              <div style="font-size:32px;margin-bottom:10px">📁</div>
              <p>No tienes categorías aún.</p>
              <p style="font-size:12px;margin-top:4px">Crea la primera desde el formulario.</p>
            </div>
            <?php else: ?>
            <div style="padding:8px">
              <?php foreach ($categorias as $cat): ?>
              <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:8px;margin-bottom:4px;<?= !$cat['activo']?'opacity:0.5':'' ?>;background:var(--gris-50);border:1px solid var(--gris-100)">
                <div style="width:36px;height:36px;border-radius:8px;background:<?= e($cat['color']) ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <span style="font-size:16px">📄</span>
                </div>
                <div style="flex:1;min-width:0">
                  <div style="font-size:13px;font-weight:500;color:var(--gris-900)"><?= e($cat['nombre']) ?></div>
                  <div style="font-size:11px;color:var(--gris-400)"><?= $cat['total_docs'] ?> documentos · <?= $cat['activo']?'<span style="color:#059669">Activo</span>':'<span style="color:var(--gris-400)">Oculto</span>' ?></div>
                </div>
                <div style="display:flex;gap:4px">
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="cat_id" value="<?= e($cat['id']) ?>">
                    <input type="hidden" name="activo" value="<?= $cat['activo'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm" title="<?= $cat['activo']?'Ocultar':'Mostrar' ?>">
                      <?= $cat['activo'] ? '👁️' : '🙈' ?>
                    </button>
                  </form>
                  <?php if ($cat['total_docs'] == 0): ?>
                  <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta categoría?')">
                    <input type="hidden" name="action" value="eliminar">
                    <input type="hidden" name="cat_id" value="<?= e($cat['id']) ?>">
                    <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--rojo)" title="Eliminar">🗑️</button>
                  </form>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Crear nueva -->
        <div>
          <div class="card card-body">
            <div class="card-title" style="margin-bottom:16px">Nueva categoría</div>
            <form method="POST">
              <input type="hidden" name="action" value="crear">
              <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-input" placeholder="Ej: Ficha RUC, Planilla, PDT 621..." required>
              </div>
              <div class="form-group">
                <label class="form-label">Color</label>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:4px">
                  <?php foreach ($colores_opciones as $i => $c): ?>
                  <label style="cursor:pointer">
                    <input type="radio" name="color_sel" value="<?= $i ?>" style="display:none"
                      onchange="document.querySelector('[name=color]').value='<?= $c['bg'] ?>';document.querySelector('[name=color_texto]').value='<?= $c['texto'] ?>'">
                    <div style="background:<?= $c['bg'] ?>;color:<?= $c['texto'] ?>;padding:8px;border-radius:8px;text-align:center;font-size:12px;font-weight:500;border:2px solid transparent" class="color-opt">
                      <?= $c['label'] ?>
                    </div>
                  </label>
                  <?php endforeach; ?>
                </div>
                <input type="hidden" name="color" value="#E6F1FB">
                <input type="hidden" name="color_texto" value="#0C447C">
              </div>
              <div class="form-group">
                <label class="form-label">Descripción (opcional)</label>
                <input type="text" name="descripcion" class="form-input" placeholder="Ej: Registro actualizado de SUNAT">
              </div>
              <button type="submit" class="btn btn-primary w-full" style="justify-content:center">
                Crear categoría
              </button>
            </form>
          </div>

          <!-- Sugerencias -->
          <div class="card card-body" style="margin-top:14px">
            <div class="card-title" style="margin-bottom:10px">💡 Categorías comunes en Perú</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
              <?php
              $sugerencias = ['Ficha RUC','Planilla de trabajadores','Boletas de pago','PDT 621','PDT 601 - PLAME','T-Registro','Constancia no adeudo','Contrato de trabajo','Liquidación CTS','Gratificación','Declaración anual IR','Comprobantes de pago'];
              foreach ($sugerencias as $s):
              ?>
              <button type="button" class="btn btn-secondary btn-sm" onclick="document.querySelector('[name=nombre]').value='<?= e($s) ?>'" style="font-size:11px">
                <?= e($s) ?>
              </button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('[name=color_sel]').forEach(r => {
  r.addEventListener('change', function() {
    document.querySelectorAll('.color-opt').forEach(o => o.style.borderColor = 'transparent');
    this.nextElementSibling.style.borderColor = '#374151';
  });
});
</script>
</body>
</html>
