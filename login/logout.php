<?php
// /login/logout.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Regenero primero para evitar fijación de sesión y que no haya warning
session_regenerate_id(true);

// Limpio variables y cookie
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// Destruyo la sesión
session_destroy();

// Redirijo al login correcto
header('Location: /login/login.html');
exit;
