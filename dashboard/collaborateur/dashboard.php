<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../php/classes/client.php';
require_once __DIR__ . '/../../php/classes/contrat.php';
require_once __DIR__ . '/../../php/classes/projet.php';
require_once __DIR__ . '/../../php/classes/ticket.php';
require_once __DIR__ . '/../../php/classes/user.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isCollaborateur()) {
  header("Location: /login_or_registration/login.php");
  exit;
}

$user = $_SESSION['user'];

// Récupérer mes tickets
$mesTickets = Ticket::getByCollaborateur($pdo, $user->getId());
$ticketsOuverts = array_filter($mesTickets, fn($t) => $t->isOpen());
$nbTicketsOuverts = count($ticketsOuverts);

// Récupérer mes projets
$mesProjets = Projet::getByCollaborateur($pdo, $user->getId());
$projetsActifs = array_filter($mesProjets, fn($p) => $p->isActif());

// Activité récente
$activites = [];
foreach ($mesTickets as $t) {
  if ($t->getDateModification()) {
    $activites[] = [
      'text' => 'Ticket #' . $t->getId() . ' mis à jour',
      'date' => $t->getDateModification(),
    ];
  }
}
usort($activites, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
$activites = array_slice($activites, 0, 3);

// Stats de la semaine (simulées pour l'exemple)
$ticketsTraites = count(array_filter($mesTickets, fn($t) => $t->getStatut() === 'termine'));
$totalTickets = count($mesTickets);
$pourcentageTickets = $totalTickets > 0 ? round(($ticketsTraites / $totalTickets) * 100) : 0;
?>
<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./dashboard.css" />
  <title>Dashboard - Vector</title>
  <script src="../../js/theme.js"></script>
</head>

<body class="oswald-font1 role-collaborateur">
  <header class="dashboard-header">
    <div class="header-container">
      <div class="logo-section">
        <a href="/index.php"><img id="logoH" src="/assets/logo.png" alt="Logo du site" /></a>
        <a href="/index.php"><img src="/assets/name.png" alt="Logo du site" /></a>
      </div>

      <div class="header-separator"></div>

      <h1 class="dashboard-title">Mon dashboard</h1>

      <nav class="header-nav">
        <a href="/tickets/collaborateur/ticket-manager.php" class="nav-btn">
          Tickets
          <span class="badge"><?= $nbTicketsOuverts ?></span>
        </a>
        <a href="/projects/collaborateur/project-manager.php" class="nav-btn">Projets</a>
      </nav>

      <div class="profile-info">
        <img src="/assets/account.png" alt="Photo de profil" class="header-profile-pic" />
        <div class="profile-text">
          <span class="profile-name"><?= htmlspecialchars($user->getName() . ' ' . $user->getSurname()) ?></span>
          <span class="profile-role">Collaborateur</span>
        </div>
      </div>
    </div>
  </header>

  <main>
    <div class="dashboard-layout">
      <!-- Contenu Central -->
      <div class="dashboard-container">
        <div id="tickets" class="open-tickets-section">
          <h2>Tickets ouverts</h2>
          <table class="tickets-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Projet</th>
                <th>Priorité</th>
                <th>Statut</th>
                <th>Temps restant</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ticketsOuverts as $t):
                $tProjet = $t->getProjetId() ? Projet::getById($pdo, $t->getProjetId()) : null;
                $priorityMap = ['haute' => 'high', 'moyenne' => 'medium', 'basse' => 'low'];
                $pClass = $priorityMap[$t->getPriorite()] ?? 'medium';
                $pLabel = ucfirst($t->getPriorite());
                $statutMap = [
                  'nouveau' => ['status-nouveau', 'Nouveau'],
                  'en_cours' => ['status-progress', 'En cours'],
                  'en_attente_client' => ['status-pending', 'En attente'],
                  'termine' => ['status-completed', 'Terminé'],
                  'a_valider' => ['status-review', 'En révision'],
                ];
                $sInfo = $statutMap[$t->getStatut()] ?? ['status-pending', $t->getStatut()];
                $tempsRestant = max(0, $t->getTempsEstime() - $t->getTempsPasse());
                $dateCreation = new DateTime($t->getDateCreation());
                ?>
                <tr>
                  <td><span class="ticket-id-badge">#<?= $t->getId() ?></span></td>
                  <td class="ticket-title-cell">
                    <a href="/tickets/collaborateur/generic-ticket.php?id=<?= $t->getId() ?>"
                      style="color:inherit;text-decoration:none">
                      <?= htmlspecialchars($t->getTitre()) ?>
                    </a>
                  </td>
                  <td><?= $tProjet ? htmlspecialchars($tProjet->getNom()) : '-' ?></td>
                  <td><span class="priority-badge <?= $pClass ?>"><?= $pLabel ?></span></td>
                  <td>
                    <span class="status-badge <?= $sInfo[0] ?>"><?= $sInfo[1] ?></span>
                  </td>
                  <td><?= $tempsRestant ?>h</td>
                  <td><?= $dateCreation->format('d M') ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($ticketsOuverts)): ?>
                <tr>
                  <td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px">Aucun ticket
                    ouvert.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Section Projets -->
        <div class="projects-section">
          <h2>Mes Projets</h2>
          <div class="projects-grid">
            <?php foreach ($projetsActifs as $p):
              $pTickets = Ticket::getByProjetId($pdo, $p->getId());
              $pTicketsOuverts = count(array_filter($pTickets, fn($t) => $t->isOpen()));
              $pCollabs = $p->getCollaborateurs($pdo);
              $pDateFin = $p->getDateFinPrevue() ? new DateTime($p->getDateFinPrevue()) : null;
              $pDeadline = $pDateFin ? $pDateFin->format('d M') : '-';
              $pStatut = 'active';
              if ($pDateFin) {
                $now = new DateTime();
                $diff = $now->diff($pDateFin);
                if ($diff->invert && $diff->days > 0) {
                  $pStatut = 'urgent';
                } elseif ($diff->days <= 7) {
                  $pStatut = 'pending';
                }
              }
              ?>
              <a href="/projects/collaborateur/generic-project.php?id=<?= $p->getId() ?>" class="project-card">
                <div class="project-header">
                  <div class="project-icon-placeholder">
                    <img src="/assets/project.png" alt="<?= htmlspecialchars($p->getNom()) ?>" />
                  </div>
                  <div class="project-status <?= $pStatut ?>">
                    <?= $pStatut === 'active' ? 'Actif' : ($pStatut === 'urgent' ? 'Urgent' : 'En attente') ?>
                  </div>
                </div>
                <h3 class="project-name"><?= htmlspecialchars($p->getNom()) ?></h3>
                <p class="project-description">
                  <?= htmlspecialchars(substr($p->getDescription() ?? '', 0, 100)) ?>...
                </p>

                <div class="project-stats">
                  <div class="stat-item">
                    <span class="stat-label">Tickets ouverts</span>
                    <span class="stat-value"><?= $pTicketsOuverts ?></span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-label">Deadline</span>
                    <span class="stat-value"><?= $pDeadline ?></span>
                  </div>
                </div>

                <div class="project-team">
                  <span class="team-label">Équipe:</span>
                  <div class="team-avatars">
                    <?php foreach ($pCollabs as $idx => $collab):
                      if ($idx >= 4)
                        break;
                      $initials = strtoupper(mb_substr($collab['name'], 0, 1) . mb_substr($collab['surname'], 0, 1));
                      ?>
                      <div class="avatar" title="<?= htmlspecialchars($collab['name'] . ' ' . $collab['surname']) ?>">
                        <?= $initials ?></div>
                    <?php endforeach; ?>
                    <?php if (count($pCollabs) > 4): ?>
                      <div class="avatar-more">+<?= count($pCollabs) - 4 ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
            <?php if (empty($projetsActifs)): ?>
              <p style="color:var(--text-muted)">Aucun projet actif.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Sidebar Droite -->
      <aside class="right-sidebar">
        <!-- Activité récente -->
        <div class="widget timeline-widget">
          <h4>Activité récentes</h4>
          <ul class="timeline-list">
            <?php foreach ($activites as $act):
              $actDate = new DateTime($act['date']);
              $now = new DateTime();
              $diff = $now->diff($actDate);
              if ($diff->d > 0) {
                $timeAgo = $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
              } elseif ($diff->h > 0) {
                $timeAgo = 'Il y a ' . $diff->h . 'h';
              } else {
                $timeAgo = 'Il y a ' . $diff->i . 'min';
              }
              ?>
              <li>
                <span class="timeline-dot"></span>
                <div class="timeline-content">
                  <p class="timeline-text"><?= $act['text'] ?></p>
                  <span class="timeline-time"><?= $timeAgo ?></span>
                </div>
              </li>
            <?php endforeach; ?>
            <?php if (empty($activites)): ?>
              <li>
                <span class="timeline-dot"></span>
                <div class="timeline-content">
                  <p class="timeline-text">Aucune activité récente</p>
                  <span class="timeline-time">-</span>
                </div>
              </li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Widget statistiques rapides -->
        <div class="widget stats-widget">
          <h4>Objectifs de la semaine</h4>
          <div class="progress-item">
            <div class="progress-info">
              <span>Tickets traités</span>
              <span class="progress-percentage"><?= $pourcentageTickets ?>%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: <?= $pourcentageTickets ?>%"></div>
            </div>
          </div>
          <div class="progress-item">
            <div class="progress-info">
              <span>Projets avancés</span>
              <span class="progress-percentage">65%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: 65%"></div>
            </div>
          </div>
          <div class="progress-item">
            <div class="progress-info">
              <span>Satisfaction client</span>
              <span class="progress-percentage">92%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: 92%"></div>
            </div>
          </div>
        </div>

        <!-- Bloc notes -->
        <div class="widget notes-widget">
          <h4>Notes rapides</h4>
          <textarea id="note-pad" placeholder="Ajoutez vos notes ici..." rows="6"></textarea>
          <button class="save-note-btn" id="save-btn">Enregistrer</button>
        </div>
      </aside>

      <div></div>
    </div>
  </main>

  <footer>
    <p>&copy; 2026 Vector - Tous droits réservés</p>
  </footer>

  <script src="script.js"></script>
</body>

</html>