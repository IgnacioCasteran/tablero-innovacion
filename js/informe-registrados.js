// --- Normalización de Rubro ---
const RUBRO_MAP = {
  'organizacion': 'Organización',
  'rrhh': 'RRHH',
  'sistemas': 'Sistemas',
  'comunicacion': 'Comunicación',
  'recursos': 'Recursos',
  'recursos humanos': 'Recursos Humanos',
};
function prettyRubro(v) {
  if (v == null) return '';
  const key = String(v).trim().toLowerCase();
  return RUBRO_MAP[key] ?? v;
}
function safe(v, fallback = '—') {
  const s = (v ?? '').toString().trim();
  return s === '' ? fallback : s;
}

document.addEventListener('DOMContentLoaded', () => {
  cargarInformes();

  // Guardar edición
  const formEditar = document.getElementById('formEditar');
  if (formEditar) {
    formEditar.addEventListener('submit', async (e) => {
      e.preventDefault();

      const datos = {
        id: document.getElementById('editarId').value,
        responsable: document.getElementById('editarResponsable').value,
        empleado: document.getElementById('editarEmpleado').value,
        desde: document.getElementById('editarDesde').value,
        hasta: document.getElementById('editarHasta').value,
        rubro: document.getElementById('editarRubro').value,
        categoria: document.getElementById('editarCategoria').value,
        descripcion: document.getElementById('editarDescripcion').value,
        observaciones: document.getElementById('editarObservaciones').value,
      };

      try {
        const resp = await fetch('editar_informe.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(datos),
        });
        const resultado = await resp.json();

        if (resultado?.success) {
          bootstrap.Modal.getInstance(document.getElementById('modalEditar'))?.hide();
          cargarInformes();
          alert('✅ Informe actualizado correctamente.');
        } else {
          alert('❌ Error al editar el informe.');
        }
      } catch (err) {
        console.error('Error al editar:', err);
        alert('Error de conexión.');
      }
    });
  }

  // Confirmar eliminación
  const btnEliminar = document.getElementById('btnConfirmarEliminar');
  if (btnEliminar) {
    btnEliminar.addEventListener('click', async () => {
      if (!window.idAEliminar) return;
      try {
        const resp = await fetch('eliminar_informe.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: window.idAEliminar }),
        });
        const resultado = await resp.json();

        if (resultado?.success) {
          bootstrap.Modal.getInstance(document.getElementById('modalEliminar'))?.hide();
          cargarInformes();
          alert('✅ Informe eliminado.');
        } else {
          alert('❌ Error al eliminar.');
        }
      } catch (err) {
        console.error('Error al eliminar:', err);
        alert('Error de conexión.');
      }
    });
  }

  // Delegación de eventos para acciones en la tabla
  const tbody = document.getElementById('cuerpoTabla');
  if (tbody) {
    tbody.addEventListener('click', (e) => {
      const btnEdit = e.target.closest('.btn-editar');
      if (btnEdit) {
        abrirModalEditarDesdeBtn(btnEdit);
        return;
      }
      const btnDel = e.target.closest('.btn-eliminar');
      if (btnDel) {
        abrirModalEliminar(btnDel.dataset.id);
      }
    });
  }
});

