// ========================================
// GESTION DES MODALS
// ========================================

// Modal Enregistrer Temps
const modalTimeOverlay = document.getElementById("modal-time-overlay");
const logTimeModal = document.getElementById("log-time-modal");
const closeTimeModal = document.getElementById("close-time-modal");
const cancelTimeModal = document.getElementById("cancel-time-modal");
const logTimeForm = document.getElementById("log-time-form");

// Modal Modifier Ticket
const modalEditOverlay = document.getElementById("modal-edit-overlay");
const editTicketModal = document.getElementById("edit-ticket-modal");
const closeEditModal = document.getElementById("close-edit-modal");
const cancelEditModal = document.getElementById("cancel-edit-modal");
const editTicketForm = document.getElementById("edit-ticket-form");

// Boutons pour ouvrir les modals
const btnLogTime = document.querySelector(".btn-log-time");
const btnAddTime = document.querySelectorAll(".add-time-btn");
const btnEditTicket = document.querySelector(".btn-edit-ticket");

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

// Ouvrir modal enregistrer temps
if (btnLogTime) {
  btnLogTime.addEventListener("click", (e) => {
    e.preventDefault();
    openModal(modalTimeOverlay);
    // Définir la date du jour par défaut
    const today = new Date().toISOString().split("T")[0];
    document.getElementById("time-date").value = today;
  });
}

// Ouvrir modal temps depuis les boutons +
if (btnAddTime) {
  btnAddTime.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      openModal(modalTimeOverlay);
      const today = new Date().toISOString().split("T")[0];
      document.getElementById("time-date").value = today;
    });
  });
}

