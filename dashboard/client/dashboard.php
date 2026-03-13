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

if (!isset($_SESSION['user']) || !$_SESSION['user']->isClient()) {
  header("Location: /login_or_registration/login.php");
  exit;
}

$user = $_SESSION['user'];

// Récupérer les clients associés à cet utilisateur
$mesClients = Client::getClientsByUtilisateur($pdo, $user->getId());

// Récupérer tous les projets et tickets liés à mes clients
$mesProjets = [];
$mesTickets = [];
$mesContrats = [];

foreach ($mesClients as $client) {
  $clientProjets = Projet::getByClient($pdo, $client->getId());
  $mesProjets = array_merge($mesProjets, $clientProjets);

  foreach ($clientProjets as $projet) {
    $projetTickets = Ticket::getByProjetId($pdo, $projet->getId());
    $mesTickets = array_merge($mesTickets, $projetTickets);

    $projetContrats = Contrat::getByProjet($pdo, $projet->getId());
    $mesContrats = array_merge($mesContrats, $projetContrats);
  }
}

// Stats globales
$nbProjetsActifs = count(array_filter($mesProjets, fn($p) => $p->isActif()));
$nbTicketsOuverts = count(array_filter($mesTickets, fn($t) => $t->isOpen()));
$nbTicketsAValider = count(array_filter($mesTickets, fn($t) => $t->getType() === 'facturable' && $t->getValidationStatus() === 'en_attente'));

// Heures totales
$heuresIncluses = array_sum(array_map(fn($c) => $c->getHeuresTotales(), $mesContrats));
$heuresConsommees = array_sum(array_map(fn($c) => $c->getHeuresConsommees(), $mesContrats));

// Tickets à valider (facturables en attente)
$ticketsAValider = array_filter($mesTickets, fn($t) => $t->getType() === 'facturable' && $t->getValidationStatus() === 'en_attente');
$ticketsAValider = array_slice($ticketsAValider, 0, 3); // Max 3

// Tickets récents (max 5)
$ticketsRecents = array_slice($mesTickets, 0, 5);

// Projets actifs (max 3)
$projetsActifs = array_filter($mesProjets, fn($p) => $p->isActif());
$projetsActifs = array_slice($projetsActifs, 0, 3);

// Contrats actifs (max 2)
$contratsActifs = array_filter($mesContrats, fn($c) => $c->getStatut() === 'actif');
$contratsActifs = array_slice($contratsActifs, 0, 2);

