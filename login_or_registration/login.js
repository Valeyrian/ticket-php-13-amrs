document.getElementById("loginForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  // Créer ou récupérer l'élément d'erreur
  let formError = document.getElementById("login-form-error");
  if (!formError) {
    formError = document.createElement("div");
    formError.id = "login-form-error";
    formError.style.cssText =
      "color: #ff4757; background: rgba(255,71,87,0.1); border: 1px solid rgba(255,71,87,0.3); padding: 10px; border-radius: 6px; margin-bottom: 15px; display: none; font-size: 14px;";
    this.insertBefore(formError, this.firstChild);
  }

  // Validation avec FormValidator
  const isValid = FormValidator.validate(
    {
      email: {
        value: email,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "L'adresse email est requise.",
          },
          {
            test: (v) => FormValidator.isValidEmail(v),
            message:
              "Veuillez saisir une adresse email valide (ex: nom@domaine.com).",
          },
        ],
      },
      password: {
        value: password,
        validators: [
          {
            test: (v) => FormValidator.isNotEmpty(v),
            message: "Le mot de passe est requis.",
          },
          {
            test: (v) => FormValidator.hasMinLength(v, 4),
            message: "Le mot de passe doit contenir au moins 4 caractères.",
          },
        ],
      },
    },
    formError,
    this,
  );

  if (!isValid) return;

  // Simulation de la connexion - À remplacer par une vraie authentification
  console.log("Tentative de connexion:", email);

  // Exemple de redirection selon le rôle (à adapter selon votre logique)
  if (email.includes("admin")) {
    window.location.href = "/dashboard/admin/dashboard.html";
  } else if (email.includes("collaborateur")) {
    window.location.href = "/dashboard/collaborateur/dashboard.html";
  } else {
    window.location.href = "/dashboard/client/dashboard.html";
  }
});
