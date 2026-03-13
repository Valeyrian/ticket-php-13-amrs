<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../db.php';
require_once '../../php/classes/client.php';
require_once '../../php/classes/contrat.php';
require_once '../../php/classes/projet.php';
require_once '../../php/classes/ticket.php';
require_once '../../php/classes/user.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
  header("Location: /login_or_registration/login.php");
  exit;
}

$user = $_SESSION['user'];

// Données
$allProjets = Projet::getAll($pdo);
$allClients = Client::getAll($pdo);
$allContrats = Contrat::getAll($pdo);
$allUsers = User::getAll($pdo);
$collaborateurs = array_filter($allUsers, fn($u) => $u->getRole() === 'collaborateur');

// Pré-calculer les données par projet
$projetData = [];
$totalHeuresConsommees = 0;
$totalHeuresDisponibles = 0;
$nbProjetsActifs = 0;

foreach ($allProjets as $projet) {
  $client = $projet->getClient($pdo);
  $contrats = Contrat::getByProjet($pdo, $projet->getId());
  $contrat = !empty($contrats) ? $contrats[0] : null;
  $collabs = $projet->getCollaborateurs($pdo);
  $tickets = Ticket::getByProjetId($pdo, $projet->getId());

  $nbTickets = count($tickets);
  $nbOuverts = count(array_filter($tickets, fn($t) => $t->isOpen()));
  $nbEnCours = count(array_filter($tickets, fn($t) => $t->getStatut() === 'en_cours'));
  $nbTermines = count(array_filter($tickets, fn($t) => in_array($t->getStatut(), ['termine', 'valide', 'ferme'])));

  $tempsPasse = $projet->getTotalTempsPasse($pdo);
  $heuresContrat = $contrat ? $contrat->getHeuresTotales() : 0;
  $heuresConsommees = $contrat ? $contrat->getHeuresConsommees() : $tempsPasse;
  $heuresRestantes = $contrat ? $contrat->getHeuresRestantes() : 0;

  $pourcentage = $heuresContrat > 0 ? round(($heuresConsommees / $heuresContrat) * 100) : 0;

  // Classes CSS pour la row
  $rowClass = '';
  if ($contrat && $heuresConsommees > $heuresContrat) {
    $rowClass = 'hours-exceeded';
  } elseif ($contrat && $heuresContrat > 0 && ($heuresRestantes / $heuresContrat) < 0.15) {
    $rowClass = 'hours-warning';
  }

  // Statut select class
  $statusClassMap = [
    'actif' => 'status-active',
    'archive' => 'status-completed',
  ];
  $statusClass = $statusClassMap[$projet->getStatut()] ?? '';

  // Collab IDs pour filtrage JS
  $collabIds = array_map(fn($c) => $c['id'], $collabs);

  // Stats globales
  $totalHeuresConsommees += $heuresConsommees;
  if ($contrat)
    $totalHeuresDisponibles += $heuresContrat;
  if ($projet->isActif())
    $nbProjetsActifs++;

  $projetData[] = [
    'projet' => $projet,
    'client' => $client,
    'contrat' => $contrat,
    'collabs' => $collabs,
    'collabIds' => $collabIds,
    'nbTickets' => $nbTickets,
    'nbOuverts' => $nbOuverts,
    'nbEnCours' => $nbEnCours,
    'nbTermines' => $nbTermines,
    'tempsPasse' => $tempsPasse,
    'heuresContrat' => $heuresContrat,
    'heuresConsommees' => $heuresConsommees,
    'heuresRestantes' => $heuresRestantes,
    'pourcentage' => $pourcentage,
    'rowClass' => $rowClass,
    'statusClass' => $statusClass,
  ];
}

