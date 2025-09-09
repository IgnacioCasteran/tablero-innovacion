<?php
function db() {
  static $cn = null;
  if ($cn !== null) return $cn;

  // === 1) Cargar variables desde .env.local o .env si existen ===
  $env   = [];
  $root  = __DIR__;
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

  // === 2) Helper para tomar en orden: .env -> getenv -> default ===
  $get = function($key, $default = null) use ($env) {
    if (array_key_exists($key, $env)) return $env[$key];
    $v = getenv($key);
    return ($v !== false && $v !== null) ? $v : $default;
  };

  // === 3) Variables de conexión ===
  $host     = $get('DB_HOST',     '127.0.0.1');
  $port     = (int)$get('DB_PORT', 3306);
  $database = $get('DB_DATABASE', 'informes_pj');
  $username = $get('DB_USERNAME', 'root');
  $password = $get('DB_PASSWORD', '');
  $charset  = $get('DB_CHARSET',  'utf8mb4');
  $appEnv   = strtolower((string)$get('APP_ENV', 'prod'));

  // Mostrar errores solo en local
  if ($appEnv === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
  } else {
    ini_set('display_errors', '0');
  }

  // Desactivar warnings automáticos de mysqli; nosotros logueamos
  if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
  }

  // === 4) Conectar con logging claro ===
  $cn = @new mysqli($host, $username, $password, $database, $port);
  if ($cn->connect_errno) {
    $msg = sprintf(
      '[DB] connect_error host=%s port=%d db=%s errno=%d error=%s',
      $host, $port, $database, $cn->connect_errno, $cn->connect_error
    );
    // Log a error_log y a logs/app.log
    error_log($msg);
    @file_put_contents($root . '/logs/app.log', date('c') . " $msg\n", FILE_APPEND);

    throw new Exception($msg);
  }

  // === 5) Charset con manejo de error ===
  if (!$cn->set_charset($charset)) {
    $msg = "[DB] set_charset($charset) failed: " . $cn->error;
    error_log($msg);
    @file_put_contents($root . '/logs/app.log', date('c') . " $msg\n", FILE_APPEND);
    // No lanzamos excepción por charset; dejamos trazado y seguimos
  }

  return $cn;
}
