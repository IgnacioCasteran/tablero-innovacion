<?php
function db() {
  static $cn = null;
  if ($cn !== null) return $cn;

  // 1) Cargar variables desde .env.local o .env si existen
  $env = [];
  $root = __DIR__;
  $paths = [
    $root . '/.env.local',   // desarrollo
    $root . '/.env',         // prod
  ];
  foreach ($paths as $p) {
    if (is_file($p)) {
      $parsed = @parse_ini_file($p, false, INI_SCANNER_RAW);
      if (is_array($parsed)) { $env = array_merge($env, $parsed); }
    }
  }

  // 2) Helper para tomar en orden: .env -> getenv -> default
  $get = function($key, $default = null) use ($env) {
    if (array_key_exists($key, $env)) return $env[$key];
    $v = getenv($key);
    return ($v !== false && $v !== null) ? $v : $default;
  };

  // 3) Variables
  $host     = $get('DB_HOST', 'localhost');
  $port     = (int)$get('DB_PORT', 3306);
  $database = $get('DB_DATABASE', 'informes_pj');
  $username = $get('DB_USERNAME', 'root');
  $password = $get('DB_PASSWORD', '');
  $charset  = $get('DB_CHARSET',  'utf8mb4');
  $appEnv   = strtolower((string)$get('APP_ENV', 'prod'));

  if ($appEnv === 'local') { ini_set('display_errors', 1); error_reporting(E_ALL); }

  // 4) Conectar
  $cn = @new mysqli($host, $username, $password, $database, $port);
  if ($cn->connect_error) {
    throw new Exception('DB connect error: ' . $cn->connect_error);
  }
  $cn->set_charset($charset);

  return $cn;
}
