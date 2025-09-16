<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login/login.html");
    exit();
}

require_once __DIR__ . '/../conexion.php';

try {
    $cn = db(); // conexión única (lee .env)
} catch (Throwable $e) {
    http_response_code(500);
    echo "Error de conexión a la base de datos.";
    exit;
}

$proyectos = [];
$reuniones = [];

// Traer datos (solo lectura, no hace falta prepared)
$result = $cn->query("SELECT * FROM reuniones_actividades ORDER BY fecha_inicio DESC");
while ($row = $result->fetch_assoc()) {
    if ($row['tipo'] === 'proyecto') {
        $proyectos[] = $row;
    } else {
        $reuniones[] = $row;
    }
}

function obtenerClaseEstado($estado)
{
    $estado = strtolower(trim($estado));
    return match ($estado) {
        'completado' => 'badge-completado',
        'en curso' => 'badge-en-curso',
        'no iniciada' => 'badge-no-iniciada',
        'bloqueada' => 'badge-bloqueada',
        'otros' => 'badge-otros',
        'proyecto chat bot' => 'badge-otros',
        'proyecto ciudadanía' => 'badge-ciudadania',
        'oj penal' => 'badge-penal',
        'oj civil' => 'badge-civil',
        'oj familia' => 'badge-familia',
        'ejecución cyq' => 'badge-cyq',
        default => 'bg-secondary'
    };
}

