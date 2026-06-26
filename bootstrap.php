<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Auth.php';

function e(string $str): string { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header("Location: $url"); exit; }
function uuid(): string { return Database::uuid(); }
function formatBytes(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes/1024) . ' KB';
    return round($bytes/1048576, 1) . ' MB';
}

// Fecha/hora en hora Perú (UTC-5) — corrección desde MySQL UTC
function fechaEs(string $fecha): string {
    $meses = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $ts = strtotime($fecha) - (0 * 3600); // MySQL ya corre en UTC, date_default_timezone_set maneja la conversión
    return date('d', $ts) . ' ' . $meses[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function fechaHoraEs(string $fecha): string {
    $meses = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    $ts = strtotime($fecha);
    return date('d', $ts) . ' ' . $meses[(int)date('n', $ts)] . ' ' . date('Y', $ts) . ' ' . date('g:i A', $ts);
}

function tiempoRelativo(string $fecha): string {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'Hace un momento';
    if ($diff < 3600)   return 'Hace ' . floor($diff/60) . ' min';
    if ($diff < 86400)  return 'Hoy a las ' . date('g:i A', strtotime($fecha));
    if ($diff < 172800) return 'Ayer a las ' . date('g:i A', strtotime($fecha));
    return fechaEs($fecha);
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code); header('Content-Type: application/json');
    echo json_encode($data); exit;
}

// Cargar planes desde BD de forma global
function getPlanes(): array {
    try {
        return Database::fetchAll("SELECT * FROM planes WHERE activo=1 ORDER BY precio ASC");
    } catch (Exception $e) {
        return [
            ['id'=>'basico','nombre'=>'Básico','precio'=>49.90,'limite_empresas'=>10,'dias_acceso'=>30],
            ['id'=>'profesional','nombre'=>'Profesional','precio'=>99.90,'limite_empresas'=>25,'dias_acceso'=>30],
            ['id'=>'ilimitado','nombre'=>'Ilimitado','precio'=>200.00,'limite_empresas'=>999999,'dias_acceso'=>30],
        ];
    }
}
