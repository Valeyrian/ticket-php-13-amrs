<?php

require_once '../db.php';
require_once '../php/classes/user.php';

session_start();

// Si déjà connecté, rediriger vers le dashboard approprié
if (isset($_SESSION['user'])) {
  $u = $_SESSION['user'];
  if ($u->isAdmin()) {
    header("Location: /dashboard/admin/dashboard.php");
    exit;
  } elseif ($u->isCollaborateur()) {
    header("Location: /dashboard/collaborateur/dashboard.php");
    exit;
  } elseif ($u->isClient()) {
    header("Location: /dashboard/client/dashboard.php");
    exit;
  }
}

?>

<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./style.css" />
  <title>Connexion - Vector</title>
  <script src="../js/theme.js"></script>
</head>

<body class="oswald-font1">
  <div class="auth-container">
    <div class="auth-card">
      <div class="logo-section">
        <img src="/assets/logo.png" alt="Logo Vector" class="logo" />
        <img src="/assets/name.png" alt="Vector" class="logo-text" />
      </div>

      <h1 class="auth-title">Connexion</h1>
      <p class="auth-subtitle">Connectez-vous à votre espace</p>

      <form class="auth-form" id="loginForm" method="POST" action="login-action.php">
        <div class="form-group">
          <label for="email">Adresse email</label>
          <input type="email" id="email" name="email" placeholder="votre@email.com" required />
        </div>

        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required />
        </div>

        <div class="form-options">
          <label class="checkbox-container">
            <input type="checkbox" name="remember" />
            <span class="checkbox-label">Se souvenir de moi</span>
          </label>
          <a href="#" class="forgot-password">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="btn-primary">Se connecter</button>




        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
          <p class="error-message">Email ou mot de passe incorrect.</p>
        <?php endif; ?>
      </form>

      <div class="auth-footer">
        <p>
          Vous n'avez pas de compte ?
          <a href="./register.php" class="auth-link">Créer un compte client</a>
        </p>
      </div>
    </div>
  </div>

  <script src="../js/form-validation.js"></script>
  <script src="./login.js"></script>
</body>

</html>