// Activité récente
$activites = [];
foreach ($mesTickets as $t) {
  if ($t->getDateModification()) {
    $activites[] = [
      'type' => 'ticket_update',
      'text' => 'Ticket #' . $t->getId() . ' mis à jour',
      'date' => $t->getDateModification(),
    ];
  }
}
usort($activites, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
$activites = array_slice($activites, 0, 3);

// Nom du client principal
$clientPrincipal = !empty($mesClients) ? $mesClients[0]->getNom() : $user->getName();
?>
<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./dashboard.css" />
  <title>Dashboard Client - Vector</title>
  <script src="../../js/theme.js"></script>
</head>

<body class="oswald-font1 role-client">
  <header class="dashboard-header">
    <div class="header-container">
      <div class="logo-section">
        <a href="/index.php"><img id="logoH" src="/assets/logo.png" alt="Logo du site" /></a>
        <a href="/index.php"><img src="/assets/name.png" alt="Logo du site" /></a>
      </div>

      <div class="header-separator"></div>

      <h1 class="dashboard-title">Mon espace client</h1>

      <nav class="header-nav">
        <a href="/tickets/client/ticket-manager.php" class="nav-btn">
          Tickets
          <span class="badge"><?= $nbTicketsOuverts ?></span>
        </a>
        <a href="/projects/client/project-manager.php" class="nav-btn">Mes Projets</a>
        <a href="/contrats/client/contrat-manager.php" class="nav-btn">Contrats</a>
      </nav>

      <div class="profile-info">
        <img src="/assets/account.png" alt="Photo de profil" class="header-profile-pic" />
        <div class="profile-text">
          <span class="profile-name"><?= htmlspecialchars($clientPrincipal) ?></span>
          <span class="profile-role">Client</span>
        </div>
      </div>
    </div>
  </header>

  <main>
    <div class="dashboard-layout">
      <!-- Contenu Central -->
      <div class="dashboard-container">
        <!-- Vue d'ensemble des statistiques -->
        <div class="stats-overview">
          <div class="stat-card">
            <div class="stat-icon projects-icon">
              <img src="/assets/project.png" alt="projets icon" />
            </div>
            <div class="stat-content">
              <h3>Projets</h3>
              <div class="stat-value"><?= $nbProjetsActifs ?></div>
              <div class="stat-label">Projets actifs</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon tickets-icon">
              <img src="/assets/ticket.png" alt="tickets icon" />
            </div>
            <div class="stat-content">
              <h3>Tickets</h3>
              <div class="stat-value"><?= $nbTicketsOuverts ?></div>
              <div class="stat-label">Tickets ouverts</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon hours-icon">
              <img src="/assets/heures-douverture.png" alt="heures icon" />
            </div>
            <div class="stat-content">
              <h3>Heures</h3>
              <div class="stat-value"><?= round($heuresConsommees) ?>h</div>
              <div class="stat-label">Consommées / <?= round($heuresIncluses) ?>h</div>
            </div>
          </div>
          <div class="stat-card validation">
            <div class="stat-icon validation-icon"></div>
            <div class="stat-content">
              <h3>Validations</h3>
              <div class="stat-value"><?= $nbTicketsAValider ?></div>
              <div class="stat-label">En attente</div>
            </div>
          </div>
        </div>

        <!-- Tickets en attente de validation -->
        <?php if (!empty($ticketsAValider)): ?>
          <div id="validation" class="validation-section">
            <div class="section-header">
              <h2>Tickets à valider</h2>
              <span class="info-text-header">Ces tickets facturables nécessitent votre validation</span>
            </div>
            <div class="validation-cards">
              <?php foreach ($ticketsAValider as $t):
                $tProjet = $t->getProjetId() ? Projet::getById($pdo, $t->getProjetId()) : null;
                $priorityMap = ['haute' => 'high', 'moyenne' => 'medium', 'basse' => 'low'];
                $pClass = $priorityMap[$t->getPriorite()] ?? 'medium';
                $pLabel = ucfirst($t->getPriorite());
                $depassement = max(0, $t->getTempsPasse() - $t->getTempsEstime());
                $tauxHoraire = 85;
                $coutSupp = $depassement * $tauxHoraire;
                ?>
                <div class="ticket-card validation-required">
                  <div class="card-header">
                    <span class="ticket-id">#<?= $t->getId() ?></span>
                    <span class="priority-badge <?= $pClass ?>"><?= $pLabel ?></span>
                  </div>
                  <h3 class="ticket-title"><?= htmlspecialchars($t->getTitre()) ?></h3>
                  <p class="ticket-desc"><?= htmlspecialchars(substr($t->getDescription() ?? '', 0, 100)) ?>...
                  </p>
                  <div class="ticket-info">
                    <div class="info-item">
                      <span class="label">Projet:</span>
                      <span class="value"><?= $tProjet ? htmlspecialchars($tProjet->getNom()) : '-' ?></span>
                    </div>
                    <div class="info-item">
                      <span class="label">Temps passé:</span>
                      <span class="value"><?= $t->getTempsPasse() ?>h</span>
                    </div>
                    <?php if ($depassement > 0): ?>
                      <div class="info-item">
                        <span class="label">Dépassement:</span>
                        <span class="value text-warning">+<?= $depassement ?>h</span>
                      </div>
                      <div class="info-item">
                        <span class="label">Coût supp:</span>
                        <span class="value text-warning"><?= round($coutSupp) ?>€</span>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="ticket-actions">
                    <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
                      <input type="hidden" name="ticket_id" value="<?= $t->getId() ?>" />
                      <input type="hidden" name="action" value="client_validate" />
                      <button type="submit" class="btn-validate">Valider</button>
                    </form>
                    <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
                      <input type="hidden" name="ticket_id" value="<?= $t->getId() ?>" />
                      <input type="hidden" name="action" value="client_reject" />
                      <button type="submit" class="btn-reject">Refuser</button>
                    </form>
                    <a href="/tickets/client/generic-ticket.php?id=<?= $t->getId() ?>" class="btn-details">Voir détails</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Mes tickets récents -->
        <div id="tickets" class="tickets-section">
          <h2>Mes tickets</h2>
          <table class="tickets-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Projet</th>
                <th>Statut</th>
                <th>Priorité</th>
                <th>Type</th>
                <th>Temps</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ticketsRecents as $t):
                $tProjet = $t->getProjetId() ? Projet::getById($pdo, $t->getProjetId()) : null;
                $statutMap = [
                  'nouveau' => ['status-nouveau', 'Nouveau'],
                  'en_cours' => ['status-progress', 'En cours'],
                  'en_attente_client' => ['status-pending', 'En attente'],
                  'termine' => ['status-completed', 'Terminé'],
                  'a_valider' => ['status-validate', 'À valider'],
                  'valide' => ['status-validated', 'Validé'],
                ];
                $sInfo = $statutMap[$t->getStatut()] ?? ['status-pending', $t->getStatut()];
                $priorityMap = ['haute' => ['high', 'Haute'], 'moyenne' => ['medium', 'Moyenne'], 'basse' => ['low', 'Basse']];
                $pInfo = $priorityMap[$t->getPriorite()] ?? ['medium', $t->getPriorite()];
                $typeClass = $t->getType() === 'facturable' ? 'billing-facturable' : 'billing-inclus';
                $typeLabel = $t->getType() === 'facturable' ? 'Facturable' : 'Inclus';
                $dateCreation = new DateTime($t->getDateCreation());
                $rowClass = $t->getValidationStatus() === 'en_attente' ? 'validation-row' : '';
                ?>
                <tr class="<?= $rowClass ?>">
                  <td>
                    <a href="/tickets/client/generic-ticket.php?id=<?= $t->getId() ?>"
                      class="ticket-link">#<?= $t->getId() ?></a>
                  </td>
                  <td class="ticket-title-cell"><?= htmlspecialchars($t->getTitre()) ?></td>
                  <td><?= $tProjet ? htmlspecialchars($tProjet->getNom()) : '-' ?></td>
                  <td>
                    <span class="status-badge <?= $sInfo[0] ?>"><?= $sInfo[1] ?></span>
                  </td>
                  <td><span class="priority-badge <?= $pInfo[0] ?>"><?= $pInfo[1] ?></span></td>
                  <td>
                    <span class="billing-badge <?= $typeClass ?>"><?= $typeLabel ?></span>
                  </td>
                  <td><?= $t->getTempsPasse() ?>h<?= $t->getTempsEstime() > 0 ? '/' . $t->getTempsEstime() . 'h' : '' ?>
                  </td>
                  <td><?= $dateCreation->format('d M') ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($ticketsRecents)): ?>
                <tr>
                  <td colspan="8" style="text-align:center;color:var(--text-muted);padding:20px">Aucun ticket.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Section Mes Projets -->
        <div id="projects" class="projects-section">
          <h2>Mes Projets</h2>
          <div class="projects-grid">
            <?php foreach ($projetsActifs as $p):
              $pTickets = Ticket::getByProjetId($pdo, $p->getId());
              $pTicketsOuverts = count(array_filter($pTickets, fn($t) => $t->isOpen()));
              $pTempsPasse = $p->getTotalTempsPasse($pdo);
              $pTempsEstime = $p->getTotalTempsEstime($pdo);
              $pProgression = $pTempsEstime > 0 ? round(($pTempsPasse / $pTempsEstime) * 100) : 0;
              $pCollabs = $p->getCollaborateurs($pdo);
              $pContrats = Contrat::getByProjet($pdo, $p->getId());
              $pContrat = !empty($pContrats) ? $pContrats[0] : null;
              $pHeures = $pContrat ? $pContrat->getHeuresConsommees() . 'h / ' . $pContrat->getHeuresTotales() . 'h' : '-';
              ?>
              <div class="project-card">
                <div class="project-header">
                  <div class="project-icon-placeholder">
                    <img src="/assets/project.png" alt="<?= htmlspecialchars($p->getNom()) ?>" />
                  </div>
                  <div class="project-status active">Actif</div>
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
                    <span class="stat-label">Progression</span>
                    <span class="stat-value"><?= $pProgression ?>%</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-label">Heures</span>
                    <span class="stat-value"><?= $pHeures ?></span>
                  </div>
                </div>

                <div class="project-progress-bar">
                  <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= min($pProgression, 100) ?>%"></div>
                  </div>
                  <span class="progress-label">Progression: <?= $pProgression ?>%</span>
                </div>

                <div class="project-team">
                  <span class="team-label">Équipe:</span>
                  <div class="team-avatars">
                    <?php foreach ($pCollabs as $idx => $collab):
                      if ($idx >= 3)
                        break;
                      $initials = strtoupper(mb_substr($collab['name'], 0, 1) . mb_substr($collab['surname'], 0, 1));
                      ?>
                      <div class="avatar" title="<?= htmlspecialchars($collab['name'] . ' ' . $collab['surname']) ?>">
                        <?= $initials ?></div>
                    <?php endforeach; ?>
                    <?php if (count($pCollabs) > 3): ?>
                      <div class="avatar-more">+<?= count($pCollabs) - 3 ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (empty($projetsActifs)): ?>
              <p style="color:var(--text-muted)">Aucun projet actif.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Section Contrats -->
        <div id="contracts" class="contracts-section">
          <h2>Mes Contrats</h2>
          <div class="contracts-list">
            <?php foreach ($contratsActifs as $c):
              $cProjet = Projet::getByContrat($pdo, $c->getId());
              $cProjetNom = !empty($cProjet) ? $cProjet[0]->getNom() : '-';
              $cPourcentage = $c->getHeuresTotales() > 0 ? round(($c->getHeuresConsommees() / $c->getHeuresTotales()) * 100) : 0;
              ?>
              <div class="contract-card">
                <div class="contract-header">
                  <h3><?= htmlspecialchars($c->getNom()) ?></h3>
                  <span class="contract-status active">Actif</span>
                </div>
                <div class="contract-details">
                  <div class="detail-row">
                    <span class="detail-label">Projet associé:</span>
                    <span class="detail-value"><?= htmlspecialchars($cProjetNom) ?></span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Période:</span>
                    <span class="detail-value"><?= date('d/m/Y', strtotime($c->getDateDebut())) ?> -
                      <?= date('d/m/Y', strtotime($c->getDateFin())) ?></span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Heures incluses:</span>
                    <span class="detail-value"><?= $c->getHeuresTotales() ?>h</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Heures consommées:</span>
                    <span class="detail-value text-warning"><?= $c->getHeuresConsommees() ?>h</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Heures restantes:</span>
                    <span class="detail-value text-success"><?= $c->getHeuresRestantes() ?>h</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">Taux horaire supp:</span>
                    <span class="detail-value"><?= $c->getTauxHoraire() ?>€/h</span>
                  </div>
                </div>
                <div class="contract-progress">
                  <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= min($cPourcentage, 100) ?>%"></div>
                  </div>
                  <span class="progress-label"><?= $cPourcentage ?>% consommé</span>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (empty($contratsActifs)): ?>
              <p style="color:var(--text-muted)">Aucun contrat actif.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Sidebar Droite -->
      <aside class="sidebar-right">
        <!-- Alertes importantes -->
        <div class="sidebar-card alerts-card">
          <h3>Alertes</h3>
          <?php if ($heuresIncluses > 0 && ($heuresConsommees / $heuresIncluses) > 0.8): ?>
            <div class="alert-item warning">
              <div class="alert-icon">!</div>
              <div class="alert-content">
                <div class="alert-title">Heures bientôt épuisées</div>
                <div class="alert-text">
                  Il reste <?= round($heuresIncluses - $heuresConsommees) ?>h sur vos contrats
                </div>
              </div>
            </div>
          <?php endif; ?>
          <?php if ($nbTicketsAValider > 0): ?>
            <div class="alert-item validation">
              <div class="alert-icon"></div>
              <div class="alert-content">
                <div class="alert-title">Validations en attente</div>
                <div class="alert-text">
                  <?= $nbTicketsAValider ?> ticket<?= $nbTicketsAValider > 1 ? 's' : '' ?>
                  facturable<?= $nbTicketsAValider > 1 ? 's' : '' ?> nécessite<?= $nbTicketsAValider > 1 ? 'nt' : '' ?>
                  votre validation
                </div>
              </div>
            </div>
          <?php endif; ?>
          <div class="alert-item info">
            <div class="alert-icon">i</div>
            <div class="alert-content">
              <div class="alert-title">Bienvenue</div>
              <div class="alert-text">
                Consultez vos projets et tickets en cours
              </div>
            </div>
          </div>
        </div>

        <!-- Activité récente -->
        <div class="sidebar-card">
          <h3>Activité Récente</h3>
          <div class="activity-list">
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
              <div class="activity-item">
                <div class="activity-icon"></div>
                <div class="activity-content">
                  <div class="activity-title"><?= $act['text'] ?></div>
                  <div class="activity-meta"><?= $timeAgo ?></div>
                </div>
              </div>
            <?php endforeach; ?>
            <?php if (empty($activites)): ?>
              <p style="color:var(--text-muted);font-size:0.9em">Aucune activité récente.</p>
            <?php endif; ?>
          </div>
        </div>
      </aside>
    </div>
  </main>

  <footer>
    <p>&copy; 2026 Vector - Tous droits réservés</p>
  </footer>
</body>

</html>