<?php
$conexion = new mysqli("localhost", "root", "", "informes_pj");
$conexion->set_charset("utf8");

$resultado = $conexion->query("SELECT * FROM eventos");

$eventos = [];

while ($fila = $resultado->fetch_assoc()) {
    $eventos[] = [
        'id' => $fila['id'],
        'title' => $fila['titulo'],
        'start' => $fila['fecha'],
        'extendedProps' => [
            'descripcion' => $fila['descripcion']
        ]
    ];
}

echo json_encode($eventos);

