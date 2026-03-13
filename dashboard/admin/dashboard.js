document.addEventListener("DOMContentLoaded", () => {
  // ========================================
  // FILTRAGE PROJETS PAR CLIENT (Ticket Modal)
  // ========================================

  const clientSelect = document.getElementById("ticket-client");
  const projectSelect = document.getElementById("ticket-project");

  if (clientSelect && projectSelect && window.clientProjects) {
    clientSelect.addEventListener("change", function () {
      const clientId = this.value;
      // Vider la liste des projets
      projectSelect.innerHTML =
        '<option value="">Sélectionner un projet</option>';
      if (window.clientProjects[clientId]) {
        window.clientProjects[clientId].forEach(function (projet) {
          const opt = document.createElement("option");
          opt.value = projet.id;
          opt.textContent = projet.nom;
          projectSelect.appendChild(opt);
        });
      }
    });
  }
  // ========================================
  // CALCUL MONTANT TOTAL CONTRAT (heures * taux)
  // ========================================

  const heuresInput = document.getElementById("contract-heures-totales");
  const tauxInput = document.getElementById("contract-taux-horaire");
  const montantDisplay = document.getElementById("contract-montant-total");
  const montantHidden = document.getElementById(
    "contract-montant-total-hidden",
  );

  function updateMontantTotal() {
    const heures = parseFloat(heuresInput?.value) || 0;
    const taux = parseFloat(tauxInput?.value) || 0;
    const total = (heures * taux).toFixed(2);
    if (montantDisplay) montantDisplay.textContent = total + " €";
    if (montantHidden) montantHidden.value = total;
  }

  if (heuresInput) heuresInput.addEventListener("input", updateMontantTotal);
  if (tauxInput) tauxInput.addEventListener("input", updateMontantTotal);

  // ========================================
  // FILTRAGE CONTRATS PAR CLIENT (Project Modal)
  // ========================================

  const projectClientSelect = document.getElementById("client-select");
  const contractSelect = document.getElementById("contract-select");

  if (projectClientSelect && contractSelect && window.clientContrats) {
    projectClientSelect.addEventListener("change", function () {
      const clientId = this.value;
      contractSelect.innerHTML = "";

      if (!clientId) {
        contractSelect.innerHTML =
          '<option value="">Sélectionnez d\'abord un client</option>';
        return;
      }

      contractSelect.innerHTML = '<option value="">Aucun contrat</option>';
      const contrats = window.clientContrats[clientId] || [];
      contrats.forEach(function (ct) {
        const opt = document.createElement("option");
        opt.value = ct.id;
        opt.textContent = ct.nom;
        contractSelect.appendChild(opt);
      });
    });
  }

  // ========================================
  // MODALS - Ouverture / Fermeture
  // ========================================

  // Mapping boutons -> modals
  const modalMap = [
    { btnSelector: "#tickets .btn-primary", modalId: "modal-ticket" },
    { btnSelector: "#projects .btn-primary", modalId: "modal-project" },
    { btnSelector: "#users .btn-primary", modalId: "modal-user" },
    { btnSelector: "#clients .btn-primary", modalId: "modal-client" },
    { btnSelector: "#contracts .btn-primary", modalId: "modal-contract" },
  ];

  modalMap.forEach(({ btnSelector, modalId }) => {
    const btn = document.querySelector(btnSelector);
    const overlay = document.getElementById(modalId);
    if (!btn || !overlay) return;

    btn.addEventListener("click", () => openModal(overlay));
  });

  // Fermer via bouton X ou Annuler
  document.querySelectorAll(".modal-close").forEach((btn) => {
    btn.addEventListener("click", () => {
      const overlay = btn.closest(".modal-overlay");
      if (overlay) closeModal(overlay);
    });
  });

  document.querySelectorAll(".btn-cancel").forEach((btn) => {
    btn.addEventListener("click", () => {
      const overlay = btn.closest(".modal-overlay");
      if (overlay) closeModal(overlay);
    });
  });

  // Fermer en cliquant sur l'overlay
  document.querySelectorAll(".modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) closeModal(overlay);
    });
  });

  // Touche Escape
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      const active = document.querySelector(".modal-overlay.active");
      if (active) closeModal(active);
    }
  });

  function openModal(overlay) {
    overlay.classList.add("active");
    const form = overlay.querySelector("form");
    if (form) form.reset();
    const err = overlay.querySelector(".form-error");
    if (err) err.classList.remove("visible");
  }

  function closeModal(overlay) {
    overlay.classList.remove("active");
  }

  // ========================================
  // TOGGLE SECTION ENTREPRISE (rôle = client)
  // ========================================

  const userRoleSelect = document.getElementById("user-role");
  const sectionCompany = document.getElementById("section-company");
  const companySelect = document.getElementById("user-company");
  const newCompanyGroup = document.getElementById("new-company-group");
  const newCompanyInput = document.getElementById("user-new-company");

  if (userRoleSelect && sectionCompany) {
    userRoleSelect.addEventListener("change", () => {
      if (userRoleSelect.value === "client") {
        sectionCompany.style.display = "";
        if (companySelect) companySelect.required = true;
      } else {
        sectionCompany.style.display = "none";
        if (companySelect) {
          companySelect.required = false;
          companySelect.value = "";
        }
        if (newCompanyGroup) newCompanyGroup.style.display = "none";
        if (newCompanyInput) {
          newCompanyInput.required = false;
          newCompanyInput.value = "";
        }
      }
    });
  }

  if (companySelect && newCompanyGroup && newCompanyInput) {
    companySelect.addEventListener("change", () => {
      if (companySelect.value === "__new__") {
        newCompanyGroup.style.display = "";
        newCompanyInput.required = true;
      } else {
        newCompanyGroup.style.display = "none";
        newCompanyInput.required = false;
        newCompanyInput.value = "";
      }
    });
  }

  // ========================================
  // SOUMISSION DES FORMULAIRES
  // ========================================

  document.querySelectorAll(".modal-form").forEach((form) => {
    form.addEventListener("submit", (e) => {
      e.preventDefault();

      const errEl = form.querySelector(".form-error");
      FormValidator.hideError(errEl);
      FormValidator.clearAllFieldErrors(form);

      // Construction dynamique des règles de validation
      const rules = {};

      // 1. Champs obligatoires
      form.querySelectorAll("[required]").forEach((field) => {
        const key = field.name || field.id;
        if (!key || rules[key]) return;
        rules[key] = {
          value: field.value,
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "Veuillez remplir tous les champs obligatoires.",
            },
          ],
        };
      });

      // 2. Emails
      form.querySelectorAll('input[type="email"]').forEach((field) => {
        const key = field.name || field.id;
        if (!rules[key]) rules[key] = { value: field.value, validators: [] };
        rules[key].validators.push({
          test: (v) => !String(v).trim() || FormValidator.isValidEmail(v),
          message:
            "Veuillez saisir une adresse email valide (ex: nom@domaine.com).",
        });
      });

      // 3. Mot de passe (force + confirmation)
      const pwdField = form.querySelector('input[name="password"]');
      const pwdConfirm = form.querySelector('input[name="password_confirm"]');
      if (pwdField && pwdField.value) {
        if (!rules["password"])
          rules["password"] = { value: pwdField.value, validators: [] };
        rules["password"].validators.push({
          test: (v) => FormValidator.isValidPassword(v),
          message:
            "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.",
        });
        if (pwdConfirm) {
          if (!rules["password_confirm"])
            rules["password_confirm"] = {
              value: pwdConfirm.value,
              validators: [],
            };
          rules["password_confirm"].validators.push({
            test: () =>
              FormValidator.passwordsMatch(pwdField.value, pwdConfirm.value),
            message: "Les mots de passe ne correspondent pas.",
          });
        }
      }

      // 4. Téléphones (optionnels)
      form.querySelectorAll('input[type="tel"]').forEach((field) => {
        const key = field.name || field.id;
        if (!rules[key]) rules[key] = { value: field.value, validators: [] };
        rules[key].validators.push({
          test: (v) => FormValidator.isValidPhone(v),
          message:
            "Le numéro de téléphone n'est pas valide (ex: 06 12 34 56 78).",
        });
      });

      // 5. Noms / Prénoms
      form
        .querySelectorAll(
          'input[name="name"], input[name="surname"], input[name*="firstname"], input[name*="lastname"]',
        )
        .forEach((field) => {
          const key = field.name || field.id;
          if (!rules[key]) rules[key] = { value: field.value, validators: [] };
          rules[key].validators.push({
            test: (v) => !String(v).trim() || FormValidator.isValidName(v),
            message:
              "Le nom doit contenir au moins 2 caractères (lettres, accents, tirets).",
          });
        });

      // 6. Dates (fin > début)
      const startDate = form.querySelector(
        'input[name*="start"], input[name*="Start"]',
      );
      const endDate = form.querySelector(
        'input[name*="end"], input[name*="End"]',
      );
      if (startDate && endDate && startDate.value && endDate.value) {
        const key = endDate.name || endDate.id;
        if (!rules[key]) rules[key] = { value: endDate.value, validators: [] };
        rules[key].validators.push({
          test: () =>
            FormValidator.isEndDateAfterStartDate(
              startDate.value,
              endDate.value,
            ),
          message: "La date de fin doit être postérieure à la date de début.",
        });
      }

      // 7. Nombres positifs
      form.querySelectorAll('input[type="number"]').forEach((field) => {
        const key = field.name || field.id;
        if (!rules[key]) rules[key] = { value: field.value, validators: [] };
        rules[key].validators.push({
          test: (v) => !String(v).trim() || FormValidator.isPositiveNumber(v),
          message: "Les valeurs numériques doivent être supérieures à 0.",
        });
      });

      // Exécuter la validation via FormValidator.validate()
      if (!FormValidator.validate(rules, errEl, form)) {
        return;
      }

      form.submit();

      // Fermer la modal
      const overlay = form.closest(".modal-overlay");
      if (overlay) closeModal(overlay);

      // Notification de succès
      const title = overlay.querySelector(".modal-header h2").textContent;
      showNotification(`${title} - Effectué avec succès !`, "success");
    });
  });

  // ========================================
  // SUPPRESSION UTILISATEURS
  // ========================================

  let formToDelete = null;
  const confirmOverlay = document.getElementById("modal-confirm-delete");
  const confirmBtn = document.getElementById("btn-confirm-delete");
  const deleteUserName = document.getElementById("delete-user-name");

  // Intercepter le submit des formulaires de suppression
  document.querySelectorAll(".inline-form").forEach((form) => {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      formToDelete = form;
      const row = form.closest("tr");
      const name = row
        ? row.querySelector(".user-cell span")?.textContent
        : "cet utilisateur";
      deleteUserName.textContent = name;
      openModal(confirmOverlay);
    });
  });

  // Clic sur "Supprimer" dans la modal de confirmation → soumet le form
  if (confirmBtn) {
    confirmBtn.addEventListener("click", () => {
      if (formToDelete) {
        formToDelete.submit();
      }
    });
  }

  // ========================================
  // NOTIFICATIONS
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
});
