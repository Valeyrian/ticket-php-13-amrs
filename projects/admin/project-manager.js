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
});

function initializeModal() {
  // Récupérer les éléments DOM
  modal = document.getElementById("add-project-modal");
  modalOverlay = document.getElementById("modal-overlay");
  modalForm = document.getElementById("add-project-form");
  closeModalBtn = document.getElementById("close-modal");
  cancelModalBtn = document.getElementById("cancel-modal");
  addProjectBtn = document.querySelector(".cta-button");

  // Attacher les événements
  addProjectBtn?.addEventListener("click", openModal);
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

  const projectData = {
    name: formData.get("projectName"),
    description: formData.get("projectDescription"),
    clientId: formData.get("clientId"),
    contractId: formData.get("contractId"),
    startDate: formData.get("startDate"),
    endDate: formData.get("endDate"),
    collaborators: formData.getAll("collaborators[]"),
  };

  const formError = document.getElementById("form-error");
  FormValidator.hideError(formError);
  FormValidator.clearAllFieldErrors(modalForm);

  // Validation complète
  const isValid = FormValidator.validate(
    {
      projectName: {
        value: projectData.name,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le nom du projet est requis.",
          },
          {
            test: (v) => FormValidator.hasMinLength(v, 3),
            message: "Le nom du projet doit contenir au moins 3 caractères.",
          },
        ],
      },
      projectDescription: {
        value: projectData.description,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La description du projet est requise.",
          },
          {
            test: (v) => FormValidator.hasMinLength(v, 10),
            message: "La description doit contenir au moins 10 caractères.",
          },
        ],
      },
      clientId: {
        value: projectData.clientId,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Veuillez sélectionner un client.",
          },
        ],
      },
      startDate: {
        value: projectData.startDate,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La date de début est requise.",
          },
        ],
      },
      endDate: {
        value: projectData.endDate,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La date de fin est requise.",
          },
          {
            test: (v) =>
              FormValidator.isEndDateAfterStartDate(projectData.startDate, v),
            message: "La date de fin doit être postérieure à la date de début.",
          },
        ],
      },
    },
    formError,
    modalForm,
  );

  if (!isValid) return;

  // Afficher les données dans la console (pour tester)
  console.log("Nouveau projet:", projectData);

  // Fermer la modal
  closeModal();

  // Afficher un message de succès
  showNotification("Projet créé avec succès !", "success");
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
