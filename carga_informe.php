<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.html");
  exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Carga de Informe Periódico</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/estilos-index.css">
</head>

<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <nav class="col-md-3 col-lg-2 d-md-block sidebar p-4">
        <div class="logo-section">
          <img src="img/Poder_Judicial_logo.png" alt="Logo Poder Judicial">
          <h1>Poder Judicial<br><small>Provincia de La Pampa</small></h1>
        </div>

        <ul class="nav flex-column mt-4">
          <li class="nav-item"><a class="nav-link active" href="#">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="informe-registrados.php">Informes Registrados</a></li>
          <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Cerrar sesión</a></li>
        </ul>

      </nav>

      <!-- Main Content -->
      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-5 py-4">
        <h2 class="text-danger mb-4">Carga de Informe Periódico</h2>
        <form class="bg-white p-4 shadow rounded">
          <div class="mb-3">
            <label for="circunscripcion" class="form-label">Circunscripción</label>
            <select class="form-select" id="circunscripcion" onchange="actualizarOficinas()">
              <option value="" disabled selected hidden>Seleccionar</option>
              <option value="I">I Circunscripción Judicial</option>
              <option value="II">II Circunscripción Judicial</option>
              <option value="III">III Circunscripción Judicial</option>
              <option value="IV">IV Circunscripción Judicial</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="oficina_judicial" class="form-label">Oficina Judicial</label>
            <select class="form-select" id="oficina_judicial">
              <option value="" disabled selected hidden>Seleccionar una circunscripción primero</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="responsable" class="form-label">Responsable</label>
            <input type="text" class="form-control" id="responsable" placeholder="Nombre del responsable" />
          </div>


          <div class="row mb-3">
            <div class="col">
              <label for="desde" class="form-label">Desde</label>
              <input type="date" class="form-control" id="desde" />
            </div>
            <div class="col">
              <label for="hasta" class="form-label">Hasta</label>
              <input type="date" class="form-control" id="hasta" />
            </div>
          </div>

          <!-- Rubro -->
          <div class="mb-3">
            <label for="rubro" class="form-label">Rubro</label>
            <select class="form-select" id="rubro">
              <option value="" disabled selected hidden>Seleccionar</option>
              <option value="Organización">Organización</option>
              <option value="RRHH">RRHH</option>
              <option value="Sistemas">Sistemas</option>
              <option value="Comunicación">Comunicación</option>
              <option value="Recursos">Recursos</option>
            </select>
          </div>


          <!-- Categoría -->
          <div class="mb-3" id="categoriaGroup">
            <label class="form-label">Categoría</label>
            <!-- Select dinámico (oculto por defecto) -->
            <select class="form-select d-none" id="categoriaSelect">
              <option value="" disabled selected hidden>Seleccionar</option>
            </select>
            <!-- Input libre (visible si la oficina NO tiene categorías predefinidas) -->
            <input type="text" class="form-control" id="categoriaInput" placeholder="Escribí la categoría" />
          </div>


          <!-- Empleado -->
          <div class="mb-3">
            <label for="empleado" class="form-label">Empleado</label>
            <select class="form-select" id="empleado">
              <option value="" disabled selected hidden>Seleccionar</option>
              <!-- Se completa dinámicamente según Oficina/Circunscripción -->
            </select>
          </div>


          <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label for="observaciones" class="form-label">Observaciones</label>
            <textarea class="form-control" id="observaciones" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label for="estado" class="form-label">Estado</label>
            <select class="form-select" id="estado">
              <option value="Inicial">Inicial</option>
              <option value="En proceso">En proceso</option>
              <option value="Finalizado">Finalizado</option>
            </select>
          </div>

          <button type="submit" class="btn btn-danger">Guardar Informe</button>
          <div id="alerta" class="alert mt-3 d-none" role="alert"></div>
        </form>
      </main>
    </div>
  </div>
  <!-- Modal de Confirmación -->
  <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-success">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="modalConfirmacionLabel">Informe Guardado</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-center">
          ✅ El informe se guardó correctamente.
        </div>
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const oficinasPorCircunscripcion = {
      I: [
        "Oficina Judicial Penal",
        "Oficina de Gestión Común Civil",
        "Oficina de Gestión Judicial de Familia"
      ],
      II: [
        "Oficina Judicial Penal",
        "Oficina de Gestión Judicial de Familia"
      ],
      III: [
        "Oficina Judicial Penal",
        "Ciudad General Acha",
        "Ciudad 25 de Mayo"
      ],
      IV: [
        "Judicial Penal"
      ]
    };

    function actualizarOficinas() {
      const circ = document.getElementById("circunscripcion").value;
      const oficinaSelect = document.getElementById("oficina_judicial");
      oficinaSelect.innerHTML = "<option value='' disabled selected hidden>Seleccionar una oficina</option>";

      if (oficinasPorCircunscripcion[circ]) {
        oficinasPorCircunscripcion[circ].forEach(oficina => {
          const option = document.createElement("option");
          option.value = oficina;
          option.textContent = oficina;
          oficinaSelect.appendChild(option);
        });
      }

      oficinaSelect.value = "";
      if (typeof actualizarCategorias === "function") actualizarCategorias();
      if (typeof actualizarEmpleados === "function") actualizarEmpleados();
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cargar-informe.js"></script>

</body>

</html>