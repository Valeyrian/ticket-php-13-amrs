// ========================================
// VARIABLES GLOBALES
// ========================================
let editModal,
  editModalOverlay,
  editModalForm,
  closeEditModalBtn,
  cancelEditModalBtn,
  editContractBtn;
let timeModal,
  timeModalOverlay,
  timeModalForm,
  closeTimeModalBtn,
  cancelTimeModalBtn,
  addTimeBtn;

// ========================================
// INITIALISATION
// ========================================
document.addEventListener("DOMContentLoaded", () => {
  initializeEditModal();
  initializeTimeModal();
  setTodayDate();
});

function setTodayDate() {
  // Définir la date d'aujourd'hui dans le champ date de la modal de temps
  const timeDate = document.getElementById("time-date");
  if (timeDate) {
    const today = new Date().toISOString().split("T")[0];
    timeDate.value = today;
  }
}

// ========================================
// MODAL MODIFICATION CONTRAT
// ========================================
function initializeEditModal() {
  // Récupérer les éléments DOM
  editModal = document.getElementById("edit-contract-modal");
  editModalOverlay = document.getElementById("modal-edit-overlay");
  editModalForm = document.getElementById("edit-contract-form");
  closeEditModalBtn = document.getElementById("close-edit-modal");
  cancelEditModalBtn = document.getElementById("cancel-edit-modal");
  editContractBtn = document.querySelector(".btn-edit");

  // Attacher les événements
  editContractBtn?.addEventListener("click", openEditModal);
  closeEditModalBtn?.addEventListener("click", closeEditModal);
  cancelEditModalBtn?.addEventListener("click", closeEditModal);

  // Fermer si clic sur l'overlay
  editModalOverlay?.addEventListener("click", (e) => {
    if (e.target === editModalOverlay) {
      closeEditModal();
    }
  });

  // Empêcher la fermeture si clic sur la modal
  editModal?.addEventListener("click", (e) => {
    e.stopPropagation();
  });

  // Gérer la soumission du formulaire
  editModalForm?.addEventListener("submit", handleEditSubmit);
}

function openEditModal() {
  editModalOverlay.classList.add("active");
  setTimeout(() => {
    editModal.classList.add("active");
  }, 10);
  // Cacher le message d'erreur
  const formError = document.getElementById("edit-form-error");
  formError.classList.remove("show");
}

function closeEditModal() {
  editModal.classList.remove("active");
  setTimeout(() => {
    editModalOverlay.classList.remove("active");
  }, 300);
}

function handleEditSubmit(event) {
  event.preventDefault();

  const formData = new FormData(editModalForm);

  const contractData = {
    contractType: formData.get("contractType"),
    contractHours: formData.get("contractHours"),
    contractAmount: formData.get("contractAmount"),
    billingFrequency: formData.get("billingFrequency"),
    startDate: formData.get("startDate"),
    endDate: formData.get("endDate"),
    contractTerms: formData.get("contractTerms"),
  };

  const formError = document.getElementById("edit-form-error");
  FormValidator.hideError(formError);
  FormValidator.clearAllFieldErrors(editModalForm);

  // Validation complète
  const isValid = FormValidator.validate(
    {
      contractType: {
        value: contractData.contractType,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le type de contrat est requis.",
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
      billingFrequency: {
        value: contractData.billingFrequency,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La fréquence de facturation est requise.",
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
    editModalForm,
  );

  if (!isValid) return;

  console.log("Contrat modifié:", contractData);

  /* 
    // ICI : Envoyer les données au backend
    // fetch('/api/contracts/CT001', {
    //   method: 'PUT',
    //   headers: { 'Content-Type': 'application/json' },
    //   body: JSON.stringify(contractData)
    // })
    */

  closeEditModal();
  showNotification("Contrat modifié avec succès !", "success");
}

// ========================================
// MODAL AJOUT DE TEMPS
// ========================================
function initializeTimeModal() {
  // Récupérer les éléments DOM
  timeModal = document.getElementById("add-time-modal");
  timeModalOverlay = document.getElementById("modal-time-overlay");
  timeModalForm = document.getElementById("add-time-form");
  closeTimeModalBtn = document.getElementById("close-time-modal");
  cancelTimeModalBtn = document.getElementById("cancel-time-modal");
  addTimeBtn = document.querySelector(".btn-add-small");

  // Attacher les événements
  addTimeBtn?.addEventListener("click", openTimeModal);
  closeTimeModalBtn?.addEventListener("click", closeTimeModal);
  cancelTimeModalBtn?.addEventListener("click", closeTimeModal);

  // Fermer si clic sur l'overlay
  timeModalOverlay?.addEventListener("click", (e) => {
    if (e.target === timeModalOverlay) {
      closeTimeModal();
    }
  });

  // Empêcher la fermeture si clic sur la modal
  timeModal?.addEventListener("click", (e) => {
    e.stopPropagation();
  });

  // Gérer la soumission du formulaire
  timeModalForm?.addEventListener("submit", handleTimeSubmit);
}

function openTimeModal() {
  timeModalOverlay.classList.add("active");
  setTimeout(() => {
    timeModal.classList.add("active");
  }, 10);
  // Réinitialiser le formulaire
  timeModalForm.reset();
  // Remettre la date d'aujourd'hui
  setTodayDate();
  // Cacher le message d'erreur
  const formError = document.getElementById("time-form-error");
  formError.classList.remove("show");
}

function closeTimeModal() {
  timeModal.classList.remove("active");
  setTimeout(() => {
    timeModalOverlay.classList.remove("active");
  }, 300);
}

function handleTimeSubmit(event) {
  event.preventDefault();

  const formData = new FormData(timeModalForm);

  const timeData = {
    timeDate: formData.get("timeDate"),
    timeHours: formData.get("timeHours"),
    collaboratorId: formData.get("collaboratorId"),
    ticketId: formData.get("ticketId"),
    timeDescription: formData.get("timeDescription"),
    billingStatus: formData.get("billingStatus"),
  };

  const formError = document.getElementById("time-form-error");
  FormValidator.hideError(formError);
  FormValidator.clearAllFieldErrors(timeModalForm);

  // Validation complète
  const isValid = FormValidator.validate(
    {
      timeDate: {
        value: timeData.timeDate,
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
      timeHours: {
        value: timeData.timeHours,
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
      collaboratorId: {
        value: timeData.collaboratorId,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Veuillez sélectionner un collaborateur.",
          },
        ],
      },
      timeDescription: {
        value: timeData.timeDescription,
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
    timeModalForm,
  );

  if (!isValid) return;

  console.log("Temps ajouté:", timeData);

  /* 
    // ICI : Envoyer les données au backend
    // fetch('/api/contracts/CT001/time-entries', {
    //   method: 'POST',
    //   headers: { 'Content-Type': 'application/json' },
    //   body: JSON.stringify(timeData)
    // })
    */

  closeTimeModal();
  showNotification(`${timeData.timeHours}h ajoutées avec succès !`, "success");
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