async function cargarInformes() {
  try {
    const resp = await fetch('obtener_informes.php');
    const datos = await resp.json();

    // Normalizamos rubro
    const datosCanon = (Array.isArray(datos) ? datos : []).map((d) => ({
      ...d,
      rubro: prettyRubro(d.rubro),
    }));

    const tbody = document.getElementById('cuerpoTabla');
    if (!tbody) return;
    tbody.innerHTML = '';

    datosCanon.forEach((inf) => {
      const tr = document.createElement('tr');

      // celdas seguras con textContent
      addCell(tr, safe(inf.circunscripcion));
      addCell(tr, safe(inf.oficina_judicial));
      addCell(tr, safe(inf.responsable));
      addCell(tr, safe(inf.desde));
      addCell(tr, safe(inf.hasta));
      addCell(tr, safe(inf.rubro));       // ya capitalizado/normalizado
      addCell(tr, safe(inf.categoria));
      addCell(tr, safe(inf.empleado));
      addCell(tr, safe(inf.estado));
      addCell(tr, safe(inf.descripcion));
      addCell(tr, safe(inf.observaciones));

      // Acciones (según permisos opcionales del backend)
      const puedeEditar = ('can_edit' in inf) ? !!inf.can_edit : true;
      const puedeBorrar = ('can_delete' in inf) ? !!inf.can_delete : true;

      const tdAcc = document.createElement('td');
      tdAcc.className = 'text-center';

      if (puedeEditar || puedeBorrar) {
        const div = document.createElement('div');
        div.className = 'dropdown';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary btn-sm dropdown-toggle';
        btn.setAttribute('data-bs-toggle', 'dropdown');
        btn.setAttribute('aria-expanded', 'false');
        btn.textContent = 'Acciones';

        const ul = document.createElement('ul');
        ul.className = 'dropdown-menu';

        if (puedeEditar) {
          const liE = document.createElement('li');
          const aE = document.createElement('button');
          aE.type = 'button';
          aE.className = 'dropdown-item text-warning btn-editar';
          aE.textContent = '✏️ Editar';
          // dataset con los campos necesarios para el modal
          aE.dataset.id = inf.id;
          aE.dataset.responsable = inf.responsable ?? '';
          aE.dataset.empleado = inf.empleado ?? '';
          aE.dataset.desde = inf.desde ?? '';
          aE.dataset.hasta = inf.hasta ?? '';
          aE.dataset.rubro = inf.rubro ?? '';
          aE.dataset.categoria = inf.categoria ?? '';
          aE.dataset.descripcion = inf.descripcion ?? '';
          aE.dataset.observaciones = inf.observaciones ?? '';
          liE.appendChild(aE);
          ul.appendChild(liE);
        }

        if (puedeBorrar) {
          const liD = document.createElement('li');
          const aD = document.createElement('button');
          aD.type = 'button';
          aD.className = 'dropdown-item text-danger btn-eliminar';
          aD.textContent = '🗑️ Eliminar';
          aD.dataset.id = inf.id;
          liD.appendChild(aD);
          ul.appendChild(liD);
        }

        div.appendChild(btn);
        div.appendChild(ul);
        tdAcc.appendChild(div);
      } else {
        tdAcc.textContent = '—';
      }

      tr.appendChild(tdAcc);
      tbody.appendChild(tr);
    });

    // Notificar a filtros / gráficos con los datos normalizados
    if (typeof setDatos === 'function') setDatos(datosCanon);
  } catch (err) {
    console.error('Error al obtener informes:', err);
    alert('No se pudieron cargar los informes.');
  }
}

function addCell(tr, text) {
  const td = document.createElement('td');
  td.textContent = text;
  tr.appendChild(td);
}

// --- Modales ---
function abrirModalEditarDesdeBtn(btn) {
  document.getElementById('editarId').value = btn.dataset.id ?? '';
  document.getElementById('editarResponsable').value = btn.dataset.responsable ?? '';
  document.getElementById('editarEmpleado').value = btn.dataset.empleado ?? '';
  document.getElementById('editarDesde').value = btn.dataset.desde ?? '';
  document.getElementById('editarHasta').value = btn.dataset.hasta ?? '';
  document.getElementById('editarRubro').value = btn.dataset.rubro ?? '';
  document.getElementById('editarCategoria').value = btn.dataset.categoria ?? '';
  document.getElementById('editarDescripcion').value = btn.dataset.descripcion ?? '';
  document.getElementById('editarObservaciones').value = btn.dataset.observaciones ?? '';

  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

function abrirModalEliminar(id) {
  window.idAEliminar = id;
  new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
