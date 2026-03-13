// ========================================
// GESTION DES MODALS
// ========================================

// Modal Nouveau Ticket
const modalTicketOverlay = document.getElementById("modal-ticket-overlay");
const addTicketModal = document.getElementById("add-ticket-modal");
const closeTicketModal = document.getElementById("close-ticket-modal");
const cancelTicketModal = document.getElementById("cancel-ticket-modal");
const addTicketForm = document.getElementById("add-ticket-form");

// Modal Enregistrer Temps
const modalTimeOverlay = document.getElementById("modal-time-overlay");
const logTimeModal = document.getElementById("log-time-modal");
const closeTimeModal = document.getElementById("close-time-modal");
const cancelTimeModal = document.getElementById("cancel-time-modal");
const logTimeForm = document.getElementById("log-time-form");

// Boutons pour ouvrir les modals
const btnNewTicket = document.querySelector(".cta-button");
const btnAddTime = document.querySelectorAll(".add-time-btn");

// Fonction pour ouvrir une modal
function openModal(overlay) {
  if (overlay) {
    overlay.style.display = "flex ";
    document.body.style.overflow = "hidden";
    console.log("Modal ouverte:", overlay.id);
  }
}

// Fonction pour fermer une modal
function closeModal(overlay) {
  if (overlay) {
    overlay.style.display = "none";
    document.body.style.overflow = "auto";
  }
}

// Fonction spécifique pour ouvrir la modal nouveau ticket
function openTicketModal() {
  openModal(modalTicketOverlay);
}

// Fonction spécifique pour ouvrir la modal temps avec ID ticket
function openTimeModal(ticketId) {
  if (ticketId) {
    const ticketIdInput = document.getElementById("time-ticket-id");
    if (ticketIdInput) {
      ticketIdInput.value = ticketId;
    }
  }
  openModal(modalTimeOverlay);
}

// Ouvrir modal nouveau ticket
if (btnNewTicket) {
  btnNewTicket.addEventListener("click", (e) => {
    e.preventDefault();
    openModal(modalTicketOverlay);
  });
}

// Ouvrir modal enregistrer temps
if (btnAddTime) {
  btnAddTime.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      openModal(modalTimeOverlay);
      // Définir la date du jour par défaut
      const today = new Date().toISOString().split("T")[0];
      document.getElementById("time-date").value = today;
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

// Fermer modal enregistrer temps
if (closeTimeModal) {
  closeTimeModal.addEventListener("click", () => {
    closeModal(modalTimeOverlay);
  });
}

if (cancelTimeModal) {
  cancelTimeModal.addEventListener("click", () => {
    closeModal(modalTimeOverlay);
  });
}

// Fermer modals en cliquant sur l'overlay
if (modalTicketOverlay) {
  modalTicketOverlay.addEventListener("click", (e) => {
    if (e.target === modalTicketOverlay) {
      closeModal(modalTicketOverlay);
    }
  });
}

if (modalTimeOverlay) {
  modalTimeOverlay.addEventListener("click", (e) => {
    if (e.target === modalTimeOverlay) {
      closeModal(modalTimeOverlay);
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

// Soumettre formulaire enregistrer temps
if (logTimeForm) {
  logTimeForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const formData = new FormData(logTimeForm);
    const timeData = Object.fromEntries(formData.entries());

    // Créer ou récupérer l'élément d'erreur
    let formError = logTimeForm.querySelector(".form-error-msg");
    if (!formError) {
      formError = document.createElement("div");
      formError.className = "form-error-msg";
      formError.style.cssText =
        "color: #ff4757; background: rgba(255,71,87,0.1); border: 1px solid rgba(255,71,87,0.3); padding: 10px; border-radius: 6px; margin-bottom: 15px; display: none; font-size: 14px;";
      logTimeForm.insertBefore(formError, logTimeForm.firstChild);
    }

    // Validation complète
    const hours = parseInt(timeData["time-hours"] || "0");
    const minutes = parseInt(timeData["time-minutes"] || "0");

    const isValid = FormValidator.validate(
      {
        "time-date": {
          value: timeData["time-date"],
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "La date est requise.",
            },
            {
              test: (v) => FormValidator.isDateNotInFuture(v),
              message: "La date ne peut pas être dans le futur.",
            },
          ],
        },
        "time-hours": {
          value: hours + minutes,
          validators: [
            {
              test: (v) => v > 0,
              message:
                "Le temps enregistré doit être supérieur à 0 (heures ou minutes).",
            },
          ],
        },
        "time-description": {
          value: timeData["time-description"],
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "La description est requise.",
            },
            {
              test: (v) => FormValidator.hasMinLength(v, 3),
              message: "La description doit contenir au moins 3 caractères.",
            },
          ],
        },
      },
      formError,
      logTimeForm,
    );

    if (!isValid) return;

    console.log("Temps enregistré:", timeData);

    // Afficher un message de succès
    alert("Temps enregistré avec succès !");

    // Réinitialiser le formulaire et fermer la modal
    logTimeForm.reset();
    closeModal(modalTimeOverlay);
  });
}

// ========================================
// GESTION DES FILTRES ET TRIS
// ========================================

const sortSelect = document.getElementById("sort-select");
const showCompleted = document.getElementById("show-completed");

if (sortSelect) {
  sortSelect.addEventListener("change", (e) => {
    console.log("Tri:", e.target.value);
    // Ajouter la logique de tri ici
  });
}

if (showCompleted) {
  showCompleted.addEventListener("change", (e) => {
    console.log("Afficher terminés:", e.target.checked);
    // Ajouter la logique de filtrage ici
  });
}

// ========================================
// GESTION DES CHANGEMENTS DE STATUT
// ========================================

const statusSelects = document.querySelectorAll(".status-select");
const prioritySelects = document.querySelectorAll(".priority-select");

statusSelects.forEach((select) => {
  select.addEventListener("change", (e) => {
    console.log("Statut changé:", e.target.value);
    // Ajouter la logique de mise à jour du statut ici

    // Changer la classe CSS selon le nouveau statut
    select.className = `status-select status-${e.target.value}`;
  });
});

prioritySelects.forEach((select) => {
  select.addEventListener("change", (e) => {
    console.log("Priorité changée:", e.target.value);
    // Ajouter la logique de mise à jour de la priorité ici

    // Changer la classe CSS selon la nouvelle priorité
    select.className = `priority-select priority-${e.target.value}`;
  });
});

// ========================================
// GESTION TOUCHE ESC POUR FERMER LES MODALS
// ========================================

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeModal(modalTicketOverlay);
    closeModal(modalTimeOverlay);
  }
});
