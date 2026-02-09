// ========================================
// VARIABLES GLOBALES
// ========================================
let modal, modalOverlay, modalForm, closeModalBtn, cancelModalBtn, addUserBtn;

// ========================================
// INITIALISATION
// ========================================
document.addEventListener("DOMContentLoaded", () => {
  initializeModal();
});

function initializeModal() {
  // Récupérer les éléments DOM
  modal = document.getElementById("add-user-modal");
  modalOverlay = document.getElementById("modal-overlay");
  modalForm = document.getElementById("add-user-form");
  closeModalBtn = document.getElementById("close-modal");
  cancelModalBtn = document.getElementById("cancel-modal");
  addUserBtn = document.querySelector(".btn-add");

  // Attacher les événements
  addUserBtn?.addEventListener("click", openModal);
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

  const userData = {
    lastname: formData.get("userLastname"),
    firstname: formData.get("userFirstname"),
    email: formData.get("userEmail"),
    phone: formData.get("userPhone"),
    role: formData.get("userRole"),
    status: formData.get("userStatus"),
    password: formData.get("userPassword"),
    passwordConfirm: formData.get("userPasswordConfirm"),
    notes: formData.get("userNotes"),
  };

  const formError = document.getElementById("form-error");
  FormValidator.hideError(formError);
  FormValidator.clearAllFieldErrors(modalForm);

  // Validation complète
  const isValid = FormValidator.validate(
    {
      userLastname: {
        value: userData.lastname,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le nom est requis.",
          },
          {
            test: (v) => FormValidator.isValidName(v),
            message:
              "Le nom doit contenir au moins 2 caractères (lettres uniquement).",
          },
        ],
      },
      userFirstname: {
        value: userData.firstname,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le prénom est requis.",
          },
          {
            test: (v) => FormValidator.isValidName(v),
            message:
              "Le prénom doit contenir au moins 2 caractères (lettres uniquement).",
          },
        ],
      },
      userEmail: {
        value: userData.email,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "L'email est requis.",
          },
          {
            test: (v) => FormValidator.isValidEmail(v),
            message:
              "Veuillez saisir une adresse email valide (ex: nom@domaine.com).",
          },
        ],
      },
      userPhone: {
        value: userData.phone,
        validators: [
          {
            test: (v) => FormValidator.isValidPhone(v),
            message:
              "Le numéro de téléphone n'est pas valide (ex: 06 12 34 56 78).",
          },
        ],
      },
      userRole: {
        value: userData.role,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le rôle est requis.",
          },
        ],
      },
      userPassword: {
        value: userData.password,
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
      userPasswordConfirm: {
        value: userData.passwordConfirm,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "La confirmation du mot de passe est requise.",
          },
          {
            test: (v) => FormValidator.passwordsMatch(userData.password, v),
            message: "Les mots de passe ne correspondent pas.",
          },
        ],
      },
    },
    formError,
    modalForm,
  );

  if (!isValid) return;

  // Afficher les données dans la console (pour tester)
  console.log("Nouvel utilisateur:", userData);

  // Fermer la modal
  closeModal();

  // Afficher un message de succès
  showNotification("Utilisateur créé avec succès !", "success");
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
