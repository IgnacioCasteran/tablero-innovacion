<?php
$dir = realpath(__DIR__ . '/../uploads/proyectos');
header('Content-Type: text/plain; charset=utf-8');
echo "Base: $dir\n";
if (!$dir || !is_dir($dir)) { echo "No existe la carpeta.\n"; exit; }
foreach (scandir($dir) as $f) {
  if ($f === '.' || $f === '..') continue;
  echo (is_file("$dir/$f") ? 'FILE ' : 'DIR  ') . $f . "\n";
}
