// ========================================
// GESTION DES MODALS
// ========================================

// Modal Nouveau Ticket
const modalTicketOverlay = document.getElementById("modal-ticket-overlay");
const addTicketModal = document.getElementById("add-ticket-modal");
const closeTicketModal = document.getElementById("close-ticket-modal");
const cancelTicketModal = document.getElementById("cancel-ticket-modal");
const addTicketForm = document.getElementById("add-ticket-form");

// Boutons pour ouvrir les modals
const btnNewTickets = document.querySelectorAll(".btn-ticket");

// Fonction pour ouvrir une modal
function openModal(overlay) {
  if (overlay) {
    overlay.style.display = "flex";
    document.body.style.overflow = "hidden";
  }
}

// Fonction pour fermer une modal
function closeModal(overlay) {
  if (overlay) {
    overlay.style.display = "none";
    document.body.style.overflow = "auto";
  }
}

// Ouvrir modal nouveau ticket
if (btnNewTickets) {
  btnNewTickets.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      openModal(modalTicketOverlay);
    });
  });
}

// Fermer modal nouveau ticket
if (closeTicketModal) {
  closeTicketModal.addEventListener("click", () => {
    closeModal(modalTicketOverlay);
  });
}

if (cancelTicketModal) {
  cancelTicketModal.addEventListener("click", () => {
    closeModal(modalTicketOverlay);
  });
}

// Fermer modal en cliquant sur l'overlay
if (modalTicketOverlay) {
  modalTicketOverlay.addEventListener("click", (e) => {
    if (e.target === modalTicketOverlay) {
      closeModal(modalTicketOverlay);
    }
  });
}

// Soumettre formulaire nouveau ticket
if (addTicketForm) {
  addTicketForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const formData = new FormData(addTicketForm);
    const ticketData = Object.fromEntries(formData.entries());

    // Créer ou récupérer l'élément d'erreur
    let formError = addTicketForm.querySelector(".form-error-msg");
    if (!formError) {
      formError = document.createElement("div");
      formError.className = "form-error-msg";
      formError.style.cssText =
        "color: #ff4757; background: rgba(255,71,87,0.1); border: 1px solid rgba(255,71,87,0.3); padding: 10px; border-radius: 6px; margin-bottom: 15px; display: none; font-size: 14px;";
      addTicketForm.insertBefore(formError, addTicketForm.firstChild);
    }

    // Validation complète
    const isValid = FormValidator.validate(
      {
        "ticket-title": {
          value: ticketData["ticket-title"],
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
        "ticket-description": {
          value: ticketData["ticket-description"],
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
        "ticket-priority": {
          value: ticketData["ticket-priority"],
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "La priorité est requise.",
            },
          ],
        },
        "ticket-project": {
          value: ticketData["ticket-project"],
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "Veuillez sélectionner un projet.",
            },
          ],
        },
        "ticket-estimated-hours": {
          value: ticketData["ticket-estimated-hours"],
          validators: [
            {
              test: (v) => !v || FormValidator.isPositiveNumber(v),
              message: "Les heures estimées doivent être supérieures à 0.",
            },
          ],
        },
      },
      formError,
      addTicketForm,
    );

    if (!isValid) return;

    console.log("Nouveau ticket créé:", ticketData);

    // Afficher un message de succès
    alert("Ticket créé avec succès !");

    // Réinitialiser le formulaire et fermer la modal
    addTicketForm.reset();
    closeModal(modalTicketOverlay);
  });
}

// ========================================
// GESTION DES FILTRES
// ========================================

const statusFilter = document.getElementById("status-filter");
const sortSelect = document.getElementById("sort-select");

if (statusFilter) {
  statusFilter.addEventListener("change", (e) => {
    console.log("Filtre statut:", e.target.value);
    // Ajouter la logique de filtrage ici
  });
}

if (sortSelect) {
  sortSelect.addEventListener("change", (e) => {
    console.log("Tri:", e.target.value);
    // Ajouter la logique de tri ici
  });
}

// ========================================
// GESTION TOUCHE ESC POUR FERMER LES MODALS
// ========================================

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeModal(modalTicketOverlay);
  }
});
