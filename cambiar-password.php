<?php
require_once __DIR__ . '/bootstrap.php';
if (!Auth::estaLogueado()) redirect('/login.php');
$user  = Auth::usuario();
$error = '';
$ok    = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva    = $_POST['password'] ?? '';
    $confirma = $_POST['confirma'] ?? '';
    if (strlen($nueva) < 8)          $error = 'La contraseña debe tener al menos 8 caracteres.';
    elseif ($nueva !== $confirma)    $error = 'Las contraseñas no coinciden.';
    else {
        $hash = Auth::hashPassword($nueva);
        Database::query("UPDATE usuarios SET password = ?, primer_login = 0 WHERE id = ?", [$hash, $user['id']]);
        $_SESSION['primer_login'] = false;
        $rutas = ['superadmin'=>'/admin/dashboard.php','contador'=>'/contador/clientes.php','cliente'=>'/cliente/documentos.php'];
        redirect($rutas[$user['rol']] ?? '/login.php');
    }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cambiar contraseña — ContaDocs</title><link rel="stylesheet" href="/assets/css/app.css?v=2"></head><body>
  <link rel="icon" type="image/png" href="/assets/img/logo_icono.svg">
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <div class="login-logo-icon">🔐</div>
      <h1>Crea tu contraseña</h1>
      <p>Es tu primer acceso. Elige una contraseña segura.</p>
    </div>
    <div class="login-card">
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Nueva contraseña</label>
          <input type="password" name="password" class="form-input" placeholder="Mínimo 8 caracteres" required minlength="8">
        </div>
        <div class="form-group">
          <label class="form-label">Confirmar contraseña</label>
          <input type="password" name="confirma" class="form-input" placeholder="Repite la contraseña" required>
        </div>
        <button type="submit" class="btn btn-primary btn-lg w-full" style="justify-content:center;margin-top:8px">Guardar y continuar</button>
      </form>
    </div>
  </div>
</div></body></html>
