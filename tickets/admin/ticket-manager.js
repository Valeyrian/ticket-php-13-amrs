// ========================================
// VARIABLES GLOBALES
// ========================================
let modal, modalOverlay, modalForm, closeModalBtn, cancelModalBtn, addTicketBtn;

// ========================================
// INITIALISATION
// ========================================
document.addEventListener("DOMContentLoaded", () => {
  initializeModal();
  initializeFilters();
  initializeModalClientProjectLink();
});

// ========================================
// MODAL
// ========================================
function initializeModal() {
  modalOverlay = document.getElementById("modal-ticket");
  modal = modalOverlay?.querySelector(".modal");
  modalForm = modalOverlay?.querySelector(".modal-form");
  closeModalBtn = modalOverlay?.querySelector(".modal-close");
  cancelModalBtn = modalOverlay?.querySelector(".btn-cancel");
  addTicketBtn = document.querySelector(".cta-button");

  addTicketBtn?.addEventListener("click", openModal);
  closeModalBtn?.addEventListener("click", closeModal);
  cancelModalBtn?.addEventListener("click", closeModal);

  modalOverlay?.addEventListener("click", (e) => {
    if (e.target === modalOverlay) closeModal();
  });

  modal?.addEventListener("click", (e) => e.stopPropagation());

  modalForm?.addEventListener("submit", handleSubmit);
}

function openModal() {
  modalOverlay.classList.add("active");
  setTimeout(() => modal.classList.add("active"), 10);
  modalForm.reset();
  const formError = modalOverlay.querySelector(".form-error");
  if (formError) formError.classList.remove("visible");
  // Réinitialiser le filtrage projet
  const projectSelect = document.getElementById("ticket-project");
  if (projectSelect) {
    Array.from(projectSelect.options).forEach((opt) => {
      opt.style.display = "";
    });
  }
}

function closeModal() {
  modal.classList.remove("active");
  setTimeout(() => modalOverlay.classList.remove("active"), 300);
}

// ========================================
// LIEN CLIENT → PROJET DANS LE MODAL
// ========================================
function initializeModalClientProjectLink() {
  const clientSelect = document.getElementById("ticket-client");
  const projectSelect = document.getElementById("ticket-project");
  if (!clientSelect || !projectSelect) return;

  clientSelect.addEventListener("change", () => {
    const selectedClientId = clientSelect.value;
    projectSelect.value = "";

    Array.from(projectSelect.options).forEach((opt) => {
      if (!opt.value) {
        opt.style.display = "";
        return;
      }
      const optClientId = opt.getAttribute("data-client-id");
      // Si pas de data-client-id, on affiche toujours
      if (!optClientId) {
        opt.style.display = "";
        return;
      }
      opt.style.display =
        !selectedClientId || optClientId === selectedClientId ? "" : "none";
    });
  });
}

// ========================================
// SOUMISSION DU FORMULAIRE
// ========================================
function handleSubmit(event) {
  event.preventDefault();

  const formData = new FormData(modalForm);

  const ticketFormData = {
    title: formData.get("title"),
    description: formData.get("description"),
    client: formData.get("client_id"),
    project: formData.get("project_id"),
    priority: formData.get("priority"),
    status: formData.get("status"),
    collaborators: formData.getAll("collaborators[]"),
    estimatedTime: formData.get("ticket-time"),
    billing: formData.get("billing_type"),
  };

  const formError = modalOverlay.querySelector(".form-error");
  FormValidator.hideError(formError);
  FormValidator.clearAllFieldErrors(modalForm);

  const isValid = FormValidator.validate(
    {
      title: {
        value: ticketFormData.title,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le titre du ticket est requis.",
          },
          {
            test: (v) => FormValidator.hasMinLength(v, 3),
            message: "Le titre doit contenir au moins 3 caractères.",
          },
        ],
      },
      description: {
        value: ticketFormData.description,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La description du ticket est requise.",
          },
          {
            test: (v) => FormValidator.hasMinLength(v, 10),
            message: "La description doit contenir au moins 10 caractères.",
          },
        ],
      },
      client_id: {
        value: ticketFormData.client,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Veuillez sélectionner un client.",
          },
        ],
      },
      project_id: {
        value: ticketFormData.project,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Veuillez sélectionner un projet.",
          },
        ],
      },
      priority: {
        value: ticketFormData.priority,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La priorité est requise.",
          },
        ],
      },
      "ticket-time": {
        value: ticketFormData.estimatedTime,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le temps estimé est requis.",
          },
          {
            test: (v) => FormValidator.isPositiveNumber(v),
            message: "Le temps estimé doit être supérieur à 0.",
          },
        ],
      },
    },
    formError,
    modalForm,
  );

  if (!isValid) return;

  // Soumettre le formulaire au serveur
  modalForm.submit();
}

// ========================================
// FILTRAGE CÔTÉ CLIENT
// ========================================
function initializeFilters() {
  const filterIds = [
    "client-filter",
    "project-filter",
    "status-filter",
    "billing-filter",
    "priority-filter",
    "collaborateur-select",
  ];

  filterIds.forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.addEventListener("change", applyFilters);
  });

  // Bouton reset si existant
  const resetBtn = document.querySelector(".btn-reset-filters");
  if (resetBtn) {
    resetBtn.addEventListener("click", () => {
      filterIds.forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.selectedIndex = 0;
      });
      applyFilters();
    });
  }
}

function applyFilters() {
  const getValue = (id) => {
    const val = document.getElementById(id)?.value || "";
    return val === "all" ? "" : val;
  };

  const clientFilter = getValue("client-filter");
  const projetFilter = getValue("project-filter");
  const statutFilter = getValue("status-filter");
  const facturationFilter = getValue("billing-filter");
  const prioriteFilter = getValue("priority-filter");
  const collabFilter = getValue("collaborateur-select");

  const rows = document.querySelectorAll("table tbody tr");
  let visibleCount = 0;

  rows.forEach((row) => {
    const ticketId = parseInt(row.getAttribute("data-ticket-id"), 10);
    const data = (window.ticketData || []).find((t) => t.id === ticketId);

    if (!data) {
      row.style.display = "none";
      return;
    }

    let show = true;

    if (clientFilter && String(data.clientId) !== clientFilter) show = false;
    if (projetFilter && String(data.projetId) !== projetFilter) show = false;
    if (statutFilter && data.statut !== statutFilter) show = false;
    if (facturationFilter && data.type !== facturationFilter) show = false;
    if (prioriteFilter && data.priorite !== prioriteFilter) show = false;
    if (collabFilter && !data.collabIds.includes(parseInt(collabFilter, 10)))
      show = false;

    row.style.display = show ? "" : "none";
    if (show) visibleCount++;
  });

  // Mettre à jour le compteur visible si élément existe
  const counter = document.getElementById("visible-count");
  if (counter) counter.textContent = visibleCount;
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
