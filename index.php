<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: /login/login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tablero Judicial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="icon" href="/img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="/css/styles.css" />
    <style>
        /* Widget de recordatorios (arriba-izquierda) */
        #recordatorios {
            position: fixed;
            top: 16px;
            left: 16px;
            max-width: 360px;
            z-index: 1050;
            /* por encima de cards/botones */
            border-left: 6px solid #7c1c2c;
        }

        #recordatorios .card-header {
            background: #f9e7e3;
            color: #7c1c2c;
            font-weight: 600;
        }

        #recordatorios .when-badge {
            font-size: .75rem;
        }
    </style>

</head>

<body>

    <!-- Botón cerrar sesión arriba a la derecha -->
    <div class="container mt-3 d-flex justify-content-end">
        <a href="/login/logout.php" class="btn btn-danger btn-sm">
            <i class="bi bi-box-arrow-right"></i>
            Cerrar sesión (<?php echo $_SESSION['usuario']; ?>)
        </a>
    </div>
    <div id="rec-list" class="list-group list-group-flush"></div>
    <div class="card-footer py-2 text-end">
        <a href="secciones/agenda.php" class="btn btn-sm btn-outline-primary">Abrir agenda</a>
    </div>
    </div>

    <!-- Botón minimizado -->
    <button id="rec-min" class="btn btn-sm btn-warning d-none"
        style="position:fixed;top:16px;left:16px;z-index:1051;border-radius:999px;box-shadow:0 2px 8px rgba(0,0,0,.15);">
        🔔 Recordatorios
    </button>

    <div id="recordatorios" class="card shadow d-none"
        style="position:fixed;top:16px;left:16px;max-width:360px;z-index:1050;border-left:6px solid #7c1c2c;">
        <button type="button" class="btn-close position-absolute top-0 end-0 m-2" id="rec-close" aria-label="Cerrar"></button>
        <div class="card-header" style="background:#f9e7e3;color:#7c1c2c;font-weight:600;">
            <i class="bi bi-bell-fill me-1"></i> Recordatorios (próx. 7 días)
        </div>

        <div class="container py-5 text-center">
            <div class="d-flex flex-column align-items-center mb-4">
                <a href="/index.php" class="text-decoration-none text-dark text-center">
                    <img src="/img/poder-judicial.png" class="logo mb-2" alt="Poder Judicial de La Pampa">
                    <h1 class="titulo mb-0">PODER JUDICIAL </h1>
                    <h4 class="subtitulo text-muted">de La Pampa</h4>

                    <div class="row g-4 justify-content-center">
                        <div class="col-md-5">
                            <a href="/secciones/coordinacion.php" class="btn-opcion">
                                <img src="/img/icon-oficinas.png" alt="" class="icon-img me-2">
                                Coordinación de Oficinas Judiciales
                            </a>
                        </div>
                        <div class="col-md-5">
                            <a href="/secciones/proyectos.php" class="btn-opcion">
                                <img src="/img/icon-proyectos.png" alt="" class="icon-img me-2">
                                Proyectos
                            </a>
                        </div>

                        <div class="col-md-5">
                            <a href="/secciones/intervenciones.php" class="btn-opcion">
                                <img src="/img/psychology.png" alt="" class="icon-img me-2">
                                Intervenciones Psicosociales
                            </a>
                        </div>
                        <div class="col-md-5">
                            <a href="#" class="btn-opcion">
                                <img src="/img/icon-laboratorio.png" alt="" class="icon-img me-2">
                                Laboratorio de Innovación Judicial
                            </a>
                        </div>

                        <div class="col-md-5">
                            <a href="/secciones/sgc.php" class="btn-opcion">
                                <img src="/img/icon-sgc.png" alt="" class="icon-img me-2">
                                SGC
                            </a>
                        </div>

                        <div class="col-12">
                            <a href="/secciones/agenda.php" class="btn-opcion btn-agenda">
                                <img src="/img/icon-agenda.png" alt="" class="icon-img me-2">
                                Agenda
                            </a>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            (function() {
                const box = document.getElementById('recordatorios');
                const list = document.getElementById('rec-list');
                const btnX = document.getElementById('rec-close');
                const mini = document.getElementById('rec-min');
                const KEY = 'recordatorios:min'; // '1' = mostrar minimizado

                function parseLocalDate(s) {
                    if (!s) return null;
                    const d = String(s).split(' ')[0]; // "YYYY-MM-DD"
                    const [Y, M, D] = d.split('-').map(n => parseInt(n, 10));
                    if (!Y || !M || !D) return null;
                    return new Date(Y, M - 1, D, 0, 0, 0, 0); // local
                }

                function startOfDay(dt) {
                    return new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
                }

                function ddmmyyyy(dt) {
                    const d = String(dt.getDate()).padStart(2, '0');
                    const m = String(dt.getMonth() + 1).padStart(2, '0');
                    const y = dt.getFullYear();
                    return `${d}/${m}/${y}`;
                }

                function humanWhen(days) {
                    if (days === 0) return 'hoy';
                    if (days === 1) return 'mañana';
                    return `en ${days} días`;
                }

                function showFull() {
                    box.classList.remove('d-none');
                    mini.classList.add('d-none');
                    sessionStorage.removeItem(KEY);
                }

                function showMini(count) {
                    mini.textContent = `🔔 Recordatorios (${count})`;
                    mini.classList.remove('d-none');
                    box.classList.add('d-none');
                    sessionStorage.setItem(KEY, '1');
                }

                function hideAll() {
                    box.classList.add('d-none');
                    mini.classList.add('d-none');
                    sessionStorage.removeItem(KEY);
                }

                btnX?.addEventListener('click', () => {
                    // Cerrar → queda minimizado
                    const count = list?.children?.length || 0;
                    showMini(count);
                });

                mini?.addEventListener('click', () => {
                    // Reabrir
                    showFull();
                });

                // Traer eventos
                fetch('api/api-agenda.php')
                    .then(r => r.json())
                    .then(items => {
                        const today = startOfDay(new Date());
                        const soon = (items || [])
                            .map(ev => {
                                const d = parseLocalDate(ev.start);
                                return d ? {
                                    ...ev,
                                    _date: d
                                } : null;
                            })
                            .filter(Boolean)
                            .map(ev => ({
                                ...ev,
                                _diff: Math.round((ev._date - today) / 86400000)
                            }))
                            .filter(ev => ev._diff >= 0 && ev._diff <= 7)
                            .sort((a, b) => a._date - b._date)
                            .slice(0, 6);

                        if (soon.length === 0) {
                            hideAll();
                            return;
                        }

                        list.innerHTML = '';
                        soon.forEach(ev => {
                            const a = document.createElement('a');
                            a.href = 'secciones/agenda.php';
                            a.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
                            a.innerHTML = `
          <div class="me-2">
            <div class="fw-semibold">${ev.title || 'Sin título'}</div>
            <small class="text-muted">${ddmmyyyy(ev._date)}</small>
            ${ev.extendedProps?.descripcion ? `<div class="small text-secondary mt-1">${(ev.extendedProps.descripcion+'').replace(/\n/g,'<br>')}</div>` : ''}
          </div>
          <span class="badge bg-warning text-dark align-self-center" style="font-size:.75rem">${humanWhen(ev._diff)}</span>
        `;
                            list.appendChild(a);
                        });

                        // ¿Mostrar grande o mini?
                        if (sessionStorage.getItem(KEY) === '1') {
                            showMini(soon.length);
                        } else {
                            showFull();
                        }
                    })
                    .catch(() => {
                        /* si falla la API, no mostramos nada */ });
            })();
        </script>
</body>

</html>