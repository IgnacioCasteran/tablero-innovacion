<?php
$host     = getenv('DB_HOST') ?: 'localhost';
$port     = getenv('DB_PORT') ?: '3306';
$database = getenv('DB_DATABASE') ?: 'informes_pj';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

$conexion = new mysqli($host, $username, $password, $database, (int)$port);

if ($conexion->connect_error) {
    die("❌ Conexión fallida: " . $conexion->connect_error);
} else {
    echo "✅ Conexión exitosa a la base de datos.";
}
