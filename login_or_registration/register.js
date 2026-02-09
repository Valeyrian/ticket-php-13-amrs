 // Liste des entreprises (simulation - à remplacer par un appel API)
      const companies = [
        { id: 1, name: "Acme Corporation" },
        { id: 2, name: "TechnoSoft Solutions" },
        { id: 3, name: "Global Industries" },
        { id: 4, name: "Innovation Labs" },
        { id: 5, name: "Digital Dynamics" },
        { id: 6, name: "Smart Systems" },
        { id: 7, name: "Future Tech" },
        { id: 8, name: "Alpha Industries" },
        { id: 9, name: "Beta Corporation" },
        { id: 10, name: "Gamma Solutions" },
      ];

      const companyInput = document.getElementById("company");
      const companyDropdown = document.getElementById("companyDropdown");
      let selectedCompanyId = null;

      // Réinitialiser l'ID sélectionné quand l'utilisateur modifie le champ
      companyInput.addEventListener("input", function () {
        selectedCompanyId = null;
        const searchTerm = this.value.toLowerCase();

        if (searchTerm.length < 2) {
          companyDropdown.style.display = "none";
          return;
        }

        const filteredCompanies = companies.filter((company) =>
          company.name.toLowerCase().includes(searchTerm),
        );

        if (filteredCompanies.length > 0) {
          companyDropdown.innerHTML = filteredCompanies
            .map(
              (company) =>
                `<div class="company-item" data-id="${company.id}">${company.name}</div>`,
            )
            .join("");
          companyDropdown.style.display = "block";
        } else {
          companyDropdown.innerHTML =
            '<div class="company-item no-result">Aucune entreprise trouvée — elle sera ajoutée automatiquement</div>';
          companyDropdown.style.display = "block";
        }
      });

      // Sélection d'une entreprise
      companyDropdown.addEventListener("click", function (e) {
        if (
          e.target.classList.contains("company-item") &&
          !e.target.classList.contains("no-result")
        ) {
          selectedCompanyId = e.target.getAttribute("data-id");
          companyInput.value = e.target.textContent;
          companyDropdown.style.display = "none";
        }
      });

      // Fermer le dropdown si on clique ailleurs
      document.addEventListener("click", function (e) {
        if (!e.target.closest(".company-search")) {
          companyDropdown.style.display = "none";
        }
      });

      // Soumission du formulaire
      document
        .getElementById("registerForm")
        .addEventListener("submit", function (e) {
          e.preventDefault();

          const firstName = document.getElementById("firstName").value;
          const lastName = document.getElementById("lastName").value;
          const email = document.getElementById("email").value;
          const phone = document.getElementById("phone").value;
          const password = document.getElementById("password").value;
          const confirmPassword =
            document.getElementById("confirmPassword").value;
          const terms = document.querySelector('input[name="terms"]').checked;

          // Créer ou récupérer l'élément d'erreur
          let formError = document.getElementById("register-form-error");
          if (!formError) {
            formError = document.createElement("div");
            formError.id = "register-form-error";
            formError.style.cssText =
              "color: #ff4757; background: rgba(255,71,87,0.1); border: 1px solid rgba(255,71,87,0.3); padding: 10px; border-radius: 6px; margin-bottom: 15px; display: none; font-size: 14px;";
            this.insertBefore(formError, this.firstChild);
          }

          // Validation complète
          const isValid = FormValidator.validate(
            {
              firstName: {
                value: firstName,
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
              lastName: {
                value: lastName,
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
              phone: {
                value: phone,
                validators: [
                  {
                    test: (v) => FormValidator.isValidPhone(v),
                    message:
                      "Le numéro de téléphone n'est pas valide (ex: 06 12 34 56 78 ou +33 6 12 34 56 78).",
                  },
                ],
              },
              company: {
                value: companyInput.value,
                validators: [
                  {
                    test: (v) => FormValidator.isNotEmpty(v),
                    message: "Le nom de l'entreprise est requis.",
                  },
                  {
                    test: (v) => FormValidator.hasMinLength(v, 2),
                    message:
                      "Le nom de l'entreprise doit contenir au moins 2 caractères.",
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
                    test: (v) => FormValidator.isValidPassword(v),
                    message:
                      "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.",
                  },
                ],
              },
              confirmPassword: {
                value: confirmPassword,
                validators: [
                  {
                    test: (v) => FormValidator.isNotEmpty(v),
                    message: "La confirmation du mot de passe est requise.",
                  },
                  {
                    test: (v) => FormValidator.passwordsMatch(password, v),
                    message: "Les mots de passe ne correspondent pas.",
                  },
                ],
              },
            },
            formError,
            this,
          );

          if (!isValid) return;

          // Vérifier les conditions d'utilisation
          if (!terms) {
            FormValidator.showError(
              formError,
              "Vous devez accepter les conditions d'utilisation.",
            );
            return;
          }

          // Récupération des données du formulaire
          const formData = {
            firstName: firstName,
            lastName: lastName,
            email: email,
            phone: phone,
            companyId: selectedCompanyId,
            companyName: companyInput.value.trim(),
            isNewCompany: selectedCompanyId === null,
            password: password,
          };

          console.log("Inscription:", formData);

          // Simulation de l'inscription réussie
          alert(
            "Compte créé avec succès ! Vous allez être redirigé vers la page de connexion.",
          );
          window.location.href = "./login.html";
        });