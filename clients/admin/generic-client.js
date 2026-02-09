// ========================================
// VARIABLES GLOBALES - MODALS
// ========================================
let modals = {
  editContact: {
    overlay: null,
    modal: null,
    form: null,
    closeBtn: null,
    cancelBtn: null,
    openBtn: null,
  },
  addAccount: {
    overlay: null,
    modal: null,
    form: null,
    closeBtn: null,
    cancelBtn: null,
    openBtn: null,
  },
  addContract: {
    overlay: null,
    modal: null,
    form: null,
    closeBtn: null,
    cancelBtn: null,
    openBtn: null,
  },
};

// ========================================
// INITIALISATION
// ========================================
document.addEventListener("DOMContentLoaded", () => {
  initializeModals();
});

function initializeModals() {
  // Modal Modifier Contact
  modals.editContact.overlay = document.getElementById("modal-edit-contact");
  modals.editContact.modal = document.getElementById("edit-contact-modal");
  modals.editContact.form = document.getElementById("edit-contact-form");
  modals.editContact.closeBtn = document.getElementById("close-edit-contact");
  modals.editContact.cancelBtn = document.getElementById("cancel-edit-contact");
  modals.editContact.openBtn = document.getElementById("btn-edit-contact");

  // Modal Ajouter Compte
  modals.addAccount.overlay = document.getElementById("modal-add-account");
  modals.addAccount.modal = document.getElementById("add-account-modal");
  modals.addAccount.form = document.getElementById("add-account-form");
  modals.addAccount.closeBtn = document.getElementById("close-add-account");
  modals.addAccount.cancelBtn = document.getElementById("cancel-add-account");
  modals.addAccount.openBtn = document.getElementById("btn-add-account");

  // Modal Ajouter Contrat
  modals.addContract.overlay = document.getElementById("modal-add-contract");
  modals.addContract.modal = document.getElementById("add-contract-modal");
  modals.addContract.form = document.getElementById("add-contract-form");
  modals.addContract.closeBtn = document.getElementById("close-add-contract");
  modals.addContract.cancelBtn = document.getElementById("cancel-add-contract");
  modals.addContract.openBtn = document.getElementById("btn-add-contract");

  // Attacher les événements
  attachModalEvents();
}

function attachModalEvents() {
  // Modal Modifier Contact
  modals.editContact.openBtn?.addEventListener("click", () =>
    openModal("editContact"),
  );
  modals.editContact.closeBtn?.addEventListener("click", () =>
    closeModal("editContact"),
  );
  modals.editContact.cancelBtn?.addEventListener("click", () =>
    closeModal("editContact"),
  );
  modals.editContact.form?.addEventListener("submit", handleEditContact);
  setupOverlayClose("editContact");

  // Gestion du mode pour le contact
  const contactModeRadios = document.querySelectorAll(
    'input[name="contact-mode"]',
  );
  contactModeRadios.forEach((radio) => {
    radio.addEventListener("change", (e) => toggleContactMode(e.target.value));
  });

  // Modal Ajouter Compte
  modals.addAccount.openBtn?.addEventListener("click", () =>
    openModal("addAccount"),
  );
  modals.addAccount.closeBtn?.addEventListener("click", () =>
    closeModal("addAccount"),
  );
  modals.addAccount.cancelBtn?.addEventListener("click", () =>
    closeModal("addAccount"),
  );
  modals.addAccount.form?.addEventListener("submit", handleAddAccount);
  setupOverlayClose("addAccount");

  // Gestion du mode pour le compte
  const accountModeRadios = document.querySelectorAll(
    'input[name="account-mode"]',
  );
  accountModeRadios.forEach((radio) => {
    radio.addEventListener("change", (e) => toggleAccountMode(e.target.value));
  });

  // Modal Ajouter Contrat
  modals.addContract.openBtn?.addEventListener("click", () =>
    openModal("addContract"),
  );
  modals.addContract.closeBtn?.addEventListener("click", () =>
    closeModal("addContract"),
  );
  modals.addContract.cancelBtn?.addEventListener("click", () =>
    closeModal("addContract"),
  );
  //modals.addContract.form?.addEventListener('submit', handleAddContract);
  setupOverlayClose("addContract");
}

