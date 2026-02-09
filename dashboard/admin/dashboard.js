document.addEventListener("DOMContentLoaded", () => {
  // ========================================
  // MODALS - Ouverture / Fermeture
  // ========================================

  // Mapping boutons -> modals
  const modalMap = [
    { btnSelector: "#tickets .btn-primary", modalId: "modal-ticket" },
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
  // SOUMISSION DES FORMULAIRES
  // ========================================

  document.querySelectorAll(".modal-form").forEach((form) => {
    form.addEventListener("submit", (e) => {
      e.preventDefault();

      // Vérifier les champs required
      const required = form.querySelectorAll("[required]");
      let valid = true;
      required.forEach((field) => {
        if (!field.value.trim()) valid = false;
      });

      const errEl = form.querySelector(".form-error");
      if (!valid) {
        if (errEl) {
          errEl.textContent = "Veuillez remplir tous les champs obligatoires.";
          errEl.classList.add("visible");
        }
        return;
      }

      // Validation spécifique des emails
      const emailFields = form.querySelectorAll(
        'input[type="email"], input[name*="email"]',
      );
      for (const emailField of emailFields) {
        if (emailField.value && !FormValidator.isValidEmail(emailField.value)) {
          if (errEl) {
            errEl.textContent =
              "Veuillez saisir une adresse email valide (ex: nom@domaine.com).";
            errEl.classList.add("visible");
          }
          FormValidator.markFieldError(emailField);
          emailField.focus();
          return;
        }
      }

      // Validation spécifique des téléphones
      const phoneFields = form.querySelectorAll(
        'input[type="tel"], input[name*="phone"]',
      );
      for (const phoneField of phoneFields) {
        if (phoneField.value && !FormValidator.isValidPhone(phoneField.value)) {
          if (errEl) {
            errEl.textContent =
              "Le numéro de téléphone n'est pas valide (ex: 06 12 34 56 78).";
            errEl.classList.add("visible");
          }
          FormValidator.markFieldError(phoneField);
          phoneField.focus();
          return;
        }
      }

      // Validation des dates (fin > début)
      const startDate = form.querySelector(
        'input[name*="start"], input[name*="Start"]',
      );
      const endDate = form.querySelector(
        'input[name*="end"], input[name*="End"]',
      );
      if (startDate && endDate && startDate.value && endDate.value) {
        if (
          !FormValidator.isEndDateAfterStartDate(startDate.value, endDate.value)
        ) {
          if (errEl) {
            errEl.textContent =
              "La date de fin doit être postérieure à la date de début.";
            errEl.classList.add("visible");
          }
          FormValidator.markFieldError(endDate);
          endDate.focus();
          return;
        }
      }

      // Validation des nombres positifs (heures, montants)
      const numberFields = form.querySelectorAll('input[type="number"]');
      for (const numField of numberFields) {
        if (numField.value && !FormValidator.isPositiveNumber(numField.value)) {
          if (errEl) {
            errEl.textContent =
              "Les valeurs numériques doivent être supérieures à 0.";
            errEl.classList.add("visible");
          }
          FormValidator.markFieldError(numField);
          numField.focus();
          return;
        }
      }

      if (errEl) errEl.classList.remove("visible");
      FormValidator.clearAllFieldErrors(form);

      // Log des données (mock)
      const formData = new FormData(form);
      const data = {};
      formData.forEach((val, key) => (data[key] = val));
      console.log("Formulaire soumis:", data);

      // Fermer la modal
      const overlay = form.closest(".modal-overlay");
      if (overlay) closeModal(overlay);

      // Notification de succès
      const title = overlay.querySelector(".modal-header h2").textContent;
      showNotification(`${title} - Effectué avec succès !`, "success");
    });
  });

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

  // ========================================
  // BADGES COULEURS DYNAMIQUES
  // ========================================

  // Couleurs pour les billing badges au clic sur les lignes tickets
  const billingColors = {
    "billing-inclus": { bg: "rgba(84, 197, 177, 0.2)", color: "#54C5B1" },
    "billing-facturable": { bg: "rgba(255, 184, 34, 0.2)", color: "#ffb822" },
  };

  // Priority badges
  const priorityColors = {
    high: { bg: "rgba(255, 71, 87, 0.2)", color: "#ff4757" },
    medium: { bg: "rgba(255, 184, 34, 0.2)", color: "#ffb822" },
    low: { bg: "rgba(84, 197, 177, 0.2)", color: "#54C5B1" },
  };

  // Status badges
  const statusColors = {
    "status-progress": { bg: "rgba(52, 152, 219, 0.2)", color: "#3498db" },
    "status-pending": { bg: "rgba(255, 184, 34, 0.2)", color: "#ffb822" },
    "status-urgent": { bg: "rgba(255, 71, 87, 0.2)", color: "#ff4757" },
    "status-review": { bg: "rgba(155, 89, 182, 0.2)", color: "#9b59b6" },
  };

  // Appliquer les couleurs depuis les classes existantes
  document.querySelectorAll(".billing-badge").forEach((badge) => {
    for (const [cls, style] of Object.entries(billingColors)) {
      if (badge.classList.contains(cls)) {
        badge.style.backgroundColor = style.bg;
        badge.style.color = style.color;
      }
    }
  });

  document.querySelectorAll(".priority-badge").forEach((badge) => {
    for (const [cls, style] of Object.entries(priorityColors)) {
      if (badge.classList.contains(cls)) {
        badge.style.backgroundColor = style.bg;
        badge.style.color = style.color;
      }
    }
  });

  document.querySelectorAll(".status-badge").forEach((badge) => {
    for (const [cls, style] of Object.entries(statusColors)) {
      if (badge.classList.contains(cls)) {
        badge.style.backgroundColor = style.bg;
        badge.style.color = style.color;
      }
    }
  });

  // ========================================
  // STAT CARDS - Animation au survol
  // ========================================

  document.querySelectorAll(".stat-card").forEach((card) => {
    card.addEventListener("mouseenter", () => {
      card.style.transform = "translateY(-5px)";
    });
    card.addEventListener("mouseleave", () => {
      card.style.transform = "translateY(0)";
    });
  });
});
