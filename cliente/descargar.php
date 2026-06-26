<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('cliente');
$user   = Auth::usuario();
$doc_id = trim($_GET['id'] ?? '');
if (!$doc_id) redirect('/cliente/documentos.php');

$doc = Database::fetch(
    "SELECT * FROM documentos WHERE id = ? AND empresa_id = ?",
    [$doc_id, $user['empresa_id']]
);
if (!$doc) { http_response_code(404); echo 'Documento no encontrado'; exit; }

// Registrar descarga
$log_id = uuid();
Database::query(
    "INSERT INTO descargas_log (id, documento_id, empresa_id) VALUES (?,?,?)",
    [$log_id, $doc['id'], $user['empresa_id']]
);

// Servir archivo
$filepath = UPLOADS_PATH . ltrim($doc['storage_path'], '/uploads/');
if (!file_exists($filepath)) { http_response_code(404); echo 'Archivo no disponible'; exit; }

$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$mime = match($ext) {
    'pdf'  => 'application/pdf',
    'png'  => 'image/png',
    'jpg', 'jpeg' => 'image/jpeg',
    default => 'application/octet-stream'
};

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($doc['nombre']) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-store');
readfile($filepath);
exit;
