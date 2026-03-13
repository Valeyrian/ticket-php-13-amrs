// ========================================
// VARIABLES GLOBALES
// ========================================
let modalOverlay, modal, modalForm, closeModalBtn, cancelModalBtn, addUserBtn;

// ========================================
// INITIALISATION
// ========================================
document.addEventListener("DOMContentLoaded", () => {
  initializeModal();
  initializeRoleToggle();
  initializeCompanyToggle();
  initializeFilters();
});

function initializeModal() {
  // Récupérer les éléments DOM
  modalOverlay = document.getElementById("modal-user");
  modal = modalOverlay?.querySelector(".modal");
  modalForm = modalOverlay?.querySelector(".modal-form");
  closeModalBtn = modalOverlay?.querySelector(".modal-close");
  cancelModalBtn = modalOverlay?.querySelector(".btn-cancel");
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
// TOGGLE SECTION ENTREPRISE SELON LE RÔLE
// ========================================
function initializeRoleToggle() {
  const roleSelect = document.getElementById("user-role");
  const sectionCompany = document.getElementById("section-company");
  if (!roleSelect || !sectionCompany) return;

  roleSelect.addEventListener("change", () => {
    if (roleSelect.value === "client") {
      sectionCompany.style.display = "";
    } else {
      sectionCompany.style.display = "none";
    }
  });
}

// ========================================
// TOGGLE NOUVELLE ENTREPRISE
// ========================================
function initializeCompanyToggle() {
  const companySelect = document.getElementById("user-company");
  const newCompanyGroup = document.getElementById("new-company-group");
  if (!companySelect || !newCompanyGroup) return;

  companySelect.addEventListener("change", () => {
    if (companySelect.value === "__new__") {
      newCompanyGroup.style.display = "";
    } else {
      newCompanyGroup.style.display = "none";
    }
  });
}

// ========================================
// FILTRES TABLEAU
// ========================================
function initializeFilters() {
  const searchInput = document.getElementById("search-input");
  const filterRole = document.getElementById("filter-role");
  const filterState = document.getElementById("filter-state");
  const rows = document.querySelectorAll(".users-table tbody tr[data-role]");

  function applyFilters() {
    const search = (searchInput?.value || "").toLowerCase().trim();
    const role = filterRole?.value || "";
    const state = filterState?.value || "";

    rows.forEach((row) => {
      const name = row.getAttribute("data-name") || "";
      const rowRole = row.getAttribute("data-role") || "";
      const rowState = row.getAttribute("data-state") || "";

      let show = true;
      if (search && !name.includes(search)) show = false;
      if (role && rowRole !== role) show = false;
      if (state && rowState !== state) show = false;

      row.style.display = show ? "" : "none";
    });
  }

  searchInput?.addEventListener("input", applyFilters);
  filterRole?.addEventListener("change", applyFilters);
  filterState?.addEventListener("change", applyFilters);
}

// ========================================
// GESTION DE LA MODAL
// ========================================
function openModal() {
  if (!modalOverlay || !modal || !modalForm) return;
  modalOverlay.style.display = "flex";
  modalOverlay.classList.add("active");
  setTimeout(() => {
    modal.classList.add("active");
  }, 10);
  modalForm.reset();
  // Réinitialiser les sections conditionnelles
  const sectionCompany = document.getElementById("section-company");
  if (sectionCompany) sectionCompany.style.display = "none";
  const newCompanyGroup = document.getElementById("new-company-group");
  if (newCompanyGroup) newCompanyGroup.style.display = "none";
  // Cacher le message d'erreur
  const formError = modalForm.querySelector(".form-error");
  if (formError) formError.classList.remove("visible");
  if (typeof FormValidator !== "undefined") {
    FormValidator.clearAllFieldErrors(modalForm);
  }
}

function closeModal() {
  if (!modal || !modalOverlay) return;
  modal.classList.remove("active");
  setTimeout(() => {
    modalOverlay.classList.remove("active");
    modalOverlay.style.display = "none";
  }, 300);
}

// ========================================
// GESTION DU FORMULAIRE
// ========================================
function handleSubmit(event) {
  event.preventDefault();

  const formData = new FormData(modalForm);

  const userData = {
    name: formData.get("name"),
    surname: formData.get("surname"),
    email: formData.get("email"),
    phone: formData.get("phone"),
    role: formData.get("role"),
    state: formData.get("state"),
    password: formData.get("password"),
    passwordConfirm: formData.get("password_confirm"),
  };

  const formError = modalForm.querySelector(".form-error");
  FormValidator.hideError(formError);
  FormValidator.clearAllFieldErrors(modalForm);

  // Validation
  const rules = {
    name: {
      value: userData.name,
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
    surname: {
      value: userData.surname,
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
    email: {
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
    phone: {
      value: userData.phone,
      validators: [
        {
          test: (v) => !v || FormValidator.isValidPhone(v),
          message:
            "Le numéro de téléphone n'est pas valide (ex: 06 12 34 56 78).",
        },
      ],
    },
    role: {
      value: userData.role,
      validators: [
        {
          test: (v) => FormValidator.isNotEmpty(v),
          message: "Le rôle est requis.",
        },
      ],
    },
    password: {
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
    password_confirm: {
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
  };

  // Si rôle client, valider l'entreprise
  if (userData.role === "client") {
    const companyVal = formData.get("company");
    rules.company = {
      value: companyVal,
      validators: [
        {
          test: (v) => FormValidator.isNotEmpty(v),
          message: "L'entreprise est requise pour un client.",
        },
      ],
    };
    if (companyVal === "__new__") {
      const newCompany = formData.get("new_company");
      rules.new_company = {
        value: newCompany,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le nom de la nouvelle entreprise est requis.",
          },
        ],
      };
    }
  }

  const isValid = FormValidator.validate(rules, formError, modalForm);
  if (!isValid) return;

  // Tout est valide : soumettre le formulaire en POST
  modalForm.submit();
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
