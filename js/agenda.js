document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'es',
    buttonText: {
      today: 'Hoy'
    },

    // Toolbar: solo Hoy / prev / next y título centrado
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: ''
    },

    expandRows: true,
    height: '100%',
    contentHeight: '100%',
    handleWindowResize: true,
    fixedWeekCount: false,

    events: '../api/api-agenda.php',

    dateClick: function (info) {
      Swal.fire({
        title: 'Nuevo evento',
        html:
          `<input id="titulo" class="swal2-input" placeholder="Título">
           <textarea id="descripcion" class="swal2-textarea" placeholder="Descripción"></textarea>`,
        confirmButtonText: 'Guardar',
        focusConfirm: false,
        preConfirm: () => {
          const titulo = document.getElementById('titulo').value.trim();
          const descripcion = document.getElementById('descripcion').value.trim();
          return { titulo, descripcion };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('../api/api-agregar-evento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              titulo: result.value.titulo,
              descripcion: result.value.descripcion,
              fecha: info.dateStr
            })
          })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                calendar.refetchEvents();
                Swal.fire('¡Evento guardado!', '', 'success');
              } else {
                Swal.fire('Error al guardar', '', 'error');
              }
            });
        }
      });
    },

    eventClick: function (info) {
      const evento = info.event;
      const descripcion = evento.extendedProps.descripcion || 'Sin descripción';

      Swal.fire({
        title: 'Detalles del Evento',
        html:
          `<strong>Título:</strong> ${evento.title}<br>` +
          `<strong>Descripción:</strong> ${descripcion}<br>` +
          `<strong>Fecha:</strong> ${evento.startStr}`,
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'Editar',
        denyButtonText: 'Cerrar',
        cancelButtonText: 'Eliminar',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        preConfirm: () => {
          return Swal.fire({
            title: 'Editar Evento',
            html:
              `<input id="titulo" class="swal2-input" value="${evento.title}">` +
              `<textarea id="descripcion" class="swal2-textarea">${descripcion}</textarea>` +
              `<input id="fecha" type="date" class="swal2-input" value="${evento.startStr.substring(0, 10)}">`,
            focusConfirm: false,
            confirmButtonText: 'Guardar',
            preConfirm: () => {
              const nuevoTitulo = document.getElementById('titulo').value.trim();
              const nuevaDescripcion = document.getElementById('descripcion').value.trim();
              const nuevaFecha = document.getElementById('fecha').value;

              return fetch('../api/api-editar-evento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  id: evento.id,
                  titulo: nuevoTitulo,
                  descripcion: nuevaDescripcion,
                  fecha: nuevaFecha
                })
              })
                .then(res => res.json())
                .then(data => {
                  if (data.success) {
                    calendar.refetchEvents();
                    Swal.fire('¡Evento actualizado!', '', 'success');
                  } else {
                    Swal.fire('Error al actualizar', '', 'error');
                  }
                });
            }
          });
        }
      }).then(result => {
        if (result.dismiss === Swal.DismissReason.cancel) {
          Swal.fire({
            title: '¿Eliminar evento?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
          }).then(confirm => {
            if (confirm.isConfirmed) {
              fetch('../api/api-eliminar-evento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: evento.id })
              })
                .then(res => res.json())
                .then(data => {
                  if (data.success) {
                    calendar.refetchEvents();
                    Swal.fire('Eliminado', '', 'success');
                  } else {
                    Swal.fire('Error al eliminar', '', 'error');
                  }
                });
            }
          });
        }
      });
    },

    // Recordatorio siempre que falte 1 día para el evento
    eventDidMount: function (info) {
      const descripcion = info.event.extendedProps.descripcion || '';
      info.el.setAttribute('title', `${info.event.title}\n${descripcion}`);

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

  // Mantener tamaño correcto si cambia el viewport
  window.addEventListener('resize', () => {
    calendar.updateSize();
  });
});
