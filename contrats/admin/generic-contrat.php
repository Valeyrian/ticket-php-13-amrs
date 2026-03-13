<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../php/classes/contrat.php';
require_once __DIR__ . '/../../php/classes/client.php';
require_once __DIR__ . '/../../php/classes/projet.php';
require_once __DIR__ . '/../../php/classes/ticket.php';
require_once __DIR__ . '/../../php/classes/user.php';

session_start();

if (!isset($_SESSION['user']) || !$_SESSION['user']->isAdmin()) {
  header("Location: /login_or_registration/login.php");
  exit;
}

$user = $_SESSION['user'];
$contratId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$contratId) {
  header("Location: /contrats/admin/contrat-manager.php");
  exit;
}

$contrat = Contrat::getById($pdo, $contratId);
if (!$contrat) {
  header("Location: /contrats/admin/contrat-manager.php?error=contrat_introuvable");
  exit;
}

// Recalculer les heures consommées
$contrat->recalculerHeuresConsommees($pdo);

// Données du contrat
$pourcentage = $contrat->getPourcentageConsomme();
$heuresRestantes = $contrat->getHeuresRestantes();
$heuresConsommees = $contrat->getHeuresConsommees();
$heuresTotales = $contrat->getHeuresTotales();
$tauxHoraire = $contrat->getTauxHoraire();
$montantTotal = $contrat->getMontantTotal();
$montantConsomme = $heuresConsommees * $tauxHoraire;
$montantRestant = $montantTotal - $montantConsomme;

// SVG circular progress
$circumference = 2 * M_PI * 75; // ~471.24
$offset = $circumference * (1 - $pourcentage / 100);

// Clients
$clients = $contrat->getClients($pdo);
$clientNom = !empty($clients) ? $clients[0]['nom'] : '—';
$clientInitials = !empty($clients) ? mb_strtoupper(mb_substr($clients[0]['nom'], 0, 2)) : '??';
$clientVille = !empty($clients) ? ($clients[0]['ville'] ?? '') : '';
$clientId = !empty($clients) ? $clients[0]['id'] : 0;

// Projets liés
$projets = $contrat->getProjets($pdo);
$allClients = Client::getAll($pdo);
$allProjets = Projet::getAll($pdo);

// Statut visuel
$statutClass = 'active';
$statutLabel = 'Actif';
if ($contrat->getStatut() === 'termine') {
  $statutClass = 'completed';
  $statutLabel = 'Terminé';
} elseif ($contrat->getStatut() === 'inactif') {
  $statutClass = 'inactive';
  $statutLabel = 'Suspendu';
} elseif ($pourcentage >= 90 && $contrat->isActif()) {
  $statutClass = 'warning';
  $statutLabel = 'Bientôt épuisé';
}

// Dates formatées
$dateDebutFormatted = $contrat->getDateDebut() ? date('d/m/Y', strtotime($contrat->getDateDebut())) : '—';
$dateFinFormatted = $contrat->getDateFin() ? date('d/m/Y', strtotime($contrat->getDateFin())) : '—';

// Durée en mois
$duree = '—';
if ($contrat->getDateDebut() && $contrat->getDateFin()) {
  $d1 = new DateTime($contrat->getDateDebut());
  $d2 = new DateTime($contrat->getDateFin());
  $diff = $d1->diff($d2);
  $mois = $diff->m + ($diff->y * 12);
  $duree = $mois . ' mois';
}

// Saisies de temps : tous les ticket_temps des tickets liés aux projets du contrat
$timeEntries = [];
foreach ($projets as $p) {
  $projetObj = Projet::getById($pdo, $p['id']);
  if (!$projetObj)
    continue;
  $tickets = Ticket::getByProjetId($pdo, $p['id']);
  foreach ($tickets as $t) {
    $temps = $t->getTemps($pdo);
    foreach ($temps as $entry) {
      $entry['ticket_id'] = $t->getId();
      $entry['ticket_titre'] = $t->getTitre();
      $timeEntries[] = $entry;
    }
  }
}
// Trier par date desc
usort($timeEntries, function ($a, $b) {
  return strtotime($b['date_travail']) - strtotime($a['date_travail']);
});

