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

// Récupérer le ticket par ID
$ticketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ticket = $ticketId ? Ticket::getById($pdo, $ticketId) : null;

if (!$ticket) {
  header("Location: /tickets/admin/ticket-manager.php?error=ticket_introuvable");
  exit;
}

// Données liées
$projet = $ticket->getProjetId() ? Projet::getById($pdo, $ticket->getProjetId()) : null;
$client = $projet ? $projet->getClient($pdo) : null;
$collabs = $ticket->getCollaborateurs($pdo);
$tempsEntries = $ticket->getTemps($pdo);
$commentaires = $ticket->getCommentaires($pdo);

// Contrat lié au projet
$contrats = $projet ? Contrat::getByProjet($pdo, $projet->getId()) : [];
$contrat = !empty($contrats) ? $contrats[0] : null;

// Tous les clients, projets, collaborateurs (pour les selects)
$allClients = Client::getAll($pdo);
$allProjets = Projet::getAll($pdo);
$allUsers = User::getAll($pdo);
$allCollaborateurs = array_filter($allUsers, fn($u) => $u->getRole() === 'collaborateur');

// Tickets ouverts/total du client
$clientTicketsTotal = 0;
$clientTicketsOuverts = 0;
if ($client) {
  $clientProjets = Projet::getByClient($pdo, $client->getId());
  foreach ($clientProjets as $cp) {
    $cpTickets = Ticket::getByProjetId($pdo, $cp->getId());
    $clientTicketsTotal += count($cpTickets);
    $clientTicketsOuverts += count(array_filter($cpTickets, fn($t) => $t->isOpen()));
  }
}

// Tickets ouverts du projet + progression
$projetTicketsOuverts = 0;
$projetProgression = 0;
if ($projet) {
  $projetTickets = Ticket::getByProjetId($pdo, $projet->getId());
  $projetTicketsOuverts = count(array_filter($projetTickets, fn($t) => $t->isOpen()));
  $projetTempsPasse = $projet->getTotalTempsPasse($pdo);
  $projetTempsEstime = $projet->getTotalTempsEstime($pdo);
  $projetProgression = $projetTempsEstime > 0 ? round(($projetTempsPasse / $projetTempsEstime) * 100) : 0;
}

// Contact principal du client
$contactPrincipal = $client ? $client->getContactPrincipal($pdo) : null;

