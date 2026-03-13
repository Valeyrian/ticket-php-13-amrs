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
$allContrats = Contrat::getAll($pdo);
$allClients = Client::getAll($pdo);
$allProjets = Projet::getAll($pdo);

// Stats
$totalContrats = count($allContrats);
$contratsActifs = count(array_filter($allContrats, fn($c) => $c->isActif()));
$totalMontant = array_sum(array_map(fn($c) => $c->getMontantTotal(), array_filter($allContrats, fn($c) => $c->isActif())));
$totalHeuresRestantes = array_sum(array_map(fn($c) => $c->getHeuresRestantes(), array_filter($allContrats, fn($c) => $c->isActif())));
$expirentBientot = count(array_filter($allContrats, function ($c) {
  if (!$c->isActif() || !$c->getDateFin())
    return false;
  $fin = strtotime($c->getDateFin());
  $dans30j = strtotime('+30 days');
  return $fin <= $dans30j && $fin >= time();
}));

// Préparer les données par contrat
$contratsData = [];
foreach ($allContrats as $c) {
  $clients = $c->getClients($pdo);
  $clientNom = !empty($clients) ? $clients[0]['nom'] : '—';
  $clientInitials = !empty($clients) ? mb_strtoupper(mb_substr($clients[0]['nom'], 0, 2)) : '??';

  $pourcentage = $c->getPourcentageConsomme();
  $heuresText = $c->getHeuresConsommees() . 'h / ' . $c->getHeuresTotales() . 'h';

  // Statut visuel
  $statutClass = 'active';
  $statutLabel = 'Actif';
  if ($c->getStatut() === 'termine') {
    $statutClass = 'completed';
    $statutLabel = 'Terminé';
  } elseif ($c->getStatut() === 'inactif') {
    $statutClass = 'inactive';
    $statutLabel = 'Suspendu';
  } elseif ($pourcentage >= 90 && $c->isActif()) {
    $statutClass = 'warning';
    $statutLabel = 'Bientôt épuisé';
  }

  // Warning row?
  $rowClass = '';
  if ($statutClass === 'warning')
    $rowClass = 'warning-row';
  if ($statutClass === 'completed')
    $rowClass = 'completed-row';

  // Progress bar class
  $progressClass = '';
  if ($pourcentage >= 90)
    $progressClass = 'warning';
  if ($pourcentage >= 100)
    $progressClass = 'complete';

  $dateDebut = $c->getDateDebut() ? date('d/m/Y', strtotime($c->getDateDebut())) : '—';
  $dateFin = $c->getDateFin() ? date('d/m/Y', strtotime($c->getDateFin())) : '—';

  $contratsData[] = [
    'contrat' => $c,
    'clientNom' => $clientNom,
    'clientInitials' => $clientInitials,
    'pourcentage' => $pourcentage,
    'heuresText' => $heuresText,
    'statutClass' => $statutClass,
    'statutLabel' => $statutLabel,
    'rowClass' => $rowClass,
    'progressClass' => $progressClass,
    'dateDebut' => $dateDebut,
    'dateFin' => $dateFin,
  ];
}
?>
<!doctype html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./contrat-manager.css" />
  <title>Gestion des Contrats - Vector</title>
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

      <h1 class="dashboard-title">Gestion des Contrats</h1>

      <nav class="header-nav">
        <a href="/dashboard/admin/dashboard.php" class="nav-btn">Dashboard</a>
        <a href="/projects/admin/project-manager.php" class="nav-btn">Projets</a>
        <a href="/tickets/admin/ticket-manager.php" class="nav-btn">Tickets</a>
        <a href="/utilisateurs/admin/utilisateur-manager.php" class="nav-btn">Utilisateurs</a>
        <a href="/clients/admin/client-manager.html" class="nav-btn">Clients</a>
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
    <div class="manager-container">
      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <img src="/assets/contrat.png" alt="contrats" />
          </div>
          <div class="stat-info">
            <div class="stat-value"><?= $contratsActifs ?></div>
            <div class="stat-label">Contrats actifs</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <img src="/assets/chiffre-daffaires.png" alt="revenu" />
          </div>
          <div class="stat-info">
            <div class="stat-value"><?= number_format($totalMontant, 0, ',', ' ') ?> €</div>
            <div class="stat-label">CA en cours</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <img src="/assets/heures-douverture.png" alt="heures" />
          </div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalHeuresRestantes ?>h</div>
            <div class="stat-label">Heures disponibles</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <img src="/assets/avertissement.png" alt="expiration" />
          </div>
          <div class="stat-info">
            <div class="stat-value"><?= $expirentBientot ?></div>
            <div class="stat-label">Expirent bientôt</div>
          </div>
        </div>
      </div>

      <!-- Filters and Actions -->
      <div class="controls-section">
        <div class="filters">
          <input type="text" placeholder="Rechercher un contrat..." class="search-input" id="search-input" />
          <select class="filter-select" id="filter-statut">
            <option value="">Tous les statuts</option>
            <option value="actif">Actif</option>
            <option value="termine">Terminé</option>
            <option value="inactif">Suspendu</option>
          </select>
          <select class="filter-select" id="filter-client">
            <option value="">Tous les clients</option>
            <?php foreach ($allClients as $cl): ?>
              <option value="<?= htmlspecialchars($cl->getNom()) ?>"><?= htmlspecialchars($cl->getNom()) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn-add">Nouveau contrat</button>
      </div>

      <!-- Contracts Table -->
      <div class="table-container">
        <table class="contracts-table">
          <thead>
            <tr>
              <th>Contrat</th>
              <th>Client</th>
              <th>Montant</th>
              <th>Heures</th>
              <th>Début</th>
              <th>Fin</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($contratsData as $data):
              $c = $data['contrat'];
              ?>
              <tr class="<?= $data['rowClass'] ?>" data-statut="<?= $c->getStatut() ?>"
                data-client="<?= htmlspecialchars($data['clientNom']) ?>" data-name="<?= strtolower($c->getNom()) ?>">
                <td>
                  <a href="/contrats/admin/generic-contrat.php?id=<?= $c->getId() ?>" class="contract-link">
                    #CT<?= str_pad($c->getId(), 3, '0', STR_PAD_LEFT) ?>
                  </a>
                  <div style="font-size:0.8rem;color:var(--text-gray)"><?= htmlspecialchars($c->getNom()) ?></div>
                </td>
                <td>
                  <div class="client-cell">
                    <div class="company-logo-mini"><?= htmlspecialchars($data['clientInitials']) ?></div>
                    <span><?= htmlspecialchars($data['clientNom']) ?></span>
                  </div>
                </td>
                <td class="amount-cell"><?= number_format($c->getMontantTotal(), 0, ',', ' ') ?> €</td>
                <td>
                  <div class="hours-cell">
                    <div class="hours-text"><?= $data['heuresText'] ?></div>
                    <div class="mini-progress-bar <?= $data['progressClass'] ?>">
                      <div class="mini-progress-fill" style="width: <?= min($data['pourcentage'], 100) ?>%"></div>
                    </div>
                  </div>
                </td>
                <td><?= $data['dateDebut'] ?></td>
                <td><?= $data['dateFin'] ?></td>
                <td><span class="status-badge <?= $data['statutClass'] ?>"><?= $data['statutLabel'] ?></span></td>
                <td>
                  <div style="display:flex;gap:5px;align-items:center">
                    <a href="/contrats/admin/generic-contrat.php?id=<?= $c->getId() ?>" class="btn-view" title="Voir">
                      <img src="/assets/oeil.png" alt="voir" class="inline-icon" />
                    </a>
                    <form method="POST" action="/php/actions/delete-contrat.php" style="display:inline"
                      onsubmit="return confirm('Supprimer le contrat #CT<?= str_pad($c->getId(), 3, '0', STR_PAD_LEFT) ?> ?')">
                      <input type="hidden" name="id" value="<?= $c->getId() ?>" />
                      <button type="submit" class="btn-delete-small" title="Supprimer">
                        <img src="/assets/supprimer.png" alt="supprimer" />
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($contratsData)): ?>
              <tr>
                <td colspan="8" style="text-align:center;color:var(--text-gray);padding:30px">Aucun contrat trouvé.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Modal Créer un Contrat -->
  <div class="modal-overlay" id="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h2>Créer un Contrat</h2>
        <button class="modal-close">&times;</button>
      </div>
      <form class="modal-form" action="/php/actions/add-contrat.php" method="POST">
        <div class="form-section">
          <h3>Informations du Contrat</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="contract-nom">Nom du contrat *</label>
              <input type="text" id="contract-nom" name="nom" required placeholder="Ex: Contrat Annuel 2026" />
            </div>
            <div class="form-group">
              <label for="contract-type">Type *</label>
              <select id="contract-type" name="type" required>
                <option value="Inclus">Inclus</option>
                <option value="Facturable">Facturable</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="contract-client">Client *</label>
              <select id="contract-client" name="client_id" required>
                <option value="">Sélectionner un client</option>
                <?php foreach ($allClients as $cl): ?>
                  <option value="<?= $cl->getId() ?>"><?= htmlspecialchars($cl->getNom()) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="project-select">Projet associé</label>
              <select id="project-select" name="projet_id">
                <option value="">Aucun projet</option>
                <?php foreach ($allProjets as $p): ?>
                  <option value="<?= $p->getId() ?>"><?= htmlspecialchars($p->getNom()) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Heures et Tarification</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="contract-heures-totales">Heures totales *</label>
              <input type="number" id="contract-heures-totales" name="heures_totales" required min="0" step="0.5"
                placeholder="Ex: 50" />
            </div>
            <div class="form-group">
              <label for="contract-taux-horaire">Taux horaire (€/h) *</label>
              <input type="number" id="contract-taux-horaire" name="taux_horaire" required min="0" step="0.01"
                placeholder="Ex: 85" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Montant total (€)</label>
              <div id="contract-montant-total" class="computed-value">0.00 €</div>
              <input type="hidden" name="montant_total" id="contract-montant-total-hidden" value="0" />
            </div>
            <div class="form-group">
              <label for="contract-statut">Statut</label>
              <select id="contract-statut" name="statut">
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
                <option value="termine">Terminé</option>
              </select>
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Période</h3>
          <div class="form-row">
            <div class="form-group">
              <label for="contract-date-debut">Date de début *</label>
              <input type="date" id="contract-date-debut" name="date_debut" required />
            </div>
            <div class="form-group">
              <label for="contract-date-fin">Date de fin *</label>
              <input type="date" id="contract-date-fin" name="date_fin" required />
            </div>
          </div>
        </div>
        <div class="form-section">
          <h3>Conditions</h3>
          <div class="form-group">
            <label for="contract-conditions">Conditions du contrat</label>
            <textarea id="contract-conditions" name="conditions" rows="3"
              placeholder="Conditions particulières..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <div class="form-error">
            Veuillez remplir tous les champs obligatoires.
          </div>
          <button type="button" class="btn-cancel">Annuler</button>
          <button type="submit" class="btn-submit">Créer le contrat</button>
        </div>
      </form>
    </div>
  </div>

  <script src="../../js/form-validation.js"></script>
  <script src="./contrat-manager.js"></script>

  <footer>
    <p>&copy; 2026 Vector - Tous droits réservés</p>
  </footer>
</body>

</html>