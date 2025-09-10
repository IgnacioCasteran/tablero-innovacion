<?php
// Sirve archivos desde /uploads/proyectos de forma segura.

declare(strict_types=1);

$base = realpath(__DIR__ . '/../uploads/proyectos');
if (!$base || !is_dir($base)) {
    http_response_code(500);
    exit('Carpeta base no disponible.');
}

$fn = basename($_GET['f'] ?? '');
if ($fn === '') {
    http_response_code(400);
    exit('Falta el parámetro f.');
}

$path = realpath($base . DIRECTORY_SEPARATOR . $fn);
if (!$path || strpos($path, $base) !== 0 || !is_file($path)) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

$mime = function_exists('mime_content_type') ? mime_content_type($path) : null;
if (!$mime) $mime = 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($path));
header('X-Content-Type-Options: nosniff');
// Inline: PDFs se abren en el visor, DOCX normalmente se descargan
header('Content-Disposition: inline; filename="' . rawurlencode($fn) . '"');

readfile($path);
