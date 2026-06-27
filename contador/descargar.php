<?php
require_once __DIR__ . '/../bootstrap.php';
Auth::requerirRol('contador');
$user = Auth::usuario();
$doc_id = trim($_GET['id'] ?? '');
if (!$doc_id) redirect('/contador/documentos.php');

$doc = Database::fetch(
    "SELECT d.* FROM documentos d
     JOIN empresas_cliente ec ON d.empresa_id = ec.id
     WHERE d.id = ? AND ec.estudio_id = ?",
    [$doc_id, $user['estudio_id']]
);
if (!$doc) { http_response_code(404); echo 'Documento no encontrado.'; exit; }

$doc_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$filepath = $doc_root . '/uploads/' . $doc['storage_path'];

if (!file_exists($filepath)) {
    http_response_code(404);
    echo 'Archivo no disponible. Ruta: ' . htmlspecialchars($filepath);
    exit;
}

// Detectar MIME real del archivo
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

// Nombre limpio para descarga
$nombre_original = $doc['nombre'] ?? 'documento';
// Asegurar que el nombre tenga extensión correcta
$nombre_sin_ext = pathinfo($nombre_original, PATHINFO_FILENAME);
$nombre_descarga = $nombre_sin_ext . '.' . $ext;

// Limpiar cualquier output previo
if (ob_get_level()) ob_end_clean();

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . rawurlencode($nombre_descarga) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

readfile($filepath);
exit;
