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

// Données pour les filtres et le contenu
$allTickets = Ticket::getAll($pdo);
$allClients = Client::getAll($pdo);
$allProjets = Projet::getAll($pdo);
$allUsers = User::getAll($pdo);
$collaborateurs = array_filter($allUsers, fn($u) => $u->getRole() === 'collaborateur');

// Stats
$nbTotal = count($allTickets);
$nbEnCours = count(array_filter($allTickets, fn($t) => $t->getStatut() === 'en_cours'));
$nbAValider = count(array_filter($allTickets, fn($t) => $t->getValidationStatus() === 'en_attente'));
$tempsTotalPasse = array_sum(array_map(fn($t) => $t->getTempsPasse(), $allTickets));

// Pré-calculer client et projet par ticket pour le JS
$ticketDataForJS = [];
foreach ($allTickets as $ticket) {
  $projet = $ticket->getProjetId() ? Projet::getById($pdo, $ticket->getProjetId()) : null;
  $client = $projet ? $projet->getClient($pdo) : null;
  $collabs = $ticket->getCollaborateurs($pdo);
  $collabIds = array_map(fn($c) => $c['id'], $collabs);

  $ticketDataForJS[] = [
    'id' => $ticket->getId(),
    'clientId' => $client ? $client->getId() : 0,
    'projetId' => $projet ? $projet->getId() : 0,
    'statut' => $ticket->getStatut(),
    'type' => $ticket->getType(),
    'priorite' => $ticket->getPriorite(),
    'collabIds' => $collabIds,
    'date' => $ticket->getDateCreation(),
  ];
}
?>
<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./ticket-manager.css" />
  <title>Gestion Tickets Admin - Vector</title>
  <script src="../../js/theme.js"></script>

  <?php
  // Générer le mapping client -> projets pour le JS 
  echo '<script>window.clientProjects = {';
  foreach ($allClients as $client) {
    echo $client->getId() . ': [';
    $projets = Projet::getByClient($pdo, $client->getId());
    foreach ($projets as $projet) {
      echo '{id: ' . $projet->getId() . ", nom: '" . addslashes($projet->getNom()) . "'},";
    }
    echo '],';
  }
  echo '};</script>';
  ?>
  <script>
    window.ticketData = [
      <?php foreach ($ticketDataForJS as $td): ?> {
          id: <?= $td['id'] ?>,
          clientId: <?= $td['clientId'] ?>,
          projetId: <?= $td['projetId'] ?>,
          statut: '<?= addslashes($td['statut']) ?>',
          type: '<?= addslashes($td['type']) ?>',
          priorite: '<?= addslashes($td['priorite']) ?>',
          collabIds: [<?= implode(',', $td['collabIds']) ?>],
          date: '<?= addslashes($td['date'] ?? '') ?>'
        },
      <?php endforeach; ?>
    ];
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

      <h1 class="dashboard-title">Gestion des Tickets</h1>

      <nav class="header-nav">
        <a href="/dashboard/admin/dashboard.php" class="nav-btn">
          Dashboard
        </a>
        <a href="/projects/admin/project-manager.php" class="nav-btn">
          Projets
        </a>
        <a href="/utilisateurs/admin/utilisateur-manager.php" class="nav-btn">
          Utilisateurs
        </a>
        <a href="/contrats/admin/contrat-manager.php" class="nav-btn">
          Contrats
        </a>
        <a href="/clients/admin/client-manager.php" class="nav-btn">
          Clients
        </a>
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
    <div class="tickets-manager">
      <div class="tickets-header">
        <h2>Tous les Tickets</h2>
        <button class="cta-button">+ Nouveau Ticket</button>
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
          <label for="project-filter">Projet:</label>
          <select id="project-filter" class="filter-select">
            <option value="all">Tous les projets</option>
            <?php foreach ($allProjets as $p): ?>
              <option value="<?= $p->getId() ?>"><?= htmlspecialchars($p->getNom()) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-group">
          <label for="status-filter">Statut:</label>
          <select id="status-filter" class="filter-select">
            <option value="all">Tous</option>
            <option value="nouveau">Nouveau</option>
            <option value="en_cours">En cours</option>
            <option value="en_attente_client">En attente client</option>
            <option value="termine">Terminé</option>
            <option value="a_valider">À valider</option>
            <option value="valide">Validé</option>
            <option value="refuse">Refusé</option>
            <option value="ferme">Fermé</option>
          </select>
        </div>

        <div class="filter-group">
          <label for="billing-filter">Facturation:</label>
          <select id="billing-filter" class="filter-select">
            <option value="all">Tous</option>
            <option value="inclus">Inclus</option>
            <option value="facturable">Facturable</option>
          </select>
        </div>

        <div class="filter-group">
          <label for="priority-filter">Priorité:</label>
          <select id="priority-filter" class="filter-select">
            <option value="all">Toutes</option>
            <option value="haute">Haute</option>
            <option value="moyenne">Moyenne</option>
            <option value="basse">Basse</option>
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
        <table class="tickets-management-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Titre</th>
              <th>Client</th>
              <th>Projet</th>
              <th>Description</th>
              <th>Statut</th>
              <th>Priorité</th>
              <th>Temps Estimé</th>
              <th>Temps Passé</th>
              <th>Collaborateurs</th>
              <th>Facturation</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allTickets as $ticket):
              $projet = $ticket->getProjetId() ? Projet::getById($pdo, $ticket->getProjetId()) : null;
              $client = $projet ? $projet->getClient($pdo) : null;
              $collabs = $ticket->getCollaborateurs($pdo);
              $clientId = $client ? $client->getId() : 0;
              $projetId = $projet ? $projet->getId() : 0;
              $collabIds = implode(',', array_map(fn($c) => $c['id'], $collabs));
              $overtime = $ticket->getTempsPasse() - $ticket->getTempsEstime();

              // Classes de statut pour le select
              $statusClassMap = [
                'nouveau' => 'status-nouveau',
                'en_cours' => 'status-progress',
                'en_attente_client' => 'status-pending',
                'termine' => 'status-completed',
                'a_valider' => 'status-validate',
                'valide' => 'status-completed',
                'refuse' => 'status-refused',
                'ferme' => 'status-completed',
              ];
              $statusClass = $statusClassMap[$ticket->getStatut()] ?? '';

              $priorityClassMap = [
                'haute' => 'priority-high',
                'moyenne' => 'priority-medium',
                'basse' => 'priority-low',
              ];
              $priorityClass = $priorityClassMap[$ticket->getPriorite()] ?? '';
              ?>
              <tr data-ticket-id="<?= $ticket->getId() ?>" data-client-id="<?= $clientId ?>"
                data-projet-id="<?= $projetId ?>" data-statut="<?= htmlspecialchars($ticket->getStatut()) ?>"
                data-type="<?= htmlspecialchars($ticket->getType()) ?>"
                data-priorite="<?= htmlspecialchars($ticket->getPriorite()) ?>" data-collab-ids="<?= $collabIds ?>"
                data-date="<?= htmlspecialchars($ticket->getDateCreation() ?? '') ?>"
                <?= $ticket->getValidationStatus() === 'en_attente' ? 'class="validation-required"' : '' ?>>
                <td>
                  <a href="./generic-ticket.php?id=<?= urlencode($ticket->getId()) ?>" class="ticket-link"
                    style="color: inherit; text-decoration: none;">
                    <span class="ticket-id-badge">#<?= htmlspecialchars($ticket->getId()) ?></span>
                  </a>
                </td>
                <td>
                  <a href="./generic-ticket.php?id=<?= urlencode($ticket->getId()) ?>" class="ticket-link ticket-title"
                    style="color: inherit; text-decoration: none;">
                    <?= htmlspecialchars($ticket->getTitre()) ?>
                  </a>
                </td>
                <td><?= $client ? htmlspecialchars($client->getNom()) : '-' ?></td>
                <td><span class="project-badge"><?= $projet ? htmlspecialchars($projet->getNom()) : '-' ?></span></td>
                <td class="description-cell">
                  <?= htmlspecialchars($ticket->getDescription() ?? '') ?>
                </td>
                <td>
                  <span
                    class="status-badge <?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $ticket->getStatut())) ?></span>
                </td>
                <td>
                  <span class="priority-badge <?= $ticket->getPriorite() ?>"><?= ucfirst($ticket->getPriorite()) ?></span>
                </td>
                <td class="time-cell"><?= $ticket->getTempsEstime() ?>h</td>
                <td class="time-cell">
                  <div class="time-display">
                    <span><?= $ticket->getTempsPasse() ?>h</span>
                    <?php if ($overtime > 0): ?>
                      <span class="overtime-badge">+<?= $overtime ?>h</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div class="collaborators-cell">
                    <?php foreach ($collabs as $collab): ?>
                      <div class="avatar small" title="<?= htmlspecialchars($collab['name'] . ' ' . $collab['surname']) ?>">
                        <?= htmlspecialchars($collab['name'][0] . $collab['surname'][0]) ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </td>
                <td>
                  <span
                    class="billing-badge billing-<?= strtolower($ticket->getType()) ?>"><?= ucfirst($ticket->getType()) ?></span>
                  <?php if ($ticket->getValidationStatus() === 'en_attente'): ?>
                    <span class="validation-badge">&#9203; En attente validation</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action-buttons">
                    <a href="./generic-ticket.php?id=<?= urlencode($ticket->getId()) ?>" class="btn-edit"
                      title="Voir / Modifier">
                      <img src="/assets/editer.png" alt="éditer" class="inline-icon" />
                    </a>
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
          <span class="stat-number"><?= $nbTotal ?></span>
          <span class="stat-label">Total tickets</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= $nbEnCours ?></span>
          <span class="stat-label">En cours</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= $nbAValider ?></span>
          <span class="stat-label">En attente validation</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= $tempsTotalPasse ?>h</span>
          <span class="stat-label">Temps total</span>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal Nouveau Ticket (cachée par défaut) -->
  <div class="modal-overlay" id="modal-ticket">
    <div class="modal">
      <div class="modal-header">
        <h2>Nouveau Ticket</h2>
        <button class="modal-close">&times;</button>
      </div>
      <form class="modal-form" method="POST" action="/php/actions/add-ticket.php">
        <div class="form-section">
          <h3>Informations Générales</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="ticket-title">Titre du ticket *</label>
              <input type="text" id="ticket-title" name="title" required placeholder="Ex: Problème de connexion" />
            </div>
          </div>
          <div class="form-group">
            <label for="ticket-description">Description *</label>
            <textarea id="ticket-description" rows="3" required name="description"
              placeholder="Description détaillée du ticket..."></textarea>
          </div>
        </div>
        <div class="form-section">
          <h3>Client et Projet</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="ticket-client">Client *</label>
              <select id="ticket-client" name="client_id" required>
                <option value="">Sélectionner un client</option>
                <?php foreach ($allClients as $client): ?>
                  <option value="<?= $client->getId() ?>"><?= htmlspecialchars($client->getNom()) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="ticket-project">Projet *</label>
              <select id="ticket-project" name="project_id" required>
                <option value="">Sélectionner un projet</option>
                <?php foreach ($allProjets as $projet):
                  $projetClient = $projet->getClient($pdo);
                  ?>
                  <option value="<?= $projet->getId() ?>"
                    data-client-id="<?= $projetClient ? $projetClient->getId() : '' ?>">
                    <?= htmlspecialchars($projet->getNom()) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Priorité et Statut</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="ticket-priority">Priorité *</label>
              <select id="ticket-priority" name="priority" required>
                <option value="">Sélectionner</option>
                <option value="haute">Haute</option>
                <option value="moyenne">Moyenne</option>
                <option value="basse">Basse</option>
              </select>
            </div>
            <div class="form-group">
              <label for="ticket-status">Statut</label>
              <select id="ticket-status" name="status">
                <option value="nouveau">Nouveau</option>
                <option value="en_cours">En cours</option>
                <option value="en_attente_client">En attente client</option>
                <option value="termine">Terminé</option>
                <option value="a_valider">À validé</option>
                <option value="valide">Validé</option>
                <option value="refuse">Refusé</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Assignation et Temps</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="ticket-collaborators">Collaborateurs</label>
              <select id="ticket-collaborators" name="collaborators[]" multiple size="4">
                <?php foreach ($collaborateurs as $collab): ?>
                  <option value="<?= $collab->getId() ?>">
                    <?= htmlspecialchars($collab->getName() . ' ' . $collab->getSurname()) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <small>Maintenez Ctrl pour sélectionner plusieurs collaborateurs</small>
            </div>
            <div class="form-group">
              <label for="ticket-time">Temps estimé (heures) *</label>
              <input type="number" id="ticket-time" name="ticket-time" required min="0.5" step="0.5"
                placeholder="Ex: 8" />
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Facturation</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="ticket-billing">Type de facturation *</label>
              <select id="ticket-billing" name="billing_type" required>
                <option value="inclus">Inclus dans le contrat</option>
                <option value="facturable">Facturable</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div class="form-error">
            Veuillez remplir tous les champs obligatoires.
          </div>
          <button type="button" class="btn-cancel">Annuler</button>
          <button type="submit" class="btn-submit">Créer le ticket</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../../js/form-validation.js"></script>
  <script src="./ticket-manager.js"></script>

  <footer>
    <p>&copy; 2026 Vector - Tous droits réservés</p>
  </footer>
</body>

</html>