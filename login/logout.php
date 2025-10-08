// /login/logout.php
<?php
session_start();

// vaciar variables y cookie de sesión
$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_regenerate_id(true);

// siempre al login correcto
header('Location: /login/login.html');
exit;