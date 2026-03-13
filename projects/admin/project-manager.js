// ========================================
// VARIABLES GLOBALES
// ========================================
let modal,
  modalOverlay,
  modalForm,
  closeModalBtn,
  cancelModalBtn,
  addProjectBtn;

// ========================================
// INITIALISATION
// ========================================
document.addEventListener("DOMContentLoaded", () => {
  initializeModal();
  initializeFilters();
});

// ========================================
// MODAL
// ========================================
function initializeModal() {
  modal = document.getElementById("add-project-modal");
  modalOverlay = document.getElementById("modal-overlay");
  modalForm = document.getElementById("add-project-form");
  closeModalBtn = document.getElementById("close-modal");
  cancelModalBtn = document.getElementById("cancel-modal");
  addProjectBtn = document.querySelector(".cta-button");

  addProjectBtn?.addEventListener("click", openModal);
  closeModalBtn?.addEventListener("click", closeModal);
  cancelModalBtn?.addEventListener("click", closeModal);

  modalOverlay?.addEventListener("click", (e) => {
    if (e.target === modalOverlay) closeModal();
  });

  modal?.addEventListener("click", (e) => e.stopPropagation());

  // Validation avant soumission POST
  modalForm?.addEventListener("submit", handleSubmit);

  // Filtrage contrats selon client sélectionné
  const clientSelect = document.getElementById("client-select");
  clientSelect?.addEventListener("change", updateContractOptions);
}

function openModal() {
  modalOverlay.classList.add("active");
  setTimeout(() => modal.classList.add("active"), 10);
  modalForm.reset();
  const formError = document.getElementById("form-error");
  formError.classList.remove("visible");
  // Reset contrats
  updateContractOptions();
}

function updateContractOptions() {
  const clientSelect = document.getElementById("client-select");
  const contractSelect = document.getElementById("contract-select");
  if (!clientSelect || !contractSelect) return;

  const clientId = clientSelect.value;
  contractSelect.innerHTML = "";

  if (!clientId) {
    contractSelect.innerHTML =
      '<option value="">Sélectionnez d\'abord un client</option>';
    return;
  }

  const contrats = (window.clientContrats || {})[clientId] || [];

  contractSelect.innerHTML = '<option value="">Aucun contrat</option>';
  contrats.forEach((ct) => {
    const opt = document.createElement("option");
    opt.value = ct.id;
    opt.textContent = ct.nom;
    contractSelect.appendChild(opt);
  });
}

function closeModal() {
  modal.classList.remove("active");
  setTimeout(() => modalOverlay.classList.remove("active"), 300);
}

// ========================================
// SOUMISSION DU FORMULAIRE (POST natif)
// ========================================
function handleSubmit(event) {
  const formData = new FormData(modalForm);

  const nom = formData.get("nom");
  const description = formData.get("description");
  const clientId = formData.get("client_id");
  const dateDebut = formData.get("date_debut");
  const dateFinPrevue = formData.get("date_fin_prevue");

  const formError = document.getElementById("form-error");
  FormValidator.hideError(formError);
  FormValidator.clearAllFieldErrors(modalForm);

  const isValid = FormValidator.validate(
    {
      nom: {
        value: nom,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le nom du projet est requis.",
          },
          {
            test: (v) => FormValidator.hasMinLength(v, 3),
            message: "Le nom doit contenir au moins 3 caractères.",
          },
        ],
      },
      description: {
        value: description,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La description est requise.",
          },
          {
            test: (v) => FormValidator.hasMinLength(v, 10),
            message: "La description doit contenir au moins 10 caractères.",
          },
        ],
      },
      client_id: {
        value: clientId,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Veuillez sélectionner un client.",
          },
        ],
      },
      date_debut: {
        value: dateDebut,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La date de début est requise.",
          },
        ],
      },
      date_fin_prevue: {
        value: dateFinPrevue,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La date de fin est requise.",
          },
          {
            test: (v) => FormValidator.isEndDateAfterStartDate(dateDebut, v),
            message: "La date de fin doit être postérieure à la date de début.",
          },
        ],
      },
    },
    formError,
    modalForm,
  );

  if (!isValid) {
    event.preventDefault();
    return;
  }

  // Validation OK → le formulaire se soumet normalement en POST
}

// ========================================
// FILTRAGE CÔTÉ CLIENT
// ========================================
function initializeFilters() {
  const filterIds = [
    "client-filter",
    "status-filter",
    "contract-filter",
    "sort-select",
    "collaborateur-select",
  ];

  filterIds.forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.addEventListener("change", applyFilters);
  });
}

function applyFilters() {
  const getValue = (id) => {
    const val = document.getElementById(id)?.value || "";
    return val === "all" ? "" : val;
  };

  const clientFilter = getValue("client-filter");
  const statutFilter = getValue("status-filter");
  const contractFilter = getValue("contract-filter");
  const collabFilter = getValue("collaborateur-select");

  const rows = document.querySelectorAll(".projects-management-table tbody tr");
  let visibleCount = 0;

  rows.forEach((row) => {
    const projectId = parseInt(row.getAttribute("data-project-id"), 10);
    const data = (window.projectData || []).find((p) => p.id === projectId);

    if (!data) {
      row.style.display = "none";
      return;
    }

    let show = true;

    // Filtre client
    if (clientFilter && String(data.clientId) !== clientFilter) show = false;

    // Filtre statut
    if (statutFilter && data.statut !== statutFilter) show = false;

    // Filtre collaborateur
    if (collabFilter && !data.collabIds.includes(parseInt(collabFilter, 10)))
      show = false;

    // Filtre contrat
    if (contractFilter === "actif") {
      if (data.heuresContrat <= 0) show = false;
    } else if (contractFilter === "alerte") {
      if (
        data.heuresContrat <= 0 ||
        data.heuresRestantes / data.heuresContrat >= 0.15
      )
        show = false;
    }

    row.style.display = show ? "" : "none";
    if (show) visibleCount++;
  });

  // Tri
  const sortValue = document.getElementById("sort-select")?.value || "date";
  sortTable(sortValue);
}

function sortTable(criteria) {
  const tbody = document.querySelector(".projects-management-table tbody");
  if (!tbody) return;

  const rows = Array.from(tbody.querySelectorAll("tr"));

  rows.sort((a, b) => {
    const idA = parseInt(a.getAttribute("data-project-id"), 10);
    const idB = parseInt(b.getAttribute("data-project-id"), 10);
    const dA = (window.projectData || []).find((p) => p.id === idA) || {};
    const dB = (window.projectData || []).find((p) => p.id === idB) || {};

    switch (criteria) {
      case "client":
        return (dA.clientId || 0) - (dB.clientId || 0);
      case "heures":
        return (dA.heuresRestantes || 0) - (dB.heuresRestantes || 0);
      case "tickets":
        return (dB.nbTickets || 0) - (dA.nbTickets || 0);
      case "date":
      default:
        return (dA.date || "").localeCompare(dB.date || "");
    }
  });

  rows.forEach((row) => tbody.appendChild(row));
}

// ========================================
// NOTIFICATION
// ========================================
function showNotification(message, type = "success") {
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.textContent = message;

  document.body.appendChild(notification);
  setTimeout(() => notification.classList.add("show"), 10);
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}
