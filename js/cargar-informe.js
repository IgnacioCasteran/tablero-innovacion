document.addEventListener("DOMContentLoaded", function () {
  const form               = document.querySelector("form");
  const categoriaSelect    = document.getElementById("categoriaSelect");
  const categoriaInput     = document.getElementById("categoriaInput");
  const oficinaSelect      = document.getElementById("oficina_judicial");
  const circSelect         = document.getElementById("circunscripcion");
  const responsableInput   = document.getElementById("responsable");
  const empleadoSelect     = document.getElementById("empleado");

  // ===== CATEGORÍAS (tu versión capitalizada) =====
  const categoriasPorOficina = {
    PENAL: [
      "Otros requerimientos",
      "Concursos de ascenso",
      "Bonificaciones",
      "Licencias",
      "Ingreso",
      "Sustituciones"
    ],
    CIVIL: [
      "Otras",
      "Licencias",
      "Otros requerimientos",
      "General",
      "Bonificaciones",
      "Concursos de ascenso",
      "Sustituciones",
      "Ingreso",
      "Solicitudes SIGE"
    ],
    FAMILIA: [
      "Concursos de ascenso",
      "General",
      "Bonificaciones",
      "Licencias",
      "Ingreso",
      "Otros requerimientos",
      "Sustituciones"
    ]
  };

  function normalizarOficina(oficina) {
    const o = (oficina || "").toLowerCase();
    // Penal (incluye sede suelta "Judicial Penal" o sub-sedes de III CJ)
    if (o.includes("penal")) return "PENAL";
    if (o === "ciudad general acha" || o === "ciudad 25 de mayo") return "PENAL";
    // Civil
    if (o.includes("gestión común civil") || o.includes("gestion comun civil")) return "CIVIL";
    // Familia
    if (
      o.includes("gestión judicial de familia") ||
      o.includes("gestion judicial de familia") ||
      o.includes("gestión común familia") ||
      o.includes("gestion comun familia")
    ) return "FAMILIA";
    return null;
  }

  function poblarSelect(selectEl, valores) {
    if (!selectEl) return;
    selectEl.innerHTML = "";
    const ph = document.createElement("option");
    ph.value = "";
    ph.textContent = "Seleccionar";
    ph.disabled = true;
    ph.selected = true;
    ph.hidden = true;
    selectEl.appendChild(ph);

    valores.forEach(v => {
      const opt = document.createElement("option");
      opt.value = v;
      opt.textContent = v;
      selectEl.appendChild(opt);
    });
  }

  function actualizarCategorias() {
    const key = normalizarOficina(oficinaSelect.value);
    if (key && categoriasPorOficina[key]) {
      poblarSelect(categoriaSelect, categoriasPorOficina[key]);
      // Mostrar select, ocultar input
      categoriaSelect.classList.remove("d-none");
      categoriaInput.classList.add("d-none");
      categoriaSelect.value = "";
      categoriaInput.value = "";
    } else {
      // Mostrar input libre, ocultar select
      categoriaInput.classList.remove("d-none");
      categoriaSelect.classList.add("d-none");
      categoriaSelect.value = "";
    }
  }
  // Exponer para que pueda ser llamada desde actualizarOficinas() global del HTML
  window.actualizarCategorias = actualizarCategorias;

  // ===== EMPLEADOS (según Circ + Oficina) =====
  // Claves: PENAL_I, PENAL_II, PENAL_III, PENAL_IV, CIVIL, FAMILIA_I, FAMILIA_II
  const empleadosPorClave = {
    PENAL_III: [
      "NO CORRESPONDE",
      "AMPUDIA, Orlando Javier",
      "BRITOS, Patricia Alejandra",
      "BUSTAMANTE, Maira Pamela",
      "CABRERA, Andrea Liliana",
      "CALDERON, Eloisa",
      "DIAZ, Ester Nélida",
      "DOMINGUEZ, José Oscar",
      "PATIÑO, Patricia Eugenia",
      "TRIPAILAO, Corina Andrea",
      "VIVIER, Verónica Beatriz",
      "ZAPPA, Mario Rubén Favio"
    ],
    PENAL_I: [
      "NO CORRESPONDE",
      "BARETTO, Ivana Daniela",
      "BARTEL, Valeria Paola",
      "BLANCO, Héctor Eduardo",
      "BRON, Adriana Ethel",
      "CARREIRA, María Gisela Belén",
      "CARRO, María Belén",
      "CHAVES, Gabriela Karina",
      "CONTI, Rita María Belén",
      "COSENTINO, Pablo Alfredo",
      "COSTABEL, María Daniela",
      "DOSIO, Nancy Mariela",
      "ESTRADA, Noelia Belén",
      "GALDÍN, María Sol",
      "GARCIA, Paula Yanina",
      "GATIVA VELAZQUEZ, Claudia Gabriela",
      "GONZÁLES RÍOS, Azucena",
      "GUZMÁN, Luciana Paola",
      "HERGENREDER, María Silvina",
      "LAMBERT, Mariana",
      "LEMA, Fernanda María",
      "MALDONADO, Jimena",
      "MICHAUX, Andrea Noelia",
      "MOREYRA, Pamela Gisel",
      "MUÑOZ, Lourdes Guadalupe",
      "OCHOA, Yanina Soledad",
      "OLGUÍN, Tatiana Soledad",
      "OLIVIERI, Cecilia Beatriz",
      "PAEZ, Juan Fernando",
      "PALAVECINO, Maria Soledad",
      "ROJO, Paola Irene",
      "QUIROGA, Adriana Cecilia",
      "SANCHEZ, Stella Maris",
      "SCHIEL, Malvina Luján",
      "SCHOLL, Flavia Silvina",
      "SERRANO, Laura",
      "TURRIÓN, María Elisa",
      "VENDRAMINI, Ana María",
      "VILLAREAL, Patricia Andrea",
      "ZAPPA, Gisela Belén"
    ],
    PENAL_II: [
      "NO CORRESPONDE",
      "ACOSTA, María Rita",
      "BALVIDARES, María Eugenia",
      "CALLOVI, Sofía Guillermina",
      "CARRASCO, Julieta",
      "CORNEJO, Silvina",
      "DEL POZO, María Mercedes",
      "ENCINAS, Daiana Solange",
      "ENCINA, Jésica Romina",
      "FERRERO, María Ester",
      "GALIZZI AGUIRRE, Vanesa Eliana",
      "GARCÍA IRASTORZA, Mario Félix",
      "GARCÍA, Patricia Inés",
      "GOMEZ, Nora Cristina",
      "Juan, María Fernanda",
      "LINAZA HOLZMAN, Jésica",
      "PASCUAL, Fernanda Alejandra",
      "PELIZZARI, Mariano Gabriel",
      "PESSANA, María Jimena",
      "PINO, Gabriel Alberto",
      "PIRAS, Adriana del Valle",
      "RAMOS CECCOPERI, Maria Carolina",
      "SAGRADO, Yanina Gisella",
      "SAINZ Johana",
      "SAN MARTÍN, Abel Norberto",
      "SARDIÑA ORDOÑEZ, Cecilia Mariel",
      "SCHONHEITER, Adriana Mercedes",
      "VICENTE, Pablo Sebastián"
    ],
    CIVIL: [
      "NO CORRESPONDE",
      "AGUIAR, Virginia Mercedes",
      "ALBORNOZ ARANDA, Hugo Carlos",
      "ALVAREZ, Ana Lia",
      "BAEZ SEVILLA, Aylén",
      "BARTHE, Silvina Mabel",
      "BENAVIDES, Rocío Esther",
      "BUFFA Eliana Pamela",
      "BUSTAMANTE, Héctor Angel",
      "CHEREQUE, Lucila Anahí",
      "CORRALES, Federico Marcelo",
      "D´ADAM, Ramón Luis",
      "DAL SANTO, Diego Andrés",
      "DE LA IGLESIA, Melina Marcela",
      "FERRERO, Eliana Mariel",
      "GARCÍA, Guadalupe",
      "GONZALEZ, Analia Inés",
      "HEICK,Nadia Florencia",
      "LUCERO, Nancy Mercedes",
      "MANSILLA, Maria de los Angeles",
      "MARTÍN DASSO, Guadalupe",
      "MORENO, Emilce Noelia",
      "MUÑOZ CASTRO, Augusto Hernán",
      "NANTON, María Natalia",
      "OLIVIA, Rita Claudia",
      "ORDOÑEZ, María Paula",
      "ORIHUELA, Mariana",
      "PEREZ, Mauro Nicolás",
      "PERRONE, Analia",
      "POMASKI, Cintia Vanina",
      "PORTALUPPI, Ana Sol",
      "QUINN, Luciana",
      "RIELA, Domingo Ceferino",
      "RIO, Rubén Tomás",
      "RODRIGUEZ, Katherine",
      "SCHREIBER, Noelia Romina",
      "SEGALA, Valentina Patricia",
      "SORIA, Agustina Cecilia",
      "URDANIZ, María Lis",
      "VICENTE, Marisa Analia",
      "VILLABA, Matías Ezequiel",
      "WIGAND, Ezequiel"
    ],
    FAMILIA_I: [
      "NO CORRESPONDE",
      "AGUERRE, Sebastián",
      "ALVAREZ, María Claudia",
      "CHAVES, Naiara Aurora",
      "CIAFFONI, Lara Maitén",
      "CONY, María Silvina",
      "CORVALAN, Daniela Rosario",
      "DIEGO, Agustina Andrea",
      "EPINAL, María Laura",
      "FERNANDEZ, Martín Ezequiel",
      "FIGUEROA, Pamela Soledad Luján",
      "GENTILE, Alicia Gabriela",
      "GIUSTI, Juan Martín",
      "HERNER, Luis Alberto",
      "MARTINEZ ANTONIO, Emmanuel",
      "MIGUEL, Mónica",
      "MONDELO LEIVA, Facundo Luis",
      "MUÑOZ, Daniela Mabel",
      "REICHERT, Analia Soraya",
      "RIOS, Marcela Fabiana",
      "ROLDAN, Matías",
      "ROMANO, Daiana Natalí",
      "RUF RODRIGEZ, Eliana María",
      "VAN SCHAIK, Ana Carla"
    ],
    FAMILIA_II: [
      "NO CORRESPONDE",
      "ARRIOLA, Emiliano Darío",
      "AVILA, María del Valle",
      "CONTI, Gloria María Inés",
      "DALMASSO, Vanesa Natalia",
      "FRESNO, Valeria Liz",
      "GARCÍA, Martín Amílcar",
      "HUERTA, Vanesa Carina",
      "MERCADO, Sebastián",
      "MODARELLI, Verónica Andrea",
      "MONTOYA, María Juliana",
      "MUÑOZ, Claudia Elisabet",
      "NAVARRO, Pablo Mariano Luis",
      "OBIOLS, Gustavo Rubén",
      "OLIVER, Florencia Carolina",
      "PADRONES, Roxana Carina",
      "PARISI, Patricio Leonel",
      "PEREZ, Martín Ezequiel",
      "QUINTEROS, Vanesa Soledad",
      "RODRIGEZ BARRON, María de la Paz",
      "SAGRADO, Mariana Lis",
      "SANZ, Gabriela",
      "SCHEFFER, Nilda Rut",
      "VELASCO, Daiana Elisabet",
      "QUINTELA, Carlota Valentina",
      "MINETTI, Edit Ester"
    ],
    PENAL_IV: [
      "NO CORRESPONDE",
      "Borthiry, Camila"
    ]
  };

  function claveEmpleados(circ, oficina) {
    const c  = (circ || "").toUpperCase();     // I, II, III, IV
    const of = (oficina || "").toLowerCase();

    // Penal (incluye sub-sedes de III CJ)
    if (of.includes("penal") || of === "judicial penal" || of === "ciudad general acha" || of === "ciudad 25 de mayo") {
      if (c === "I")   return "PENAL_I";
      if (c === "II")  return "PENAL_II";
      if (c === "III") return "PENAL_III";
      if (c === "IV")  return "PENAL_IV";
    }

    // Civil (lista única)
    if (of.includes("gestión común civil") || of.includes("gestion comun civil")) {
      return "CIVIL";
    }

    // Familia (depende de circ I / II)
    if (of.includes("gestión judicial de familia") || of.includes("gestion judicial de familia")
        || of.includes("gestión común familia") || of.includes("gestion comun familia")) {
      if (c === "I")  return "FAMILIA_I";
      if (c === "II") return "FAMILIA_II";
    }

    return null;
  }

  function actualizarEmpleados() {
    if (!empleadoSelect) return;
    const clave = claveEmpleados(circSelect?.value, oficinaSelect?.value);
    const lista = clave && empleadosPorClave[clave] ? empleadosPorClave[clave] : ["NO CORRESPONDE"];
    poblarSelect(empleadoSelect, lista);
  }
  // Exponer global para que la llame actualizarOficinas() del HTML
  window.actualizarEmpleados = actualizarEmpleados;

  // ===== Responsable fijo por pestaña =====
  const RESPONSABLE_KEY = "pj_responsable_tab";
  if (responsableInput) {
    // Cargar si existe
    const respGuardado = sessionStorage.getItem(RESPONSABLE_KEY);
    if (respGuardado) {
      responsableInput.value = respGuardado;
    }
    // Guardar en vivo
    responsableInput.addEventListener("input", () => {
      sessionStorage.setItem(RESPONSABLE_KEY, responsableInput.value || "");
    });
  }

  // ===== Listeners =====
  if (oficinaSelect) {
    oficinaSelect.addEventListener("change", () => {
      actualizarCategorias();
      actualizarEmpleados();
    });
  }
  if (circSelect) {
    circSelect.addEventListener("change", () => {
      // En algunos flujos tu HTML repuebla oficinas con actualizarOficinas()
      actualizarCategorias();
      actualizarEmpleados();
    });
  }

  // ===== Submit =====
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      // Campos base obligatorios (sin 'categoria' porque es dinámico)
      const campos = [
        "circunscripcion",
        "oficina_judicial",
        "responsable",
        "desde",
        "hasta",
        "rubro",
        "empleado",
        "descripcion",
        "estado"
      ];

      for (const campo of campos) {
        const el = document.getElementById(campo);
        const valor = el && "value" in el ? String(el.value).trim() : "";
        if (!valor) {
          alert("Por favor completá todos los campos obligatorios.");
          return;
        }
      }

      // Categoría según control visible
      let categoriaValor = "";
      const selectVisible = !categoriaSelect.classList.contains("d-none");
      categoriaValor = (selectVisible ? categoriaSelect.value : categoriaInput.value).trim();
      if (!categoriaValor) {
        alert("Por favor completá la categoría.");
        return;
      }

      const datos = {
        circunscripcion: document.getElementById("circunscripcion").value,
        oficina_judicial: document.getElementById("oficina_judicial").value,
        responsable: document.getElementById("responsable").value,
        desde: document.getElementById("desde").value,
        hasta: document.getElementById("hasta").value,
        rubro: document.getElementById("rubro").value,
        categoria: categoriaValor,
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

          // Guardar el responsable antes de resetear (persistencia por pestaña)
          const responsableActual = responsableInput ? responsableInput.value : "";

          form.reset();

          // Restaurar responsable fijo y refrescar dependientes
          if (responsableInput) {
            responsableInput.value = responsableActual;
            sessionStorage.setItem(RESPONSABLE_KEY, responsableActual || "");
          }
          actualizarCategorias();
          actualizarEmpleados();
        } else {
          alert("❌ Error al guardar el informe.");
        }
      } catch (error) {
        console.error("Error al enviar datos:", error);
        alert("Error de conexión.");
      }
    });
  }

  // ===== Si desde el HTML llaman actualizarOficinas(), que también refresque estos =====
  // (Tu HTML ya tiene oficinasPorCircunscripcion y la función global actualizarOficinas)
  // Solo aseguramos que existan por si se invocan al cargar.
  actualizarCategorias();
  actualizarEmpleados();

  // === IMPORTANTE ===
  // Si este archivo también define 'function actualizarOficinas() { ... }' (como pegaste al final),
  // dejalo GLOBAL en el HTML (script inline) y NO lo redefinas aquí para evitar sombras de ámbito.
  // Desde ese global, ya podés llamar:
  //   if (typeof actualizarCategorias === "function") actualizarCategorias();
  //   if (typeof actualizarEmpleados  === "function") actualizarEmpleados();
});

