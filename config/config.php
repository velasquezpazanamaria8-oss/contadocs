<?php
// ============================================================
// ContaDocs — Configuración principal
// Edita estos valores con tus datos de Hostinger
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'u375417970_contaplay');   // hPanel > MySQL > nombre BD
define('DB_USER', 'u375417970_contaplay');       // hPanel > MySQL > usuario
define('DB_PASS', 'Eduarmarina1111****');      // hPanel > MySQL > contraseña
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'ContaDocs');
define('APP_URL', 'https://contaplay.goslam.net'); // Tu subdominio
define('APP_SECRET', 'cambia-esto-por-texto-largo-secreto-2025'); // Para sesiones

define('UPLOADS_PATH', __DIR__ . '/../uploads/');
define('UPLOADS_URL', APP_URL . '/uploads/');

// Zona horaria Perú
date_default_timezone_set('America/Lima');

// Planes y límites
define('PLAN_LIMITES', [
    'basico'       => 10,
    'profesional'  => 25,
    'ilimitado'    => 999999,
]);

define('PLAN_PRECIOS', [
    'basico'       => 49.90,
    'profesional'  => 99.90,
    'ilimitado'    => 200.00,
]);

define('PLAN_NOMBRES', [
    'basico'       => 'Básico',
    'profesional'  => 'Profesional',
    'ilimitado'    => 'Ilimitado',
]);
