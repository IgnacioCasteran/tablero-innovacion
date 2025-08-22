document.addEventListener("DOMContentLoaded", function () {
  cargarInformes();

  const formEditar = document.getElementById("formEditar");

  if (formEditar) {
    formEditar.addEventListener("submit", async function (e) {
      e.preventDefault();

      const datos = {
        id: document.getElementById("editarId").value,
        responsable: document.getElementById("editarResponsable").value,
        empleado: document.getElementById("editarEmpleado").value,
        desde: document.getElementById("editarDesde").value,
        hasta: document.getElementById("editarHasta").value,
        rubro: document.getElementById("editarRubro").value,
        categoria: document.getElementById("editarCategoria").value,
        descripcion: document.getElementById("editarDescripcion").value,
        observaciones: document.getElementById("editarObservaciones").value,
      };

      try {
        const response = await fetch("editar_informe.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(datos),
        });

        const resultado = await response.json();

        if (resultado.success) {
          bootstrap.Modal.getInstance(document.getElementById("modalEditar")).hide();
          cargarInformes();
          alert("✅ Informe actualizado correctamente.");
        } else {
          alert("❌ Error al editar el informe.");
        }
      } catch (error) {
        console.error("Error al editar:", error);
        alert("Error de conexión.");
      }
    });
  }

  document.getElementById("btnConfirmarEliminar").addEventListener("click", async function () {
    if (window.idAEliminar) {
      try {
        const response = await fetch("eliminar_informe.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: window.idAEliminar }),
        });

        const resultado = await response.json();

        if (resultado.success) {
          bootstrap.Modal.getInstance(document.getElementById("modalEliminar")).hide();
          cargarInformes();
          alert("✅ Informe eliminado.");
        } else {
          alert("❌ Error al eliminar.");
        }
      } catch (error) {
        console.error("Error al eliminar:", error);
        alert("Error de conexión.");
      }
    }
  });
});

async function cargarInformes() {
  try {
    const response = await fetch("obtener_informes.php");
    const datos = await response.json();
    const tbody = document.getElementById("cuerpoTabla");
    tbody.innerHTML = "";

    datos.forEach((informe) => {
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
            ✏️ Editar
          </a>
        </li>
        <li>
          <a class="dropdown-item text-danger" href="#" onclick="abrirModalEliminar(${informe.id})">
            🗑️ Eliminar
          </a>
        </li>
      </ul>
      </div>
      </td>
      `;

      tbody.appendChild(fila);
    });

        // ⚠️ Este es el punto clave: pasamos los datos a filtros.js
    if (typeof setDatos === "function") {
      setDatos(datos); // ← actualiza datosGlobales y activa los filtros
    }
  } catch (error) {
    console.error("Error al obtener informes:", error);
    alert("No se pudieron cargar los informes.");
  }
}

function abrirModalEditar(informe) {
  document.getElementById("editarId").value = informe.id;
  document.getElementById("editarResponsable").value = informe.responsable;
  document.getElementById("editarEmpleado").value = informe.empleado;
  document.getElementById("editarDesde").value = informe.desde;
  document.getElementById("editarHasta").value = informe.hasta;
  document.getElementById("editarRubro").value = informe.rubro;
  document.getElementById("editarCategoria").value = informe.categoria;
  document.getElementById("editarDescripcion").value = informe.descripcion;
  document.getElementById("editarObservaciones").value = informe.observaciones;

  const modal = new bootstrap.Modal(document.getElementById("modalEditar"));
  modal.show();
}

function abrirModalEliminar(id) {
  window.idAEliminar = id;
  const modal = new bootstrap.Modal(document.getElementById("modalEliminar"));
  modal.show();
}
