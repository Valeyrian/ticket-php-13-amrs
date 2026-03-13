document.addEventListener("DOMContentLoaded", () => {
  // ====== SELECTS: couleurs dynamiques ======

  const statusSelect = document.querySelector(".status-select");
  const prioritySelect = document.querySelector(".priority-select");
  const typeSelect = document.querySelector(".type-select");

  // Couleurs par statut
  const statusColors = {
    nouveau: {
      bg: "rgba(52, 152, 219, 0.2)",
      color: "#3498db",
      border: "rgba(52, 152, 219, 0.5)",
    },
    en_cours: {
      bg: "rgba(84, 197, 177, 0.2)",
      color: "#54C5B1",
      border: "rgba(84, 197, 177, 0.5)",
    },
    en_attente_client: {
      bg: "rgba(255, 193, 7, 0.2)",
      color: "#ffc107",
      border: "rgba(255, 193, 7, 0.5)",
    },
    termine: {
      bg: "rgba(46, 204, 113, 0.2)",
      color: "#2ecc71",
      border: "rgba(46, 204, 113, 0.5)",
    },
    a_valider: {
      bg: "rgba(255, 184, 34, 0.2)",
      color: "#ffb822",
      border: "rgba(255, 184, 34, 0.5)",
    },
    valide: {
      bg: "rgba(46, 204, 113, 0.2)",
      color: "#2ecc71",
      border: "rgba(46, 204, 113, 0.5)",
    },
    refuse: {
      bg: "rgba(255, 71, 87, 0.2)",
      color: "#ff4757",
      border: "rgba(255, 71, 87, 0.5)",
    },
    ferme: {
      bg: "rgba(149, 165, 166, 0.2)",
      color: "#95a5a6",
      border: "rgba(149, 165, 166, 0.5)",
    },
  };

  // Couleurs par priorité
  const priorityColors = {
    haute: {
      bg: "rgba(255, 71, 87, 0.2)",
      color: "#ff4757",
      border: "rgba(255, 71, 87, 0.5)",
    },
    moyenne: {
      bg: "rgba(255, 193, 7, 0.2)",
      color: "#ffc107",
      border: "rgba(255, 193, 7, 0.5)",
    },
    basse: {
      bg: "rgba(46, 204, 113, 0.2)",
      color: "#2ecc71",
      border: "rgba(46, 204, 113, 0.5)",
    },
  };

  // Couleurs par type
  const typeColors = {
    inclus: {
      bg: "rgba(84, 197, 177, 0.2)",
      color: "#54C5B1",
      border: "rgba(84, 197, 177, 0.5)",
    },
    facturable: {
      bg: "rgba(255, 184, 34, 0.2)",
      color: "#ffb822",
      border: "rgba(255, 184, 34, 0.5)",
    },
  };

  function applySelectColor(select, colorMap) {
    const style = colorMap[select.value];
    if (style) {
      select.style.backgroundColor = style.bg;
      select.style.color = style.color;
      select.style.border = `1px solid ${style.border}`;
    }
  }

  // Init + listeners
  if (statusSelect) {
    applySelectColor(statusSelect, statusColors);
    statusSelect.addEventListener("change", () =>
      applySelectColor(statusSelect, statusColors),
    );
  }

  if (prioritySelect) {
    applySelectColor(prioritySelect, priorityColors);
    prioritySelect.addEventListener("change", () =>
      applySelectColor(prioritySelect, priorityColors),
    );
  }

  if (typeSelect) {
    applySelectColor(typeSelect, typeColors);
    typeSelect.addEventListener("change", () => {
      applySelectColor(typeSelect, typeColors);
      toggleValidationSection();
    });
  }

  // ====== CACHER la section validation si type = inclus ======

  const validationSection = document.querySelector(".admin-validation-section");

  function toggleValidationSection() {
    if (!validationSection || !typeSelect) return;
    if (typeSelect.value === "inclus") {
      validationSection.style.display = "none";
    } else {
      validationSection.style.display = "block";
    }
  }

  // Init au chargement
  toggleValidationSection();

  // ====== Filtrer projets par client sélectionné ======

  const clientSelect = document.querySelector(".client-select");
  const projectSelect = document.querySelector(".project-select");
  const hiddenProjetId = document.getElementById("hidden-projet-id");

  if (clientSelect && projectSelect) {
    const allProjectOptions = Array.from(projectSelect.options).map((opt) => ({
      value: opt.value,
      text: opt.textContent,
      clientId: opt.dataset.clientId || "",
    }));

    clientSelect.addEventListener("change", () => {
      const selectedClientId = clientSelect.value;

      // Vider et repeupler le select projet
      projectSelect.innerHTML = '<option value="">-- Aucun --</option>';
      allProjectOptions.forEach((opt) => {
        if (
          opt.value &&
          (!selectedClientId || opt.clientId === selectedClientId)
        ) {
          const option = document.createElement("option");
          option.value = opt.value;
          option.textContent = opt.text;
          option.dataset.clientId = opt.clientId;
          projectSelect.appendChild(option);
        }
      });

      // Reset hidden projet id
      if (hiddenProjetId) hiddenProjetId.value = "";
    });

    // Synchroniser le hidden quand on change de projet
    projectSelect.addEventListener("change", () => {
      if (hiddenProjetId) hiddenProjetId.value = projectSelect.value;
    });
  }
});
