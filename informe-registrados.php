<?php

require_once __DIR__ . '/auth.php';
require_login();          // exige sesión
enforce_route_access();   // aplica restricciones por rol (coord solo Informes, STJ solo lectura)
render_readonly_ui();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Informes Registrados - Poder Judicial</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/estilos.css">

</head>

<body>
    <?php
    // Inyectamos en JS el rol y el alcance del usuario logueado
    require_once __DIR__ . '/conexion.php';

    $scope = ['rol' => current_role(), 'circ' => null, 'oficina' => null];
    try {
        $cn = db();
        $uid = (int)($_SESSION['user_id'] ?? 0);
        if ($uid > 0) {
            $st = $cn->prepare("SELECT alcance_circ, alcance_oficina FROM usuarios WHERE id=? LIMIT 1");
            $st->bind_param("i", $uid);
            $st->execute();
            if ($u = $st->get_result()->fetch_assoc()) {
                $scope['circ']    = $u['alcance_circ'] ?? null;
                $scope['oficina'] = $u['alcance_oficina'] ?? null;
            }
            $st->close();
        }
    } catch (Throwable $e) { /* silencioso */
    }
    ?>
    <script>
        // Disponible para filtros.js
        window.USER_SCOPE = <?= json_encode($scope, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar p-4">
                <div class="logo-section">
                    <img src="img/Poder_Judicial_logo.png" alt="Logo Poder Judicial">
                    <h1>Poder Judicial<br><small>Provincia de La Pampa</small></h1>
                </div>

                <ul class="nav flex-column mt-4">
                    <li class="nav-item"><a class="nav-link" href="carga_informe.php">Carga de Informe Periódico</a></li>
                    <li class="nav-item"><a class="nav-link active" href="informe-registrados.php">Informes Registrados</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="/login/logout.php">Cerrar sesión</a></li>
                </ul>

            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">
                <h2 class="text-danger mb-4">Informes Periódicos Registrados</h2>
                <!-- Filtros visuales -->
                <div class="row g-2 align-items-end mb-4">
                    <div class="col-md-2">
                        <label class="form-label">Circunscripción</label>
                        <select class="form-select" id="filtroCirc">
                            <option value="">Todas</option>
                            <option value="I">I Circ.</option>
                            <option value="II">II Circ.</option>
                            <option value="III">III Circ.</option>
                            <option value="IV">IV Circ.</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select class="form-select" id="filtroEstado">
                            <option value="">Todos</option>
                            <option value="Inicial">Inicial</option>
                            <option value="En proceso">En proceso</option>
                            <option value="Finalizado">Finalizado</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filtroRubro" class="form-label">Rubro</label>
                        <input type="text" id="filtroRubro" class="form-control" placeholder="Buscar rubro" />
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Oficina Judicial</label>
                        <select class="form-select" id="filtroOficina">
                            <option value="">Todas</option>
                            <option value="Oficina Judicial Penal">Oficina Judicial Penal</option>
                            <option value="Oficina de Gestión Judicial de Familia">Oficina de Gestión Judicial de
                                Familia</option>
                            <option value="Ciudad General Acha">Ciudad General Acha</option>
                            <option value="Judicial Penal">Judicial Penal</option>
                            <option value="Oficina de Gestión Común Civil">Oficina de Gestión Común Civil</option>
                            <option value="Ciudad 25 de Mayo">Ciudad 25 de Mayo</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Categoría</label>
                        <input type="text" id="filtroCategoria" class="form-control" placeholder="Buscar categoría" />
                    </div>


                    <div class="col-md-2">
                        <label class="form-label">Responsable</label>
                        <input type="text" class="form-control" id="filtroResponsable" placeholder="Buscar responsable">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Empleado/a</label>
                        <input type="text" class="form-control" id="filtroEmpleado" placeholder="Buscar empleado/a">
                    </div>



                    <div class="table-responsive bg-white shadow p-3 rounded">
                        <table class="table table-striped table-bordered" id="tablaInformes">
                            <thead class="table-danger">
                                <tr>
                                    <th>Circunscripción</th>
                                    <th>Oficina Judicial</th>
                                    <th>Responsable</th>
                                    <th>Desde</th>
                                    <th>Hasta</th>
                                    <th>Rubro</th>
                                    <th>Categoría</th>
                                    <th>Empleado/a</th>
                                    <th>Estado</th>
                                    <th>Descripción</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>

                            <tbody id="cuerpoTabla">
                                <!-- Datos generados por JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal Editar Informe -->
                    <div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content border-primary">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">Editar Informe</h5>
                                    <button type="button" class="btn-close btn-close-white"
                                        data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="formEditar">
                                        <input type="hidden" id="editarId">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Responsable</label>
                                                <input type="text" id="editarResponsable" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Empleado</label>
                                                <input type="text" id="editarEmpleado" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Desde</label>
                                                <input type="date" id="editarDesde" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Hasta</label>
                                                <input type="date" id="editarHasta" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Rubro</label>
                                                <input type="text" id="editarRubro" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Categoría</label>
                                                <input type="text" id="editarCategoria" class="form-control" required>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Descripción</label>
                                                <textarea id="editarDescripcion" class="form-control" rows="3"
                                                    required></textarea>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Observaciones</label>
                                                <textarea id="editarObservaciones" class="form-control"
                                                    rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="mt-4 text-end">
                                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Confirmar Eliminación -->
                    <div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content border-danger">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Eliminar Informe</h5>
                                    <button type="button" class="btn-close btn-close-white"
                                        data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <p>¿Estás seguro de que deseas eliminar este informe?</p>
                                    <button class="btn btn-danger me-2" id="btnConfirmarEliminar">Sí, eliminar</button>
                                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Gráficos -->
                    <h3 class="mt-5 text-danger">Estadísticas Generales</h3>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <canvas id="estadoChart"></canvas>
                        </div>
                        <div class="col-md-6 mb-4">
                            <canvas id="circunsChart"></canvas>
                        </div>
                        <div class="col-md-6 mb-4">
                            <canvas id="rubroChart"></canvas>
                        </div>
                    </div>

                    <!-- Estadísticas sin gráficos -->
                    <h3 class="mt-5 text-danger">Resumen Estadístico (sin gráficos)</h3>
                    <h4 class="mt-4 text-primary">Estadísticas por Estado</h4>
                    <div class="row g-3 mb-5">
                        <div class="col-md-4">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <strong>Total de informes:</strong> <span id="totalInformes"></span><br>
                                <strong>Finalizados:</strong> <span id="finalizados"></span> (<span
                                    id="porcentajeFinalizados"></span>%)
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <strong>En proceso:</strong> <span id="enProceso"></span> (<span
                                    id="porcentajeEnProceso"></span>%)
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-white border rounded shadow-sm">
                                <strong>Iniciales:</strong> <span id="iniciales"></span> (<span
                                    id="porcentajeIniciales"></span>%)
                            </div>
                        </div>
                    </div>

                    <h4 class="mt-4 text-primary">Estadísticas por Rubro</h4>
                    <div class="row row-cols-1 row-cols-md-3 g-3 mb-4" id="estadisticasRubros"></div>

                    <h4 class="mt-4 text-primary">Estadísticas por Oficina Judicial</h4>
                    <div class="row row-cols-1 row-cols-md-3 g-3 mb-5" id="estadisticasOficinas"></div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/datos.js"></script>
    <script src="js/informe-registrados.js"></script>
    <script src="js/filtros.js"></script>
    <script src="js/graficos.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            configurarEventosFiltros();
            actualizarOficinas();
        });
    </script>

</body>

</html>