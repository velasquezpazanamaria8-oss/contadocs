<?php
require_once __DIR__ . '/bootstrap.php';
if (Auth::estaLogueado()) {
    $rutas = ['superadmin'=>'/admin/dashboard.php','contador'=>'/contador/clientes.php','cliente'=>'/cliente/documentos.php'];
    redirect($rutas[Auth::rol()] ?? '/login.php');
}
redirect('/login.php');
