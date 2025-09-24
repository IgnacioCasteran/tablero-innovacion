document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');

  // ===== Catálogo de categorías (value para DB, label para UI) =====
  // Quedan: General, Reunión, Licencia, Audiencia, Actividad
  const CATEGORIAS = [
    { value: 'general',   label: 'General' },
    { value: 'reunion',   label: 'Reunión' },
    { value: 'licencia',  label: 'Licencia' },
    { value: 'audiencia', label: 'Audiencia' },
    { value: 'actividad', label: 'Actividad' }
  ];
  const catOptions = CATEGORIAS.map(c => `<option value="${c.value}">${c.label}</option>`).join('');

  // ===== Colores por categoría (fallback por si el CSS no aplica) =====
  const COLOR_MAP = {
    general:   { bg: '#7c1c2c', border: '#7c1c2c', text: '#ffffff' }, // bordó
    reunion:   { bg: '#0d6efd', border: '#0d6efd', text: '#ffffff' }, // azul
    licencia:  { bg: '#198754', border: '#198754', text: '#ffffff' }, // verde
    audiencia: { bg: '#6f42c1', border: '#6f42c1', text: '#ffffff' }, // violeta
    actividad: { bg: '#fd7e14', border: '#fd7e14', text: '#ffffff' }  // naranja
  };
  const fallbackColor = COLOR_MAP.general;

  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: 'es',
    initialView: 'dayGridMonth',

    // Toolbar
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },

    // ====== BANDAS HORARIAS ======
    slotMinTime: '07:00:00',     // primera franja visible
    slotMaxTime: '20:00:00',     // última franja visible
    slotDuration: '00:30:00',    // paso entre slots
    businessHours: [
      { daysOfWeek: [1,2,3,4,5], startTime: '07:00', endTime: '14:00' } // Lun-Vie
      // { daysOfWeek: [6], startTime: '09:00', endTime: '12:00' },     // Sáb (opcional)
    ],
    nowIndicator: true,

    // sizing
    expandRows: true,
    height: '100%',
    contentHeight: '100%',
    handleWindowResize: true,
    fixedWeekCount: false,

    // eventos
    events: '../api/api-agenda.php',
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },

    // Agregamos clase por categoría para permitir estilado vía CSS (.cat-xxxx)
    eventClassNames: function (arg) {
      const cat = (arg.event.extendedProps && arg.event.extendedProps.categoria) || 'general';
      return [`cat-${cat}`];
    },

    // click en un día/slot -> crear evento
    selectable: true,
    selectMirror: true,
    select: async function (info) {
      // En timeGrid puede venir hora; mostramos solo la fecha en el modal
      const fecha = info.startStr.slice(0, 10);

      const { value: formValues } = await Swal.fire({
        title: 'Nuevo evento',
        html: `
          <input id="swal-titulo" class="swal2-input" placeholder="Título">
          <textarea id="swal-desc" class="swal2-textarea" placeholder="Descripción (opcional)"></textarea>
          <div style="display:flex;gap:8px;justify-content:center">
            <input id="swal-hini" type="time" class="swal2-input" style="width:140px" placeholder="Hora inicio">
            <input id="swal-hfin" type="time" class="swal2-input" style="width:140px" placeholder="Hora fin">
          </div>
          <select id="swal-cat" class="swal2-input" style="width:100%;max-width:300px">
            ${catOptions}
          </select>
          <div class="swal2-html-container" style="margin-top:4px">Fecha: <b>${fecha}</b></div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
          const titulo = document.getElementById('swal-titulo').value.trim();
          const descripcion = document.getElementById('swal-desc').value.trim();
          const hini = document.getElementById('swal-hini').value;
          const hfin = document.getElementById('swal-hfin').value;
          const categoria = document.getElementById('swal-cat').value || 'general';

          if (!titulo) {
            Swal.showValidationMessage('Poné un título');
            return false;
          }
          if (hfin && !hini) {
            Swal.showValidationMessage('Si ponés hora fin, también hora inicio');
            return false;
          }
          if (hini && hfin && hfin <= hini) {
            Swal.showValidationMessage('La hora fin debe ser mayor que la de inicio');
            return false;
          }
          return { titulo, descripcion, fecha, hora_inicio: hini, hora_fin: hfin, categoria };
        }
      });

      calendar.unselect();
      if (!formValues) return;

      try {
        const body = new URLSearchParams(formValues);
        const res = await fetch('../api/api-agregar-evento.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.error || 'Error al guardar');

        Swal.fire('¡Evento guardado!', '', 'success');
        calendar.refetchEvents();
      } catch (e) {
        Swal.fire('Error', e.message || 'No se pudo guardar', 'error');
      }
    },

    // click sobre un evento: editar o eliminar
    eventClick: async function (info) {
      const e = info.event;
      const fecha = e.startStr.slice(0, 10);
      const descripcion = e.extendedProps?.descripcion || '';

      // si tiene hora, las leemos; si es allDay, vacías
      const hIni = e.allDay ? '' : (e.start?.toTimeString().slice(0,5) || '');
      const hFin = (e.end && !e.allDay) ? e.end.toTimeString().slice(0,5) : '';
      const catActual = e.extendedProps?.categoria || 'general';

      const popup = await Swal.fire({
        title: 'Evento',
        html: `
          <input id="swal-titulo" class="swal2-input" value="${e.title || ''}" placeholder="Título">
          <textarea id="swal-desc" class="swal2-textarea" placeholder="Descripción">${descripcion}</textarea>
          <div style="display:flex;gap:8px;justify-content:center">
            <input id="swal-hini" type="time" class="swal2-input" style="width:140px" value="${hIni}">
            <input id="swal-hfin" type="time" class="swal2-input" style="width:140px" value="${hFin}">
          </div>
          <select id="swal-cat" class="swal2-input" style="width:100%;max-width:300px">
            ${CATEGORIAS.map(c => `<option value="${c.value}" ${c.value===catActual?'selected':''}>${c.label}</option>`).join('')}
          </select>
          <div class="swal2-html-container" style="margin-top:4px">Fecha: <b>${fecha}</b></div>
        `,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        denyButtonText: 'Eliminar',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#3085d6',
        denyButtonColor: '#d33',
        preConfirm: () => {
          const titulo = document.getElementById('swal-titulo').value.trim();
          const desc = document.getElementById('swal-desc').value.trim();
          const hini = document.getElementById('swal-hini').value;
          const hfin = document.getElementById('swal-hfin').value;
          const categoria = document.getElementById('swal-cat').value || 'general';

          if (!titulo) {
            Swal.showValidationMessage('Poné un título');
            return false;
          }
          if (hfin && !hini) {
            Swal.showValidationMessage('Si ponés hora fin, también hora inicio');
            return false;
          }
          if (hini && hfin && hfin <= hini) {
            Swal.showValidationMessage('La hora fin debe ser mayor que la de inicio');
            return false;
          }
          return { titulo, descripcion: desc, fecha, hora_inicio: hini, hora_fin: hfin, categoria };
        }
      });

      if (popup.isDenied) {
        // eliminar
        const ok = await Swal.fire({
          icon: 'warning',
          title: '¿Eliminar evento?',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#d33'
        });
        if (!ok.isConfirmed) return;

        try {
          const res = await fetch('../api/api-eliminar-evento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: e.id })
          });
          const data = await res.json();
          if (!res.ok || !data.success) throw new Error(data.error || 'Error al eliminar');

          Swal.fire('Eliminado', '', 'success');
          calendar.refetchEvents();
        } catch (err) {
          Swal.fire('Error', err.message || 'No se pudo eliminar', 'error');
        }
        return;
      }

      if (!popup.isConfirmed) return; // cerrar

      // guardar edición
      try {
        const payload = { id: e.id, ...popup.value };
        const res = await fetch('../api/api-editar-evento.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.error || 'Error al actualizar');

        Swal.fire('¡Evento actualizado!', '', 'success');
        calendar.refetchEvents();
      } catch (err) {
        Swal.fire('Error', err.message || 'No se pudo actualizar', 'error');
      }
    },

    // tooltip + color por categoría + recordatorio 1 día antes
    eventDidMount: function (info) {
      const descripcion = info.event.extendedProps.descripcion || '';
      info.el.setAttribute('title', `${info.event.title}\n${descripcion}`);

      // === Color por categoría (inline, para que no lo pisen otros CSS) ===
      const cat = (info.event.extendedProps && info.event.extendedProps.categoria) || 'general';
      const col = COLOR_MAP[cat] || fallbackColor;
      // el elemento raíz del evento
      info.el.style.backgroundColor = col.bg;
      info.el.style.borderColor = col.border;
      info.el.style.color = col.text;

      // extra: a veces el texto está en nodos internos:
      const mains = info.el.querySelectorAll('.fc-event-title, .fc-event-time, .fc-event-main, .fc-event-main-frame, .fc-list-event-title a');
      mains.forEach(n => { n.style.color = col.text; });

      // === Recordatorio simple: 1 día antes ===
      const normalize = d => new Date(d.getFullYear(), d.getMonth(), d.getDate());
      const hoy = normalize(new Date());
      const fechaEvento = normalize(info.event.start);
      const diffDays = Math.round((fechaEvento - hoy) / (1000 * 60 * 60 * 24));
      if (diffDays === 1) {
        Swal.fire({
          icon: 'info',
          title: '¡Recordatorio!',
          text: `Mañana es: ${info.event.title}`,
          footer: descripcion
        });
      }
    }
  });

  calendar.render();

  // tamaño correcto si cambia el viewport
  window.addEventListener('resize', () => calendar.updateSize());
});