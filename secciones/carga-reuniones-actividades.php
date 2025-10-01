<?php
// secciones/agenda.php
require_once __DIR__ . '/../auth.php';
require_login();          // exige sesión
enforce_route_access();   // aplica restricciones por rol (coord solo Informes, STJ solo lectura)
render_readonly_ui();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cargar Reunión / Actividad</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background-color: #f4f6f9;
    }

    h1,
    h2 {
      color: #7c1c2c;
    }

    .card {
      border-left: 5px solid #7c1c2c;
    }

    label {
      font-weight: 500;
    }
  </style>
</head>

<body>
  <div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Cargar Reunión o Actividad</h2>
      <a href="reuniones-actividades.php" class="btn btn-outline-dark">
        <i class="bi bi-arrow-left-circle"></i> Volver
      </a>
    </div>

    <form id="formReunion" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Tipo</label>
        <select class="form-select" name="tipo" id="tipo" required>
          <option value="">Seleccionar</option>
          <option value="proyecto">Actividad</option>
          <option value="reunion">Reunión</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Proyecto / Tarea</label>
        <input type="text" class="form-control" name="tarea" required>
      </div>

      <!-- ESTADO (solo ACTIVIDADES) -->
      <div class="mb-3 d-none" id="grp-estado">
        <label class="form-label">Estado</label>
        <select class="form-select" name="estado" id="estado">
          <!-- se llena por JS -->
        </select>
      </div>

      <!-- ORGANISMO / PROYECTO ( ambos; obligatorio en REUNIONES ) -->
      <div class="mb-3 d-none" id="grp-organismo">
        <label class="form-label">
          Organismo / Proyecto
          <span class="text-muted" id="org-help"></span>
        </label>
        <select class="form-select" id="organismo" name="organismo">
          <!-- se llena por JS -->
        </select>
        <input type="text" class="form-control mt-2 d-none" id="organismo-otro"
          placeholder="Especificá el organismo/proyecto">
      </div>

      <div class="mb-3">
        <label class="form-label">Notas</label>
        <textarea class="form-control" name="notas" rows="3"></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Fecha de inicio</label>
        <input type="date" class="form-control" name="fecha_inicio">
      </div>

      <div class="mb-3">
        <label class="form-label">Fecha de finalización</label>
        <input type="date" class="form-control" name="fecha_fin">
      </div>

      <div class="mb-3 d-none" id="campo-asistentes">
        <label class="form-label">Asistentes</label>
        <input type="text" class="form-control" name="asistentes">
      </div>

      <div class="mb-3">
        <label class="form-label">Documentos adjuntos</label>
        <input
          type="file"
          class="form-control"
          name="archivos[]"
          id="archivos"
          accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
          multiple>
        <div class="form-text">Podés seleccionar varios a la vez.</div>
      </div>


      <button type="submit" class="btn btn-primary">Guardar</button>
      <a href="reuniones-actividades.php" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // ==========================
    // Catálogos
    // ==========================
    const ESTADOS_ACT = ['Completado', 'En curso', 'No iniciada', 'Bloqueada'];
    const ORGANISMOS = [
      // I CJ
      'I CJ - Oficina Judicial Penal',
      'I CJ - Oficina de Gestión Común Civil',
      'I CJ - Oficina de Gestión Judicial de Familia',
      // II CJ
      'II CJ - Oficina Judicial Penal',
      'II CJ - Oficina de Gestión Judicial de Familia',
      // III CJ
      'III CJ - Oficina Judicial Penal',
      'III CJ - Ciudad General Acha',
      'III CJ - Ciudad 25 de Mayo',
      // IV CJ
      'IV CJ - Judicial Penal'
    ];
    const OTRO_SENTINEL = '__OTRO__';

    // ==========================
    // Helpers UI
    // ==========================
    function fillSelect(select, values, addOtro = false) {
      select.innerHTML = '<option value="">Seleccionar...</option>';
      values.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v;
        opt.textContent = v;
        select.appendChild(opt);
      });
      if (addOtro) {
        const opt = document.createElement('option');
        opt.value = OTRO_SENTINEL;
        opt.textContent = 'Otro (especificar)';
        select.appendChild(opt);
      }
    }

    function applyTipoUI(tipo) {
      const grpEstado = document.getElementById('grp-estado');
      const grpOrg = document.getElementById('grp-organismo');
      const estadoSel = document.getElementById('estado');
      const orgSel = document.getElementById('organismo');
      const orgHelp = document.getElementById('org-help');
      const orgOtro = document.getElementById('organismo-otro');
      const asistentes = document.getElementById('campo-asistentes');

      // reset campos específicos
      estadoSel.required = false;
      orgSel.required = false;
      orgOtro.classList.add('d-none');
      orgOtro.value = '';

      if (tipo === 'proyecto') {
        grpEstado.classList.remove('d-none');
        grpOrg.classList.remove('d-none');
        asistentes.classList.add('d-none');
        fillSelect(estadoSel, ESTADOS_ACT, false);
        fillSelect(orgSel, ORGANISMOS, true);
        estadoSel.required = true; // obligatorio en actividades
        orgSel.required = false; // opcional en actividades
      } else if (tipo === 'reunion') {
        grpEstado.classList.add('d-none'); // no se usa estado
        grpOrg.classList.remove('d-none');
        asistentes.classList.remove('d-none');
        fillSelect(orgSel, ORGANISMOS, true);
        orgSel.required = true; // obligatorio en reuniones
      } else {
        // nada seleccionado
        grpEstado.classList.add('d-none');
        grpOrg.classList.add('d-none');
        asistentes.classList.add('d-none');
      }
    }

    // ==========================
    // Eventos
    // ==========================
    document.addEventListener('DOMContentLoaded', () => {
      const tipoSel = document.getElementById('tipo');
      const orgSel = document.getElementById('organismo');
      const orgOtro = document.getElementById('organismo-otro');

      // al cambiar tipo -> configurar UI
      tipoSel.addEventListener('change', () => applyTipoUI(tipoSel.value));

      // al cambiar organismo -> mostrar campo "otro"
      orgSel.addEventListener('change', () => {
        orgOtro.classList.toggle('d-none', orgSel.value !== OTRO_SENTINEL);
        if (orgSel.value !== OTRO_SENTINEL) orgOtro.value = '';
      });

      // estado inicial (oculto)
      applyTipoUI('');
    });

    // ==========================
    // Envío
    // ==========================
    document.getElementById('formReunion').addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = e.target;
      const fd = new FormData(form);

      const tipo = document.getElementById('tipo').value;
      const orgSel = document.getElementById('organismo');
      const orgOtro = document.getElementById('organismo-otro');
      const estadoSel = document.getElementById('estado');

      // Resolver valor de ORGANISMO
      let organismo = (orgSel && orgSel.value) ? orgSel.value.trim() : '';
      if (organismo === OTRO_SENTINEL) organismo = (orgOtro.value || '').trim();

      if (tipo === 'reunion') {
        if (!organismo) {
          Swal.fire('Falta completar', 'Especificá el organismo/proyecto.', 'warning');
          return;
        }
        fd.set('organismo', organismo);
        // compatibilidad: estado = organismo en reuniones
        fd.set('estado', organismo);
      } else if (tipo === 'proyecto') {
        // Estado requerido en actividades
        const estado = (estadoSel && estadoSel.value) ? estadoSel.value : '';
        if (!estado) {
          Swal.fire('Falta completar', 'Elegí el estado de la actividad.', 'warning');
          return;
        }
        fd.set('estado', estado);
        // Organismo opcional en actividades
        if (organismo) fd.set('organismo', organismo);
        else fd.delete('organismo');
      }

      try {
        const res = await fetch('../api/api-reuniones.php', {
          method: 'POST',
          body: fd
        });
        const data = await res.json();
        if (res.ok) {
          await Swal.fire({
            icon: 'success',
            title: '¡Guardado correctamente!',
            text: data.mensaje || 'OK'
          });
          form.reset();
          applyTipoUI('');
        } else {
          throw new Error(data.error || 'Error al guardar');
        }
      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: err.message || 'No se pudo guardar la reunión o actividad.'
        });
      }
    });
  </script>
</body>

</html>