// Collaborateurs liés (pour la modal ajouter temps)
$collaborateurs = [];
foreach ($projets as $p) {
  $projetObj = Projet::getById($pdo, $p['id']);
  if (!$projetObj)
    continue;
  $collabs = $projetObj->getCollaborateurs($pdo);
  foreach ($collabs as $col) {
    $collaborateurs[$col['id']] = $col;
  }
}
// Si aucun collaborateur lié aux projets, charger tous les collaborateurs
if (empty($collaborateurs)) {
  $allUsers = User::getAll($pdo);
  foreach ($allUsers as $u) {
    if ($u->getRole() === 'collaborateur') {
      $collaborateurs[$u->getId()] = [
        'id' => $u->getId(),
        'name' => $u->getName(),
        'surname' => $u->getSurname(),
      ];
    }
  }
}

// Tickets liés (pour la modal ajouter temps)
$ticketsLies = [];
foreach ($projets as $p) {
  $tickets = Ticket::getByProjetId($pdo, $p['id']);
  foreach ($tickets as $t) {
    $ticketsLies[] = $t;
  }
}

$contratIdFormatted = '#CT' . str_pad($contrat->getId(), 3, '0', STR_PAD_LEFT);
?>
<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./generic-contrat.css" />
  <title>Contrat <?= $contratIdFormatted ?> - Vector</title>
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

      <h1 class="dashboard-title">Détail Contrat</h1>

      <nav class="header-nav">
        <a href="/dashboard/admin/dashboard.php" class="nav-btn">Dashboard</a>
        <a href="/contrats/admin/contrat-manager.php" class="nav-btn">Contrats</a>
        <a href="/clients/admin/client-manager.html" class="nav-btn">Clients</a>
        <a href="/projects/admin/project-manager.php" class="nav-btn">Projets</a>
        <a href="/utilisateurs/admin/utilisateur-manager.php" class="nav-btn">Utilisateurs</a>
        <a href="/tickets/admin/ticket-manager.php" class="nav-btn">Tickets</a>
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
    <div class="contract-detail">
      <!-- Contract Header -->
      <div class="contract-header-section">
        <div class="contract-title-block">
          <div class="contract-id"><?= $contratIdFormatted ?></div>
          <h2 class="contract-name"><?= htmlspecialchars($contrat->getNom()) ?></h2>
          <span class="status-badge <?= $statutClass ?>"><?= $statutLabel ?></span>
        </div>
        <div class="contract-actions">
          <button class="btn-edit">
            <img src="/assets/editer.png" alt="modifier" /> Modifier
          </button>
          <form method="POST" action="/php/actions/delete-contrat.php" style="display:inline"
            onsubmit="return confirm('Supprimer le contrat <?= $contratIdFormatted ?> ?')">
            <input type="hidden" name="id" value="<?= $contrat->getId() ?>" />
            <button type="submit" class="btn-delete-action">
              <img src="/assets/supprimer.png" alt="supprimer" /> Supprimer
            </button>
          </form>
        </div>
      </div>

      <!-- Progress Overview -->
      <div class="progress-section">
        <div class="progress-card">
          <h3>Consommation du contrat</h3>
          <div class="progress-visual">
            <div class="circular-progress">
              <svg width="180" height="180">
                <circle cx="90" cy="90" r="75" fill="none" stroke="#1e1f22" stroke-width="15"></circle>
                <circle cx="90" cy="90" r="75" fill="none" stroke="<?= $pourcentage >= 90 ? '#e67e22' : '#54c5b1' ?>"
                  stroke-width="15" stroke-dasharray="<?= round($circumference, 2) ?>"
                  stroke-dashoffset="<?= round($offset, 2) ?>" transform="rotate(-90 90 90)" stroke-linecap="round">
                </circle>
              </svg>
              <div class="progress-text">
                <div class="progress-percentage"><?= round($pourcentage) ?>%</div>
                <div class="progress-label">consommé</div>
              </div>
            </div>
            <div class="progress-details">
              <div class="detail-item">
                <span class="detail-label">Heures consommées:</span>
                <span class="detail-value consumed"><?= $heuresConsommees ?>h</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Heures restantes:</span>
                <span class="detail-value remaining"><?= $heuresRestantes ?>h</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Total forfait:</span>
                <span class="detail-value total"><?= $heuresTotales ?>h</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Taux horaire:</span>
                <span class="detail-value rate"><?= number_format($tauxHoraire, 0, ',', ' ') ?> €/h</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Info Grid -->
      <div class="info-grid">
        <div class="info-card">
          <h3>Informations générales</h3>
          <div class="info-row">
            <span class="info-label">Type de contrat:</span>
            <span class="info-value"><?= htmlspecialchars($contrat->getType()) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Date de début:</span>
            <span class="info-value"><?= $dateDebutFormatted ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Date de fin:</span>
            <span class="info-value"><?= $dateFinFormatted ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Durée:</span>
            <span class="info-value"><?= $duree ?></span>
          </div>
          <?php if ($contrat->getConditions()): ?>
            <div class="info-row">
              <span class="info-label">Conditions:</span>
              <span class="info-value"><?= nl2br(htmlspecialchars($contrat->getConditions())) ?></span>
            </div>
          <?php endif; ?>
        </div>

        <div class="info-card">
          <h3>Client</h3>
          <?php if (!empty($clients)): ?>
            <div class="client-info-block">
              <div class="company-logo-medium"><?= htmlspecialchars($clientInitials) ?></div>
              <div class="client-details">
                <div class="client-name"><?= htmlspecialchars($clientNom) ?></div>
                <?php if ($clientVille): ?>
                  <div class="client-contact"><?= htmlspecialchars($clientVille) ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php else: ?>
            <p style="color:var(--text-gray)">Aucun client associé</p>
          <?php endif; ?>
        </div>

        <div class="info-card">
          <h3>Financier</h3>
          <div class="info-row">
            <span class="info-label">Montant total:</span>
            <span class="info-value highlight"><?= number_format($montantTotal, 0, ',', ' ') ?> €</span>
          </div>
          <div class="info-row">
            <span class="info-label">Consommé:</span>
            <span class="info-value"><?= number_format($montantConsomme, 0, ',', ' ') ?> €</span>
          </div>
          <div class="info-row">
            <span class="info-label">Restant:</span>
            <span class="info-value"><?= number_format($montantRestant, 0, ',', ' ') ?> €</span>
          </div>
        </div>
      </div>

      <!-- Projets Liés -->
      <div class="section-card">
        <h3>Projet(s) associé(s)</h3>
        <?php if (!empty($projets)): ?>
          <?php foreach ($projets as $p):
            $projetObj = Projet::getById($pdo, $p['id']);
            $nbTickets = $projetObj ? $projetObj->countTicketsOuverts($pdo) : 0;
            $nbCollabs = $projetObj ? count($projetObj->getCollaborateurs($pdo)) : 0;
            $projetStatut = $p['statut'] ?? 'actif';
            $projetStatutClass = $projetStatut === 'actif' ? 'active' : ($projetStatut === 'termine' ? 'completed' : 'inactive');
            $projetStatutLabel = $projetStatut === 'actif' ? 'Actif' : ($projetStatut === 'termine' ? 'Terminé' : 'Suspendu');
            ?>
            <div class="project-linked-card">
              <div class="project-header">
                <div class="project-id-badge">#P<?= str_pad($p['id'], 3, '0', STR_PAD_LEFT) ?></div>
                <h4><?= htmlspecialchars($p['nom']) ?></h4>
                <span class="status-badge <?= $projetStatutClass ?>"><?= $projetStatutLabel ?></span>
              </div>
              <div class="project-info">
                <div class="project-stat">
                  <img src="/assets/ticket.png" alt="tickets" />
                  <span><?= $nbTickets ?> ticket(s) actif(s)</span>
                </div>
                <div class="project-stat">
                  <span><?= $nbCollabs ?> collaborateur(s)</span>
                </div>
              </div>
              <a href="/projects/admin/generic-project.php?id=<?= $p['id'] ?>" class="btn-view-project">Voir le projet</a>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:var(--text-gray);padding:15px 0">Aucun projet associé à ce contrat.</p>
        <?php endif; ?>
      </div>

      <!-- Saisies de temps -->
      <div class="section-card">
        <div class="section-header">
          <h3>Saisies de temps (<?= count($timeEntries) ?> entrées)</h3>
          <button class="btn-add-small">
            <img src="/assets/heures-douverture.png" alt="temps" /> Ajouter du temps
          </button>
        </div>
        <div class="time-entries-table-container">
          <table class="time-entries-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Collaborateur</th>
                <th>Ticket</th>
                <th>Description</th>
                <th>Heures</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($timeEntries)): ?>
                <?php foreach ($timeEntries as $entry):
                  $initials = mb_strtoupper(mb_substr($entry['name'] ?? '', 0, 1) . mb_substr($entry['surname'] ?? '', 0, 1));
                  $collabName = ($entry['name'] ?? '') . ' ' . ($entry['surname'] ?? '');
                  $dateFormatted = date('d/m/Y', strtotime($entry['date_travail']));
                  ?>
                  <tr>
                    <td><?= $dateFormatted ?></td>
                    <td>
                      <div class="collab-cell">
                        <div class="avatar-tiny"><?= htmlspecialchars($initials) ?></div>
                        <span><?= htmlspecialchars(trim($collabName)) ?></span>
                      </div>
                    </td>
                    <td>
                      <a href="/tickets/admin/generic-ticket.php?id=<?= $entry['ticket_id'] ?>" class="ticket-link">
                        #<?= $entry['ticket_id'] ?>
                      </a>
                      <div style="font-size:0.75rem;color:var(--text-gray)">
                        <?= htmlspecialchars($entry['ticket_titre'] ?? '') ?>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($entry['commentaire'] ?? '—') ?></td>
                    <td class="hours-cell"><?= $entry['duree'] ?>h</td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" style="text-align:center;color:var(--text-gray);padding:30px">Aucune saisie de temps.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal Modifier Contrat -->
  <div class="modal-overlay" id="modal-edit-overlay">
    <div class="modal" id="edit-contract-modal">
      <div class="modal-header">
        <h2>Modifier le Contrat</h2>
        <button class="modal-close" id="close-edit-modal">&times;</button>
      </div>
      <form id="edit-contract-form" class="modal-form" method="POST" action="/php/actions/update-contrat.php">
        <input type="hidden" name="id" value="<?= $contrat->getId() ?>" />
        <input type="hidden" name="action" value="update_info" />

        <div class="form-section">
          <h3>Informations du Contrat</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="edit-contract-nom">Nom *</label>
              <input type="text" id="edit-contract-nom" name="nom" required
                value="<?= htmlspecialchars($contrat->getNom()) ?>" />
            </div>
            <div class="form-group">
              <label for="edit-contract-type">Type *</label>
              <select id="edit-contract-type" name="type" required>
                <option value="Inclus" <?= $contrat->getType() === 'Inclus' ? 'selected' : '' ?>>Inclus</option>
                <option value="Facturable" <?= $contrat->getType() === 'Facturable' ? 'selected' : '' ?>>Facturable
                </option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="edit-contract-hours">Heures totales *</label>
              <input type="number" id="edit-contract-hours" name="heures_totales" required min="1"
                value="<?= $heuresTotales ?>" />
            </div>
            <div class="form-group">
              <label for="edit-contract-rate">Taux horaire (€) *</label>
              <input type="number" id="edit-contract-rate" name="taux_horaire" required min="0" step="0.01"
                value="<?= $tauxHoraire ?>" />
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Montant et Statut</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="edit-contract-amount">Montant total (€) *</label>
              <input type="number" id="edit-contract-amount" name="montant_total" required min="0" step="0.01"
                value="<?= $montantTotal ?>" />
            </div>
            <div class="form-group">
              <label for="edit-contract-statut">Statut *</label>
              <select id="edit-contract-statut" name="statut" required>
                <option value="actif" <?= $contrat->getStatut() === 'actif' ? 'selected' : '' ?>>Actif</option>
                <option value="inactif" <?= $contrat->getStatut() === 'inactif' ? 'selected' : '' ?>>Suspendu</option>
                <option value="termine" <?= $contrat->getStatut() === 'termine' ? 'selected' : '' ?>>Terminé</option>
              </select>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Période</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="edit-start-date">Date de début *</label>
              <input type="date" id="edit-start-date" name="date_debut" required
                value="<?= $contrat->getDateDebut() ?>" />
            </div>
            <div class="form-group">
              <label for="edit-end-date">Date de fin *</label>
              <input type="date" id="edit-end-date" name="date_fin" required value="<?= $contrat->getDateFin() ?>" />
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Conditions Particulières</h3>
          <div class="form-group">
            <label for="edit-contract-terms">Conditions et clauses</label>
            <textarea id="edit-contract-terms" name="conditions" rows="4"
              placeholder="Conditions spécifiques, clauses particulières..."><?= htmlspecialchars($contrat->getConditions() ?? '') ?></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" id="cancel-edit-modal">Annuler</button>
          <button type="submit" class="btn-submit">Enregistrer les modifications</button>
          <div class="form-error" id="edit-form-error">Veuillez remplir tous les champs obligatoires.</div>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Ajouter du Temps -->
  <div class="modal-overlay" id="modal-time-overlay">
    <div class="modal" id="add-time-modal">
      <div class="modal-header">
        <h2>Ajouter du Temps</h2>
        <button class="modal-close" id="close-time-modal">&times;</button>
      </div>
      <form id="add-time-form" class="modal-form" method="POST" action="/php/actions/update-contrat.php">
        <input type="hidden" name="id" value="<?= $contrat->getId() ?>" />
        <input type="hidden" name="action" value="add_time" />

        <div class="form-section">
          <h3>Informations de la Saisie</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="time-date">Date *</label>
              <input type="date" id="time-date" name="date_travail" required />
            </div>
            <div class="form-group">
              <label for="time-hours">Heures *</label>
              <input type="number" id="time-hours" name="duree" required min="0.25" step="0.25" placeholder="Ex: 2.5" />
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Collaborateur et Ticket</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="time-collaborator">Collaborateur *</label>
              <select id="time-collaborator" name="collaborateur_id" required>
                <option value="">Sélectionner un collaborateur</option>
                <?php foreach ($collaborateurs as $col): ?>
                  <option value="<?= $col['id'] ?>"><?= htmlspecialchars($col['name'] . ' ' . $col['surname']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="time-ticket">Ticket associé *</label>
              <select id="time-ticket" name="ticket_id" required>
                <option value="">Sélectionner un ticket</option>
                <?php foreach ($ticketsLies as $t): ?>
                  <option value="<?= $t->getId() ?>">#<?= $t->getId() ?> - <?= htmlspecialchars($t->getTitre()) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Description de la Tâche</h3>
          <div class="form-group">
            <label for="time-description">Description</label>
            <textarea id="time-description" name="commentaire" rows="3"
              placeholder="Décrivez brièvement le travail effectué..."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" id="cancel-time-modal">Annuler</button>
          <button type="submit" class="btn-submit">Ajouter le temps</button>
          <div class="form-error" id="time-form-error">Veuillez remplir tous les champs obligatoires.</div>
        </div>
      </form>
    </div>
  </div>

  <script src="../../js/form-validation.js"></script>
  <script src="./generic-contrat.js"></script>

  <footer>
    <p>&copy; 2026 Vector - Tous droits réservés</p>
  </footer>
</body>

</html>