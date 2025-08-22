// js/proyectos.js

document.addEventListener("DOMContentLoaded", () => {
  const tablaBody = document.querySelector("#tablaProyectos tbody");
  const cardsWrap = document.getElementById("cardsProyectos"); // existe solo si agregaste las cards en el HTML
  const form = document.getElementById("formProyecto");
  const formEditar = document.getElementById("formEditar");

  obtenerProyectos();

  function obtenerProyectos() {
    fetch("../api/api-proyectos.php")
      .then(res => res.json())
      .then(data => {
        renderTabla(data);
        renderCards(data);
        attachActions(); // vincula eventos editar/eliminar en ambos renders
      });
  }

  /* ------------ Render Tabla (desktop/tablet) ------------ */
  function renderTabla(lista) {
    if (!tablaBody) return;
    tablaBody.innerHTML = "";

    lista.forEach(p => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${escapeHtml(p.titulo || "")}</td>
        <td>${escapeHtml(p.responsable || "")}</td>
        <td>${escapeHtml(p.estado || "")}</td>
        <td>${escapeHtml(p.fecha || "")}</td>
        <td>${escapeHtml(p.descripcion || "")}</td>
        <td class="text-nowrap">
          ${p.ficha ? `<a href="../uploads/proyectos/${encodeURIComponent(p.ficha)}" target="_blank" class="btn btn-sm btn-primary mb-1">📄 Ver ficha</a> ` : ""}
          <button class="btn btn-warning btn-sm me-2 btn-editar"
            data-id="${p.id}"
            data-titulo="${attr(p.titulo)}"
            data-responsable="${attr(p.responsable)}"
            data-descripcion="${attr(p.descripcion)}"
            data-estado="${attr(p.estado)}"
            data-fecha="${attr(p.fecha)}"
            data-ficha="${attr(p.ficha || "")}"
          >✏️</button>
          <button class="btn btn-danger btn-sm btn-eliminar" data-id="${p.id}">🗑️</button>
        </td>
      `;
      tablaBody.appendChild(tr);
    });
  }

  /* ------------ Render Cards (mobile) ------------ */
  function renderCards(lista) {
    if (!cardsWrap) return;
    cardsWrap.innerHTML = "";

    lista.forEach(p => {
      const titulo = escapeHtml(p.titulo || "");
      const responsable = escapeHtml(p.responsable || "");
      const estado = (p.estado || "Pendiente").trim();
      const fecha = escapeHtml(p.fecha || "");
      const descripcion = escapeHtml(p.descripcion || "");
      const ficha = p.ficha ? encodeURIComponent(p.ficha) : "";

      const card = document.createElement("div");
      card.className = "pj-card";
      card.innerHTML = `
        <div class="pj-title">${titulo}</div>
        <div class="pj-row">
          <div class="full">
            <span class="pj-badge ${cssState(estado)}">${escapeHtml(estado)}</span>
          </div>
          <div><strong>Resp.:</strong> ${responsable || "—"}</div>
          <div><strong>Fecha:</strong> ${fecha || "—"}</div>
          ${descripcion ? `<div class="full pj-meta"><strong>Desc.:</strong> ${descripcion}</div>` : ""}
          ${ficha ? `<div class="full"><a href="../uploads/proyectos/${ficha}" target="_blank" class="btn btn-sm btn-primary">Ver ficha</a></div>` : ""}
        </div>
        <div class="pj-actions">
          <button class="btn btn-sm btn-warning btn-editar"
            data-id="${p.id}"
            data-titulo="${attr(p.titulo)}"
            data-responsable="${attr(p.responsable)}"
            data-descripcion="${attr(p.descripcion)}"
            data-estado="${attr(p.estado)}"
            data-fecha="${attr(p.fecha)}"
            data-ficha="${attr(p.ficha || "")}"
          >✏️ Editar</button>
          <button class="btn btn-sm btn-danger btn-eliminar" data-id="${p.id}">🗑️ Eliminar</button>
        </div>
      `;
      cardsWrap.appendChild(card);
    });
  }

  /* ------------ Actions (editar / eliminar) ------------ */
  function attachActions() {
    document.querySelectorAll(".btn-editar").forEach(btn => {
      btn.onclick = () => {
        const p = {
          id: btn.dataset.id,
          titulo: btn.dataset.titulo || "",
          responsable: btn.dataset.responsable || "",
          descripcion: btn.dataset.descripcion || "",
          estado: btn.dataset.estado || "",
          fecha: btn.dataset.fecha || "",
          ficha: btn.dataset.ficha || ""
        };
        abrirModalEdicion(p);
      };
    });

    document.querySelectorAll(".btn-eliminar").forEach(btn => {
      btn.onclick = () => {
        const id = btn.dataset.id;
        eliminar(id);
      };
    });
  }

  function abrirModalEdicion(p) {
    document.getElementById("edit-id").value = p.id;
    document.getElementById("edit-titulo").value = p.titulo;
    document.getElementById("edit-responsable").value = p.responsable;
    document.getElementById("edit-descripcion").value = p.descripcion;
    document.getElementById("edit-estado").value = p.estado;
    document.getElementById("edit-fecha").value = p.fecha;

    const fichaActual = document.getElementById("ficha-actual");
    if (p.ficha && p.ficha !== "null") {
      const safe = escapeHtml(p.ficha);
      fichaActual.innerHTML = `<a href="../uploads/proyectos/${encodeURIComponent(p.ficha)}" target="_blank">${safe}</a>`;
    } else {
      fichaActual.textContent = "No hay ficha cargada actualmente.";
    }

    const modal = new bootstrap.Modal(document.getElementById("modalEditar"));
    modal.show();
  }

  /* ------------ Crear ------------ */
  if (form) {
    form.addEventListener("submit", e => {
      e.preventDefault();
      const formData = new FormData();
      formData.append("titulo", form.titulo.value);
      formData.append("responsable", form.responsable.value);
      formData.append("descripcion", form.descripcion.value);
      formData.append("estado", form.estado.value);
      formData.append("fecha", form.fecha.value);

      if (form.ficha.files.length > 0) {
        formData.append("ficha", form.ficha.files[0]);
      }

      fetch("../api/api-proyectos.php", { method: "POST", body: formData })
        .then(res => res.json())
        .then(() => {
          form.reset();
          obtenerProyectos();
        });
    });
  }

  /* ------------ Editar ------------ */
  if (formEditar) {
    formEditar.addEventListener("submit", e => {
      e.preventDefault();

      const formData = new FormData();
      formData.append("id", document.getElementById("edit-id").value);
      formData.append("titulo", document.getElementById("edit-titulo").value);
      formData.append("responsable", document.getElementById("edit-responsable").value);
      formData.append("descripcion", document.getElementById("edit-descripcion").value);
      formData.append("estado", document.getElementById("edit-estado").value);
      formData.append("fecha", document.getElementById("edit-fecha").value);

      const archivoFicha = document.getElementById("edit-ficha").files[0];
      if (archivoFicha) {
        formData.append("ficha", archivoFicha);
      }

      fetch("../api/api-proyectos.php", { method: "POST", body: formData })
        .then(res => res.json())
        .then(() => {
          const modal = bootstrap.Modal.getInstance(document.getElementById("modalEditar"));
          modal.hide();
          obtenerProyectos();
        });
    });
  }

  /* ------------ Eliminar ------------ */
  function eliminar(id) {
    if (!confirm("¿Estás seguro de eliminar este proyecto?")) return;

    fetch(`../api/api-proyectos.php?id=${id}`, { method: "DELETE" })
      .then(res => res.json())
      .then(() => obtenerProyectos())
      .catch(err => console.error("Error al eliminar:", err));
  }

  /* ------------ Helpers ------------ */
  function escapeHtml(str) {
    return String(str ?? "").replace(/[&<>"']/g, s => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[s]));
  }

  // Para atributos data-*: escapamos comillas y reemplazamos saltos de línea
  function attr(str) {
    return String(str ?? "").replace(/"/g, "&quot;").replace(/\n/g, " ");
  }

  // Normaliza nombre de estado a clase css (para badges de cards)
  function cssState(estado) {
    // Mapeo por texto visible (coincide con los de tu select)
    const map = {
      "En curso": "En\\ curso",
      "Finalizado": "Finalizado",
      "Pendiente": "Pendiente"
    };
    return (map[estado] || estado).replace(/\s/g, "\\ ");
  }
});