function setupOverlayClose(modalName) {
  const modal = modals[modalName];

  // Fermer si clic sur l'overlay
  modal.overlay?.addEventListener("click", (e) => {
    if (e.target === modal.overlay) {
      closeModal(modalName);
    }
  });

  // Empêcher la fermeture si clic sur la modal
  modal.modal?.addEventListener("click", (e) => {
    e.stopPropagation();
  });
}

// ========================================
// GESTION DES MODES (Nouveau / Existant)
// ========================================
function toggleContactMode(mode) {
  const editSection = document.getElementById("edit-contact-section");
  const existingSection = document.getElementById("existing-contact-section");

  if (mode === "edit") {
    editSection.style.display = "block";
    existingSection.style.display = "none";
    // Rendre les champs obligatoires
    document.getElementById("contact-firstname").required = true;
    document.getElementById("contact-lastname").required = true;
    document.getElementById("contact-position").required = true;
    document.getElementById("contact-email").required = true;
    document.getElementById("contact-phone").required = true;
    document.getElementById("existing-contact-select").required = false;
  } else {
    editSection.style.display = "none";
    existingSection.style.display = "block";
    // Retirer l'obligation des champs
    document.getElementById("contact-firstname").required = false;
    document.getElementById("contact-lastname").required = false;
    document.getElementById("contact-position").required = false;
    document.getElementById("contact-email").required = false;
    document.getElementById("contact-phone").required = false;
    document.getElementById("existing-contact-select").required = true;
  }
}

function toggleAccountMode(mode) {
  const newSection = document.getElementById("new-account-section");
  const existingSection = document.getElementById("existing-account-section");

  if (mode === "new") {
    newSection.style.display = "block";
    existingSection.style.display = "none";
    // Rendre les champs du nouveau compte obligatoires
    document.getElementById("account-firstname").required = true;
    document.getElementById("account-lastname").required = true;
    document.getElementById("account-position").required = true;
    document.getElementById("account-email").required = true;
    document.getElementById("account-role").required = true;
    document.getElementById("existing-account-select").required = false;
    document.getElementById("existing-account-role").required = false;
  } else {
    newSection.style.display = "none";
    existingSection.style.display = "block";
    // Retirer l'obligation des champs du nouveau compte
    document.getElementById("account-firstname").required = false;
    document.getElementById("account-lastname").required = false;
    document.getElementById("account-position").required = false;
    document.getElementById("account-email").required = false;
    document.getElementById("account-role").required = false;
    document.getElementById("existing-account-select").required = true;
    document.getElementById("existing-account-role").required = true;
  }
}

// ========================================
// GESTION DES MODALS
// ========================================
function openModal(modalName) {
  const modal = modals[modalName];

  modal.overlay?.classList.add("active");
  console.log(modal);
  setTimeout(() => {
    modal.modal?.classList.add("active");
  }, 10);

  // Réinitialiser le formulaire si c'est add/new
  if (modalName === "addAccount" || modalName === "addContract") {
    modal.form?.reset();
  }
}

function closeModal(modalName) {
  const modal = modals[modalName];

  modal.modal?.classList.remove("active");
  setTimeout(() => {
    modal.overlay?.classList.remove("active");
  }, 300);
}

// ========================================
// GESTION DES FORMULAIRES
// ========================================
function handleEditContact(event) {
  event.preventDefault();

  const formData = new FormData(modals.editContact.form);
  const mode = formData.get("contact-mode");

  if (mode === "existing") {
    // Rattacher un compte existant comme contact principal
    const existingContactId = formData.get("existingContactId");

    if (!existingContactId) {
      showNotification("Veuillez sélectionner un compte", "error");
      return;
    }

    console.log("Rattacher le compte existant:", existingContactId);

    closeModal("editContact");
    showNotification("Contact principal mis à jour avec succès !", "success");
  } else {
    // Modifier les informations du contact existant
    const contactData = {
      firstname: formData.get("firstname"),
      lastname: formData.get("lastname"),
      position: formData.get("position"),
      email: formData.get("email"),
      phone: formData.get("phone"),
    };

    // Créer ou récupérer l'élément d'erreur
    let formError = modals.editContact.form.querySelector(".form-error-msg");
    if (!formError) {
      formError = document.createElement("div");
      formError.className = "form-error-msg";
      formError.style.cssText =
        "color: #ff4757; background: rgba(255,71,87,0.1); border: 1px solid rgba(255,71,87,0.3); padding: 10px; border-radius: 6px; margin-bottom: 15px; display: none; font-size: 14px;";
      modals.editContact.form.insertBefore(
        formError,
        modals.editContact.form.firstChild,
      );
    }

    const isValid = FormValidator.validate(
      {
        firstname: {
          value: contactData.firstname,
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
        lastname: {
          value: contactData.lastname,
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
          value: contactData.email,
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
          value: contactData.phone,
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "Le téléphone est requis.",
            },
            {
              test: (v) => FormValidator.isValidPhone(v),
              message:
                "Le numéro de téléphone n'est pas valide (ex: 06 12 34 56 78).",
            },
          ],
        },
      },
      formError,
      modals.editContact.form,
    );

    if (!isValid) return;

    console.log("Modification du contact:", contactData);

    closeModal("editContact");
    showNotification("Contact modifié avec succès !", "success");

    // Mettre à jour l'affichage
    updateContactDisplay(contactData);
  }
}

