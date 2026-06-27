<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Auth.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $u): void { header("Location: $u"); exit; }
function uuid(): string { return Database::uuid(); }

function formatBytes(int $b): string {
    if ($b < 1024) return $b . ' B';
    if ($b < 1048576) return round($b/1024) . ' KB';
    return round($b/1048576, 1) . ' MB';
}

function fechaEs(string $f): string {
    $m = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $t = strtotime($f);
    return date('d', $t) . ' ' . $m[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function tiempoRelativo(string $f): string {
    $d = time() - strtotime($f);
    if ($d < 60)     return 'Hace un momento';
    if ($d < 3600)   return 'Hace ' . floor($d/60) . ' min';
    if ($d < 86400)  return 'Hoy ' . date('g:i A', strtotime($f));
    if ($d < 172800) return 'Ayer ' . date('g:i A', strtotime($f));
    return fechaEs($f);
}

// Obtener todos los planes desde BD (con fallback si tabla no existe)
function getPlanes(): array {
    try {
        $planes = Database::fetchAll("SELECT * FROM planes WHERE activo=1 ORDER BY precio ASC");
        if (!empty($planes)) return $planes;
    } catch (Exception $e) {}
    // Fallback con los 3 planes base
    return [
        ['id'=>'basico',      'nombre'=>'Básico',      'precio'=>49.90,  'limite_empresas'=>10,     'dias_acceso'=>30],
        ['id'=>'profesional', 'nombre'=>'Profesional', 'precio'=>99.90,  'limite_empresas'=>25,     'dias_acceso'=>30],
        ['id'=>'ilimitado',   'nombre'=>'Ilimitado',   'precio'=>200.00, 'limite_empresas'=>999999, 'dias_acceso'=>30],
    ];
}

// Obtener el límite de empresas para un plan dado su ID o nombre
function getLimite(string $plan_id): int {
    if (empty($plan_id)) return 10;
    // Buscar en BD primero
    try {
        $p = Database::fetch("SELECT limite_empresas FROM planes WHERE id=? OR nombre=? LIMIT 1", [$plan_id, $plan_id]);
        if ($p) return (int)$p['limite_empresas'];
    } catch (Exception $e) {}
    // Fallback con constantes
    return PLAN_LIMITES[$plan_id] ?? 10;
}

function uploadsPath(): string {
    return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/';
}
