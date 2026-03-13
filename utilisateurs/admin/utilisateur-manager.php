<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../php/classes/user.php';
require_once __DIR__ . '/../../php/classes/projet.php';
require_once __DIR__ . '/../../php/classes/ticket.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
  header("Location: /login_or_registration/login.php");
  exit;
}

$user = $_SESSION['user'];
$allUsers = User::getAll($pdo);
$allProjets = Projet::getAll($pdo);

// Préparer les données par utilisateur
$usersData = [];
foreach ($allUsers as $u) {
  $uid = $u->getId();
  $projets = Projet::getByCollaborateur($pdo, $uid);
  $tickets = Ticket::getByCollaborateur($pdo, $uid);
  $nbProjets = count($projets);
  $nbTickets = count($tickets);

  // Rôle affiché
  $roleMap = [
    'admin' => ['admin', 'Administrateur'],
    'collaborateur' => ['collab', 'Collaborateur'],
    'client' => ['client', 'Client'],
  ];
  $roleInfo = $roleMap[$u->getRole()] ?? ['client', $u->getRole()];

  // Statut affiché
  $stateMap = [
    'active' => ['active', 'Actif'],
    'inactive' => ['inactive', 'Inactif'],
    'suspended' => ['suspended', 'Suspendu'],
  ];
  $stateInfo = $stateMap[$u->getState()] ?? ['active', $u->getState() ?: 'Actif'];

  $usersData[] = [
    'user' => $u,
    'nbProjets' => $nbProjets,
    'nbTickets' => $nbTickets,
    'roleClass' => $roleInfo[0],
    'roleLabel' => $roleInfo[1],
    'stateClass' => $stateInfo[0],
    'stateLabel' => $stateInfo[1],
  ];
}

// Stats
$totalUsers = count($allUsers);
$totalAdmins = count(array_filter($allUsers, fn($u) => $u->getRole() === 'admin'));
$totalCollabs = count(array_filter($allUsers, fn($u) => $u->getRole() === 'collaborateur'));
$totalClients = count(array_filter($allUsers, fn($u) => $u->getRole() === 'client'));

