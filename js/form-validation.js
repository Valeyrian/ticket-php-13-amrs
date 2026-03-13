// ========================================
// UTILITAIRES DE VALIDATION DE FORMULAIRES
// ========================================

const FormValidator = {
  // ========================================
  // REGEX PATTERNS
  // ========================================
  patterns: {
    email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    phone: /^(\+33|0033|0)\s?[1-9](\s?\d{2}){4}$/,
    postalCode: /^\d{5}$/,
    password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/,
    name: /^[a-zA-ZÀ-ÿ\s'-]{2,}$/,
  },

  // ========================================
  // VALIDATEURS INDIVIDUELS
  // ========================================

  /**
   * Vérifie qu'un champ n'est pas vide
   */
  isNotEmpty(value) {
    return value !== null && value !== undefined && String(value).trim() !== "";
  },

  /**
   * Vérifie le format d'un email
   */
  isValidEmail(value) {
    if (!this.isNotEmpty(value)) return false;
    return this.patterns.email.test(String(value).trim());
  },

  /**
   * Vérifie le format d'un numéro de téléphone français
   */
  isValidPhone(value) {
    if (!this.isNotEmpty(value)) return true; // Optionnel par défaut
    const cleaned = String(value)
      .trim()
      .replace(/[\s.-]/g, "");
    // Accepter formats: 0612345678, +33612345678, 0033612345678
    return /^(\+33|0033|0)[1-9]\d{8}$/.test(cleaned);
  },

  /**
   * Vérifie le format d'un code postal français
   */
  isValidPostalCode(value) {
    if (!this.isNotEmpty(value)) return false;
    return this.patterns.postalCode.test(String(value).trim());
  },

  /**
   * Vérifie la force d'un mot de passe
   * - Au moins 8 caractères
   * - Au moins 1 majuscule
   * - Au moins 1 minuscule
   * - Au moins 1 chiffre
   */
  isValidPassword(value) {
    if (!this.isNotEmpty(value)) return false;
    return this.patterns.password.test(String(value));
  },

  /**
   * Vérifie que deux mots de passe correspondent
   */
  passwordsMatch(password, confirmPassword) {
    return password === confirmPassword;
  },

  /**
   * Vérifie la longueur minimale d'un texte
   */
  hasMinLength(value, minLength) {
    if (!this.isNotEmpty(value)) return false;
    return String(value).trim().length >= minLength;
  },

  /**
   * Vérifie qu'un nom/prénom est valide (lettres, accents, espaces, tirets)
   */
  isValidName(value) {
    if (!this.isNotEmpty(value)) return false;
    return this.patterns.name.test(String(value).trim());
  },

  /**
   * Vérifie que la date de fin est après la date de début
   */
  isEndDateAfterStartDate(startDate, endDate) {
    if (!startDate || !endDate) return false;
    return new Date(endDate) > new Date(startDate);
  },

  /**
   * Vérifie qu'une date n'est pas dans le futur
   */
  isDateNotInFuture(dateValue) {
    if (!dateValue) return false;
    const today = new Date();
    today.setHours(23, 59, 59, 999);
    return new Date(dateValue) <= today;
  },

  /**
   * Vérifie qu'un nombre est positif
   */
  isPositiveNumber(value) {
    const num = parseFloat(value);
    return !isNaN(num) && num > 0;
  },

  /**
   * Échappe le HTML pour éviter les injections XSS
   */
  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  },

  // ========================================
  // AFFICHAGE DES ERREURS
  // ========================================

  /**
   * Affiche un message d'erreur dans un élément
   */
  showError(errorElement, message) {
    if (!errorElement) return;
    errorElement.textContent = message;
    errorElement.classList.add("show");
    errorElement.classList.add("visible");
    errorElement.style.display = "block";
  },

  /**
   * Cache le message d'erreur
   */
  hideError(errorElement) {
    if (!errorElement) return;
    errorElement.textContent = "";
    errorElement.classList.remove("show");
    errorElement.classList.remove("visible");
    errorElement.style.display = "none";
  },

  /**
   * Marque un champ en erreur visuellement
   */
  markFieldError(field) {
    if (!field) return;
    field.style.borderColor = "#ff4757";
    field.style.boxShadow = "0 0 0 2px rgba(255, 71, 87, 0.2)";
  },

  /**
   * Retire le marquage d'erreur d'un champ
   */
  clearFieldError(field) {
    if (!field) return;
    field.style.borderColor = "";
    field.style.boxShadow = "";
  },

  /**
   * Retire toutes les erreurs visuelles d'un formulaire
   */
  clearAllFieldErrors(form) {
    if (!form) return;
    form
      .querySelectorAll("input, select, textarea")
      .forEach((field) => this.clearFieldError(field));
  },

  // ========================================
  // VALIDATION DE FORMULAIRE COMPLÈTE
  // ========================================

  /**
   * Valide un formulaire selon des règles définies
   * @param {Object} rules - { fieldName: { value, validators: [{test, message}] } }
   * @param {HTMLElement} errorElement - L'élément d'erreur à utiliser
   * @param {HTMLFormElement} form - Le formulaire (pour marquer les champs en erreur)
   * @returns {boolean} true si tout est valide
   */
  validate(rules, errorElement, form) {
    this.hideError(errorElement);
    if (form) this.clearAllFieldErrors(form);

    for (const [fieldName, rule] of Object.entries(rules)) {
      for (const validator of rule.validators) {
        if (!validator.test(rule.value)) {
          this.showError(errorElement, validator.message);
          // Marquer le champ en erreur
          if (form) {
            const field =
              form.querySelector(`[name="${fieldName}"]`) ||
              form.querySelector(`#${fieldName}`);
            this.markFieldError(field);
            field?.focus();
          }
          return false;
        }
      }
    }
    return true;
  },
};
