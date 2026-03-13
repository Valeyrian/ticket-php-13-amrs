<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="./style.css" />
    <title>Inscription - Vector</title>
    <script src="../js/theme.js"></script>
  </head>
  <body class="oswald-font1">
    <div class="auth-container">
      <div class="auth-card register-card">
        <div class="logo-section">
          <img src="/assets/logo.png" alt="Logo Vector" class="logo" />
          <img src="/assets/name.png" alt="Vector" class="logo-text" />
        </div>

        <h1 class="auth-title">Créer un compte client</h1>
        <p class="auth-subtitle">Rejoignez Vector pour gérer vos projets</p>

        <form class="auth-form" id="registerForm" method="POST" action="registration-action.php">
          <div class="form-row">
            <div class="form-group">
              <label for="firstName">Prénom</label>
              <input
                type="text"
                id="firstName"
                name="firstName"
                placeholder="Jean"
                required
              />
            </div>

            <div class="form-group">
              <label for="lastName">Nom</label>
              <input
                type="text"
                id="lastName"
                name="lastName"
                placeholder="Dupont"
                required
              />
            </div>
          </div>

          <div class="form-group">
            <label for="email">Adresse email professionnelle</label>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="jean.dupont@entreprise.com"
              required
            />
          </div>

          <div class="form-group">
            <label for="phone">Téléphone</label>
            <input
              type="tel"
              id="phone"
              name="phone"
              placeholder="+33 6 12 34 56 78"
            />
          </div>

          <div class="form-group">
            <label for="company">Entreprise</label>
            <div class="company-search">
              <input
                type="text"
                id="company"
                name="company"
                placeholder="Rechercher votre entreprise..."
                required
                autocomplete="off"
              />
              <div class="company-dropdown" id="companyDropdown"></div>
            </div>
            <small class="form-help"
              >Tapez le nom de votre entreprise pour la rechercher, ou
              saisissez-le si elle n'est pas dans la liste</small
            >
          </div>

          <div class="form-group">
            <label for="password">Mot de passe</label>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="••••••••"
              required
              minlength="8"
            />
            <small class="form-help">Minimum 8 caractères</small>
          </div>

          <div class="form-group">
            <label for="confirmPassword">Confirmer le mot de passe</label>
            <input
              type="password"
              id="confirmPassword"
              name="confirmPassword"
              placeholder="••••••••"
              required
            />
          </div>

          <div class="form-group">
            <label class="checkbox-container">
              <input type="checkbox" name="terms" required />
              <span class="checkbox-label"
                >J'accepte les
                <a href="#" class="terms-link">conditions d'utilisation</a> et
                la
                <a href="#" class="terms-link"
                  >politique de confidentialité</a
                ></span
              >
            </label>
          </div>

          <button type="submit"  class="btn-primary">Créer mon compte</button>
        </form>

        <div class="auth-footer">
          <p>
            Vous avez déjà un compte ?
            <a href="./login.php" class="auth-link">Se connecter</a>
          </p>
        </div>
      </div>
    </div>

    <script src="../js/form-validation.js"></script>
    <script src="./register.js"></script>
  </body>
</html>
