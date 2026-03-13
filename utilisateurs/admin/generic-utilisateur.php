<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../php/classes/user.php';
require_once __DIR__ . '/../../php/classes/projet.php';
require_once __DIR__ . '/../../php/classes/ticket.php';
require_once __DIR__ . '/../../php/classes/client.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
  header("Location: /login_or_registration/login.php");
  exit;
}

$user = $_SESSION['user'];

// Récupérer l'utilisateur par ID
$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$viewedUser = $userId ? User::getById($pdo, $userId) : null;

if (!$viewedUser) {
  header("Location: /utilisateurs/admin/utilisateur-manager.php?error=utilisateur_introuvable");
  exit;
}

// Données de base
$fullName = htmlspecialchars($viewedUser->getName() . ' ' . $viewedUser->getSurname());
$initials = $viewedUser->getInitials();
$email = htmlspecialchars($viewedUser->getEmail());
$role = $viewedUser->getRole();
$state = $viewedUser->getState();
$company = htmlspecialchars($viewedUser->getCompany());
$creationDate = $viewedUser->getCreationDate();

// Projets et tickets selon le rôle
if ($role === 'client') {
  $allProjets = Projet::getAll($pdo);
  $projets = [];
  foreach ($allProjets as $p) {
    $c = $p->getClient($pdo);
    if ($c && strtolower($c->getNom()) === strtolower($viewedUser->getCompany())) {
      $projets[] = $p;
    }
  }
  $tickets = [];
  foreach ($projets as $p) {
    $tickets = array_merge($tickets, Ticket::getByProjetId($pdo, $p->getId()));
  }
} else {
  $projets = Projet::getByCollaborateur($pdo, $viewedUser->getId());
  $tickets = Ticket::getByCollaborateur($pdo, $viewedUser->getId());
}

$nbProjets = count($projets);
$nbTickets = count($tickets);
$nbTicketsOuverts = count(array_filter($tickets, fn($t) => $t->isOpen()));

// Badges
$roleBadgeMap = [
  'admin' => ['class' => 'admin', 'label' => 'Administrateur'],
  'collaborateur' => ['class' => 'collab', 'label' => 'Collaborateur'],
  'client' => ['class' => 'client', 'label' => 'Client'],
];
$roleBadge = $roleBadgeMap[$role] ?? ['class' => '', 'label' => $role];

$stateBadgeMap = [
  'active' => ['class' => 'active', 'label' => 'Actif'],
  'inactive' => ['class' => 'inactive', 'label' => 'Inactif'],
];
$stateBadge = $stateBadgeMap[$state] ?? ['class' => '', 'label' => $state];

