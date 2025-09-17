// js/proyectos.js

document.addEventListener("DOMContentLoaded", () => {
  const tablaBody  = document.querySelector("#tablaProyectos tbody");
  const cardsWrap  = document.getElementById("cardsProyectos");
  const form       = document.getElementById("formProyecto");
  const formEditar = document.getElementById("formEditar");

  obtenerProyectos();

  function obtenerProyectos() {
    fetch("../api/api-proyectos.php")
      .then(res => res.json())
      .then(data => {
        renderTabla(data);
        renderCards(data);
        attachActions();
      })
      .catch(err => console.error("Error al listar proyectos:", err));
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
          <button class="btn btn-primary btn-sm me-2 btn-archivos" data-id="${p.id}">📄 Ver archivos</button>
          <button class="btn btn-warning btn-sm me-2 btn-editar"
            data-id="${p.id}"
            data-titulo="${attr(p.titulo)}"
            data-responsable="${attr(p.responsable)}"
            data-descripcion="${attr(p.descripcion)}"
            data-estado="${attr(p.estado)}"
            data-fecha="${attr(p.fecha)}"
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
      const titulo       = escapeHtml(p.titulo || "");
      const responsable  = escapeHtml(p.responsable || "");
      const estado       = (p.estado || "Pendiente").trim();
      const fecha        = escapeHtml(p.fecha || "");
      const descripcion  = escapeHtml(p.descripcion || "");

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
        </div>
        <div class="pj-actions">
          <button class="btn btn-sm btn-primary btn-archivos" data-id="${p.id}">📄 Ver archivos</button>
          <button class="btn btn-sm btn-warning btn-editar"
            data-id="${p.id}"
            data-titulo="${attr(p.titulo)}"
            data-responsable="${attr(p.responsable)}"
            data-descripcion="${attr(p.descripcion)}"
            data-estado="${attr(p.estado)}"
            data-fecha="${attr(p.fecha)}"
          >✏️ Editar</button>
          <button class="btn btn-sm btn-danger btn-eliminar" data-id="${p.id}">🗑️ Eliminar</button>
        </div>
      `;
      cardsWrap.appendChild(card);
    });
  }

  /* ------------ Actions (archivos / editar / eliminar) ------------ */
  function attachActions() {
    // Ver archivos (usa la función global del modal definida en proyectos.php)
    document.querySelectorAll(".btn-archivos").forEach(btn => {
      btn.onclick = () => {
        const id = btn.dataset.id;
        if (typeof window.abrirArchivos === "function") {
          window.abrirArchivos(id);
        } else {
          alert("La función 'abrirArchivos' no está definida. Verificá el modal en proyectos.php");
        }
      };
    });

    // Editar
    document.querySelectorAll(".btn-editar").forEach(btn => {
      btn.onclick = () => {
        const p = {
          id: btn.dataset.id,
          titulo: btn.dataset.titulo || "",
          responsable: btn.dataset.responsable || "",
          descripcion: btn.dataset.descripcion || "",
          estado: btn.dataset.estado || "",
          fecha: btn.dataset.fecha || ""
        };
        abrirModalEdicion(p);
      };
    });

    // Eliminar
    document.querySelectorAll(".btn-eliminar").forEach(btn => {
      btn.onclick = () => eliminar(btn.dataset.id);
    });
  }

  function abrirModalEdicion(p) {
    document.getElementById("edit-id").value = p.id;
    document.getElementById("edit-titulo").value = p.titulo;
    document.getElementById("edit-responsable").value = p.responsable;
    document.getElementById("edit-descripcion").value = p.descripcion;
    document.getElementById("edit-estado").value = p.estado;
    document.getElementById("edit-fecha").value = p.fecha;

    // Texto fijo (los adjuntos se ven/gestionan en “Ver archivos”)
    const fichaActual = document.getElementById("ficha-actual");
    if (fichaActual) {
      fichaActual.textContent = "Usá “Ver archivos” para ver/eliminar.";
    }

    new bootstrap.Modal(document.getElementById("modalEditar")).show();
  }

  /* ------------ Crear (alta) ------------ */
  if (form) {
    form.addEventListener("submit", e => {
      e.preventDefault();

      const fd = new FormData();
      fd.append("titulo", form.titulo.value);
      fd.append("responsable", form.responsable.value);
      fd.append("descripcion", form.descripcion.value);
      fd.append("estado", form.estado.value);
      fd.append("fecha", form.fecha.value);

      // múltiples archivos (input id="fichas" name="fichas[]")
      const filesAlta = form.querySelector("#fichas")?.files || [];
      for (const f of filesAlta) fd.append("fichas[]", f);

      fetch("../api/api-proyectos.php", { method: "POST", body: fd })
        .then(res => res.json())
        .then(() => {
          form.reset();
          obtenerProyectos();
        })
        .catch(err => console.error("Error al crear proyecto:", err));
    });
  }

  /* ------------ Editar ------------ */
  if (formEditar) {
    formEditar.addEventListener("submit", e => {
      e.preventDefault();

      const fd = new FormData();
      fd.append("id", document.getElementById("edit-id").value);
      fd.append("titulo", document.getElementById("edit-titulo").value);
      fd.append("responsable", document.getElementById("edit-responsable").value);
      fd.append("descripcion", document.getElementById("edit-descripcion").value);
      fd.append("estado", document.getElementById("edit-estado").value);
      fd.append("fecha", document.getElementById("edit-fecha").value);

      // múltiples archivos adicionales en edición (input id="edit-fichas" name="edit-fichas[]")
      const filesEdit = document.getElementById("edit-fichas")?.files || [];
      for (const f of filesEdit) fd.append("edit-fichas[]", f);

      fetch("../api/api-proyectos.php", { method: "POST", body: fd })
        .then(res => res.json())
        .then(() => {
          const modal = bootstrap.Modal.getInstance(document.getElementById("modalEditar"));
          modal.hide();
          // limpiamos el input de archivos del modal
          const inputEdit = document.getElementById("edit-fichas");
          if (inputEdit) inputEdit.value = "";
          obtenerProyectos();
        })
        .catch(err => console.error("Error al editar proyecto:", err));
    });
  }

  /* ------------ Eliminar ------------ */
  function eliminar(id) {
    if (!confirm("¿Estás seguro de eliminar este proyecto?")) return;

    fetch(`../api/api-proyectos.php?id=${encodeURIComponent(id)}`, { method: "DELETE" })
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
    const map = { "En curso": "En\\ curso", "Finalizado": "Finalizado", "Pendiente": "Pendiente" };
    return (map[estado] || estado).replace(/\s/g, "\\ ");
  }
});

