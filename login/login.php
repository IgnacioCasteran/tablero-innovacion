<?php
session_start();
$conexion = new mysqli("localhost", "root", "", "informes_pj");

if ($conexion->connect_error) {
  die("Error de conexión: " . $conexion->connect_error);
}

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
  $usuario = $resultado->fetch_assoc();
  if (password_verify($password, $usuario['password'])) {
    $_SESSION['usuario'] = $usuario['nombre'];
    header("Location: ../index.php");
    exit();
  }
}

echo "<script>window.location.href='../login/login.html?error=1';</script>";


