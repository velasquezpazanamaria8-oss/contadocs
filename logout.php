<?php
require_once __DIR__ . '/bootstrap.php';
Auth::cerrarSesion();
redirect('/login.php');
