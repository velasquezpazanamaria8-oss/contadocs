<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');
$user   = Auth::usuario();
$doc_id = trim($_GET['id'] ?? '');
if (!$doc_id) redirect('/contador/documentos.php');

// Verificar que el doc pertenece a un cliente de este contador
$doc = Database::fetch(
    "SELECT d.* FROM documentos d
     JOIN empresas_cliente ec ON d.empresa_id=ec.id
     WHERE d.id=? AND ec.estudio_id=?",
    [$doc_id, $user['estudio_id']]
);
if (!$doc) { http_response_code(404); echo 'Documento no encontrado'; exit; }

$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$filepath = $doc_root . '/uploads/' . $doc['storage_path'];

if (!file_exists($filepath)) {
    http_response_code(404);
    echo 'Archivo no disponible en el servidor. Ruta: ' . $filepath;
    exit;
}

$ext  = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$mime = match($ext) {
    'pdf'  => 'application/pdf',
    'png'  => 'image/png',
    'jpg','jpeg' => 'image/jpeg',
    default => 'application/octet-stream'
};

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($doc['nombre']) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-store');
readfile($filepath);
exit;
