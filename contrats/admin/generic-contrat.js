document.addEventListener("DOMContentLoaded", () => {
  // ========================================
  // DATE DU JOUR PAR DÉFAUT
  // ========================================

  function setTodayDate() {
    const timeDate = document.getElementById("time-date");
    if (timeDate) {
      const today = new Date().toISOString().split("T")[0];
      timeDate.value = today;
    }
  }

  setTodayDate();

  // ========================================
  // MODALS - Ouverture / Fermeture
  // ========================================

  const modalMap = [
    { btnSelector: ".btn-edit", modalId: "modal-edit-overlay" },
    { btnSelector: ".btn-add-small", modalId: "modal-time-overlay" },
  ];

  modalMap.forEach(({ btnSelector, modalId }) => {
    const btn = document.querySelector(btnSelector);
    const overlay = document.getElementById(modalId);
    if (!btn || !overlay) return;
    btn.addEventListener("click", () => openModal(overlay));
  });

  // Fermer via bouton X
  document.querySelectorAll(".modal-close").forEach((btn) => {
    btn.addEventListener("click", () => {
      const overlay = btn.closest(".modal-overlay");
      if (overlay) closeModal(overlay);
    });
  });

  // Fermer via bouton Annuler
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
    overlay.style.display = "flex";
    overlay.classList.add("active");
    const form = overlay.querySelector("form");
    // Reset seulement la modal temps (pas l'edit qui a des valeurs pré-remplies)
    if (overlay.id === "modal-time-overlay" && form) {
      form.reset();
      setTodayDate();
    }
    const err = overlay.querySelector(".form-error");
    if (err) err.classList.remove("visible", "show");
    if (typeof FormValidator !== "undefined" && form) {
      FormValidator.clearAllFieldErrors(form);
    }
  }

  function closeModal(overlay) {
    overlay.classList.remove("active");
    setTimeout(() => {
      overlay.style.display = "none";
    }, 300);
  }

  // ========================================
  // SOUMISSION DES FORMULAIRES
  // ========================================

  document.querySelectorAll(".modal-form").forEach((form) => {
    form.addEventListener("submit", (e) => {
      e.preventDefault();

      if (typeof FormValidator === "undefined") {
        form.submit();
        return;
      }

      const errEl = form.querySelector(".form-error");
      FormValidator.hideError(errEl);
      FormValidator.clearAllFieldErrors(form);

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

      // 2. Dates (fin > début)
      const startDate = form.querySelector('input[name="date_debut"]');
      const endDate = form.querySelector('input[name="date_fin"]');
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

      // 3. Nombres positifs
      form.querySelectorAll('input[type="number"]').forEach((field) => {
        const key = field.name || field.id;
        if (!rules[key]) rules[key] = { value: field.value, validators: [] };
        rules[key].validators.push({
          test: (v) => !String(v).trim() || FormValidator.isPositiveNumber(v),
          message: "Les valeurs numériques doivent être supérieures à 0.",
        });
      });

      if (!FormValidator.validate(rules, errEl, form)) {
        return;
      }

      form.submit();
    });
  });
});