function handleAddAccount(event) {
  event.preventDefault();

  const formData = new FormData(modals.addAccount.form);
  const mode = formData.get("account-mode");

  if (mode === "existing") {
    // Rattacher un compte existant
    const existingAccountId = formData.get("existingAccountId");
    const existingRole = formData.get("existingRole");

    if (!existingAccountId || !existingRole) {
      showNotification("Veuillez sélectionner un compte et un rôle", "error");
      return;
    }

    const linkData = {
      accountId: existingAccountId,
      role: existingRole,
    };

    console.log("Rattacher un compte existant:", linkData);

    closeModal("addAccount");
    showNotification("Compte rattaché avec succès !", "success");
  } else {
    // Créer un nouveau compte
    const accountData = {
      firstname: formData.get("firstname"),
      lastname: formData.get("lastname"),
      position: formData.get("position"),
      email: formData.get("email"),
      phone: formData.get("phone"),
      role: formData.get("role"),
      sendInvitation: formData.get("sendInvitation") === "on",
    };

    // Créer ou récupérer l'élément d'erreur
    let formError = modals.addAccount.form.querySelector(".form-error-msg");
    if (!formError) {
      formError = document.createElement("div");
      formError.className = "form-error-msg";
      formError.style.cssText =
        "color: #ff4757; background: rgba(255,71,87,0.1); border: 1px solid rgba(255,71,87,0.3); padding: 10px; border-radius: 6px; margin-bottom: 15px; display: none; font-size: 14px;";
      modals.addAccount.form.insertBefore(
        formError,
        modals.addAccount.form.firstChild,
      );
    }

    const isValid = FormValidator.validate(
      {
        firstname: {
          value: accountData.firstname,
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
        lastname: {
          value: accountData.lastname,
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
          value: accountData.email,
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
          value: accountData.phone,
          validators: [
            {
              test: (v) => FormValidator.isValidPhone(v),
              message:
                "Le numéro de téléphone n'est pas valide (ex: 06 12 34 56 78).",
            },
          ],
        },
      },
      formError,
      modals.addAccount.form,
    );

    if (!isValid) return;

    console.log("Nouveau compte utilisateur:", accountData);

    closeModal("addAccount");
    showNotification("Compte créé avec succès !", "success");
  }
}

// ========================================
// MISE À JOUR DE L'AFFICHAGE
// ========================================
function updateContactDisplay(contactData) {
  // Mettre à jour le nom
  const contactNameElement = document.querySelector(".contact-name");
  if (contactNameElement) {
    contactNameElement.textContent = `${contactData.firstname} ${contactData.lastname}`;
  }

  // Mettre à jour le titre
  const contactTitleElement = document.querySelector(".contact-title");
  if (contactTitleElement) {
    contactTitleElement.textContent = contactData.position;
  }

  // Mettre à jour l'email
  const emailElement = document.querySelector(".contact-item span");
  if (emailElement) {
    emailElement.textContent = contactData.email;
  }

  // Mettre à jour le téléphone
  const phoneElements = document.querySelectorAll(".contact-item span");
  if (phoneElements.length > 1) {
    phoneElements[1].textContent = contactData.phone;
  }

  // Mettre à jour les initiales
  const avatarElement = document.querySelector(".contact-avatar");
  if (avatarElement) {
    const initials =
      `${contactData.firstname[0]}${contactData.lastname[0]}`.toUpperCase();
    avatarElement.textContent = initials;
  }
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
