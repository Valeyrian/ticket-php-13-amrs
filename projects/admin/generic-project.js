document.addEventListener("DOMContentLoaded", () => {
  // ========================================
  // BADGES: couleurs dynamiques au clic
  // ========================================

  const statusBadge = document.querySelector(".project-status-badge");

  const statusColors = {
    active: {
      bg: "rgba(84, 197, 177, 0.2)",
      color: "#54C5B1",
      border: "rgba(84, 197, 177, 0.5)",
      text: "Actif",
    },
    paused: {
      bg: "rgba(255, 193, 7, 0.2)",
      color: "#ffc107",
      border: "rgba(255, 193, 7, 0.5)",
      text: "En pause",
    },
    completed: {
      bg: "rgba(46, 204, 113, 0.2)",
      color: "#2ecc71",
      border: "rgba(46, 204, 113, 0.5)",
      text: "Terminé",
    },
    archived: {
      bg: "rgba(149, 165, 166, 0.2)",
      color: "#95a5a6",
      border: "rgba(149, 165, 166, 0.5)",
      text: "Archivé",
    },
  };

  function applyBadgeColor(badge, statusKey) {
    const style = statusColors[statusKey];
    if (style && badge) {
      badge.style.backgroundColor = style.bg;
      badge.style.color = style.color;
      badge.style.border = `1px solid ${style.border}`;
      badge.textContent = style.text;
      badge.className = "project-status-badge " + statusKey;
    }
  }

  // ========================================
  // TICKETS TABLE: couleurs des badges
  // ========================================

  // Couleurs dynamiques pour les selects de priorité
  const priorityColors = {
    high: {
      bg: "rgba(255, 71, 87, 0.2)",
      color: "#ff4757",
      border: "rgba(255, 71, 87, 0.5)",
    },
    medium: {
      bg: "rgba(255, 184, 34, 0.2)",
      color: "#ffb822",
      border: "rgba(255, 184, 34, 0.5)",
    },
    low: {
      bg: "rgba(46, 204, 113, 0.2)",
      color: "#2ecc71",
      border: "rgba(46, 204, 113, 0.5)",
    },
    urgent: {
      bg: "rgba(231, 76, 60, 0.2)",
      color: "#e74c3c",
      border: "rgba(231, 76, 60, 0.5)",
    },
  };

  const statusTableColors = {
    progress: {
      bg: "rgba(84, 197, 177, 0.2)",
      color: "#54C5B1",
      border: "rgba(84, 197, 177, 0.5)",
    },
    pending: {
      bg: "rgba(255, 184, 34, 0.2)",
      color: "#ffb822",
      border: "rgba(255, 184, 34, 0.5)",
    },
    completed: {
      bg: "rgba(46, 204, 113, 0.2)",
      color: "#2ecc71",
      border: "rgba(46, 204, 113, 0.5)",
    },
    nouveau: {
      bg: "rgba(52, 152, 219, 0.2)",
      color: "#3498db",
      border: "rgba(52, 152, 219, 0.5)",
    },
  };

  const billingColors = {
    inclus: {
      bg: "rgba(84, 197, 177, 0.2)",
      color: "#54C5B1",
      border: "rgba(84, 197, 177, 0.5)",
    },
    facturable: {
      bg: "rgba(255, 184, 34, 0.2)",
      color: "#ffb822",
      border: "rgba(255, 184, 34, 0.5)",
    },
  };

  // ========================================
  // CACHER FACTURATION SI INCLUS
  // ========================================

  // Pour chaque ligne de ticket, si le type est "Inclus", cacher les infos de facturation
  function updateBillingVisibility() {
    document.querySelectorAll(".tickets-table tbody tr").forEach((row) => {
      const billingBadge = row.querySelector(".billing-badge");
      if (!billingBadge) return;

      const isInclus = billingBadge.classList.contains("inclus");
      const timeCell = row.querySelector("td:nth-child(7)");

      // Si inclus, on masque la colonne temps facturable (pas de surcoût)
      if (isInclus) {
        if (timeCell) {
          timeCell.style.color = "var(--text-gray)";
        }
      } else {
        if (timeCell) {
          timeCell.style.color = "#ffb822";
          timeCell.style.fontWeight = "bold";
        }
      }
    });
  }

  updateBillingVisibility();

  // ========================================
  // MODALS
  // ========================================

  // --- Modal Modifier Projet ---
  const editOverlay = document.getElementById("modal-edit-overlay");
  const editModal = document.getElementById("edit-project-modal");
  const editForm = document.getElementById("edit-project-form");
  const closeEditBtn = document.getElementById("close-edit-modal");
  const cancelEditBtn = document.getElementById("cancel-edit-modal");
  const btnEditProject = document.querySelector(".project-actions .btn-edit");
  const btnEditDescription = document.querySelector(
    ".project-description + .btn-secondary",
  );

  function openModal(overlay, modal) {
    overlay.style.display = "flex";
    setTimeout(() => {
      overlay.classList.add("active");
      modal.classList.add("active");
    }, 10);
  }

  function closeModal(overlay, modal) {
    modal.classList.remove("active");
    overlay.classList.remove("active");
    setTimeout(() => {
      overlay.style.display = "none";
    }, 300);
  }

  // Ouvrir modal modifier projet
  btnEditProject?.addEventListener("click", () => {
    openModal(editOverlay, editModal);
  });

  // Ouvrir modal modifier projet depuis le bouton description
  btnEditDescription?.addEventListener("click", () => {
    openModal(editOverlay, editModal);
  });

  // Fermer modal modifier
  closeEditBtn?.addEventListener("click", () =>
    closeModal(editOverlay, editModal),
  );
  cancelEditBtn?.addEventListener("click", () =>
    closeModal(editOverlay, editModal),
  );
  editOverlay?.addEventListener("click", (e) => {
    if (e.target === editOverlay) closeModal(editOverlay, editModal);
  });

  // Soumission du formulaire modifier
  editForm?.addEventListener("submit", (e) => {
    e.preventDefault();

    const formData = new FormData(editForm);
    const data = {
      name: formData.get("projectName"),
      description: formData.get("projectDescription"),
      startDate: formData.get("startDate"),
      endDate: formData.get("endDate"),
      status: formData.get("projectStatus"),
    };

    const err = document.getElementById("edit-form-error");
    FormValidator.hideError(err);
    FormValidator.clearAllFieldErrors(editForm);

    // Validation complète
    const isValid = FormValidator.validate(
      {
        projectName: {
          value: data.name,
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
          value: data.description,
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "La description est requise.",
            },
            {
              test: (v) => FormValidator.hasMinLength(v, 10),
              message: "La description doit contenir au moins 10 caractères.",
            },
          ],
        },
        startDate: {
          value: data.startDate,
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "La date de début est requise.",
            },
          ],
        },
        endDate: {
          value: data.endDate,
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "La date de fin est requise.",
            },
            {
              test: (v) =>
                FormValidator.isEndDateAfterStartDate(data.startDate, v),
              message:
                "La date de fin doit être postérieure à la date de début.",
            },
          ],
        },
      },
      err,
      editForm,
    );

    if (!isValid) return;

    // Appliquer les modifications à la page
    const projectName = document.querySelector(".project-name");
    const projectDesc = document.querySelector(".project-description");

    if (projectName) projectName.textContent = data.name;
    if (projectDesc) projectDesc.textContent = data.description;
    if (statusBadge) applyBadgeColor(statusBadge, data.status);

    // Mettre à jour les dates dans la card info
    const infoValues = document.querySelectorAll(
      ".info-card:first-child .info-value",
    );
    if (infoValues.length >= 4) {
      infoValues[2].textContent = formatDate(data.startDate);
      infoValues[3].textContent = formatDate(data.endDate);
    }

    console.log("Projet modifié:", data);
    closeModal(editOverlay, editModal);
    showNotification("Projet modifié avec succès !", "success");
  });

  // --- Modal Nouveau Ticket ---
  const ticketOverlay = document.getElementById("modal-ticket-overlay");
  const ticketModal = document.getElementById("add-ticket-modal");
  const ticketForm = document.getElementById("add-ticket-form");
  const closeTicketBtn = document.getElementById("close-ticket-modal");
  const cancelTicketBtn = document.getElementById("cancel-ticket-modal");
  const btnAddTicket = document.querySelector(".section-header .btn-add");

  // On cherche le bon bouton "Nouveau ticket"
  document.querySelectorAll(".section-header .btn-add").forEach((btn) => {
    if (btn.textContent.trim() === "Nouveau ticket") {
      btn.addEventListener("click", () => {
        openModal(ticketOverlay, ticketModal);
      });
    }
  });

  closeTicketBtn?.addEventListener("click", () =>
    closeModal(ticketOverlay, ticketModal),
  );
  cancelTicketBtn?.addEventListener("click", () =>
    closeModal(ticketOverlay, ticketModal),
  );
  ticketOverlay?.addEventListener("click", (e) => {
    if (e.target === ticketOverlay) closeModal(ticketOverlay, ticketModal);
  });

  // Soumission nouveau ticket
  ticketForm?.addEventListener("submit", (e) => {
    e.preventDefault();

    const formData = new FormData(ticketForm);
    const data = {
      title: formData.get("ticketTitle"),
      description: formData.get("ticketDescription"),
      priority: formData.get("ticketPriority"),
      assignee: formData.get("assigneeId"),
      category: formData.get("ticketCategory"),
      estimatedHours: formData.get("estimatedHours"),
      dueDate: formData.get("dueDate"),
    };

    const err = document.getElementById("ticket-form-error");
    FormValidator.hideError(err);
    FormValidator.clearAllFieldErrors(ticketForm);

    // Validation complète
    const isValid = FormValidator.validate(
      {
        ticketTitle: {
          value: data.title,
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "Le titre du ticket est requis.",
            },
            {
              test: (v) => FormValidator.hasMinLength(v, 3),
              message: "Le titre doit contenir au moins 3 caractères.",
            },
          ],
        },
        ticketDescription: {
          value: data.description,
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "La description du ticket est requise.",
            },
            {
              test: (v) => FormValidator.hasMinLength(v, 10),
              message: "La description doit contenir au moins 10 caractères.",
            },
          ],
        },
        ticketPriority: {
          value: data.priority,
          validators: [
            {
              test: (v) => FormValidator.isNotEmpty(v),
              message: "La priorité est requise.",
            },
          ],
        },
        estimatedHours: {
          value: data.estimatedHours,
          validators: [
            {
              test: (v) => !v || FormValidator.isPositiveNumber(v),
              message: "Les heures estimées doivent être supérieures à 0.",
            },
          ],
        },
      },
      err,
      ticketForm,
    );

    if (!isValid) return;

    // Ajouter dans la timeline
    addTimelineEntry(
      `<strong>Ticket #${104 + (tbody?.querySelectorAll("tr").length || 0) - 1}</strong> "${escapeHtml(data.title)}" créé par Admin Principal`,
    );

    console.log("Nouveau ticket:", data);
    ticketForm.reset();
    closeModal(ticketOverlay, ticketModal);
    showNotification("Ticket créé avec succès !", "success");
  });

  // --- Modal Ajouter Collaborateur ---
  const collabOverlay = document.getElementById("modal-collab-overlay");
  const collabModal = document.getElementById("add-collab-modal");
  const collabForm = document.getElementById("add-collab-form");
  const closeCollabBtn = document.getElementById("close-collab-modal");
  const cancelCollabBtn = document.getElementById("cancel-collab-modal");

  // Bouton "Ajouter un collaborateur"
  document.querySelectorAll(".section-header .btn-add").forEach((btn) => {
    if (btn.textContent.trim() === "Ajouter un collaborateur") {
      btn.addEventListener("click", () => {
        openModal(collabOverlay, collabModal);
      });
    }
  });

  closeCollabBtn?.addEventListener("click", () =>
    closeModal(collabOverlay, collabModal),
  );
  cancelCollabBtn?.addEventListener("click", () =>
    closeModal(collabOverlay, collabModal),
  );
  collabOverlay?.addEventListener("click", (e) => {
    if (e.target === collabOverlay) closeModal(collabOverlay, collabModal);
  });

  // Soumission ajout collaborateur
  collabForm?.addEventListener("submit", (e) => {
    e.preventDefault();

    const formData = new FormData(collabForm);
    const collabId = formData.get("collaboratorId");
    const role = formData.get("collaboratorRole");

    if (!collabId) {
      const err = document.getElementById("collab-form-error");
      err.textContent = "Veuillez sélectionner un collaborateur.";
      err.classList.add("visible");
      return;
    }

    const collabSelect = document.getElementById("collab-select");
    const selectedOpt = collabSelect?.querySelector(
      `option[value="${collabId}"]`,
    );

    collabForm.reset();
    closeModal(collabOverlay, collabModal);
    showNotification(`${name} ajouté(e) au projet !`, "success");
  });

  // ========================================
  // ARCHIVER LE PROJET
  // ========================================

  const btnArchive = document.querySelector(".project-actions .btn-delete");
  btnArchive?.addEventListener("click", () => {
    if (confirm("Êtes-vous sûr de vouloir archiver ce projet ?")) {
      applyBadgeColor(statusBadge, "archived");
      addTimelineEntry(`<strong>Projet archivé</strong> par Admin Principal`);
      showNotification("Projet archivé.", "warning");
    }
  });

  // ========================================
  // UTILITAIRES
  // ========================================

  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString("fr-FR", {
      day: "2-digit",
      month: "long",
      year: "numeric",
    });
  }

  // ========================================
  // NOTIFICATION
  // ========================================

  function showNotification(message, type = "success") {
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    // Styles inline pour la notification
    Object.assign(notification.style, {
      position: "fixed",
      top: "20px",
      right: "20px",
      padding: "15px 25px",
      borderRadius: "8px",
      color: "#fff",
      fontWeight: "bold",
      fontSize: "14px",
      zIndex: "9999",
      opacity: "0",
      transform: "translateX(50px)",
      transition: "all 0.3s ease",
      boxShadow: "0 4px 12px rgba(0,0,0,0.3)",
    });

    if (type === "success") {
      notification.style.backgroundColor = "rgba(46, 204, 113, 0.9)";
      notification.style.border = "1px solid #2ecc71";
    } else if (type === "warning") {
      notification.style.backgroundColor = "rgba(255, 193, 7, 0.9)";
      notification.style.border = "1px solid #ffc107";
      notification.style.color = "#1a1a1a";
    } else if (type === "info") {
      notification.style.backgroundColor = "rgba(52, 152, 219, 0.9)";
      notification.style.border = "1px solid #3498db";
    } else {
      notification.style.backgroundColor = "rgba(255, 71, 87, 0.9)";
      notification.style.border = "1px solid #ff4757";
    }

    document.body.appendChild(notification);

    setTimeout(() => {
      notification.style.opacity = "1";
      notification.style.transform = "translateX(0)";
    }, 10);

    setTimeout(() => {
      notification.style.opacity = "0";
      notification.style.transform = "translateX(50px)";
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }

  // ========================================
  // FERMETURE MODALS AVEC ESCAPE
  // ========================================

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (editOverlay?.classList.contains("active"))
        closeModal(editOverlay, editModal);
      if (ticketOverlay?.classList.contains("active"))
        closeModal(ticketOverlay, ticketModal);
      if (collabOverlay?.classList.contains("active"))
        closeModal(collabOverlay, collabModal);
    }
  });
});
