<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Vector - Gestion de Ticketing et Projets</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet" />
  <script src="js/theme.js"></script>
</head>

<body class="oswald-font1 role-client">
  <!-- Header / Navigation -->
  <header class="main-header">
    <nav class="navbar">
      <div class="nav-container">
        <div class="logo-section">
          <a href="index.php" class="logo-link">
            <img id="logoH" src="./assets/logo.png" alt="Logo Vector" />
          </a>
          <a href="index.php" class="logo-link">
            <img src="./assets/name.png" alt="Vector" />
          </a>
        </div>

        <div class="nav-links">
          <a class="nav-link" href="index.php">Accueil</a>
          <a class="nav-link" href="#solutions">Nos solutions</a>
          <a class="nav-link" href="#features">Fonctionnalités</a>
          <a class="nav-link" href="#contact">Contact</a>
          <button id="toggle-theme">Mode clair</button>
          <a href="login_or_registration/login.php" class="login-button">
            <img id="account-logo" src="assets/account.png" alt="Se connecter" />
          </a>
        </div>
      </div>
    </nav>
  </header>

  <main>
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="hero-container">
        <div class="hero-content">
          <h1 class="hero-title">
            Gérez vos projets avec <span class="highlight">VECTOR</span>
          </h1>
          <p class="hero-description">
            La solution complète pour votre gestion de ticketing et
            collaboration client. Optimisez vos processus, suivez vos projets
            et améliorez la satisfaction de vos clients.
          </p>

          <div class="hero-buttons">
            <a href="login_or_registration/register.php" class="btn-primary">Commencer gratuitement</a>
            <a href="#features" class="btn-secondary">Découvrir</a>
          </div>
        </div>

        <div class="hero-image">
          <img src="./assets/dashboard_sample.png" alt="Dashboard Vector" />
        </div>
      </div>
    </section>

    <!-- Solutions Section -->
    <section id="solutions" class="solutions-section">
      <div class="container">
        <h2 class="section-title">Nos Solutions</h2>
        <p class="section-subtitle">
          Des outils performants adaptés à vos besoins
        </p>

        <div class="solutions-grid">
          <div class="solution-card">
            <div class="solution-icon">
              <img src="./assets/ticket.png" alt="Ticketing" />
            </div>
            <h3>Gestion de Tickets</h3>
            <p>
              Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suivez
              et résolvez les demandes de vos clients efficacement.
            </p>
            <a href="#" class="card-link">En savoir plus →</a>
          </div>

          <div class="solution-card">
            <div class="solution-icon">
              <img src="./assets/logo.png" alt="Projets" />
            </div>
            <h3>Gestion de Projets</h3>
            <p>
              Lorem ipsum dolor sit amet, consectetur adipiscing elit.
              Planifiez, organisez et suivez l'avancement de vos projets en
              temps réel.
            </p>
            <a href="#" class="card-link">En savoir plus →</a>
          </div>

          <div class="solution-card">
            <div class="solution-icon">
              <img src="./assets/client.png" alt="Collaboration" />
            </div>
            <h3>Collaboration Client</h3>
            <p>
              Lorem ipsum dolor sit amet, consectetur adipiscing elit.
              Renforcez la relation client avec des outils de communication
              intégrés.
            </p>
            <a href="#" class="card-link">En savoir plus →</a>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
      <div class="container">
        <h2 class="section-title">Fonctionnalités Clés</h2>
        <p class="section-subtitle">
          Tout ce dont vous avez besoin pour réussir
        </p>

        <div class="features-list">
          <div class="feature-item">
            <div class="feature-number">01</div>
            <div class="feature-content">
              <h3>Dashboard Personnalisé</h3>
              <p>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                Visualisez toutes vos données importantes en un coup d'œil
                avec des tableaux de bord intuitifs et personnalisables.
              </p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-number">02</div>
            <div class="feature-content">
              <h3>Suivi en Temps Réel</h3>
              <p>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                Restez informé de l'avancement de vos tickets et projets grâce
                aux notifications en temps réel.
              </p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-number">03</div>
            <div class="feature-content">
              <h3>Gestion Multi-Utilisateurs</h3>
              <p>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Gérez
                facilement les permissions et les rôles de vos collaborateurs
                et clients.
              </p>
            </div>
          </div>

          <div class="feature-item">
            <div class="feature-number">04</div>
            <div class="feature-content">
              <h3>Rapports Détaillés</h3>
              <p>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                Analysez vos performances avec des rapports complets et
                exportables.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <div class="container">
        <div class="cta-content">
          <h2>Prêt à transformer votre gestion de projets ?</h2>
          <p>
            Rejoignez des centaines d'entreprises qui font confiance à Vector
            pour gérer leurs projets et leurs clients.
          </p>
          <a href="login_or_registration/register.php" class="btn-cta">Créer un compte gratuitement</a>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
      <div class="container">
        <h2 class="section-title">Contactez-nous</h2>
        <p class="section-subtitle">
          Une question ? Notre équipe est là pour vous aider
        </p>

        <div class="contact-content">
          <div class="contact-info">
            <div class="info-item">
              <h3>
                <img src="/assets/enveloppe.png" alt="email" class="inline-icon" />
                Email
              </h3>
              <p>contact@vector.com</p>
            </div>
            <div class="info-item">
              <h3>
                <img src="/assets/telephone.png" alt="téléphone" class="inline-icon" />
                Téléphone
              </h3>
              <p>+33 1 23 45 67 89</p>
            </div>
            <div class="info-item">
              <h3>
                <img src="/assets/logo.png" alt="localisation" class="inline-icon" />
                Adresse
              </h3>
              <p>123 Avenue de la République<br />75011 Paris, France</p>
            </div>
          </div>

          <form class="contact-form">
            <div class="form-group">
              <input type="text" placeholder="Votre nom" required />
            </div>
            <div class="form-group">
              <input type="email" placeholder="Votre email" required />
            </div>
            <div class="form-group">
              <textarea placeholder="Votre message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn-primary">Envoyer</button>
          </form>
        </div>
      </div>
    </section>
  </main>

  <footer class="main-footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-section">
          <h4>Vector</h4>
          <p>
            La solution complète pour votre gestion de ticketing et
            collaboration client.
          </p>
        </div>
        <div class="footer-section">
          <h4>Liens rapides</h4>
          <ul>
            <li><a href="#solutions">Solutions</a></li>
            <li><a href="#features">Fonctionnalités</a></li>
            <li><a href="#contact">Contact</a></li>
          </ul>
        </div>
        <div class="footer-section">
          <h4>Légal</h4>
          <ul>
            <li><a href="#">Mentions légales</a></li>
            <li><a href="#">Politique de confidentialité</a></li>
            <li><a href="#">CGU</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2026 Vector - Tous droits réservés</p>
      </div>
    </div>
  </footer>
</body>

</html>