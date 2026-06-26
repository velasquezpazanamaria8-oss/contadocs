<?php
require_once __DIR__ . '/bootstrap.php';
if (Auth::estaLogueado()) {
    $rutas = ['superadmin'=>'/admin/dashboard.php','contador'=>'/contador/clientes.php','cliente'=>'/cliente/documentos.php'];
    redirect($rutas[Auth::rol()] ?? '/login.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(strtolower($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    if (!$email || !$pass) { $error = 'Completa todos los campos.'; }
    else {
        $u = Database::fetch(
            "SELECT u.*, e.estado as estudio_estado FROM usuarios u LEFT JOIN estudios e ON u.estudio_id=e.id WHERE u.email=? AND u.activo=1",
            [$email]
        );
        if (!$u || !Auth::verificarPassword($pass, $u['password'])) $error = 'Correo o contraseña incorrectos.';
        elseif ($u['estudio_id'] && $u['estudio_estado'] === 'vencido')    $error = 'Tu acceso está vencido. Contacta a tu contador.';
        elseif ($u['estudio_id'] && $u['estudio_estado'] === 'suspendido') $error = 'Cuenta suspendida. Contacta a soporte.';
        else {
            Auth::iniciarSesion($u);
            if ($u['primer_login']) redirect('/cambiar-password.php');
            $rutas = ['superadmin'=>'/admin/dashboard.php','contador'=>'/contador/clientes.php','cliente'=>'/cliente/documentos.php'];
            redirect($rutas[$u['rol']] ?? '/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ingresar — ContaDocs</title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <div class="login-logo-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <h1>Conta<span>Docs</span></h1>
      <p>Portal de documentos contables</p>
    </div>
    <div class="login-card">
      <?php if ($error): ?>
      <div class="login-alert">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= e($error) ?>
      </div>
      <?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Correo electrónico</label>
          <div class="input-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <input type="email" name="email" class="form-input" placeholder="tucorreo@email.com" value="<?= e($_POST['email']??'') ?>" required autofocus>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Contraseña</label>
          <div class="input-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <input type="password" name="password" id="pass" class="form-input" placeholder="Tu contraseña" required>
          </div>
        </div>
        <button type="submit" class="login-btn">Ingresar al sistema →</button>
      </form>
    </div>
    <p class="login-footer">¿Problemas? Contacta a tu contador o estudio contable.</p>
  </div>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
