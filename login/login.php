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

// ⬇️ Traemos también alcance_circ y alcance_oficina
$stmt = $cn->prepare("
    SELECT id, nombre, email, password, rol, alcance_circ, alcance_oficina
    FROM usuarios
    WHERE email = ?
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id, $nombre, $mail, $hash, $rol, $alcCirc, $alcOfi);

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

    // ⬇️ NUEVO: alcance del coordinador (o null si no aplica)
    $_SESSION['alcance_circ']   = ($alcCirc !== null && $alcCirc !== '') ? $alcCirc : null;
    $_SESSION['alcance_oficina']= ($alcOfi  !== null && $alcOfi  !== '') ? $alcOfi  : null;

    // opcional: snapshot completo para helpers que lean $_SESSION['user']
    $_SESSION['user'] = [
        'id'             => (int)$id,
        'nombre'         => $nombre ?? '',
        'email'          => $mail,
        'rol'            => $_SESSION['rol'],
        'alcance_circ'   => $_SESSION['alcance_circ'],
        'alcance_oficina'=> $_SESSION['alcance_oficina'],
    ];

    header("Location: ../index.php");
    exit();
}

// credenciales inválidas
usleep(300000);
header("Location: ./login.html?error=1");
exit();




