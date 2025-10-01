<?php
// login/login.php
session_start();
require_once __DIR__ . '/../conexion.php';

try {
    $cn = db(); // usa la misma conexión (DB tabin)
} catch (Throwable $e) {
    http_response_code(500);
    die('Error de conexión');
}

$email    = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header("Location: ./login.html?error=1");
    exit();
}

$stmt = $cn->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id, $nombre, $mail, $hash, $rol);

$ok = false;
if ($stmt->fetch() && password_verify($password, $hash)) {
    $ok = true;
}
$stmt->close();

if ($ok) {
    // seguridad: evita fijación de sesión
    session_regenerate_id(true);

    // variables que ya usa el sistema + rol
    $_SESSION['usuario'] = $mail;                 // el check actual mira 'usuario'
    $_SESSION['nombre']  = $nombre ?? '';
    $_SESSION['user_id'] = (int)$id;
    $_SESSION['rol']     = $rol ?: 'secretaria';  // 'secretaria' | 'coordinador' | 'stj'

    header("Location: ../index.php");             // dejé tu redirección original
    exit();
}

// credenciales inválidas
// (pequeño delay para desmotivar fuerza bruta)
usleep(300000);
header("Location: ./login.html?error=1");
exit();



