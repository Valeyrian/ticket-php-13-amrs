document.addEventListener("DOMContentLoaded", () => {
  // ========================================
  // FILTRES TABLEAU
  // ========================================

  const searchInput = document.getElementById("search-input");
  const filterStatut = document.getElementById("filter-statut");
  const filterClient = document.getElementById("filter-client");
  const rows = document.querySelectorAll(
    ".contracts-table tbody tr[data-statut]",
  );

  function applyFilters() {
    const search = (searchInput?.value || "").toLowerCase().trim();
    const statut = filterStatut?.value || "";
    const client = filterClient?.value || "";

    rows.forEach((row) => {
      const name = row.getAttribute("data-name") || "";
      const rowStatut = row.getAttribute("data-statut") || "";
      const rowClient = row.getAttribute("data-client") || "";

      let show = true;
      if (search && !name.includes(search)) show = false;
      if (statut && rowStatut !== statut) show = false;
      if (client && rowClient !== client) show = false;

      row.style.display = show ? "" : "none";
    });
  }

  searchInput?.addEventListener("input", applyFilters);
  filterStatut?.addEventListener("change", applyFilters);
  filterClient?.addEventListener("change", applyFilters);

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
  // MODAL - Ouverture / Fermeture
  // ========================================

  const overlay = document.getElementById("modal-overlay");
  const addBtn = document.querySelector(".btn-add");

  if (addBtn && overlay) {
    addBtn.addEventListener("click", () => openModal(overlay));
  }

  document.querySelectorAll(".modal-close").forEach((btn) => {
    btn.addEventListener("click", () => {
      const ov = btn.closest(".modal-overlay");
      if (ov) closeModal(ov);
    });
  });

  document.querySelectorAll(".btn-cancel").forEach((btn) => {
    btn.addEventListener("click", () => {
      const ov = btn.closest(".modal-overlay");
      if (ov) closeModal(ov);
    });
  });

  if (overlay) {
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) closeModal(overlay);
    });
  }

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      const active = document.querySelector(".modal-overlay.active");
      if (active) closeModal(active);
    }
  });

  function openModal(ov) {
    ov.style.display = "flex";
    ov.classList.add("active");
    const form = ov.querySelector("form");
    if (form) form.reset();
    if (montantDisplay) montantDisplay.textContent = "0.00 €";
    if (montantHidden) montantHidden.value = "0";
    const err = ov.querySelector(".form-error");
    if (err) err.classList.remove("visible", "show");
    if (typeof FormValidator !== "undefined" && form) {
      FormValidator.clearAllFieldErrors(form);
    }
  }

  function closeModal(ov) {
    ov.classList.remove("active");
    setTimeout(() => {
      ov.style.display = "none";
    }, 300);
  }

  // ========================================
  // SOUMISSION DU FORMULAIRE
  // ========================================

  document.querySelectorAll(".modal-form").forEach((form) => {
    form.addEventListener("submit", (e) => {
      e.preventDefault();

      // Si FormValidator n'est pas chargé, soumettre directement
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

      // Soumettre le formulaire nativement (bypasse le listener)
      form.submit();
    });
  });
});