$nbClients = count($allClients);
?>
<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./project-manager.css" />
  <title>Gestion Projets Admin - Vector</title>
  <script src="../../js/theme.js"></script>

  <script>
    window.projectData = [
      <?php foreach ($projetData as $pd): ?> {
          id: <?= $pd['projet']->getId() ?>,
          clientId: <?= $pd['client'] ? $pd['client']->getId() : 0 ?>,
          statut: '<?= addslashes($pd['projet']->getStatut()) ?>',
          collabIds: [<?= implode(',', $pd['collabIds']) ?>],
          date: '<?= addslashes($pd['projet']->getDateCreation() ?? '') ?>',
          heuresRestantes: <?= $pd['heuresRestantes'] ?>,
          heuresContrat: <?= $pd['heuresContrat'] ?>,
          nbTickets: <?= $pd['nbTickets'] ?>,
        },
      <?php endforeach; ?>
    ];

    // Mapping client → contrats pour filtrage modal
    window.clientContrats = {
      <?php
      foreach ($allClients as $c) {
        $contratsClient = Contrat::getByClient($pdo, $c->getId());
        $items = array_map(fn($ct) => '{id:' . $ct->getId() . ',nom:"' . addslashes($ct->getNom()) . '"}', $contratsClient);
        echo $c->getId() . ': [' . implode(',', $items) . "],\n      ";
      }
      ?>
    };
  </script>
</head>

