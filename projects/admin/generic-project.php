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

// Récupérer le projet par ID
$projetId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$projet = $projetId ? Projet::getById($pdo, $projetId) : null;

if (!$projet) {
  header("Location: /projects/admin/project-manager.php?error=projet_introuvable");
  exit;
}

// Données liées
$client = $projet->getClient($pdo);
$collabs = $projet->getCollaborateurs($pdo);
$tickets = Ticket::getByProjetId($pdo, $projet->getId());
$contrats = Contrat::getByProjet($pdo, $projet->getId());
$contrat = !empty($contrats) ? $contrats[0] : null;

// Stats tickets
$nbTickets = count($tickets);
$nbOuverts = count(array_filter($tickets, fn($t) => $t->isOpen()));
$nbEnCours = count(array_filter($tickets, fn($t) => $t->getStatut() === 'en_cours'));
$nbTermines = count(array_filter($tickets, fn($t) => in_array($t->getStatut(), ['termine', 'valide', 'ferme'])));

// Heures
$tempsPasse = $projet->getTotalTempsPasse($pdo);
$tempsEstime = $projet->getTotalTempsEstime($pdo);
$heuresContrat = $contrat ? $contrat->getHeuresTotales() : 0;
$heuresConsommees = $contrat ? $contrat->getHeuresConsommees() : $tempsPasse;
$heuresRestantes = $contrat ? $contrat->getHeuresRestantes() : 0;
$tauxHoraire = $contrat ? $contrat->getTauxHoraire() : 0;
$pourcentage = $heuresContrat > 0 ? round(($heuresConsommees / $heuresContrat) * 100) : 0;

// Collaborateurs disponibles (pas déjà assignés)
$allUsers = User::getAll($pdo);
$allCollaborateurs = array_filter($allUsers, fn($u) => $u->getRole() === 'collaborateur');
$collabIds = array_map(fn($c) => $c['id'], $collabs);
$availableCollabs = array_filter($allCollaborateurs, fn($u) => !in_array($u->getId(), $collabIds));

// Format dates
function formatDateFr(?string $date): string
{
  if (!$date)
    return '-';
  $d = new DateTime($date);
  return $d->format('d/m/Y');
}

// Badge statut
$statusBadgeClass = $projet->isActif() ? 'active' : 'archived';
$statusBadgeText = $projet->isActif() ? 'Actif' : 'Archivé';

// Heures classes
$hoursClass = '';
if ($pourcentage >= 100)
  $hoursClass = 'text-danger';
elseif ($pourcentage >= 85)
  $hoursClass = 'text-warning';
else
  $hoursClass = 'text-success';
