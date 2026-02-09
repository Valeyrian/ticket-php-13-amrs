document.addEventListener("DOMContentLoaded", () => {
  // ====== SELECTS: couleurs dynamiques ======

  const statusSelect = document.querySelector(".status-select");
  const prioritySelect = document.querySelector(".priority-select");
  const typeSelect = document.querySelector(".type-select");

  // Couleurs par statut
  const statusColors = {
    nouveau: {
      bg: "rgba(52, 152, 219, 0.2)",
      color: "#3498db",
      border: "rgba(52, 152, 219, 0.5)",
    },
    progress: {
      bg: "rgba(84, 197, 177, 0.2)",
      color: "#54C5B1",
      border: "rgba(84, 197, 177, 0.5)",
    },
    pending: {
      bg: "rgba(255, 193, 7, 0.2)",
      color: "#ffc107",
      border: "rgba(255, 193, 7, 0.5)",
    },
    termine: {
      bg: "rgba(46, 204, 113, 0.2)",
      color: "#2ecc71",
      border: "rgba(46, 204, 113, 0.5)",
    },
    "a-valider": {
      bg: "rgba(255, 184, 34, 0.2)",
      color: "#ffb822",
      border: "rgba(255, 184, 34, 0.5)",
    },
    valide: {
      bg: "rgba(46, 204, 113, 0.2)",
      color: "#2ecc71",
      border: "rgba(46, 204, 113, 0.5)",
    },
    refuse: {
      bg: "rgba(255, 71, 87, 0.2)",
      color: "#ff4757",
      border: "rgba(255, 71, 87, 0.5)",
    },
  };

  // Couleurs par priorité
  const priorityColors = {
    high: {
      bg: "rgba(255, 71, 87, 0.2)",
      color: "#ff4757",
      border: "rgba(255, 71, 87, 0.5)",
    },
    medium: {
      bg: "rgba(255, 193, 7, 0.2)",
      color: "#ffc107",
      border: "rgba(255, 193, 7, 0.5)",
    },
    low: {
      bg: "rgba(46, 204, 113, 0.2)",
      color: "#2ecc71",
      border: "rgba(46, 204, 113, 0.5)",
    },
  };

  // Couleurs par type
  const typeColors = {
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

  function applySelectColor(select, colorMap) {
    const style = colorMap[select.value];
    if (style) {
      select.style.backgroundColor = style.bg;
      select.style.color = style.color;
      select.style.border = `1px solid ${style.border}`;
    }
  }

  // Init + listeners
  if (statusSelect) {
    applySelectColor(statusSelect, statusColors);
    statusSelect.addEventListener("change", () =>
      applySelectColor(statusSelect, statusColors),
    );
  }

  if (prioritySelect) {
    applySelectColor(prioritySelect, priorityColors);
    prioritySelect.addEventListener("change", () =>
      applySelectColor(prioritySelect, priorityColors),
    );
  }

  if (typeSelect) {
    applySelectColor(typeSelect, typeColors);
    typeSelect.addEventListener("change", () => {
      applySelectColor(typeSelect, typeColors);
      toggleValidationSection();
      syncBtnToggleWithType();
    });
  }

  // ====== CACHER la section validation si type = inclus ======

  const validationSection = document.querySelector(".admin-validation-section");

  function toggleValidationSection() {
    if (!validationSection || !typeSelect) return;
    if (typeSelect.value === "inclus") {
      validationSection.style.display = "none";
    } else {
      validationSection.style.display = "block";
    }
  }

  // Init au chargement
  toggleValidationSection();

  // ====== Boutons toggle Inclus / Facturable ======

  const btnToggles = document.querySelectorAll(".btn-toggle");

  function syncBtnToggleWithType() {
    if (!typeSelect) return;
    btnToggles.forEach((btn) => {
      const label = btn.textContent.trim().toLowerCase();
      if (
        (typeSelect.value === "inclus" && label === "inclus") ||
        (typeSelect.value === "facturable" && label === "facturable")
      ) {
        btn.classList.add("active");
      } else {
        btn.classList.remove("active");
      }
    });
  }

  btnToggles.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Toggle le bouton actif
      btnToggles.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      // Synchroniser le select type
      const label = btn.textContent.trim().toLowerCase();
      if (typeSelect) {
        if (label === "inclus") {
          typeSelect.value = "inclus";
        } else if (label === "facturable") {
          typeSelect.value = "facturable";
        }
        applySelectColor(typeSelect, typeColors);
        toggleValidationSection();
      }
    });
  });

  // Init sync au chargement
  syncBtnToggleWithType();

  // ====== Ajout collaborateur depuis le select ======

  const addCollabSelect = document.querySelector(".add-collaborator-select");
  const collabList = document.querySelector(".collaborators-list");

  if (addCollabSelect && collabList) {
    addCollabSelect.addEventListener("change", () => {
      const selectedOption =
        addCollabSelect.options[addCollabSelect.selectedIndex];
      if (!selectedOption.value) return;

      const name = selectedOption.textContent;
      const initials = name
        .split(" ")
        .map((w) => w[0])
        .join("")
        .toUpperCase();

      const item = document.createElement("div");
      item.classList.add("collaborator-item");
      item.innerHTML = `
                <div class="avatar">${initials}</div>
                <span>${name}</span>
                <button class="btn-remove-collab" title="Retirer">✖</button>
            `;

      // Bouton supprimer
      item.querySelector(".btn-remove-collab").addEventListener("click", () => {
        item.remove();
        // Remettre l'option dans le select
        const opt = document.createElement("option");
        opt.value = selectedOption.value;
        opt.textContent = name;
        addCollabSelect.appendChild(opt);
      });

      collabList.appendChild(item);

      // Retirer l'option du select
      selectedOption.remove();
      addCollabSelect.value = "";
    });
  }

  // ====== Retirer collaborateur existant ======

  document.querySelectorAll(".btn-remove-collab").forEach((btn) => {
    btn.addEventListener("click", () => {
      const item = btn.closest(".collaborator-item");
      if (!item) return;
      const name = item.querySelector("span").textContent;
      const value = name.toLowerCase().replace(/\s/g, "-");
      item.remove();

      // Remettre dans le select
      if (addCollabSelect) {
        const opt = document.createElement("option");
        opt.value = value;
        opt.textContent = name;
        addCollabSelect.appendChild(opt);
      }
    });
  });

  // ====== Suppression entrées de temps ======

  document.querySelectorAll(".btn-delete-entry").forEach((btn) => {
    btn.addEventListener("click", () => {
      const entry = btn.closest(".time-entry");
      if (entry && confirm("Supprimer cette entrée de temps ?")) {
        entry.style.transition = "opacity 0.3s ease, transform 0.3s ease";
        entry.style.opacity = "0";
        entry.style.transform = "translateX(20px)";
        setTimeout(() => entry.remove(), 300);
      }
    });
  });

  // ====== Suppression commentaires ======

  document.querySelectorAll(".btn-delete-comment").forEach((btn) => {
    btn.addEventListener("click", () => {
      const comment = btn.closest(".comment");
      if (comment && confirm("Supprimer ce commentaire ?")) {
        comment.style.transition = "opacity 0.3s ease, transform 0.3s ease";
        comment.style.opacity = "0";
        comment.style.transform = "translateX(20px)";
        setTimeout(() => comment.remove(), 300);
      }
    });
  });

  // ====== Publier un commentaire ======

  const commentInput = document.querySelector(".comment-input");
  const publishBtn = document.querySelector(".add-comment .btn-primary");
  const commentsList = document.querySelector(".comments-list");

  if (publishBtn && commentInput && commentsList) {
    publishBtn.addEventListener("click", () => {
      const text = commentInput.value.trim();
      if (!text) {
        commentInput.style.borderColor = "#ff4757";
        commentInput.style.boxShadow = "0 0 0 2px rgba(255, 71, 87, 0.2)";
        commentInput.setAttribute(
          "placeholder",
          "Veuillez saisir un commentaire...",
        );
        return;
      }
      if (text.length < 3) {
        commentInput.style.borderColor = "#ff4757";
        commentInput.style.boxShadow = "0 0 0 2px rgba(255, 71, 87, 0.2)";
        return;
      }
      commentInput.style.borderColor = "";
      commentInput.style.boxShadow = "";

      // Échapper le HTML pour éviter les injections XSS
      const safeText = FormValidator.escapeHtml(text);

      const now = new Date();
      const dateStr =
        now.toLocaleDateString("fr-FR", {
          day: "numeric",
          month: "short",
          year: "numeric",
        }) +
        " à " +
        now.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" });

      const comment = document.createElement("div");
      comment.classList.add("comment");
      comment.innerHTML = `
                <div class="comment-header">
                    <div class="comment-author">
                        <div class="avatar small">AP</div>
                        <div>
                            <strong>Admin Principal</strong>
                            <span class="comment-date">${dateStr}</span>
                        </div>
                    </div>
                    <button class="btn-delete-comment" title="Supprimer"><img src="/assets/supprimer.png" alt="supprimer" class="inline-icon"></button>
                </div>
                <div class="comment-body">${safeText}</div>
            `;

      comment
        .querySelector(".btn-delete-comment")
        .addEventListener("click", () => {
          if (confirm("Supprimer ce commentaire ?")) {
            comment.style.transition = "opacity 0.3s ease, transform 0.3s ease";
            comment.style.opacity = "0";
            comment.style.transform = "translateX(20px)";
            setTimeout(() => comment.remove(), 300);
          }
        });

      commentsList.appendChild(comment);
      commentInput.value = "";
    });
  }

  // ====== Forcer validation / refus ======

  const btnForceValidate = document.querySelector(".btn-force-validate");
  const btnForceReject = document.querySelector(".btn-force-reject");
  const validationBadge = document.querySelector(".validation-badge");

  if (btnForceValidate && validationBadge) {
    btnForceValidate.addEventListener("click", () => {
      validationBadge.textContent = "Validé (forcé par admin)";
      validationBadge.className = "validation-badge";
      validationBadge.style.backgroundColor = "rgba(46, 204, 113, 0.2)";
      validationBadge.style.color = "#2ecc71";
      validationBadge.style.border = "1px solid #2ecc71";
    });
  }

  if (btnForceReject && validationBadge) {
    btnForceReject.addEventListener("click", () => {
      validationBadge.textContent = "Refusé (forcé par admin)";
      validationBadge.className = "validation-badge";
      validationBadge.style.backgroundColor = "rgba(255, 71, 87, 0.2)";
      validationBadge.style.color = "#ff4757";
      validationBadge.style.border = "1px solid #ff4757";
    });
  }
});