<body class="oswald-font1 role-admin">
  <header class="dashboard-header">
    <div class="header-container">
      <div class="logo-section">
        <a href="/index.php"><img id="logoH" src="/assets/logo.png" alt="Logo du site" /></a>
        <a href="/index.php"><img src="/assets/name.png" alt="Logo du site" /></a>
      </div>

      <div class="header-separator"></div>

      <h1 class="dashboard-title">Gestion des Projets</h1>

      <nav class="header-nav">
        <a href="/dashboard/admin/dashboard.php" class="nav-btn">Dashboard</a>
        <a href="/tickets/admin/ticket-manager.php" class="nav-btn">Tickets</a>
        <a href="/utilisateurs/admin/utilisateur-manager.php" class="nav-btn">Utilisateurs</a>
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
    <div class="projects-manager">
      <div class="projects-header">
        <h2>Tous les Projets</h2>
        <button class="cta-button">+ Nouveau Projet</button>
      </div>

      <div class="filters-section">
        <div class="filter-group">
          <label for="client-filter">Client:</label>
          <select id="client-filter" class="filter-select">
            <option value="all">Tous les clients</option>
            <?php foreach ($allClients as $c): ?>
              <option value="<?= $c->getId() ?>"><?= htmlspecialchars($c->getNom()) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group">
          <label for="status-filter">Statut:</label>
          <select id="status-filter" class="filter-select">
            <option value="all">Tous</option>
            <option value="actif">Actif</option>
            <option value="archive">Archivé</option>
          </select>
        </div>

        <div class="filter-group">
          <label for="contract-filter">Contrat:</label>
          <select id="contract-filter" class="filter-select">
            <option value="all">Tous</option>
            <option value="actif">Contrat actif</option>
            <option value="alerte">Heures bientôt épuisées</option>
          </select>
        </div>

        <div class="filter-group">
          <label for="sort-select">Trier par:</label>
          <select id="sort-select" class="filter-select">
            <option value="date">Date création</option>
            <option value="client">Client</option>
            <option value="heures">Heures restantes</option>
            <option value="tickets">Nombre de tickets</option>
          </select>
        </div>

        <div class="filter-group">
          <label for="collaborateur-select">Collaborateur:</label>
          <select id="collaborateur-select" class="filter-select">
            <option value="all">Tous</option>
            <?php foreach ($collaborateurs as $collab): ?>
              <option value="<?= $collab->getId() ?>">
                <?= htmlspecialchars($collab->getName() . ' ' . $collab->getSurname()) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="table-container">
        <table class="projects-management-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nom du Projet</th>
              <th>Client</th>
              <th>Description</th>
              <th>Collaborateurs</th>
              <th>Contrat</th>
              <th>Heures Consommées</th>
              <th>Tickets</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($projetData as $pd):
              $p = $pd['projet'];
              $client = $pd['client'];
              $contrat = $pd['contrat'];
              $collabs = $pd['collabs'];

              // Validité contrat
              $contratValidite = '';
              if ($contrat && $contrat->getDateFin()) {
                $dateFin = new DateTime($contrat->getDateFin());
                $now = new DateTime();
                $contratValidite = $dateFin > $now
                  ? 'Valide jusqu\'au ' . $dateFin->format('d/m/Y')
                  : 'Expiré le ' . $dateFin->format('d/m/Y');
              }

              // Hours bar classes
              $fillClass = '';
              if ($pd['pourcentage'] >= 100) {
                $fillClass = 'exceeded';
              } elseif ($pd['pourcentage'] >= 85) {
                $fillClass = 'warning';
              } elseif ($pd['projet']->getStatut() === 'archive') {
                $fillClass = 'completed';
              }
              ?>
              <tr class="<?= $pd['rowClass'] ?>" data-project-id="<?= $p->getId() ?>"
                data-client-id="<?= $client ? $client->getId() : 0 ?>"
                data-statut="<?= htmlspecialchars($p->getStatut()) ?>"
                data-collab-ids="<?= implode(',', $pd['collabIds']) ?>">
                <td>
                  <a href="./generic-project.php?id=<?= $p->getId() ?>"
                    class="project-link">#P<?= str_pad($p->getId(), 3, '0', STR_PAD_LEFT) ?></a>
                </td>
                <td>
                  <a href="./generic-project.php?id=<?= $p->getId() ?>"
                    class="project-link project-title"><?= htmlspecialchars($p->getNom()) ?></a>
                </td>
                <td><?= $client ? htmlspecialchars($client->getNom()) : '-' ?></td>
                <td class="description-cell">
                  <?= htmlspecialchars($p->getDescription() ?? '') ?>
                </td>
                <td>
                  <div class="collaborators-cell">
                    <?php foreach ($collabs as $collab):
                      $initials = strtoupper(mb_substr($collab['name'], 0, 1) . mb_substr($collab['surname'], 0, 1));
                      $fullName = htmlspecialchars($collab['name'] . ' ' . $collab['surname']);
                      ?>
                      <div class="avatar small" title="<?= $fullName ?>"><?= $initials ?></div>
                    <?php endforeach; ?>
                  </div>
                </td>
                <td>
                  <?php if ($contrat): ?>
                    <div class="contract-info">
                      <span class="contract-hours"><?= $pd['heuresContrat'] ?>h incluses</span>
                      <?php if ($contratValidite): ?>
                        <span class="contract-validity"><?= $contratValidite ?></span>
                      <?php endif; ?>
                    </div>
                  <?php else: ?>
                    <span style="color:var(--text-muted)">Aucun contrat</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="hours-progress">
                    <div class="hours-bar">
                      <div class="hours-fill <?= $fillClass ?>" style="width: <?= min($pd['pourcentage'], 100) ?>%"></div>
                    </div>
                    <span class="hours-text <?= $fillClass ?>"><?= $pd['heuresConsommees'] ?>h /
                      <?= $pd['heuresContrat'] ?>h</span>
                    <?php if ($pd['heuresConsommees'] > $pd['heuresContrat'] && $pd['heuresContrat'] > 0): ?>
                      <span class="hours-alert danger"><img src="/assets/urgent.png" alt="interdit" class="inline-icon" />
                        +<?= round($pd['heuresConsommees'] - $pd['heuresContrat'], 1) ?>h facturables</span>
                    <?php elseif ($pd['rowClass'] === 'hours-warning'): ?>
                      <span class="hours-alert"><img src="/assets/avertissement.png" alt="avertissement"
                          class="inline-icon" />
                        Bientôt épuisées</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div class="tickets-info">
                    <span class="ticket-count"><?= $pd['nbTickets'] ?> tickets</span>
                    <div class="ticket-status-mini">
                      <span class="mini-badge open"><?= $pd['nbOuverts'] ?></span>
                      <span class="mini-badge progress"><?= $pd['nbEnCours'] ?></span>
                      <span class="mini-badge done"><?= $pd['nbTermines'] ?></span>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="status-badge <?= $pd['statusClass'] ?>">
                    <?= $p->getStatut() === 'actif' ? 'Actif' : 'Archivé' ?>
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <?php if ($contrat): ?>
                      <a href="/contrats/admin/generic-contrat.php?id=<?= $contrat->getId() ?>" class="btn-contract"
                        title="Gérer contrat">
                        <img src="/assets/contrat.png" alt="contrat" class="inline-icon" />
                      </a>
                    <?php endif; ?>
                    <button class="btn-delete" title="Supprimer" data-project-id="<?= $p->getId() ?>"
                      onclick="if(confirm('Supprimer le projet #P<?= str_pad($p->getId(), 3, '0', STR_PAD_LEFT) ?> - <?= addslashes(htmlspecialchars($p->getNom())) ?> ?')) { document.getElementById('delete-form-<?= $p->getId() ?>').submit(); }">
                      <img src="/assets/supprimer.png" alt="supprimer" class="inline-icon" />
                    </button>
                    <form id="delete-form-<?= $p->getId() ?>" method="POST" action="/php/actions/delete-project.php"
                      style="display:none;">
                      <input type="hidden" name="project_id" value="<?= $p->getId() ?>" />
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Stats rapides -->
      <div class="quick-stats">
        <div class="stat-item">
          <span class="stat-number"><?= $nbProjetsActifs ?></span>
          <span class="stat-label">Projets actifs</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= $nbClients ?></span>
          <span class="stat-label">Clients</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= round($totalHeuresDisponibles - $totalHeuresConsommees) ?>h</span>
          <span class="stat-label">Heures disponibles</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= round($totalHeuresConsommees) ?>h</span>
          <span class="stat-label">Heures consommées</span>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal Nouveau Projet -->
  <div class="modal-overlay" id="modal-overlay">
    <div class="modal" id="add-project-modal">
      <div class="modal-header">
        <h2>Nouveau Projet</h2>
        <button class="modal-close" id="close-modal">&times;</button>
      </div>
      <form id="add-project-form" class="modal-form" method="POST" action="/php/actions/add-project.php">
        <div class="form-section">
          <h3>Informations Générales</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="project-name">Nom du projet *</label>
              <input type="text" id="project-name" name="nom" required placeholder="Ex: Refonte Site Web" />
            </div>
          </div>
          <div class="form-group">
            <label for="project-description">Description *</label>
            <textarea id="project-description" name="description" rows="3" required
              placeholder="Description du projet..."></textarea>
          </div>
        </div>

        <div class="form-section">
          <h3>Client et Contrat</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="client-select">Client *</label>
              <select id="client-select" name="client_id" required>
                <option value="">Sélectionner un client</option>
                <?php foreach ($allClients as $c): ?>
                  <option value="<?= $c->getId() ?>"><?= htmlspecialchars($c->getNom()) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="contract-select">Contrat associé</label>
              <select id="contract-select" name="contrat_id">
                <option value="">Sélectionnez d'abord un client</option>
              </select>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Dates</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="start-date">Date de début *</label>
              <input type="date" id="start-date" name="date_debut" required />
            </div>
            <div class="form-group">
              <label for="end-date">Date de fin prévue *</label>
              <input type="date" id="end-date" name="date_fin_prevue" required />
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Collaborateurs</h3>
          <div class="form-group">
            <label for="collaborators">Assigner des collaborateurs</label>
            <select id="collaborators" name="collaborators[]" multiple size="4">
              <?php foreach ($collaborateurs as $collab): ?>
                <option value="<?= $collab->getId() ?>">
                  <?= htmlspecialchars($collab->getName() . ' ' . $collab->getSurname()) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small style="color: var(--text-muted); margin-top: 0.3rem">Maintenez Ctrl/Cmd pour sélectionner plusieurs
              collaborateurs</small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" id="cancel-modal">
            Annuler
          </button>
          <button type="submit" class="btn-submit">Créer le projet</button>
          <div class="form-error" id="form-error">
            Veuillez remplir tous les champs obligatoires.
          </div>
        </div>
      </form>
    </div>
  </div>

  <script src="../../js/form-validation.js"></script>
  <script src="./project-manager.js"></script>

  <footer>
    <p>&copy; 2026 Vector - Tous droits réservés</p>
  </footer>
</body>

</html>