?>
<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./generic-project.css" />
  <title>Projet #P<?= str_pad($projet->getId(), 3, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($projet->getNom()) ?> -
    Vector</title>
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

      <h1 class="dashboard-title">Détail du Projet</h1>

      <nav class="header-nav">
        <a href="/dashboard/admin/dashboard.php" class="nav-btn">Dashboard</a>
        <a href="/projects/admin/project-manager.php" class="nav-btn">Projets</a>
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
    <div class="project-detail">
      <!-- En-tête du projet -->
      <div class="project-header-section">
        <div class="project-title-block">
          <div class="project-id">#P<?= str_pad($projet->getId(), 3, '0', STR_PAD_LEFT) ?></div>
          <h2 class="project-name"><?= htmlspecialchars($projet->getNom()) ?></h2>
          <div class="project-status-badge <?= $statusBadgeClass ?>"><?= $statusBadgeText ?></div>
        </div>
        <div class="project-actions">
          <?php if ($contrat): ?>
            <a href="/contrats/admin/generic-contrat.php?id=<?= $contrat->getId() ?>" class="btn-contract">
              <img src="/assets/contrat.png" alt="contrat" /> Gérer contrat
            </a>
          <?php endif; ?>
          <form method="POST" action="/php/actions/update-project.php" style="display:inline"
            onsubmit="return confirm('Archiver ce projet ?')">
            <input type="hidden" name="project_id" value="<?= $projet->getId() ?>" />
            <input type="hidden" name="action" value="archive" />
            <button type="submit" class="btn-delete">
              <img src="/assets/supprimer.png" alt="archiver" />
            </button>
          </form>
        </div>
      </div>

      <!-- Informations principales -->
      <div class="project-info-grid">
        <div class="info-card">
          <h3>Informations générales</h3>
          <div class="info-row">
            <span class="info-label">Client:</span>
            <span class="info-value"><?= $client ? htmlspecialchars($client->getNom()) : '-' ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Date de création:</span>
            <span class="info-value"><?= formatDateFr($projet->getDateCreation()) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Date de début:</span>
            <span class="info-value"><?= formatDateFr($projet->getDateDebut()) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Date de fin prévue:</span>
            <span class="info-value"><?= formatDateFr($projet->getDateFinPrevue()) ?></span>
          </div>
        </div>

        <div class="info-card">
          <h3>Contrat</h3>
          <?php if ($contrat): ?>
            <div class="info-row">
              <span class="info-label">Heures incluses:</span>
              <span class="info-value"><?= $heuresContrat ?>h</span>
            </div>
            <div class="info-row">
              <span class="info-label">Heures consommées:</span>
              <span class="info-value <?= $hoursClass ?>"><?= $heuresConsommees ?>h</span>
            </div>
            <div class="info-row">
              <span class="info-label">Heures restantes:</span>
              <span
                class="info-value <?= $heuresRestantes > 0 ? 'text-success' : 'text-danger' ?>"><?= $heuresRestantes ?>h</span>
            </div>
            <div class="info-row">
              <span class="info-label">Taux horaire supp:</span>
              <span class="info-value"><?= $tauxHoraire ?>€/h</span>
            </div>
            <div class="contract-progress">
              <div class="progress-bar">
                <div class="progress-fill <?= $pourcentage >= 100 ? 'exceeded' : ($pourcentage >= 85 ? 'warning' : '') ?>"
                  style="width: <?= min($pourcentage, 100) ?>%"></div>
              </div>
              <span class="progress-text"><?= $pourcentage ?>% consommé</span>
            </div>
          <?php else: ?>
            <p style="color:var(--text-muted)">Aucun contrat associé</p>
          <?php endif; ?>
        </div>

        <div class="info-card">
          <h3>Statistiques</h3>
          <div class="info-row">
            <span class="info-label">Tickets total:</span>
            <span class="info-value"><?= $nbTickets ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Tickets ouverts:</span>
            <span class="info-value"><?= $nbOuverts ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">En cours:</span>
            <span class="info-value"><?= $nbEnCours ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">Terminés:</span>
            <span class="info-value"><?= $nbTermines ?></span>
          </div>
        </div>
      </div>

      <!-- Description -->
      <form method="POST" action="/php/actions/update-project.php">
        <input type="hidden" name="project_id" value="<?= $projet->getId() ?>" />
        <input type="hidden" name="action" value="update_description" />
        <div class="project-section">
          <h3>Description du projet</h3>
          <textarea name="description" class="project-description"
            rows="6"><?= htmlspecialchars($projet->getDescription() ?? '') ?></textarea>
          <button type="submit" class="btn-secondary" style="margin-top:10px">
            <img src="/assets/editer.png" alt="modifier" /> Enregistrer la description
          </button>
        </div>
      </form>

      <!-- Équipe -->
      <div class="project-section">
        <div class="section-header">
          <h3>Équipe assignée</h3>
        </div>
        <div class="team-grid">
          <?php foreach ($collabs as $collab):
            $initials = strtoupper(mb_substr($collab['name'], 0, 1) . mb_substr($collab['surname'], 0, 1));
            $fullName = htmlspecialchars($collab['name'] . ' ' . $collab['surname']);
            // Compter les tickets et heures de ce collaborateur sur ce projet
            $collabTickets = 0;
            $collabHeures = 0;
            foreach ($tickets as $t) {
              $tCollabs = $t->getCollaborateurs($pdo);
              foreach ($tCollabs as $tc) {
                if ($tc['id'] == $collab['id']) {
                  $collabTickets++;
                  break;
                }
              }
            }
            ?>
            <div class="team-member-card">
              <div class="member-avatar"><?= $initials ?></div>
              <div class="member-info">
                <div class="member-name"><?= $fullName ?></div>
                <div class="member-role"><?= htmlspecialchars($collab['role'] ?? 'Collaborateur') ?></div>
                <div class="member-stats">
                  <span><?= $collabTickets ?> tickets</span>
                </div>
              </div>
              <form method="POST" action="/php/actions/update-project.php" style="display:inline"
                onsubmit="return confirm('Retirer <?= addslashes($fullName) ?> du projet ?')">
                <input type="hidden" name="project_id" value="<?= $projet->getId() ?>" />
                <input type="hidden" name="action" value="remove_collaborateur" />
                <input type="hidden" name="collaborateur_id" value="<?= $collab['id'] ?>" />
                <button type="submit" class="btn-remove-member" title="Retirer">
                  <img src="/assets/supprimer.png" alt="retirer" />
                </button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if (!empty($availableCollabs)): ?>
          <form method="POST" action="/php/actions/update-project.php"
            style="display:flex;gap:8px;align-items:center;margin-top:12px">
            <input type="hidden" name="project_id" value="<?= $projet->getId() ?>" />
            <input type="hidden" name="action" value="add_collaborateur" />
            <select name="collaborateur_id" class="add-collaborator-select" required>
              <option value="">-- Ajouter un collaborateur --</option>
              <?php foreach ($availableCollabs as $ac): ?>
                <option value="<?= $ac->getId() ?>"><?= htmlspecialchars($ac->getName() . ' ' . $ac->getSurname()) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-add">Ajouter</button>
          </form>
        <?php endif; ?>
      </div>

      <!-- Tickets du projet -->
      <div class="project-section">
        <div class="section-header">
          <h3>Tickets du projet</h3>
          <a href="/tickets/admin/ticket-manager.php" class="btn-add">Voir tous les tickets</a>
        </div>
        <div class="tickets-table-container">
          <table class="tickets-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Assigné à</th>
                <th>Priorité</th>
                <th>Statut</th>
                <th>Type</th>
                <th>Temps</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t):
                $tCollabs = $t->getCollaborateurs($pdo);
                // Priority
                $priorityMap = ['haute' => ['high', 'Haute'], 'moyenne' => ['medium', 'Moyenne'], 'basse' => ['low', 'Basse']];
                $pInfo = $priorityMap[$t->getPriorite()] ?? ['medium', $t->getPriorite()];
                // Status
                $statutMap = [
                  'nouveau' => ['nouveau', 'Nouveau'],
                  'en_cours' => ['progress', 'En cours'],
                  'en_attente_client' => ['pending', 'En attente'],
                  'termine' => ['completed', 'Terminé'],
                  'a_valider' => ['pending', 'À valider'],
                  'valide' => ['completed', 'Validé'],
                  'refuse' => ['refused', 'Refusé'],
                  'ferme' => ['completed', 'Fermé'],
                ];
                $sInfo = $statutMap[$t->getStatut()] ?? ['pending', $t->getStatut()];
                // Type
                $typeClass = $t->getType() === 'facturable' ? 'facturable' : 'inclus';
                $typeLabel = $t->getType() === 'facturable' ? 'Facturable' : 'Inclus';
                ?>
                <tr>
                  <td>
                    <a href="/tickets/admin/generic-ticket.php?id=<?= $t->getId() ?>"
                      class="ticket-link">#<?= $t->getId() ?></a>
                  </td>
                  <td class="ticket-title"><?= htmlspecialchars($t->getTitre()) ?></td>
                  <td>
                    <div class="collaborators-mini">
                      <?php foreach ($tCollabs as $tc):
                        $tcInit = strtoupper(mb_substr($tc['name'], 0, 1) . mb_substr($tc['surname'], 0, 1));
                        ?>
                        <div class="avatar-tiny" title="<?= htmlspecialchars($tc['name'] . ' ' . $tc['surname']) ?>">
                          <?= $tcInit ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </td>
                  <td><span class="priority-badge <?= $pInfo[0] ?>"><?= $pInfo[1] ?></span></td>
                  <td><span class="status-badge <?= $sInfo[0] ?>"><?= $sInfo[1] ?></span></td>
                  <td><span class="billing-badge <?= $typeClass ?>"><?= $typeLabel ?></span></td>
                  <td><?= $t->getTempsPasse() ?>h/<?= $t->getTempsEstime() ?>h</td>
                  <td>
                    <div class="action-buttons">
                      <a href="/tickets/admin/generic-ticket.php?id=<?= $t->getId() ?>" class="btn-edit"
                        title="Voir / Modifier">
                        <img src="/assets/editer.png" alt="modifier" />
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($tickets)): ?>
                <tr>
                  <td colspan="8" style="text-align:center;color:var(--text-muted);padding:20px">Aucun ticket pour ce
                    projet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Informations du projet modifiables -->
      <form method="POST" action="/php/actions/update-project.php">
        <input type="hidden" name="project_id" value="<?= $projet->getId() ?>" />
        <input type="hidden" name="action" value="update_info" />
        <div class="project-section">
          <h3>Modifier les informations</h3>
          <div class="form-row" style="display:flex;gap:16px;flex-wrap:wrap">
            <div class="form-group" style="flex:1;min-width:200px">
              <label>Nom du projet</label>
              <input type="text" name="nom" value="<?= htmlspecialchars($projet->getNom()) ?>" required
                class="form-control" />
            </div>
            <div class="form-group" style="flex:0 0 150px">
              <label>Statut</label>
              <select name="statut" class="form-control">
                <option value="actif" <?= $projet->getStatut() === 'actif' ? 'selected' : '' ?>>Actif</option>
                <option value="archive" <?= $projet->getStatut() === 'archive' ? 'selected' : '' ?>>Archivé</option>
              </select>
            </div>
          </div>
          <div class="form-row" style="display:flex;gap:16px;flex-wrap:wrap;margin-top:12px">
            <div class="form-group" style="flex:1;min-width:150px">
              <label>Date de début</label>
              <input type="date" name="date_debut" value="<?= $projet->getDateDebut() ?? '' ?>" class="form-control" />
            </div>
            <div class="form-group" style="flex:1;min-width:150px">
              <label>Date de fin prévue</label>
              <input type="date" name="date_fin_prevue" value="<?= $projet->getDateFinPrevue() ?? '' ?>"
                class="form-control" />
            </div>
          </div>
          <button type="submit" class="btn-primary" style="margin-top:12px">Enregistrer les modifications</button>
        </div>
      </form>

      <!-- Historique du projet -->
      <div class="project-section">
        <h3>Historique du projet</h3>
        <div class="timeline">
          <?php
          // Construire l'historique à partir des tickets
          $historyItems = [];
          if ($projet->getDateCreation()) {
            $historyItems[] = [
              'date' => $projet->getDateCreation(),
              'text' => '<strong>Projet créé</strong>',
            ];
          }
          foreach ($tickets as $t) {
            if ($t->getDateCreation()) {
              $historyItems[] = [
                'date' => $t->getDateCreation(),
                'text' => 'Ticket <strong>#' . $t->getId() . '</strong> "' . htmlspecialchars($t->getTitre()) . '" créé',
              ];
            }
          }
          usort($historyItems, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
          $historyItems = array_slice($historyItems, 0, 10);
          ?>
          <?php foreach ($historyItems as $item):
            $itemDate = new DateTime($item['date']);
            ?>
            <div class="timeline-item">
              <div class="timeline-date"><?= $itemDate->format('d/m/Y - H:i') ?></div>
              <div class="timeline-content">
                <div class="timeline-icon"> <img src="/assets/oeil.png" alt="timeline-icon"></div>
                <div class="timeline-text"><?= $item['text'] ?></div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($historyItems)): ?>
            <p style="color:var(--text-muted)">Aucun historique.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <script src="../../js/form-validation.js"></script>
  <script src="./generic-project.js"></script>

  <footer>
    <p>&copy; 2026 Vector - Tous droits réservés</p>
  </footer>
</body>

</html>