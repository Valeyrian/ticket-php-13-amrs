// ========================================
// VARIABLES GLOBALES
// ========================================
let modal, modalOverlay, modalForm, closeModalBtn, cancelModalBtn, addTicketBtn;

// ========================================
// INITIALISATION
// ========================================
document.addEventListener("DOMContentLoaded", () => {
  initializeModal();
});

function initializeModal() {
  // Récupérer les éléments DOM
  modal = document.getElementById("add-ticket-modal");
  modalOverlay = document.getElementById("modal-overlay");
  modalForm = document.getElementById("add-ticket-form");
  closeModalBtn = document.getElementById("close-modal");
  cancelModalBtn = document.getElementById("cancel-modal");
  addTicketBtn = document.querySelector(".cta-button");

  // Attacher les événements
  addTicketBtn?.addEventListener("click", openModal);
  closeModalBtn?.addEventListener("click", closeModal);
  cancelModalBtn?.addEventListener("click", closeModal);

  // Fermer si clic sur l'overlay (en dehors de la modal)
  modalOverlay?.addEventListener("click", (e) => {
    if (e.target === modalOverlay) {
      closeModal();
    }
  });

  // Empêcher la fermeture si clic sur la modal elle-même
  modal?.addEventListener("click", (e) => {
    e.stopPropagation();
  });

  // Gérer la soumission du formulaire
  modalForm?.addEventListener("submit", handleSubmit);
}

// ========================================
// GESTION DE LA MODAL
// ========================================
function openModal() {
  modalOverlay.classList.add("active");
  // Petit délai pour l'animation
  setTimeout(() => {
    modal.classList.add("active");
  }, 10);
  // Réinitialiser le formulaire
  modalForm.reset();
  // Cacher le message d'erreur
  const formError = document.getElementById("form-error");
  formError.classList.remove("visible");
}

function closeModal() {
  modal.classList.remove("active");
  // Attendre la fin de l'animation avant de masquer l'overlay
  setTimeout(() => {
    modalOverlay.classList.remove("active");
  }, 300);
}

// ========================================
// GESTION DU FORMULAIRE
// ========================================
function handleSubmit(event) {
  event.preventDefault();

  // Récupérer les données du formulaire
  const formData = new FormData(modalForm);

  const ticketData = {
    title: formData.get("ticketTitle"),
    description: formData.get("ticketDescription"),
    client: formData.get("ticketClient"),
    project: formData.get("ticketProject"),
    priority: formData.get("ticketPriority"),
    status: formData.get("ticketStatus"),
    collaborators: formData.getAll("ticketCollaborators[]"),
    estimatedTime: formData.get("ticketEstimatedTime"),
    billing: formData.get("ticketBilling"),
  };

  const formError = document.getElementById("form-error");
  FormValidator.hideError(formError);
  FormValidator.clearAllFieldErrors(modalForm);

  // Validation complète
  const isValid = FormValidator.validate(
    {
      ticketTitle: {
        value: ticketData.title,
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
      ticketDescription: {
        value: ticketData.description,
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
      ticketClient: {
        value: ticketData.client,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Veuillez sélectionner un client.",
          },
        ],
      },
      ticketProject: {
        value: ticketData.project,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Veuillez sélectionner un projet.",
          },
        ],
      },
      ticketPriority: {
        value: ticketData.priority,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La priorité est requise.",
          },
        ],
      },
      ticketEstimatedTime: {
        value: ticketData.estimatedTime,
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

  // Afficher les données dans la console (pour tester)
  console.log("Nouveau ticket:", ticketData);

  // Fermer la modal
  closeModal();

  // Afficher un message de succès
  showNotification("Ticket créé avec succès !", "success");
}

// ========================================
// NOTIFICATION
// ========================================
function showNotification(message, type = "success") {
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.textContent = message;

  document.body.appendChild(notification);

  // Afficher avec animation
  setTimeout(() => notification.classList.add("show"), 10);

  // Masquer et supprimer après 3 secondes
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}
