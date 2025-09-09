<?php
require_once __DIR__ . '/../conexion.php';
header('Content-Type: text/plain; charset=utf-8');

try {
  $cn = db();
  $res = $cn->query('SELECT 1');
  if ($res) {
    echo "OK\n";
    exit;
  }
  http_response_code(500);
  echo "ERROR: SELECT 1 failed\n";
} catch (Throwable $e) {
  http_response_code(500);
  echo "ERROR: " . $e->getMessage() . "\n";
}
