let charts = [];

function generarGraficosYEstadisticas(data = datos) {
  // Limpiar contenedores de estadísticas simples
  document.getElementById("estadisticasRubros").innerHTML = "";
  document.getElementById("estadisticasOficinas").innerHTML = "";

  // Destruir gráficos anteriores si existen
  charts.forEach(chart => chart.destroy());
  charts = [];

  const total = data.length;
  const estados = ["Inicial", "En proceso", "Finalizado"];
  const estadoCounts = estados.map(e => data.filter(d => d.estado === e).length);
  const circuns = [...new Set(data.map(d => d.circunscripcion))];
  const circCounts = circuns.map(c => data.filter(d => d.circunscripcion === c).length);
  const rubrosUnicos = [...new Set(data.map(d => d.rubro))];
  const rubroCounts = rubrosUnicos.map(r => data.filter(d => d.rubro === r).length);

  charts.push(new Chart(document.getElementById("estadoChart"), {
    type: "bar",
    data: {
      labels: estados,
      datasets: [{
        label: "Informes por Estado",
        data: estadoCounts,
        backgroundColor: ["#ffc107", "#17a2b8", "#28a745"]
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        title: { display: true, text: "Distribución por Estado" }
      }
    }
  }));

  charts.push(new Chart(document.getElementById("circunsChart"), {
    type: "pie",
    data: {
      labels: circuns,
      datasets: [{
        label: "Informes por Circunscripción",
        data: circCounts,
        backgroundColor: ["#dc3545", "#fd7e14", "#20c997", "#6f42c1"]
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: { display: true, text: "Distribución por Circunscripción" }
      }
    }
  }));

  charts.push(new Chart(document.getElementById("rubroChart"), {
    type: "bar",
    data: {
      labels: rubrosUnicos,
      datasets: [{
        label: "Informes por Rubro",
        data: rubroCounts,
        backgroundColor: "#0d6efd"
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        title: { display: true, text: "Distribución por Rubro" }
      }
    }
  }));

  // Estadísticas sin gráficos
  const totalFinalizado = estadoCounts[2];
  const totalEnProceso = estadoCounts[1];
  const totalInicial = estadoCounts[0];

  document.getElementById("totalInformes").textContent = total;
  document.getElementById("finalizados").textContent = totalFinalizado;
  document.getElementById("enProceso").textContent = totalEnProceso;
  document.getElementById("iniciales").textContent = totalInicial;

  document.getElementById("porcentajeFinalizados").textContent = total ? ((totalFinalizado / total) * 100).toFixed(1) : 0;
  document.getElementById("porcentajeEnProceso").textContent = total ? ((totalEnProceso / total) * 100).toFixed(1) : 0;
  document.getElementById("porcentajeIniciales").textContent = total ? ((totalInicial / total) * 100).toFixed(1) : 0;

  const contenedorRubros = document.getElementById("estadisticasRubros");
  rubrosUnicos.forEach(rubro => {
    const cantidad = data.filter(d => d.rubro === rubro).length;
    const porcentaje = total ? ((cantidad / total) * 100).toFixed(1) : 0;
    const div = document.createElement("div");
    div.className = "col";
    div.innerHTML = `<div class="p-3 bg-white border rounded shadow-sm"><strong>${rubro}:</strong> ${cantidad} informes (${porcentaje}%)</div>`;
    contenedorRubros.appendChild(div);
  });

  const oficinasUnicas = [...new Set(data.map(d => d.oficina_judicial))];
  const contenedorOficinas = document.getElementById("estadisticasOficinas");
  oficinasUnicas.forEach(oficina => {
    const cantidad = data.filter(d => d.oficina === oficina).length;
    const porcentaje = total ? ((cantidad / total) * 100).toFixed(1) : 0;
    const div = document.createElement("div");
    div.className = "col";
    div.innerHTML = `<div class="p-3 bg-white border rounded shadow-sm"><strong>${oficina}:</strong> ${cantidad} informes (${porcentaje}%)</div>`;
    contenedorOficinas.appendChild(div);
  });
}


