<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login/login.html");
    exit();
}

require_once __DIR__ . '/../conexion.php';
try {
    $cn = db();
} catch (Throwable $e) {
    http_response_code(500);
    echo "Error de conexión a la base de datos.";
    exit;
}

$proyectos = [];
$reuniones = [];

// Orden: fijados primero, luego últimos cargados
$result = $cn->query("SELECT * FROM reuniones_actividades ORDER BY fijado DESC, id DESC");
while ($row = $result->fetch_assoc()) {
    if ($row['tipo'] === 'reunion') {
        if (!isset($row['organismo']) || $row['organismo'] === null || $row['organismo'] === '') {
            $row['organismo'] = $row['estado'];
        }
    } else {
        if (!isset($row['organismo'])) $row['organismo'] = '';
    }

    if ($row['tipo'] === 'proyecto') $proyectos[] = $row;
    else $reuniones[] = $row;
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

        thead tr.filters th {
            background: #fafafa;
        }

        thead tr.filters .form-control,
        thead tr.filters .form-select {
            font-size: .85rem;
            padding: .15rem .4rem;
        }

        thead tr.filters .btn-clear {
            font-size: .8rem;
        }

        .badge-completado {
            background: #28a745 !important;
            color: #fff;
        }

        .badge-en-curso {
            background: #ffc107 !important;
            color: #000;
        }

        .badge-no-iniciada {
            background: #0d6efd !important;
            color: #fff;
        }

        .badge-bloqueada {
            background: #dc3545 !important;
            color: #fff;
        }

        .badge-otros {
            background: #6f42c1 !important;
            color: #fff;
        }

        .badge-ciudadania {
            background: #0dcaf0 !important;
            color: #fff;
        }

        .badge-penal {
            background: #ff6b6b !important;
            color: #fff;
        }

        .badge-civil {
            background: #28d5a0 !important;
            color: #fff;
        }

        .badge-familia {
            background: #ffc107 !important;
            color: #000;
        }

        .badge-cyq {
            background: #adb5bd !important;
            color: #000;
        }

        .fila-fijada {
            background: #fff8e1;
        }

        .fila-fijada .pin-icon {
            color: #f0ad4e !important;
        }

        .badge-prioridad {
            background: #f0ad4e;
            color: #000;
        }

        .actions-wrap {
            gap: .5rem;
        }

        @media (max-width:576px) {
            h1 {
                font-size: 1.35rem
            }

            h2 {
                font-size: 1.15rem
            }

            .badge {
                font-size: .85rem
            }

            .actions-wrap {
                flex-direction: column
            }

            .actions-wrap .btn {
                width: 100%
            }

            .table th,
            .table td {
                font-size: .95rem
            }

            .table-responsive {
                -webkit-overflow-scrolling: touch
            }
        }

        @media (max-width:575.98px) {
            .ra-card {
                border: 1px solid #e9ecef;
                border-left: 5px solid #7c1c2c;
                border-radius: 12px;
                background: #fff;
                padding: 12px 12px 8px;
                margin-bottom: 12px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, .05)
            }

            .ra-title {
                font-weight: 600;
                margin-bottom: 6px
            }

            .ra-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px 10px;
                font-size: .95rem
            }

            .ra-row .full {
                grid-column: 1 / -1
            }

            .ra-meta {
                color: #6c757d;
                font-size: .9rem
            }

            .ra-actions {
                display: flex;
                gap: 8px;
                margin-top: 8px
            }

            .badge {
                font-size: .85rem
            }

            .section-title {
                padding: 10px 15px;
                margin: 0 -15px 20px;
                background: linear-gradient(to right, #fdf0eb, #f9d9d1);
                border-left: 5px solid #7c1c2c;
                font-weight: bold;
                box-shadow: 0 2px 4px rgba(0, 0, 0, .05)
            }
        }

        .fila-fijada td {
            background: #fff3cd !important;
            border-top: 1px solid #ffe08a !important;
            border-bottom: 1px solid #ffe08a !important
        }

        .fila-fijada td:first-child {
            box-shadow: inset 8px 0 0 #f0ad4e
        }

        .fila-fijada .pin-icon {
            color: #d97706 !important;
            font-size: 1.15rem
        }

        .ra-card.fila-fijada {
            background: #fff3cd;
            border-left: 8px solid #f0ad4e;
            box-shadow: 0 0 0 2px #ffe08a inset
        }

        .ra-card.fila-fijada .pin-icon {
            color: #d97706 !important
        }

        .ra-card.fila-fijada .ra-title::before {
            content: "PRIORIDAD";
            display: inline-block;
            margin-right: .4rem;
            padding: 2px 8px;
            font-size: .75rem;
            font-weight: 700;
            background: #f0ad4e;
            color: #000;
            border-radius: 6px
        }

        @media (min-width:768px) {
            .section-title {
                margin: 0 0 20px 0;
                border-left: 5px solid #7c1c2c;
                border-radius: 4px
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

        <!-- ACTIVIDADES -->
        <h2 class="card-title section-title">ACTIVIDADES</h2>

        <div class="table-responsive d-none d-md-block">
            <table id="tbl-actividades" class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Proyecto / Tarea</th>
                        <th>Estado</th>
                        <th>Organismo</th>
                        <th>Notas</th>
                        <th>Fecha de inicio</th>
                        <th>Fecha de finalización</th>
                        <th>Acciones</th>
                    </tr>
                    <!-- FILTROS SOLO: Tarea, Estado, Organismo -->
                    <tr class="filters">
                        <th><input type="text" class="form-control form-control-sm" placeholder="Filtrar…"></th>
                        <th>
                            <select class="form-select form-select-sm">
                                <option value="">Todos</option>
                            </select>
                        </th>
                        <th>
                            <select class="form-select form-select-sm">
                                <option value="">Todos</option>
                            </select>
                        </th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th><button type="button" class="btn btn-sm btn-outline-secondary w-100 btn-clear">Limpiar</button></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proyectos as $p): ?>
                        <tr class="<?= !empty($p['fijado']) ? 'fila-fijada' : '' ?>">
                            <td><?= htmlspecialchars($p['tarea']) ?></td>
                            <td>
                                <span class="badge <?= obtenerClaseEstado($p['estado']) ?>">
                                    <?= htmlspecialchars($p['estado']) ?>
                                </span>
                            </td>
                            <td><?= $p['organismo'] ? htmlspecialchars($p['organismo']) : '—' ?></td>
                            <td><?= htmlspecialchars($p['notas']) ?></td>
                            <td><?= $p['fecha_inicio'] ?></td>
                            <td><?= $p['fecha_fin'] ?: '—' ?></td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-warning btn-pin"
                                    title="<?= !empty($p['fijado']) ? 'Quitar prioridad' : 'Fijar prioridad' ?>"
                                    data-id="<?= $p['id'] ?>"
                                    data-fijado="<?= (int)($p['fijado'] ?? 0) ?>">
                                    <i class="bi <?= !empty($p['fijado']) ? 'bi-star-fill' : 'bi-star' ?> pin-icon"></i>
                                </button>
                                <!-- NUEVO: enviar a agenda -->
                                <button class="btn btn-sm btn-outline-primary btn-agenda"
                                    title="Agregar a Agenda"
                                    data-reg='<?= json_encode($p) ?>'>
                                    <i class="bi bi-calendar-plus"></i>
                                </button>
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
                <div class="ra-card <?= !empty($p['fijado']) ? 'fila-fijada' : '' ?>">
                    <div class="ra-title">
                        <?php if (!empty($p['fijado'])): ?><span class="badge badge-prioridad me-1">PRIORIDAD</span><?php endif; ?>
                        <?= htmlspecialchars($p['tarea']) ?>
                    </div>
                    <div class="ra-row">
                        <div class="full">
                            <span class="badge <?= obtenerClaseEstado($p['estado']) ?>"><?= htmlspecialchars($p['estado']) ?></span>
                        </div>
                        <?php if (!empty($p['organismo'])): ?>
                            <div class="full ra-meta"><strong>Organismo:</strong> <?= htmlspecialchars($p['organismo']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($p['notas'])): ?>
                            <div class="full ra-meta"><strong>Notas:</strong> <?= htmlspecialchars($p['notas']) ?></div>
                        <?php endif; ?>
                        <div><strong>Inicio:</strong> <?= $p['fecha_inicio'] ?></div>
                        <div><strong>Fin:</strong> <?= $p['fecha_fin'] ?: '—' ?></div>
                    </div>
                    <div class="ra-actions">
                        <button class="btn btn-sm btn-outline-warning btn-pin"
                            title="<?= !empty($p['fijado']) ? 'Quitar prioridad' : 'Fijar prioridad' ?>"
                            data-id="<?= $p['id'] ?>"
                            data-fijado="<?= (int)($p['fijado'] ?? 0) ?>">
                            <i class="bi <?= !empty($p['fijado']) ? 'bi-star-fill' : 'bi-star' ?> pin-icon"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary btn-agenda"
                            title="Agregar a Agenda"
                            data-reg='<?= json_encode($p /* o $r */) ?>'>
                            <i class="bi bi-calendar-plus"></i>
                        </button>

                        <button class="btn btn-sm btn-warning btn-editar" data-reg='<?= json_encode($p) ?>'>✏️ Editar</button>
                        <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?= $p['id'] ?>">🗑️ Eliminar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- REUNIONES -->
        <h2 class="card-title section-title">REUNIONES</h2>

        <div class="table-responsive d-none d-md-block">
            <table id="tbl-reuniones" class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Fecha de inicio</th>
                        <th>Proyecto / Tarea</th>
                        <th>Organismo / Proyecto</th>
                        <th>Asistentes</th>
                        <th>Documento Adjunto</th>
                        <th>Acciones</th>
                    </tr>
                    <!-- FILTROS SOLO: Tarea, Organismo -->
                    <tr class="filters">
                        <th></th>
                        <th><input type="text" class="form-control form-control-sm" placeholder="Filtrar…"></th>
                        <th>
                            <select class="form-select form-select-sm">
                                <option value="">Todos</option>
                            </select>
                        </th>
                        <th></th>
                        <th></th>
                        <th><button type="button" class="btn btn-sm btn-outline-secondary w-100 btn-clear">Limpiar</button></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reuniones as $r): ?>
                        <tr class="<?= !empty($r['fijado']) ? 'fila-fijada' : '' ?>">
                            <td><?= $r['fecha_inicio'] ?></td>
                            <td><?= htmlspecialchars($r['tarea']) ?></td>
                            <td><?= $r['organismo'] ? htmlspecialchars($r['organismo']) : '—' ?></td>
                            <td><?= htmlspecialchars($r['asistentes']) ?></td>
                            <td>
                                <?php if (!empty($r['archivo'])): ?>
                                    <a href="../uploads/reuniones/<?= $r['archivo'] ?>" target="_blank" class="btn btn-sm btn-primary">Ver archivo</a>
                                <?php else: ?> — <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <button class="btn btn-sm btn-outline-warning btn-pin"
                                    title="<?= !empty($r['fijado']) ? 'Quitar prioridad' : 'Fijar prioridad' ?>"
                                    data-id="<?= $r['id'] ?>"
                                    data-fijado="<?= (int)($r['fijado'] ?? 0) ?>">
                                    <i class="bi <?= !empty($r['fijado']) ? 'bi-star-fill' : 'bi-star' ?> pin-icon"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary btn-agenda"
                                    title="Agregar a Agenda"
                                    data-reg='<?= json_encode($r) ?>'>
                                    <i class="bi bi-calendar-plus"></i>
                                </button>
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
                <div class="ra-card <?= !empty($r['fijado']) ? 'fila-fijada' : '' ?>">
                    <div class="ra-title">
                        <?php if (!empty($r['fijado'])): ?><span class="badge badge-prioridad me-1">PRIORIDAD</span><?php endif; ?>
                        <?= htmlspecialchars($r['tarea']) ?>
                    </div>
                    <div class="ra-row">
                        <?php if (!empty($r['organismo'])): ?>
                            <div class="full ra-meta"><strong>Organismo:</strong> <?= htmlspecialchars($r['organismo']) ?></div>
                        <?php endif; ?>
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
                        <button class="btn btn-sm btn-outline-warning btn-pin"
                            title="<?= !empty($r['fijado']) ? 'Quitar prioridad' : 'Fijar prioridad' ?>"
                            data-id="<?= $r['id'] ?>"
                            data-fijado="<?= (int)($r['fijado'] ?? 0) ?>">
                            <i class="bi <?= !empty($r['fijado']) ? 'bi-star-fill' : 'bi-star' ?> pin-icon"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary btn-agenda"
                            title="Agregar a Agenda"
                            data-reg='<?= json_encode($p /* o $r */) ?>'>
                            <i class="bi bi-calendar-plus"></i>
                        </button>

                        <button class="btn btn-sm btn-warning btn-editar" data-reg='<?= json_encode($r) ?>'>✏️ Editar</button>
                        <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?= $r['id'] ?>">🗑️ Eliminar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Modal edición (igual que antes) -->
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
                                <option value="proyecto">ACTIVIDADES</option>
                                <option value="reunion">REUNIONES</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Proyecto / Tarea</label>
                            <input type="text" class="form-control" name="tarea" id="edit-tarea" required>
                        </div>
                        <div class="mb-3" id="grp-estado">
                            <label>Estado</label>
                            <select class="form-select" name="estado" id="edit-estado"></select>
                        </div>
                        <div class="mb-3" id="grp-organismo">
                            <label>Organismo / Proyecto</label>
                            <select class="form-select" id="edit-organismo" name="organismo"></select>
                            <input type="text" class="form-control mt-2 d-none" id="edit-organismo-otro" placeholder="Especificá el organismo/proyecto">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ESTADOS_ACT = ['Completado', 'En curso', 'No iniciada', 'Bloqueada'];
            const ORGANISMOS = [
                'I CJ - Oficina Judicial Penal',
                'I CJ - Oficina de Gestión Común Civil',
                'I CJ - Oficina de Gestión Judicial de Familia',
                'II CJ - Oficina Judicial Penal',
                'II CJ - Oficina de Gestión Judicial de Familia',
                'III CJ - Oficina Judicial Penal',
                'III CJ - Ciudad General Acha',
                'III CJ - Ciudad 25 de Mayo',
                'IV CJ - Judicial Penal'
            ];

            const norm = s => (s || '').toString().trim().toLowerCase();

            function fillSelect(select, values, current, addOtro = false) {
                if (!select) return;
                select.innerHTML = '';
                values.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v;
                    opt.textContent = v;
                    select.appendChild(opt);
                });
                if (addOtro) {
                    const opt = document.createElement('option');
                    opt.value = '__OTRO__';
                    opt.textContent = 'Otro (especificar)';
                    select.appendChild(opt);
                }
                if (current && !values.includes(current)) {
                    const opt = document.createElement('option');
                    opt.value = current;
                    opt.textContent = `${current} (existente)`;
                    select.appendChild(opt);
                }
                if (current) select.value = current;
            }

            function fillSelectOptions(select, values) {
                const curr = select.value;
                values.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v;
                    opt.textContent = v;
                    select.appendChild(opt);
                });
                if (curr) select.value = curr;
            }

            function normalizarEstado(s) {
                s = norm(s);
                const map = {
                    'completado': 'Completado',
                    'en curso': 'En curso',
                    'no iniciada': 'No iniciada',
                    'bloqueada': 'Bloqueada'
                };
                return map[s] || null;
            }

            // --- Modal UI (igual que antes) ---
            function applyTipoUI(tipo, estadoActual = '', organismoActual = '') {
                const grpEstado = document.getElementById('grp-estado');
                const selEstado = document.getElementById('edit-estado');
                const grpOrg = document.getElementById('grp-organismo');
                const selOrg = document.getElementById('edit-organismo');
                const inpOtro = document.getElementById('edit-organismo-otro');

                grpOrg?.classList.remove('d-none');
                fillSelect(selOrg, ORGANISMOS, organismoActual || estadoActual || '', true);
                if (selOrg) selOrg.required = (tipo === 'reunion');

                if (tipo === 'reunion') {
                    grpEstado?.classList.add('d-none');
                } else {
                    grpEstado?.classList.remove('d-none');
                    const canon = normalizarEstado(estadoActual) || 'No iniciada';
                    fillSelect(selEstado, ESTADOS_ACT, canon, false);
                }

                const showOtro = selOrg?.value === '__OTRO__';
                inpOtro?.classList.toggle('d-none', !showOtro);
                if (!showOtro && inpOtro) inpOtro.value = '';
            }
            document.getElementById('edit-organismo')?.addEventListener('change', (e) => {
                const inpOtro = document.getElementById('edit-organismo-otro');
                const showOtro = e.target.value === '__OTRO__';
                inpOtro?.classList.toggle('d-none', !showOtro);
                if (!showOtro && inpOtro) inpOtro.value = '';
            });
            document.getElementById('edit-tipo')?.addEventListener('change', (e) => applyTipoUI(e.target.value, '', ''));

            document.querySelectorAll(".btn-editar").forEach(btn => {
                btn.addEventListener("click", () => {
                    const data = JSON.parse(btn.dataset.reg);
                    document.getElementById('edit-id').value = data.id;
                    document.getElementById('edit-tipo').value = data.tipo;
                    document.getElementById('edit-tarea').value = data.tarea;
                    document.getElementById('edit-notas').value = data.notas || '';
                    document.getElementById('edit-fecha-inicio').value = data.fecha_inicio || '';
                    document.getElementById('edit-fecha-fin').value = data.fecha_fin || '';
                    document.getElementById('edit-asistentes').value = data.asistentes || '';

                    applyTipoUI(data.tipo, (data.estado || ''), (data.organismo || ''));

                    if (data.tipo !== 'reunion') {
                        const sel = document.getElementById('edit-estado');
                        const canon = normalizarEstado(data.estado) || 'No iniciada';
                        if (sel) {
                            if (![...sel.options].some(o => o.value === canon)) {
                                const opt = document.createElement('option');
                                opt.value = canon;
                                opt.textContent = canon;
                                sel.appendChild(opt);
                            }
                            sel.value = canon;
                        }
                    } else {
                        const sel = document.getElementById('edit-organismo');
                        const actual = (data.organismo || data.estado || '').trim();
                        if (sel && actual && ![...sel.options].some(o => o.value === actual)) {
                            const opt = document.createElement('option');
                            opt.value = actual;
                            opt.textContent = `${actual} (existente)`;
                            sel.appendChild(opt);
                            sel.value = actual;
                        }
                    }
                    new bootstrap.Modal(document.getElementById('modalEditar')).show();
                });
            });

            document.getElementById("formEditar")?.addEventListener("submit", function(e) {
                e.preventDefault();
                const form = e.target;
                const fd = new FormData(form);
                const tipo = form.querySelector('#edit-tipo')?.value;
                const selOrg = form.querySelector('#edit-organismo');
                const inpOrg = form.querySelector('#edit-organismo-otro');
                let valorOrg = (selOrg?.value === '__OTRO__') ? (inpOrg?.value || '').trim() : (selOrg?.value || '').trim();
                fd.set('organismo', valorOrg);
                if (tipo === 'reunion') {
                    if (valorOrg) fd.set('estado', valorOrg);
                } else {
                    const selEstado = form.querySelector('#edit-estado');
                    const canon = normalizarEstado(selEstado?.value) || 'No iniciada';
                    fd.set('estado', canon);
                }
                fetch('../api/api-reuniones.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(res => res.json())
                    .then(data => Swal.fire('Actualizado', (data && data.mensaje) || 'OK', 'success').then(() => location.reload()))
                    .catch(() => Swal.fire('Error', 'No se pudo actualizar', 'error'));
            });

            document.querySelectorAll(".btn-eliminar").forEach(btn => {
                btn.addEventListener("click", () => {
                    const id = btn.dataset.id;
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: 'Esta acción no se puede deshacer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((r) => {
                        if (r.isConfirmed) {
                            fetch('../api/api-reuniones.php', {
                                    method: 'DELETE',
                                    body: new URLSearchParams({
                                        id
                                    })
                                })
                                .then(res => res.json())
                                .then(data => Swal.fire('Eliminado', (data && data.mensaje) || 'OK', 'success').then(() => location.reload()))
                                .catch(() => Swal.fire('Error', 'No se pudo eliminar', 'error'));
                        }
                    });
                });
            });

            document.querySelectorAll('.btn-pin').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = btn.dataset.id;
                    const fijadoActual = Number(btn.dataset.fijado) || 0;
                    const nuevo = fijadoActual ? 0 : 1;
                    try {
                        const res = await fetch('../api/api-reuniones.php', {
                            method: 'POST',
                            body: new URLSearchParams({
                                accion: 'pin',
                                id,
                                fijado: String(nuevo)
                            })
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.error || 'Error');
                        location.reload();
                    } catch (e) {
                        Swal.fire('Error', e.message || 'No se pudo actualizar la prioridad', 'error');
                    }
                });
            });

            /* ====== FILTROS SIMPLIFICADOS ====== */
            function attachFiltersActividades() {
                const table = document.getElementById('tbl-actividades');
                if (!table) return;
                const filters = table.querySelector('thead tr.filters');
                const inpTarea = filters.children[0].querySelector('input');
                const selEstado = filters.children[1].querySelector('select');
                const selOrg = filters.children[2].querySelector('select');
                const btnClear = filters.querySelector('.btn-clear');

                // opciones
                fillSelectOptions(selEstado, ESTADOS_ACT);
                fillSelectOptions(selOrg, ORGANISMOS);
                const optVacio = document.createElement('option');
                optVacio.value = '__vacio__';
                optVacio.textContent = '— / Vacío';
                selOrg.appendChild(optVacio);

                function apply() {
                    const t = norm(inpTarea.value);
                    const e = norm(selEstado.value);
                    const o = selOrg.value; // '__vacio__' o texto

                    table.querySelectorAll('tbody tr').forEach(tr => {
                        const cTarea = norm(tr.children[0].textContent);
                        const cEstado = norm(tr.children[1].textContent);
                        const orgTxt = tr.children[2].textContent.trim();
                        const cOrg = norm(orgTxt === '—' ? '' : orgTxt);

                        let ok = true;
                        if (t && !cTarea.includes(t)) ok = false;
                        if (e && cEstado !== e) ok = false;
                        if (o) {
                            if (o === '__vacio__') {
                                if (cOrg) ok = false;
                            } else if (cOrg !== norm(o)) ok = false;
                        }
                        tr.style.display = ok ? '' : 'none';
                    });
                }
                [inpTarea, selEstado, selOrg].forEach(el => {
                    el.addEventListener('input', apply);
                    el.addEventListener('change', apply);
                });
                btnClear.addEventListener('click', () => {
                    [inpTarea, selEstado, selOrg].forEach(el => el.value = '');
                    apply();
                });
                apply();
            }

            function attachFiltersReuniones() {
                const table = document.getElementById('tbl-reuniones');
                if (!table) return;
                const filters = table.querySelector('thead tr.filters');
                const inpTarea = filters.children[1].querySelector('input');
                const selOrg = filters.children[2].querySelector('select');
                const btnClear = filters.querySelector('.btn-clear');

                fillSelectOptions(selOrg, ORGANISMOS);

                function apply() {
                    const t = norm(inpTarea.value);
                    const o = norm(selOrg.value);

                    table.querySelectorAll('tbody tr').forEach(tr => {
                        const cTarea = norm(tr.children[1].textContent);
                        const cOrg = norm(tr.children[2].textContent);

                        let ok = true;
                        if (t && !cTarea.includes(t)) ok = false;
                        if (o && cOrg !== o) ok = false;

                        tr.style.display = ok ? '' : 'none';
                    });
                }
                [inpTarea, selOrg].forEach(el => {
                    el.addEventListener('input', apply);
                    el.addEventListener('change', apply);
                });
                btnClear.addEventListener('click', () => {
                    [inpTarea, selOrg].forEach(el => el.value = '');
                    apply();
                });
                apply();
            }

            attachFiltersActividades();
            attachFiltersReuniones();
        });

        /* ===============================
         *  Enviar a Agenda
         * =============================== */
        function prettyTipo(t) {
            const v = String(t || '').toLowerCase();
            if (v === 'proyecto') return 'ACTIVIDAD';
            if (v === 'reunion') return 'REUNIÓN';
            return v.toUpperCase();
        }

        function buildDescripcionAgenda(data) {
            const partes = [];
            if (data.tipo) partes.push(`Tipo: ${prettyTipo(data.tipo)}`);
            if (data.organismo) {
                partes.push(`Organismo/Proyecto: ${data.organismo}`);
            } else if (data.estado && data.tipo === 'reunion') {
                partes.push(`Organismo/Proyecto: ${data.estado}`);
            }
            if (data.asistentes) partes.push(`Asistentes: ${data.asistentes}`);
            if (data.notas) partes.push(`Notas: ${data.notas}`);
            return partes.join('\n');
        }

        async function crearEventoAgenda({
            titulo,
            descripcion,
            fecha
        }) {
            const fd = new FormData();
            fd.append('titulo', titulo);
            fd.append('descripcion', descripcion || '');
            fd.append('fecha', fecha); // YYYY-MM-DD

            const res = await fetch('../api/api-agregar-evento.php', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            if (!res.ok || !data || data.success !== true) {
                throw new Error((data && data.error) || 'Error creando evento');
            }
            return data;
        }

        /* ===============================
         *  Enviar SIEMPRE a Agenda con fecha elegida
         * =============================== */
        document.querySelectorAll('.btn-agenda').forEach(btn => {
            btn.addEventListener('click', async () => {
                const reg = JSON.parse(btn.dataset.reg || '{}');
                const titulo = (reg.tarea || '').trim() || 'Sin título';
                const descripcion = buildDescripcionAgenda(reg);

                try {
                    // pedir SIEMPRE la fecha (no usamos fecha_inicio/fin del registro)
                    const pick = await Swal.fire({
                        title: 'Elegí la fecha del evento',
                        input: 'date',
                        inputValue: new Date().toISOString().slice(0, 10),
                        showCancelButton: true,
                        confirmButtonText: 'Agendar',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!pick.isConfirmed || !pick.value) return;

                    const fecha = pick.value; // YYYY-MM-DD
                    await crearEventoAgenda({
                        titulo,
                        descripcion,
                        fecha
                    });

                    const go = await Swal.fire({
                        icon: 'success',
                        title: 'Agendado',
                        text: 'Se creó el evento en la agenda.',
                        showCancelButton: true,
                        confirmButtonText: 'Abrir agenda',
                        cancelButtonText: 'OK'
                    });
                    if (go.isConfirmed) window.location.href = 'agenda.php';
                } catch (err) {
                    Swal.fire('Error', err.message || 'No se pudo agendar', 'error');
                }
            });
        });
    </script>

</body>

</html>