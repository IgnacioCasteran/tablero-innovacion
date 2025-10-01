<?php
// login/login.php
session_start();
require_once __DIR__ . '/../conexion.php';

try {
  $cn = db(); // usa la conexión centralizada (lee .env)
} catch (Throwable $e) {
  header('Location: /login/login.html?error=db');
  exit;
}

// 1) Tomar datos del form
$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($email === '' || $pass === '') {
  header('Location: /login/login.html?error=campos');
  exit;
}

// 2) Buscar usuario (ajusta nombres si hace falta)
$stmt = $cn->prepare('SELECT id, nombre, email, password FROM usuarios WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
  header('Location: /login/login.html?error=cred');
  exit;
}

// 3) Verificar contraseña
$hash = $user['password'] ?? '';
$ok = false;

// Si está hasheada con bcrypt:
if (strlen($hash) && preg_match('/^\$2y\$/', $hash)) {
  $ok = password_verify($pass, $hash);
} else {
  // Si por ahora es texto plano:
  $ok = ($pass === $hash);
}

if (!$ok) {
  header('Location: /login/login.html?error=cred');
  exit;
}

// 4) Login OK → sesión y redirección a la app
session_regenerate_id(true);
$_SESSION['usuario_id'] = (int)$user['id'];
$_SESSION['usuario']    = $user['nombre'] ?? $user['email'];

header('Location: /index.php');
exit;



