document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form");

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const campos = [
        "circunscripcion",
        "oficina_judicial",
        "responsable",
        "desde",
        "hasta",
        "rubro",
        "categoria",
        "empleado",
        "descripcion",
        "estado"
      ];

      for (const campo of campos) {
        const valor = document.getElementById(campo)?.value.trim();
        if (!valor) {
          alert("Por favor completá todos los campos obligatorios.");
          return;
        }
      }

      const datos = {
        circunscripcion: document.getElementById("circunscripcion").value,
        oficina_judicial: document.getElementById("oficina_judicial").value,
        responsable: document.getElementById("responsable").value,
        desde: document.getElementById("desde").value,
        hasta: document.getElementById("hasta").value,
        rubro: document.getElementById("rubro").value,
        categoria: document.getElementById("categoria").value,
        empleado: document.getElementById("empleado").value,
        descripcion: document.getElementById("descripcion").value,
        observaciones: document.getElementById("observaciones").value,
        estado: document.getElementById("estado").value
      };

      try {
        const response = await fetch("guardar_informe.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(datos),
        });

        const resultado = await response.json();

        if (resultado.success) {
          const modal = new bootstrap.Modal(document.getElementById("modalConfirmacion"));
          modal.show();
          form.reset();
        } else {
          alert("❌ Error al guardar el informe.");
        }
      } catch (error) {
        console.error("Error al enviar datos:", error);
        alert("Error de conexión.");
      }
    });
  }
});


