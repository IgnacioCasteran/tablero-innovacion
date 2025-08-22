let datosGlobales = []; // Guardamos los informes una vez cargados

// Esta función se llama cuando se cargan los informes desde PHP
function setDatos(informes) {
  datosGlobales = informes;
  aplicarFiltros();
  generarGraficosYEstadisticas(datosGlobales);
}

function aplicarFiltros() {
  const filtroCirc = document.getElementById("filtroCirc").value.trim().toLowerCase();
  const filtroEstado = document.getElementById("filtroEstado").value.trim().toLowerCase();
  const filtroRubro = document.getElementById("filtroRubro").value.trim().toLowerCase();
  const filtroOficina = document.getElementById("filtroOficina").value.trim().toLowerCase();
  const filtroCategoria = document.getElementById("filtroCategoria").value.trim().toLowerCase();
  const filtroResponsable = document.getElementById("filtroResponsable").value.trim().toLowerCase();
  const filtroEmpleado = document.getElementById("filtroEmpleado").value.trim().toLowerCase();

  const filtrados = datosGlobales.filter(d => {
    const coincideCirc = !filtroCirc || (d.circunscripcion || "").trim().toLowerCase() === filtroCirc;
    const coincideEstado = !filtroEstado || (d.estado || "").trim().toLowerCase() === filtroEstado;
    const coincideRubro = !filtroRubro || (d.rubro || "").toLowerCase().includes(filtroRubro);
    const coincideOficina = !filtroOficina || (d.oficina_judicial || "").trim().toLowerCase() === filtroOficina;
    const coincideCategoria = !filtroCategoria || (d.categoria || "").toLowerCase().includes(filtroCategoria);
    const coincideResponsable = !filtroResponsable || (d.responsable || "").toLowerCase().includes(filtroResponsable);
    const coincideEmpleado = !filtroEmpleado || (d.empleado || "").toLowerCase().includes(filtroEmpleado);

    return coincideCirc && coincideEstado && coincideRubro && coincideOficina && coincideCategoria && coincideResponsable && coincideEmpleado;
  });

  renderizarFiltrados(filtrados);
  generarGraficosYEstadisticas(filtrados);
}


function renderizarFiltrados(informes) {
  const tbody = document.getElementById("cuerpoTabla");
  tbody.innerHTML = "";

  informes.forEach((informe) => {
    const fila = document.createElement("tr");

    fila.innerHTML = `
      <td>${informe.circunscripcion}</td>
      <td>${informe.oficina_judicial}</td>
      <td>${informe.responsable}</td>
      <td>${informe.desde}</td>
      <td>${informe.hasta}</td>
      <td>${informe.rubro}</td>
      <td>${informe.categoria}</td>
      <td>${informe.empleado}</td>
      <td>${informe.estado}</td>
      <td>${informe.descripcion}</td>
      <td>${informe.observaciones}</td>
      <td class="text-center">
        <div class="dropdown">
          <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            Acciones
          </button>
          <ul class="dropdown-menu">
            <li>
              <a class="dropdown-item text-warning" href="#" onclick="abrirModalEditar(${JSON.stringify(informe).replace(/"/g, '&quot;')})">
                Editar
              </a>
            </li>
            <li>
              <a class="dropdown-item text-danger" href="#" onclick="abrirModalEliminar(${informe.id})">
                Eliminar
              </a>
            </li>
          </ul>
        </div>
      </td>
    `;

    tbody.appendChild(fila);
  });
}

function configurarEventosFiltros() {
  const filtros = [
    "filtroCirc",
    "filtroEstado",
    "filtroRubro",
    "filtroOficina",
    "filtroCategoria",
    "filtroResponsable",
    "filtroEmpleado"
  ];

  filtros.forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener("input", aplicarFiltros);
    }
  });

  document.getElementById("filtroCirc").addEventListener("input", actualizarOficinas);
}

function actualizarOficinas() {
  const circ = document.getElementById("filtroCirc").value;
  const selectOficina = document.getElementById("filtroOficina");
  selectOficina.innerHTML = "<option value=''>Todas</option>";

  const oficinas = circ
    ? datosGlobales.filter(d => d.circunscripcion === circ).map(d => d.oficina_judicial)
    : datosGlobales.map(d => d.oficina_judicial);

  const oficinasUnicas = [...new Set(oficinas)];
  oficinasUnicas.forEach(oficina => {
    const opt = document.createElement("option");
    opt.value = oficina;
    opt.textContent = oficina;
    selectOficina.appendChild(opt);
  });
}