$cn->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reuniones y Actividades</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #f4f6f9;
        }

        h1,
        h2 {
            color: #7c1c2c;
        }

        .card {
            border-left: 5px solid #7c1c2c;
        }

        .table th {
            background-color: #eaeaea;
        }

        /* Badges (estados) */
        .badge-completado {
            background-color: #28a745 !important;
            color: #fff;
        }

        .badge-en-curso {
            background-color: #ffc107 !important;
            color: #000;
        }

        .badge-no-iniciada {
            background-color: #0d6efd !important;
            color: #fff;
        }

        .badge-bloqueada {
            background-color: #dc3545 !important;
            color: #fff;
        }

        .badge-otros {
            background-color: #6f42c1 !important;
            color: #fff;
        }

        .badge-ciudadania {
            background-color: #0dcaf0 !important;
            color: #fff;
        }

        .badge-penal {
            background-color: #ff6b6b !important;
            color: #fff;
        }

        .badge-civil {
            background-color: #28d5a0 !important;
            color: #fff;
        }

        .badge-familia {
            background-color: #ffc107 !important;
            color: #000;
        }

        .badge-cyq {
            background-color: #adb5bd !important;
            color: #000;
        }

        /* Mejoras responsive */
        .actions-wrap {
            gap: .5rem;
        }

        @media (max-width: 576px) {
            h1 {
                font-size: 1.35rem;
            }

            h2 {
                font-size: 1.15rem;
            }

            .badge {
                font-size: .85rem;
            }

            .actions-wrap {
                flex-direction: column;
            }

            .actions-wrap .btn {
                width: 100%;
            }

            .table th,
            .table td {
                font-size: .95rem;
            }

            /* Contenedores de tabla con scroll suave en móvil */
            .table-responsive {
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Tarjetas mobile */
        @media (max-width: 575.98px) {
            .ra-card {
                border: 1px solid #e9ecef;
                border-left: 5px solid #7c1c2c;
                border-radius: 12px;
                background: #fff;
                padding: 12px 12px 8px;
                margin-bottom: 12px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, .05);
            }

            .ra-title {
                font-weight: 600;
                margin-bottom: 6px;
            }

            .ra-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px 10px;
                font-size: .95rem;
            }

            .ra-row .full {
                grid-column: 1 / -1;
            }

            .ra-meta {
                color: #6c757d;
                font-size: .9rem;
            }

            .ra-actions {
                display: flex;
                gap: 8px;
                margin-top: 8px;
            }

            .badge {
                font-size: .85rem;
            }

            /* Fondo y separación para títulos principales */
            .section-title {
                padding: 10px 15px;
                margin: 0 -15px 20px;
                /* ocupa todo el ancho del contenedor */
                background: linear-gradient(to right, #fdf0eb, #f9d9d1);
                border-left: 5px solid #7c1c2c;
                font-weight: bold;
                box-shadow: 0 2px 4px rgba(0, 0, 0, .05);
            }

            /* En desktop que sea más sutil */
            @media (min-width: 768px) {
                .section-title {
                    margin: 0 0 20px 0;
                    border-left: 5px solid #7c1c2c;
                    border-radius: 4px;
                }
            }

        }
    </style>
</head>

<body>
    <div class="container py-4 py-md-5">
        <div class="d-flex justify-content-between align-items-center mb-3 mb-md-4">
            <h1 class="mb-0">Reuniones y Actividades</h1>
            <div class="d-flex actions-wrap">
                <a href="carga-reuniones-actividades.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Cargar Reunión / Actividad
                </a>
                <a href="sgc.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left-circle"></i> Volver a SGC
                </a>
            </div>
        </div>

        <!-- Proyectos y Actividades -->
        <h2 class="card-title section-title">Proyectos y Actividades</h2>

        <!-- Tabla (desktop y tablet) -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Proyecto / Tarea</th>
                        <th>Estado</th>
                        <th>Notas</th>
                        <th>Fecha de inicio</th>
                        <th>Fecha de finalización</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proyectos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['tarea']) ?></td>
                            <td>
                                <span class="badge <?= obtenerClaseEstado($p['estado']) ?>">
                                    <?= htmlspecialchars($p['estado']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($p['notas']) ?></td>
                            <td><?= $p['fecha_inicio'] ?></td>
                            <td><?= $p['fecha_fin'] ?: '—' ?></td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-warning btn-editar" data-reg='<?= json_encode($p) ?>'>✏️</button>
                                <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?= $p['id'] ?>">🗑️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tarjetas (mobile) -->
        <div class="d-md-none">
            <?php foreach ($proyectos as $p): ?>
                <div class="ra-card">
                    <div class="ra-title"><?= htmlspecialchars($p['tarea']) ?></div>
                    <div class="ra-row">
                        <div class="full">
                            <span class="badge <?= obtenerClaseEstado($p['estado']) ?>"><?= htmlspecialchars($p['estado']) ?></span>
                        </div>
                        <?php if (!empty($p['notas'])): ?>
                            <div class="full ra-meta"><strong>Notas:</strong> <?= htmlspecialchars($p['notas']) ?></div>
                        <?php endif; ?>
                        <div><strong>Inicio:</strong> <?= $p['fecha_inicio'] ?></div>
                        <div><strong>Fin:</strong> <?= $p['fecha_fin'] ?: '—' ?></div>
                    </div>
                    <div class="ra-actions">
                        <button class="btn btn-sm btn-warning btn-editar" data-reg='<?= json_encode($p) ?>'>✏️ Editar</button>
                        <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?= $p['id'] ?>">🗑️ Eliminar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>


        <!-- Reuniones -->
        <h2 class="card-title section-title">Reuniones</h2>

        <!-- Tabla (desktop y tablet) -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Fecha de inicio</th>
                        <th>Proyecto / Tarea</th>
                        <th>Estado</th>
                        <th>Asistentes</th>
                        <th>Documento Adjunto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reuniones as $r): ?>
                        <tr>
                            <td><?= $r['fecha_inicio'] ?></td>
                            <td><?= htmlspecialchars($r['tarea']) ?></td>
                            <td>
                                <span class="badge <?= obtenerClaseEstado($r['estado']) ?>">
                                    <?= htmlspecialchars($r['estado']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($r['asistentes']) ?></td>
                            <td>
                                <?php if ($r['archivo']): ?>
                                    <a href="../uploads/reuniones/<?= $r['archivo'] ?>" target="_blank" class="btn btn-sm btn-primary">Ver archivo</a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-warning btn-editar" data-reg='<?= json_encode($r) ?>'>✏️</button>
                                <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?= $r['id'] ?>">🗑️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tarjetas (mobile) -->
        <div class="d-md-none">
            <?php foreach ($reuniones as $r): ?>
                <div class="ra-card">
                    <div class="ra-title"><?= htmlspecialchars($r['tarea']) ?></div>
                    <div class="ra-row">
                        <div class="full">
                            <span class="badge <?= obtenerClaseEstado($r['estado']) ?>"><?= htmlspecialchars($r['estado']) ?></span>
                        </div>
                        <div><strong>Inicio:</strong> <?= $r['fecha_inicio'] ?></div>
                        <?php if (!empty($r['asistentes'])): ?>
                            <div class="full ra-meta"><strong>Asistentes:</strong> <?= htmlspecialchars($r['asistentes']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($r['archivo'])): ?>
                            <div class="full">
                                <a href="../uploads/reuniones/<?= $r['archivo'] ?>" target="_blank" class="btn btn-sm btn-primary">Ver archivo</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ra-actions">
                        <button class="btn btn-sm btn-warning btn-editar" data-reg='<?= json_encode($r) ?>'>✏️ Editar</button>
                        <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?= $r['id'] ?>">🗑️ Eliminar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>


        <!-- Modal edición -->
        <div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="formEditar" class="modal-content" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarLabel">Editar Reunión / Actividad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="mb-3">
                            <label>Tipo</label>
                            <select class="form-select" name="tipo" id="edit-tipo" required>
                                <option value="proyecto">Proyecto / Actividad</option>
                                <option value="reunion">Reunión</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Proyecto / Tarea</label>
                            <input type="text" class="form-control" name="tarea" id="edit-tarea" required>
                        </div>
                        <div class="mb-3">
                            <label>Estado</label>
                            <input type="text" class="form-control" name="estado" id="edit-estado">
                        </div>
                        <div class="mb-3">
                            <label>Notas</label>
                            <textarea class="form-control" name="notas" id="edit-notas"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Fecha de inicio</label>
                            <input type="date" class="form-control" name="fecha_inicio" id="edit-fecha-inicio">
                        </div>
                        <div class="mb-3">
                            <label>Fecha de finalización</label>
                            <input type="date" class="form-control" name="fecha_fin" id="edit-fecha-fin">
                        </div>
                        <div class="mb-3">
                            <label>Asistentes</label>
                            <input type="text" class="form-control" name="asistentes" id="edit-asistentes">
                        </div>
                        <div class="mb-3">
                            <label>Nuevo archivo (opcional)</label>
                            <input type="file" class="form-control" name="archivo">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Guardar cambios</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bootstrap -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.querySelectorAll(".btn-editar").forEach(btn => {
                btn.addEventListener("click", () => {
                    const data = JSON.parse(btn.dataset.reg);
                    document.getElementById('edit-id').value = data.id;
                    document.getElementById('edit-tipo').value = data.tipo;
                    document.getElementById('edit-tarea').value = data.tarea;
                    document.getElementById('edit-estado').value = data.estado || '';
                    document.getElementById('edit-notas').value = data.notas || '';
                    document.getElementById('edit-fecha-inicio').value = data.fecha_inicio || '';
                    document.getElementById('edit-fecha-fin').value = data.fecha_fin || '';
                    document.getElementById('edit-asistentes').value = data.asistentes || '';

                    const modal = new bootstrap.Modal(document.getElementById('modalEditar'));
                    modal.show();
                });
            });

            document.getElementById("formEditar").addEventListener("submit", function(e) {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);

                fetch('../api/api-reuniones.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        Swal.fire('Actualizado', data.mensaje, 'success').then(() => location.reload());
                    })
                    .catch(() => Swal.fire('Error', 'No se pudo actualizar', 'error'));
            });

            document.querySelectorAll(".btn-eliminar").forEach(btn => {
                btn.addEventListener("click", () => {
                    const id = btn.dataset.id;
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "Esta acción no se puede deshacer.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('../api/api-reuniones.php', {
                                    method: 'DELETE',
                                    body: new URLSearchParams({
                                        id
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    Swal.fire('Eliminado', data.mensaje, 'success').then(() => location.reload());
                                })
                                .catch(() => Swal.fire('Error', 'No se pudo eliminar', 'error'));
                        }
                    });
                });
            });
        </script>
</body>

</html>