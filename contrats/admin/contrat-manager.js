// ========================================
// VARIABLES GLOBALES
// ========================================
let modal,
  modalOverlay,
  modalForm,
  closeModalBtn,
  cancelModalBtn,
  addContractBtn;

// ========================================
// INITIALISATION
// ========================================
document.addEventListener("DOMContentLoaded", () => {
  initializeModal();
});

function initializeModal() {
  // Récupérer les éléments DOM
  modal = document.getElementById("add-contract-modal");
  modalOverlay = document.getElementById("modal-overlay");
  modalForm = document.getElementById("add-contract-form");
  closeModalBtn = document.getElementById("close-modal");
  cancelModalBtn = document.getElementById("cancel-modal");
  addContractBtn = document.querySelector(".btn-add");

  // Attacher les événements
  addContractBtn?.addEventListener("click", openModal);
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
  formError.classList.remove("show");
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

  const contractData = {
    clientId: formData.get("clientId"),
    contractHours: formData.get("contractHours"),
    contractAmount: formData.get("contractAmount"),
    startDate: formData.get("startDate"),
    endDate: formData.get("endDate"),
    contractTerms: formData.get("contractTerms"),
  };

  const formError = document.getElementById("form-error");
  FormValidator.hideError(formError);
  FormValidator.clearAllFieldErrors(modalForm);

  // Validation complète
  const isValid = FormValidator.validate(
    {
      clientId: {
        value: contractData.clientId,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Veuillez sélectionner un client.",
          },
        ],
      },
      contractHours: {
        value: contractData.contractHours,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le nombre d'heures est requis.",
          },
          {
            test: (v) => FormValidator.isPositiveNumber(v),
            message: "Le nombre d'heures doit être supérieur à 0.",
          },
        ],
      },
      contractAmount: {
        value: contractData.contractAmount,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le montant est requis.",
          },
          {
            test: (v) => FormValidator.isPositiveNumber(v),
            message: "Le montant doit être supérieur à 0.",
          },
        ],
      },
      startDate: {
        value: contractData.startDate,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La date de début est requise.",
          },
        ],
      },
      endDate: {
        value: contractData.endDate,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La date de fin est requise.",
          },
          {
            test: (v) =>
              FormValidator.isEndDateAfterStartDate(contractData.startDate, v),
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
  console.log("Nouveau contrat:", contractData);

  // Fermer la modal
  closeModal();

  // Afficher un message de succès
  showNotification("Contrat créé avec succès !", "success");
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