// Initiales admin
$adminInitials = strtoupper(mb_substr($user->getName(), 0, 1) . mb_substr($user->getSurname(), 0, 1));
?>
<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./generic-ticket.css" />
  <title>Ticket #<?= $ticket->getId() ?> - Vector</title>
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

      <h1 class="dashboard-title">Détail Ticket</h1>

      <nav class="header-nav">
        <a href="/dashboard/admin/dashboard.php" class="nav-btn">Dashboard</a>
        <a href="/tickets/admin/ticket-manager.php" class="nav-btn">Tickets</a>
        <a href="/projects/admin/project-manager.php" class="nav-btn">Projets</a>
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
    <div class="ticket-detail-container">
      <!-- En-tête du ticket -->
      <div class="ticket-header">
        <div class="ticket-header-left">
          <span class="ticket-id">#<?= $ticket->getId() ?></span>
          <h2 class="ticket-title-main"><?= htmlspecialchars($ticket->getTitre()) ?></h2>
        </div>
      </div>

      <!-- Métadonnées principales -->
      <form method="POST" action="/php/actions/update-ticket.php">
        <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
        <input type="hidden" name="action" value="update_metadata" />
        <div class="ticket-metadata">
          <div class="metadata-card">
            <label>Statut</label>
            <?php
            $statuts = [
              'nouveau' => 'Nouveau',
              'en_cours' => 'En cours',
              'en_attente_client' => 'En attente client',
              'termine' => 'Terminé',
              'a_valider' => 'À valider',
              'valide' => 'Validé',
              'refuse' => 'Refusé',
              'ferme' => 'Fermé',
            ];
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
            $currentStatutClass = $statusClassMap[$ticket->getStatut()] ?? '';
            ?>
            <select name="statut" class="status-select <?= $currentStatutClass ?>">
              <?php foreach ($statuts as $val => $label): ?>
                <option value="<?= $val ?>" <?= $ticket->getStatut() === $val ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="metadata-card">
            <label>Priorité</label>
            <?php
            $priorites = ['haute' => 'Haute', 'moyenne' => 'Moyenne', 'basse' => 'Basse'];
            $priorityClassMap = ['haute' => 'priority-high', 'moyenne' => 'priority-medium', 'basse' => 'priority-low'];
            $currentPriorityClass = $priorityClassMap[$ticket->getPriorite()] ?? '';
            ?>
            <select name="priorite" class="priority-select <?= $currentPriorityClass ?>">
              <?php foreach ($priorites as $val => $label): ?>
                <option value="<?= $val ?>" <?= $ticket->getPriorite() === $val ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="metadata-card">
            <label>Type</label>
            <select name="type" class="type-select">
              <option value="inclus" <?= $ticket->getType() === 'inclus' ? 'selected' : '' ?>>Inclus</option>
              <option value="facturable" <?= $ticket->getType() === 'facturable' ? 'selected' : '' ?>>Facturable</option>
            </select>
            <?php if ($ticket->getType() === 'facturable'): ?>
              <?php
              $validationLabels = [
                'en_attente' => ['text' => 'En attente de validation', 'class' => 'billing-pending'],
                'valide'     => ['text' => 'Validé par le client', 'class' => 'billing-validated'],
                'refuse'     => ['text' => 'Refusé par le client', 'class' => 'billing-refused'],
              ];
              $vs = $ticket->getValidationStatus();
              $billingInfo = $validationLabels[$vs] ?? $validationLabels['en_attente'];
              ?>
              <span class="billing-status <?= $billingInfo['class'] ?>"><?= $billingInfo['text'] ?></span>
            <?php else: ?>
              <span class="billing-status billing-included">Inclus dans le contrat</span>
            <?php endif; ?>
          </div>

          <div class="metadata-card">
            <label>Temps estimé</label>
            <input type="number" name="temps_estime" class="time-input-meta" value="<?= $ticket->getTempsEstime() ?>" step="0.5" min="0" />
            <span>heures</span>
          </div>

          <div class="metadata-card highlight">
            <label>Temps passé total</label>
            <div class="time-total"><?= $ticket->getTempsPasse() ?>h</div>
          </div>

          <!-- Projet (hidden select value) -->
          <input type="hidden" name="projet_id" value="<?= $projet ? $projet->getId() : '' ?>" id="hidden-projet-id" />

          <div class="metadata-card">
            <button type="submit" class="btn-primary">Enregistrer</button>
          </div>
        </div>
      </form>

      <!-- Informations client, projet et contrat -->
      <div class="project-client-info">
        <div class="info-card">
          <h3>Client</h3>
          <select class="client-select">
            <option value="">-- Aucun --</option>
            <?php foreach ($allClients as $c): ?>
              <option value="<?= $c->getId() ?>" <?= ($client && $client->getId() === $c->getId()) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c->getNom()) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if ($contactPrincipal): ?>
            <p class="client-contact"><?= htmlspecialchars($contactPrincipal['email'] ?? '') ?></p>
          <?php endif; ?>
          <div class="client-stats">
            <span>Tickets ouverts: <strong><?= $clientTicketsOuverts ?></strong></span>
            <span>Tickets total: <strong><?= $clientTicketsTotal ?></strong></span>
          </div>
        </div>

        <div class="info-card">
          <h3>Projet</h3>
          <select class="project-select">
            <option value="">-- Aucun --</option>
            <?php foreach ($allProjets as $p):
              $pClient = $p->getClient($pdo);
            ?>
              <option value="<?= $p->getId() ?>"
                data-client-id="<?= $pClient ? $pClient->getId() : '' ?>"
                <?= ($projet && $projet->getId() === $p->getId()) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p->getNom()) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if ($projet): ?>
            <div class="project-stats-small">
              <span>Tickets: <strong><?= $projetTicketsOuverts ?> ouverts</strong></span>
              <span>Progression: <strong><?= $projetProgression ?>%</strong></span>
            </div>
          <?php endif; ?>
        </div>


        <div class="info-card">
          <h3>Collaborateurs assignés</h3>
          <div class="collaborators-list">
            <?php foreach ($collabs as $collab): ?>
              <div class="collaborator-item" data-collab-id="<?= $collab['id'] ?>">
                <div class="avatar"><?= strtoupper(mb_substr($collab['name'], 0, 1) . mb_substr($collab['surname'], 0, 1)) ?></div>
                <span><?= htmlspecialchars($collab['name'] . ' ' . $collab['surname']) ?></span>
                <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
                  <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                  <input type="hidden" name="action" value="remove_collaborateur" />
                  <input type="hidden" name="collaborateur_id" value="<?= $collab['id'] ?>" />
                  <button type="submit" class="btn-remove-collab" title="Retirer">✖</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
          <?php
          $collabIds = array_map(fn($c) => $c['id'], $collabs);
          $availableCollabs = array_filter($allCollaborateurs, fn($u) => !in_array($u->getId(), $collabIds));
          ?>
          <form method="POST" action="/php/actions/update-ticket.php" style="display:flex;gap:8px;align-items:center;margin-top:8px">
            <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
            <input type="hidden" name="action" value="add_collaborateur" />
            <select name="collaborateur_id" class="add-collaborator-select">
              <option value="">-- Ajouter un collaborateur --</option>
              <?php foreach ($availableCollabs as $ac): ?>
                <option value="<?= $ac->getId() ?>"><?= htmlspecialchars($ac->getName() . ' ' . $ac->getSurname()) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Ajouter</button>
          </form>
        </div>
      </div>

      <!-- Description du ticket -->
      <form method="POST" action="/php/actions/update-ticket.php">
        <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
        <input type="hidden" name="action" value="update_description" />
        <div class="ticket-section">
          <h3>Description</h3>
          <textarea name="description" class="ticket-description" rows="6"><?= htmlspecialchars($ticket->getDescription() ?? '') ?></textarea>
          <button type="submit" class="btn-primary" style="margin-top:10px">Enregistrer la description</button>
        </div>
      </form>

      <!-- Admin: Gestion du temps -->
      <div class="ticket-section admin-time-section">
        <h3>Gestion du temps (Admin)</h3>
        <div class="admin-time-controls">
          <div class="control-item">
            <label>Forcer le type de facturation:</label>
            <div class="btn-group">
              <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
                <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                <input type="hidden" name="action" value="force_type" />
                <input type="hidden" name="type" value="inclus" />
                <button type="submit" class="btn-toggle <?= $ticket->getType() === 'inclus' ? 'active' : '' ?>">Inclus</button>
              </form>
              <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
                <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                <input type="hidden" name="action" value="force_type" />
                <input type="hidden" name="type" value="facturable" />
                <button type="submit" class="btn-toggle <?= $ticket->getType() === 'facturable' ? 'active' : '' ?>">Facturable</button>
              </form>
            </div>
          </div>
          <div class="control-item">
            <label>Ajuster le temps passé:</label>
            <form method="POST" action="/php/actions/update-ticket.php" style="display:flex;gap:8px;align-items:center">
              <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
              <input type="hidden" name="action" value="adjust_temps" />
              <div class="time-adjust">
                <input type="number" name="temps_passe" class="form-control-sm" value="<?= $ticket->getTempsPasse() ?>" step="0.5" min="0" />
                <span>heures</span>
                <button type="submit" class="btn-secondary">Appliquer</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Enregistrement du temps -->
      <form method="POST" action="/php/actions/update-ticket.php">
        <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
        <input type="hidden" name="action" value="add_temps" />
        <div class="ticket-section">
          <h3>Enregistrer du temps</h3>
          <div class="time-entry-form">
            <div class="form-group">
              <label>Collaborateur</label>
              <select name="collaborateur_id" class="form-control">
                <?php foreach ($collabs as $collab): ?>
                  <option value="<?= $collab['id'] ?>"><?= htmlspecialchars($collab['name'] . ' ' . $collab['surname']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Date</label>
              <input type="date" name="date_travail" class="form-control" value="<?= date('Y-m-d') ?>" />
            </div>
            <div class="form-group">
              <label>Durée (heures)</label>
              <input type="number" name="duree" class="form-control" step="0.5" min="0" placeholder="2.5" required />
            </div>
            <div class="form-group full-width">
              <label>Commentaire (optionnel)</label>
              <input type="text" name="commentaire" class="form-control" placeholder="Description du travail effectué..." />
            </div>
            <button type="submit" class="btn-primary">Ajouter l'entrée</button>
          </div>
        </div>
      </form>

      <!-- Historique du temps -->
      <div class="ticket-section">
        <h3>Historique du temps passé</h3>
        <div class="time-entries-list">
          <?php foreach ($tempsEntries as $entry):
            $entryDate = new DateTime($entry['date_travail']);
            $initials = strtoupper(mb_substr($entry['name'] ?? '?', 0, 1) . mb_substr($entry['surname'] ?? '?', 0, 1));
          ?>
            <div class="time-entry" data-entry-id="<?= $entry['id'] ?>">
              <div class="entry-header">
                <div class="entry-collaborator">
                  <div class="avatar small"><?= $initials ?></div>
                  <span><?= htmlspecialchars(($entry['name'] ?? '') . ' ' . ($entry['surname'] ?? '')) ?></span>
                </div>
                <div class="entry-date"><?= $entryDate->format('d F Y') ?></div>
                <div class="entry-duration"><?= $entry['duree'] ?>h</div>
                <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
                  <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                  <input type="hidden" name="action" value="delete_temps" />
                  <input type="hidden" name="temps_id" value="<?= $entry['id'] ?>" />
                  <button type="submit" class="btn-delete-entry" title="Supprimer" onclick="return confirm('Supprimer cette entrée de temps ?')">
                    <img src="/assets/supprimer.png" alt="supprimer" class="inline-icon" />
                  </button>
                </form>
              </div>
              <?php if (!empty($entry['commentaire'])): ?>
                <div class="entry-comment"><?= htmlspecialchars($entry['commentaire']) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if (empty($tempsEntries)): ?>
            <p class="text-muted">Aucune entrée de temps enregistrée.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Commentaires -->
      <div class="ticket-section">
        <h3>Commentaires</h3>
        <div class="comments-list">
          <?php foreach ($commentaires as $com):
            $comDate = new DateTime($com['date_creation']);
            $comInitials = strtoupper(mb_substr($com['name'] ?? '?', 0, 1) . mb_substr($com['surname'] ?? '?', 0, 1));
          ?>
            <div class="comment" data-comment-id="<?= $com['id'] ?>">
              <div class="comment-header">
                <div class="comment-author">
                  <div class="avatar small"><?= $comInitials ?></div>
                  <div>
                    <strong><?= htmlspecialchars(($com['name'] ?? '') . ' ' . ($com['surname'] ?? '')) ?></strong>
                    <span class="comment-date"><?= $comDate->format('d M Y') ?> à <?= $comDate->format('H:i') ?></span>
                  </div>
                </div>
                <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
                  <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
                  <input type="hidden" name="action" value="delete_commentaire" />
                  <input type="hidden" name="commentaire_id" value="<?= $com['id'] ?>" />
                  <button type="submit" class="btn-delete-comment" title="Supprimer" onclick="return confirm('Supprimer ce commentaire ?')">
                    <img src="/assets/supprimer.png" alt="supprimer" class="inline-icon" />
                  </button>
                </form>
              </div>
              <div class="comment-body"><?= htmlspecialchars($com['contenu']) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($commentaires)): ?>
            <p class="text-muted">Aucun commentaire.</p>
          <?php endif; ?>
        </div>

        <form method="POST" action="/php/actions/update-ticket.php">
          <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
          <input type="hidden" name="action" value="add_commentaire" />
          <div class="add-comment">
            <textarea name="contenu" class="comment-input" placeholder="Ajouter un commentaire..." rows="3" required minlength="3"></textarea>
            <button type="submit" class="btn-primary">Publier</button>
          </div>
        </form>
      </div>

      <!-- Admin: Validation forcée -->
      <div class="ticket-section admin-validation-section" style="<?= $ticket->getType() === 'inclus' ? 'display:none' : '' ?>">
        <h3>Validation client (Admin)</h3>
        <div class="validation-info">
          <p class="info-text">
            Ce ticket est marqué comme <strong>facturable</strong>. Il nécessite une validation du client avant facturation.
          </p>
          <div class="validation-status">
            <span class="status-label">Statut de validation:</span>
            <?php
            $validationMap = [
              'en_attente' => ['label' => 'En attente de validation', 'class' => 'pending'],
              'valide' => ['label' => 'Validé', 'class' => 'validated'],
              'refuse' => ['label' => 'Refusé', 'class' => 'refused'],
            ];
            $vs = $ticket->getValidationStatus();
            $vInfo = $validationMap[$vs] ?? $validationMap['en_attente'];
            ?>
            <span class="validation-badge <?= $vInfo['class'] ?>"><?= $vInfo['label'] ?></span>
          </div>
          <div class="admin-validation-controls">
            <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
              <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
              <input type="hidden" name="action" value="force_validate" />
              <button type="submit" class="btn-force-validate" onclick="return confirm('Forcer la validation ?')">Forcer la validation</button>
            </form>
            <form method="POST" action="/php/actions/update-ticket.php" style="display:inline">
              <input type="hidden" name="ticket_id" value="<?= $ticket->getId() ?>" />
              <input type="hidden" name="action" value="force_reject" />
              <button type="submit" class="btn-force-reject" onclick="return confirm('Forcer le refus ?')">Forcer le refus</button>
            </form>
            <p class="warning-text">
              ! Ces actions contournent la validation normale du client. À utiliser uniquement en cas de nécessité.
            </p>
          </div>
        </div>
      </div>

      <!-- Historique des actions -->
      <div class="ticket-section">
        <h3>Historique des actions</h3>
        <div class="history-list">
          <?php
          $historyItems = [];
          if ($ticket->getDateCreation()) {
            $historyItems[] = [
              'date' => $ticket->getDateCreation(),
              'text' => 'Ticket créé',
            ];
          }
          foreach ($tempsEntries as $entry) {
            $who = trim(($entry['name'] ?? '') . ' ' . ($entry['surname'] ?? ''));
            $historyItems[] = [
              'date' => $entry['date_travail'],
              'text' => '<strong>' . htmlspecialchars($who) . '</strong> a enregistré ' . $entry['duree'] . 'h de travail',
            ];
          }
          foreach ($commentaires as $com) {
            $who = trim(($com['name'] ?? '') . ' ' . ($com['surname'] ?? ''));
            $historyItems[] = [
              'date' => $com['date_creation'],
              'text' => '<strong>' . htmlspecialchars($who) . '</strong> a ajouté un commentaire',
            ];
          }
          usort($historyItems, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
          ?>
          <?php foreach ($historyItems as $item):
            $itemDate = new DateTime($item['date']);
          ?>
            <div class="history-item">
              <div class="history-icon">•</div>
              <div class="history-content">
                <div class="history-text"><?= $item['text'] ?></div>
                <div class="history-date"><?= $itemDate->format('d F Y') ?> à <?= $itemDate->format('H:i') ?></div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($historyItems)): ?>
            <p class="text-muted">Aucun historique.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <footer>
    <p>&copy; 2026 Vector - Tous droits réservés</p>
  </footer>

  <script>
    window.ticketConfig = {
      ticketId: <?= $ticket->getId() ?>,
      adminInitials: '<?= addslashes($adminInitials) ?>',
      adminName: '<?= addslashes($user->getName() . ' ' . $user->getSurname()) ?>'
    };
  </script>
  <script src="../../js/form-validation.js"></script>
  <script src="./generic-ticket.js" defer></script>
</body>

</html>
