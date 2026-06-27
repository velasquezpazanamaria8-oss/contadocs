<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('cliente');
$user = Auth::usuario();
$doc_id = trim($_GET['id'] ?? '');
if (!$doc_id) redirect('/cliente/documentos.php');

$doc = Database::fetch(
    "SELECT * FROM documentos WHERE id = ? AND empresa_id = ?",
    [$doc_id, $user['empresa_id']]
);
if (!$doc) { http_response_code(404); echo 'Documento no encontrado.'; exit; }

// Registrar descarga
Database::query(
    "INSERT INTO descargas_log (id, documento_id, empresa_id) VALUES (?, ?, ?)",
    [uuid(), $doc['id'], $user['empresa_id']]
);

$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$filepath = $doc_root . '/uploads/' . $doc['storage_path'];

if (!file_exists($filepath)) {
    http_response_code(404);
    echo 'Archivo no disponible.';
    exit;
}

$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$mime_map = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];
$mime = $mime_map[$ext] ?? 'application/octet-stream';

$nombre_original = $doc['nombre'] ?? 'documento';
$nombre_sin_ext  = pathinfo($nombre_original, PATHINFO_FILENAME);
$nombre_descarga = $nombre_sin_ext . '.' . $ext;

if (ob_get_level()) ob_end_clean();

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($nombre_descarga) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

readfile($filepath);
exit;