// Ouvrir modal modifier ticket
if (btnEditTicket) {
  btnEditTicket.addEventListener("click", (e) => {
    e.preventDefault();
    openModal(modalEditOverlay);
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

// Fermer modal modifier ticket
if (closeEditModal) {
  closeEditModal.addEventListener("click", () => {
    closeModal(modalEditOverlay);
  });
}

if (cancelEditModal) {
  cancelEditModal.addEventListener("click", () => {
    closeModal(modalEditOverlay);
  });
}

// Fermer modals en cliquant sur l'overlay
if (modalTimeOverlay) {
  modalTimeOverlay.addEventListener("click", (e) => {
    if (e.target === modalTimeOverlay) {
      closeModal(modalTimeOverlay);
    }
  });
}

if (modalEditOverlay) {
  modalEditOverlay.addEventListener("click", (e) => {
    if (e.target === modalEditOverlay) {
      closeModal(modalEditOverlay);
    }
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

    // Recharger la page pour mettre à jour l'affichage
    // window.location.reload();
  });
}

// Soumettre formulaire modifier ticket
if (editTicketForm) {
  editTicketForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const formData = new FormData(editTicketForm);
    const ticketData = Object.fromEntries(formData.entries());

    // Créer ou récupérer l'élément d'erreur
    let formError = editTicketForm.querySelector(".form-error-msg");
    if (!formError) {
      formError = document.createElement("div");
      formError.className = "form-error-msg";
      formError.style.cssText =
        "color: #ff4757; background: rgba(255,71,87,0.1); border: 1px solid rgba(255,71,87,0.3); padding: 10px; border-radius: 6px; margin-bottom: 15px; display: none; font-size: 14px;";
      editTicketForm.insertBefore(formError, editTicketForm.firstChild);
    }

    // Validation complète
    const isValid = FormValidator.validate(
      {
        "edit-ticket-title": {
          value: ticketData["edit-ticket-title"],
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
        "edit-ticket-description": {
          value: ticketData["edit-ticket-description"],
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
        "edit-ticket-priority": {
          value: ticketData["edit-ticket-priority"],
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "La priorité est requise.",
            },
          ],
        },
        "edit-estimated-hours": {
          value: ticketData["edit-estimated-hours"],
          validators: [
            {
              test: (v) => !v || FormValidator.isPositiveNumber(v),
              message: "Les heures estimées doivent être supérieures à 0.",
            },
          ],
        },
      },
      formError,
      editTicketForm,
    );

    if (!isValid) return;

    console.log("Ticket modifié:", ticketData);

    // Afficher un message de succès
    alert("Ticket modifié avec succès !");

    // Fermer la modal
    closeModal(modalEditOverlay);

    // Recharger la page pour mettre à jour l'affichage
    // window.location.reload();
  });
}

// ========================================
// GESTION DES CHANGEMENTS DE STATUT ET PRIORITÉ
// ========================================

const statusSelects = document.querySelectorAll(".status-select");
const prioritySelects = document.querySelectorAll(".priority-select");
const typeSelects = document.querySelectorAll(".type-select");
const validationSection = document.querySelector(".validation-section");

// Fonction pour mettre à jour la couleur d'un select
function updateSelectColor(select, prefix) {
  // Retirer toutes les classes de couleur existantes
  const classes = [...select.classList];
  classes.forEach((cls) => {
    if (cls.startsWith(prefix + "-") && cls !== prefix + "-select") {
      select.classList.remove(cls);
    }
  });
  // Ajouter la nouvelle classe
  select.classList.add(`${prefix}-${select.value}`);
}

// Fonction pour afficher/masquer la section validation
function toggleValidationSection() {
  if (!validationSection) return;
  const typeSelect = document.querySelector(".type-select");
  if (typeSelect && typeSelect.value === "facturable") {
    validationSection.style.display = "";
  } else {
    validationSection.style.display = "none";
  }
}

statusSelects.forEach((select) => {
  // Appliquer la couleur initiale
  updateSelectColor(select, "status");

  select.addEventListener("change", (e) => {
    console.log("Statut changé:", e.target.value);
    updateSelectColor(select, "status");
  });
});

prioritySelects.forEach((select) => {
  // Appliquer la couleur initiale
  updateSelectColor(select, "priority");

  select.addEventListener("change", (e) => {
    console.log("Priorité changée:", e.target.value);
    updateSelectColor(select, "priority");
  });
});

typeSelects.forEach((select) => {
  // Appliquer la couleur initiale
  updateSelectColor(select, "type");

  select.addEventListener("change", (e) => {
    console.log("Type de facturation changé:", e.target.value);
    updateSelectColor(select, "type");
    toggleValidationSection();
  });
});

// Appliquer l'état initial de la section validation
toggleValidationSection();

// ========================================
// GESTION DES COMMENTAIRES
// ========================================

const commentInput = document.querySelector(".comment-input");
const btnPublishComment = document.querySelector(".btn-primary");

if (btnPublishComment && commentInput) {
  btnPublishComment.addEventListener("click", (e) => {
    e.preventDefault();

    const commentText = commentInput.value.trim();

    if (!commentText) {
      commentInput.style.borderColor = "#ff4757";
      commentInput.style.boxShadow = "0 0 0 2px rgba(255, 71, 87, 0.2)";
      commentInput.setAttribute(
        "placeholder",
        "Veuillez saisir un commentaire...",
      );
      return;
    }

    if (commentText.length < 3) {
      commentInput.style.borderColor = "#ff4757";
      commentInput.style.boxShadow = "0 0 0 2px rgba(255, 71, 87, 0.2)";
      alert("Le commentaire doit contenir au moins 3 caractères.");
      return;
    }

    commentInput.style.borderColor = "";
    commentInput.style.boxShadow = "";

    if (commentText) {
      console.log(
        "Nouveau commentaire:",
        FormValidator.escapeHtml(commentText),
      );

      // Afficher un message de succès
      alert("Commentaire publié avec succès !");

      // Réinitialiser le champ
      commentInput.value = "";
    }
  });
}

// ========================================
// GESTION MODAL AJOUTER COLLABORATEUR
// ========================================

const modalCollabOverlay = document.getElementById("modal-collab-overlay");
const closeCollabModal = document.getElementById("close-collab-modal");
const cancelCollabModal = document.getElementById("cancel-collab-modal");
const addCollabForm = document.getElementById("add-collab-form");
const btnAddCollab = document.querySelector(".btn-add-collab");
const collabSearch = document.getElementById("collab-search");

// Ouvrir la modal
if (btnAddCollab) {
  btnAddCollab.addEventListener("click", (e) => {
    e.preventDefault();
    openModal(modalCollabOverlay);
  });
}

// Fermer la modal
if (closeCollabModal) {
  closeCollabModal.addEventListener("click", () =>
    closeModal(modalCollabOverlay),
  );
}
if (cancelCollabModal) {
  cancelCollabModal.addEventListener("click", () =>
    closeModal(modalCollabOverlay),
  );
}
if (modalCollabOverlay) {
  modalCollabOverlay.addEventListener("click", (e) => {
    if (e.target === modalCollabOverlay) closeModal(modalCollabOverlay);
  });
}

// Recherche de collaborateur
if (collabSearch) {
  collabSearch.addEventListener("input", (e) => {
    const query = e.target.value.toLowerCase();
    const options = document.querySelectorAll(".collab-option");
    options.forEach((option) => {
      const name = option
        .querySelector(".collab-name")
        .textContent.toLowerCase();
      const role = option
        .querySelector(".collab-role")
        .textContent.toLowerCase();
      option.style.display =
        name.includes(query) || role.includes(query) ? "" : "none";
    });
  });
}

// Soumission du formulaire
if (addCollabForm) {
  addCollabForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const checked = addCollabForm.querySelectorAll(
      'input[name="collaborators"]:checked',
    );
    const formError = document.getElementById("collab-form-error");

    if (checked.length === 0) {
      if (formError) {
        formError.style.display = "block";
      }
      return;
    }

    if (formError) formError.style.display = "none";

    const selected = [...checked].map((cb) => {
      const label = cb.closest(".collab-option");
      return label.querySelector(".collab-name").textContent;
    });

    console.log("Collaborateurs ajoutés:", selected);
    alert("Collaborateur(s) ajouté(s) : " + selected.join(", "));

    // Reset et fermer
    addCollabForm.reset();
    closeModal(modalCollabOverlay);
  });
}

// ========================================
// GESTION TOUCHE ESC POUR FERMER LES MODALS
// ========================================

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeModal(modalTimeOverlay);
    closeModal(modalEditOverlay);
    closeModal(modalCollabOverlay);
  }
});

// ========================================
// INITIALISATION DATE PAR DÉFAUT
// ========================================

// Définir la date du jour par défaut
const timeDate = document.getElementById("time-date");
if (timeDate && !timeDate.value) {
  const today = new Date().toISOString().split("T")[0];
  timeDate.value = today;
}