$dateFormatted = $creationDate ? date('d/m/Y', strtotime($creationDate)) : 'N/A';
?>
<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./generic-utilisateur.css" />
  <title><?= $fullName ?> - Profil - Vector</title>
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

      <h1 class="dashboard-title">Profil Utilisateur</h1>

      <nav class="header-nav">
        <a href="/dashboard/admin/dashboard.php" class="nav-btn">Dashboard</a>
        <a href="/utilisateurs/admin/utilisateur-manager.php" class="nav-btn">Utilisateurs</a>
        <a href="/clients/admin/client-manager.html" class="nav-btn">Clients</a>
        <a href="/projects/admin/project-manager.php" class="nav-btn">Projets</a>
        <a href="/tickets/admin/ticket-manager.php" class="nav-btn">Tickets</a>
        <a href="/contrats/admin/contrat-manager.html" class="nav-btn">Contrats</a>
      </nav>

      <div class="profile-info">
        <img src="/assets/account.png" alt="Photo de profil" class="header-profile-pic" />
        <div class="profile-text">
          <span class="profile-name"><?= htmlspecialchars($user->getName()) ?></span>
          <span class="profile-role">Administrateur</span>
        </div>
      </div>
    </div>
  </header>

  <main>
    <div class="user-container">
      <!-- User Header -->
      <div class="user-header">
        <div class="header-left">
          <div class="user-avatar-large"><?= $initials ?></div>
          <div class="user-main-info">
            <h2 class="user-fullname"><?= $fullName ?></h2>
            <div class="user-subtitle">
              <span class="user-id">#U<?= str_pad($viewedUser->getId(), 3, '0', STR_PAD_LEFT) ?></span>
              <span class="role-badge <?= $roleBadge['class'] ?>"><?= $roleBadge['label'] ?></span>
              <span class="status-badge <?= $stateBadge['class'] ?>"><?= $stateBadge['label'] ?></span>
            </div>
            <div class="user-contact">
              <span><img src="/assets/enveloppe.png" alt="email" /> <?= $email ?></span>
              <?php if ($company): ?>
                <span>Entreprise : <?= $company ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="header-actions">
          <a href="/utilisateurs/admin/utilisateur-manager.php" class="btn-secondary">← Retour à la liste</a>
          <?php if ($viewedUser->getId() !== $user->getId()): ?>
            <form method="POST" action="/php/actions/update-user.php" style="display:inline">
              <input type="hidden" name="id" value="<?= $viewedUser->getId() ?>" />
              <input type="hidden" name="action" value="toggle_state" />
              <?php if ($state === 'active'): ?>
                <button type="submit" class="btn-danger"
                  onclick="return confirm('Suspendre <?= addslashes($fullName) ?> ?')">Suspendre</button>
              <?php else: ?>
                <button type="submit" class="btn-primary">Réactiver</button>
              <?php endif; ?>
            </form>
            <form method="POST" action="/php/actions/delete-user.php" style="display:inline"
              onsubmit="return confirm('Supprimer définitivement <?= addslashes($fullName) ?> ?')">
              <input type="hidden" name="id" value="<?= $viewedUser->getId() ?>" />
              <button type="submit" class="btn-danger">Supprimer</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <!-- Formulaire d'édition + Infos -->
      <div class="section-card">
        <h3 class="section-title">Modifier les informations</h3>
        <form method="POST" action="/php/actions/update-user.php" class="edit-form">
          <input type="hidden" name="id" value="<?= $viewedUser->getId() ?>" />
          <input type="hidden" name="action" value="update_info" />
          <div class="info-grid">
            <div class="info-section">
              <div class="form-group">
                <label for="edit-name">Prénom</label>
                <input type="text" id="edit-name" name="name" value="<?= htmlspecialchars($viewedUser->getName()) ?>"
                  class="form-control" required />
              </div>
              <div class="form-group">
                <label for="edit-surname">Nom</label>
                <input type="text" id="edit-surname" name="surname"
                  value="<?= htmlspecialchars($viewedUser->getSurname()) ?>" class="form-control" required />
              </div>
              <div class="form-group">
                <label for="edit-email">Email</label>
                <input type="email" id="edit-email" name="email" value="<?= $email ?>" class="form-control" required />
              </div>
              <div class="form-group">
                <label for="edit-role">Rôle</label>
                <select id="edit-role" name="role" class="form-control">
                  <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                  <option value="collaborateur" <?= $role === 'collaborateur' ? 'selected' : '' ?>>Collaborateur</option>
                  <option value="client" <?= $role === 'client' ? 'selected' : '' ?>>Client</option>
                </select>
              </div>
              <div class="form-group">
                <label for="edit-company">Entreprise</label>
                <input type="text" id="edit-company" name="company" value="<?= $company ?>" class="form-control" />
              </div>
              <div style="margin-top:15px">
                <button type="submit" class="btn-primary">Enregistrer les modifications</button>
              </div>
            </div>

            <div class="info-section">
              <h3 class="section-title">Informations</h3>
              <div class="info-list">
                <div class="info-item">
                  <span class="info-label">ID :</span>
                  <span class="info-value">#U<?= str_pad($viewedUser->getId(), 3, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="info-item">
                  <span class="info-label">Date de création :</span>
                  <span class="info-value"><?= $dateFormatted ?></span>
                </div>
                <div class="info-item">
                  <span class="info-label">Rôle :</span>
                  <span class="info-value"><?= $roleBadge['label'] ?></span>
                </div>
                <div class="info-item">
                  <span class="info-label">Statut :</span>
                  <span class="info-value"><?= $stateBadge['label'] ?></span>
                </div>
                <?php if ($company): ?>
                  <div class="info-item">
                    <span class="info-label">Entreprise :</span>
                    <span class="info-value"><?= $company ?></span>
                  </div>
                <?php endif; ?>
              </div>

              <h3 class="section-title" style="margin-top:20px">Statistiques</h3>
              <div class="stats-cards">
                <div class="mini-stat">
                  <div class="mini-stat-value"><?= $nbProjets ?></div>
                  <div class="mini-stat-label">Projets</div>
                </div>
                <div class="mini-stat">
                  <div class="mini-stat-value"><?= $nbTicketsOuverts ?></div>
                  <div class="mini-stat-label">Tickets ouverts</div>
                </div>
                <div class="mini-stat">
                  <div class="mini-stat-value"><?= $nbTickets ?></div>
                  <div class="mini-stat-label">Tickets total</div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Projets Assignés -->
      <div class="section-card">
        <div class="section-header">
          <h3 class="section-title">Projets assignés (<?= $nbProjets ?>)</h3>
        </div>
        <?php if (empty($projets)): ?>
          <p style="color:var(--text-gray);padding:15px 0">Aucun projet assigné.</p>
        <?php else: ?>
          <div class="projects-grid">
            <?php foreach ($projets as $p):
              $pTickets = Ticket::getByProjetId($pdo, $p->getId());
              $nbPTickets = count($pTickets);
              $nbPTermines = count(array_filter($pTickets, fn($t) => in_array($t->getStatut(), ['termine', 'valide'])));
              $progress = $nbPTickets > 0 ? round(($nbPTermines / $nbPTickets) * 100) : 0;
              $pClient = $p->getClient($pdo);
              $statutLabel = $p->isActif() ? 'En cours' : 'Archivé';
              $statutClass = $p->isActif() ? 'active' : 'inactive';
              ?>
              <div class="project-card">
                <div class="project-header">
                  <div class="project-icon">
                    <img src="/assets/project.png" alt="projet" />
                  </div>
                  <div class="project-main">
                    <h4 class="project-name">
                      <a
                        href="/projects/admin/generic-project.php?id=<?= $p->getId() ?>"><?= htmlspecialchars($p->getNom()) ?></a>
                    </h4>
                    <span class="project-id">#P<?= str_pad($p->getId(), 3, '0', STR_PAD_LEFT) ?></span>
                  </div>
                </div>
                <div class="project-info">
                  <?php if ($pClient): ?>
                    <div class="project-client">
                      <div class="company-logo-mini"><?= mb_strtoupper(mb_substr($pClient->getNom(), 0, 2)) ?></div>
                      <span><?= htmlspecialchars($pClient->getNom()) ?></span>
                    </div>
                  <?php endif; ?>
                  <div class="project-status">
                    <span class="status-badge <?= $statutClass ?>"><?= $statutLabel ?></span>
                  </div>
                </div>
                <div class="project-meta">
                  <span><img src="/assets/ticket.png" alt="tickets" /> <?= $nbPTickets ?> tickets</span>
                </div>
                <div class="project-progress">
                  <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                  </div>
                  <span class="progress-text"><?= $progress ?>%</span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Tickets -->
      <div class="section-card">
        <div class="section-header">
          <h3 class="section-title">Tickets (<?= $nbTickets ?>)</h3>
        </div>
        <?php if (empty($tickets)): ?>
          <p style="color:var(--text-gray);padding:15px 0">Aucun ticket.</p>
        <?php else: ?>
          <div class="tickets-table-container">
            <table class="tickets-table">
              <thead>
                <tr>
                  <th>Ticket</th>
                  <th>Projet</th>
                  <th>Priorité</th>
                  <th>Type</th>
                  <th>Statut</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tickets as $t):
                  $tProjet = $t->getProjetId() ? Projet::getById($pdo, $t->getProjetId()) : null;
                  $prioriteMap = ['haute' => 'high', 'moyenne' => 'medium', 'basse' => 'low'];
                  $statutMap = [
                    'nouveau' => ['class' => 'new', 'label' => 'Nouveau'],
                    'progress' => ['class' => 'active', 'label' => 'En cours'],
                    'pending' => ['class' => 'pending', 'label' => 'En attente'],
                    'termine' => ['class' => 'done', 'label' => 'Terminé'],
                    'a-valider' => ['class' => 'pending', 'label' => 'À valider'],
                    'valide' => ['class' => 'done', 'label' => 'Validé'],
                    'refuse' => ['class' => 'inactive', 'label' => 'Refusé'],
                  ];
                  $tStatut = $statutMap[$t->getStatut()] ?? ['class' => '', 'label' => $t->getStatut()];
                  $tPriorite = $prioriteMap[$t->getPriorite()] ?? 'medium';
                  ?>
                  <tr>
                    <td>
                      <div class="ticket-cell">
                        <span class="ticket-id">#T<?= str_pad($t->getId(), 3, '0', STR_PAD_LEFT) ?></span>
                        <a href="/tickets/admin/generic-ticket.php?id=<?= $t->getId() ?>" class="ticket-title">
                          <?= htmlspecialchars($t->getTitre()) ?>
                        </a>
                      </div>
                    </td>
                    <td>
                      <?php if ($tProjet): ?>
                        <a href="/projects/admin/generic-project.php?id=<?= $tProjet->getId() ?>" class="project-link">
                          #P<?= str_pad($tProjet->getId(), 3, '0', STR_PAD_LEFT) ?>
                          <?= htmlspecialchars($tProjet->getNom()) ?>
                        </a>
                      <?php else: ?>
                        <span style="color:var(--text-gray)">—</span>
                      <?php endif; ?>
                    </td>
                    <td><span class="priority-badge <?= $tPriorite ?>"><?= ucfirst($t->getPriorite()) ?></span></td>
                    <td><?= ucfirst($t->getType()) ?></td>
                    <td><span class="status-badge <?= $tStatut['class'] ?>"><?= $tStatut['label'] ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <footer>
    <p>&copy; 2026 Vector - Tous droits réservés</p>
  </footer>
</body>

</html>