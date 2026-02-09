// ========================================
// VARIABLES GLOBALES
// ========================================
let modal, modalOverlay, modalForm, closeModalBtn, cancelModalBtn, addClientBtn;

// ========================================
// INITIALISATION
// ========================================
document.addEventListener("DOMContentLoaded", () => {
  initializeModal();
});

function initializeModal() {
  // Récupérer les éléments DOM
  modal = document.getElementById("add-client-modal");
  modalOverlay = document.getElementById("modal-overlay");
  modalForm = document.getElementById("add-client-form");
  closeModalBtn = document.getElementById("close-modal");
  cancelModalBtn = document.getElementById("cancel-modal");
  addClientBtn = document.querySelector(".btn-add");

  // Attacher les événements
  addClientBtn?.addEventListener("click", openModal);
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

  const clientData = {
    companyName: formData.get("companyName"),
    contactName: formData.get("contactName"),
    contactFirstName: formData.get("contact-first-name"),
    contactEmail: formData.get("contactEmail"),
    contactPhone: formData.get("contactPhone"),
    address: formData.get("address"),
    postalCode: formData.get("postalCode"),
    city: formData.get("city"),
    country: formData.get("country"),
    notes: formData.get("notes"),
    contactPassword: formData.get("contactPassword"),
  };

  const formError = document.getElementById("form-error");
  FormValidator.hideError(formError);
  FormValidator.clearAllFieldErrors(modalForm);

  // Validation complète
  const isValid = FormValidator.validate(
    {
      companyName: {
        value: clientData.companyName,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le nom de l'entreprise est requis.",
          },
          {
            test: (v) => FormValidator.hasMinLength(v, 2),
            message:
              "Le nom de l'entreprise doit contenir au moins 2 caractères.",
          },
        ],
      },
      contactName: {
        value: clientData.contactName,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le nom du contact est requis.",
          },
          {
            test: (v) => FormValidator.isValidName(v),
            message:
              "Le nom du contact doit contenir au moins 2 caractères (lettres uniquement).",
          },
        ],
      },
      "contact-first-name": {
        value: clientData.contactFirstName,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le prénom du contact est requis.",
          },
          {
            test: (v) => FormValidator.isValidName(v),
            message:
              "Le prénom du contact doit contenir au moins 2 caractères (lettres uniquement).",
          },
        ],
      },
      contactEmail: {
        value: clientData.contactEmail,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "L'email du contact est requis.",
          },
          {
            test: (v) => FormValidator.isValidEmail(v),
            message:
              "Veuillez saisir une adresse email valide (ex: nom@domaine.com).",
          },
        ],
      },
      contactPhone: {
        value: clientData.contactPhone,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le téléphone du contact est requis.",
          },
          {
            test: (v) => FormValidator.isValidPhone(v),
            message:
              "Le numéro de téléphone n'est pas valide (ex: 06 12 34 56 78).",
          },
        ],
      },
      contactPassword: {
        value: clientData.contactPassword,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le mot de passe est requis.",
          },
          {
            test: (v) => FormValidator.isValidPassword(v),
            message:
              "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.",
          },
        ],
      },
      address: {
        value: clientData.address,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "L'adresse est requise.",
          },
        ],
      },
      postalCode: {
        value: clientData.postalCode,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le code postal est requis.",
          },
          {
            test: (v) => FormValidator.isValidPostalCode(v),
            message: "Le code postal doit contenir exactement 5 chiffres.",
          },
        ],
      },
      city: {
        value: clientData.city,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La ville est requise.",
          },
          {
            test: (v) => FormValidator.hasMinLength(v, 2),
            message: "La ville doit contenir au moins 2 caractères.",
          },
        ],
      },
      country: {
        value: clientData.country,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le pays est requis.",
          },
        ],
      },
    },
    formError,
    modalForm,
  );

  if (!isValid) return;

  // Afficher les données dans la console (pour tester)
  console.log("Nouveau client:", clientData);

  /* 
    //
    //
    //
    // 
    */

  // Fermer la modal
  closeModal();

  // Afficher un message de succès
  showNotification("Client créé avec succès !", "success");
}

// ========================================
// NOTIFICATION (BONUS)
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
