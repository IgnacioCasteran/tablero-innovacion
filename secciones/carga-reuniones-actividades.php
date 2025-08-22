<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: ../login/login.html");
  exit();
}
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
        <select class="form-select" name="tipo" id="tipo" required onchange="mostrarCampos()">
          <option value="">Seleccionar</option>
          <option value="proyecto">Proyecto / Actividad</option>
          <option value="reunion">Reunión</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Proyecto / Tarea</label>
        <input type="text" class="form-control" name="tarea" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Estado</label>
        <select class="form-select" name="estado" id="estado" required>
          <option value="">Seleccionar estado</option>
        </select>
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

      <div class="mb-3" id="campo-asistentes" style="display:none;">
        <label class="form-label">Asistentes</label>
        <input type="text" class="form-control" name="asistentes">
      </div>

      <div class="mb-3">
        <label class="form-label">Documento adjunto</label>
        <input type="file" class="form-control" name="archivo" accept=".pdf,.doc,.docx">
      </div>

      <button type="submit" class="btn btn-primary">Guardar</button>
      <a href="reuniones-actividades.php" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>

  <script>
    function mostrarCampos() {
      const tipo = document.getElementById('tipo').value;
      document.getElementById('campo-asistentes').style.display = tipo === 'reunion' ? 'block' : 'none';
    }
  </script>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const tipoSelect = document.getElementById('tipo');
      const estadoSelect = document.getElementById('estado');
      const campoAsistentes = document.getElementById('campo-asistentes');

      const opcionesEstado = {
        proyecto: [
          "Completado",
          "En curso",
          "No iniciada",
          "Bloqueada"
        ],
        reunion: [
          "Proyecto Chat Bot",
          "Proyecto Ciudadanía",
          "OJ Penal",
          "OJ Civil",
          "OJ Familia",
          "Ejecución CyQ",
          "Otros"
        ]
      };

      tipoSelect.addEventListener('change', () => {
        const tipo = tipoSelect.value;

        // Mostrar u ocultar asistentes
        campoAsistentes.style.display = (tipo === 'reunion') ? 'block' : 'none';

        // Vaciar opciones actuales del select estado
        estadoSelect.innerHTML = '<option value="">Seleccionar estado</option>';

        // Agregar nuevas opciones según tipo
        if (opcionesEstado[tipo]) {
          opcionesEstado[tipo].forEach(estado => {
            const option = document.createElement('option');
            option.value = estado;
            option.textContent = estado;
            estadoSelect.appendChild(option);
          });
        }
      });
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function mostrarCampos() {
      const tipo = document.getElementById('tipo').value;
      document.getElementById('campo-asistentes').style.display = tipo === 'reunion' ? 'block' : 'none';
    }

    document.getElementById('formReunion').addEventListener('submit', function (e) {
      e.preventDefault();

      const form = e.target;
      const formData = new FormData(form);

      fetch('../api/api-reuniones.php', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          if (data.mensaje) {
            Swal.fire({
              icon: 'success',
              title: '¡Guardado correctamente!',
              text: data.mensaje,
              confirmButtonText: 'OK'
            }).then(() => {
              form.reset();
              mostrarCampos();
            });
          } else {
            Swal.fire({
              icon: 'warning',
              title: 'Ups...',
              text: 'Ocurrió un error inesperado.',
            });
          }
        })
        .catch(() => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo guardar la reunión o actividad.',
          });
        });
    });
  </script>



</body>

</html>