// Entreprises pour la modal
$companies = $pdo->query("SELECT DISTINCT company FROM users WHERE company IS NOT NULL AND company != '' ORDER BY company ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./utilisateur-manager.css" />
  <title>Gestion des Utilisateurs - Vector</title>
  <script src="../../js/theme.js"></script>
</head>

<body class="oswald-font1 role-admin">
  <header class="dashboard-header">
    <div class="header-container">
      <div class="logo-section">
        <a href="/index.php"><img id="logoH" src="/assets/logo.png" alt="Logo du site" /></a>
        <a href="/index.php"><img src="/assets/name.png" alt="Logo du site" /></a>
      </div>

      <div class="header-separator"></div>

      <h1 class="dashboard-title">Gestion des Utilisateurs</h1>

      <nav class="header-nav">
        <a href="/dashboard/admin/dashboard.php" class="nav-btn">Dashboard</a>
        <a href="/projects/admin/project-manager.php" class="nav-btn">Projets</a>
        <a href="/tickets/admin/ticket-manager.php" class="nav-btn">Tickets</a>
        <a href="/contrats/admin/contrat-manager.php" class="nav-btn">Contrats</a>
        <a href="/clients/admin/client-manager.php" class="nav-btn">Clients</a>
      </nav>

      <div class="profile-info">
        <img src="/assets/account.png" alt="Photo de profil" class="header-profile-pic" />
        <div class="profile-text">
          <span class="profile-name"><?= htmlspecialchars($user->getName() . ' ' . $user->getSurname()) ?></span>
          <span class="profile-role">Administrateur</span>
        </div>
      </div>
    </div>
  </header>

  <main>
    <div class="manager-container">
      <!-- Stats rapides -->
      <div class="stats-bar">
        <div class="stat-item">
          <span class="stat-number"><?= $totalUsers ?></span>
          <span class="stat-label">utilisateurs</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= $totalAdmins ?></span>
          <span class="stat-label">admins</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= $totalCollabs ?></span>
          <span class="stat-label">collaborateurs</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= $totalClients ?></span>
          <span class="stat-label">clients</span>
        </div>
      </div>

      <!-- Filters and Actions -->
      <div class="controls-section">
        <div class="filters">
          <input type="text" placeholder="Rechercher un utilisateur..." class="search-input" id="search-input" />
          <select class="filter-select" id="filter-role">
            <option value="">Tous les rôles</option>
            <option value="admin">Administrateur</option>
            <option value="collaborateur">Collaborateur</option>
            <option value="client">Client</option>
          </select>
          <select class="filter-select" id="filter-state">
            <option value="">Tous les statuts</option>
            <option value="active">Actif</option>
            <option value="inactive">Inactif</option>
          </select>
        </div>
        <button class="btn-add">Nouvel utilisateur</button>
      </div>

      <!-- Users Table -->
      <div class="table-container">
        <table class="users-table">
          <thead>
            <tr>
              <th>Utilisateur</th>
              <th>Rôle</th>
              <th>Projets</th>
              <th>Tickets</th>
              <th>Entreprise</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usersData as $ud):
              $u = $ud['user'];
              $initials = $u->getInitials();
              $fullName = htmlspecialchars($u->getName() . ' ' . $u->getSurname());
              ?>
              <tr data-role="<?= htmlspecialchars($u->getRole()) ?>" data-state="<?= htmlspecialchars($u->getState()) ?>"
                data-name="<?= strtolower($fullName) ?>">
                <td>
                  <div class="user-cell">
                    <div class="user-avatar"><?= $initials ?></div>
                    <div class="user-info">
                      <div class="user-name">
                        <a href="/utilisateurs/admin/generic-utilisateur.php?id=<?= $u->getId() ?>"><?= $fullName ?></a>
                      </div>
                      <div class="user-email"><?= htmlspecialchars($u->getEmail()) ?></div>
                    </div>
                  </div>
                </td>
                <td><span class="role-badge <?= $ud['roleClass'] ?>"><?= $ud['roleLabel'] ?></span></td>
                <td><span class="count-badge"><?= $ud['nbProjets'] ?> projet<?= $ud['nbProjets'] > 1 ? 's' : '' ?></span>
                </td>
                <td><span class="count-badge"><?= $ud['nbTickets'] ?> ticket<?= $ud['nbTickets'] > 1 ? 's' : '' ?></span>
                </td>
                <td><?= htmlspecialchars($u->getCompany() ?: '-') ?></td>
                <td><span class="status-badge <?= $ud['stateClass'] ?>"><?= $ud['stateLabel'] ?></span></td>
                <td>
                  <div class="action-buttons">
                    <a href="/utilisateurs/admin/generic-utilisateur.php?id=<?= $u->getId() ?>" class="btn-edit"
                      title="Voir / Modifier">
                      <img src="/assets/editer.png" alt="modifier" />
                    </a>
                    <?php if ($u->getId() !== $user->getId()): ?>
                      <form method="POST" action="/php/actions/delete-user.php" style="display:inline"
                        onsubmit="return confirm('Supprimer <?= addslashes($fullName) ?> ?')">
                        <input type="hidden" name="id" value="<?= $u->getId() ?>" />
                        <button type="submit" class="btn-delete-small" title="Supprimer">
                          <img src="/assets/supprimer.png" alt="supprimer" />
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($usersData)): ?>
              <tr>
                <td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px">Aucun utilisateur.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Modal Nouvel Utilisateur -->
  <div class="modal-overlay" id="modal-user">
    <div class="modal">
      <div class="modal-header">
        <h2>Ajouter un Utilisateur</h2>
        <button class="modal-close">&times;</button>
      </div>
      <form class="modal-form" action="/php/actions/add-user.php" method="POST">
        <div class="form-section">
          <h3>Informations Personnelles</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="user-firstname">Prénom *</label>
              <input type="text" id="user-firstname" name="name" required placeholder="Ex: Jean" />
            </div>
            <div class="form-group">
              <label for="user-lastname">Nom *</label>
              <input type="text" id="user-lastname" name="surname" required placeholder="Ex: Dupont" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="user-email">Email *</label>
              <input type="email" id="user-email" name="email" required placeholder="Ex: jean.dupont@vector.fr" />
            </div>
            <div class="form-group">
              <label for="user-phone">Téléphone</label>
              <input type="tel" id="user-phone" name="phone" placeholder="Ex: 06 12 34 56 78" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="user-password">Mot de passe *</label>
              <input type="password" id="user-password" name="password" required minlength="8"
                placeholder="Minimum 8 caractères" />
            </div>
            <div class="form-group">
              <label for="user-password-confirm">Confirmer le mot de passe *</label>
              <input type="password" id="user-password-confirm" name="password_confirm" required minlength="8"
                placeholder="Répéter le mot de passe" />
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Rôle et Accès</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="user-role">Rôle *</label>
              <select id="user-role" name="role" required>
                <option value="">Sélectionner un rôle</option>
                <option value="admin">Administrateur</option>
                <option value="collaborateur">Collaborateur</option>
                <option value="client">Client</option>
              </select>
            </div>
            <div class="form-group">
              <label for="user-status">Statut</label>
              <select id="user-status" name="state">
                <option value="active">Actif</option>
                <option value="inactive">Inactif</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-section" id="section-company" style="display: none;">
          <h3>Entreprise</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="user-company">Entreprise *</label>
              <select id="user-company" name="company">
                <option value="">Sélectionner ou créer une entreprise</option>
                <?php foreach ($companies as $comp): ?>
                  <option value="<?= htmlspecialchars($comp) ?>"><?= htmlspecialchars($comp) ?></option>
                <?php endforeach; ?>
                <option value="__new__">+ Créer une nouvelle entreprise</option>
              </select>
            </div>
            <div class="form-group" id="new-company-group" style="display: none;">
              <label for="user-new-company">Nom de l'entreprise *</label>
              <input type="text" id="user-new-company" name="new_company" placeholder="Ex: Entreprise ABC" />
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Assignation Projets</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="user-projects">Projets assignés</label>
              <select id="user-projects" name="projects[]" multiple size="3">
                <?php foreach ($allProjets as $projet): ?>
                  <option value="<?= $projet->getId() ?>"><?= htmlspecialchars($projet->getNom()) ?></option>
                <?php endforeach; ?>
              </select>
              <small>Maintenez Ctrl pour sélectionner plusieurs projets</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div class="form-error">
            Veuillez remplir tous les champs obligatoires.
          </div>
          <button type="button" class="btn-cancel">Annuler</button>
          <button type="submit" class="btn-submit">
            Ajouter l'utilisateur
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="../../js/form-validation.js"></script>
  <script src="./utilisateur-manager.js"></script>

  <footer>
    <p>&copy; 2026 Vector - Tous droits réservés</p>
  </footer>
</body>

</html>