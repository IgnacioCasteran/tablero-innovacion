<?php
// tools/rol-debug.php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_login();         // exige estar logueado
// NO llames enforce_route_access() acá si te lo bloquea por rol,
// o agregá este archivo al allow de enforce_route_access().

header('X-Role-Debug: 1');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$raw   = current_role_raw();
$norm  = current_role();
$email = $_SESSION['usuario'] ?? ($_SESSION['user']['email'] ?? '(sin email)');
$who   = [
  'email'            => $email,
  'role_raw'         => $raw,
  'role_normalizado' => $norm,
  'session_id'       => session_id(),
  'uri'              => ($_SERVER['REQUEST_URI'] ?? ''),
];

if (isset($_GET['json']) && $_GET['json'] === '1') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($who, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

// Respuesta HTML simple
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Debug de Rol</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial,sans-serif;margin:24px;}
    .card{border:1px solid #ddd;border-radius:10px;padding:16px;max-width:680px}
    .k{color:#555}
    code{background:#f6f8fa;padding:2px 6px;border-radius:6px}
  </style>
</head>
<body>
  <h2>🔐 Debug de Rol</h2>
  <div class="card">
    <p><span class="k">Email:</span> <code><?= htmlspecialchars($email) ?></code></p>
    <p><span class="k">Rol (raw):</span> <code><?= htmlspecialchars(var_export($raw, true)) ?></code></p>
    <p><span class="k">Rol (normalizado):</span> <code><?= htmlspecialchars((string)$norm) ?></code></p>
    <p><span class="k">Session ID:</span> <code><?= htmlspecialchars(session_id()) ?></code></p>
    <p><span class="k">URI:</span> <code><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?></code></p>
    <hr>
    <p>También podés verlo como JSON: <code>/tools/rol-debug.php?json=1</code></p>
  </div>
</body>
</html>
